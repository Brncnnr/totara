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

namespace totara_competency\userdata;

use context;
use core\orm\query\builder;
use totara_competency\entity;
use totara_competency\entity\assignment;
use totara_competency\entity\competency_assignment_user;
use totara_competency\entity\competency_assignment_user_log;
use totara_competency\user_groups;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * If a users membership in an audience, or if his job assignment still exists
 * his assignments might be automatically recreated by the expand_task.
 * To fully purge a users assignment his job assignments and audience membership
 * need to be purged as well.
 */
class assignment_user extends item {

    /**
     * Get main Frankenstyle component name (core subsystem or plugin).
     * This is used for UI purposes to group items into components.
     */
    public static function get_main_component() {
        return 'totara_competency';
    }

    /**
     * Returns sort order.
     *
     * @return int
     */
    public static function get_sortorder() {
        return 0; // 1st item of 6 in the 'Competencies' list.
    }

    /**
     * Can user data of this item data be purged from system?
     *
     * @param int $userstatus target_user::STATUS_ACTIVE, target_user::STATUS_DELETED or target_user::STATUS_SUSPENDED
     * @return bool
     */
    public static function is_purgeable(int $userstatus) {
        return true;
    }

    /**
     * Purge user data for this item.
     *
     * NOTE: Remember that context record does not exist for deleted users any more,
     *       it is also possible that we do not know the original user context id.
     *
     * @param target_user $user
     * @param context $context restriction for purging e.g., system context for everything, course context for purging one course
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or status::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, context $context) {
        // Delete in a transaction to avoid potential collision
        // with expand_task running at the exact same time and
        // restoring deleted records for individual user assignment
        builder::get_db()->transaction(function () use ($user) {
            // Delete logs for this user
            builder::table(competency_assignment_user_log::TABLE)
                ->where('user_id', $user->id)
                ->delete();

            // Delete all user assignment records
            builder::table(competency_assignment_user::TABLE)
                ->where('user_id', $user->id)
                ->delete();

            // Delete all individual assignments
            builder::table(assignment::TABLE)
                ->where('user_group_type', user_groups::USER)
                ->where('user_group_id', $user->id)
                ->delete();
        });

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * Can user data of this item data be exported from the system?
     *
     * @return bool
     */
    public static function is_exportable() {
        return true;
    }

    /**
     * Export user data from this item.
     *
     * @param target_user $user
     * @param context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return export|int object or error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, context $context) {
        $export = new export();

        // Get all assignments for the given user
        $assignments = assignment::repository()
            ->select('*')
            ->with_names()
            ->join(competency_assignment_user::TABLE, 'id', 'assignment_id')
            ->where(competency_assignment_user::TABLE.'.user_id', $user->id)
            ->get();

        $export->data['assignments'] = $assignments->to_array();

        $logs = competency_assignment_user_log::repository()
            ->where('user_id', $user->id)
            ->get();

        $export->data['logs'] = $logs->to_array();

        return $export;
    }


    /**
     * Can user data of this item be somehow counted?
     * How much data is there?
     *
     * @return bool
     */
    public static function is_countable() {
        return true;
    }

    /**
     * Count user data for this item.
     *
     * @param target_user $user
     * @param context $context restriction for counting i.e., system context for everything and course context for course data
     * @return int negative number is error result self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function count(target_user $user, context $context) {
        return competency_assignment_user::repository()
            ->where('user_id', $user->id)
            ->count();
    }
}