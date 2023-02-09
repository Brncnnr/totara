<?php
/*
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\watcher;

use container_perform\perform;
use context_user;
use core\orm\entity\repository;
use core\orm\query\field;
use core\tenant_orm_helper;
use core_user\hook\allow_view_profile;
use core_user\hook\allow_view_profile_field;
use core_user\profile\display_setting;
use mod_perform\entity\activity\activity as activity_entity;
use mod_perform\entity\activity\manual_relationship_selection;
use mod_perform\entity\activity\manual_relationship_selection_progress;
use mod_perform\entity\activity\manual_relationship_selector;
use mod_perform\entity\activity\participant_instance;
use mod_perform\entity\activity\subject_static_instance;
use mod_perform\util;
use totara_core\advanced_feature;
use totara_core\hook\base;

class core_user {

    /**
     * Cache for storing already resolved lookups.
     *
     * @var array
     */
    private static $resolution_cache = [];

    /**
     * Clear the static cache. Mainly used by unit testing.
     *
     * @return void
     */
    public static function clear_resolution_cache(): void {
        self::$resolution_cache = [];
    }

    /**
     * User access hook to check if one user can view another user's profile field in the context of mod perform.
     *
     * @param allow_view_profile_field $hook
     */
    public static function allow_view_profile_field(allow_view_profile_field $hook): void {
        if ($hook->has_permission()) {
            return;
        }

        if (!advanced_feature::is_enabled('performance_activities')) {
            return;
        }

        // Ignore anything other than perform containers
        $course = $hook->get_course();
        if (!$course || $course->containertype !== perform::get_type()) {
            return;
        }

        // Handle site admins explicitly (performance optimisation)
        if (is_siteadmin()) {
            $hook->give_permission();
            return;
        }

        // Check for any user data which is required specifically for perform (which may
        // or may not have overlap with the user profile card fields below).
        if ($hook->field == 'fullname'
            || in_array($hook->field, display_setting::get_display_fields())
            || in_array($hook->field, display_setting::get_default_display_picture_fields())
        ) {
            if (self::can_view_user($hook)) {
                $hook->give_permission();
                return;
            }
        }

        // If the field is one required to display a user profile card and hasn't already been granted
        // above then check if the viewer is in any situation where they need to be able to select from
        // all (tenant) users.
        if (in_array($hook->field, display_setting::get_display_fields())
            || in_array($hook->field, display_setting::get_display_picture_fields())
        ) {
            if (self::can_select_any_user($hook)) {
                $hook->give_permission();
                return;
            }
        }

        return;
    }

    /**
     * User access hook to check if one user can select any (tenant) user in the context of mod perform.
     *
     * @param base|allow_view_profile|allow_view_profile_field $hook
     * @return bool
     */
    private static function can_select_any_user(base $hook): bool {
        if (self::is_involved_in_manual_instance_progress($hook)) {
            return true;
        }

        if (util::can_potentially_manage_participants($hook->viewing_user_id)) {
            return true;
        }

        return false;
    }


    /**
     * User access hook to check if one user can view another users profile data in the context of mod perform.
     *
     * @param base|allow_view_profile|allow_view_profile_field $hook
     * @return bool
     */
    private static function can_view_user(base $hook): bool {
        $viewing_user_id = $hook->viewing_user_id;
        $target_user_id = $hook->target_user_id;

        // This method doesn't care about what profile field the hook is for,
        // only about the user ids, so avoid repeated DB lookups by using the cache.
        $cache_key = __METHOD__ . '::' . $viewing_user_id . '::' . $target_user_id;
        if (isset(self::$resolution_cache[$cache_key])) {
            return self::$resolution_cache[$cache_key];
        }

        self::$resolution_cache[$cache_key] = (

            // If both users are participants in an activity or the target
            // user is the subject and the viewing user a participant.
            participant_instance::repository()::user_can_view_other_users_profile($viewing_user_id, $target_user_id)

                || util::can_report_on_user($target_user_id, $viewing_user_id)

                // Is the user a manager or appraiser in one of the users subject static instance records.
                || subject_static_instance::repository()::user_can_view_other_users_profile($viewing_user_id, $target_user_id)
        );

        return self::$resolution_cache[$cache_key];
    }

    /**
     * Check if the viewing user is currently assigned as a selector for a subject instance
     * of the given activity. This means he would be allowed to see all users on the site
     * or in the same tenant (if multi tenancy is enabled).
     *
     * @param base|allow_view_profile|allow_view_profile_field $hook
     * @return bool
     */
    private static function is_involved_in_manual_instance_progress(base $hook): bool {
        $cache_key = __METHOD__ . '::' . $hook->get_course()->id . '::' . $hook->viewing_user_id . '::' . $hook->target_user_id;
        if (isset(self::$resolution_cache[$cache_key])) {
            return self::$resolution_cache[$cache_key];
        }

        self::$resolution_cache[$cache_key] = manual_relationship_selector::repository()
            ->join([manual_relationship_selection_progress::TABLE, 'mrsp'], 'manual_relation_select_progress_id', 'id')
            ->join([manual_relationship_selection::TABLE, 'mrs'], 'mrsp.manual_relation_selection_id', 'id')
            ->join([activity_entity::TABLE, 'a'], 'mrs.activity_id', 'id')
            ->where('a.course', $hook->get_course()->id)
            ->where('mrsp.status', manual_relationship_selection_progress::STATUS_PENDING)
            ->where('user_id', $hook->viewing_user_id)
            ->when(true, function (repository $repository) use ($hook) {
                // This makes sure this query is multi tenancy compatible
                // and both users are in the same tenant
                tenant_orm_helper::restrict_users(
                    $repository,
                    new field('user_id', $repository->get_builder()),
                    context_user::instance($hook->target_user_id)
                );
            })
            ->exists();

        return self::$resolution_cache[$cache_key];
    }
}