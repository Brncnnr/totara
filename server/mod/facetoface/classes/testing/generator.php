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
 * facetoface module data generator class
 *
 * @package    mod_facetoface
 * @author     Maria Torres <maria.torres@totaralms.com>
 * @author     Nathan Lewis <nathan.lewis@totaralms.com>
 * @author     Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 *
 */

namespace mod_facetoface\testing;

use stdClass, coding_exception;
use core\plugininfo\virtualmeeting;
use totara_job\job_assignment;
use mod_facetoface\seminar;
use mod_facetoface\signup;
use mod_facetoface\signup_helper;
use mod_facetoface\seminar_event;
use mod_facetoface\seminar_event_helper;
use mod_facetoface\calendar;
use mod_facetoface\room_virtualmeeting;

final class generator extends \core\testing\mod_generator {

    /**
     * The number of rooms created so far.
     * @var int
     */
    protected $roominstancecount = 0;

    /**
     * The number of assets created so far.
     * @var int
     */
    protected $assetinstancecount = 0;

    /**
     * The number of facilitators created so far.
     * @var int
     */
    protected $facilitatorinstancecount = 0;

    /**
     * Cache to reduce lookups.
     * @var array
     */
    protected $mapsessioncourse = [];

    /**
     * Cache to reduce lookups.
     * @var array
     */
    protected $mapsessionf2f = [];


    /**
     * Create new facetoface module instance
     * @param array|stdClass $record
     * @param array $options
     * @throws coding_exception
     * @return stdClass activity record with extra cmid field
     */
    public function create_instance($record = null, array $options = null) {
        global $CFG;
        require_once("$CFG->dirroot/mod/facetoface/lib.php");

        $this->instancecount++;
        $i = $this->instancecount;

        $record = (object)(array)$record;
        $options = (array)$options;

        if (empty($record->course)) {
            throw new coding_exception('module generator requires $record->course');
        }

        if (!in_array($record->sessionattendance ?? 0, seminar::SESSION_ATTENDANCE_VALID_VALUES)) {
            debugging('$record->sessionattendance is not a valid value.', DEBUG_DEVELOPER);
        }
        if (!in_array($record->attendancetime ?? 0, seminar::EVENT_ATTENDANCE_VALID_VALUES)) {
            debugging('$record->attendancetime is not a valid value.', DEBUG_DEVELOPER);
        }

        $defaults = array();
        $defaults['intro'] = 'Test facetoface ' . $i;
        $defaults['introformat'] = FORMAT_MOODLE;
        $defaults['name'] = get_string('pluginname', 'facetoface').' '.$i;
        $defaults['shortname'] = 'facetoface' . $i;
        $defaults['description'] = 'description';
        $defaults['thirdparty'] = null; // Default to username
        $defaults['thirdpartywaitlist'] = 0;
        $defaults['display'] = 6;
        $defaults['showoncalendar'] = '1';
        $defaults['approvaloptions'] = 'approval_none';
        $defaults['usercalentry'] = 1;
        $defaults['multiplesessions'] = 0;
        $defaults['multisignupmaximum'] = 0;
        $defaults['multisignupnoshow'] = 0;
        $defaults['multisignuppartly'] = 0;
        $defaults['multisignupfully'] = 0;
        $defaults['multisignupunableto'] = 0;
        $defaults['completionstatusrequired'] = '{"100":1}';
        $defaults['managerreserve'] = 0;
        $defaults['maxmanagerreserves'] = 1;
        $defaults['reservecanceldays'] = 1;
        $defaults['reservedays'] = 2;
        $defaults['decluttersessiontable'] = 0;
        $defaults['sessionattendance'] = seminar::SESSION_ATTENDANCE_DEFAULT;
        $defaults['attendancetime'] = seminar::EVENT_ATTENDANCE_DEFAULT;
        $defaults['eventgradingmanual'] = 0;
        $defaults['eventgradingmethod'] = seminar::GRADING_METHOD_DEFAULT;
        $defaults['completionpass'] = seminar::COMPLETION_PASS_DISABLED;
        $defaults['completiondelay'] = null;
        $defaults['legacy_notifications'] = 1;

        foreach ($defaults as $field => $value) {
            if (!isset($record->$field)) {
                $record->$field = $value;
            }
        }

        if (isset($options['idnumber'])) {
            $record->cmidnumber = $options['idnumber'];
        } else {
            $record->cmidnumber = '';
        }

        $record->coursemodule = $this->precreate_course_module($record->course, $options);
        $id = facetoface_add_instance($record, null);
        return $this->post_add_instance($id, $record->coursemodule);
    }

