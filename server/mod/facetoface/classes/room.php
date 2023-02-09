<?php
/*
 * This file is part of Totara LMS
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
 * @author Moises Burgos <moises.burgos@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface;

use core\orm\query\builder;

require_once("{$CFG->dirroot}/mod/facetoface/lib.php");

defined('MOODLE_INTERNAL') || die();

/**
 * Class Room represents Seminar Room
 */
final class room implements seminar_iterator_item, seminar_attachment_item {

    use traits\crud_mapper;

    /**
     * Room identifier options
     */
    const ROOM_IDENTIFIER_NAME = 0;
    const ROOM_IDENTIFIER_BUILDING = 1;
    const ROOM_IDENTIFIER_LOCATION = 2;
    const ROOM_NAME_LENGTH = 100;

    /**
     * @var int {facetoface_room}.id
     */
    private $id = 0;

    /**
     *  @var string {facetoface_room}.name
     */
    private $name = '';

    /**
     *  @var int {facetoface_room}.capacity
     */
    private $capacity = null;

    /**
     *  @var int {facetoface_room}.allowconflicts
     */
    private $allowconflicts = 0;

    /**
     * @var string {facetoface_room}.description
     */
    private $description = '';

    /**
     * @var string {facetoface_room}.url
     */
    private $url = '';

    /**
     *  @var int {facetoface_room}.custom
     */
    private $custom = 0;

    /**
     *  @var int {facetoface_room}.hidden
     */
    private $hidden = 0;

    /**
     *  @var int {facetoface_room}.usercreated
     */
    private $usercreated = 0;

    /**
     *  @var int {facetoface_room}.usermodified
     */
    private $usermodified = 0;

    /**
     * @var int {facetoface_room}.timecreated
     */
    private $timecreated = 0;

    /**
     * @var int {facetoface_room}.timemodified
     */
    private $timemodified = 0;

    /**
     * @var string facetoface rooms table name
     */
    const DBTABLE = 'facetoface_room';

    /**
     * Seminar room constructor
     * @param int $id {facetoface_room}.id If 0 - new Seminar Room will be created
     */
    public function __construct(int $id = 0) {

        if ((int)$id > 0) {
            $this->id = $id;
            $this->load();
        }
    }

    /**
     * Get names of customfields that should be displayed along with rooms name
     *
     * @param bool $all if true return all possible display custom fields, otherwise return fields based on roomidentifier setting
     * @return array
     */
    protected static function get_display_customfields(bool $all = true): array {
        if ($all) {
            return [CUSTOMFIELD_BUILDING, CUSTOMFIELD_LOCATION];
        } else {
            global $CFG;
            switch ($CFG->facetoface_roomidentifier) {
                case self::ROOM_IDENTIFIER_BUILDING:
                    return [CUSTOMFIELD_BUILDING];

                case self::ROOM_IDENTIFIER_LOCATION:
                    return [CUSTOMFIELD_BUILDING, CUSTOMFIELD_LOCATION];

                default:
                    return [];
            }
        }
    }

    /**
     * Get a detailed room description as a string
     *
     * @return string
     */
    public function __toString(): string {
        // Return all details, regardless of roomidentifier configuration.
        return $this->get_detailed_name(true);
    }

    /**
     * Get a detailed room description as a string
     *
     * @param bool $all_fields if true include all possible display custom fields, otherwise include fields based on roomidentifier setting
     * @return string
     */
    public function get_detailed_name(bool $all_fields = false): string {
        $customfields = $this->get_customfield_array();
        $displayfields = room::get_display_customfields($all_fields);
        $items = [];
        if (!empty($this->name)) {
            $items[] = $this->name;
        }

        foreach ($displayfields as $field) {
            if (!empty($customfields[$field])) {
                if ($field == CUSTOMFIELD_LOCATION) {
                    $items[] = str_replace('<br />', ', ', $customfields[$field]);
                } else {
                    $items[] = $customfields[$field];
                }
            }
        }

        return implode(", ", $items);
    }

    /**
     * Create a new room with the custom flag set
     *
     * @return room
     */
    public static function create_custom_room(): room {
        $room = new room();
        $room->custom = 1;
        return $room;
    }

