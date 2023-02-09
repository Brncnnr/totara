<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @package mod_perform
 */

namespace mod_perform\task\service;

use coding_exception;
use core\orm\collection;
use core\orm\entity\repository;
use core\orm\query\builder;
use mod_perform\entity\activity\manual_relationship_selection;
use mod_perform\entity\activity\manual_relationship_selection_progress;
use mod_perform\entity\activity\manual_relationship_selector;
use mod_perform\entity\activity\subject_instance;
use mod_perform\models\activity\activity;
use mod_perform\state\subject_instance\closed;
use mod_perform\state\subject_instance\pending;
use mod_perform\totara_notification\resolver\participant_selection_resolver;
use stdClass;
use totara_core\entity\relationship;
use totara_core\relationship\helpers\relationship_collection_manager;
use totara_notification\external_helper;

class manual_participant_progress {

    /** @var relationship_collection_manager|null */
    protected $relationship_manager = null;

    /** @var array */
    protected $selectors_to_insert = [];

    /** @var array */
    protected $subject_instances_to_process = [];

    public function generate() {
        $pending_subject_instances = $this->load_pending_subject_instances();
        $this->relationship_manager = $this->prepare_relationship_manager($pending_subject_instances);

        foreach ($pending_subject_instances as $subject_instance) {
            // If the subject instance already has progress records sync the users,
            // making sure all current relationships have the current users in there
            // i.e. if a manager of a user changed
            if ($subject_instance->manual_relationship_selection_progress
                && count($subject_instance->manual_relationship_selection_progress) > 0
            ) {
                $this->sync_progress($subject_instance);
                continue;
            }

            $manual_relationships = $this->get_manual_relationships($subject_instance);

            $this->create_progress_for_subject_instance($subject_instance, $manual_relationships);
        }

        // At the end add all the selectors determined before with the least amount of queries
        if (!empty($this->selectors_to_insert)) {
            builder::get_db()->insert_records_via_batch(manual_relationship_selector::TABLE, $this->selectors_to_insert);
        }
    }

    /**
     * Load all subject instance which are pending
     *
     * @return collection<subject_instance>
     */
    private function load_pending_subject_instances(): collection {
        // Use eager loading to reduce number of queries in case we have a lot of subject instances to process
        return subject_instance::repository()
            ->with([
                'manual_relationship_selection_progress' => function (repository $repository) {
                    $repository->with('manual_relationship_selection')
                        ->with('manual_relationship_selectors');
                }
            ])
            ->with([
                'track.activity' => function (repository $repository) {
                    $repository->with('sections.manual_relationships')
                        ->with('manual_relation_selection');
                }
            ])
            ->where('status', pending::get_code())
            ->where('availability', '<>', closed::get_code())
            ->get();
    }

    /**
     * Prepare the relationship manager with preloading all relationships we need for the given subject instances
     *
     * @param collection|subject_instance[] $subject_instances
     * @return relationship_collection_manager|null
     */
    private function prepare_relationship_manager(collection $subject_instances): ?relationship_collection_manager {
        if ($subject_instances->count() === 0) {
            return null;
        }

        $relationship_ids = [];
        foreach ($subject_instances as $subject_instance) {
            // Get the manual_relation_selection records for this activity
            $manual_relation_selections = $subject_instance->track->activity->manual_relation_selection;
            if ($manual_relation_selections->count() === 0) {
                throw new coding_exception(
                    'Missing manual relationship selection records for activity ' . $subject_instance->track->activity_id
                );
            }

            $relationship_ids[] = $manual_relation_selections->pluck('selector_relationship_id');
        }
        $relationship_ids = array_unique(array_merge(...$relationship_ids));
        if (empty($relationship_ids)) {
            throw new coding_exception('Missing manual relationship selection records');
        }

        return new relationship_collection_manager($relationship_ids);
    }

