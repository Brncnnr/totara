<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * facetoface module PHPUnit archive test class
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit mod_facetoface_notifications_testcase mod/facetoface/tests/notifications_test.php
 *
 * @package    mod_facetoface
 * @subpackage phpunit
 * @author     Oleg Demeshev <oleg.demeshev@totaralms.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/facetoface/lib.php');
require_once($CFG->dirroot . '/mod/facetoface/tests/facetoface_testcase.php');
require_once($CFG->dirroot . '/totara/hierarchy/prefix/position/lib.php');
require_once($CFG->dirroot . '/totara/customfield/field/datetime/define.class.php');
require_once($CFG->dirroot . '/totara/customfield/field/datetime/field.class.php');

use mod_facetoface\{facilitator_helper, room_helper, signup_helper, seminar_event, trainer_helper};

class mod_facetoface_notifications_testcase extends mod_facetoface_facetoface_testcase {

    /**
     * PhpUnit fixture method that runs before the test method executes.
     */
    public function setUp(): void {
        parent::setUp();
        set_config('facetoface_allow_legacy_notifications', 1);
    }

    /**
     * Create user
     *
     * @param null|array|\stdClass $record
     * @param null|array|\stdClass $options
     * @return stdClass
     */
    private function createUser($record = null, $options = null) {
        return $this->getDataGenerator()->create_user($record, $options);
    }

    /**
     * Create new course
     *
     * @param null|array|\stdClass $record
     * @param null|array|\stdClass $options
     * @return \stdClass
     */
    private function createCourse($record = null, $options = null) {
        return $this->getDataGenerator()->create_course($record, $options);
    }

    /**
     * New seminar date object
     *
     * @param null|int $start Timestamp start
     * @param null $finish Timestamp finish
     * @param int $room Int seminar room id
     * @param string $timezone timezone
     * @return \stdClass
     */
    private function createSeminarDate($start = null, $finish = null, $room = 0, $timezone = 'Pacific/Auckland') {
        $start = $start ?: time();
        $finish = $finish ?: $start + 3600;

        return (object)[
            'sessiontimezone' => $timezone,
            'timestart' => $start,
            'timefinish' => $finish,
            'roomids' => [$room],
        ];
    }

    /**
     * Enrol user to a course
     *
     * @param int|\stdClass $user User to enrol
     * @param int|\stdClass $course Course to enrol
     * @param string $role Role to enrol
     * @param null|boolean $success The success of the operation
     * @return $this
     */
    private function enrolUser($user, $course, $role = 'student', &$success = null) {
        $generator = $this->getDataGenerator();

        if (is_object($user)) {
            $user = $user->id;
        }

        if (is_object($course)) {
            $course = $course->id;
        }

        $success = $generator->enrol_user($user, $course, $role);

        return $this;
    }

    /**
     * Returns facetoface plugin generator.
     *
     * @return \mod_facetoface\testing\generator
     * @throws coding_exception
     */
    private function getSeminarGenerator() {
        return $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
    }

    /**
     * Create a new seminar
     *
     * @param null|\stdClass|int $course Course object or id (null to create a new course
     * @param string|array $record Record array or seminar name
     * @param null|array $options Options
     * @return \stdClass Seminar object
     * @throws coding_exception
     */
    private function createSeminar($course = null, $record = 'facetoface', $options = null) {
        if (is_null($course)) {
            $course = $this->createCourse();
        }

        if (is_object($course)) {
            $course = $course->id;
        }

        if (is_string($record)) {
            $record = [
                'name' => $record
            ];
        }

        $record = array_merge([
            'course' => $course,
        ], $record);

        return $this->getSeminarGenerator()->create_instance($record, $options);
    }

    /**
     * Add a new seminar room
     *
     * @param \stdClass|int $seminar Seminar object or id
     * @param \stdClass|null $dates Seminar dates
     * @param array $params Parameters ($record) for the created seminar, doesn't require default values
     * @param null|array $options
     * @return mixed
     * @throws coding_exception
     */
    private function addSeminarSession($seminar, $dates = null, array $params = [], $options = null) {
        if (is_object($seminar)) {
            $seminar = $seminar->id;
        }

        $params = array_merge([
            'facetoface' => $seminar,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => $dates ?: [$this->createSeminarDate()],
            'mincapacity' => '1',
            'cutoff' => DAYSECS - 60
        ], $params);

        $sessionid = $this->getSeminarGenerator()->add_session($params, $options);
        $seminarevent = new seminar_event($sessionid);
        $session = $seminarevent->to_record();
        $session->sessiondates = $seminarevent->get_sessions()->sort('timestart')->to_records(false);
        return $session;
    }

    /**
     * Create site wide seminar room
     *
     * @param string|array $record Record array or a string name
     * @param array $customfields Customfields key value pair array, w\o customfield_ prefix
     * @return stdClass Seminar room
     * @throws coding_exception
     */
    private function createSeminarRoom($record = null, $customfields = []) {
        if (is_null($record)) {
            $record = 'New room ' . rand(1,100000);
        }

        if (is_string($record)) {
            $record = [
                'name' => $record,
            ];
        }

        $room = $this->getSeminarGenerator()
            ->add_site_wide_room($record);

        if (!empty($customfields)) {
            foreach ($customfields as $key => $value) {
                $name = "customfield_$key";
                $room->$name = $value;
            }
        }

        customfield_save_data($room, 'facetofaceroom', 'facetoface_room');
        return $room;
    }

    public function test_cancellation_send_delete_session() {

        $session = $this->generate_data();

        // Call facetoface_delete_session function for session1.
        $emailsink = $this->redirectMessages();
        $e = new seminar_event($session->id);
        $e->delete();
        $this->executeAdhocTasks();
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertCount(2, $emails, 'Wrong no of cancellation notifications sent out.');
    }

    public function test_cancellation_nonesend_delete_session() {

        $session = $this->generate_data(false);

        // Call facetoface_delete_session function for session1.
        $emailsink = $this->redirectMessages();
        $e = new seminar_event($session->id);
        $e->delete();
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertCount(0, $emails, 'Error: cancellation notifications should not be sent out.');
    }