    /**
     * Add facetoface session aka seminar event
     * @param array|stdClass $record
     * @param array $options not used
     * @throws coding_exception
     * @return bool|int session created
     */
    public function add_session($record, $options = array()) {
        global $USER, $CFG;
        require_once("$CFG->dirroot/mod/facetoface/lib.php");

        $record = (object) (array) $record;

        if (empty($record->facetoface)) {
            throw new coding_exception('Session generator requires $record->facetoface');
        }

        if (!isset($record->sessiondates) && empty($record->sessiondates)) {
            $time = time();
            $sessiondate = new stdClass();
            $sessiondate->timestart = $time;
            $sessiondate->timefinish = $time + (DAYSECS * 2);
            $sessiondate->sessiontimezone = 'Pacific/Auckland';
            $sessiondate->roomids = array();
            $sessiondate->assetids = array();
            $sessiondate->facilitatorids = array();
            $sessiondates = array($sessiondate);
        } else {
            $sessiondates = array_map(function ($date) {
                if (is_number($date)) {
                    $sessiondate = new stdClass();
                    $sessiondate->timestart = (int)$date;
                    $sessiondate->timefinish = (int)$date + (DAYSECS * 2);
                    $sessiondate->sessiontimezone = 'Pacific/Auckland';
                    $sessiondate->roomids = array();
                    $sessiondate->assetids = array();
                    $sessiondate->facilitatorids = array();
                    return $sessiondate;
                } else {
                    if (isset($date->roomid)) {
                        throw new coding_exception('roomid is no longer valid. please use roomids instead.');
                    }
                    return (object) (array) $date;
                }
            }, $record->sessiondates);
            unset($record->sessiondates);
        }
        foreach ($sessiondates as $sessiondate) {
            if ($sessiondate->timestart >= $sessiondate->timefinish) {
                throw new coding_exception("The session start time {$sessiondate->timestart} is later than the finish time {$sessiondate->timefinish}.");
            }
        }

        if (!isset($record->capacity)) {
            $record->capacity = 10;
        }
        if (!isset($record->allowoverbook)) {
            $record->allowoverbook = 0;
        }
        if (!isset($record->normalcost)) {
            $record->normalcost = '$100';
        }
        if (!isset($record->discountcost)) {
            $record->discountcost = '$NZ20';
        }
        if (!isset($record->discountcost)) {
            $record->discountcost = FORMAT_MOODLE;
        }
        if (!isset($record->timemodified)) {
            $record->timemodified = time();
        }
        if (!isset($record->waitlisteveryone)) {
            $record->waitlisteveryone = 0;
        }
        if (!isset($record->registrationtimestart)) {
            $record->registrationtimestart = 0;
        }
        if (!isset($record->registrationtimefinish)) {
            $record->registrationtimefinish = 0;
        }

        $record->usermodified = $USER->id;

        $seminarevent = new \mod_facetoface\seminar_event();
        $seminarevent->from_record($record);
        $seminarevent->save();
        seminar_event_helper::merge_sessions($seminarevent, $sessiondates);

        // Make calendar entries.
        calendar::update_entries($seminarevent);

        return $seminarevent->get_id();
    }

