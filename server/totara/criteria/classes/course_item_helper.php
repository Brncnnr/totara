<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_criteria
 */

namespace totara_criteria;


use core\orm\collection;
use core\orm\entity\repository;
use core\orm\query\builder;
use totara_criteria\entity\criteria_item as criteria_item_entity;
use totara_criteria\entity\criteria_item_record as criteria_item_record_entity;
use totara_criteria\entity\criterion as criterion_entity;
use totara_criteria\hook\criteria_achievement_changed;
use totara_criteria\hook\criteria_validity_changed;

class course_item_helper {

    /**
     * Course completion records were updated. We need to make sure competency achievement is reaggregated
     *
     * @param array $user_courses Array of courses per user. Key is user_id, value = array of course ids
     * @param string|null $plugin_type Used for criteria filtering if provided
     */
    public static function course_completions_updated(array $user_courses, ?string $plugin_type = null) {

        // As the criterion has no knowledge whether this user's satisfaction of the criteria is to be tracked,
        // it simply generates an criteria_achievement_changed event with the relevant criterion ids for each user.
        // Modules that use these criteria are responsible for initiating the relevant processes to create/update
        // the item_record(s) for this user

        // Find a list of all unique course ids
        $course_ids = array_unique(array_merge(...$user_courses));

        // Map course_ids to criterion_ids
        $course_criteria = array_fill_keys($course_ids, []);
        $criteria_items = self::get_criteria_items_from_course_ids($course_ids, $plugin_type);
        foreach ($criteria_items as $criteria_item) {
            $course_criteria[$criteria_item->item_id][] = $criteria_item->criterion_id;
        }

        // Build the list of criterion_ids per user
        $user_criteria_ids = [];
        foreach ($user_courses as $user_id => $course_ids) {
            $ids = [];
            foreach ($course_ids as $course_id) {
                if (!empty($course_criteria[$course_id])) {
                    array_push($ids, ...$course_criteria[$course_id]);
                }
            }

            if (!empty($ids)) {
                $user_criteria_ids[$user_id] = array_unique($ids);
            }
        }

        if (!empty($user_criteria_ids)) {
            $hook = new criteria_achievement_changed($user_criteria_ids);
            $hook->execute();
        }
    }

    /**
     * All course completion records of the ccourse were reset
     *
     * @param int $course_id
     * @param string|null $plugin_type Used for criteria filtering if provided
     */
    public static function course_completions_reset(int $course_id, ?string $plugin_type = null) {

        // We find all criteria that refer to this course as well as all users for whom we already have an item_record
        // We then generate a criteria_achievement_changed event for affected users and criteria

        $item_records = self::get_criteria_item_records_from_course($course_id, $plugin_type);
        if (!$item_records->count()) {
            return;
        }

        // Build the list of criterion_ids per user
        $user_criteria_ids = [];
        foreach ($item_records as $item_record) {
            if (!isset($user_criteria_ids[$item_record->user_id])) {
                $user_criteria_ids[$item_record->user_id] = [$item_record->item->criterion_id];
            } else if (!in_array($item_record->item->criterion_id, $user_criteria_ids[$item_record->user_id])) {
                $user_criteria_ids[$item_record->user_id][] = $item_record->item->criterion_id;
            }
        }

        if (!empty($user_criteria_ids)) {
            $hook = new criteria_achievement_changed($user_criteria_ids);
            $hook->execute();
        }
    }

    /**
     * When a course is deleted, we re-evaluate the validity of all criteria.
     * A validity_changed hook is executed with the ids of all criteria that were affected
     *
     * @param int $course_id
     * @param string|null $plugin_type Used for criteria filtering if provided
     */
    public static function course_deleted(int $course_id, ?string $plugin_type = null) {
        $criteria = self::get_criteria_from_course($course_id, false, $plugin_type);

        if (!$criteria->count()) {
            return;
        }

        $affected_criteria = [];
        foreach ($criteria as $criterion_entity) {
            $criterion = criterion_factory::fetch_from_entity($criterion_entity);
            // Not checking anything here - this criterion refers to a deleted course
            // If already marked as invalid - nothing to do
            if ($criterion_entity->valid) {
                $criterion->set_valid(false);
                $criterion->save_valid();

                $affected_criteria[] = $criterion_entity->id;
            }
        }

        if (!empty($affected_criteria)) {
            $hook = new criteria_validity_changed($affected_criteria);
            $hook->execute();
        }
    }

    /**
     * When a course is restored, we update all items with the original course id to restored course id.
     * Then we re-evaluate the validity of all criteria using this course.
     * A validity_changed hook is executed with the ids of all criteria that were affected
     *
     * @param int original_course_id
     * @param int restored_course_id
     * @param string|null $plugin_type Used for criteria filtering if provided
     */
    public static function course_restored(int $original_course_id, int $restored_course_id, ?string $plugin_type = null) {
        $criteria = self::get_criteria_from_course($original_course_id, true, $plugin_type);

        if (!$criteria->count()) {
            return;
        }

        $affected_criteria = [];
        foreach ($criteria as $criterion_entity) {
            // If only course content is restored, the original and restored course ids are the same
            // In this case we don't need to update the items
            // However, if a deleted course is restored, the course id changes.
            // In this case we need to update all items to refer the restored id
            if ($original_course_id != $restored_course_id) {
                foreach ($criterion_entity->items as $item) {
                    if ($item->item_type == 'course' && $item->item_id == $original_course_id) {
                        $item->item_id = $restored_course_id;
                        $item->save();
                    }
                }
            }

            $criterion = criterion_factory::fetch_from_entity($criterion_entity);
            // Validate and save validity
            $criterion->validate();
            if ($criterion->is_valid() != $criterion_entity->valid) {
                $criterion->save_valid();
                $affected_criteria[] = $criterion_entity->id;
            }
        }

        if (!empty($affected_criteria)) {
            $hook = new criteria_validity_changed($affected_criteria);
            $hook->execute();
        }
    }

