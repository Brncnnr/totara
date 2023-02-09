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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package mod_facetoface
*/

namespace mod_facetoface\userdata;

use context;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

class facilitator extends item {

    /**
     * Can user data of this item data be purged from system?
     * @param int $userstatus target_user::STATUS_ACTIVE, target_user::STATUS_DELETED or target_user::STATUS_SUSPENDED
     * @return bool
     */
    public static function is_purgeable(int $userstatus): bool {
        return true;
    }

    /**
     * Can user data of this item be exported from the system?
     * @return bool
     */
    public static function is_exportable(): bool {
        return true;
    }

    /**
     * Can user data of this item be somehow counted?
     * How much data is there?
     * @return bool
     */
    public static function is_countable(): bool {
        return true;
    }

    /**
     * Is the given context level compatible with this item?
     * @return array
     */
    public static function get_compatible_context_levels(): array {
        return [
            CONTEXT_SYSTEM,
            CONTEXT_COURSECAT,
            CONTEXT_COURSE,
            CONTEXT_MODULE
        ];
    }

    /**
     * Execute user data purging for this item.
     * @param target_user $user
     * @param context $context restriction for purging e.g., system context for everything, course context for purging one course
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, context $context): int {

        $records = self::get_facilitators($user, $context);
        if (empty($records)) {
            // Nothing to purge.
            return self::RESULT_STATUS_SUCCESS;
        }

        foreach ($records as $record) {
            $facilitator = new \mod_facetoface\facilitator($record->id);
            $facilitator->delete();
        }

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * Count user data for this item.
     * @param target_user $user
     * @param context $context restriction for counting i.e., system context for everything and course context for course data
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, context $context): int {
        return count(self::get_facilitators($user, $context));
    }

    /**
     * Export user data from this item.
     * @param target_user $user
     * @param context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, context $context) {
        $export = new export();
        $export->data = self::get_facilitators($user, $context);
        return $export;
    }

    /**
     * Get records for the given user and context.
     * @param target_user $user
     * @param context $context
     * @return array
     */
    protected static function get_facilitators(target_user $user, context $context): array {
        global $DB;

        $join = self::get_activities_join($context, 'facetoface', 'fs.facetoface');

        $sql = "SELECT DISTINCT ff.*
                  FROM {facetoface_facilitator} ff
                  JOIN {facetoface_facilitator_dates} ffd ON ffd.facilitatorid = ff.id
                  JOIN {facetoface_sessions_dates} fsd ON fsd.id = ffd.sessionsdateid
                  JOIN {facetoface_sessions} fs ON fs.id = fsd.sessionid
                 $join
                 WHERE ff.userid = :userid
              ORDER BY ff.id";

        return $DB->get_records_sql($sql, ['userid' => $user->id]);
    }
}