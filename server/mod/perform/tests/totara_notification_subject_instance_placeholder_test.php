<?php
/**
 * This file is part of Totara Perform
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Ben Fesili <ben.fesili@totaralearning.com>
 * @package mod_perform
 */

use core_phpunit\testcase;
use mod_perform\controllers\activity\user_activities_select_participants;
use mod_perform\models\activity\subject_instance;
use mod_perform\notification\factory;
use mod_perform\testing\generator;
use mod_perform\totara_notification\placeholder\subject_instance as subject_instance_placeholder;
use totara_notification\placeholder\option;
use mod_perform\controllers\activity\view_user_activity;

defined('MOODLE_INTERNAL') || die();

/**
 * @group mod_perform
 * @group totara_notification
 */
class mod_perform_totara_notification_subject_instance_placeholder_testcase extends testcase {
    /**
     * @return void
     * @throws coding_exception
     */
    public function test_placeholder(): void {
        $subject_instance = $this->create_data();
        $placeholder = subject_instance_placeholder::from_id($subject_instance->id);
        $option_keys = array_map(static function (option $option) {
            return $option->get_key();
        }, $placeholder::get_options());
        self::assertEqualsCanonicalizing([
            'days_remaining',
            'days_active',
            'duedate',
            'conditional_duedate',
            'fullname',
            'created_at',
            'days_overdue',
            'activity_url',
            'participant_selection_url',
            'participant_selection_link',
            'activity_name_link',
            'recipient_relationship',
        ], $option_keys);

        self::assertEquals(5, $placeholder->do_get('days_remaining'));

        self::assertEquals(0, $placeholder->do_get('days_active'));

        $duedate = factory::create_clock()->get_time() + (5 * DAYSECS);
        $user_date = userdate($duedate, get_string('strftimedate'));
        self::assertEquals($user_date, $placeholder->do_get('duedate'));

        $a = new \stdClass();
        $a->duedate = $user_date;
        self::assertEquals(
            get_string('conditional_duedate_subject_placeholder', 'mod_perform', $a),
            $placeholder->do_get('conditional_duedate')
        );

        self::assertEquals($subject_instance->get_subject_user()->fullname, $placeholder->do_get('fullname'));
        self::assertEquals(userdate($subject_instance->created_at, get_string('strftimedate')), $placeholder->do_get('created_at'));
        self::assertEquals(0, $placeholder->do_get('days_overdue'));
        self::assertEquals(\mod_perform\controllers\activity\view_user_activity::get_url(), $placeholder->do_get('activity_url'));
        $activity_url = new moodle_url(\mod_perform\controllers\activity\user_activities::get_base_url());
        $link = html_writer::link($activity_url, $subject_instance->get_activity()->name);
        self::assertEquals($link, $placeholder->do_get('activity_name_link'));
        $url = user_activities_select_participants::get_url();
        $link = html_writer::link($url, get_string('user_activities_select_participants_page_title', 'mod_perform'));
        self::assertEquals($link, $placeholder->do_get('participant_selection_link'));
        self::assertEquals($url, $placeholder->do_get('participant_selection_url'));
        self::assertEquals('Relationship missing', $placeholder->do_get('recipient_relationship'));
    }

    public function test_activity_name_link_placeholder() {
        // setup the data
        $participant_instance = new \mod_perform\entity\activity\participant_instance();
        $perform_generator = \mod_perform\testing\generator::instance();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        $activity = $perform_generator->create_activity_in_container();
        // Participant section
        $subject_instance = $perform_generator->create_subject_instance(
            [
                'activity_id'       => $activity->get_id(),
                'subject_user_id'   => $user1->id,
                'include_questions' => false,
            ]
        );
        $participant_instance = $perform_generator->create_participant_instance(
            $user2, $subject_instance->id, 0
        );
        $subject_instance_placeholder = subject_instance_placeholder::from_id($subject_instance->id);
        $subject_instance_placeholder->set_recipient_id($user2->id);

        // run the placeholder
        $activity_url = $subject_instance_placeholder->do_get('activity_name_link');
        $expected_url = view_user_activity::get_url(['participant_instance_id' => $participant_instance->id]);
        $expected_url = html_writer::link($expected_url, $activity->name);
        $this->assertSame($expected_url, $activity_url);
    }

    /**
     * @return subject_instance
     * @throws coding_exception
     */
    private function create_data(): subject_instance {
        self::setAdminUser();

        $generator = generator::instance();

        $duedate = factory::create_clock()->get_time() + (5 * DAYSECS);
        $user = self::getDataGenerator()->create_user(['firstname' => 'Bruce', 'lastname' => 'Wayne']);

        $subject_instance_entity = $generator->create_subject_instance(
            [
                'due_date' => $duedate,
                'subject_user_id' => $user->id,
            ]
        );

        $subject_instance = subject_instance::load_by_entity($subject_instance_entity);

        return $subject_instance;
    }

    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        self::setAdminUser();
    }
}
