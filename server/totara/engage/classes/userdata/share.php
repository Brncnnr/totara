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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage\userdata;

use core_user;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

/**
 * GDPR for resource item
 */
final class share extends item {
    /**
     * @param int $user_status
     * @return bool
     */
    public static function is_purgeable(int $user_status) {
        return true;
    }

    /**
     * @return bool
     */
    public static function is_exportable() {
        return true;
    }

    /**
     * @return bool
     */
    public static function is_countable() {
        return true;
    }

    /**
     * @param target_user $user
     * @param \context $context
     * @return int|void
     */
    protected static function purge(target_user $user, \context $context): int {
        global $DB;

        $DB->delete_records('engage_share', ['ownerid' => (int)$user->id]);
        $DB->delete_records_select(
            'engage_share_recipient',
            ' sharerid = :sharerid OR (instanceid = :instanceid AND component = :component)',
            ['sharerid' => (int)$user->id, 'instanceid' => (int)$user->id, 'component' => 'core_user']
        );

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * @param target_user $user
     * @param \context $context
     *
     * @return export
     */
    protected static function export(target_user $user, \context $context): export{
        global $DB;

        $sql = 'SELECT sr.id, er.name AS name, s.timecreated AS timecreated, sr.instanceid, sr.component
         FROM {engage_share_recipient} sr
         LEFT JOIN {engage_share} s ON s.id = sr.shareid
         INNER JOIN {engage_resource} er ON s.itemid = er.id
         WHERE sr.sharerid = :sharerid';

        $records = $DB->get_records_sql($sql, ['sharerid' => $user->id]);

        $export = new export();
        $export->data = [];
        foreach ($records as $record) {
            $recipient = null;
            if ($record->component === 'core_user') {
                $user = core_user::get_user($record->instanceid, '*', MUST_EXIST);
                $recipient = fullname($user);
            } else {
                $course = get_course($record->instanceid);
                $recipient = $course->fullname;
            }

            $export->data[] = [
                'name' => $record->name,
                'timecreated' => $record->timecreated,
                'recipient' => $recipient
            ];
        }

        return $export;
    }

    /**
     * @param target_user $user
     * @param \context $context
     * @return int
     */
    protected static function count(target_user $user, \context $context): int {
        global $DB;
        return (int) $DB->count_records('engage_share', ['ownerid' => (int)$user->id]);
    }
}