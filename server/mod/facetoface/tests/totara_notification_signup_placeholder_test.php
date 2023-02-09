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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_facetoface
 * @category totara_notification
 */

use core_phpunit\testcase;
use core\testing\generator as core_generator;
use mod_facetoface\seminar_event;
use mod_facetoface\signup;
use mod_facetoface\signup_helper;
use mod_facetoface\testing\generator as f2f_generator;
use mod_facetoface\totara_notification\placeholder\signup as signup_placeholder_group;
use totara_job\job_assignment;
use totara_notification\placeholder\option;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_notification
 */
class mod_facetoface_totara_notification_signup_placeholder_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        signup_placeholder_group::clear_instance_cache();
    }

    protected function tearDown(): void {
        parent::tearDown();
        signup_placeholder_group::clear_instance_cache();
    }

    /**
     * Setup the data
     */
    private function setup_data() {
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
        $learner = $generator->create_user(['username' => 'learner1']);
        $manager = $generator->create_user(['username' => 'manager']);

        $managerja = job_assignment::create_default($manager->id);
        job_assignment::create_default($learner->id, ['managerjaid' => $managerja->id]);
        $course = $generator->create_course();

        $teacher_role = $DB->get_record('role', ['shortname' => 'editingteacher']);
        $learner_role = $DB->get_record('role', ['shortname' => 'student']);

        $generator->enrol_user($teacher->id, $course->id, $teacher_role->id);
        $generator->enrol_user($learner->id, $course->id, $learner_role->id);

        $f2f_data = [
            'course' => $course->id,
            'name' => 'Seminar1',
            'description' => 'First test seminar',
        ];
        $seminar = $f2f_generator->create_instance($f2f_data);

        $data->start = strtotime('+1 week 9am');
        $data->end = strtotime('+1 week 3pm');
        $data->cutoff = strtotime('+5 days 5pm');

        $session_dates = [
            (object)[
                'sessiontimezone' => 'Pacific/Auckland',
                'timestart' => $data->start,
                'timefinish' => $data->end,
                'assetids' => []
            ],
        ];

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
        $this->setUser($learner);
        signup_helper::signup(signup::create($learner->id, new seminar_event($session_id)));

        $data->users = [
            'teacher' => $teacher,
            'learner' => $learner,
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
        }, signup_placeholder_group::get_options());
        self::assertEqualsCanonicalizing(
            [
                'cost',
            ],
            $option_keys,
            'Please add missing placeholders to test coverage.'
        );

        $data = $this->setup_data();

        $placeholder_group = signup_placeholder_group::from_event_id_and_user_id(
            $data->event->get_id(),
            $data->users['learner']->id
        );

        self::assertEquals('$123', $placeholder_group->do_get('cost'));
    }

    public function test_event_placeholders_cost(): void {
        $data = $this->setup_data();

        // Signup another user with a discount code
        $generator = $this->getDataGenerator();
        $discount_learner = $generator->create_user(['username' => 'discount_learner']);
        $generator->enrol_user($discount_learner->id, $data->course->id);

        $this->setUser($discount_learner);
        $session_id = $data->session_ids[0];
        $signup = signup::create($discount_learner->id, new seminar_event($session_id));
        $signup->set_discountcode('nzdisc');
        signup_helper::signup($signup);

        $placeholder_group1 = signup_placeholder_group::from_event_id_and_user_id(
            $data->event->get_id(),
            $data->users['learner']->id
        );
        $placeholder_group2 = signup_placeholder_group::from_event_id_and_user_id(
            $data->event->get_id(),
            $discount_learner->id
        );

        // They each see their own costs.
        self::assertEquals('$123', $placeholder_group1->do_get('cost'));
        self::assertEquals('NZ$45', $placeholder_group2->do_get('cost'));

        // Testing hiding cost information through config.

        // When discount is hidden, they both see the event cost.
        set_config('facetoface_hidediscount', 1);
        self::assertEquals('$123', $placeholder_group1->do_get('cost'));
        self::assertEquals('$123', $placeholder_group2->do_get('cost'));

        // When event cost is hidden, they only see their discount cost.
        set_config('facetoface_hidediscount', null);
        set_config('facetoface_hidecost', 1);
        self::assertEquals('', $placeholder_group1->do_get('cost'));
        self::assertEquals('NZ$45', $placeholder_group2->do_get('cost'));

        // When both are hidden, they see nothing.
        set_config('facetoface_hidediscount', 1);
        self::assertEquals('', $placeholder_group1->do_get('cost'));
        self::assertEquals('', $placeholder_group2->do_get('cost'));
    }
}