    /**
     * Create a room - please use the add_custom_room, or add_site_wide_room methods.
     *
     * @param stdClass|array $record
     * @return mixed
     */
    protected function add_room($record) {
        global $DB, $USER;

        $this->roominstancecount++;
        $record = (object)$record;

        if (!isset($record->name)) {
            $record->name = 'Room '.$this->roominstancecount;
        }
        if (!isset($record->capacity)) {
            // Don't ever bet on the capacity, if you need to be something specific set it to that.
            $record->capacity = floor(rand(5, 50));
        }

        if (!empty($record->allowconflicts)) {
            $record->allowconflicts = 1;
        } else {
            $record->allowconflicts = 0;
        }

        if (empty($record->url)) {
            $record->url = '';
        }

        if (!isset($record->description)) {
            $record->description = 'Description for room '.$this->roominstancecount;
        }
        if (!isset($record->custom)) {
            $record->custom = 1;
        }
        if (!isset($record->usercreated)) {
            $record->usercreated = $USER->id;
        }
        $record->usermodified = $record->usercreated;
        if (!isset($record->usercreated)) {
            $record->usercreated = $USER->id;
        }
        if (!isset($record->timecreated)) {
            $record->timecreated = time();
        }
        $record->timemodified = $record->timecreated;
        $id = $DB->insert_record('facetoface_room', $record);
        return $DB->get_record('facetoface_room', array('id' => $id));
    }

    /**
     * Add a custom room.
     *
     * @param stdClass|array $record
     * @return stdClass
     */
    public function add_custom_room($record) {
        $record = (object)$record;
        $record->custom = 1;
        return $this->add_room($record);
    }

    /**
     * Add a site wide room.
     *
     * @param stdClass|array $record
     * @return stdClass
     */
    public function add_site_wide_room($record) {
        $record = (object)$record;
        $record->custom = 0;
        return $this->add_room($record);
    }

    /**
     * Add a virtualmeeting room.
     *
     * @param stdClass|array $record
     * @param array $options
     * @return stdClass
     */
    public function add_virtualmeeting_room($record, $options = array()): stdClass {
        global $DB, $USER;

        if (!virtualmeeting::is_poc_available()) {
            throw new coding_exception('add_virtualmeeting_room() is available only if poc plugins are available.');
        }

        // Insert a room.
        $record = (object)$record;
        $record->custom = 1;
        $record->url = '';
        $room = $this->add_room($record);

        // Insert room_virtualmeeting
        $frvm = new stdClass();
        if (empty($options['userid'])) {
            $options['userid'] = $USER->id;
        }
        if (empty($options['plugin'])) {
            $options['plugin'] = 'poc_app';
        }
        if (!array_key_exists('status', $options)) {
            $options['status'] = room_virtualmeeting::STATUS_CONFIRMED;
        }
        $frvm->userid = $options['userid'];
        $frvm->plugin = $options['plugin'];
        $frvm->roomid = $room->id;
        $frvm->status = $options['status'];
        $DB->insert_record('facetoface_room_virtualmeeting', $frvm);

        return $room;
    }

    /**
     * Validate record fields for behat.
     *
     * @param array $record
     * @param string[] $musthavefields array of field names $record must have
     * @param string[] $mustnothavefields array of field names $record must not have
     * @throws coding_exception
     */
    private function validate_record_for_behat(array $record, array $musthavefields, array $mustnothavefields) {
        if (empty($musthavefields) && empty($mustnothavefields)) {
            throw new coding_exception('No constraints specified');
        }
        foreach ($musthavefields as $fieldname) {
            if (!isset($record[$fieldname])) {
                throw new coding_exception("The field {$fieldname} is mandatory");
            }
        }
        foreach ($mustnothavefields as $fieldname) {
            if (isset($record[$fieldname])) {
                throw new coding_exception("The field {$fieldname} is not accepted");
            }
        }
    }

