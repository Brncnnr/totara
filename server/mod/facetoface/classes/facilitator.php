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

namespace mod_facetoface;

defined('MOODLE_INTERNAL') || die();

use \mod_facetoface\facilitator_user;
use \mod_facetoface\customfield_area\facetofacefacilitator as facilitatorcustomfield;

/**
 * Class Facilitator represents Seminar Facilitator
 */
final class facilitator implements seminar_iterator_item, seminar_attachment_item {

    use traits\crud_mapper;

    const FACILITATOR_NAME_LENGTH = 100;

    /**
     * @var int {facetoface_facilitator}.id
     */
    private $id = 0;

    /**
     * @var string {facetoface_facilitator}.name
     */
    private $name = '';

    /**
     * @var int {facetoface_facilitator}.userid
     */
    private $userid = 0;

    /**
     * @var int {facetoface_facilitator}.allowconflicts
     */
    private $allowconflicts = 0;

    /**
     * @var string {facetoface_facilitator}.description
     */
    private $description = '';

    /**
     * @var int {facetoface_facilitator}.custom
     */
    private $custom = 0;

    /**
     * @var int {facetoface_facilitator}.hidden
     */
    private $hidden = 0;

    /**
     * @var int {facetoface_facilitator}.usercreated
     */
    private $usercreated = 0;

    /**
     * @var int {facetoface_facilitator}.usermodified
     */
    private $usermodified = 0;

    /**
     * @var int {facetoface_facilitator}.timecreated
     */
    private $timecreated = 0;

    /**
     * @var int {facetoface_facilitator}.timemodified
     */
    private $timemodified = 0;

    /**
     * @var string facetoface facilitator table name
     */
    const DBTABLE = 'facetoface_facilitator';

    /**
     * Seminar facilitator constructor
     * @param int $id {facetoface_facilitator}.id If 0 - new Seminar facilitator will be created
     */
    public function __construct(int $id = 0) {
        if ((int)$id > 0) {
            $this->id = $id;
            $this->load();
        }
    }

    /**
     * Create a new facilitator with the custom flag set.
     * @return facilitator
     */
    public static function create_custom_facilitator(): facilitator {
        $facilitator = new static();
        $facilitator->custom = 1;
        return $facilitator;
    }

    /**
     * Load facilitator data from DB
     * @return facilitator this
     */
    public function load(): facilitator {
        return $this->crud_load();
    }

    /**
     * Map data object to class instance.
     * @param \stdClass $object
     * @return facilitator this
     */
    public function from_record(\stdClass $object): facilitator {
        $this->map_object($object, false);
        return $this;
    }

    /**
     * Map class instance to data object.
     * @return \stdClass
     */
    public function to_record(): \stdClass {
        return $this->unmap_object();
    }

    /**
     * Store facilitator into database
     * @return void
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
     * Remove facilitator from database
     * @return void
     */
    public function delete(): void {
        global $DB;

        $this->delete_customfields();
        $this->delete_embedded_files();

        // Unlink this facilitator from any session, then delete the facilitator
        $DB->delete_records('facetoface_facilitator_dates', array('facilitatorid' => $this->id));
        $DB->delete_records(self::DBTABLE, array('id' => $this->id));
        // Re-load instance with default values.
        $this->map_object((object)get_object_vars(new self()));
    }

    /**
     * Delete customfields associated with this facilitator
     * @return facilitator $this
     */
    private function delete_customfields(): facilitator {
        global $DB, $CFG;
        require_once("$CFG->dirroot/totara/customfield/fieldlib.php");

        $tblprefix = facilitatorcustomfield::get_prefix();
        $filearea = facilitatorcustomfield::get_area_name();
        // Delete all custom fields related to facilitator.
        $facilitatorfields = $DB->get_records("{$tblprefix}_info_field");
        foreach ($facilitatorfields as $facilitatorfield) {
            /** @var \customfield_base $customfieldentry */
            $customfieldentry = customfield_get_field_instance(
                $this->unmap_object(),
                $facilitatorfield->id,
                $tblprefix,
                $filearea
            );
            if (!empty($customfieldentry)) {
                $customfieldentry->delete();
            }
        }
        return $this;
    }