    /**
     * When the course settings are changed we re-evaluate the validity of all criteria that uses the course
     * as the change may have included a change to the course's completion tracking
     * A validity_changed hook is executed with the ids of all criteria that were affected
     *
     * @param int $course_id
     * @param string|null $plugin_type Used for criteria filtering if provided
     */
    public static function course_settings_changed(int $course_id, ?string $plugin_type = null) {
        $criteria = self::get_criteria_from_course($course_id, false, $plugin_type);

        if (!$criteria->count()) {
            return;
        }

        $affected_criteria = [];
        foreach ($criteria as $criterion_entity) {
            $criterion = criterion_factory::fetch_from_entity($criterion_entity);

            // Validate and save validity
            $criterion->validate();
            if ($criterion->is_valid() != $criterion_entity->valid) {
                $criterion->save_valid();
                $affected_criteria[] = $criterion_entity->id;
            }
        }

        if (!empty($affected_criteria)) {
            $hook = new criteria_validity_changed($affected_criteria);
            $hook->execute();
        }
    }

    /**
     * When the global 'enablecompletion' setting is changed, we re-evaluate the validity of
     * all criteria with course items
     *
     * @param string|null $plugin_type Used for criteria filtering if provided
     */
    public static function global_setting_changed(?string $plugin_type = null) {
        global $CFG;

        $item_builder = builder::table(criteria_item_entity::TABLE)
            ->where_field('criterion_id', 'c.id')
            ->where('item_type', 'course');

        $criteria = criterion_entity::repository()
            ->as('c')
            ->where_exists($item_builder)
            ->when(!is_null($plugin_type), function (repository $repository) use ($plugin_type) {
                $repository->where('c.plugin_type', $plugin_type);
            })
            ->get();

        $affected_criteria = [];
        foreach ($criteria as $criterion_entity) {
            $criterion = criterion_factory::fetch_from_entity($criterion_entity);
            if (!$CFG->enablecompletion) {
                // Now disabled - no need to valid, we simply need to mark all existing 'valid' criteria as invalid
                if ($criterion_entity->valid) {
                    $criterion->set_valid(false);
                    $criterion->save_valid();

                    $affected_criteria[] = $criterion_entity->id;
                }
            } else {
                // Now enabled, we need to re-evaluate the criteria
                $criterion->validate();
                if ($criterion->is_valid() != $criterion_entity->valid) {
                    $criterion->save_valid();
                    $affected_criteria[] = $criterion_entity->id;
                }
            }
        }

        if (!empty($affected_criteria)) {
            $hook = new criteria_validity_changed($affected_criteria);
            $hook->execute();
        }
    }


    /**
     * Find all criteria items for completion of this course
     *
     * @param int[] $ids
     * @param string|null $plugin_type
     * @return collection
     */
    private static function get_criteria_items_from_course_ids(array $ids, ?string $plugin_type = null): collection {
        return criteria_item_entity::repository()
            ->where('item_type', 'course')
            ->where('item_id', $ids)
            ->when(!is_null($plugin_type), function (repository $repository) use ($plugin_type) {
                $repository->join([criterion_entity::TABLE, 'criterion'], 'criterion_id', 'id');
                $repository->where('criterion.plugin_type', $plugin_type);
            })
            ->get();
    }

    /**
     * Find all criteria that has contains the specified course as item
     * @param int $course_id
     * @param bool $with_items
     * @param string|null $plugin_type Used for criteria filtering if provided
     * @return collection
     */
    private static function get_criteria_from_course(int $course_id, bool $with_items, ?string $plugin_type): collection {
        return criterion_entity::repository()
            ->as('c')
            ->from_item_ids('course', $course_id)
            ->when(!is_null($plugin_type), function (repository $repository) use ($plugin_type) {
                $repository->where('plugin_type', $plugin_type);
            })
            ->when($with_items, function (repository $repository) {
                $repository->with('items');
            })
            ->get();
    }

    /**
     * Find all criteria item records for completion of this course
     *
     * @param int $course_id
     * @param string|null $plugin_type
     * @return collection
     */
    private static function get_criteria_item_records_from_course(int $course_id, ?string $plugin_type = null): collection {
        return criteria_item_record_entity::repository()
            ->as('cir')
            ->join([criteria_item_entity::TABLE, 'item'], 'cir.criterion_item_id', 'item.id')
            ->when(!is_null($plugin_type), function (repository $repository) use ($plugin_type) {
                $repository->join([criterion_entity::TABLE, 'criterion'], 'item.criterion_id', 'criterion.id');
                $repository->where('criterion.plugin_type', $plugin_type);
            })
            ->where('item.item_type', 'course')
            ->where('item.item_id', $course_id)
            ->get();
    }


}