    /**
     * Resolve record fields for behat.
     *
     * @param array $record
     * @param boolean $usernametouserid
     * @return void
     */
    private function translate_record_for_behat(array &$record, bool $usernametouserid = false) {
        global $CFG;
        foreach (['usercreated', 'usermodified'] as $fieldname) {
            if (isset($record[$fieldname])) {
                $user = \core_user::get_user_by_username($record[$fieldname], 'id');
                if ($user) {
                    $record[$fieldname] = $user->id;
                } else {
                    // Remove the field if a user is not found.
                    unset($record[$fieldname]);
                }
            }
        }
        if ($usernametouserid && isset($record['username']) && (string)$record['username'] !== '') {
            $user = \core_user::get_user_by_username($record['username'], 'id', null, MUST_EXIST);
            $record['userid'] = $user->id;
            unset($record['username']);
        }
        if (isset($record['url']) && $record['url'] === '/mod/facetoface/tests/fixtures/bph4svcr.php') {
            $record['url'] = $CFG->wwwroot . $record['url'];
        }
        // Add any adjustments here if necessary.
    }

    /**
     * Create a asset - please use the add_custom_asset, or add_site_wide_asset methods.
     *
     * @param stdClass|array $record
     * @return mixed
     */
    protected function add_asset($record) {
        global $DB, $USER;

        $this->assetinstancecount++;
        $record = (object) $record;

        if (!isset($record->name)) {
            $record->name = 'asset '.$this->assetinstancecount;
        }

        if (!empty($record->allowconflicts)) {
            $record->allowconflicts = 1;
        } else {
            $record->allowconflicts = 0;
        }

        if (!isset($record->description)) {
            $record->description = 'Description for asset '.$this->assetinstancecount;
        }
        if (!isset($record->custom)) {
            $record->custom = 1;
        }
        if (!isset($record->usercreated)) {
            $record->usercreated = $USER->id;
        }
        $record->usermodified = $record->usercreated;
        if (!isset($record->usercreated)) {
            $record->usercreated = $USER->id;
        }
        if (!isset($record->timecreated)) {
            $record->timecreated = time();
        }
        $record->timemodified = $record->timecreated;
        $id = $DB->insert_record('facetoface_asset', $record);
        return $DB->get_record('facetoface_asset', array('id' => $id));
    }

    /**
     * Add a custom asset.
     *
     * @param stdClass|array $record
     * @return stdClass
     */
    public function add_custom_asset($record) {
        $record = (object)$record;
        $record->custom = 1;
        return $this->add_asset($record);
    }

    /**
     * Add a site wide asset.
     *
     * @param stdClass|array $record
     * @return stdClass
     */
    public function add_site_wide_asset($record) {
        $record = (object)$record;
        $record->custom = 0;
        return $this->add_asset($record);
    }

    /**
     * Create a facilitator - please use the add_custom_facilitator, or add_site_wide_facilitator methods.
     * @param stdClass|array $record
     * @return mixed
     */
    protected function add_facilitator($record) {
        global $DB, $USER;

        $this->facilitatorinstancecount++;
        $record = (object)$record;

        if (!isset($record->name)) {
            $record->name = 'facilitator '.$this->facilitatorinstancecount;
        }

        if (!empty($record->allowconflicts)) {
            $record->allowconflicts = 1;
        } else {
            $record->allowconflicts = 0;
        }

        if (!isset($record->description)) {
            $record->description = 'Description for facilitator '.$this->facilitatorinstancecount;
        }
        if (!isset($record->custom)) {
            $record->custom = 1;
        }
        if (!isset($record->usercreated)) {
            $record->usercreated = $USER->id;
        }
        $record->usermodified = $record->usercreated;
        if (!isset($record->usercreated)) {
            $record->usercreated = $USER->id;
        }
        if (!isset($record->timecreated)) {
            $record->timecreated = time();
        }
        $record->timemodified = $record->timecreated;
        $id = $DB->insert_record('facetoface_facilitator', $record);
        return $DB->get_record('facetoface_facilitator', array('id' => $id));
    }