    /**
     * Add missing users and delete users which are not in the respective relations any more
     *
     * @param subject_instance $subject_instance
     */
    private function sync_progress(subject_instance $subject_instance): void {
        $relationship_args = $this->get_args_for_resolving_relationships($subject_instance);

        $now = time();
        foreach ($subject_instance->manual_relationship_selection_progress as $progress) {
            // If this is already done leave it as is
            if ($progress->status) {
                continue;
            }

            $selector_relationship_id = $progress->manual_relationship_selection->selector_relationship_id;

            // Get the users which should be there
            $expected_users = $this->relationship_manager->get_users_for_relationships(
                $relationship_args,
                [$selector_relationship_id]
            );

            $expected_user_ids = array_map(
                function ($user_dto) {
                    return $user_dto->get_user_id();
                },
                $expected_users[$selector_relationship_id]
            );

            // Get the current users
            $current_user_ids = $progress->manual_relationship_selectors->pluck('user_id');

            // Work out who to add
            $user_ids_to_add = array_diff($expected_user_ids, $current_user_ids);
            if (!empty($user_ids_to_add)) {
                $this->subject_instances_to_process[$subject_instance->id] = $subject_instance;

                foreach ($user_ids_to_add as $user_id) {
                    $selector = new stdClass();
                    $selector->manual_relation_select_progress_id = $progress->id;
                    $selector->user_id = $user_id;
                    $selector->created_at = $now;

                    $this->selectors_to_insert[] = $selector;
                }
            }
        }
    }

    /**
     * @param subject_instance $subject_instance
     * @return array
     */
    private function get_args_for_resolving_relationships(subject_instance $subject_instance): array {
        $args = ['user_id' => $subject_instance->subject_user_id];
        if (!empty($subject_instance->job_assignment_id)) {
            $args['job_assignment_id'] = $subject_instance->job_assignment_id;
        }
        return $args;
    }

    /**
     * Get manual relationships if this activity has them
     *
     * @param subject_instance $subject_instance
     * @return collection<relationship>
     */
    private function get_manual_relationships(subject_instance $subject_instance): collection {
        $sections = $subject_instance->track->activity->sections;

        // Make the result unique
        $result = [];
        foreach ($sections as $section) {
            $manual_relationships = $section->manual_relationships;
            if ($manual_relationships && count($manual_relationships) > 0) {
                foreach ($manual_relationships as $manual_relationship) {
                    $result[$manual_relationship->id] = $manual_relationship;
                }
            }
        }

        return collection::new($result);
    }

    /**
     * Create progress records for given manual relationships
     *
     * @param subject_instance $subject_instance
     * @param collection|relationship[] $manual_relationships
     */
    private function create_progress_for_subject_instance(
        subject_instance $subject_instance,
        collection $manual_relationships
    ): void {
        if ($manual_relationships->count() === 0) {
            return;
        }

        // Get the manual_relation_selection records for this activity
        $manual_relation_selections = $subject_instance->track->activity->manual_relation_selection;
        $relationship_args = $this->get_args_for_resolving_relationships($subject_instance);

        $created_at = time();
        foreach ($manual_relationships as $manual_relationship) {
            // Make sure it is really a manual one
            if ($manual_relationship->type != relationship::TYPE_MANUAL) {
                continue;
            }

            // Get the id of the manual_relation_selection record related to this relationship
            /** @var manual_relationship_selection $manual_relation_selection */
            $manual_relation_selection = $manual_relation_selections->find('manual_relationship_id', $manual_relationship->id);
            if (!$manual_relation_selection) {
                throw new coding_exception(sprintf(
                    'No manual_relation_selection record found for relationship id %d in activity %d',
                    $manual_relationship->id,
                    $subject_instance->track->activity_id
                ));
            }

            // Create one progress record for this relationship / subject_instance combo
            $progress = new manual_relationship_selection_progress();
            $progress->subject_instance_id = $subject_instance->id;
            $progress->manual_relation_selection_id = $manual_relation_selection->id;
            $progress->status = 0;
            $progress->save();

            // Get the users for this relationship and create one selector record for each user
            $user_dtos = $this->relationship_manager->get_users_for_relationships(
                $relationship_args,
                [$manual_relation_selection->selector_relationship_id]
            );

            foreach ($user_dtos[$manual_relation_selection->selector_relationship_id] as $user_dto) {
                $selector = new stdClass();
                $selector->manual_relation_select_progress_id = $progress->id;
                $selector->user_id = $user_dto->get_user_id();
                $selector->created_at = $created_at;

                $this->selectors_to_insert[] = $selector;
            }

            $this->subject_instances_to_process[$subject_instance->id] = $subject_instance;
        }
    }

}
