<?php
/*
 * This file is part of Totara LMS
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_facetoface
 */
namespace mod_facetoface\testing;

use stdClass, coding_exception;
use mod_facetoface\{signup, signup_status, seminar_event, seminar, calendar};
use mod_facetoface\signup\state\{
    attendance_state,
    booked,
    event_cancelled,
    waitlisted,
    requested,
    requestedadmin,
    requestedrole,
    fully_attended,
    partially_attended,
    unable_to_attend,
    no_show,
    state,
    not_set,
    user_cancelled
};

defined('MOODLE_INTERNAL') || die();

final class generator_util {
    /**
     * Map for status in behat step with the one in code
     * @var array
     */
    private static $map = [
        'booked' => booked::class,
        'waitlisted' => waitlisted::class,
        'requested' => requested::class,
        'requestedadmin' => requestedadmin::class,
        'requestedrole' => requestedrole::class,
        'fully_attended' => fully_attended::class,
        'partially_attended' => partially_attended::class,
        'unable_to_attend' => unable_to_attend::class,
        'no_show' => no_show::class,
        'event_cancelled' => event_cancelled::class,
        'user_cancelled' => user_cancelled::class,
    ];

    private static function get_event_id_from_detail(string $details): int {
        global $DB;
        $sql = 'SELECT id FROM {facetoface_sessions} WHERE details = ?';

        // This seems to be a bad idea, but presumably that the test environment does only have one record
        // per test suite, then it is okay to do so. Just pass IGNORE_MISSING here, so that it can return false
        // when record is not found.
        $record = $DB->get_record_sql($sql, [$details], IGNORE_MISSING);
        if (!$record) {
            throw new coding_exception("event '{$details}' does not exist");
        }
        return (int)$record->id;
    }

    /**
     * For start/finish time, we use the format that php is supporting. Therefore, please provided it if
     * @see https://www.php.net/manual/en/datetime.formats.relative.php
     * @param array $record
     * @return int
     */
    public static function create_sessiondates_for_behat(array $record): int {
        global $DB;

        $keys = ['eventdetails', 'start', 'finish'];
        foreach ($keys as $key) {
            if (!array_key_exists($key, $record)) {
                throw new coding_exception("The property '{$key}' is missing");
            }
        }

        // This seems to be a bad idea, but presumably that the test environment does only have one record
        // per test suite, then it is okay to do so. Just pass MUST_EXIST here, so that it can fail the test straight
        // away when record is not found
        $eventid = static::get_event_id_from_detail($record['eventdetails']);

        $times = [
            'start' => 0,
            'finish' => 0
        ];

        foreach ($times as $key => $time) {
            $times[$key] = self::compute_timestamp($record, $key);
        }

        $rc = new stdClass();
        $rc->sessionid = $eventid;
        $rc->timestart = $times['start'];
        $rc->timefinish = $times['finish'];
        $rc->sessiontimezone = isset($record['sessiontimezone']) ? $record['sessiontimezone'] : 99;
        $rc->id = $DB->insert_record('facetoface_sessions_dates', $rc);

        if (isset($record['room']) && !isset($record['rooms'])) {
            if ((defined('PHPUNIT_TEST') && PHPUNIT_TEST) || (defined('BEHAT_UTIL') || defined('BEHAT_TEST') || defined('BEHAT_SITE_RUNNING'))) {
                throw new coding_exception('The room field is no longer supported. Please replace it with rooms.');
            } else {
                debugging('The room field is no longer supported. Please replace it with rooms.', DEBUG_DEVELOPER);
                $record['rooms'] = $record['room'];
                unset($record['room']);
            }
        }

        if (isset($record['rooms'])) {
            // Start processing on rooms if there are any provided.
            $rooms = array_filter(explode(",", $record['rooms']));
            foreach ($rooms as $room) {
                $room = trim($room);
                $frd = new \stdClass();
                $frd->sessionsdateid = $rc->id;
                // Expecting room to be existing in the storage, with the given name from the step.
                if (!($frd->roomid = $DB->get_field('facetoface_room', 'id', ['name' => $room]))) {
                    throw new coding_exception("room '{$room}' does not exist");
                }
                $DB->insert_record('facetoface_room_dates', $frd);
            }
        }

        if (isset($record['facilitators'])) {
            // Start processing on facilitators if there are any provided.
            $facilitators = array_filter(explode(",", $record['facilitators']));
            foreach ($facilitators as $facilitator) {
                $facilitator = trim($facilitator);
                $o = new \stdClass();
                $o->sessionsdateid = $rc->id;

                // Expecting facilitator to be existing in the storage, with the given name from the step.
                if (!($o->facilitatorid = $DB->get_field('facetoface_facilitator', 'id', ['name' => $facilitator]))) {
                    throw new coding_exception("facilitator '{$facilitator}' does not exist");
                }
                $DB->insert_record('facetoface_facilitator_dates', $o);
            }
        }

        if (isset($record['assets'])) {
            // Start processing on assets if there are any provided.
            $assets = array_filter(explode(",", $record['assets']));
            foreach ($assets as $asset) {
                $asset = trim($asset);
                $o = new \stdClass();
                $o->sessionsdateid = $rc->id;
                // Expecting asset to be existing in the storage, with the given name from the step.
                if (!($o->assetid = $DB->get_field('facetoface_asset', 'id', ['name' => $asset]))) {
                    throw new coding_exception("asset '{$asset}' does not exist");
                }
                $DB->insert_record('facetoface_asset_dates', $o);
            }
        }

        // Make calendar entries.
        $seminar_event = new seminar_event($rc->sessionid);
        calendar::update_entries($seminar_event);

        return $rc->id;
    }

