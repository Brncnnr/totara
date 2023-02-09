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
 * @author  Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface;

use \stdClass;
use \context_module;
use core\orm\query\builder;
use mod_facetoface\signup_status;
use mod_facetoface\exception\signup_exception;
use mod_facetoface\signup\state\{
    state,
    not_set,
    booked,
    requested,
    requestedrole,
    requestedadmin,
    waitlisted,
    user_cancelled,
    attendance_state
};
use totara_job\job_assignment;

defined('MOODLE_INTERNAL') || die();

/**
 * Manage signups
 * Create new signup with all required parameters
 */
final class signup_helper {
    /**
     * Attempt to perform signup process.
     * Check that user can sign up must be done separately.
     * @param signup $signup
     * @return signup
     */
    public static function signup(signup $signup) : signup {
        global $DB;

        // User cannot signup - no effect.
        if (!self::can_signup($signup)) {
            throw new signup_exception("Cannot signup.");
        }

        $trans = $DB->start_delegated_transaction();
        $signup->save();

        $signup->switch_state(booked::class, waitlisted::class, requested::class, requestedrole::class);

        static::trigger_event($signup);
        static::set_default_job_assignment($signup);
        static::withdraw_interest($signup);

        $trans->allow_commit();

        return $signup;
    }

    /**
     * Check if user can signup.
     *
     * User can signup if it is their initial signup and they match all requirements
     * or if it is subsequential signup and state is cancelled and they match all requirements.
     *
     * @param signup $signup
     * @return bool
     */
    public static function can_signup(signup $signup) : bool {
        // Cannot sign up when already signed up.
        if ($signup->get_state() instanceof booked
            || $signup->get_state() instanceof waitlisted) {
            return false;
        }
        return $signup->can_switch(booked::class, waitlisted::class, requested::class, requestedrole::class);
    }

    /**
     * Get expected state upon signup
     *
     * @param signup $signup
     * @return state
     */
    public static function expected_signup_state(signup $signup) : state {
        $oldstate = $signup->get_state();
        if ($oldstate->can_switch(booked::class, waitlisted::class, requested::class, requestedrole::class)) {
            return $oldstate->switch_to(booked::class, waitlisted::class, requested::class, requestedrole::class);
        }
        return $oldstate;
    }
    /**
     * Get the reasons a signup is failing
     * @param signup $signup
     * @return array
     */
    public static function get_failures(signup $signup) : array {
        // Cannot sign up when already signed up.
        if ($signup->get_state() instanceof booked
            || $signup->get_state() instanceof waitlisted) {
            return ['addalreadysignedupattendee' => get_string('error:addalreadysignedupattendee', 'mod_facetoface')];
        }
        return $signup->get_failures(booked::class, waitlisted::class, requested::class, requestedrole::class);
    }

    /**
     * Cancel a user signup to a seminar event.
     *
     * @param signup $signup
     * @param string $cancellationreason
     * @return signup
     */
    public static function user_cancel(signup $signup, string $cancellationreason = '') : signup {
        global $DB;

        // User cannot cancel their own signup - no effect.
        if (!self::can_user_cancel($signup)) {
            throw new signup_exception("Cannot cancel signup.");
        }

        $seminarevent = $signup->get_seminar_event();
        $trans = $DB->start_delegated_transaction();

        $signup->switch_state(user_cancelled::class);

        // Write or update the cancellation field when necessary/possible.
        if (!empty($cancellationreason)) {
            $params = array('shortname' => 'cancellationnote', 'datatype' => 'text');
            if ($cancelfieldid = $DB->get_field('facetoface_cancellation_info_field', 'id', $params)) {
                $canceldataparams = array('fieldid' => $cancelfieldid, 'facetofacecancellationid' => $signup->get_id());
                if ($DB->record_exists('facetoface_cancellation_info_data', $canceldataparams)) {
                    $DB->set_field('facetoface_cancellation_info_data', 'data', $cancellationreason, $canceldataparams);
                } else {
                    $todb = new stdClass();
                    $todb->data = $cancellationreason;
                    $todb->fieldid = $cancelfieldid;
                    $todb->facetofacecancellationid = $signup->get_id();
                    $DB->insert_record('facetoface_cancellation_info_data', $todb);
                }
            }
        }

        // Open the spot up for anyone on the waitlist.
        self::update_attendees($seminarevent);

        $trans->allow_commit();

        return $signup;
    }