    /**
     * Deletes files associated with this facilitator
     * @return facilitator $this
     */
    private function delete_embedded_files(): facilitator {
        // Delete all files embedded in the facilitator description.
        $context = facilitatorcustomfield::get_context();
        $component = facilitatorcustomfield::get_component();
        $filearea = facilitatorcustomfield::get_area_name();
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, $component, $filearea, $this->id);
        return $this;
    }

    /**
     * Check whether the facilitator exists yet or not.
     * If the facilitator has been saved into the database the $id field should be non-zero.
     * @return bool - true if the facilitator has an $id, false if it hasn't
     */
    public function exists(): bool {
        return (bool)$this->id;
    }

    /**
     * Checks if the facilitator is in use anywhere
     * @return bool
     */
    public function is_used(): bool {
        global $DB;
        $count = $DB->count_records('facetoface_facilitator_dates', array('facilitatorid' => $this->id));
        return $count > 0;
    }

    /**
     * Switch an facilitator from a single use custom facilitator to a site wide reusable facilitator.
     * Note: that this function is instead of the set_custom() function, and it enforces
     *       the behaviour that an facilitator can not be republished if it is currently published.
     * @return facilitator this
     */
    public function publish(): facilitator {
        // Utilising identical check to prevent false positives when custom not yet set.
        if ($this->custom === false) {
            debugging(get_string('error:cannotrepublishfacilitator', 'mod_facetoface'), DEBUG_DEVELOPER);
            return $this;
        }
        $this->custom = (int)false;
        return $this;
    }

    /**
     * Check if facilitator is available during certain time slot.
     * Available facilitators are facilitators where the start- OR end times don't fall within that of another session's facilitator,
     * as well as facilitators where the start- AND end times don't encapsulate that of another session's facilitator
     * @param int $timestart
     * @param int $timefinish
     * @param seminar_event $seminarevent
     * @return bool
     */
    public function is_available(int $timestart, int $timefinish, seminar_event $seminarevent): bool {
        global $DB, $USER;

        // Hidden facilitators can be assigned only if they are already used in the session.
        if ($this->get_hidden()) {
            if (!$seminarevent->exists()) {
                return false;
            }
            $sql = "SELECT 'x'
                      FROM {facetoface_facilitator_dates} ffd
                      JOIN {facetoface_sessions_dates} fsd ON fsd.id = ffd.sessionsdateid
                     WHERE ffd.facilitatorid = :facilitatorid
                       AND fsd.sessionid = :sessionid";
            if (!$DB->record_exists_sql($sql, ['facilitatorid' => $this->id, 'sessionid' => $seminarevent->get_id()])) {
                return false;
            }
        }

        // Custom facilitators can be used only if already used in seminar, or not used anywhere and created by current user.
        if ($this->get_custom()) {
            $seminarid = $seminarevent->get_facetoface();

            $sql = "SELECT 'x'
                      FROM {facetoface_facilitator_dates} ffd
                      JOIN {facetoface_sessions_dates} fsd ON fsd.id = ffd.sessionsdateid
                      JOIN {facetoface_sessions} fs ON fs.id = fsd.sessionid
                     WHERE ffd.facilitatorid = :facilitatorid
                       AND fs.facetoface = :facetofaceid";
            if (!$DB->record_exists_sql($sql, array('facilitatorid' => $this->id, 'facetofaceid' => $seminarid))) {
                if ($this->usercreated == $USER->id) {
                    if ($DB->record_exists('facetoface_facilitator_dates', array('facilitatorid' => $this->id))) {
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

        if ($this->get_allowconflicts()) {
            // No need to worry about time slots.
            return true;
        }

        if ($timestart > $timefinish) {
            debugging('Invalid slot specified, start cannot be later than finish', DEBUG_DEVELOPER);
            return false;
        }

        // Is there any other event using this facilitator in this slot?
        // Note that there cannot be collisions in session dates of one event because they cannot overlap.
        $params = ['timestart' => $timestart, 'timefinish' => $timefinish, 'facilitatorid' => $this->id, 'sessionid' => $seminarevent->get_id()];

        $sql = "SELECT 'x'
              FROM {facetoface_facilitator_dates} ffd
              JOIN {facetoface_sessions_dates} fsd ON fsd.id = ffd.sessionsdateid
              JOIN {facetoface_sessions} fs ON (fs.id = fsd.sessionid AND fs.cancelledstatus = 0)
             WHERE ffd.facilitatorid = :facilitatorid AND fs.id <> :sessionid
                   AND :timefinish > fsd.timestart AND :timestart < fsd.timefinish";
        return !$DB->record_exists_sql($sql, $params);
    }

    /**
     * Find out if facilitator has scheduling conflicts.
     * @return bool
     */
    public function has_conflicts(): bool {
        global $DB;

        $sql = "SELECT 'x'
              FROM {facetoface_sessions_dates} fsd
              JOIN {facetoface_facilitator_dates} ffd ON ffd.sessionsdateid = fsd.id
              JOIN {facetoface_facilitator_dates} ffd2 ON ffd2.facilitatorid = ffd.facilitatorid
              JOIN {facetoface_sessions_dates} fsd2 ON (fsd2.id = ffd2.sessionsdateid AND fsd2.id <> fsd.id)
              JOIN {facetoface_sessions} fs ON (fs.id = fsd.sessionid AND fs.cancelledstatus = 0)
             WHERE ffd.facilitatorid = :facilitatorid AND
                   ((fsd.timestart >= fsd2.timestart AND fsd.timestart < fsd2.timefinish)
                    OR (fsd.timefinish > fsd2.timestart AND fsd.timefinish <= fsd2.timefinish))";
        return $DB->record_exists_sql($sql, array('facilitatorid' => $this->id));
    }

    /**
     * Get facilitator ID
     * @return int
     */
    public function get_id(): int {
        return (int)$this->id;
    }

    /**
     * Get facilitator user ID
     * @return int
     */
    public function get_userid(): int {
        return (int)$this->userid;
    }

    /**
     * @param int $userid
     * @return facilitator
     */
    public function set_userid(int $userid): facilitator {
        $this->userid = (int)$userid;
        return $this;
    }

    /**
     * Get name for facilitator
     * @return string
     */
    public function get_name(): string {
        return (string)$this->name;
    }

    /**
     * Set name for facilitator
     * @param string $name Name to give the facilitator
     * @return facilitator
     */
    public function set_name(string $name): facilitator {
        $this->name = (string)$name;
        return $this;
    }

    /**
     * Gets the facilitator description
     * @return string facilitator description
     */
    public function get_description(): string {
        return (string)$this->description;
    }

    /**
     * Sets the facilitator description
     * @param string $description facilitator description
     * @return facilitator $this
     */
    public function set_description(string $description): facilitator {
        $this->description = (string)$description;
        return $this;
    }

    /**
     * Get whether this facilitator allows conflicts
     * @return bool
     */
    public function get_allowconflicts(): bool {
        return (bool)$this->allowconflicts;
    }

    /**
     * Set whether this facilitator allows conflicts
     * @param int $allowconflicts
     *
     * @return facilitator $this
     */
    public function set_allowconflicts(int $allowconflicts): facilitator {
        $this->allowconflicts = (int)$allowconflicts;
        return $this;
    }

    /**
     * Get the id of the user who created this facilitator
     * @return int
     */
    public function get_usercreated(): int {
        return (int)$this->usercreated;
    }

    /**
     * Get the id of the user who last modified this facilitator
     * @return int
     */
    public function get_usermodified(): int {
        return (int)$this->usermodified;
    }

    /**
     * Set the user who last modified this facilitator
     * @param int $usermodified
     * @return facilitator $this
     */
    public function set_usermodified(int $usermodified): facilitator {
        $this->usermodified = (int)$usermodified;
        return $this;
    }

    /**
     * Get the time this facilitator was created
     * @return int
     */
    public function get_timecreated(): int {
        return (int)$this->timecreated;
    }

    /**
     * Get the time this facilitator was last modified
     * @return int
     */
    public function get_timemodified(): int {
        return (int)$this->timemodified;
    }

    /**
     * Set the time this facilitator was modified
     * @param int $timemodified
     * @return facilitator $this
     */
    public function set_timemodified(int $timemodified): facilitator {
        $this->timemodified = (int)$timemodified;
        return $this;
    }

    /**
     * Get whether this facilitator is custom
     * Note: There is no setter for this field as it can only move in
     *       one direction, this is controlled by the publish function.
     * @return bool
     */
    public function get_custom(): bool {
        return (bool)$this->custom;
    }

    /**
     * Get whether this facilitator is hidden
     * Note: There is no setter for this field, please use the hide()
     *       and show() functions instead.
     * @return bool
     */
    public function get_hidden(): bool {
        return (bool)$this->hidden;
    }

    /**
     * Hides this facilitator
     * Note: This is the equivalent of set_hidden(true);
     * @return facilitator $this
     */
    public function hide(): facilitator {
        $this->hidden = (int)true;
        return $this;
    }

    /**
     * Shows the facilitator
     * Note: This is the equivalent of set_hidden(false);
     * @return facilitator $this
     */
    public function show(): facilitator {
        $this->hidden = (int)false;
        return $this;
    }

    /**
     * Check if the current user is not deleted or suspened.
     * @return bool
     */
    public function can_show(): bool {
        if ($this->userid == 0) {
            return true;
        }
        return facilitator_user::is_userid_active($this->userid);
    }
}