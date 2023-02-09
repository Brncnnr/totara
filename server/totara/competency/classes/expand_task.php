<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency;

use context_system;
use core\entity\expandable;
use core\orm\collection;
use core\orm\entity\entity;
use core\orm\query\builder;
use totara_competency\entity\assignment;
use totara_competency\entity\competency_assignment_user;
use totara_competency\entity\competency_assignment_user_repository;
use totara_competency\event\assignment_user_assigned_bulk;
use totara_competency\event\assignment_user_unassigned;
use totara_competency\models\assignment as assignment_model;

class expand_task {

    /**
     * @var \moodle_database
     */
    protected $db;

    private $cache = [];

    public function __construct(\moodle_database $db) {
        $this->db = $db;
    }

    /**
     * Expand all active assignments
     */
    public function expand_all() {
        // clean up all orphaned records (archived or deleted assignments)
        competency_assignment_user_repository::remove_orphaned_records();

        $assignments = assignment::repository()
            ->filter_by_active()
            ->filter_by_expand()
            ->order_by('id')
            ->get_lazy();

        foreach ($assignments as $assignment) {
            $this->expand_assignment($assignment);
        }
    }

    /**
     * Expand only given assignments. Non existing or inactive assignments will be ignored.
     *
     * @param array $assignment_ids
     */
    public function expand_multiple(array $assignment_ids) {
        // clean up all orphaned records (archived or deleted assignments)
        competency_assignment_user_repository::remove_orphaned_records();

        $assignment_ids = $this->sanitise_ids($assignment_ids);
        if (!empty($assignment_ids)) {
            $assignments = assignment::repository()
                ->filter_by_ids($assignment_ids)
                ->filter_by_active()
                ->filter_by_expand()
                ->get();
            foreach ($assignments as $assignment) {
                $this->expand_assignment($assignment);
            }
        }
    }

    /**
     * Expand a single assignment with the given id. Missing assignment is ignored.
     *
     * @param int $assignment_id
     */
    public function expand_single(int $assignment_id) {
        // clean up all orphaned records (archived or deleted assignments)
        competency_assignment_user_repository::remove_orphaned_records();

        /** @var assignment $assignment */
        $assignment = assignment::repository()
            ->where('id', $assignment_id)
            ->where('status', assignment::STATUS_ACTIVE)
            ->filter_by_expand()
            ->one();

        if ($assignment) {
            $this->expand_assignment($assignment);
        }
    }

    /**
     * Expand the given assignment.
     *
     * @param assignment $assignment
     */
    private function expand_assignment(assignment $assignment) {
        // We ignore missing assignments to not cause failing tasks.
        // It can happen that an assignment was deleted before the task is run.
        // On the next run the assignments will be picked up again.
        if (!$assignment->exists()) {
            return;
        }

        // Reset the expand flag early as something could change it in between
        $assignment->expand = false;
        $assignment->save();

        // load all current source targets relations of the assignment
        // to avoid more requests to the database when checking if entry
        // already exists.
        $current_entries = $this->load_current_entries($assignment->id);

        $user_ids = $this->get_expanded_users($assignment->user_group_type, $assignment->user_group_id);

        $new_user_ids = array_diff($user_ids, $current_entries->pluck('user_id'));
        if (!empty($new_user_ids)) {
            builder::get_db()->transaction(function () use ($new_user_ids, $assignment) {
                $to_create = [];
                $count = 0;

                foreach ($new_user_ids as $user_id) {
                    // If the entry does not exist yet, create it now otherwise just ignore it
                    $competency_user = [
                        'assignment_id' => $assignment->id,
                        'competency_id' => $assignment->competency_id,
                        'user_id' => $user_id,
                        'created_at' => time(),
                        'updated_at' => time(),
                    ];

                    $to_create[] = (object)$competency_user;

                    $count++;

                    // Make sure we inset multiple rows but only to a certain limit
                    // to limit memory consumption and prevent leaks
                    if ($count == BATCH_INSERT_MAX_ROW_COUNT) {
                        $this->assign_bulk($assignment, $to_create);
                        $to_create = [];
                        $count = 0;
                    }
                }

                // Insert the last batch
                if (!empty($to_create)) {
                    $this->assign_bulk($assignment, $to_create);
                }
            });
        }

        // Delete all records which were not processed within this run, means they were removed from the source or targets
        $this->unassign_users($current_entries, $user_ids);
    }