    /**
     * Load record from $id, if it is the invalid $id, that does not exist within the database, then we should probably not throw
     * any exceptions, rather than just return an object without default empty data set here.
     * @param int $id
     * @return room
     * @deprecated since Totara 13.0
     */
    public static function find(int $id): room {
        debugging('room::find() function has been deprecated, please use room::seek()', DEBUG_DEVELOPER);
        return self::seek($id);
    }

    /**
     * Loads a seminar room.
     *
     * @return room
     */
    public function load(): room {
        return $this->crud_load();
    }

    /**
     * Map data object to class instance.
     *
     * @param \stdClass $object
     * @return room this
     */
    public function from_record(\stdClass $object): room {
        $this->map_object($object);
        return $this;
    }

    /**
     * Map class instance onto data object
     *
     * @return \stdClass
     */
    public function to_record() : \stdClass {
        return $this->unmap_object();
    }

    /**
     * Store room into database
     */
    public function save(): void {
        global $USER;

        $this->usermodified = $USER->id;
        $this->timemodified = time();

        if (!$this->id) {
            $this->usercreated = $USER->id;
            $this->timecreated = time();
        }

        $this->crud_save();
    }

    /**
     * Deletes a seminar room.
     */
    public function delete(): void {
        global $DB;

        // Nothing to delete.
        if ($this->id == 0) {
            return;
        }

        $this->delete_customfields();
        $this->delete_embedded_files();

        // Unlink this room from a virtual meeting.
        room_virtualmeeting::delete_by_roomid($this->id);

        // Get room date ids for this room
        $roomdates = $DB->get_records('facetoface_room_dates', ['roomid' => $this->id]);
        foreach ($roomdates as $roomdate) {
            // Unlink this room from a room dates_virtual meeting.
            room_dates_virtualmeeting::delete_by_roomdateid($roomdate->id);
        }

        // Unlink this room from any session.
        $DB->delete_records('facetoface_room_dates', ['roomid' => $this->id]);
        // Finally delete the room record itself.
        $DB->delete_records(self::DBTABLE, ['id' => $this->id]);

        $this->map_object((object)get_object_vars(new self()));
    }

    /**
     * Deletes all custom fields related to a room.
     */
    private function delete_customfields(): void {
        global $DB, $CFG;

        // Room doesn't exist.
        if ($this->id == 0) {
            return;
        }

        require_once("$CFG->dirroot/totara/customfield/fieldlib.php");

        $roomdata = $this->to_record();
        $roomfields = $DB->get_records('facetoface_room_info_field');
        foreach ($roomfields as $roomfield) {
            /** @var customfield_base $customfieldentry */
            $customfieldentry = customfield_get_field_instance($roomdata, $roomfield->id, 'facetoface_room', 'facetofaceroom');
            if (!empty($customfieldentry)) {
                $customfieldentry->delete();
            }
        }
    }

    /**
     * Deletes all files embedded in the room description.
     */
    private function delete_embedded_files(): void {
        // Room doesn't exist.
        if ($this->id == 0) {
            return;
        }
        $fs = get_file_storage();
        $syscontext = \context_system::instance();
        $fs->delete_area_files($syscontext->id, 'mod_facetoface', 'room', $this->id);
    }

    /**
     * Check whether the room exists yet or not.
     * If the room has been saved into the database the $id field should be non-zero
     *
     * @return bool - true if the room has an $id, false if it hasn't
     */
    public function exists(): bool {
        return (bool)$this->get_id();
    }

    /**
     * Checks if the room is in use anywhere
     * @return bool
     */
    public function is_used(): bool {
        global $DB;
        $count = $DB->count_records('facetoface_room_dates', ['roomid' => $this->get_id()]);
        return $count > 0;
    }

    /**
     * Check whether the room is a virtual room or not.
     * @return boolean true if it is a virtual meeting or a custom virtual room
     */
    public function is_virtual(): bool {
        return $this->is_custom_virtual() || $this->is_virtual_meeting();
    }

    /**
     * Check whether the room is a custom virtual room or not.
     * @return boolean true if it is a custom virtual room
     */
    public function is_custom_virtual(): bool {
        return !empty($this->url);
    }