    /**
     * A simple function to check whether a user has cancelled their signup or not.
     * @param signup $signup
     * @return bool
     */
    public static function is_cancelled(signup $signup) : bool {
        $state = $signup->get_state();
        return $state instanceof \mod_facetoface\signup\state\user_cancelled;
    }

    /**
     * Check if the user can cancel their signup or not.
     * @param signup $signup
     * @return bool
     */
    public static function can_user_cancel(signup $signup) : bool {
        return $signup->can_switch(user_cancelled::class);
    }

    /**
     * Process the attendance records for a seminar event. This is for event taking attendance.
     *
     * Here is the internal workflow just for illustration purposes.
     * Please do not rely on this as it is not contractual.
     *
     * + signup_helper::process_attendance():
     *   + signup_helper::switch_state_and_grade():
     *     + signup::switch_state_with_grade():
     *       + signup::update_status():                     - work out state transition
     *       | + signup_status::save()                      - supersede signup_status
     *       | + signup_status_updated::trigger():          - trigger event
     *       |   + event_handler::signup_status_updated():  - event observer
     *       |     + grade_helper::grade_signup():
     *       |       + facetoface_update_grades():          - module callback function to update grade
     *       |       | + facetoface_grade_item_update()
     *       |       |   + grade_update()                   - update grade
     *       |       + seminar::set_completion():
     *       |         + completion_info::update_state()    - update activity completion status
     *       + interface_event::trigger()
     *
     * @param seminar_event $seminarevent
     * @param array         $attendance     an array containing [ signup::id => state::get_code() ]
     * @param array         $grades         an array containing [ signup::id => grade or null ]
     *                                      only valid for seminars with manual event grading
     * @return bool
     * @throws coding_exception             thrown if $grades has any keys that do not exist in $attendance
     */
    public static function process_attendance(seminar_event $seminarevent, array $attendance, array $grades = null) : bool {
        if ($grades === null) {
            $grades = [];
        }
        $eventgradingmanual = (bool)$seminarevent->get_seminar()->get_eventgradingmanual();

        // Validation: The $attendance array must contain all the keys of the $grades array.
        $attendance_keys = array_keys($attendance);
        $grades_keys = array_keys($grades);
        $diff_keys = array_diff($grades_keys, $attendance_keys);
        if (count($diff_keys) > 0) {
            sort($diff_keys);
            throw new \coding_exception('Strayed signups found in $grades: ' . implode(', ', $diff_keys));
        }

        // Validation: All the sessionid of the signup of $attendance must be $seminarevent->id
        foreach ($attendance as $signupid => $statuscode) {
            if ((new signup($signupid))->get_sessionid() != $seminarevent->get_id()) {
                return false;
            }
        }

        foreach ($attendance as $signupid => $statuscode) {
            $grade = $grades[$signupid] ?? null;
            $signup = (new signup($signupid))->set_attendance_processed(true);
            $desiredstate = state::from_code($statuscode);

            if (!self::switch_state_and_grade($signup, $desiredstate, $eventgradingmanual, $grade)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Switch state and grade.
     *
     * @param signup        $signup
     * @param string        $desiredstate       the fully-qualified class name of a new state
     * @param bool          $eventgradingmanual true to use $grade value, false to use the default grade of $desiredstate
     * @param float|null    $grade
     * @return bool
     */
    private static function switch_state_and_grade(signup $signup, string $desiredstate, bool $eventgradingmanual, float $grade = null) : bool {
        $currentstate = null; // Delay-load signup_status.

        if ($desiredstate == not_set::class) {
            $currentstate = $currentstate ?? $signup->get_state();
            // If current state is attendance, try fallback to booked, otherwise leave it as is
            if ($currentstate instanceof attendance_state) {
                $desiredstate = booked::class;
            } else {
                $desiredstate = get_class($currentstate);
            }
        }

        if (!$eventgradingmanual) {
            $grade = $desiredstate::get_grade();
        }

        if ($signup->can_switch($desiredstate)) {
            $signup->switch_state_with_grade($grade, null, $desiredstate);
        } else {
            $currentstate = $currentstate ?? $signup->get_state();
            if (get_class($currentstate) === $desiredstate) {
                $signup->switch_state_with_grade($grade, ['gradeonly' => true], $desiredstate);
            } else {
                $error = sprintf("Seminar: could not switch signup #%d from '%s' to '%s'", $signup->get_id(), get_class($currentstate), $desiredstate);
                error_log($error);
                // Also dump the error message under PHPUnit.
                if (PHPUNIT_TEST) {
                    debugging($error);
                }
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate a user's final grade.
     *
     * @param \stdClass|seminar $facetoface
     * @param int               $userid
     * @return float|null a grade value or null if nothing applicable
     */
    public static function compute_final_grade($facetoface, int $userid) : ?float {
        debugging('The method ' . __METHOD__ . '() has been deprecated. Please use grade_helper::get_final_grades() instead.', DEBUG_DEVELOPER);

        if (empty($userid)) { // Doesn't support this!
            return null;
        }
        $grades = grade_helper::get_final_grades($facetoface, $userid, grade_helper::FORMAT_FACETOFACE);
        if (empty($grades)) {
            return null;
        }
        return $grades[$userid]->rawgrade;
    }

    /**
     * Calculate a user's final grade.
     *
     * @param \stdClass|seminar $facetoface
     * @param int               $userid
     * @return \stdClass|null an object containing [ grade, timefinish ], or null if nothing applicable
     */
    public static function compute_final_grade_with_time($facetoface, int $userid) : ?\stdClass {
        debugging('The method ' . __METHOD__ . '() has been deprecated. Please use grade_helper::get_final_grades() instead.', DEBUG_DEVELOPER);

        if (empty($userid)) { // Doesn't support this!
            return null;
        }
        $grades = grade_helper::get_final_grades($facetoface, $userid, grade_helper::FORMAT_FACETOFACE);
        if (empty($grades)) {
            return null;
        }
        $object = new \stdClass();
        $object->grade = $grades[$userid]->rawgrade;
        $object->timefinish = $grades[$userid]->timecompleted;
        return $object;
    }

    /**
     * Update the activty completion of a seminar.
     *
     * @param integer $signupid
     * @return void
     */
    protected static function update_activity_completion(int $signupid): void {
        debugging('The method ' . __METHOD__ . '() has been deprecated. Please use seminar::set_completion() instead.', DEBUG_DEVELOPER);

        global $DB;
        /** @var \moodle_database $DB */

        // Update activity completion.
        $rec = $DB->get_record_sql(
            'SELECT c.*, f.id AS facetofaceid, fsu.userid AS userid
              FROM {course} c
              JOIN {facetoface} f ON f.course = c.id
              JOIN {facetoface_sessions} fs ON fs.facetoface = f.id
              JOIN {facetoface_signups} fsu ON fsu.sessionid = fs.id
             WHERE fsu.id = ?', [ $signupid ], MUST_EXIST);

        $completion = new \completion_info($rec);
        if ($completion->is_enabled()) {
            $course_module = get_coursemodule_from_instance('facetoface', $rec->facetofaceid, $rec->id);
            // The aggregation of activty completion state is not necessary here.
            // \completion_indo::update_state() calls internal_get_state() that calls facetoface_get_completion_state(),
            // in which other criteria take account of activity completion.
            $completion->update_state($course_module, COMPLETION_UNKNOWN, $rec->userid);
        }
    }

    /**
     * Update attendees status regarding new event settingss
     * @param seminar_event $seminarevent
     */
    public static function update_attendees(seminar_event $seminarevent): void {
        if ($seminarevent->is_first_started()) {
            return;
        }

        $helper = new attendees_helper($seminarevent);

        // Just need the list of attendees without associated user id at array key.
        $users = array_values($helper->get_attendees_with_codes([booked::get_code(), waitlisted::get_code()]));
        $reservedusers = $helper->get_reservations();

        // We need to add reservation into this list too, because the seminar event is being udpated, and reservation
        // without the attendee to fill up the space need to be udpated as well.
        foreach ($reservedusers as $reserveduser) {
            if ($reserveduser->has_bookedby() && !$reserveduser->is_valid()) {
                // We only want the free space reservation, those reservation that had filled up would probably
                // already included in the list of attendees_with_codes.
                $users[] = $reserveduser;
                continue;
            }
        }

        \core_collator::asort_objects_by_property($users, 'timesignedup', \core_collator::SORT_NUMERIC);

        $newstate = false;
        if ($users) {
            // We want to book users from waitlist, unless waitlist everyone is enabled.
            if (empty($seminarevent->get_waitlisteveryone())) {
                $oldstate = waitlisted::class;
                $newstate = booked::class;
            }
            // Unless there no sessions, in which case we want to waitlist booked users.
            if (!$seminarevent->is_sessions()) {
                $oldstate = booked::class;
                $newstate = waitlisted::class;
            }

            if ($newstate) {
                foreach ($users as $user) {
                    $signup = new \mod_facetoface\signup((int) $user->submissionid);
                    $signup->set_actorid($signup->get_userid());
                    $state = $signup->get_state();
                    if ($state instanceof $oldstate) {
                        if ($state->can_switch($newstate)) {
                            $signup->switch_state($newstate);
                        }
                    }
                }
            }
        }
    }

    /**
     * Add default job assignment if required
     * @param signup $signup
     */
    protected static function set_default_job_assignment(signup $signup): void {
        $seminar = $signup->get_seminar_event()->get_seminar();
        $selectjobassignmentonsignupglobal = get_config(null, 'facetoface_selectjobassignmentonsignupglobal');
        $jobassignmentrequired = !empty($selectjobassignmentonsignupglobal) && !empty($seminar->get_selectjobassignmentonsignup());

        if ($jobassignmentrequired) {
            $jobassignment = \totara_job\job_assignment::get_first($signup->get_userid(), false);

            if (!empty($jobassignment)) {
                $signup->set_jobassignmentid((int)$jobassignment->id);
            }
        }
    }

    /**
     * Trigger signup event
     * @param signup $signup
     */
    protected static function trigger_event(signup $signup): void {
        $cm = $signup->get_seminar_event()->get_seminar()->get_coursemodule();
        $context = \context_module::instance($cm->id);
        \mod_facetoface\event\session_signup::create_from_signup($signup, $context)->trigger();
    }

    /**
     * Remove user expression of interest since they are already signed up
     *
     * @param signup $signup
     */
    protected static function withdraw_interest(signup $signup): void {
        $interest = interest::from_seminar($signup->get_seminar_event()->get_seminar(), $signup->get_userid());
        $interest->withdraw();
    }

    /**
     * Confirms waitlisted users from an array as booked on a session.
     *
     * @param seminar_event $seminarevent
     * @param array  $userids    Array of user ids to confirm
     * @return array $result success|failures
     */
    public static function confirm_waitlist(\mod_facetoface\seminar_event $seminarevent, array $userids): array {
        global $DB;

        $errors = [];
        foreach ($userids as $userid) {
            $signup = signup::create($userid, $seminarevent);
            if ($signup->get_state() instanceof \mod_facetoface\signup\state\not_set) {
                continue;
            }

            if ($signup->can_switch(\mod_facetoface\signup\state\booked::class)) {
                $signup->switch_state(\mod_facetoface\signup\state\booked::class);
            } else {
                $failures = $signup->get_failures(\mod_facetoface\signup\state\booked::class);
                if (!empty($failures)) {
                    $errors[$signup->get_userid()] = current($failures);
                }
            }
        }
        $result = [];
        if (empty($errors)) {
            $result['result'] = 'success';
        } else {
            $result['result'] = 'failure';
            $errormsgs = [];
            list($sqlin, $inparams) = $DB->get_in_or_equal(array_keys($errors));
            $users = $DB->get_records_sql('SELECT * FROM {user} WHERE id '.$sqlin, $inparams);
            foreach ($users as $user) {
                $errormsgs[] = get_string(
                    'error:cannotchangestateuser',
                    'mod_facetoface',
                    (object)['user' => fullname($user), 'error' => $errors[$user->id]]
                );
            }
            $result['content'] = \html_writer::alist($errormsgs);
        }
        return $result;
    }

    /**
     * Randomly books waitlisted users on to a session.
     *
     * @param seminar_event $seminarevent
     * @param array $userids a list of user ids
     * @return array $result success|failure
     */
    public static function confirm_waitlist_randomly(\mod_facetoface\seminar_event $seminarevent, array $userids): array {
        $helper = new attendees_helper($seminarevent);
        $signupcount = $helper->count_attendees();
        $numtoconfirm = $seminarevent->get_capacity() - $signupcount;

        if ($numtoconfirm < 1) {
            $errormsgs = [get_string('error:lotterynocapacity', 'mod_facetoface')];
            return [
                'result' => 'failure',
                'content' => \html_writer::alist($errormsgs),
            ];
        }

        if (count($userids) <= $numtoconfirm) {
            $winners = $userids;
        } else {
            $winners = array_rand(array_flip($userids), $numtoconfirm);
            if ($numtoconfirm == 1) {
                $winners = array($winners);
            }
        }
        return self::confirm_waitlist($seminarevent, $winners);
    }

    /**
     * Cancels waitlisted users from an array on a session
     * @param seminar_event $seminarevent
     * @param array  $userids    Array of user ids to cancel
     */
    public static function cancel_waitlist(\mod_facetoface\seminar_event $seminarevent, array $userids): void {

        foreach ($userids as $userid) {
            $signup = signup::create($userid, $seminarevent);
            if ($signup->get_state() instanceof \mod_facetoface\signup\state\not_set) {
                continue;
            }
            if ($signup->can_switch(\mod_facetoface\signup\state\user_cancelled::class)) {
                $signup->switch_state(\mod_facetoface\signup\state\user_cancelled::class);
            }
        }
    }

    /**
     * @param signup $signup
     * @return stdClass[]
     */
    public static function find_managers_from_signup(signup $signup): array {
        global $DB;

        if (!$signup->exists()) {
            // Prevent the path that throws exception with empty signup id. Just don't bother to find the managers of
            // a user within signup, if the signup does not exists in the system though.
            return [];
        }

        $managerselect = get_config(null, 'facetoface_managerselect');
        $selectjobassignmentonsignupglobal = get_config(null, 'facetoface_selectjobassignmentonsignupglobal');

        $managers = [];

        if ($managerselect && $signup->has_manager()) {
            // Check if they selected a manager for their signup.
            $managerid = $signup->get_managerid();
            $managers[] = \core_user::get_user($managerid);
        } else if ($selectjobassignmentonsignupglobal && $signup->has_jobassignment()) {
            // The job assignment could not be found here, because the system admin might had deleted
            // the job assignment record, but did not update the seminar signup record here.

            // This could mean that, seminar system is not able to notify this user's manager here.
            // However, when deleting the job assignment of a user, this could indicate that this
            // user is no longer being managed by the same manager anymore. Unless, deleting job
            $jobasssignmentid = $signup->get_jobassignmentid();
            $ja = job_assignment::get_with_id($jobasssignmentid, false);

            if (null !== $ja) {
                if ($ja->managerid) {
                    // Add the permanent manager.
                    $managers[] = \core_user::get_user($ja->managerid);
                }
                if ($ja->tempmanagerid) {
                    // Add the temporary manager. This will make that there are two managers
                    // for sending notification to.
                    $managers[] = \core_user::get_user($ja->tempmanagerid);
                }
            }
        } else {
            $userid = $signup->get_userid();
            $managerids = job_assignment::get_all_manager_userids($userid);

            if (!empty($managerids)) {
                [$psql, $params] = $DB->get_in_or_equal($managerids, SQL_PARAMS_NAMED);
                $managers = $DB->get_records_select('user', "id {$psql}", $params);
            }
        }

        array_walk($managers, function (stdClass &$manager) {
            $manager->fullname = fullname($manager);
        });

        return $managers;
    }

    /**
     * Get user details.
     * @param int $userid
     * @return stdClass
     */
    public static function get_user_details(int $userid): \stdClass {
        return \core_user::get_user($userid, '*', MUST_EXIST);
    }

    /**
     * A simple function to check whether the signup state is booked or one of graded states.
     *
     * @param signup $signup
     * @param bool $includerequested Set true to include requested and waitlisted status (default)
     * @return boolean
     */
    public static function is_booked(signup $signup, bool $includerequested = true): bool {
        if (!$signup->exists()) {
            return false;
        }
        $state = $signup->get_state();
        if ($includerequested) {
            $statuscodes = attendance_state::get_all_attendance_code_with([
                requested::class,
                requestedrole::class,
                requestedadmin::class,
                waitlisted::class,
                booked::class,
            ]);
        } else {
            $statuscodes = attendance_state::get_all_attendance_code_with([
                booked::class
            ]);
        }
        return in_array($state::get_code(), $statuscodes);
    }

    /**
     * Get the user's booking status as a human-readable string.
     *
     * @param int|string|state $state The user's booking status as status code, a state class string or a state class instance
     * @param boolean $attendancestatus Set false to hide attendance status from a user
     * @return string
     */
    public static function get_user_booking_status($state, bool $attendancestatus = true): string {
        if ($state instanceof state) {
            $state = $state->get_code();
        } else if (is_number($state)) {
            $state = (int)$state;
        } else {
            if (!is_string($state) || strpos($state, 'mod_facetoface\\signup\\state\\') !== 0 || !class_exists($state)) {
                throw new \coding_exception('$state must be a state class string, a state class instance or status code');
            }
            $state = $state::get_code();
        }

        // Optimise for most frequent scenarios.
        if ($state == not_set::get_code()) {
            return not_set::get_string();
        } else if ($state == booked::get_code()) {
            return booked::get_string();
        } else if ($state == waitlisted::get_code()) {
            return waitlisted::get_string();
        }

        if ($attendancestatus) {
            $stateclass = state::from_code($state);
            return $stateclass::get_string();
        } else {
            $stateclass = state::from_code($state);
            $attendancestates = attendance_state::get_all_attendance_states();
            if (in_array($stateclass, $attendancestates)) {
                // Display "Booked" instead of attendance states such as "Fully attended"
                return booked::get_string();
            } else {
                return $stateclass::get_string();
            }
        }
    }

    /**
     * A simple function to check whether the sign-up is open for the user or not.
     * Use signup_helper::can_signup() to check whether the user is able to sign up or not.
     *
     * @param seminar_event $seminarevent
     * @param integer $signupcount by attendees_helper::count_signups()
     * @param integer $userid
     * @param integer $timenow
     * @return boolean
     */
    public static function is_signup_open(seminar_event $seminarevent, int $signupcount, int $userid = 0, int $timenow = 0): bool {
        if ($timenow <= 0) {
            $timenow = time();
        }
        if ($signupcount >= $seminarevent->get_capacity()) {
            return false;
        }
        if (!empty($seminarevent->get_registrationtimestart()) && $seminarevent->get_registrationtimestart() > $timenow) {
            return false;
        }
        if (!empty($seminarevent->get_registrationtimefinish()) && $timenow > $seminarevent->get_registrationtimefinish()) {
            return false;
        }
        if ($seminarevent->is_first_started($timenow)) {
            return false;
        }
        if (!seminar_event_helper::is_available($seminarevent, $userid)) {
            return false;
        }
        return true;
    }

    /**
     * Check if a user is able to sign up, cancel or see the event.
     *
     * @todo this function needs test coverage (phpunit or behat or both)
     *
     * @param seminar_event $seminarevent
     * @param integer $userid
     * @param integer $timenow
     * @return boolean
     */
    public static function is_user_actionable(seminar_event $seminarevent, int $userid = 0, int $timenow = 0): bool {
        global $USER;

        if ($userid == 0) {
            $userid = $USER->id;
        }
        if ($timenow <= 0) {
            $timenow = time();
        }

        // Hide button from cancelled events.
        if ($seminarevent->get_cancelledstatus()) {
            return false;
        }

        // Hide button from past events.
        if ($seminarevent->is_over($timenow)) {
            return false;
        }

        // Hide button if the seminar activity is unavailable to the user.
        if (!seminar_event_helper::is_available($seminarevent, $userid)) {
            return false;
        }

        return true;
    }

    /**
     * Get the array of archived sign-up records for the seminar event.
     *
     * @param integer $event_id seminar_event.id
     * @return array of [signupid => [signup.id, signup.userid, signup_status.statuscode, signup_status.timecreated]]
     */
    public static function get_archived_signups(int $event_id): array {
        $records = builder::table('facetoface_signups', 'su')
            ->join(['user', 'u'], 'userid', 'id')
            ->left_join(['facetoface_signups_status', 'sus'], 'id', 'signupid')
            ->where(function (builder $mediator) {
                return $mediator->where_null('sus.superceded')->or_where('sus.superceded', 0);
            })
            ->where('su.archived', '!=', 0)
            ->where('su.sessionid', $event_id)
            ->where('u.deleted', 0)
            ->select(['su.id', 'su.userid', 'sus.timecreated'])
            ->add_select_raw('COALESCE(sus.statuscode,:css) as statuscode', ['css' => booked::get_code()])
            ->order_by('u.username')
            ->get()
            ->all(true);
        return $records;
    }

    /**
     * Unset the archived flag of the specific sign-up records.
     *
     * @param integer $event_id seminar_event.id
     * @param array $signup_ids array of signup.id
     * @return integer the number of sign-ups that have been un-archived
     */
    public static function unarchive_signups(int $event_id, array $signup_ids): int {
        return builder::get_db()->transaction(function() use ($event_id, $signup_ids) {
            $signup_ids_archived = array_keys(self::get_archived_signups($event_id));
            $signup_ids_of_interest = array_intersect($signup_ids_archived, $signup_ids);
            $time = time();
            builder::table('facetoface_signups')
                ->where_in('id', $signup_ids_of_interest)
                ->update(['archived' => 0]);
            // Reset attendance status to prevent the next cron run from completing the seminar activity of the sign-ups.
            builder::table('facetoface_signups_status')
                ->where_in('signupid', $signup_ids_of_interest)
                ->update(['superceded' => 1]);
            foreach ($signup_ids_of_interest as $signupid) {
                builder::table('facetoface_signups_status')
                    ->insert([
                        'signupid' => $signupid,
                        'statuscode' => booked::get_code(),
                        'superceded' => 0,
                        'grade' => null,
                        'createdby' => 0,
                        'timecreated' => $time
                    ]);
            }
            return count($signup_ids_of_interest);
        });
    }
}