    /**
     * Create course, users, face-to-face, session
     *
     * @param bool $future, time status: future or past, to test cancellation notifications
     * @return \stdClass $session
     */
    private function generate_data($future = true) {
        global $DB;

        $this->setAdminUser();

        $teacher1 = $this->getDataGenerator()->create_user();
        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();

        $managerja = \totara_job\job_assignment::create_default($manager->id);
        \totara_job\job_assignment::create_default($student1->id, array('managerjaid' => $managerja->id));
        \totara_job\job_assignment::create_default($student2->id, array('managerjaid' => $managerja->id));

        $course = $this->getDataGenerator()->create_course();

        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        $this->getDataGenerator()->enrol_user($teacher1->id, $course->id, $teacherrole->id);
        $this->getDataGenerator()->enrol_user($student1->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course->id, $studentrole->id);

        /** @var \mod_facetoface\testing\generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $facetofacedata = array(
            'name' => 'facetoface',
            'course' => $course->id
        );
        $facetoface = $facetofacegenerator->create_instance($facetofacedata);

        $sessiondate = new stdClass();
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $sessiondate->timestart = time() + WEEKSECS;
        $sessiondate->timefinish = time() + WEEKSECS + 60;
        $sessiondate->assetids = array();

        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate),
            'mincapacity' => '1',
            'cutoff' => DAYSECS - 60
        );

        $sessionid = $facetofacegenerator->add_session($sessiondata);
        $seminarevent = new seminar_event($sessionid);
        $session = $seminarevent->to_record();
        $session->mintimestart = $seminarevent->get_mintimestart();
        $session->sessiondates = $seminarevent->get_sessions()->sort('timestart')->to_records(false);

        // Signup user1.
        $this->setUser($student1);
        signup_helper::signup(\mod_facetoface\signup::create($student1->id, new \mod_facetoface\seminar_event($sessionid)));

        // Signup user2.
        $this->setUser($student2);
        signup_helper::signup(\mod_facetoface\signup::create($student2->id, new \mod_facetoface\seminar_event($sessionid)));

        $emailsink = $this->redirectMessages();
        $this->executeAdhocTasks();
        $emailsink->close();

        if (!$future) {
            $sessiondate->timestart = time() - WEEKSECS;
            $sessiondate->timefinish = time() - WEEKSECS + 60;
            $seminarevent = new seminar_event($sessionid);
            \mod_facetoface\seminar_event_helper::merge_sessions($seminarevent, [$sessiondate]);
        }

        return $session;
    }

    /**
     * @return array of timestamps for use in testing.
     */
    private function create_array_of_times() {
        $times = array(
            'start1' => time() + 1 * DAYSECS,
            'end1' => time() + 1 * DAYSECS + 2 * HOURSECS,
            'other1' => time() + 5 * DAYSECS,
            'start2' => time() + 3 * DAYSECS + 30 * MINSECS,
            'end2' => time() + 4 * DAYSECS + 6 * HOURSECS,
            'other2' => time() - 4 * DAYSECS
        );
        if (date('G', $times['other1']) == 0) {
            $times['other1'] += 1; // Otherwise a different display format will be used for customfield_datetime.
        }
        if (date('G', $times['other2']) == 0) {
            $times['other2'] += 1; // Otherwise a different display format will be used for customfield_datetime.
        }

        return $times;
    }

    /**
     * Test iCal generation.
     */
    public function test_ical_generation() {

        // Reusable human error messages.
        $errors = [
            'dates_dont_match' => 'Session dates don\'t match to the iCal generated dates.',
            'uids_dont_match' => 'iCal UID doesn\'t match to an earlier generated iCal for this date',
            'uids_match' => 'Two different dates have matching UIDs',
            'location_doesnt_match' => 'iCal location doesn\'t match predefined seminar room location',
            'cancelled_count_dont_match' => 'The number of cancelled dates doesn\'t match',
            'description_not_found' => 'iCal description does not contain expected string'
        ];

        $icals = [];

        $students = [
            $this->createUser(),
            $this->createUser(),
        ];

        $course = $this->createCourse();

        foreach ($students as $student) {
            $this->enrolUser($student, $course);
        }

        $seminar = $this->createSeminar($course, 'f2f');

        $room = $this->createSeminarRoom(
            [
                'name' => 'Site x 1',
                'url' => 'https://example.com/channel/id/12345',
            ],
            [
                'locationaddress' => "Address\nTest\nTest2",
            ]
        );

        $dates = [
            $this->createSeminarDate(WEEKSECS, null, $room->id),
            $this->createSeminarDate(WEEKSECS + DAYSECS * 2),
            $this->createSeminarDate(WEEKSECS + DAYSECS * 5),
        ];

        $session = $this->addSeminarSession($seminar, $dates);

        $icals['original'] = [
            $this->dissect_ical(\mod_facetoface\messaging::generate_ical(
                $seminar,
                $session,
                MDL_F2F_INVITE,
                $students[0],
                null,
                [],
                'iCal description must have this text')->content,
                ['location', 'uid', 'sequence', 'dtstart', 'dtend', 'description']
            ),
            $this->dissect_ical(\mod_facetoface\messaging::generate_ical(
                $seminar,
                $session,
                MDL_F2F_INVITE,
                $students[1])->content,
                ['location', 'uid', 'sequence', 'dtstart', 'dtend']
            ),
        ];

        // Checking that iCal contains custom description.
        $this->assertMatchesRegularExpression('/.*iCal description must have this text.*/',
            implode('', $icals['original'][0]->description), $errors['description_not_found']);

        // Checking that dates match for both events.
        $this->assertTrue($this->ical_date_match($icals['original'][0], $session->sessiondates),
            $errors['dates_dont_match']);

        $this->assertTrue($this->ical_date_match($icals['original'][1], $session->sessiondates),
            $errors['dates_dont_match']);

        // UIDs are different.
        $this->assertNotEquals($icals['original'][0]->uid[0], $icals['original'][0]->uid[1],
            $errors['uids_match']);

        $this->assertNotEquals($icals['original'][0]->uid[ 1], $icals['original'][0]->uid[2],
        $errors['uids_match']);

        $this->assertNotEquals($icals['original'][0]->uid[0], $icals['original'][0]->uid[2],
            $errors['uids_match']);

        // Location matches the generated room location.
        $this->assertEquals('Site x 1\, https://example.com/channel/id/12345\, Address,Test,Test2', $icals['original'][0]->location[0],
            $errors['location_doesnt_match']);

        // Need to cancel seminar date, in the middle!
        \mod_facetoface\seminar_event_helper::merge_sessions(
            new seminar_event($session->id),
            [$session->sessiondates[0], $session->sessiondates[2]]
        );
        $old = $session->sessiondates;
        $seminarevent = new seminar_event($session->id);
        $session->sessiondates = $seminarevent->get_sessions()->sort('timestart')->to_records(false);

        $icals['session_date_removed'] = [
            $this->dissect_ical(\mod_facetoface\messaging::generate_ical($seminar,
                $session,
                MDL_F2F_INVITE,
                $students[0],
                null,
                $old)->content, ['location', 'uid', 'sequence', 'dtstart', 'dtend', 'status']),
            $this->dissect_ical(\mod_facetoface\messaging::generate_ical($seminar,
                $session,
                MDL_F2F_INVITE,
                $students[1],
                null,
                $old)->content, ['location', 'uid', 'sequence', 'dtstart', 'dtend', 'status']),
        ];

        // Will match old dates as it will include notification for a cancelled date.
        $this->assertTrue($this->ical_date_match($icals['session_date_removed'][0], $old),
            $errors['dates_dont_match']);

        $this->assertTrue($this->ical_date_match($icals['session_date_removed'][1], $old),
            $errors['dates_dont_match']);

        // Must include ONE cancelled date.
        $this->assertCount(1, $icals['session_date_removed'][0]->status,
            $errors['cancelled_count_dont_match']);

        // Match that uids are the same, however order is different as it first includes dates to create or
        // update and then cancelled dates.
        $this->assertEquals($icals['session_date_removed'][0]->uid[0], $icals['original'][0]->uid[0],
            $errors['uids_dont_match']);
        $this->assertEquals($icals['session_date_removed'][0]->uid[1], $icals['original'][0]->uid[2],
            $errors['uids_dont_match']);
        $this->assertEquals($icals['session_date_removed'][0]->uid[2], $icals['original'][0]->uid[1],
            $errors['uids_dont_match']);

        // Adding a date and removing a date and modifying a date.
        $old = $session->sessiondates;
        array_shift($session->sessiondates);
        \mod_facetoface\seminar_event_helper::merge_sessions(
            new seminar_event($session->id),
            array_merge($session->sessiondates, [
                $added = $this->createSeminarDate(time() + YEARSECS, null, $room->id)
            ])
        );
        $seminarevent = new seminar_event($session->id);
        $session->sessiondates = $seminarevent->get_sessions()->sort('timestart')->to_records(false);

        $icals['session_date_removed_and_added'] = [
            $this->dissect_ical(\mod_facetoface\messaging::generate_ical($seminar,
                $session,
                MDL_F2F_INVITE,
                $students[0],
                null,
                $old)->content, ['location', 'uid', 'sequence', 'dtstart', 'dtend', 'status']),
            $this->dissect_ical(\mod_facetoface\messaging::generate_ical($seminar,
                $session,
                MDL_F2F_INVITE,
                $students[1],
                null,
                $old)->content, ['location', 'uid', 'sequence', 'dtstart', 'dtend', 'status']),
        ];

        // Will match old dates and a new date as it will include notification for a cancelled and added dates.
        $this->assertTrue($this->ical_date_match($icals['session_date_removed_and_added'][0],
            array_merge($old, [$added])), $errors['dates_dont_match']);

        $this->assertTrue($this->ical_date_match($icals['session_date_removed_and_added'][1],
            array_merge($old, [$added])), $errors['dates_dont_match']);

        // Must include ONE cancelled date.
        $this->assertCount(1, $icals['session_date_removed_and_added'][0]->status,
            $errors['cancelled_count_dont_match']);

        // Match that uids are the same, however order is different as it first includes dates to create or
        // update and then cancelled dates. UID[1] should be unique and not match anything before.
        $this->assertEquals($icals['session_date_removed_and_added'][0]->uid[0],
            $icals['session_date_removed'][0]->uid[1], $errors['uids_dont_match']);

        $this->assertEquals($icals['session_date_removed_and_added'][0]->uid[2],
            $icals['session_date_removed'][0]->uid[0], $errors['uids_dont_match']);

        // Location matches the generated room location.
        $this->assertEquals('Site x 1\, https://example.com/channel/id/12345\, Address,Test,Test2',
            $icals['session_date_removed_and_added'][0]->location[2], $errors['location_doesnt_match']);

        // User 1 cancelled.
        $this->mock_status_change($students[0]->id, $session->id);

        $icals['first_user_status_changed'] = [
            $this->dissect_ical(\mod_facetoface\messaging::generate_ical($seminar,
                $session,
                MDL_F2F_CANCEL,
                $students[0])->content, ['location', 'uid', 'sequence', 'dtstart', 'dtend', 'status']),
            $this->dissect_ical(\mod_facetoface\messaging::generate_ical($seminar,
                $session,
                MDL_F2F_INVITE,
                $students[1])->content, ['location', 'uid', 'sequence', 'dtstart', 'dtend']),
        ];

        // Will match session dates as the dates shouldn't have changed.
        $this->assertTrue($this->ical_date_match($icals['first_user_status_changed'][0],
            $session->sessiondates), $errors['dates_dont_match']);

        $this->assertTrue($this->ical_date_match($icals['first_user_status_changed'][1],
            $session->sessiondates), $errors['dates_dont_match']);

        // Uids shoud stay the same.
        $this->assertEquals($icals['first_user_status_changed'][0]->uid[0],
            $icals['session_date_removed_and_added'][0]->uid[0], $errors['uids_dont_match']);

        $this->assertEquals($icals['first_user_status_changed'][0]->uid[1],
            $icals['session_date_removed_and_added'][0]->uid[1], $errors['uids_dont_match']);

        // Both dates must be cancelled.
        $this->assertCount(2, $icals['first_user_status_changed'][0]->status,
            $errors['cancelled_count_dont_match']);
    }

    /**
     * Test sending notifications when "facetoface_oneemailperday" is enabled
     */
    public function test_oneperday_ical_generation() {
        // Reusable human error messages.
        $errors = [
            'dates_dont_match' => 'Session dates don\'t match to the iCal generated dates.',
            'uids_dont_match' => 'iCal UID doesn\'t match to an earlier generated iCal for this date',
            'uids_match' => 'Two different dates have matching UIDs',
            'cancelled_count_dont_match' => 'The number of cancelled dates doesn\'t match',
        ];

        $this->setAdminUser();
        set_config('facetoface_oneemailperday', true);

        $this->enrolUser($student = $this->createUser(),
                         $course = $this->createCourse());

        $seminar = $this->createSeminar($course);

        $session = $this->addSeminarSession($seminar, $dates = [
            $this->createSeminarDate(time() + WEEKSECS),
            $this->createSeminarDate(time() + WEEKSECS + DAYSECS),
        ]);

        $emailsink = $this->redirectMessages();
        signup_helper::signup(\mod_facetoface\signup::create($student->id, new \mod_facetoface\seminar_event($session->id)));
        $this->executeAdhocTasks();
        $preemails = $emailsink->get_messages();
        $emailsink->clear();
        foreach($preemails as $preemail) {
            $this->assertStringContainsString("This is to confirm that you are now booked", $preemail->fullmessagehtml);
        }

        $icals = [
            'original' => [
                $this->dissect_ical(\mod_facetoface\messaging::generate_ical($seminar, $session, MDL_F2F_INVITE,
                    $student, $session->sessiondates[0])->content,
                    ['location', 'uid', 'sequence', 'dtstart', 'dtend']),
                $this->dissect_ical(\mod_facetoface\messaging::generate_ical($seminar, $session, MDL_F2F_INVITE,
                    $student, $session->sessiondates[1])->content,
                    ['location', 'uid', 'sequence', 'dtstart', 'dtend']),
            ]
        ];

        // Dates match to seminar dates.
        $this->assertTrue($this->ical_date_match($icals['original'][0], $session->sessiondates[0]),
            $errors['dates_dont_match']);
        $this->assertTrue($this->ical_date_match($icals['original'][1], $session->sessiondates[1]),
            $errors['dates_dont_match']);

        // Uids do not match.
        $this->assertNotEquals($icals['original'][0]->uid[0], $icals['original'][1]->uid[0], $errors['uids_match']);

        // Editing one date and cancelling the second one.
        $dates = $session->sessiondates;
        $new = [$this->createSeminarDate(time() + 2 * WEEKSECS)];

        // Preserving the id of the edited date, otherwise it will be treated as a new date.
        $new[0]->id = $dates[0]->id;

        $emailsink = $this->redirectMessages();
        unset($session->notifyuser, $session->trainers, $session->trainerroles, $session->sessiondates,
            $session->maxtimefinish, $session->mintimestart, $session->cntdates);
        $seminarevent = new seminar_event();
        $seminarevent->from_record($session);
        $seminarevent->save();
        \mod_facetoface\seminar_event_helper::merge_sessions($seminarevent, $new);

        $session = $seminarevent->to_record();

        // Send message.
        \mod_facetoface\notice_sender::signup_datetime_changed(
            \mod_facetoface\signup::create($student->id, new \mod_facetoface\seminar_event($session->id)),
            $dates
        );

        $icals['date_edited_and_cancelled'] = [
            $this->dissect_ical(\mod_facetoface\messaging::generate_ical($seminar, $session, MDL_F2F_INVITE,
                $student, $new[0])->content,
                ['location', 'uid', 'sequence', 'dtstart', 'dtend']),
            $this->dissect_ical(\mod_facetoface\messaging::generate_ical($seminar, $session, MDL_F2F_CANCEL,
                $student, $dates[1])->content,
                ['location', 'uid', 'sequence', 'dtstart', 'dtend', 'status']),
        ];

        // Dates match to seminar dates.
        $this->assertTrue($this->ical_date_match($icals['date_edited_and_cancelled'][0], $new[0]),
            $errors['dates_dont_match']);
        $this->assertTrue($this->ical_date_match($icals['date_edited_and_cancelled'][1], $dates[1]),
            $errors['dates_dont_match']);

        // Checking that UIDs haven't changed.
        $this->assertEquals($icals['original'][0]->uid[0],$icals['date_edited_and_cancelled'][0]->uid[0],
            $errors['uids_dont_match']);
        $this->assertEquals($icals['original'][1]->uid[0],$icals['date_edited_and_cancelled'][1]->uid[0],
            $errors['uids_dont_match']);

        // Second date actually has been cancelled.
        $this->assertCount(1, $icals['date_edited_and_cancelled'][1]->status,
            $errors['cancelled_count_dont_match']);

        $this->executeAdhocTasks();
        $emails = $emailsink->get_messages();
        $emailsink->clear();

        usort($emails, function($email1, $email2) {
           return strcmp($email1->subject, $email2->subject);
        });
        $this->assertStringContainsString("BOOKING CANCELLED", $emails[0]->fullmessagehtml);
        $this->assertStringContainsString("The session you are booked on (or on the waitlist) has changed:", $emails[1]->fullmessagehtml);

        // Now test cancelling the session.
        $result = $seminarevent->cancel();
        $this->assertTrue($result);

        // One email has been sent and it contains all the required data.
        $this->executeAdhocTasks();
        $this->assertCount(1, $messages = $emailsink->get_messages());
        $message = $messages[0];
        $emailsink->close();

        $this->assertStringContainsString('Seminar event cancellation', $message->subject);
        $this->assertStringContainsString('This is to advise that the following session has been cancelled',
            $message->fullmessagehtml);
        $this->assertStringContainsString('Course:   Test course 1', $message->fullmessagehtml);
        $this->assertStringContainsString('Seminar:   facetoface', $message->fullmessagehtml);
        $this->assertStringContainsString('Details:', $message->fullmessagehtml);

        // seminar event needs to be reloaded with info from DB
        $seminarevent = new seminar_event($seminarevent->get_id());
        $this->assertEquals(1, $seminarevent->get_cancelledstatus());
    }

    /**
     * Simplified parse $ical content and return values of requested property
     * @param string $content
     * @param string $name
     * @return array of values
     */
    private function get_ical_values($content, $name) {
        $strings = explode("\n", $content);
        $result = array();
        $isdecription = false;
        foreach($strings as $string) {
            // Multi-line description workaround.
            if ($isdecription) {
                if (strpos($string, 'SUMMARY:') !== 0) {
                    $result[] = trim($string);
                    continue;
                }
                $isdecription = false;
            }

            if (strpos($string, $name.':') === 0) {
                $result[] = trim(substr($string, strlen($name)+1));
                // Multi-line description workaround.
                if ($name == 'DESCRIPTION') {
                    $isdecription = true;
                }
            }
        }
        return $result;
    }

    /**
     * Search for a matching date from an ical file in the array of seminar event dates.
     *
     * @param \stdClass $needle dissected ical \stdClass
     * @param array|\stdClass $haystack seminar event date(s)
     * @return bool
     */
    private function ical_date_match($needle, $haystack) {

        // Normalizing needle(s).
        if (!isset($needle->dtstart) || !isset($needle->dtend)
            || count($needle->dtstart) != count($needle->dtend)) {
            return false;
        }

        $dates = [];

        for ($i = 0; $i < count($needle->dtstart); $i++) {
            $dates[] = (object) [
                'dtstart' => $needle->dtstart[$i],
                'dtend' => $needle->dtend[$i],
            ];
        }

        // Normalizing haystack.
        $haystack = array_map(function($item) {
            // We are expecting a seminar date to be passed here, so keys will be different.
            return (object) [
                'dtstart' => \mod_facetoface\messaging::ical_generate_timestamp($item->timestart),
                'dtend' => \mod_facetoface\messaging::ical_generate_timestamp($item->timefinish),
            ];
        }, !is_array($haystack) ? [$haystack] : $haystack);

        // Looking that all dates present in the haystack.
        $dates = array_filter($dates, function ($date) use (&$haystack) {
            foreach ($haystack as $key => $piece) {
                if ($date->dtstart == $piece->dtstart &&
                    $date->dtend == $piece->dtend) {
                    unset($haystack[$key]);
                    return false;
                }
            }

            return true;
        });

        // Return true only if we matched all needles to the haystack and there is no more needles (dates) left there.
        return !!(empty($dates) & empty($haystack));
    }

    /**
     * Convert iCal file to a nice readable object of arrays.
     *
     * @param string $ical iCal file content
     * @param array $filter filter returned iCal items
     * @param bool $asobj return as object with lower-cased properties or as array of arrays
     * @return array|\stdClass
     */
    private function dissect_ical($ical, $filter = [], $asobj = true) {
        $keys = [
            'BEGIN',
            'METHOD',
            'PRODID',
            'VERSION',
            'UID',
            'SEQUENCE',
            'LOCATION',
            'STATUS',
            'SUMMARY',
            'DESCRIPTION',
            'CLASS',
            'LAST-MODIFIED',
            'DTSTAMP',
            'DTSTART',
            'DTEND',
            'CATEGORIES',
            'END',
        ];

        if (!empty($filter)) {
            $filter = array_map('strtoupper', $filter);
            $keys = array_filter($keys, function($item) use ($filter) {
                return in_array($item, $filter);
            });
        }

        // Converting the keys array to the format [$key[0]=>$key[0], ...]
        $keys = array_combine($asobj ? array_map('strtolower', $keys) : $keys, $keys);

        $keys = array_map(function($item) use ($ical) {
            return $this->get_ical_values($ical, $item);
        }, $keys);

        return $asobj ? (object) $keys : $keys;
    }

    /**
     * Add superceeded record to signup status to mock user status change
     * @param int $userid
     * @param int $sessionid
     */
    private function mock_status_change($userid, $sessionid) {
        global $DB;

        $signupid = $DB->get_field('facetoface_signups', 'id', array('userid' => $userid, 'sessionid' => $sessionid));
        if (!$signupid) {
            $signupmock = new stdClass();
            $signupmock->userid = $userid;
            $signupmock->sessionid = $sessionid;
            $signupmock->notificationtype = 3;
            $signupmock->bookedby = 2;
            $signupid = $DB->insert_record('facetoface_signups', $signupmock);
        }

        $mock = new stdClass();
        $mock->superceded = 1;
        $mock->statuscode = 0;
        $mock->signupid = $signupid;
        $mock->createdby = 2;
        $mock->timecreated = time();
        $DB->insert_record('facetoface_signups_status', $mock);
    }

    /**
     * Tests the facetoface_notification_loop_session_placeholders function alone, without relying on proper working
     * of functions for saving to and retrieving from database.
     */
    public function test_facetoface_notification_loop_session_placeholders() {

        [$session, $roomlist, $roomcf, $times, $timezone] = $this->set_data_for_session_placeholders(false);

        $msg = "Testing with non-saved session.[#sessions] Start time is [session:starttime]. Finish time is [session:finishtime].[/sessions] That is all.";

        $replacedmsg = facetoface_notification_loop_session_placeholders($msg, $session);

        $expectedstart = userdate($times['start1'], get_string('strftimetime', 'langconfig'), $timezone);
        $expectedend = userdate($times['end1'], get_string('strftimetime', 'langconfig'), $timezone);

        $this->assertEquals("Testing with non-saved session. Start time is ".$expectedstart.". Finish time is ".$expectedend.". That is all.", $replacedmsg);
    }

    /**
     * Tests the facetoface_notification_loop_session_placeholders function alone, without relying on proper working
     * of functions for saving to and retrieving from database. In this case, there are two lots of tags.
     */
    public function test_facetoface_notification_loop_session_placeholders_double() {

        [$session, $roomlist, $roomcf, $times, $timezone] = $this->set_data_for_session_placeholders();

        $msg = "Testing with non-saved session.[#sessions]Start time is [session:starttime]. Finish time is [session:finishtime].\n[/sessions]";
        $msg .= "[#sessions]Start date is [session:startdate]. Finish date is [session:finishdate].\n[/sessions]";
        $msg .= "That is all.";

        $replacedmsg = facetoface_notification_loop_session_placeholders($msg, $session);

        // Get strings for display of dates and times in email.
        $startdate1 = userdate($times['start1'], get_string('strftimedate', 'langconfig'), $timezone);
        $starttime1 = userdate($times['start1'], get_string('strftimetime', 'langconfig'), $timezone);
        $enddate1 = userdate($times['end1'], get_string('strftimedate', 'langconfig'), $timezone);
        $endtime1 = userdate($times['end1'], get_string('strftimetime', 'langconfig'), $timezone);
        $startdate2 = userdate($times['start2'], get_string('strftimedate', 'langconfig'), $timezone);
        $starttime2 = userdate($times['start2'], get_string('strftimetime', 'langconfig'), $timezone);
        $enddate2 = userdate($times['end2'], get_string('strftimedate', 'langconfig'), $timezone);
        $endtime2 = userdate($times['end2'], get_string('strftimetime', 'langconfig'), $timezone);

        $expectedmsg = "Testing with non-saved session.";
        $expectedmsg .= "Start time is ".$starttime1.". Finish time is ".$endtime1.".\n";
        $expectedmsg .= "Start time is ".$starttime2.". Finish time is ".$endtime2.".\n";
        $expectedmsg .= "Start date is ".$startdate1.". Finish date is ".$enddate1.".\n";
        $expectedmsg .= "Start date is ".$startdate2.". Finish date is ".$enddate2.".\n";
        $expectedmsg .= "That is all.";
        $this->assertEquals($expectedmsg, $replacedmsg);
    }

    public function test_facetoface_notification_loop_session_placeholders_no_session() {
        $msg = "Testing with non-saved session. A[#sessions]Start time is [session:starttime]. Finish time is [session:finishtime].\n[/sessions]A";
        $msg .= " I repeat: [#sessions]Start date is [session:startdate]. Finish date is [session:finishdate].\n[/sessions]";
        $msg .= " That is all.";

        $session = new stdClass();
        $session->sessiondates = array();
        $replacedmsg = facetoface_notification_loop_session_placeholders($msg, $session);
        $expectedmsg = "Testing with non-saved session. ALocation and time to be announced later.A I repeat: Location and time to be announced later. That is all.";
        $this->assertEquals($expectedmsg, $replacedmsg);
    }

    /**
     * Tests facetoface_notification_loop_session_placeholders function with data returned by functions generally used
     * to retrieve facetoface session data.
     */
    public function test_facetoface_notification_loop_session_placeholders_with_session() {

        [$session, $roomlist, $roomcf, $times, $timezone] = $this->set_data_for_session_placeholders();

        $msg = "The details for each session:\n";
        $msg .= "[#sessions]";
        $msg .= "* Start time of [session:startdate] at [session:starttime] and end time of [session:finishdate] at [session:finishtime] ([session:timezone]).\n";
        $msg .= "  Location is [session:room:name].\n";
        $msg .= "[/sessions]";
        $msg .= "Those are all the details.";

        $replacedmsg = facetoface_notification_loop_session_placeholders($msg, $session, $roomlist, $roomcf);

        // Get strings for display of dates and times in email.
        $startdate1 = userdate($times['start1'], get_string('strftimedate', 'langconfig'), $timezone);
        $starttime1 = userdate($times['start1'], get_string('strftimetime', 'langconfig'), $timezone);
        $enddate1 = userdate($times['end1'], get_string('strftimedate', 'langconfig'), $timezone);
        $endtime1 = userdate($times['end1'], get_string('strftimetime', 'langconfig'), $timezone);
        $startdate2 = userdate($times['start2'], get_string('strftimedate', 'langconfig'), $timezone);
        $starttime2 = userdate($times['start2'], get_string('strftimetime', 'langconfig'), $timezone);
        $enddate2 = userdate($times['end2'], get_string('strftimedate', 'langconfig'), $timezone);
        $endtime2 = userdate($times['end2'], get_string('strftimetime', 'langconfig'), $timezone);

        $expectedmsg = "The details for each session:\n";
        $expectedmsg .= "* Start time of ".$startdate1." at ".$starttime1." and end time of ".$enddate1." at ".$endtime1." (".$timezone.").\n";
        $expectedmsg .= "  Location is Room One.\n";
        $expectedmsg .= "* Start time of ".$startdate2." at ".$starttime2." and end time of ".$enddate2." at ".$endtime2." (".$timezone.").\n";
        $expectedmsg .= "  Location is .\n";
        $expectedmsg .= "Those are all the details.";

        $this->assertEquals($expectedmsg, $replacedmsg);
    }

    private function set_data_for_session_placeholders(bool $multisessiondates = true) {
        // We'll use the server timezone otherwise this test will fail in some parts of the world and not others.
        $timezone = core_date::get_server_timezone();

        $times = $this->create_array_of_times();

        $course = $this->getDataGenerator()->create_course();

        /** @var \mod_facetoface\testing\generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $facetofacedata = array(
            'name' => 'facetoface',
            'course' => $course->id
        );
        $facetoface = $facetofacegenerator->create_instance($facetofacedata);

        // Create a room to add to a session date.
        $room = $this->createSeminarRoom(['name' => 'Room One', 'capacity' => 20]);

        $dates = [
            $this->createSeminarDate($times['start1'], $times['end1'], $room->id, $timezone),
        ];

        if ($multisessiondates) {
            $dates[] =
                $this->createSeminarDate($times['start2'], $times['end2'], 0, $timezone);
        }

        $session = $this->addSeminarSession($facetoface, $dates);

        $roomcf = [];
        $roomlist = \mod_facetoface\room_list::get_event_rooms($session->id);
        foreach ($roomlist as $room) {
            $roomcf[$room->get_id()] = customfield_get_data($room->to_record(), "facetoface_room", "facetofaceroom");
        }

        return [$session, $roomlist, $roomcf, $times, $timezone];
    }

    public function test_facetoface_notification_loop_session_placeholders_room_customfields() {
        global $DB;

        // We'll use the server timezone otherwise this test will fail in some parts of the world and not others.
        $timezone = core_date::get_server_timezone();

        $times = $this->create_array_of_times();

        $course = $this->getDataGenerator()->create_course();

        /** @var \mod_facetoface\testing\generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $facetofacedata = array(
            'name' => 'facetoface',
            'course' => $course->id
        );
        $facetoface = $facetofacegenerator->create_instance($facetofacedata);

        /** @var \totara_customfield\testing\generator $customfieldgenerator */
        $customfieldgenerator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');

        $customfields = array();

        // Create a datetime customfield.
        $cfsettings = array('Room Date' => array('shortname' => 'roomdate', 'startyear' => 2015, 'endyear' => 2030));
        $customfields += $customfieldgenerator->create_datetime('facetoface_room', $cfsettings);

        // Create a text customfield.
        $cfsettings = array('Room Text'); // Will have the shortname of RoomText
        $customfields += $customfieldgenerator->create_text('facetoface_room', $cfsettings);

        // Create a location customfield.
        $cfsettings = array('Room Location' => array('shortname' => 'roomlocation')); // Will have the shortname of RoomText
        $customfields += $customfieldgenerator->create_location('facetoface_room', $cfsettings);

        // Create a room to add to a session date.
        $room1 = new stdClass();
        $room1->name = 'Room One';
        $room1->capacity = 20;
        $room1->timemodified = time();
        $room1->timecreated = $room1->timemodified;
        $room1->id = $DB->insert_record('facetoface_room', $room1);

        $customfieldgenerator->set_datetime($room1, $customfields['Room Date'], $times['other1'], 'facetofaceroom', 'facetoface_room');
        $customfieldgenerator->set_text($room1, $customfields['Room Text'], 'Details about the room', 'facetofaceroom', 'facetoface_room');
        $location1 = new stdClass();
        $customfieldgenerator->set_location_address($room1, $customfields['Room Location'], '150 Willis Street', 'facetofaceroom', 'facetoface_room');

        // Create another room to add to a session date.
        $room2 = new stdClass();
        $room2->name = 'Room Two';
        $room2->capacity = 40;
        $room2->timemodified = time();
        $room2->timecreated = $room2->timemodified;
        $room2->id = $DB->insert_record('facetoface_room', $room2);

        $customfieldgenerator->set_datetime($room2, $customfields['Room Date'], $times['other2'], 'facetofaceroom', 'facetoface_room');

        // Set up the face-to-face session.
        $sessiondate1 = new stdClass();
        $sessiondate1->sessiontimezone = $timezone;
        $sessiondate1->timestart = $times['start1'];
        $sessiondate1->timefinish = $times['end1'];
        $sessiondate1->roomids = [$room1->id];
        $sessiondate1->assetids = array();

        $sessiondate2 = new stdClass();
        $sessiondate2->sessiontimezone = $timezone;
        $sessiondate2->timestart = $times['start2'];
        $sessiondate2->timefinish = $times['end2'];
        $sessiondate2->roomids = [$room2->id];
        $sessiondate2->assetids = array();

        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate1, $sessiondate2),
            'mincapacity' => '1',
            'cutoff' => DAYSECS - 60
        );