    /**
     * Add a custom facilitator.
     * @param stdClass|array $record
     * @return stdClass
     */
    public function add_custom_facilitator($record) {
        $record = (object)$record;
        $record->custom = 1;
        return $this->add_facilitator($record);
    }

    /**
     * Add a site wide facilitator.
     * @param stdClass|array $record
     * @return stdClass
     */
    public function add_site_wide_facilitator($record) {
        $record = (object)$record;
        $record->custom = 0;
        return $this->add_facilitator($record);
    }

    /**
     * Add an internal (site wide only) facilitator.
     * @param stdClass|array $record
     * @return stdClass
     */
    public function add_internal_facilitator($record = null, stdClass $user = null) {
        if (empty($record)) {
            $record = new \stdClass();
        } else {
            $record = (object)$record;
        }
        if (empty($user)) {
            $user = $this->datagenerator->create_user();
        }

        $record->custom = 0;
        $record->userid = $user->id;
        if (empty($record->name)) {
            $record->name = 'Facilitator ' . $user->firstname . ' ' . $user->lastname;
        }
        return $this->add_facilitator($record);
    }

    /**
     * Resets this generator instance.
     */
    public function reset() {
        $this->roominstancecount = 0;
        $this->assetinstancecount = 0;
        $this->facilitatorinstancecount = 0;
        parent::reset();
    }

    /**
     * Create facetoface content (Session)
     * @param stdClass $instance
     * @param array|stdClass $record
     * @return bool|int content created
     */
    public function create_content($instance, $record = array()) {
        $record = (array)$record + array(
                'facetoface' => $instance->id
            );

        return $this->add_session($record);
    }

    /**
     * Create a session for the given course.
     * Creates facetoface for the session as well.
     *
     * @param stdClass $course
     * @param int $daysoffset how many days from now session will occur
     * @return seminar_event
     */
    public function create_session_for_course(stdClass $course, int $daysoffset = 1): seminar_event {
        // Set up facetoface.
        $facetofacedata = [
            'name' => 'facetoface1',
            'course' => $course->id
        ];
        $facetoface = $this->create_instance($facetofacedata);

        // Set up session.
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + $daysoffset * DAYSECS;
        $sessiondate->timefinish = time() + $daysoffset * DAYSECS + 60;
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $sessiondata = [
            'facetoface' => $facetoface->id,
            'sessiondates' => [$sessiondate],
        ];
        $sessionid = $this->add_session($sessiondata);

        $this->mapsessioncourse[$sessionid] = $course;
        $this->mapsessionf2f[$sessionid] = $facetoface;

        return new seminar_event($sessionid);
    }

    /**
     * Create a signup for given student and session.
     *
     * @param stdClass $student
     * @param seminar_event $seminarevent
     * @return stdClass
     */
    public function create_signup(stdClass $student, \mod_facetoface\seminar_event $seminarevent): stdClass {
        global $DB;

        $this->create_job_assignment_if_not_exists($student);

        $discountcode = 'disc1';
        $notificationtype = 1;

        $signup = \mod_facetoface\signup::create($student->id, $seminarevent, $notificationtype);
        $signup->set_discountcode($discountcode);
        signup_helper::signup($signup);

        return $DB->get_record('facetoface_signups', ['userid' => $student->id, 'sessionid' => $seminarevent->get_id()]);
    }

    /**
     * @param stdClass $student
     * @param seminar_event $seminarevent
     */
    public function create_cancellation(stdClass $student, seminar_event $seminarevent) {
        $signup = signup::create($student->id, $seminarevent);
        if (signup_helper::can_user_cancel($signup)) {
            signup_helper::user_cancel($signup);
        }
    }

