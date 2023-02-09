<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package mod_facetoface
 * @category totara_notification
 */

use core_phpunit\testcase;
use core\testing\generator as core_generator;
use mod_facetoface\seminar_event;
use mod_facetoface\seminar_session;
use mod_facetoface\signup;
use mod_facetoface\signup_helper;
use mod_facetoface\testing\generator as f2f_generator;
use mod_facetoface\totara_notification\placeholder\event as event_placeholder_group;
use totara_notification\placeholder\option;
use totara_job\job_assignment;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_notification
 */
class mod_facetoface_totara_notification_event_placeholder_testcase extends testcase {
    /**
     * Set up the data
     *
     * @param array|null $session_dates array of session date objects
     */
    private function setup_data(array $session_dates = null) {
        global $DB;

        $data = new class() {
            public $users = [];
            public $course;
            public $seminar;
            /** @var seminar_event $event */
            public $event;
            public $session_ids = [];
            public $start;
            public $end;
            public $cutoff;
        };

        $generator = core_generator::instance();
        $f2f_generator = f2f_generator::instance();

        $teacher = $generator->create_user(['username' => 'teacher']);
        $learner1 = $generator->create_user(['username' => 'learner1']);
        $learner2 = $generator->create_user(['username' => 'learner2']);
        $manager = $generator->create_user(['username' => 'manager']);

        $managerja = job_assignment::create_default($manager->id);
        job_assignment::create_default($learner1->id, ['managerjaid' => $managerja->id]);
        job_assignment::create_default($learner2->id, ['managerjaid' => $managerja->id]);
        $course = $generator->create_course();

        $teacher_role = $DB->get_record('role', ['shortname' => 'editingteacher']);
        $learner_role = $DB->get_record('role', ['shortname' => 'student']);

        $generator->enrol_user($teacher->id, $course->id, $teacher_role->id);
        $generator->enrol_user($learner1->id, $course->id, $learner_role->id);
        $generator->enrol_user($learner2->id, $course->id, $learner_role->id);

        $f2f_data = [
            'course' => $course->id,
            'name' => 'Seminar1',
            'description' => 'First test seminar',
        ];
        $seminar = $f2f_generator->create_instance($f2f_data);

        $data->start = strtotime('+1 week 9am');
        $data->end = strtotime('+1 week 3pm');
        $data->cutoff = strtotime('+5 days 5pm');

        if (!$session_dates) {
            $session_dates = [
                (object)[
                    'sessiontimezone' => 'Pacific/Auckland',
                    'timestart' => $data->start,
                    'timefinish' => $data->end,
                    'assetids' => [],
                ]
            ];
        }

        $session_data = [
            'facetoface' => $seminar->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => $session_dates,
            'mincapacity' => '1',
            'cutoff' => $data->cutoff,
            'normalcost' => '$123',
            'discountcost' => 'NZ$45',
            'details' => 'Some or other event details',
        ];

        $session_id = $f2f_generator->add_session($session_data);
        $event = new seminar_event($session_id);
        $session = $event->to_record();
        $session->mintimestart = $event->get_mintimestart();
        $session->sessiondates = $event->get_sessions()->sort('timestart')->to_records(false);

        // Signup user1.
        $this->setUser($learner1);
        signup_helper::signup(signup::create($learner1->id, new seminar_event($session_id)));
        $this->setUser($learner2);
        signup_helper::signup(signup::create($learner2->id, new seminar_event($session_id)));

        $data->users = [
            'teacher' => $teacher,
            'learner1' => $learner1,
            'learner2' => $learner2,
            'manager' => $manager,
        ];
        $data->course = $course;
        $data->event = $event;
        $data->session_ids = [$session_id];

        return $data;
    }