    /**
     * Check whether the room is a virtual meeting or not.
     * @return boolean true if it is a virtual meeting
     */
    public function is_virtual_meeting(): bool {
        if (empty($this->id) || empty($this->custom)) {
            return false;
        }
        return builder::table(room_virtualmeeting::DBTABLE)->where('roomid', $this->id)->exists();
    }

    /**
     * @return int
     */
    public function get_id(): int {
        return (int)$this->id;
    }

    /**
     * @return string
     */
    public function get_name(): string {
        return (string)$this->name;
    }

    /**
     * @param string $name
     * @return room this
     */
    public function set_name(string $name): room {
        $this->name = $name;
        return $this;
    }

    /**
     * @return int or null
     */
    public function get_capacity(): ?int {
        if (is_null($this->capacity)) {
            return null;
        }
        return (int)$this->capacity;
    }

    /**
     * @param int $capacity
     * @return room this
     */
    public function set_capacity(int $capacity): room {
        $this->capacity = $capacity;
        return $this;
    }

    /**
     * @return bool
     */
    public function get_allowconflicts(): bool {
        return (bool)$this->allowconflicts;
    }

    /**
     * @param bool $allowconflicts
     * @return room this
     */
    public function set_allowconflicts(bool $allowconflicts): room {
        $this->allowconflicts = (int)$allowconflicts;
        return $this;
    }

    /**
     * @return string
     */
    public function get_description(): string {
        return (string)$this->description;
    }