    private function assign_bulk(assignment $assignment, array $to_create) {
        builder::get_db()->insert_records('totara_competency_assignment_users', $to_create);
        $event_user_ids = array_column($to_create, 'user_id');
        assignment_user_assigned_bulk::create_from_assignment_users(
            $assignment->id,
            $assignment->competency_id,
            $event_user_ids,
            $assignment->type
        )->trigger();
    }

    /**
     * Load all current entries for the given assignment, key uniquely identifies the source target
     *
     * @param int $assignment_id
     * @return collection|competency_assignment_user[]
     */
    private function load_current_entries(int $assignment_id): collection {
        return competency_assignment_user::repository()
            ->filter_by_assignment_id($assignment_id)
            ->get();
    }

    /**
     * Expand the user group
     *
     * @param string $user_group_type something like 'cohort', 'position', 'organisation', 'user'
     * @param int $user_group_id
     * @return array
     */
    private function get_expanded_users(string $user_group_type, int $user_group_id): array {
        if (!$expanded_records = $this->get_cache_entry($user_group_type, $user_group_id)) {
            $expanded_records = $this->expand_entity($user_group_type, $user_group_id);
            $this->add_cache_entry($user_group_type, $user_group_id, $expanded_records);
        }
        return $expanded_records;
    }

    private function has_cache_entry(string $cache_type, int $id): bool {
        return isset($this->cache[$cache_type][$id]);
    }

    private function add_cache_entry(string $cache_type, int $id, array $record) {
        if (!isset($this->cache[$cache_type])) {
            $this->cache[$cache_type] = [];
        }
        $this->cache[$cache_type][$id] = $record;
    }

    private function get_cache_entry(string $cache_type, int $id): array {
        $cache_entry = [];
        if ($this->has_cache_entry($cache_type, $id)) {
            $cache_entry = $this->cache[$cache_type][$id];
        }
        return $cache_entry;
    }

    /**
     * Expand the entity, if possible, to individual entries. for example expand a cohort to it's members
     *
     * @param string $type
     * @param int $target_id
     * @return array
     */
    private function expand_entity(string $type, int $target_id): array {
        $class_name = assignment_model::get_entity_class_by_user_group_type($type);
        if (is_subclass_of($class_name, expandable::class)) {
            if (!is_subclass_of($class_name, entity::class)) {
                throw new \coding_exception('Currently only entities can be expanded');
            }
            // Try to load the user group entity to make sure it's there
            /** @var expandable $entity */
            $entity = $class_name::repository()->find($target_id);
            if (!$entity) {
                // User group got probably deleted
                // in this case return empty result which will trigger unassigning
                // of all users still assigned to that group
                return [];
            }
            return $entity->expand(context_system::instance());
        }
        return [$target_id];
    }

    /**
     * Unassign all records which are not in any user group anymore.
     *
     * @param collection $current_records keys are identifier, values are entities
     * @param array $new_user_ids newly determined user ids which should be in the group now
     */
    private function unassign_users(collection $current_records, array $new_user_ids) {
        $records_to_delete = $current_records->filter(
            function (competency_assignment_user $assignment_user) use ($new_user_ids) {
                return !in_array($assignment_user->user_id, $new_user_ids);
            }
        );

        if ($records_to_delete->count() > 0) {
            builder::get_db()->transaction(function () use ($records_to_delete) {
                competency_assignment_user::repository()
                    ->where('id', $records_to_delete->pluck('id'))
                    ->delete();

                /** @var competency_assignment_user $assignment_user */
                foreach ($records_to_delete as $assignment_user) {
                    // Trigger event for all affected entries
                    $event = assignment_user_unassigned::create_from_assignment_user($assignment_user);
                    $event->trigger();
                }
            });
        }
    }

    /**
     * Sanitize given ids, leave only proper positive numbers
     *
     * @param array $ids
     * @return array
     */
    private function sanitise_ids(array $ids): array {
        $ids = array_filter(
            $ids,
            function ($id) {
                return is_numeric($id) && $id > 0;
            }
        );
        return array_map('intval', $ids);
    }
}