    /**
     * @param stdClass $signup
     * @param string $type
     * @param string $filename
     * @param int $itemid  Any integer. Use the same number if you want multiple files for
     *  the same field. See \totara_customfield\testing\generator::create_test_file_from_content().
     * @return \stored_file
     */
    public function create_file_customfield(stdClass $signup, string $type, string $filename, int $itemid) {
        global $DB;

        $datagenerator = $this->datagenerator;
        $cfgenerator = \totara_customfield\testing\generator::instance();
        $cfid = $cfgenerator->create_file("facetoface_{$type}", ['f2ffile' => []]);

        $filecontent = 'Test file content';
        $filepath = '/';
        $cfgenerator->create_test_file_from_content($filename, $filecontent, $itemid, $filepath, $signup->userid);

        $cfgenerator->set_file($signup, $cfid['f2ffile'], $itemid, "facetoface{$type}", "facetoface_{$type}");

        $customfieldid = $DB->get_field(
            "facetoface_{$type}_info_data",
            'id',
            ["facetoface{$type}id" => $signup->id, 'fieldid' => $cfid['f2ffile']]
        );

        $syscontext = \context_system::instance();
        $fs = get_file_storage();
        $file = $fs->get_file(
            $syscontext->id,
            'totara_customfield',
            "facetoface{$type}_filemgr",
            $customfieldid,
            $filepath,
            $filename
        );
        return $file;
    }

    /**
     * Create some customfield data that results in the given amount of field and parameter data.
     *
     * @param stdClass $signup
     * @param string $type
     * @param int $fieldcount
     * @param int $paramcount
     * @return array array of facetoface_$type_info_data ids
     */
    public function create_customfield_data(stdClass $signup, string $type, int $fieldcount, int $paramcount): array {
        global $DB;

        $datagenerator = $this->datagenerator;
        $cfgenerator = \totara_customfield\testing\generator::instance();

        if ($fieldcount < 1) {
            return [];
        }

        $customfieldids = [];
        if ($paramcount) {
            // If we want data in the *info_data_param table, we need one multiselect field with the desired number of options.

            // Create options.
            $options = array_map(function($i) use ($type) {
                return "{$type}_option_{$i}";
            }, range(1, $paramcount));

            // Create customfield.
            $uniquefieldname = "{$type}_multi_{$signup->id}";
            $cfids = $cfgenerator->create_multiselect("facetoface_{$type}", [$uniquefieldname => $options]);

            // Create customfield data with all options selected.
            $cfgenerator->set_multiselect($signup, $cfids[$uniquefieldname], $options, "facetoface{$type}", "facetoface_{$type}");

            $fieldcount --;

            $customfieldids[] = $DB->get_field(
                "facetoface_{$type}_info_data",
                'id',
                ["facetoface{$type}id" => $signup->id, 'fieldid' => $cfids[$uniquefieldname]]
            );
        }

        // Use text field for the other customfields that don't need data in the *info_data_param table.
        for ($i = 1; $i <= $fieldcount; $i ++) {
            $uniquefieldname = "{$type}_text_{$signup->id}_{$i}";
            $cfids = $cfgenerator->create_text("facetoface_{$type}", [$uniquefieldname]);
            $cfgenerator->set_text($signup, $cfids[$uniquefieldname], "value_{$i}", "facetoface{$type}", "facetoface_{$type}");

            $customfieldids[] = $DB->get_field(
                "facetoface_{$type}_info_data",
                'id',
                ["facetoface{$type}id" => $signup->id, 'fieldid' => $cfids[$uniquefieldname]]
            );
        }

        return $customfieldids;
    }

    /**
     * Students need job assignments with manager so we can sign them up to a facetoface session.
     *
     * @param stdClass $student
     */
    protected function create_job_assignment_if_not_exists(stdClass $student) {
        global $DB;
        // Skip if we already created it.
        if (!$DB->record_exists('job_assignment', ['userid' => $student->id])) {
            $datagenerator = $this->datagenerator;
            $manager = $datagenerator->create_user();
            $managerja = job_assignment::create_default($manager->id);
            $data = [
                'userid' => $student->id,
                'fullname' => 'student1ja',
                'shortname' => 'student1ja',
                'idnumber' => 'student1ja',
                'managerjaid' => $managerja->id,
            ];
            job_assignment::create($data);
        }
    }