        $sessionid = $facetofacegenerator->add_session($sessiondata);
        $seminarevent = new seminar_event($sessionid);

        // Now get all the date we've created.
        $session = $DB->get_record('facetoface_sessions', array('id' => $sessionid));
        $session->sessiondates = $seminarevent->get_sessions()->sort('timestart')->to_records(false);
        // Get data for room custom fields.
        $roomcf = [];
        $roomlist = \mod_facetoface\room_list::get_event_rooms($session->id);
        foreach ($roomlist as $room) {
            $roomcf[$room->get_id()] = customfield_get_data($room->to_record(), "facetoface_room", "facetofaceroom", false);
        }

        $msg = "The details for each session:\n";
        $msg .= "[#sessions]";
        $msg .= "[session:room:name] has custom date of [session:room:cf_roomdate].\n";
        $msg .= "[session:room:name] has custom text of [session:room:cf_RoomText].\n";
        $msg .= "[session:room:name] has a custom location of [session:room:cf_roomlocation].\n";
        $msg .= "[/sessions]";
        $msg .= "Those are all the details.";

        $replacedmsg = facetoface_notification_loop_session_placeholders($msg, $session, $roomlist, $roomcf);

        $expectedmsg = "The details for each session:\n";
        $expectedmsg .= "Room One has custom date of ".userdate($times['other1'], get_string('strftimedaydatetime', 'langconfig'), $timezone).".\n";
        $expectedmsg .= "Room One has custom text of Details about the room.\n";
        $expectedmsg .= "Room One has a custom location of 150 Willis Street.\n";
        $expectedmsg .= "Room Two has custom date of ".userdate($times['other2'], get_string('strftimedaydatetime', 'langconfig'), $timezone).".\n";
        $expectedmsg .= "Room Two has custom text of .\n";
        $expectedmsg .= "Room Two has a custom location of .\n";
        $expectedmsg .= "Those are all the details.";

