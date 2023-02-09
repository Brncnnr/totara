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
 * @author  David Curry <david.curry@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface;

defined('MOODLE_INTERNAL') || die();

/**
 * Class room_list represents all rooms
 */
final class room_list implements \Iterator, \Countable {

    use traits\seminar_iterator;

    /**
     * room_list constructor.
     *
     * @param string $sql       a sql query that will return the desired rooms.
     * @param array  $params    Either the variables to go with the sql, or the parameters for the get_records call
     * @param string $sort      an order to sort the results in.
     */
    public function __construct(string $sql = null, array $params = [], string $sort = '') {
        global $DB;

        if (empty($sql)) {
            if ($sql === null) {
                debugging('Creating an instance without the first parameter is deprecated. Please pass an empty string "" if you really want it to return all records.', DEBUG_DEVELOPER);
            }
            // Get all of the rooms, restricted by any params handed through.
            // Note: in this case the params MUST match a record in the facetoface_room table
            $roomsdata = $DB->get_records('facetoface_room', $params, $sort, '*');
        } else {
            if (!empty($sort)) {
                $sql .= " ORDER BY {$sort}";
            }

            $roomsdata = $DB->get_records_sql($sql, $params);
        }

        foreach ($roomsdata as $roomdata) {
            $room = new room();
            $this->add($room->from_record($roomdata));
        }
        $this->rewind();
    }

    /**
     * Add room to list
     * @param room $item
     */
    public function add(room $item): void {
        $this->items[$item->get_id()] = $item;
    }

    /**
     * Get the relevant session rooms for a seminar activity
     * @param int $seminarid
     * @return room_list $this
     */
    public static function get_seminar_rooms(int $seminarid): room_list {
        $sql = "SELECT DISTINCT fr.*
                  FROM {facetoface_room} fr
                  JOIN {facetoface_room_dates} frd ON frd.roomid = fr.id
                  JOIN {facetoface_sessions_dates} fsd ON fsd.id = frd.sessionsdateid
                  JOIN {facetoface_sessions} fs ON fs.id = fsd.sessionid
                 WHERE fs.facetoface = :facetofaceid
              ORDER BY fr.name ASC, fr.id ASC";

        return new room_list($sql, ['facetofaceid' => $seminarid]);
    }

    /**
     * Get the room record for the specified session
     * @param int $seminareventid
     * @return room_list
     */
    public static function get_event_rooms(int $seminareventid): room_list {

        $sql = "SELECT DISTINCT fr.*
                  FROM {facetoface_room} fr
                  JOIN {facetoface_room_dates} frd ON frd.roomid = fr.id
                  JOIN {facetoface_sessions_dates} fsd ON fsd.id = frd.sessionsdateid
                 WHERE fsd.sessionid = :seminareventid 
              ORDER BY fr.name ASC, fr.id ASC";

        return new room_list($sql, ['seminareventid' => $seminareventid]);
    }

    /**
     * Getting all the custom rooms given by the seminareventid.
     * @param int $seminareventid
     * @return room_list
     */
    public static function get_custom_rooms_from_seminarevent(int $seminareventid): room_list {

        $sql = "SELECT DISTINCT fr.*
                  FROM {facetoface_room} fr
            INNER JOIN {facetoface_room_dates} frd ON frd.roomid = fr.id
            INNER JOIN {facetoface_sessions_dates} fsd ON fsd.id = frd.sessionsdateid
                 WHERE fsd.sessionid = :seminareventid AND fr.custom = 1
              ORDER BY fr.name ASC, fr.id ASC";

        return new room_list($sql, ['seminareventid' => $seminareventid]);
    }

    /**
     * Get assets by seminar session dates
     * @param int $sessiondateid
     * @return asset_list
     */
    public static function from_session(int $sessiondateid): room_list {

        $sql = "SELECT fr.*
                  FROM {facetoface_room} fr
            INNER JOIN {facetoface_room_dates} frd ON frd.roomid = fr.id
            INNER JOIN {facetoface_sessions_dates} fsd ON fsd.id = frd.sessionsdateid
                 WHERE fsd.id = :id
              ORDER BY fr.name ASC, fr.id ASC";

        return new room_list($sql, ['id' => $sessiondateid]);

    }