    public function test_event_placeholders(): void {
        // Make devs aware they should extend this test when adding placeholders.
        $option_keys = array_map(static function (option $option) {
            return $option->get_key();
        }, event_placeholder_group::get_options());
        self::assertEqualsCanonicalizing(
            [
                'all_sessions',
                'attendees_link',
                'booked',
                'capacity',
                'cost',
                'details',
                'duration',
                'event_page_link',
                'finish_date',
                'finish_time',
                'latest_finish_date',
                'latest_finish_time',
                'latest_start_date',
                'latest_start_time',
                'minimum_capacity',
                'registration_cutoff',
                'reminder_period',
                'session_date',
                'session_role',
                'start_date',
                'start_time',
            ],
            $option_keys,
            'Please add missing placeholders to test coverage.'
        );

        $data = $this->setup_data();
        $strftimedate = get_string('strftimedate');
        $strftimetime = get_string('strftimetime');
        $start_date_string = userdate($data->start, $strftimedate, 'Pacific/Auckland');
        $start_time_string = userdate($data->start, $strftimetime, 'Pacific/Auckland');
        $end_date_string = userdate($data->end, $strftimedate, 'Pacific/Auckland');
        $end_time_string = userdate($data->end, $strftimetime, 'Pacific/Auckland');

        $placeholder_group1 = event_placeholder_group::from_event_id($data->event->get_id());
        self::assertEquals(
            '<a title="Attendees" href="https://www.example.com/moodle/mod/facetoface/attendees/approvalrequired.php?s=' .
            $data->event->get_id() . '">https://www.example.com/moodle/mod/facetoface/attendees/approvalrequired.php?s=' .
            $data->event->get_id() . '</a>',
            $placeholder_group1->do_get('attendees_link')
        );

        self::assertEquals(2, $placeholder_group1->do_get('booked'));
        self::assertEquals(3, $placeholder_group1->do_get('capacity'));
        self::assertEquals('$123', $placeholder_group1->do_get('cost'));
        self::assertEquals('Some or other event details', $placeholder_group1->do_get('details'));
        self::assertEquals('6 hours', $placeholder_group1->do_get('duration'));
        self::assertEquals(
            '<a href="https://www.example.com/moodle/mod/facetoface/eventinfo.php?s=' .
            $data->event->get_id() . '">https://www.example.com/moodle/mod/facetoface/eventinfo.php?s=' .
            $data->event->get_id() . '</a>',
            $placeholder_group1->do_get('event_page_link')
        );

        self::assertEquals($end_date_string, $placeholder_group1->do_get('finish_date'));
        self::assertEquals($end_time_string, $placeholder_group1->do_get('finish_time'));
        self::assertEquals($end_date_string, $placeholder_group1->do_get('latest_finish_date'));
        self::assertEquals($end_time_string, $placeholder_group1->do_get('latest_finish_time'));
        self::assertEquals($start_date_string, $placeholder_group1->do_get('latest_start_date'));
        self::assertEquals($start_time_string, $placeholder_group1->do_get('latest_start_time'));

        self::assertEquals(1, $placeholder_group1->do_get('minimum_capacity'));
        self::assertEquals(
            userdate($data->start, get_string('strftimerecent'), 'Pacific/Auckland'),
            $placeholder_group1->do_get('registration_cutoff')
        );
        self::assertEquals(2, $placeholder_group1->do_get('reminder_period'));

        // Single day event
        self::assertEquals($start_date_string, $placeholder_group1->do_get('session_date'));

        self::assertEmpty($placeholder_group1->do_get('session_role'));
        self::assertEquals($start_date_string, $placeholder_group1->do_get('start_date'));
        self::assertEquals($start_time_string, $placeholder_group1->do_get('start_time'));
    }

    public function test_event_placeholders_deleted_session(): void {
        // Create the seminar with a session, sign users up and then remove the session
        $data = $this->setup_data();
        $placeholder_group1 = event_placeholder_group::from_event_id($data->event->get_id());
        $data->event->delete();

        $unknown_date = get_string('unknowndate', 'facetoface');
        $unknown_time = get_string('unknowntime', 'facetoface');

        self::assertEquals(
            '<a title="Attendees" href="https://www.example.com/moodle/mod/facetoface/attendees/approvalrequired.php?s=' .
            $data->session_ids[0] . '">https://www.example.com/moodle/mod/facetoface/attendees/approvalrequired.php?s=' .
            $data->session_ids[0] . '</a>',
            $placeholder_group1->do_get('attendees_link')
        );

        self::assertEquals(0, $placeholder_group1->do_get('booked'));
        self::assertEquals(3, $placeholder_group1->do_get('capacity'));
        self::assertEquals('$123', $placeholder_group1->do_get('cost'));
        self::assertEquals('Some or other event details', $placeholder_group1->do_get('details'));
        self::assertEquals('', $placeholder_group1->do_get('duration'));
        self::assertEquals(
            '<a href="https://www.example.com/moodle/mod/facetoface/eventinfo.php?s=' .
            $data->session_ids[0] . '">https://www.example.com/moodle/mod/facetoface/eventinfo.php?s=' .
            $data->session_ids[0] . '</a>',
            $placeholder_group1->do_get('event_page_link')
        );

        self::assertEquals($unknown_date, $placeholder_group1->do_get('finish_date'));
        self::assertEquals($unknown_time, $placeholder_group1->do_get('finish_time'));
        self::assertEquals($unknown_date, $placeholder_group1->do_get('latest_finish_date'));
        self::assertEquals($unknown_time, $placeholder_group1->do_get('latest_finish_time'));
        self::assertEquals($unknown_date, $placeholder_group1->do_get('latest_start_date'));
        self::assertEquals($unknown_time, $placeholder_group1->do_get('latest_start_time'));

        self::assertEquals(1, $placeholder_group1->do_get('minimum_capacity'));
        self::assertEquals($unknown_date, $placeholder_group1->do_get('registration_cutoff'));
        self::assertEquals(2, $placeholder_group1->do_get('reminder_period'));

        // Single day event
        self::assertEquals($unknown_date, $placeholder_group1->do_get('session_date'));

        self::assertEmpty($placeholder_group1->do_get('session_role'));
        self::assertEquals($unknown_date, $placeholder_group1->do_get('start_date'));
        self::assertEquals($unknown_time, $placeholder_group1->do_get('start_time'));
    }