    /**
     * @param string $description
     * @return room this
     */
    public function set_description(string $description): room {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function get_url(): string {
        return (string)$this->url;
    }

    /**
     * Link to use in virtual room
     * @param null|string $url
     * @return room this
     */
    public function set_url(?string $url): room {
        $this->url = $url;
        return $this;
    }

    /**
     * Get whether this room is hidden
     * Note: There is no setter for this field as it only moves
     *       in one direction, use the publish() function instead
     *
     * @return bool
     */
    public function get_custom(): bool {
        return (bool)$this->custom;
    }

    /**
     * Switch an room from a single use custom room to a site wide reusable room.
     * Note: that this function is instead of the set_custom() function, and it enforces
     *       the behaviour that an room can only become more public not less.
     *
     * @return room $this
     */
    public function publish(): room {
        if ($this->custom === false) {
            \core\notification::warning(get_string('error:cannotrepublishroom', 'mod_facetoface'));
            return $this;
        }
        $this->custom = (int)false;
        return $this;
    }

    /**
     * Get whether this room is hidden
     * Note: There is no setter for this field, use
     *       the hide() and show() functions instead
     *
     * @return bool
     */
    public function get_hidden(): bool {
        return (bool)$this->hidden;
    }

    /**
     * Hides this room
     * Note: This is the equivalent of set_hidden(true);
     *
     * @return room $this
     */
    public function hide(): room {
        $this->hidden = (int)true;
        return $this;
    }

    /**
     * Shows this room
     * Note: This is the equivalent of set_hidden(false);
     *
     * @return room $this
     */
    public function show(): room {
        $this->hidden = (int)false;
        return $this;
    }

    /**
     * @return int
     */
    public function get_usercreated(): int {
        return (int)$this->usercreated;
    }

    /**
     * @return int
     */
    public function get_usermodified(): int {
        return (int)$this->usermodified;
    }

    /**
     * @param int $usermodified
     * @return room this
     */
    public function set_usermodified(int $usermodified): room {
        $this->usermodified = $usermodified;
        return $this;
    }

    /**
     * @return int
     */
    public function get_timecreated(): int {
        return (int)$this->timecreated;
    }

    /**
     * @return int
     */
    public function get_timemodified(): int {
        return (int)$this->timemodified;
    }

    /**
     * @param int $timemodified
     * @return room this
     */
    public function set_timemodified(int $timemodified): room {
        $this->timemodified = $timemodified;
        return $this;
    }

    /**
     * Check if room is available during certain time slot.
     *
     * Available rooms are rooms where the start OR end times don't fall within that of another session's room,
     * as well as rooms where the start AND end times don't encapsulate that of another session's room
     *
     * @param int $timestart
     * @param int $timefinish
     * @param seminar_event $seminarevent
     * @return bool
     */
    public function is_available(int $timestart, int $timefinish, seminar_event $seminarevent): bool {
        global $DB, $USER;

       if ($this->get_hidden()) {
            // Hidden rooms can be assigned only if they are already used in the session.
            if (!$seminarevent->exists()) {
                return false;
            }
            $sql = "SELECT 'x'
                      FROM {facetoface_room_dates} frd
                      JOIN {facetoface_sessions_dates} fsd ON fsd.id = frd.sessionsdateid
                     WHERE frd.roomid = :roomid AND fsd.sessionid = :sessionid";

            if (!$DB->record_exists_sql($sql, ['roomid' => $this->id, 'sessionid' => $seminarevent->get_id()])) {
                return false;
            }
       }

       if ($this->get_custom()) {
            // Custom rooms can be used only if already used in seminar
            // or not used anywhere and created by current user.
            $sql = "SELECT 'x'
                      FROM {facetoface_room_dates} frd
                      JOIN {facetoface_sessions_dates} fsd ON fsd.id = frd.sessionsdateid
                      JOIN {facetoface_sessions} fs ON fs.id = fsd.sessionid
                     WHERE frd.roomid = :roomid AND fs.facetoface = :facetofaceid";

            if (!$DB->record_exists_sql($sql, ['roomid' => $this->id, 'facetofaceid' => $seminarevent->get_facetoface()])) {
                if ($this->usercreated == $USER->id) {
                    if ($DB->record_exists('facetoface_room_dates', array('roomid' => $this->id))) {
                        return false;
                    }
                } else {
                    return false;
                }
            }
       }

       if (!$timestart and !$timefinish) {
           // Time not specified, no need to verify conflicts.
           return true;
       }

       if ($this->allowconflicts) {
           // No need to worry about time slots.
           return true;
       }

       if ($timestart > $timefinish) {
           debugging('Invalid slot specified, start cannot be later than finish', DEBUG_DEVELOPER);
       }

       // Is there any other event using this room in this slot?
       // Note that there cannot be collisions in session dates of one event because they cannot overlap.
       $params = array('timestart' => $timestart, 'timefinish' => $timefinish, 'roomid' => $this->id, 'sessionid' => $seminarevent->get_id());

       $sql = "SELECT 'x'
             FROM {facetoface_room_dates} frd
             JOIN {facetoface_sessions_dates} fsd ON (fsd.id = frd.sessionsdateid)
             JOIN {facetoface_sessions} fs ON (fs.id = fsd.sessionid AND fs.cancelledstatus = 0)
            WHERE frd.roomid = :roomid AND fs.id <> :sessionid
                  AND :timefinish > fsd.timestart AND :timestart < fsd.timefinish";
       return !$DB->record_exists_sql($sql, $params);
    }

    /**
     * Find out if the room has any scheduling conflicts.
     * @return bool
     */
    public function has_conflicts(): bool {
        global $DB;

        $sql = "SELECT 'x'
              FROM {facetoface_sessions_dates} fsd
              JOIN {facetoface_room_dates} frd ON frd.sessionsdateid = fsd.id
              JOIN {facetoface_room_dates} frd2 ON frd2.roomid = frd.roomid
              JOIN {facetoface_sessions_dates} fsd2 ON (fsd2.id = frd2.sessionsdateid AND fsd2.id <> fsd.id)
              JOIN {facetoface_sessions} fs ON (fs.id = fsd.sessionid AND fs.cancelledstatus = 0)
             WHERE frd.roomid = :roomid
               AND ((fsd.timestart >= fsd2.timestart AND fsd.timestart < fsd2.timefinish) OR
                    (fsd.timefinish > fsd2.timestart AND fsd.timefinish <= fsd2.timefinish))";

        return $DB->record_exists_sql($sql, array('roomid' => $this->get_id()));
    }

    /**
     * Switch the class to a stdClass, add all the custom fields, and format the location field.
     *
     * @return array
     */
    public function get_customfield_array(): array {
        global $CFG;
        require_once($CFG->dirroot . '/totara/customfield/fieldlib.php');
        require_once($CFG->dirroot . '/totara/customfield/field/location/define.class.php');

        $cf = $this->to_record();

        $cfdata = customfield_get_data($cf, "facetoface_room", "facetofaceroom", false, ['extended' => false]);

        return (array)$cfdata;
    }
}
