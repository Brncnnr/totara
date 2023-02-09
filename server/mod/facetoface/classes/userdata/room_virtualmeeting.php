<?php
/*
* This file is part of Totara Learn
*
* Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
use mod_facetoface\room_dates_virtualmeeting;
use mod_facetoface\room_virtualmeeting as virtualmeeting_room;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

class room_virtualmeeting extends item {

    /**
     * @inheritDoc
     */
    public static function is_purgeable(int $userstatus) {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function is_exportable() {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function is_countable() {
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
     * @inheritDoc
     */
    protected static function purge(target_user $user, context $context): int {
        global $DB;

        $rooms = self::get_virtual_meetings($user, $context);
        if (empty($rooms)) {
            // Nothing to purge.
            return self::RESULT_STATUS_SUCCESS;
        }

        foreach ($rooms as $room) {
            // Get room date ids for this room
            $roomdates = $DB->get_records('facetoface_room_dates', ['roomid' => $room->id], 'id', 'id');
            foreach ($roomdates as $roomdate) {
                // Unlink this room from a room dates_virtual meeting.
                room_dates_virtualmeeting::delete_by_roomdateid($roomdate->id);
            }
            // Unlink this room from a virtual meeting.
            virtualmeeting_room::delete_by_roomid($room->id);
        }

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * @inheritDoc
     */
    protected static function count(target_user $user, context $context): int {
        return count(self::get_virtual_meetings($user, $context));
    }

    /**
     * @inheritDoc
     */
    protected static function export(target_user $user, context $context) {
        $export = new export();
        $export->data = self::get_virtual_meetings($user, $context);
        return $export;
    }

    /**
     * Get records for the given user and context.
     * @param target_user $user
     * @param context $context
     * @return array
     */
    protected static function get_virtual_meetings(target_user $user, context $context): array {
        global $DB;

        $join = self::get_activities_join($context, 'facetoface', 'fs.facetoface');

        $sql = "SELECT DISTINCT fr.id, frvm.userid, frvm.plugin, fr.name, fr.description
                  FROM {facetoface_room_virtualmeeting} frvm
                  JOIN {facetoface_room} fr ON frvm.roomid = fr.id
                  JOIN {facetoface_room_dates} frd ON frd.roomid = fr.id
                  JOIN {facetoface_sessions_dates} fsd ON fsd.id = frd.sessionsdateid
                  JOIN {facetoface_sessions} fs ON fs.id = fsd.sessionid
                 $join
                 WHERE frvm.userid = :userid
              ORDER BY fr.id";

        return $DB->get_records_sql($sql, ['userid' => $user->id]);
    }
}