    public function test_event_placeholders_cost(): void {
        $data = $this->setup_data();

        // Signup another user with a discount code
        $generator = core_generator::instance();
        $discount_learner = $generator->create_user(['username' => 'discount_learner']);
        $generator->enrol_user($discount_learner->id, $data->course->id);

        $session_id = $data->session_ids[0];
        $signup = signup::create($discount_learner->id, new seminar_event($session_id));
        $signup->set_discountcode('nzdisc');
        signup_helper::signup($signup);

        $placeholder_group = event_placeholder_group::from_event_id($data->event->get_id());

        // Admin sees cost of event.
        self::assertEquals('$123', $placeholder_group->do_get('cost'));

        // User sees cost of event, even if they have a specific cost in their signup.
        $this->setUser($discount_learner);
        self::assertEquals('$123', $placeholder_group->do_get('cost'));

        // Testing hiding cost information through config.

        // If discount is hidden, user can still see cost.
        set_config('facetoface_hidediscount', 1);
        self::assertEquals('$123', $placeholder_group->do_get('cost'));

        // If cost is hidden, user CANNOT see cost.
        set_config('facetoface_hidediscount', null);
        set_config('facetoface_hidecost', 1);
        self::assertEquals('', $placeholder_group->do_get('cost'));

        // If both are hidden, cust cannot see cost.
        set_config('facetoface_hidediscount', 1);
        self::assertEquals('', $placeholder_group->do_get('cost'));
    }

    public function test_event_placeholders_registration_finish(): void {
        $data = $this->setup_data();

        $finish = strtotime('+2 days 8am');
        $finish_string = userdate($finish, get_string('strftimerecent'), 'Pacific/Auckland');
        $data->event->set_registrationtimefinish($finish)->save();

        $placeholder_group = event_placeholder_group::from_event_id($data->event->get_id());
        self::assertEquals($finish_string, $placeholder_group->do_get('registration_cutoff'));
    }

    /**
     * @dataProvider reminder_period_data_provider
     */
    public function test_event_placeholders_reminder_period($unit, $amount, $expected): void {
        global $USER;

        $data = $this->setup_data();

        $this->setAdminUser();

        $notification = new facetoface_notification();
        $notification->type = MDL_F2F_NOTIFICATION_SCHEDULED;
        $notification->title = 'Test notification';
        $notification->body = 'Test notification body';
        $notification->courseid = $data->course->id;
        $notification->facetofaceid = $data->event->get_seminar()->get_id();
        $notification->timemodified = time();
        $notification->usermodified = $USER->id;
        $notification->conditiontype = MDL_F2F_CONDITION_BEFORE_SESSION;
        $notification->scheduleunit = $unit;
        $notification->scheduleamount = $amount;
        $notification->status = 1;
        $notification->save();

        $placeholder_group = event_placeholder_group::from_event_id($data->event->get_id());
        self::assertEquals($expected, $placeholder_group->do_get('reminder_period'));
    }

    public function test_event_placeholders_registration_session_date(): void {
        $data = $this->setup_data();

        /** @var seminar_session $session */
        $session = $data->event->get_sessions()->get_first();
        $new_start = strtotime('+7 days 9am');
        $new_end = strtotime('+9 days 3pm');
        $session->set_timestart($new_start);
        $session->set_timefinish($new_end);
        $session->save();

        $placeholder_group = event_placeholder_group::from_event_id($data->event->get_id());

        $strftimedate = get_string('strftimedate');
        $start_string = userdate($new_start, $strftimedate, 'Pacific/Auckland');
        $end_string = userdate($new_end, $strftimedate, 'Pacific/Auckland');
        $expected = $start_string . ' - ' . $end_string;
        self::assertEquals($expected, $placeholder_group->do_get('session_date'));
    }