        $this->assertEquals($expectedmsg, $replacedmsg);
    }

    /*
     * Creates a seminar event with two sessions.
     * One has a single room, asset, and facilitator; the other has two of each.
     * @return array [$session, $roomlist, $roomcf, $attachmenturls] for use with following session loop tests
     */
    private function setup_for_multiple_attachment_session_placeholders() {
        global $DB;

        // Store and return attachment urls.
        $attachmenturls = ['room', 'asset', 'facilitator'];

        // We'll use the server timezone otherwise this test will fail in some parts of the world and not others.
        $timezone = core_date::get_server_timezone();

        $times = $this->create_array_of_times();

        $course = $this->getDataGenerator()->create_course();

        /** @var \mod_facetoface\testing\generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $facetofacedata = array(
            'name' => 'facetoface',
            'course' => $course->id
        );
        $facetoface = $facetofacegenerator->create_instance($facetofacedata);

        /** @var \totara_customfield\testing\generator $customfieldgenerator */
        $customfieldgenerator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');

        // Use existing room custom fields.
        $roomcustomfields = customfield_get_fields_definition('facetoface_room');
        foreach ($roomcustomfields as $cfdef) {
            $roomcustomfields[$cfdef->shortname] = $cfdef->id;
        }

        // Create asset and facilitator custom fields
        $cfsettings = array('color'); // Will have the shortname of color
        $assetcustomfields = $customfieldgenerator->create_text('facetoface_asset', $cfsettings);
        $cfsettings = array('t-shirt'); // Will have the shortname of tshirt
        $facilitatorcustomfields = $customfieldgenerator->create_text('facetoface_facilitator', $cfsettings);

        // Create a room with building and location.
        $room1 = new stdClass();
        $room1->name = 'Room One';
        $room1->url = 'https://example.com/virtual/room/1';
        $room1->capacity = 20;
        $room1->timemodified = time();
        $room1->timecreated = $room1->timemodified;
        $room1->id = $DB->insert_record('facetoface_room', $room1);
        $customfieldgenerator->set_text($room1, $roomcustomfields['building'], 'Catalyst House', 'facetofaceroom', 'facetoface_room');
        $customfieldgenerator->set_location_address($room1, $roomcustomfields['location'], '150 Willis Street', 'facetofaceroom', 'facetoface_room');

        // Create another room with building only.
        $room2 = new stdClass();
        $room2->name = 'Room Two';
        $room2->url = 'https://example.com/virtual/room/2';
        $room2->capacity = 40;
        $room2->timemodified = time();
        $room2->timecreated = $room2->timemodified;
        $room2->id = $DB->insert_record('facetoface_room', $room2);
        $customfieldgenerator->set_text($room2, $roomcustomfields['building'], 'South Campus', 'facetofaceroom', 'facetoface_room');

        // Create a third room with location only.
        $room3 = new stdClass();
        $room3->name = 'Room Three';
        $room3->url = 'https://example.com/virtual/room/3';
        $room3->capacity = 40;
        $room3->timemodified = time();
        $room3->timecreated = $room3->timemodified;
        $room3->id = $DB->insert_record('facetoface_room', $room3);
        $customfieldgenerator->set_location_address($room3, $roomcustomfields['location'], '186 Willis Street', 'facetofaceroom', 'facetoface_room');

        // Create an asset with color.
        $asset1 = new stdClass();
        $asset1->name = 'Asset One';
        $asset1->timemodified = time();
        $asset1->timecreated = $asset1->timemodified;
        $asset1->id = $DB->insert_record('facetoface_asset', $asset1);
        $customfieldgenerator->set_text($asset1, $assetcustomfields['color'], 'Gold', 'facetofaceasset', 'facetoface_asset');

        // Create another asset with color.
        $asset2 = new stdClass();
        $asset2->name = 'Asset Two';
        $asset2->timemodified = time();
        $asset2->timecreated = $asset2->timemodified;
        $asset2->id = $DB->insert_record('facetoface_asset', $asset2);
        $customfieldgenerator->set_text($asset2, $assetcustomfields['color'], 'Silver', 'facetofaceasset', 'facetoface_asset');

        // Create a third asset with no color
        $asset3 = new stdClass();
        $asset3->name = 'Asset Three';
        $asset3->timemodified = time();
        $asset3->timecreated = $asset3->timemodified;
        $asset3->id = $DB->insert_record('facetoface_asset', $asset3);

        // Create a facilitator with tshirt.
        $facilitator1 = new stdClass();
        $facilitator1->name = 'Facilitator One';
        $facilitator1->timemodified = time();
        $facilitator1->timecreated = $facilitator1->timemodified;
        $facilitator1->id = $DB->insert_record('facetoface_facilitator', $facilitator1);
        $customfieldgenerator->set_text($facilitator1, $facilitatorcustomfields['t-shirt'], 'Medium', 'facetofacefacilitator', 'facetoface_facilitator');

        // Create another facilitator with tshirt.
        $facilitator2 = new stdClass();
        $facilitator2->name = 'Facilitator Two';
        $facilitator2->timemodified = time();
        $facilitator2->timecreated = $facilitator2->timemodified;
        $facilitator2->id = $DB->insert_record('facetoface_facilitator', $facilitator2);
        $customfieldgenerator->set_text($facilitator2, $facilitatorcustomfields['t-shirt'], 'Large', 'facetofacefacilitator', 'facetoface_facilitator');

        // Create a third facilitator with no tshirt
        $facilitator3 = new stdClass();
        $facilitator3->name = 'Facilitator Three';
        $facilitator3->timemodified = time();
        $facilitator3->timecreated = $facilitator3->timemodified;
        $facilitator3->id = $DB->insert_record('facetoface_facilitator', $facilitator3);

        // Set up the face-to-face session.
        $sessiondate1 = new stdClass();
        $sessiondate1->sessiontimezone = $timezone;
        $sessiondate1->timestart = $times['start1'];
        $sessiondate1->timefinish = $times['end1'];
        $sessiondate1->roomids = [$room1->id];
        $sessiondate1->assetids = [$asset1->id];
        $sessiondate1->facilitatorids = [$facilitator1->id];

        $sessiondate2 = new stdClass();
        $sessiondate2->sessiontimezone = $timezone;
        $sessiondate2->timestart = $times['start2'];
        $sessiondate2->timefinish = $times['end2'];
        $sessiondate2->roomids = [$room2->id, $room3->id];
        $sessiondate2->assetids = [$asset2->id, $asset3->id];
        $sessiondate2->facilitatorids = [$facilitator2->id, $facilitator3->id];

        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate1, $sessiondate2),
            'mincapacity' => '1',
            'cutoff' => DAYSECS - 60
        );

        $sessionid = $facetofacegenerator->add_session($sessiondata);
        $seminarevent = new seminar_event($sessionid);

        // Attachments urls.
        $roomindex = 1;
        $assetindex = 1;
        $facilitatorindex = 1;
        foreach ($sessiondata['sessiondates'] as $sessiondate) {
            // Get session date record.
            $sd = $DB->get_record('facetoface_sessions_dates', [
                'sessionid' => $sessionid,
                'sessiontimezone' => $sessiondate->sessiontimezone,
                'timestart' => $sessiondate->timestart,
                'timefinish' => $sessiondate->timefinish
            ]);

            foreach ($sessiondate->roomids as $roomid) {
                $attachmenturls['room'][$roomindex] = new moodle_url(
                    '/mod/facetoface/reports/rooms.php',
                    [
                        'roomid' => $roomid,
                        "sdid" => $sd->id,
                        "b" => "/mod/facetoface/view.php?f=$facetoface->id"
                    ]
                );
                $roomindex++;
            }

            foreach ($sessiondate->assetids as $assetid) {
                $attachmenturls['asset'][$assetindex] = new moodle_url(
                    '/mod/facetoface/reports/assets.php',
                    [
                        'assetid' => $assetid,
                        "sdid" => $sd->id,
                        "b" => "/mod/facetoface/view.php?f=$facetoface->id"
                    ]
                );
                $assetindex++;
            }

            foreach ($sessiondate->facilitatorids as $facilitatorid) {
                $attachmenturls['facilitator'][$facilitatorindex] = new moodle_url(
                    '/mod/facetoface/reports/facilitators.php',
                    [
                        'facilitatorid' => $facilitatorid,
                        "sdid" => $sd->id,
                        "b" => "/mod/facetoface/view.php?f=$facetoface->id"
                    ]
                );
                $facilitatorindex++;
            }
        }

        // Now get all the data we've created.
        $session = $DB->get_record('facetoface_sessions', array('id' => $sessionid));
        $session->sessiondates = $seminarevent->get_sessions()->sort('timestart')->to_records(false);
        // Get data for room custom fields.
        $roomcf = [];
        $roomlist = \mod_facetoface\room_list::get_event_rooms($session->id);
        foreach ($roomlist as $room) {
            $roomcf[$room->get_id()] = customfield_get_data($room->to_record(), "facetoface_room", "facetofaceroom", false);
        }

        return [$session, $roomlist, $roomcf, $attachmenturls];
    }

    /**
     * Test [session:room:name] and friends with multiple rooms
     */
    public function test_facetoface_notification_loop_session_placeholders_multiple_rooms() {
        list($session, $roomlist, $roomcf, $attachmenturls) = $this->setup_for_multiple_attachment_session_placeholders();

        $roomurls = $attachmenturls['room'];

        $msg = "The details for each session:\n";
        $msg .= "[#sessions]\n";
        $msg .= "Room: [session:room:name]\n";
        $msg .= "Building: [session:room:cf_building]\n";
        $msg .= "Location: [session:room:cf_location]\n";
        $msg .= "[session:room:link]\n";
        $msg .= "[/sessions]\n";
        $msg .= "Those are all the details.";

        $replacedmsg = strip_tags(facetoface_notification_loop_session_placeholders($msg, $session, $roomlist, $roomcf));

        $expectedmsg = "The details for each session:\n";
        $expectedmsg .= "\nRoom: Room One\n";
        $expectedmsg .= "Building: Catalyst House\n";
        $expectedmsg .= "Location: 150 Willis Street\n";
        $expectedmsg .= "{$roomurls[1]}\n";
        $expectedmsg .= "\nRoom: \n";
        $expectedmsg .= " Room Three\n";
        $expectedmsg .= " Room Two\n";
        $expectedmsg .= "Building: \n";
        $expectedmsg .= " \n";
        $expectedmsg .= " South Campus\n";
        $expectedmsg .= "Location: \n";
        $expectedmsg .= " 186 Willis Street\n";
        $expectedmsg .= " \n";
        $expectedmsg .= "\n";
        $expectedmsg .= " {$roomurls[3]}\n";
        $expectedmsg .= " {$roomurls[2]}\n";
        $expectedmsg .= "\nThose are all the details.";

        $this->assertEquals($expectedmsg, $replacedmsg);
    }

    /**
     * Test [session:rooms] placeholder.
     */
    public function test_facetoface_notification_loop_session_roomdetails() {
        list($session, $roomlist, $roomcf, $attachmenturls) = $this->setup_for_multiple_attachment_session_placeholders();

        $roomurls = $attachmenturls['room'];

        $msg = "The details for each session:\n";
        $msg .= "[#sessions]\n";
        $msg .= "[session:rooms]\n";
        $msg .= "[/sessions]\n";
        $msg .= "Those are all the details.";

        $replacedmsg = strip_tags(facetoface_notification_loop_session_placeholders($msg, $session, $roomlist, $roomcf));

        $expectedmsg = "The details for each session:\n";
        $expectedmsg .= "\nRoom: ";
        $expectedmsg .= "Room One\n Virtual room link\n Catalyst House\n 150 Willis Street\n";
        $expectedmsg .= "\nRooms:\n";
        $expectedmsg .= "Room Three\n Virtual room link\n 186 Willis Street\n";
        $expectedmsg .= "Room Two\n Virtual room link\n South Campus\n";
        $expectedmsg .= "\nThose are all the details.";

        $this->assertEquals($expectedmsg, $replacedmsg);
    }

    /**
     * Test [session:asset:nnn] placeholders
     */
    public function test_facetoface_notification_loop_session_asset_placeholders() {
        list($session, $roomlist, $roomcf, $attachmenturls) = $this->setup_for_multiple_attachment_session_placeholders();

        $asseturls = $attachmenturls['asset'];

        $msg = "The details for each session:\n";
        $msg .= "[#sessions]\n";
        $msg .= "Asset: [session:asset:name]\n";
        $msg .= "Colour: [session:asset:cf_color]\n";
        $msg .= "[session:asset:link]\n";
        $msg .= "[/sessions]\n";
        $msg .= "Those are all the details.";

        $replacedmsg = strip_tags(facetoface_notification_loop_session_placeholders($msg, $session, $roomlist, $roomcf));

        $expectedmsg = "The details for each session:\n";
        $expectedmsg .= "\nAsset: Asset One\n";
        $expectedmsg .= "Colour: Gold\n";
        $expectedmsg .= "{$asseturls[1]}\n";
        $expectedmsg .= "\nAsset: \n";
        $expectedmsg .= " Asset Three\n";
        $expectedmsg .= " Asset Two\n";
        $expectedmsg .= "Colour: \n";
        $expectedmsg .= " \n";
        $expectedmsg .= " Silver\n";
        $expectedmsg .= "\n";
        $expectedmsg .= " {$asseturls[3]}\n";
        $expectedmsg .= " {$asseturls[2]}\n";
        $expectedmsg .= "\nThose are all the details.";

        $this->assertEquals($expectedmsg, $replacedmsg);
    }

    /**
     * Test [session:assets] placeholder.
     */
    public function test_facetoface_notification_loop_session_assetdetails() {
        list($session, $roomlist, $roomcf, $attachmenturls) = $this->setup_for_multiple_attachment_session_placeholders();

        $asseturls = $attachmenturls['asset'];

        $msg = "The details for each session:\n";
        $msg .= "[#sessions]\n";
        $msg .= "[session:assets]\n";
        $msg .= "[/sessions]\n";
        $msg .= "Those are all the details.";

        $replacedmsg = strip_tags(facetoface_notification_loop_session_placeholders($msg, $session, $roomlist, $roomcf));

        $expectedmsg = "The details for each session:\n";
        $expectedmsg .= "\nAsset: ";
        $expectedmsg .= "Asset One\n";
        $expectedmsg .= "\nAssets:\n";
        $expectedmsg .= "Asset Three\n";
        $expectedmsg .= "Asset Two\n";
        $expectedmsg .= "\nThose are all the details.";

        $this->assertEquals($expectedmsg, $replacedmsg);
    }

    /**
     * Test [session:facilitator:nnn] placeholders
     */
    public function test_facetoface_notification_loop_session_facilitator_placeholders() {
        list($session, $roomlist, $roomcf, $attachmenturls) = $this->setup_for_multiple_attachment_session_placeholders();

        $facilitatorurls = $attachmenturls['facilitator'];

        $msg = "The details for each session:\n";
        $msg .= "[#sessions]\n";
        $msg .= "Facilitator: [session:facilitator:name]\n";
        $msg .= "T-Shirt: [session:facilitator:cf_t-shirt]\n";
        $msg .= "[session:facilitator:link]\n";
        $msg .= "[/sessions]\n";
        $msg .= "Those are all the details.";

        $replacedmsg = strip_tags(facetoface_notification_loop_session_placeholders($msg, $session, $roomlist, $roomcf));

        $expectedmsg = "The details for each session:\n";
        $expectedmsg .= "\nFacilitator: Facilitator One\n";
        $expectedmsg .= "T-Shirt: Medium\n";
        $expectedmsg .= "{$facilitatorurls[1]}\n";
        $expectedmsg .= "\nFacilitator: \n";
        $expectedmsg .= " Facilitator Three\n";
        $expectedmsg .= " Facilitator Two\n";
        $expectedmsg .= "T-Shirt: \n";
        $expectedmsg .= " \n";
        $expectedmsg .= " Large\n";
        $expectedmsg .= "\n";
        $expectedmsg .= " {$facilitatorurls[3]}\n";
        $expectedmsg .= " {$facilitatorurls[2]}\n";
        $expectedmsg .= "\nThose are all the details.";

        $this->assertEquals($expectedmsg, $replacedmsg);
    }

    /**
     * Test [session:facilitators] placeholder.
     */
    public function test_facetoface_notification_loop_session_facilitatordetails() {
        list($session, $roomlist, $roomcf, $attachmenturls) = $this->setup_for_multiple_attachment_session_placeholders();

        $facilitatorurls = $attachmenturls['facilitator'];

        $msg = "The details for each session:\n";
        $msg .= "[#sessions]\n";
        $msg .= "[session:facilitators]\n";
        $msg .= "[/sessions]\n";
        $msg .= "Those are all the details.";

        $replacedmsg = strip_tags(facetoface_notification_loop_session_placeholders($msg, $session, $roomlist, $roomcf));

        $expectedmsg = "The details for each session:\n";
        $expectedmsg .= "\nFacilitator: ";
        $expectedmsg .= "Facilitator One\n";
        $expectedmsg .= "\nFacilitators:\n";
        $expectedmsg .= "Facilitator Three\n";
        $expectedmsg .= "Facilitator Two\n";
        $expectedmsg .= "\nThose are all the details.";

        $this->assertEquals($expectedmsg, $replacedmsg);
    }

    /**
     * Tests the output of facetoface_get_default_notifications.
     */
    public function test_facetoface_get_default_notifications() {
        global $DB;

        $course = $this->getDataGenerator()->create_course();

        /** @var \mod_facetoface\testing\generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $facetofacedata = array(
            'name' => 'facetoface',
            'course' => $course->id
        );
        $facetoface = $facetofacegenerator->create_instance($facetofacedata);

        list($notifications, $missing) = facetoface_get_default_notifications($facetoface->id);

        // Get templates.
        $templaterecords = $DB->get_records('facetoface_notification_tpl');

        // There should be no missing notifications.
        $this->assertEmpty($missing);

        // The number of default notifications should equal the number of templates.
        $this->assertEquals(count($templaterecords), count($notifications));
    }

    /**
     * Tests values returned by facetoface_notification_get_templates_with_old_placeholders.
     */
    public function test_facetoface_notification_get_templates_with_old_placeholders() {
        global $DB;

        $oldnotifications = facetoface_notification_get_templates_with_old_placeholders();
        // There should be no oldplaceholders in templates on a newly installed 9.0 site.
        // We expect an empty array, rather than false or null.
        $this->assertEquals(array(), $oldnotifications);

        // A template with the placeholder in the title.
        $newtemplate1 = new stdClass();
        $newtemplate1->title = 'Sometitle with an old placeholder [session:location] ...';
        $newtemplate1->body = 'A body with a new placeholder [session:room:location] ...';
        $newtemplate1->managerprefix = 'A managerprefix with a new placeholder [session:room:link] ...';
        $newtemplate1->status = 1;
        $newtemplate1->id = $DB->insert_record('facetoface_notification_tpl', $newtemplate1);

        // A template with the placeholder in the body.
        $newtemplate2 = new stdClass();
        $newtemplate2->title = 'Sometitle with an no placeholders';
        $newtemplate2->body = 'A body with a new placeholder [session:venue] ...';
        $newtemplate2->managerprefix = null; // Managerprefix field can be null.
        $newtemplate2->status = 1;
        $newtemplate2->id = $DB->insert_record('facetoface_notification_tpl', $newtemplate2);

        // A template with the placeholder in the managerprefix.
        $newtemplate3 = new stdClass();
        $newtemplate3->title = 'Sometitle with a new placeholder [session:room:name] ...';
        $newtemplate3->body = 'A body with no placeholders ...';
        $newtemplate3->managerprefix = 'A managerprefix with two old placeholders [session:room] and [alldates]...';
        $newtemplate3->status = 1;
        $newtemplate3->id = $DB->insert_record('facetoface_notification_tpl', $newtemplate3);

        // Another new template with no old placeholders.
        $newtemplate4 = new stdClass();
        $newtemplate4->title = 'Sometitle with a new placeholder [session:room:location] ...';
        $newtemplate4->body = 'A body with a placeholders that works before and after 9.0 [startdate] ...';
        $newtemplate4->managerprefix = 'A managerprefix with no placeholders...';
        $newtemplate4->status = 1;
        $newtemplate4->id = $DB->insert_record('facetoface_notification_tpl', $newtemplate4);

        // Let's edit an existing template to include an old placeholder.
        $existingtemplate = $DB->get_record('facetoface_notification_tpl', array('reference' => 'confirmation'));
        $existingtemplate->body = 'Overwriting the body with a message the includes an old template [session:room] ...';
        $DB->update_record('facetoface_notification_tpl', $existingtemplate);

        // We need to clear the cache.
        $cacheoptions = array(
            'simplekeys' => true,
            'simpledata' => true
        );
        $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_facetoface', 'notificationtpl', array(), $cacheoptions);
        $cache->delete('oldnotifications');

        $oldnotifications = facetoface_notification_get_templates_with_old_placeholders();

        $expected = array(
            $newtemplate1->id,
            $newtemplate2->id,
            $newtemplate3->id,
            $existingtemplate->id
        );

        // Order does not matter. Sorting both should set the orders in each to be the same.
        sort($expected);
        sort($oldnotifications);
        $this->assertEquals($expected, $oldnotifications);
    }

    /**
     * Check auto notifications duplicates recovery code
     */
    public function test_notification_duplicates() {
        global $DB;
        $sessionok = $this->generate_data(false);
        $sessionbad = $session = $this->generate_data(true);

        // Make duplicate.
        $duplicate = $DB->get_record('facetoface_notification', array(
            'facetofaceid' => $sessionbad->facetoface,
            'type' => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION
        ));
        $duplicate->id = null;
        $DB->insert_record('facetoface_notification', $duplicate);

        $noduplicate = $DB->get_record('facetoface_notification', array(
            'facetofaceid' => $sessionok->facetoface,
            'type' => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION
        ));
        $noduplicate->id = null;
        $noduplicate->type = 1;
        $DB->insert_record('facetoface_notification', $noduplicate);

        // Check duplicates detection.
        $this->assertTrue(facetoface_notification::has_auto_duplicates($sessionbad->facetoface));
        $this->assertFalse(facetoface_notification::has_auto_duplicates($sessionok->facetoface));

        // Check that it will not fail when attempted to send duplicate.
        $facetoface = $DB->get_record('facetoface', array('id' => $sessionbad->facetoface));
        $course = $DB->get_record("course", array('id' => $facetoface->course));
        $student = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);

        $signup = signup_helper::signup(\mod_facetoface\signup::create($student->id, new \mod_facetoface\seminar_event($session->id)));
        \mod_facetoface\notice_sender::signup_cancellation($signup);

        $this->assertDebuggingCalled();

        // Check duplicates prevention.
        $allbefore = $DB->get_records('facetoface_notification', array('facetofaceid' => $sessionok->facetoface));

        $note = new facetoface_notification(array(
            'facetofaceid'  => $sessionok->facetoface,
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION
        ));
        $note->id = null;
        $note->save();
        $this->assertDebuggingCalled();

        $allafter = $DB->get_records('facetoface_notification', array('facetofaceid' => $sessionok->facetoface));
        $this->assertEquals(count($allbefore), count($allafter));
    }

    private function session_generate_data($future = true) {
        global $DB;

        $this->setAdminUser();

        $teacher1 = $this->getDataGenerator()->create_user();
        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();
        $manager  = $this->getDataGenerator()->create_user();

        $managerja = \totara_job\job_assignment::create_default($manager->id);
        \totara_job\job_assignment::create_default($student1->id, array('managerjaid' => $managerja->id));
        \totara_job\job_assignment::create_default($student2->id, array('managerjaid' => $managerja->id));

        $course = $this->getDataGenerator()->create_course();

        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        $this->getDataGenerator()->enrol_user($teacher1->id, $course->id, $teacherrole->id);
        $this->getDataGenerator()->enrol_user($student1->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course->id, $studentrole->id);

        /** @var \mod_facetoface\testing\generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $facetofacedata = array(
            'name' => 'facetoface',
            'course' => $course->id
        );
        $facetoface = $facetofacegenerator->create_instance($facetofacedata);

        $sessiondate = new stdClass();
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $sessiondate->timestart = time() + WEEKSECS;
        $sessiondate->timefinish = time() + WEEKSECS + 60;

        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate),
            'mincapacity' => '1',
            'cutoff' => DAYSECS - 60
        );

        $sessionid = $facetofacegenerator->add_session($sessiondata);
        $sessiondata['datetimeknown'] = '1';
        $seminarevent = new seminar_event($sessionid);

        return array($seminarevent, $facetoface, $course, $student1, $student2, $teacher1, $manager);
    }

    public function test_booking_confirmation_default() {
        // Default test Manager copy is enable and suppressccmanager is disabled.
        list($seminarevent, $facetoface, $course, $student1, $student2, $teacher1, $manager) = $this->session_generate_data();

        $emailsink = $this->redirectMessages();
        signup_helper::signup(\mod_facetoface\signup::create($student1->id, $seminarevent));
        $this->executeAdhocTasks();
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertCount(2, $emails, 'Wrong booking confirmation for Default test Manager copy is enable and suppressccmanager is disabled.');
    }

    /**
     * $this->test_booking_confirmation_default() checks that both user and manager receive the signup notification.
     * This function servers to test the other three possibilities:
     * - notification to user only;
     * - notification to manager only;
     * - notification to neither user, or manager.
     */
    public function test_booking_confirmation_not_sent() {

        $emailsink = $this->redirectMessages();

        // Only send to user.
        list($seminarevent, $facetoface, $course, $student1, $student2, $teacher1, $manager) = $this->session_generate_data();
        $new_signup = \mod_facetoface\signup::create($student1->id, $seminarevent);
        $new_signup->set_skipusernotification(false);
        $new_signup->set_skipmanagernotification();
        signup_helper::signup($new_signup);
        $this->executeAdhocTasks();
        $emails = $emailsink->get_messages();
        $this->assertCount(1, $emails, 'Incorrect number of notification emails generated for signup.');
        $emailsink->clear();
        $new_signup = null;

        // Only send to manager.
        $new_signup = \mod_facetoface\signup::create($student2->id, $seminarevent);
        $new_signup->set_skipusernotification();
        $new_signup->set_skipmanagernotification(false);
        signup_helper::signup($new_signup);
        $this->executeAdhocTasks();
        $emails = $emailsink->get_messages();
        $this->assertCount(1, $emails, 'Incorrect number of notification emails generated for signup.');
        $emailsink->clear();
        $new_signup = null;

        // Send to neither user, nor manager.
        list($seminarevent, $facetoface, $course, $student1, $student2, $teacher1, $manager) = $this->session_generate_data();
        $new_signup = \mod_facetoface\signup::create($student1->id, $seminarevent);
        $new_signup->set_skipusernotification();
        $new_signup->set_skipmanagernotification();
        signup_helper::signup($new_signup);
        $this->executeAdhocTasks();
        $emails = $emailsink->get_messages();
        $this->assertCount(0, $emails, 'Incorrect number of notification emails generated for signup.');

        $emailsink->close();
    }

    public function test_booking_confirmation_suppress_ccmanager() {
        // Test Manager copy is enable and suppressccmanager is enabled(do not send a copy to manager).
        list($seminarevent, $facetoface, $course, $student1, $student2, $teacher1, $manager) = $this->session_generate_data();

        $emailsink = $this->redirectMessages();
        $signup = \mod_facetoface\signup::create($student1->id, $seminarevent);
        $signup->set_skipmanagernotification();
        signup_helper::signup($signup);
        $this->executeAdhocTasks();
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertCount(1, $emails, 'Wrong booking confirmation for Test Manager copy is enable and suppressccmanager is enabled(do not send a copy to manager).');
    }

    public function test_booking_confirmation_no_ccmanager() {
        // Test Manager copy is disabled and suppressccmanager is disbaled.
        list($seminarevent, $facetoface, $course, $student1, $student2, $teacher1, $manager) = $this->session_generate_data();

        $params = array(
            'facetofaceid'  => $facetoface->id,
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_BOOKING_CONFIRMATION
        );
        $this->update_notification($params, 0);

        $emailsink = $this->redirectMessages();
        signup_helper::signup(\mod_facetoface\signup::create($student1->id, $seminarevent));
        $this->executeAdhocTasks();
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertCount(1, $emails, 'Wrong booking confirmation for Test Manager copy is disabled and suppressccmanager is disbaled.');
    }

    public function test_booking_confirmation_no_ccmanager_and_suppress_ccmanager() {
        // Test Manager copy is disabled and suppressccmanager is disbaled.
        list($seminarevent, $facetoface, $course, $student1, $student2, $teacher1, $manager) = $this->session_generate_data();

        $suppressccmanager = true;

        $params = array(
            'facetofaceid'  => $facetoface->id,
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_BOOKING_CONFIRMATION
        );
        $this->update_notification($params, 0);

        $data = array();
        if ($suppressccmanager) {
            $data['ccmanager'] = 0;
        }
        $emailsink = $this->redirectMessages();
        signup_helper::signup(\mod_facetoface\signup::create($student1->id, $seminarevent));
        $this->executeAdhocTasks();
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertCount(1, $emails, 'Wrong booking confirmation for Test Manager copy is disabled and suppressccmanager is disbaled.');
    }

    public function test_booking_cancellation_default() {
        // Default test Manager copy is enable and suppressccmanager is disabled.
        list($seminarevent, $facetoface, $course, $student1, $student2, $teacher1, $manager) = $this->session_generate_data();

        $emailsink = $this->redirectMessages();
        $signup = signup_helper::signup(\mod_facetoface\signup::create($student1->id, $seminarevent));
        $this->executeAdhocTasks();
        $emailsink->close();

        $emailsink = $this->redirectMessages();

        if (signup_helper::can_user_cancel($signup)) {
            signup_helper::user_cancel($signup);
            \mod_facetoface\notice_sender::signup_cancellation($signup);
        }

        $this->executeAdhocTasks();
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertCount(2, $emails, 'Wrong booking cancellation for Default test Manager copy is enable and suppressccmanager is disabled.');
    }

    public function test_booking_cancellation_suppress_ccmanager() {
        // Test Manager copy is enable and suppressccmanager is enabled.
        list($seminarevent, $facetoface, $course, $student1, $student2, $teacher1, $manager) = $this->session_generate_data();

        $suppressccmanager = true;
        $emailsink = $this->redirectMessages();

        $signup = signup_helper::signup(\mod_facetoface\signup::create($student1->id, $seminarevent));
        $this->executeAdhocTasks();

        $emailsink->close();
        $emailsink = $this->redirectMessages();

        if (signup_helper::can_user_cancel($signup)) {
           signup_helper::user_cancel($signup);
            if ($suppressccmanager) {
                $signup->set_skipmanagernotification();
            }
            \mod_facetoface\notice_sender::signup_cancellation($signup);
        }

        $this->executeAdhocTasks();
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertCount(1, $emails, 'Wrong booking cancellation for Test Manager copy is enable and suppressccmanager is enabled.');
    }

    public function test_booking_cancellation_only_ccmanager() {
        // Test Manager copy is disabled and suppressccmanager is disbaled.
        list($seminarevent, $facetoface, $course, $student1, $student2, $teacher1, $manager) = $this->session_generate_data();

        $emailsink = $this->redirectMessages();
        $signup = signup_helper::signup(\mod_facetoface\signup::create($student1->id, $seminarevent));
        $this->executeAdhocTasks();
        $emailsink->close();

        $params = array(
            'facetofaceid'  => $facetoface->id,
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION
        );

        $this->update_notification($params, 1);
        $emailsink = $this->redirectMessages();

        if (signup_helper::can_user_cancel($signup)) {
            signup_helper::user_cancel($signup);
            $signup->set_skipusernotification();
            \mod_facetoface\notice_sender::signup_cancellation($signup);
        }

        $this->executeAdhocTasks();
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertCount(1, $emails, 'Only one message is expected');
        $this->assertEquals($manager->id, $emails[0]->useridto);
        $joinedbody = str_replace("=\n", "", $emails[0]->fullmessagehtml);
        $this->assertStringContainsString('you as their Team Leader', $joinedbody);
    }

    public function test_booking_cancellation_no_ccmanager() {
        // Test Manager copy is disabled and suppressccmanager is disbaled.
        list($seminarevent, $facetoface, $course, $student1, $student2, $teacher1, $manager) = $this->session_generate_data();

        $emailsink = $this->redirectMessages();
        $signup = signup_helper::signup(\mod_facetoface\signup::create($student1->id, $seminarevent));
        $this->executeAdhocTasks();
        $emailsink->close();

        $params = array(
            'facetofaceid'  => $facetoface->id,
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION
        );

        $this->update_notification($params, 0);

        $emailsink = $this->redirectMessages();
        if (signup_helper::can_user_cancel($signup)) {
            signup_helper::user_cancel($signup);
            \mod_facetoface\notice_sender::signup_cancellation($signup);
        }

        $this->executeAdhocTasks();
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertCount(1, $emails, 'Wrong booking cancellation for Test Manager copy is disabled and suppressccmanager is disbaled.');
    }

    public function test_booking_cancellation_no_ccmanager_and_suppress_ccmanager() {
        // Test Manager copy is disabled and suppressccmanager is disbaled.
        list($seminarevent, $facetoface, $course, $student1, $student2, $teacher1, $manager) = $this->session_generate_data();

        $suppressccmanager = true;
        $emailsink = $this->redirectMessages();

        $signup = signup_helper::signup(\mod_facetoface\signup::create($student1->id, $seminarevent));
        $this->executeAdhocTasks();
        $emailsink->close();

        $params = array(
            'facetofaceid'  => $facetoface->id,
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION
        );
        $this->update_notification($params, 0);

        $emailsink = $this->redirectMessages();

        if (signup_helper::can_user_cancel($signup)) {
            signup_helper::user_cancel($signup);
            if ($suppressccmanager) {
                $signup->set_skipmanagernotification();
            }
            \mod_facetoface\notice_sender::signup_cancellation($signup);
        }

        $this->executeAdhocTasks();
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertCount(1, $emails, 'Wrong booking cancellation for Test Manager copy is disabled and suppressccmanager is disbaled.');
    }

    private function update_notification($params, $ccmanager) {
        global $DB;

        $notification = new facetoface_notification($params);

        $notice = new stdClass();
        $notice->id = $notification->id;
        $notice->ccmanager = $ccmanager;

        return $DB->update_record('facetoface_notification', $notice);
    }

    public function test_user_timezone() {
        global $DB;

        $emailsink = $this->redirectMessages();
        list($sessiondate, $student1, $student2, $student3) = $this->session_generate_timezone(99);
        $this->executeAdhocTasks();
        $emailsink->close();

        // Test we are getting F2F booking confirmation email.
        $haystack = $emailsink->get_messages();
        $this->notification_content_test(
            'This is to confirm that you are now booked on the following course',
            $haystack,
            'Wrong notification, must be Face-to-face booking confirmation');

        $alldates = $this->get_user_date($sessiondate, $student1);
        // Test user timezone date with session timezone date.
        $this->assertStringContainsString(
            $alldates,
            $haystack[0]->fullmessagehtml,
            'Wrong session timezone date for student 1 Face-to-face booking confirmation notification');

        $alldates = $this->get_user_date($sessiondate, $student2);
        // Test user timezone date with session timezone date.
        $this->assertStringContainsString(
            $alldates,
            $haystack[1]->fullmessagehtml,
            'Wrong session timezone date for student 2 Face-to-face booking confirmation notification');

        $alldates = $this->get_user_date($sessiondate, $student3);
        // Test user timezone date with session timezone date.
        $this->assertStringContainsString(
            $alldates,
            $haystack[2]->fullmessagehtml,
            'Wrong session timezone date for student 3 Face-to-face booking confirmation notification');

        $scheduled = $DB->get_records_select('facetoface_notification', 'conditiontype = ?', array(MDL_F2F_CONDITION_BEFORE_SESSION));
        $this->assertCount(1, $scheduled);
        $notify = reset($scheduled);
        $emailsink = $this->redirectMessages();
        $notification = new \facetoface_notification((array)$notify, false);
        $notification->send_scheduled();
        $this->executeAdhocTasks();
        $emailsink->close();
        // Test we are getting F2F booking reminder email.
        $haystack = $emailsink->get_messages();
        $this->notification_content_test(
            'This is a reminder that you are booked on the following course',
            $haystack,
            'Wrong notification, must be Face-to-face booking reminder');

        $alldates = $this->get_user_date($sessiondate, $student1);
        // Test user timezone date with session timezone date.
        $this->assertStringContainsString(
            $alldates,
            $haystack[0]->fullmessagehtml,
            'Wrong session timezone date for student 1 of Face-to-face booking reminder notification');

        $alldates = $this->get_user_date($sessiondate, $student2);
        // Test user timezone date with session timezone date.
        $this->assertStringContainsString(
            $alldates,
            $haystack[1]->fullmessagehtml,
            'Wrong session timezone date for student 2 of Face-to-face booking reminder notification');

        $alldates = $this->get_user_date($sessiondate, $student3);
        // Test user timezone date with session timezone date.
        $this->assertStringContainsString(
            $alldates,
            $haystack[2]->fullmessagehtml,
            'Wrong session timezone date for student 3 of Face-to-face booking reminder notification');
    }

    public function test_session_timezone() {
        global $DB;

        $test = new stdClass();
        $test->timezone = 'America/New_York';

        $emailsink = $this->redirectMessages();
        list($sessiondate, $student1, $student2, $student3) = $this->session_generate_timezone($test->timezone);
        $this->executeAdhocTasks();
        $emailsink->close();

        // Test we are getting F2F booking confirmation email.
        $haystack = $emailsink->get_messages();
        $this->notification_content_test(
            'This is to confirm that you are now booked on the following course',
            $haystack,
            'Wrong notification, must be Face-to-face booking confirmation');

        $alldates = $this->get_user_date($sessiondate, $test);

        // Test user timezone date with session timezone date.
        $this->assertStringContainsString(
            $alldates,
            $haystack[0]->fullmessagehtml,
            'Wrong session timezone date for student 1 Face-to-face booking confirmation notification');

        // Test user timezone date with session timezone date.
        $this->assertStringContainsString(
            $alldates,
            $haystack[1]->fullmessagehtml,
            'Wrong session timezone date for student 2 Face-to-face booking confirmation notification');

        // Test user timezone date with session timezone date.
        $this->assertStringContainsString(
            $alldates,
            $haystack[2]->fullmessagehtml,
            'Wrong session timezone date for student 3 Face-to-face booking confirmation notification');

        $scheduled = $DB->get_records_select('facetoface_notification', 'conditiontype = ?', array(MDL_F2F_CONDITION_BEFORE_SESSION));
        $this->assertCount(1, $scheduled);
        $notify = reset($scheduled);
        $emailsink = $this->redirectMessages();
        $notification = new \facetoface_notification((array)$notify, false);
        $notification->send_scheduled();
        $this->executeAdhocTasks();
        $emailsink->close();
        // Test we are getting F2F booking reminder email.
        $haystack = $emailsink->get_messages();
        $this->notification_content_test(
            'This is a reminder that you are booked on the following course',
            $haystack,
            'Wrong notification, must be Face-to-face booking reminder');

        // Test user timezone date with session timezone date.
        $this->assertStringContainsString(
            $alldates,
            $haystack[0]->fullmessagehtml,
            'Wrong session timezone date for student 1 of Face-to-face booking reminder notification');

        // Test user timezone date with session timezone date.
        $this->assertStringContainsString(
            $alldates,
            $haystack[1]->fullmessagehtml,
            'Wrong session timezone date for student 2 of Face-to-face booking reminder notification');

        // Test user timezone date with session timezone date.
        $this->assertStringContainsString(
            $alldates,
            $haystack[2]->fullmessagehtml,
            'Wrong session timezone date for student 3 of Face-to-face booking reminder notification');
    }

    /**
     * Test facetoface cancel session notification
     */
    public function test_facetoface_cancel_session() {
        global $DB;
        $this->setAdminUser();

        /** @var \mod_facetoface\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $course = $this->getDataGenerator()->create_course();

        $facetoface = $generator->create_instance(array('course' => $course->id, 'approvaltype' => 0));
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + DAYSECS;
        $sessiondate->timefinish = $sessiondate->timestart + (DAYSECS * 2);
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $session1id = $generator->add_session(array('facetoface' => $facetoface->id, 'sessiondates' => array($sessiondate)));
        $session2id = $generator->add_session(array('facetoface' => $facetoface->id, 'sessiondates' => []));

        $facetoface2 = $generator->create_instance(array('course' => $course->id, 'approvaltype' => \mod_facetoface\seminar::APPROVAL_ADMIN));
        $session3id = $generator->add_session(array('facetoface' => $facetoface2->id, 'sessiondates' => array($sessiondate)));

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course->id);
        $this->getDataGenerator()->enrol_user($user4->id, $course->id);
        $this->getDataGenerator()->enrol_user($user5->id, $course->id);

        $managerja = \totara_job\job_assignment::create_default($manager->id);
        \totara_job\job_assignment::create_default($user4->id, array('managerjaid' => $managerja->id));
        \totara_job\job_assignment::create_default($user5->id, array('managerjaid' => $managerja->id));

        $seminarevent1 = new seminar_event($session1id);
        $seminarevent2 = new seminar_event($session2id);
        $seminarevent3 = new seminar_event($session3id);

        signup_helper::signup(\mod_facetoface\signup::create($user1->id, new \mod_facetoface\seminar_event($session1id)));
        \mod_facetoface\signup_helper::cancel_waitlist($seminarevent1, array($user1->id));

        $signup2 = \mod_facetoface\signup::create($user2->id, new \mod_facetoface\seminar_event($session1id));
        signup_helper::signup($signup2);
        signup_helper::signup(\mod_facetoface\signup::create($user3->id, new \mod_facetoface\seminar_event($session2id)));
        signup_helper::signup(\mod_facetoface\signup::create($user4->id, new \mod_facetoface\seminar_event($session3id)));
        signup_helper::signup(\mod_facetoface\signup::create($user5->id, new \mod_facetoface\seminar_event($session3id)));

        $signup53 = \mod_facetoface\signup::create($user5->id, $seminarevent3);
        $signup53->switch_state(\mod_facetoface\signup\state\declined::class);

        $sql = "SELECT ss.statuscode
                  FROM {facetoface_signups} s
                  JOIN {facetoface_signups_status} ss ON ss.signupid = s.id
                 WHERE s.sessionid = :sid AND ss.superceded = 0 AND s.userid = :uid";

        $this->assertEquals(\mod_facetoface\signup\state\user_cancelled::get_code(), $DB->get_field_sql($sql, array('sid' => $session1id, 'uid' => $user1->id)));
        $this->assertEquals(\mod_facetoface\signup\state\booked::get_code(), $DB->get_field_sql($sql, array('sid' => $session1id, 'uid' => $user2->id)));
        $this->assertEquals(\mod_facetoface\signup\state\waitlisted::get_code(), $DB->get_field_sql($sql, array('sid' => $session2id, 'uid' => $user3->id)));
        $this->assertEquals(\mod_facetoface\signup\state\requested::get_code(), $DB->get_field_sql($sql, array('sid' => $session3id, 'uid' => $user4->id)));
        $this->assertEquals(\mod_facetoface\signup\state\declined::get_code(), $DB->get_field_sql($sql, array('sid' => $session3id, 'uid' => $user5->id)));

        // Clean messages stack.
        $emailsink = $this->redirectMessages();
        $this->executeAdhocTasks();
        $emailsink->close();

        // Now test cancelling the session.
        $emailsink = $this->redirectMessages();
        $result1 = $seminarevent1->cancel();
        $result2 = $seminarevent2->cancel();
        $result3 = $seminarevent3->cancel();
        $this->assertTrue($result1);
        $this->assertTrue($result2);
        $this->assertTrue($result3);
        $this->executeAdhocTasks();
        $emailsink->close();

        $messages = $emailsink->get_messages();
        $this->assertCount(3, $messages);

        // Users that have cancelled their session or their request have been declined should not being affected when a
        // session is cancelled.
        $affectedusers = array($user2->id, $user3->id, $user4->id);
        foreach ($messages as $message) {
            $this->assertStringContainsString('Seminar event cancellation', $message->subject);
            $this->assertStringContainsString('This is to advise that the following session has been cancelled', $message->fullmessagehtml);
            $this->assertStringContainsString('Course:   Test course 1', $message->fullmessagehtml);
            $this->assertStringContainsString('Seminar:   Seminar ', $message->fullmessagehtml);
            $this->assertContains($message->useridto, $affectedusers);
        }
    }

    private function session_generate_timezone($sessiontimezone) {
        global $DB, $CFG;

        $this->setAdminUser();

        // Server timezone is Australia/Perth = $CFG->timezone.
        $student1 = $this->getDataGenerator()->create_user(array('timezone' => 'Europe/London'));
        $student2 = $this->getDataGenerator()->create_user(array('timezone' => 'Pacific/Auckland'));
        $student3 = $this->getDataGenerator()->create_user(array('timezone' => $CFG->timezone));
        $this->assertEquals($student1->timezone, 'Europe/London');
        $this->assertEquals($student2->timezone, 'Pacific/Auckland');
        $this->assertEquals($student3->timezone, $CFG->timezone);

        \totara_job\job_assignment::create_default($student1->id);
        \totara_job\job_assignment::create_default($student2->id);
        \totara_job\job_assignment::create_default($student3->id);

        $course = $this->getDataGenerator()->create_course();

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($student1->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($student3->id, $course->id, $studentrole->id);

        /** @var \mod_facetoface\testing\generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $facetofacedata = array(
            'name' => 'facetoface',
            'course' => $course->id
        );
        $facetoface = $facetofacegenerator->create_instance($facetofacedata);

        $sessiondate = new stdClass();
        $sessiondate->sessiontimezone = $sessiontimezone;
        $sessiondate->timestart = time() + DAYSECS;
        $sessiondate->timefinish = time() + DAYSECS + (4 * HOURSECS);

        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 5,
            'sessiondates' => array($sessiondate),
        );

        $sessionid = $facetofacegenerator->add_session($sessiondata);
        $seminarevent = new seminar_event($sessionid);
        $sessiondata['datetimeknown'] = '1';
        $session = $DB->get_record('facetoface_sessions', array('id' => $sessionid));
        $session->sessiondates = $seminarevent->get_sessions()->sort('timestart')->to_records(false);

        signup_helper::signup(\mod_facetoface\signup::create($student1->id, new \mod_facetoface\seminar_event($session->id)));
        signup_helper::signup(\mod_facetoface\signup::create($student2->id, new \mod_facetoface\seminar_event($session->id)));
        signup_helper::signup(\mod_facetoface\signup::create($student3->id, new \mod_facetoface\seminar_event($session->id)));

        return array($sessiondate, $student1, $student2, $student3);
    }

    private function notification_content_test($needlebody, $emails, $message) {

        $this->assertStringContainsString($needlebody, $emails[0]->fullmessagehtml, $message);
        $this->assertStringContainsString($needlebody, $emails[1]->fullmessagehtml, $message);
        $this->assertStringContainsString($needlebody, $emails[2]->fullmessagehtml, $message);
    }

    private function get_user_date($sessiondate, $date) {
        // Get user settings.
        $alldates = '';
        $strftimedate = get_string('strftimedate');
        $strftimetime = get_string('strftimetime');

        $startdate  = userdate($sessiondate->timestart, $strftimedate, $date->timezone);
        $startime   = userdate($sessiondate->timestart, $strftimetime, $date->timezone);

        $finishdate = userdate($sessiondate->timefinish, $strftimedate, $date->timezone);
        $finishtime = userdate($sessiondate->timefinish, $strftimetime, $date->timezone);

        // Template example: [session:startdate], [session:starttime] - [session:finishdate], [session:finishtime] [session:timezone]
        $alldates .= $startdate .', '.$startime .' - '. $finishdate .', '. $finishtime . ' '. $date->timezone;

        return $alldates;
    }

    /**
     * Test sending notifications when "facetoface_oneemailperday" is enabled,
     * with a event without a date and the learner is waitlisted.
     */
    public function test_oneperday_waitlisted_no_events() {
        global $DB;
        $this->setAdminUser();

        set_config('facetoface_oneemailperday', true);

        $student1 = $this->getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($student1->id, $course->id, $studentrole->id);

        /** @var \mod_facetoface\testing\generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $facetofacedata = array(
            'name' => 'facetoface',
            'course' => $course->id
        );
        $facetoface = $facetofacegenerator->create_instance($facetofacedata);

        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => [],
            'mincapacity' => '1'
        );
        $seminarevent = new seminar_event($facetofacegenerator->add_session($sessiondata));
        $sessiondata['datetimeknown'] = '0';

        $emailsink = $this->redirectMessages();
        signup_helper::signup(\mod_facetoface\signup::create($student1->id, $seminarevent));
        $this->executeAdhocTasks();
        $emailsink->close();

        $preemails = $emailsink->get_messages();
        foreach ($preemails as $preemail) {
            $this->assertStringContainsString("This is to advise that you have been added to the waitlist", $preemail->fullmessagehtml);
        }
    }

    /**
     * Test facetoface_is_notification_active function works correctly with all the available seminar notification.
     */
    public function test_facetoface_is_notification_active() {
        global $DB,$CFG;

        // Seeding initial data.
        $f2f = $this->getDataGenerator()
            ->get_plugin_generator('mod_facetoface')
            ->create_instance([
                'name' => 'facetoface',
                'course' => $this->getDataGenerator()->create_course()->id
            ]);

        $states = [true, false];

        $get_notification = function ($f2fid, $type) use ($DB) {
            return facetoface_notification::fetch([
                'facetofaceid' => $f2fid,
                'conditiontype' => $type,
            ]);
        };

        foreach (facetoface_notification::get_references() as $notification => $type) {

            $local = $get_notification($f2f->id, $type);

            foreach ($states as $state) {
                $DB->update_record('facetoface_notification', (object) [
                    'id' => $local->id,
                    'status' => (int) $state,
                ]);

                // Calling twice as it supports 'overload' where either notification type (int) or object can be passed.
                $this->assertEquals(facetoface_is_notification_active($get_notification($f2f->id, $type)), $state);
                $this->assertEquals(facetoface_is_notification_active($type, $f2f), $state);

                // Check it works with the global flag.
                $CFG->facetoface_notificationdisable = 1;
                $this->assertFalse(facetoface_is_notification_active($type, $f2f, true));
                $this->assertEquals(facetoface_is_notification_active($type, $f2f), $state);
                unset($CFG->facetoface_notificationdisable);
            }
        }
    }

    /**
     * Test to restore missing default notification templates for existing seminars,
     * this is happening when upgrade from t2.9 to t9.
     */
    public function test_restore_missing_default_notifications() {
        global $DB;

        // Seeding initial data.
        $f2f1 = $this->getDataGenerator()
            ->get_plugin_generator('mod_facetoface')
            ->create_instance([
                'name' => 'Seminar 17288A',
                'course' => $this->getDataGenerator()->create_course()->id
            ]);
        $f2f2 = $this->getDataGenerator()
            ->get_plugin_generator('mod_facetoface')
            ->create_instance([
                'name' => 'Seminar 17288B',
                'course' => $this->getDataGenerator()->create_course()->id
            ]);
        $f2f3 = $this->getDataGenerator()
            ->get_plugin_generator('mod_facetoface')
            ->create_instance([
                'name' => 'Seminar 17288C',
                'course' => $this->getDataGenerator()->create_course()->id
            ]);

        // Get a count default notification templates.
        $counttpl = $DB->count_records('facetoface_notification_tpl');
        // Get total amount all notifications for 3 seminars.
        $countnote = $DB->count_records('facetoface_notification');

        // Multiply default count by 3 as we have 3 seminars created.
        $this->assertEquals($countnote, $counttpl * 3);

        // Test the facetoface_notification_get_missing_templates() function there are no missing templates.
        $this->assertEmpty(facetoface_notification_get_missing_templates());

        // Test facetoface_notification_restore_missing_template function there are nothing to restore.
        $affectedrows = facetoface_notification_restore_missing_template(MDL_F2F_CONDITION_SESSION_CANCELLATION);
        $this->assertEquals(0, $affectedrows);

        // This a hack to pretend that the 'Seminar event cancellation' default template is missing.
        $DB->delete_records('facetoface_notification', ['type' => MDL_F2F_NOTIFICATION_AUTO, 'conditiontype' => MDL_F2F_CONDITION_SESSION_CANCELLATION]);
        // Test we deleted 3 records.
        $this->assertEquals($countnote - 3, $DB->count_records('facetoface_notification'));

        // Test the facetoface_notification_get_missing_templates() function there are missing templates.
        // MDL_F2F_CONDITION_SESSION_CANCELLATION is missing template.
        $this->assertCount(1, facetoface_notification_get_missing_templates());

        // Restore templates.
        $affectedrows = facetoface_notification_restore_missing_template(MDL_F2F_CONDITION_SESSION_CANCELLATION);
        $this->assertEquals(3, $affectedrows);
    }

    /**
     * Test that under capacity notifications are not sent for cancelled notifications.
     * @dataProvider status_provider
     */
    public function test_facetoface_notify_under_capacity_not_sent_for_cancelled_events($cancelled) {
        global $CFG, $DB;

        $course = $this->getDataGenerator()->create_course();

        /**
         * @var \mod_facetoface\testing\generator $seminargen
         */
        $seminargen = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $seminarrec = $seminargen->create_instance([
            'name' => 'Seminar 1',
            'course' => $course->id
        ]);

        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + DAYSECS;
        $sessiondate->timefinish = $sessiondate->timestart + (DAYSECS * 2);
        $sessiondate->sessiontimezone = 'Pacific/Auckland';

        $seminareventid = $seminargen->add_session([
            'facetoface' => $seminarrec->id,
            'cutoff' => DAYSECS+1,
            'mincapacity' => 1,
            'cancelledstatus' => $cancelled,
            'sessiondates' => [$sessiondate]
        ]);

        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $CFG->facetoface_session_rolesnotify = $teacherrole->id;
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $teacherrole->id);

        $sessrole = new stdClass();
        $sessrole->roleid = $teacherrole->id;
        $sessrole->sessionid = $seminareventid;
        $sessrole->userid = $user->id;
        $DB->insert_record('facetoface_session_roles', $sessrole);

        $emailsink = $this->redirectMessages();

        $helper = new \mod_facetoface\notification\notification_helper();
        $helper->notify_under_capacity();
        $this->executeAdhocTasks();

        $messages = $emailsink->get_messages();
        $emailsink->close();

        $CFG->facetoface_session_rolesnotify = '';

        if ($cancelled) {
            $this->assertCount(0, $messages);
        } else {
            $this->assertCount(1, $messages);
            $this->assertStringContainsString('Event under minimum bookings', current($messages)->subject);
        }
    }

    /**
     * Provider for test_facetoface_notify_under_capacity_not_sent_for_cancelled_events
     * @return array
     */
    public function status_provider() {
        return [
            [0],
            [1]
        ];
    }

    public function test_trainer_confirmation_trainer_unassigned(): void {
        global $DB;

        // Prepare data.
        $f2f = $this->create_facetoface();
        $role = $DB->get_record('role', array('shortname' => 'teacher'));
        $DB->set_field('facetoface', 'approvalrole', $role->id, ['id' => $f2f->get_id()]);
        $seminarevent = $f2f->get_events()->current();

        // Assign teacher1 to seminar event.
        $teacher1 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher1->id, $f2f->get_course(), $role->id);
        $teachers[] = $teacher1->id;
        $form[$role->id] = $teachers;
        $sink = $this->redirectEmails();

        $helper = new \mod_facetoface\trainer_helper($seminarevent);
        foreach ($form as $roleid => $trainers) {
            $helper->add_trainers($roleid, $trainers);
        }

        $form = [];
        $teachers = [];

        $count = $DB->count_records('facetoface_session_roles', ['roleid' => $role->id, 'userid' => $teacher1->id]);
        $this->assertEquals(1, (int)$count);

        $this->executeAdhocTasks();
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $messages);

        $message = $messages[0];
        $this->assertSame($teacher1->email, $message->to);
        $this->assertStringStartsWith('Seminar trainer confirmation:', $message->subject);

        // Assign teacher2 to seminar event and unassign teacher1 from, check emails.
        $teacher2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher2->id, $f2f->get_course(), $role->id);
        $teachers[] = $teacher2->id;
        $form[$role->id] = $teachers;
        $sink = $this->redirectEmails();

        $excludedusers = [];
        foreach ($form as $roleid => $trainers) {
            $added = $helper->add_trainers($roleid, $trainers);
            $excludedusers = array_merge($excludedusers, $added);
        }

        $helper->remove_trainers($excludedusers);

        unset($form, $teachers);
        $count = $DB->count_records('facetoface_session_roles', ['roleid' => $role->id, 'userid' => $teacher2->id]);
        $this->assertEquals(1, (int)$count);

        $this->executeAdhocTasks();
        $messages = $sink->get_messages();
        $sink->close();
        usort($messages, function($email1, $email2) {
            return strcmp($email1->to, $email2->to);
        });
        $this->assertCount(2, $messages);

        $message2 = $messages[1];
        $this->assertSame($teacher2->email, $message2->to);
        $this->assertStringStartsWith('Seminar trainer confirmation:', $message2->subject);

        $message1 = $messages[0];
        $this->assertSame($teacher1->email, $message1->to);
        $this->assertStringStartsWith('Seminar event trainer unassigned', $message1->subject);
        $this->assertEquals(1, 1);
    }

    /**
     * Test normal cost and discount cost with default settings
     */
    public function test_normalcost_discountcost_default_with_discountcode() {
        list($seminarevent, $facetoface, $course, $student1, $student2, $teacher1, $manager) = $this->session_generate_data();

        $emailsink = $this->redirectMessages();
        $signup = \mod_facetoface\signup::create($student1->id, $seminarevent);
        $signup->set_discountcode('XMAS');
        signup_helper::signup($signup);

        $this->executeAdhocTasks();
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertStringContainsString('Cost:', current($emails)->fullmessagehtml);
        // Test normal cost
        $this->assertStringNotContainsString('$100', current($emails)->fullmessagehtml);
        // Test discount cost
        $this->assertStringContainsString('$NZ20', current($emails)->fullmessagehtml);
    }

    /**
     * Test normal cost and discount cost with default settings
     */
    public function test_normalcost_discountcost_default_without_discountcode() {
        list($seminarevent, $facetoface, $course, $student1, $student2, $teacher1, $manager) = $this->session_generate_data();

        $emailsink = $this->redirectMessages();
        $signup = \mod_facetoface\signup::create($student1->id, $seminarevent);
        signup_helper::signup($signup);

        $this->executeAdhocTasks();
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertStringContainsString('Cost:', current($emails)->fullmessagehtml);
        // Test normal cost
        $this->assertStringContainsString('$100', current($emails)->fullmessagehtml);
        // Test discount cost
        $this->assertStringNotContainsString('$NZ20', current($emails)->fullmessagehtml);
    }

    /**
     * Test normal cost and discount cost with disabled settings
     */
    public function test_normalcost_discountcost_both_disabled() {

        set_config('facetoface_hidecost', true);
        set_config('facetoface_hidediscount', true);

        list($seminarevent, $facetoface, $course, $student1, $student2, $teacher1, $manager) = $this->session_generate_data();

        $emailsink = $this->redirectMessages();
        $signup = \mod_facetoface\signup::create($student1->id, $seminarevent);
        $signup->set_discountcode('XMAS');
        signup_helper::signup($signup);

        $this->executeAdhocTasks();
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertStringContainsString('Cost:', current($emails)->fullmessagehtml);
        // Test normal cost
        $this->assertStringNotContainsString('$100', current($emails)->fullmessagehtml);
        // Test discount cost
        $this->assertStringNotContainsString('$NZ20', current($emails)->fullmessagehtml);

        $emailsink = $this->redirectMessages();
        $signup = \mod_facetoface\signup::create($student2->id, $seminarevent);
        signup_helper::signup($signup);

        $this->executeAdhocTasks();
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertStringContainsString('Cost:', current($emails)->fullmessagehtml);
        // Test normal cost
        $this->assertStringNotContainsString('$100', current($emails)->fullmessagehtml);
        // Test discount cost
        $this->assertStringNotContainsString('$NZ20', current($emails)->fullmessagehtml);
    }

    /**
     * Test normal cost and with disabled discount cost
     */
    public function test_normalcost_discountcost_disabled_discountcode() {

        set_config('facetoface_hidediscount', true);

        list($seminarevent, $facetoface, $course, $student1, $student2, $teacher1, $manager) = $this->session_generate_data();

        $emailsink = $this->redirectMessages();
        $signup = \mod_facetoface\signup::create($student1->id, $seminarevent);
        $signup->set_discountcode('XMAS');
        signup_helper::signup($signup);
        $this->executeAdhocTasks();
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertStringContainsString('Cost:', current($emails)->fullmessagehtml);
        // Test normal cost
        $this->assertStringContainsString('$100', current($emails)->fullmessagehtml);
        // Test discount cost
        $this->assertStringNotContainsString('$NZ20', current($emails)->fullmessagehtml);
    }

    private function create_facetoface() {

        $course = $this->createCourse(null, ['createsections' => true]);
        $f2f = $this->createSeminar($course, 'Approval', ['approvaltype' => \mod_facetoface\seminar::APPROVAL_ROLE]);

        $seminarevent = new \mod_facetoface\seminar_event();
        $seminarevent->set_facetoface($f2f->id)->save();

        $time = time() + 3600;
        $seminarsession = new \mod_facetoface\seminar_session();
        $seminarsession->set_sessionid($seminarevent->get_id())
            ->set_timestart($time)
            ->set_timefinish($time + 7200)
            ->save();

        return new \mod_facetoface\seminar($f2f->id);
    }

    /**
     * Check signup_datetime_changed notification in conjunction with the 'facetoface_oneemailperday' setting turned on.
     *
     * Only those dates with relevant changes are expected to have a notification sent.
     */
    public function test_signup_datetime_changed_oneemailperday(): void {
        set_config('facetoface_oneemailperday', true);
        $course = $this->createCourse();
        $user = $this->createUser();
        $this->enrolUser($user, $course);
        $seminar = $this->createSeminar($course, 'f2f');
        $room1 = $this->createSeminarRoom(
            [
                'name' => 'Test Room One',
                'url' => 'https://example.com/test_room_1',
            ],
            [
                'locationaddress' => "Address\nSuburb\nCity",
            ]
        );

        $dates = [
            // Use different timezones for easy distinction when asserting.
            $this->createSeminarDate(WEEKSECS, null, 0, 'Pacific/Tahiti'),
            $this->createSeminarDate(WEEKSECS + DAYSECS * 2, null, 0, 'Pacific/Galapagos'),
            $this->createSeminarDate(WEEKSECS + DAYSECS * 5, null, 0, 'Pacific/Honolulu'),
        ];
        $session = $this->addSeminarSession($seminar, $dates);

        // Trigger the signup_datetime_changed message WITHOUT having changed anything (old dates = new dates).
        $old_dates = $session->sessiondates;
        $signup = \mod_facetoface\signup::create($user->id, new \mod_facetoface\seminar_event($session->id));
        \mod_facetoface\notice_sender::signup_datetime_changed($signup, $old_dates);

        // No notifications expected.
        $this->assert_datetime_changed_notifications_sent($user, []);

        // Set the roomids/facilitatorids properties of old dates to existing values.
        foreach ($old_dates as &$old_date) {
            $old_date->roomids = room_helper::get_room_ids_sorted($old_date->id);
            $old_date->facilitatorids = facilitator_helper::get_facilitator_ids_sorted($old_date->id);
        }
        unset($old_date);
        \mod_facetoface\notice_sender::signup_datetime_changed($signup, $old_dates);

        // Still no notifications expected.
        $this->assert_datetime_changed_notifications_sent($user, []);

        // Set room id for one old date, so a change should be detected.
        $old_dates[0]->roomids = [$room1->id];
        \mod_facetoface\notice_sender::signup_datetime_changed($signup, $old_dates);

        // One notification expected.
        $this->assert_datetime_changed_notifications_sent($user, ['Pacific/Tahiti']);

        // Make sure a change of facilitator also triggers notification.
        $facilitator = $this->createUser();
        $old_dates[1]->facilitatorids = [$facilitator->id];
        \mod_facetoface\notice_sender::signup_datetime_changed($signup, $old_dates);

        // Two notifications expected.
        $this->assert_datetime_changed_notifications_sent($user, ['Pacific/Tahiti', 'Pacific/Galapagos']);

        // Make sure changing time also triggers notification.
        $old_dates[2]->timestart ++;
        \mod_facetoface\notice_sender::signup_datetime_changed($signup, $old_dates);

        // Three notifications expected.
        $this->assert_datetime_changed_notifications_sent($user, ['Pacific/Tahiti', 'Pacific/Galapagos', 'Pacific/Honolulu']);
    }

    /**
     * @param stdClass $recipient
     * @param array $expected_fullmessage_substrings
     */
    private function assert_datetime_changed_notifications_sent(stdClass $recipient, array $expected_fullmessage_substrings): void {
        $email_sink = $this->redirectMessages();
        self::executeAdhocTasks();
        $emails = $email_sink->get_messages();
        self::assertCount(count($expected_fullmessage_substrings), $emails);
        foreach ($emails as $email) {
            self::assertEquals($recipient->id, $email->useridto);
            self::assertStringContainsString('Seminar date/time changed', $email->subject);

            // Make sure it matches exactly one of the expected fullmessage substrings.
            $count_fullmessage_match = 0;
            $found_key = null;
            foreach ($expected_fullmessage_substrings as $key => $expected_substring) {
                if (strpos($email->fullmessage, $expected_substring) !== false) {
                    $count_fullmessage_match ++;
                    $found_key = $key;
                }
            }
            unset($expected_fullmessage_substrings[$found_key]);
            self::assertEquals(1, $count_fullmessage_match);
        }
        $email_sink->clear();
    }
}