    /**
     * Just returning an id of seminar event to the usage. Creating seminar event for an instance.
     *
     * @param array $record
     * @return int
     */
    public static function create_session_for_behat(array $record): int {
        global $DB;

        if (!isset($record['facetoface'])) {
            throw new coding_exception("No property 'facetoface' defined in \$record");
        }

        $f2f = $record['facetoface'];
        $seminarevent = new seminar_event();
        $seminarevent->set_details($record['details']);

        if (!is_numeric($f2f)) {
            // Must be idnumber or name, if it is not an id/integer.
            $f2fid = $DB->get_field('facetoface', 'id', ['name' => $f2f]);
            if (!$f2fid) {
                // If facetoface id is not being found via name, then we should try the idnumber instead, allow the
                // developer to have more than one way to create the seminar within generator.
                $f2fid = $DB->get_field('course_modules', 'instance', ['idnumber' => $f2f]);
                if (!$f2fid) {
                    throw new coding_exception(
                        "The property 'facetoface' must be an idnumber that is associated with seminar"
                    );
                }

            }

            $seminarevent->set_facetoface((int) $f2fid);
        } else {
            $seminarevent->set_facetoface((int) $record['facetoface']);
        }

        if (isset($record['capacity'])) {
            $seminarevent->set_capacity((int) $record['capacity']);
        }

        if (isset($record['allowoverbook'])) {
            $seminarevent->set_allowoverbook((int) $record['allowoverbook']);
        }

        if (isset($record['waitlistedeveryone'])) {
            $seminarevent->set_waitlisteveryone((int) $record['waitlistedeveryone']);
        }

        if (isset($record['normalcost'])) {
            $seminarevent->set_normalcost($record['normalcost']);
        }

        if (isset($record['discountcost'])) {
            $seminarevent->set_discountcost($record['discountcost']);
        }

        if (isset($record['allowcancellations'])) {
            $seminarevent->set_allowcancellations((int) $record['allowcancellations']);
        }

        if (isset($record['cancellationcutoff'])) {
            $seminarevent->set_cancellationcutoff($record['cancellationcutoff']);
        }

        if (isset($record['cutoff'])) {
            $seminarevent->set_cutoff($record['cutoff']);
        }

        if (isset($record['approval'])) {
            if (seminar::APPROVAL_SELF == $record['approval']) {
                $seminarevent->set_selfapproval(1);
            }
        }

        if (isset($record['mincapacity'])) {
            $seminarevent->set_mincapacity($record['mincapacity']);
        }

        if (isset($record['sendcapacityemail'])) {
            $seminarevent->set_sendcapacityemail((int) $record['sendcapacityemail']);
        }


        $items = ['registrationtimestart', 'registrationtimefinish'];
        $registrationtime = [];
        foreach ($items as $item) {
            if (isset($record[$item])) {
                $registrationtime[$item] = self::compute_timestamp($record, $item);
            }
        }

        if (isset($registrationtime['registrationtimestart'])) {
            $seminarevent->set_registrationtimestart($registrationtime['registrationtimestart']);
        }

        if (isset($registrationtime['registrationtimefinish'])) {
            $seminarevent->set_registrationtimefinish($registrationtime['registrationtimefinish']);
        }

        if (isset($record['cancelledstatus'])) {
            $seminarevent->set_cancelledstatus((int) $record['cancelledstatus']);
        }

        $seminarevent->save();
        return $seminarevent->get_id();
    }