    public function test_event_placeholders_all_sessions(): void {
        $start1 = strtotime('+1 week 9am');
        $end1 = strtotime('+1 week 3pm');
        $start2 = strtotime('+2 week 9am');
        $end2 = strtotime('+2 week 3pm');

        // Create a room to add to a session date.
        $room = $this->getDataGenerator()->get_plugin_generator('mod_facetoface')->add_site_wide_room(['name' => 'Room 1']);

        $session_dates = [
            (object)[
                'sessiontimezone' => 'Pacific/Auckland',
                'timestart' => $start1,
                'timefinish' => $end1,
                'assetids' => [],
                'roomids' => [$room->id],
            ],
            (object)[
                'sessiontimezone' => 'Pacific/Auckland',
                'timestart' => $start2,
                'timefinish' => $end2,
                'assetids' => [],
                'roomids' => [],
            ]
        ];

        $data = $this->setup_data($session_dates);

        $placeholder_group = event_placeholder_group::from_event_id($data->event->get_id());

        $strftimedate = get_string('strftimedate');
        $start_string1 = userdate($start1, $strftimedate, 'Pacific/Auckland');
        $start_string2 = userdate($start2, $strftimedate, 'Pacific/Auckland');

        $strftimetime = get_string('strftimetime');
        $starttime_string1 = userdate($start1, $strftimetime, 'Pacific/Auckland');
        $starttime_string2 = userdate($start2, $strftimetime, 'Pacific/Auckland');

        $end_string1 = userdate($end1, $strftimedate, 'Pacific/Auckland');
        $end_string2 = userdate($end2, $strftimedate, 'Pacific/Auckland');

        $endtime_string1 = userdate($end1, $strftimetime, 'Pacific/Auckland');
        $endtime_string2 = userdate($end2, $strftimetime, 'Pacific/Auckland');


        $tz = core_date::get_user_timezone('Pacific/Auckland');

        $expected_date = $start_string1 . ', ' . $starttime_string1 . ' - ' . $end_string1 . ', ' . $endtime_string1 . ' ' . $tz;
        $expected_date2 = $start_string2 . ', ' . $starttime_string2 . ' - ' . $end_string2 . ', ' . $endtime_string2 . ' ' . $tz;

        $expected = $expected_date . "Duration: 6 hoursRoom: Room 1" . $expected_date2 . "Duration: 6 hours";

        $expected = strip_tags(preg_replace("/\r|\n/", "", $expected));
        $actual = strip_tags(preg_replace("/\r|\n/", "", $placeholder_group->do_get('all_sessions')));

        self::assertEquals($expected, $actual);
    }

    public function test_event_placeholder_instances_are_cached(): void {
        global $DB;

        $data = $this->setup_data();

        $query_count = $DB->perf_get_reads();

        // One query is executed when we cache the data.
        event_placeholder_group::from_event_id($data->event->get_id());
        self::assertEquals($query_count + 1, $DB->perf_get_reads());

        // No extra queries when we load the same event.
        event_placeholder_group::from_event_id($data->event->get_id());
        self::assertEquals($query_count + 1, $DB->perf_get_reads());
    }


    public function reminder_period_data_provider(): array {
        // Using the defines directly results in string (e.g. "MDL_F2F_SCHEDULE_UNIT_HOUR") instead of the value being passed ??!!

        return [
            [
                'unit' => 1,        // MDL_F2F_SCHEDULE_UNIT_HOUR,
                'amount' => 80,
                'expected' => 3,
            ],
            [
                'unit' => 2,        // MDL_F2F_SCHEDULE_UNIT_DAY,
                'amount' => 4,
                'expected' => 4,
            ],
            [
                'unit' => 4,        // MDL_F2F_SCHEDULE_UNIT_WEEK,
                'amount' => 2,
                'expected' => 14,
            ],
            [
                'unit' => 5,        // MDL_F2F_SCHEDULE_UNIT_MONTH,
                'amount' => 2,
                'expected' => 60,
            ],
            [
                'unit' => 6,        // MDL_F2F_SCHEDULE_UNIT_WEEK,
                'amount' => 1,
                'expected' => 365,
            ],
        ];
    }

}