    /**
     * Add a site-wide room.
     * @param array $record
     * @return stdClass
     */
    public function create_global_room_for_behat(array $record): stdClass {
        $this->validate_record_for_behat($record, [], ['custom']);
        $this->translate_record_for_behat($record);
        return $this->add_site_wide_room($record);
    }

    /**
     * Add an ad-hoc room.
     * @param array $record
     * @return stdClass
     */
    public function create_custom_room_for_behat(array $record): stdClass {
        $this->validate_record_for_behat($record, [], ['custom']);
        $this->translate_record_for_behat($record);
        return $this->add_custom_room($record);
    }

    /**
     * Add a site-wide asset.
     * @param array $record
     * @return stdClass
     */
    public function create_global_asset_for_behat(array $record): stdClass {
        $this->validate_record_for_behat($record, [], ['custom']);
        $this->translate_record_for_behat($record);
        return $this->add_site_wide_asset($record);
    }

    /**
     * Add an ad-hoc asset.
     * @param array $record
     * @return stdClass
     */
    public function create_custom_asset_for_behat(array $record): stdClass {
        $this->validate_record_for_behat($record, [], ['custom']);
        $this->translate_record_for_behat($record);
        return $this->add_custom_asset($record);
    }

    /**
     * Add a site-wide facilitator.
     * @param array $record
     * @return stdClass
     */
    public function create_global_facilitator_for_behat(array $record): stdClass {
        $this->validate_record_for_behat($record, [], ['custom', 'userid']);
        $this->translate_record_for_behat($record, true);
        return $this->add_site_wide_facilitator($record);
    }

    /**
     * Add an ad-hoc facilitator.
     * @param array $record
     * @return stdClass
     */
    public function create_custom_facilitator_for_behat(array $record): stdClass {
        $this->validate_record_for_behat($record, [], ['custom', 'userid', 'username']);
        $this->translate_record_for_behat($record);
        return $this->add_custom_facilitator($record);
    }

    /**
     * Add session attendance status record
     * @param int $signupid
     * @param int $sessiondateid
     * @param array|stdClass $sessionstatus
     * @return stdClass
     */
    public function add_session_status(int $signupid, int $sessiondateid, $sessionstatus = null): stdClass {
        global $DB, $USER;
        $sessionstatus = (array)$sessionstatus;

        $sessionstatus['signupid'] = $signupid;
        $sessionstatus['sessiondateid'] = $sessiondateid;
        $sessionstatus['superceded'] = 0;

        if (!isset($sessionstatus['attendancecode'])) {
            $sessionstatus['attendancecode'] = \mod_facetoface\signup\state\partially_attended::get_code();
        }
        if (!isset($sessionstatus['createdby'])) {
            $sessionstatus['createdby'] = $USER->id;
        }
        if (!isset($sessionstatus['timecreated'])) {
            $sessionstatus['timecreated'] = time();
        }
        $DB->execute(
            'UPDATE {facetoface_signups_dates_status} SET superceded = 1 WHERE signupid = :signupid AND sessiondateid = :sessiondateid',
            ['signupid' => $signupid, 'sessiondateid' => $sessiondateid]
        );
        $id = $DB->insert_record('facetoface_signups_dates_status', (object)$sessionstatus);
        return $DB->get_record('facetoface_signups_dates_status', ['id' => $id]);
    }