    /**
     * Translate the date/time field with optionally taking timezone into account.
     * If $record[$field] is a number, then it is treated as Unix timestamp in UTC.
     * If it is a string, then it is passed to the DateTime constructor with $record[$field.'timezone'] as timezone
     *
     * @param array $record
     * @param string $field
     * @return integer
     */
    private static function compute_timestamp(array $record, string $field): int {
        $original = $record[$field];
        if (!empty($original) && !is_numeric($original)) {
            if (isset($record[$field.'timezone']) && (string)$record[$field.'timezone'] !== '') {
                $timezone = \core_date::get_user_timezone_object($record[$field.'timezone']);
            } else {
                $timezone = null;
            }
            // This could mean that it is a format instead of normal returned value from `time()`.
            // Must be some kind of time format string that the PHP is able to understand.
            // Otherwise, we should throw exception here to let the test just fail.
            try {
                // Using DateTime object, because it can throw exception, and so we could catch and fail the
                // behat step pretty much.
                $datetimeobject = new \DateTime($original, $timezone);
                $original = $datetimeobject->getTimestamp();
            } catch (\Throwable $e) {
                throw new coding_exception("An exception occurred: {$e->getMessage()}'");
            }
        }
        return (int)$original;
    }

    /**
     * Create signup for an event. The property 'user' of $record must be a username of the specific user that
     * we are trying to create a signup for.
     *
     * NOTE: When you are creating a signup within an event, make sure the user had been already enrolled to the
     * course first.
     *
     * @param array $record
     * @return int
     */
    public static function create_signups_for_behat(array $record): int {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/facetoface/lib.php'); // for MDL_F2F_BOTH
        require_once($CFG->libdir.'/gradelib.php'); // for grade_floatval

        $keys = array('user', 'eventdetails');
        foreach ($keys as $key) {
            if (!array_key_exists($key, $record)) {
                throw new coding_exception("The property '{$key}' does not exist in \$record");
            }
        }

        $signup = new signup();
        $signup->set_userid($DB->get_field('user', 'id', ['username' => $record['user']], MUST_EXIST));
        $signup->set_sessionid(static::get_event_id_from_detail($record['eventdetails']));

        if (isset($record['discountcode'])) {
            $signup->set_discountcode($record['discountcode']);
        }

        if (isset($record['notificationtype'])) {
            $signup->set_notificationtype((int) $record['notificationtype']);
        } else {
            $signup->set_notificationtype(MDL_F2F_BOTH);
        }

        if (isset($record['bookedby'])) {
            $signup->set_bookedby((int) $record['bookedby']);
        }

        if (isset($record['jobassignmentid'])) {
            $signup->set_jobassignmentid((int) $record['jobassignmentid']);
        }

        if (isset($record['managerid'])) {
            $signup->set_managerid((int) $record['managerid']);
        };

        $signup->save();

        $desiredclass = '';
        if (isset($record['status'])) {
            $status = $record['status'];
            if (isset(self::$map[$status])) {
                $desiredclass = self::$map[$status];
            } else {
                throw new coding_exception("The status '{$status}' is unknown.");
            }
        } else {
            // Status is not provided, try to switch to many different type of state here.
            $states = state::get_all_states();
            foreach ($states as $state) {
                if ($state == not_set::class) {
                    // Skip not set.
                    continue;
                }

                if ($signup->can_switch($state)) {
                    $desiredclass = $state;
                    break;
                }
            }
        }
        if ($desiredclass) {
            $grade = null;
            if (isset($record['grade'])) {
                $grade = grade_floatval($record['grade']);
            }
            signup_status::create($signup, new $desiredclass($signup), 0, $grade)->save();
        }

        return $signup->get_id();
    }

}
