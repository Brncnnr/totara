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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package mod_facetoface
*/

namespace mod_facetoface;

use coding_exception;
use context_module;
use core\orm\query\builder;
use core\task\manager as task_manager;
use mod_facetoface\task\manage_virtualmeetings_adhoc_task;

/**
 * Additional room functionality.
 */
final class room_helper {

    /**
     * The 15 minutes is time prior to the start of a session
     */
    const JOIN_NOW_TIME = MINSECS * 15;

    /**
     * Room data
     *
     * @param \stdClass $data to be saved includes:
     *      - int {facetoface_room}.id
     *      - string {facetoface_room}.name
     *      - int {facetoface_room}.capacity
     *      - string {facetoface_room}.url
     *      - int {facetoface_room}.allowconflicts
     *      - string {facetoface_room}.description
     *      - bool {facetoface_room}.notcustom
     *      - int {facetoface_room}.hidden
     * @return room
     */
    public static function save(\stdClass $data): room {
        $is_sitewide = !empty($data->notcustom);

        if ($data->id) {
            $room = new room($data->id);
            if ($is_sitewide) {
                $room->publish();
            }
        } else {
            if ($is_sitewide) {
                $room = new room();
            } else {
                $room = room::create_custom_room();
            }
        }
        $room->set_name($data->name);
        $room->set_allowconflicts($data->allowconflicts);
        $room->set_capacity($data->roomcapacity);

        if (room_virtualmeeting::VIRTUAL_MEETING_INTERNAL != $data->plugin) {
            // Clear the value if the update is changing the value
            // from 'internal' plugin to 'none/zoom/msteams'
            $room->set_url('');
        } else if (!filter_var($data->url, FILTER_VALIDATE_URL)) {
            throw new coding_exception('the url is not in a valid format');
        } else {
            $room->set_url($data->url);
        }

        // We need to make sure the room exists before formatting the customfields and description.
        if (!$room->exists()) {
            $room->save();
        }

        // Export data to store in customfields and description.
        self::save_custom_fields_and_description($room, $data);

        // Set room virtual meeting record.
        self::create_or_update_virtual_meeting($room, $data, $is_sitewide);

        // Return new/updated room.
        return $room;
    }

    /**
     * Helper function for room_helper::save().
     *
     * @param room $room
     * @param \stdClass $data
     */
    private static function save_custom_fields_and_description(room $room, \stdClass $data): void {
        global $TEXTAREA_OPTIONS;

        $data = clone $data;
        $data->id = $room->get_id();
        customfield_save_data($data, 'facetofaceroom', 'facetoface_room');

        // Update description.
        $data = file_postupdate_standard_editor(
            $data,
            'description',
            $TEXTAREA_OPTIONS,
            $TEXTAREA_OPTIONS['context'],
            'mod_facetoface',
            'room',
            $room->get_id()
        );
        $room->set_description($data->description);
        $room->save();
    }

    /**
     * Helper function for room_helper::save().
     *
     * @param room $room
     * @param \stdClass $data
     * @param boolean $is_sitewide
     */
    private static function create_or_update_virtual_meeting(room $room, \stdClass $data, bool $is_sitewide): void {
        global $USER;

        // Physical room
        if (empty($data->plugin) || !room_virtualmeeting::is_virtual_meeting($data->plugin)) {
            return;
        }
        if ($is_sitewide) {
            throw new coding_exception("you cannot create a site-wide virtual meeting!");
        }

        $virtual_meeting = room_virtualmeeting::get_virtual_meeting($room);
        // Lets check if this room a new or an update.
        if ($virtual_meeting->exists()) {
            // Nobody can update or delete another user's virtual meeting room
            if (!$virtual_meeting->can_manage($USER->id)) {
                throw new coding_exception("you cannot update or delete another user's virtual meeting!");
            }
            $virtual_meeting->set_status(room_virtualmeeting::STATUS_PENDING)->save();
            return;
        }

        $virtual_meeting->set_plugin($data->plugin)
            ->set_roomid($room->get_id())
            ->set_userid($USER->id)
            ->set_status(room_virtualmeeting::STATUS_PENDING)
            ->save();
    }

    /**
     * Sync the list of rooms for a given seminar event date
     * @param integer $date Seminar date Id
     * @param array $rooms List of room Ids
     * @return bool
     */
    public static function sync(int $date, array $rooms = []): bool {
        return resource_helper::sync_resources($date, $rooms, 'room');
    }

    /**
     * Get rooms for specific session.
     * @param int $sessionid
     * @return string
     */
    public static function get_session_roomids(int $sessionid): string {
        global $DB;

        $roomid = $DB->sql_group_concat($DB->sql_cast_2char('frd.roomid'), ',');
        $sql = "SELECT {$roomid} AS roomids
                  FROM {facetoface_sessions_dates} fsd
             LEFT JOIN {facetoface_room_dates} frd ON frd.sessionsdateid = fsd.id
                 WHERE fsd.id = :id";
        $ret = $DB->get_field_sql($sql, array('id' => $sessionid));
        return $ret ? $ret : '';
    }

