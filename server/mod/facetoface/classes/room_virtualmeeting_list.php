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
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package mod_facetoface
*/

namespace mod_facetoface;

defined('MOODLE_INTERNAL') || die();

/**
 * Class room_virtualmeeting_list represents virtual meeting rooms
 */
class room_virtualmeeting_list implements \Iterator, \Countable {

    use traits\seminar_iterator;

    /**
     * room_virtualmeeting_list constructor.
     *
     * @param string $sql a sql query that will return the desired rooms.
     * @param array $params Either the variables to go with the sql, or the parameters for the get_records call
     * @param bool $roomid_key
     * @param string $sort an order to sort the results in.
     */
    public function __construct(string $sql = null, array $params = [], bool $roomid_key = false, string $sort = '') {
        global $DB;

        if (empty($sql)) {
            if ($sql === null) {
                debugging('Creating an instance without the first parameter is deprecated. Please pass an empty string "" if you really want it to return all records.', DEBUG_DEVELOPER);
            }
            $data = $DB->get_records('facetoface_room_virtualmeeting', $params, $sort, '*');
        } else {
            if (!empty($sort)) {
                $sql .= " ORDER BY {$sort}";
            }
            $data = $DB->get_records_sql($sql, $params);
        }

        foreach ($data as $item) {
            $virtualmeeting = new room_virtualmeeting();
            $this->add($virtualmeeting->from_record($item), $roomid_key);
        }
        $this->rewind();
    }

    /**
     * Add room_virtualmeeting to list
     * @param room_virtualmeeting $item
     * @param bool $roomid_key
     */
    public function add(room_virtualmeeting $item, bool $roomid_key = false): void {
        if ($roomid_key) {
            $this->items[$item->get_roomid()] = $item;
        } else {
            $this->items[$item->get_id()] = $item;
        }
    }

    /**
     * Get virtual meetings by room ids
     * @param array $roomids
     * @return null|room_virtualmeeting_list
     */
    public static function from_roomids(array $roomids = []): ?room_virtualmeeting_list {
        global $DB;

        if (empty($roomids)) {
            return null;
        }

        list($sqlin, $inparams) = $DB->get_in_or_equal($roomids);
        $sql = "SELECT frvm.*
                  FROM {facetoface_room_virtualmeeting} frvm
                  JOIN {facetoface_room} fr ON frvm.roomid = fr.id
                 WHERE frvm.roomid $sqlin";

        return new room_virtualmeeting_list($sql, $inparams, true);
    }
}