    /**
     * Get available rooms for the specified time slot, or all rooms if $timestart and $timefinish are empty.
     *
     * NOTE: performance is not critical here because this function should be used only when assigning rooms to sessions.
     *
     * @param int $timestart start of requested slot
     * @param int $timefinish end of requested slot
     * @param seminar_event $seminarevent current session, 0 if session is being created, all current session rooms are always included
     * @return room_list
     */
    public static function get_available_rooms(int $timestart, int $timefinish, seminar_event $seminarevent): room_list {
        global $DB, $USER;

        $list = new room_list('', ['id' => 0]); // Create an empty list
        $seminarid = $seminarevent->get_facetoface();

        $params = array();
        $params['timestart'] = (int)$timestart;
        $params['timefinish'] = (int)$timefinish;
        $params['sessionid'] = $seminarevent->get_id();
        $params['facetofaceid'] = $seminarid;
        $params['userid'] = $USER->id;

        $bookedrooms = array();
        if ($timestart and $timefinish) {
            if ($timestart > $timefinish) {
                debugging('Invalid slot specified, start cannot be later than finish', DEBUG_DEVELOPER);
            }

            $sql = "SELECT DISTINCT fr.*
                      FROM {facetoface_room} fr
                      JOIN {facetoface_room_dates} frd ON frd.roomid = fr.id
                      JOIN {facetoface_sessions_dates} fsd ON fsd.id = frd.sessionsdateid
                      JOIN {facetoface_sessions} fs ON fs.id = fsd.sessionid AND fs.cancelledstatus = 0
                     WHERE fr.allowconflicts = 0 AND fsd.sessionid <> :sessionid
                       AND (fsd.timestart < :timefinish AND fsd.timefinish > :timestart)
                  ORDER BY fr.name ASC, fr.id ASC";

            $bookedrooms = $DB->get_records_sql($sql, $params);
        }
        // First get all site rooms that either allow conflicts
        // or are not occupied at the given times
        // or are already used from the current event.
        // Note that hidden rooms may be reused in the same session if already there,
        // but are completely hidden everywhere else.
        if ($seminarevent->exists()) {
            $sql = "SELECT DISTINCT fr.*
                      FROM {facetoface_room} fr
                 LEFT JOIN {facetoface_room_dates} frd ON frd.roomid = fr.id
                 LEFT JOIN {facetoface_sessions_dates} fsd ON fsd.id = frd.sessionsdateid
                     WHERE fr.custom = 0 AND (fr.hidden = 0 OR fsd.sessionid = :sessionid)
                  ORDER BY fr.name ASC, fr.id ASC";
        } else {
            $sql = "SELECT fr.*
                      FROM {facetoface_room} fr
                     WHERE fr.custom = 0 AND fr.hidden = 0
                  ORDER BY fr.name ASC, fr.id ASC";
        }
        $rooms = $DB->get_records_sql($sql, $params);
        foreach ($bookedrooms as $rid => $unused) {
            unset($rooms[$rid]);
        }

        // Then include any custom rooms that are in the current facetoface activity.
        if ($seminarid > 0) {
            $sql = "SELECT DISTINCT fr.*
                      FROM {facetoface_room} fr
                      JOIN {facetoface_room_dates} frd ON frd.roomid = fr.id
                      JOIN {facetoface_sessions_dates} fsd ON fsd.id = frd.sessionsdateid
                      JOIN {facetoface_sessions} fs ON fs.id = fsd.sessionid
                     WHERE fs.facetoface = :facetofaceid AND fr.custom = 1
                  ORDER BY fr.name ASC, fr.id ASC";
            $customrooms = $DB->get_records_sql($sql, $params);
            foreach ($customrooms as $room) {
                if (!isset($bookedrooms[$room->id])) {
                    $rooms[$room->id] = $room;
                }
            }
            unset($customrooms);
        }

        // Add custom rooms of the current user that are not assigned yet or any more.
        $sql = "SELECT fr.*
                  FROM {facetoface_room} fr
             LEFT JOIN {facetoface_room_dates} frd ON frd.roomid = fr.id
             LEFT JOIN {facetoface_sessions_dates} fsd ON fsd.id = frd.sessionsdateid
                 WHERE fsd.id IS NULL AND fr.custom = 1 AND fr.usercreated = :userid
              ORDER BY fr.name ASC, fr.id ASC";
        $userrooms = $DB->get_records_sql($sql, $params);
        foreach ($userrooms as $room) {
            $rooms[$room->id] = $room;
        }

        // Construct all the assets and add them to the iterator list.
        foreach ($rooms as $roomdata) {
            $room = new room();
            $room->from_record($roomdata);
            $list->add($room);
        }
        return $list;
    }
}