    /**
     * Is the current user capable to access the virtual room at any time?
     *
     * @param seminar_session $session
     * @param integer $userid
     * @return boolean
     */
    public static function has_access_at_any_time(seminar_session $session, int $userid = 0): bool {
        global $USER;
        if (!$userid) {
            $userid = $USER->id;
        }
        if (self::has_join_room_capability($session->get_sessionid(), $userid)) {
            return true;
        }
        if (self::is_user_facilitator($session, $userid)) {
            return true;
        }
        return false;
    }

    /**
     * Can we display a virtual room link?
     * @param seminar_event $seminarevent
     * @param seminar_session $session
     * @param signup|null $signup signup instance or null to use the signup of the current user
     * @return bool
     */
    public static function show_room_link(seminar_session $session, ?signup $signup = null): bool {
        global $USER;
        if (is_null($signup)) {
            $signup = signup::create($USER->id, $session->get_seminar_event());
        }
        if ($signup->get_sessionid() != $session->get_sessionid()) {
            throw new coding_exception('foreign $signup is not allowed');
        }
        if (signup_helper::is_booked($signup, false)) {
            return true;
        }
        return self::has_access_at_any_time($session, $signup->get_userid());
    }

    /**
     * Can we display 'Join now' link button?
     * @param seminar_event $seminarevent
     * @param seminar_session $session
     * @param signup|null $signup signup instance or null to use the signup of the current user
     * @param int $time time stamp or 0 to use the current time
     * @return bool
     */
    public static function show_joinnow(seminar_event $seminarevent, seminar_session $session, ?signup $signup = null, int $time = 0): bool {
        if ($seminarevent->get_id() != $session->get_sessionid()) {
            throw new coding_exception('$session does not belong to the $seminarevent');
        }
        if ($signup && $signup->get_sessionid() != $session->get_sessionid()) {
            throw new coding_exception('foreign $signup is not allowed');
        }
        return self::has_time_come($seminarevent, $session, $time) && self::show_room_link($session, $signup);
    }

    /**
     * Is the current user capable to update the virtual room?
     *
     * @param room $room
     * @return boolean
     * @deprecated since Totara 13.5
     */
    public static function can_update_virtualmeeting(room $room): bool {
        debugging('room_helper::' . __FUNCTION__ . '() is deprecated. Please use room_virtualmeeting::can_manage() instead.', DEBUG_DEVELOPER);
        $virtual_meeting = room_virtualmeeting::get_virtual_meeting($room);
        return $virtual_meeting->can_manage();
    }

    /**
     * room::show_joinnow(html button)::has_time_come(to display 'Join now' button)?
     * Has time come to display the 'Join now' link button
     * from 15 minutes prior to the session start time, until the session end time
     * @param seminar_event $seminarevent
     * @param seminar_session $session
     * @param int $time time stamp or 0 to use the current time
     * @return bool
     */
    public static function has_time_come(seminar_event $seminarevent, seminar_session $session, int $time = 0): bool {
        if ($seminarevent->get_id() != $session->get_sessionid()) {
            throw new coding_exception('$session does not belong to the $seminarevent');
        }
        if ($seminarevent->get_cancelledstatus()) {
            return false;
        }
        if ($time <= 0) {
            $time = time();
        }
        if ($session->is_start($time + self::JOIN_NOW_TIME) && !$session->is_over($time)) {
            return true;
        }
        return false;
    }

    /**
     * Check if a user has the joinanyvirtualroom capability at the seminar module
     * @param integer $eventid
     * @param integer $userid
     * @return bool
     */
    private static function has_join_room_capability(int $eventid, int $userid): bool {
        $cm = builder::table('course_modules', 'cm')
            ->join(['modules', 'md'], 'module', 'id')
            ->join(['facetoface', 'f'], 'instance', 'id')
            ->join(['facetoface_sessions', 's'], 'f.id', 'facetoface')
            ->where('s.id', $eventid)
            ->where('md.name', 'facetoface')
            ->where_field('cm.course', 'f.course')
            ->select('cm.id')
            ->one(true);
        $context = context_module::instance($cm->id);
        return has_capability('mod/facetoface:joinanyvirtualroom', $context, $userid);
    }

    /**
     * Check if a user is facilitating the session
     * @param seminar_session $session
     * @param integer $userid
     * @return bool
     */
    private static function is_user_facilitator(seminar_session $session, int $userid): bool {
        return builder::table('facetoface_facilitator', 'fa')
            ->join(['user', 'u'], 'userid', 'id')
            ->join(['facetoface_facilitator_dates', 'fad'], 'fa.id', 'facilitatorid')
            ->join(['facetoface_sessions_dates', 'sd'], 'fad.sessionsdateid', 'id')
            ->where('u.id', $userid)
            ->where('u.deleted', 0)
            ->where('u.suspended', 0)
            ->where('fa.hidden', 0)
            ->where('sd.id', $session->get_id())
            ->exists();
    }

    /**
     * Get all room ids for a given session date, sorted by id.
     *
     * @param int $session_date_id
     * @return array
     */
    public static function get_room_ids_sorted(int $session_date_id): array {
        global $DB;
        return array_keys($DB->get_records(
            'facetoface_room_dates',
            ['sessionsdateid' => $session_date_id],
            'roomid',
            'roomid'
        ));
    }
}