    /**
     * The identifier 'course' could either be a cousre shortname or course idnumber. As long as it is being found
     * within the storage, then we are able to create an instance within the system.
     *
     * @param array $record
     * @return int
     */
    public function create_instance_for_behat(array $record) {
        global $DB;

        if (!isset($record['course'])) {
            throw new coding_exception("No property 'course' defined in \$record");
        }

        $course = $record['course'];
        $copy = $record;

        if (!is_numeric($course)) {
            // Must be course shortname or idnumber, therefore it needs to find the courseid base on this shortname
            $courseid = $DB->get_field('course', 'id', ['shortname' => $course]);

            if (!$courseid) {
                // If the course is not found by shortname, then idnumber is our next try.
                $courseid = $DB->get_field('course', 'id', ['idnumber' => $course]);
                if (!$courseid) {
                    throw new coding_exception("The property 'course' must be a shortname of course");
                }
            }

            $copy['course'] = $courseid;
        }

        $options = [];
        if (isset($record['idnumber'])) {
            $options['idnumber'] = $record['idnumber'];
        }

        if (isset($record['multisignupamount'])) {
            // Taken from mod/facetoface/mod_form.php.
            $copy['multiplesessions'] = $record['multisignupamount'] != 1;
            $copy['multisignupmaximum'] = (int)$record['multisignupamount'];
            unset($copy['multisignupamount']);
        }

        $instance = $this->create_instance($copy, $options);
        return $instance->id;
    }

    /**
     * @param array $record
     * @return int|bool
     */
    public function create_sessions_for_behat(array $record) {
        return generator_util::create_session_for_behat($record);
    }

    /**
     * For start/finish time, we use the format that php is supporting. Therefore, please provided it if
     * @see https://www.php.net/manual/en/datetime.formats.relative.php
     * @param array $record
     * @return int
     */
    public function create_sessiondates_for_behat(array $record) {
        return generator_util::create_sessiondates_for_behat($record);
    }

    /**
     * @param array $record
     *
     * @return int
     */
    public function create_signups_for_behat(array $record) {
        return generator_util::create_signups_for_behat($record);
    }

    /**
     * @param array $record
     * @return stdClass
     */
    public function create_custom_field_for_behat(array $record) {
        $gen = \totara_core\testing\generator::instance();
        $prefix = 'facetoface_'.$record['prefix'];
        unset($record['prefix']);
        return $gen->create_custom_field($prefix, $record);
    }

    /**
     * Age the timecreated of asset
     *
     * @param string $name Asset name to age
     * @param int $seconds Number of seconds to age
     * @return void
     */
    public function age_asset_timecreated(string $name, int $seconds): void {
        $this->age_thing_timecreated('facetoface_asset', $name, $seconds);
    }

    /**
     * Age the timecreated of facilitator
     *
     * @param string $name facilitator name to age
     * @param int $seconds Number of seconds to age
     * @return void
     */
    public function age_facilitator_timecreated(string $name, int $seconds): void {
        $this->age_thing_timecreated('facetoface_facilitator', $name, $seconds);
    }

    /**
     * Age the timecreated of room
     *
     * @param string $name room name to age
     * @param int $seconds Number of seconds to age
     * @return void
     */
    public function age_room_timecreated(string $name, int $seconds): void {
        $this->age_thing_timecreated('facetoface_room', $name, $seconds);
    }

    /**
     * Age the timecreated
     *
     * @param string $tablename facetoface_{asset/facilitator/room} sql table name
     * @param string $name Asset name to age
     * @param int $seconds Number of seconds to age
     * @return void
     */
    private function age_thing_timecreated(string $tablename, string $name, int $seconds): void {
        global $DB;

        $timecreated = (int)$DB->get_field($tablename, 'timecreated', ['name' => $name], IGNORE_MULTIPLE);
        if (!$timecreated) {
            throw new coding_exception(
                "The thing with the {$name} name does not exists"
            );
        }

        // Age the time.
        $timecreated = $timecreated - $seconds;

        $sql = "UPDATE {{$tablename}}
                   SET timecreated = :timecreated
                 WHERE name = :name";
        $DB->execute($sql, ['name' => $name, 'timecreated' => $timecreated]);
    }
}
