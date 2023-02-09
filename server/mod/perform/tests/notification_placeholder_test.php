<?php
/**
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\constants;
use mod_perform\controllers\activity\user_activities_select_participants;
use mod_perform\controllers\activity\view_user_activity;
use mod_perform\dates\date_offset;
use mod_perform\entity\activity\activity_type;
use mod_perform\expand_task;
use mod_perform\models\activity\activity_type as activity_type_model;
use mod_perform\models\activity\participant_instance;
use mod_perform\notification\factory;
use mod_perform\notification\placeholder;
use totara_core\relationship\relationship;
use totara_job\job_assignment;

require_once(__DIR__ . '/notification_testcase.php');

/**
 * @coversDefaultClass \mod_perform\notification\placeholder
 * @group perform
 * @group perform_notifications
 */
class mod_perform_notification_placeholder_testcase extends mod_perform_notification_testcase {

    /**
     * Create an activity and some subject and participant instances for testing.
     *
     * @param date_offset|null $dateoffset
     * @return array
     */
    private function create_instances(date_offset $dateoffset = null): array {
        $this->setAdminUser();
        $users['user1'] = $this->getDataGenerator()->create_user(['username' => 'user1', 'firstname'=>'User', 'lastname' => 'One']);
        $users['user2'] = $this->getDataGenerator()->create_user(['username' => 'user2', 'firstname'=>'User', 'lastname' => 'Two']);
        $users['manager'] = $this->getDataGenerator()->create_user(['username' => 'manager', 'firstname'=>'Your', 'lastname' => 'Manager']);
        $users['appraiser'] = $this->getDataGenerator()->create_user(['username' => 'appraiser', 'firstname'=>'Our', 'lastname' => 'Appraiser']);

        $managerja = job_assignment::create_default($users['manager']->id);
        job_assignment::create_default($users['user1']->id, ['appraiserid' => $users['appraiser']->id, 'managerjaid' => $managerja->id]);
        job_assignment::create_default($users['user2']->id, ['managerjaid' => $managerja->id]);

        $activity = $this->create_activity();
        $section = $this->create_section($activity);
        $this->create_section_relationships($section);
        $track = $this->create_single_activity_track_and_assignment($activity, [$users['user1']->id, $users['user2']->id], $dateoffset);
        $element = $this->perfgen->create_element(['title' => 'Question one', 'plugin_name' => 'short_text']);
        $this->perfgen->create_section_element($section, $element);

        expand_task::create()->expand_multiple($track->assignments->map(function ($ass) {
            return $ass->id;
        })->all());

        $activity->activate();
        $this->assertTrue($activity->is_active());

        // Use real task to create subject instances.
        $sic = new \mod_perform\task\service\subject_instance_creation();
        $sic->generate_instances();

        $entities = \mod_perform\entity\activity\participant_instance::repository()->get()->map_to(participant_instance::class);
        return [$activity, $users, $entities];
    }

    /**
     * @covers ::from_data
     */
    public function test_from_data() {
        $activity_type = activity_type::repository()->where('name', 'appraisal')->one();
        $relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_SUBJECT);

        $placeholders = placeholder::from_data([
            'recipient_fullname' => 'User One',
            'activity_name' => 'My activity',
            'activity_type' => activity_type_model::load_by_entity($activity_type),
            'subject_fullname' => 'Any One',
            'participant_fullname' => 'Some One',
            'participant_relationship' => $relationship,
            'instance_duedate' => 1672484400,
            'instance_created_at' => 1640948400,
            'instance_days_active' => 3,
            'instance_days_remaining' => 1,
            'instance_days_overdue' => 4,
            'activity_url' => 'http://example.com/kia/ora.php',
            'participant_selection_url' => 'http://example.com/haere/mai.php',
            'conditional_duedate' => placeholder::DUE_DATE_PARTICIPANT,
        ]);
        $this->assertEquals('User One', $placeholders->recipient_fullname);
        $this->assertEquals('My activity', $placeholders->activity_name);
        $this->assertEquals('Appraisal', $placeholders->activity_type);
        $this->assertEquals('Any One', $placeholders->subject_fullname);
        $this->assertEquals('Some One', $placeholders->participant_fullname);
        $this->assertEquals('Subject', $placeholders->participant_relationship);
        $this->assertEquals('31 December 2022', $placeholders->instance_duedate);
        $this->assertStringContainsString('This needs to be completed by 31 December 2022.', $placeholders->conditional_duedate);
        $this->assertEquals(1640948400, $placeholders->instance_created_at);
        $this->assertEquals(3, $placeholders->instance_days_active);
        $this->assertEquals(1, $placeholders->instance_days_remaining);
        $this->assertEquals(4, $placeholders->instance_days_overdue);
        $this->assertEquals('http://example.com/kia/ora.php', $placeholders->activity_url);
        $this->assertEquals('http://example.com/haere/mai.php', $placeholders->participant_selection_url);

        // Tes converting into record
        $record = $placeholders->to_record();
        $record->conditional_duedate = trim($record->conditional_duedate);

        $this->assertEquals(
            [
                'recipient_fullname' => 'User One',
                'activity_name' => 'My activity',
                'activity_type' => 'Appraisal',
                'subject_fullname' => 'Any One',
                'participant_fullname' => 'Some One',
                'participant_relationship' => 'Subject',
                'instance_duedate' => '31 December 2022',
                'conditional_duedate' => 'This needs to be completed by 31 December 2022.',
                'instance_created_at' => 1640948400,
                'instance_days_active' => 3,
                'instance_days_remaining' => 1,
                'instance_days_overdue' => 4,
                'activity_url' => 'http://example.com/kia/ora.php',
                'participant_selection_url' => 'http://example.com/haere/mai.php',
                'activity_link' => '<a href="http://example.com/kia/ora.php">My activity</a>',
                'participant_selection_link' => '<a href="http://example.com/haere/mai.php">Select participants</a>',
            ],
            (array) $record
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('i_do_not_exist is not a valid placeholder name');

        placeholder::from_data(['i_do_not_exist' => 'what?']);
    }

    /**
     * Tests that placeholders are correct for participant instances created without due date.
     *
     * @covers ::from_participant_instance
     */
    public function test_correct_placeholder_values_from_open_participant_instance(): void {
        list($activity, $users, $participant_instances) = $this->create_instances();
        $this->assertCount(5, $participant_instances);
        foreach ($participant_instances as $px => $participant_instance) {
            $placeholders = placeholder::from_participant_instance($participant_instance);
            $participant = $users[$participant_instance->get_participant()->username];
            $subject_instance = $participant_instance->get_subject_instance();

            $pi_url = $participant_instance->get_participation_url();
            $pi_link = html_writer::link($pi_url, $activity->name);
            $ps_url = user_activities_select_participants::get_url();
            $ps_link = html_writer::link($ps_url, get_string('user_activities_select_participants_page_title', 'mod_perform'));

            $this->assertEquals(fullname($participant), $placeholders->recipient_fullname);
            $this->assertEquals($activity->name, $placeholders->activity_name);
            $this->assertEquals($activity->get_type()->get_display_name(), $placeholders->activity_type);
            $this->assertEquals($subject_instance->subject_user->fullname, $placeholders->subject_fullname);
            $this->assertEquals(fullname($participant), $placeholders->participant_fullname);
            $this->assertEquals($participant_instance->get_core_relationship()->get_name(), $placeholders->participant_relationship);
            $this->assertEquals(0, $placeholders->instance_duedate);
            $this->assertEquals('', $placeholders->conditional_duedate);
            $this->assertEquals($subject_instance->created_at, $placeholders->instance_created_at);
            $this->assertEquals(0, $placeholders->instance_days_active);
            $this->assertEquals(0, $placeholders->instance_days_remaining);
            $this->assertEquals(0, $placeholders->instance_days_overdue);
            $this->assertEquals($pi_url, $placeholders->activity_url);
            $this->assertEquals($pi_link, $placeholders->activity_link);
            $this->assertEquals($ps_url, $placeholders->participant_selection_url);
            $this->assertEquals($ps_link, $placeholders->participant_selection_link);
        }
    }

    /**
     * Tests that placeholders are correct for participant instances created with due date set.
     *
     * @covers ::from_participant_instance
     */
    public function test_correct_placeholder_values_from_participant_instance_with_duedate(): void {
        // Expected to be due in two weeks.
        $clock = factory::create_clock();
        $duedate_offset = new date_offset(2, date_offset::UNIT_WEEK, date_offset::DIRECTION_AFTER);
        $duedate = $duedate_offset->apply($clock->get_time());
        $strftimedate = get_string('strftimedate');
        $formatted_duedate = userdate($duedate, $strftimedate);
        $a = new stdClass();
        $a->duedate = $formatted_duedate;
        $conditional_duedate = get_string('conditional_duedate_participant_placeholder', 'mod_perform', $a);
        list($activity, $users, $participant_instances) = $this->create_instances($duedate_offset);
        $this->assertCount(5, $participant_instances);

        $check = function ($active, $remaining, $overdue) use ($participant_instances, $formatted_duedate, $conditional_duedate) {
            foreach ($participant_instances as $px => $participant_instance) {
                $placeholders = placeholder::from_participant_instance($participant_instance);
                $this->assertEquals($formatted_duedate, $placeholders->instance_duedate);
                $this->assertEquals($conditional_duedate, $placeholders->conditional_duedate);
                $this->assertEquals($active, $placeholders->instance_days_active);
                $this->assertEquals($remaining, $placeholders->instance_days_remaining);
                $this->assertEquals($overdue, $placeholders->instance_days_overdue);
            }
        };

        $check(0, 14, 0);
        $clock = factory::create_clock_with_time_offset(5 * DAYSECS);
        $check(5, 9, 0);
        $clock = factory::create_clock_with_time_offset(12 * DAYSECS);
        $check(17, 0, 3);
    }

    /**
     * Tests that placehoders are correct when created from subject instance without a due date.
     *
     * @covers ::from_subject_instance
     * @covers ::set_participant
     */
    public function test_correct_placeholder_values_from_subject_instance(): void {
        list($activity, $users, $participant_instances) = $this->create_instances();
        $this->assertCount(5, $participant_instances);
        $subject_instance = $participant_instances->first()->get_subject_instance();
        $placeholders = placeholder::from_subject_instance($subject_instance);
        $subject = $users[$subject_instance->get_subject_user()->username];

        $si_url = view_user_activity::get_url();
        $si_link = html_writer::link($si_url, $activity->name);
        $ps_url = user_activities_select_participants::get_url();
        $ps_link = html_writer::link($ps_url, get_string('user_activities_select_participants_page_title', 'mod_perform'));

        $this->assertEquals(fullname($subject), $placeholders->recipient_fullname);
        $this->assertEquals($activity->name, $placeholders->activity_name);
        $this->assertEquals($activity->get_type()->get_display_name(), $placeholders->activity_type);
        $this->assertEquals($subject_instance->subject_user->fullname, $placeholders->subject_fullname);
        $this->assertEquals(fullname($subject), $placeholders->participant_fullname);
        $this->assertEquals('Subject', $placeholders->participant_relationship);
        $this->assertEquals(0, $placeholders->instance_duedate);
        $this->assertEquals('', $placeholders->conditional_duedate);
        $this->assertEquals($subject_instance->created_at, $placeholders->instance_created_at);
        $this->assertEquals(0, $placeholders->instance_days_active);
        $this->assertEquals(0, $placeholders->instance_days_remaining);
        $this->assertEquals(0, $placeholders->instance_days_overdue);
        $this->assertEquals($si_url, $placeholders->activity_url);
        $this->assertEquals($si_link, $placeholders->activity_link);
        $this->assertEquals($ps_url, $placeholders->participant_selection_url);
        $this->assertEquals($ps_link, $placeholders->participant_selection_link);

        // Also test updating the placeholders with a participant and relationship.
        foreach ($participant_instances as $px => $participant_instance) {
            $participant = $users[$participant_instance->get_participant()->username];
            $placeholders->set_participant($participant_instance->get_participant()->get_user(), $participant_instance->get_core_relationship());
            $this->assertEquals(fullname($participant), $placeholders->recipient_fullname);
            $this->assertEquals($activity->name, $placeholders->activity_name);
            $this->assertEquals($activity->get_type()->get_display_name(), $placeholders->activity_type);
            $this->assertEquals($subject_instance->subject_user->fullname, $placeholders->subject_fullname);
            $this->assertEquals(fullname($participant), $placeholders->participant_fullname);
            $this->assertEquals($participant_instance->get_core_relationship()->get_name(), $placeholders->participant_relationship);
            $this->assertEquals(0, $placeholders->instance_duedate);
            $this->assertEquals('', $placeholders->conditional_duedate);
            $this->assertEquals($subject_instance->created_at, $placeholders->instance_created_at);
            $this->assertEquals(0, $placeholders->instance_days_active);
            $this->assertEquals(0, $placeholders->instance_days_remaining);
            $this->assertEquals(0, $placeholders->instance_days_overdue);
            // Note that placeholders created from a subject instance do not have link to a specific participant instance.
            $this->assertEquals($si_url, $placeholders->activity_url);
            $this->assertEquals($si_link, $placeholders->activity_link);
            $this->assertEquals($ps_url, $placeholders->participant_selection_url);
            $this->assertEquals($ps_link, $placeholders->participant_selection_link);
        }
    }

    /**
     * Tests that placeholders are correct when created from a subject instance with a due date set.
     *
     * @covers ::from_subject_instance
     * @covers ::set_participant
     */
    public function test_correct_placeholder_values_from_subject_instance_with_duedate(): void {
        // Expected to be due in two weeks.
        $clock = factory::create_clock();
        $duedate_offset = new date_offset(2, date_offset::UNIT_WEEK, date_offset::DIRECTION_AFTER);
        $duedate = $duedate_offset->apply($clock->get_time());
        $strftimedate = get_string('strftimedate');
        $formatted_duedate = userdate($duedate, $strftimedate);
        $a = new stdClass();
        $a->duedate = $formatted_duedate;
        $conditional_duedate = get_string('conditional_duedate_subject_placeholder', 'mod_perform', $a);
        list($activity, $users, $participant_instances) = $this->create_instances($duedate_offset);
        $this->assertCount(5, $participant_instances);
        $subject_instance = $participant_instances->first()->get_subject_instance();

        $check = function ($active, $remaining, $overdue) use ($subject_instance, $participant_instances, $formatted_duedate, $conditional_duedate) {
            $placeholders = placeholder::from_subject_instance($subject_instance);
            $this->assertEquals($formatted_duedate, $placeholders->instance_duedate);
            $this->assertEquals($conditional_duedate, $placeholders->conditional_duedate);
            $this->assertEquals($active, $placeholders->instance_days_active);
            $this->assertEquals($remaining, $placeholders->instance_days_remaining);
            $this->assertEquals($overdue, $placeholders->instance_days_overdue);
            // Also test updating the placeholders with a participant and relationship.
            foreach ($participant_instances as $px => $participant_instance) {
                $placeholders->set_participant($participant_instance->get_participant()->get_user(), $participant_instance->get_core_relationship());
                $this->assertEquals($formatted_duedate, $placeholders->instance_duedate);
                $this->assertEquals($conditional_duedate, $placeholders->conditional_duedate);
                $this->assertEquals($active, $placeholders->instance_days_active);
                $this->assertEquals($remaining, $placeholders->instance_days_remaining);
                $this->assertEquals($overdue, $placeholders->instance_days_overdue);
            }
        };

        $check(0, 14, 0);
        $clock = factory::create_clock_with_time_offset(5 * DAYSECS);
        $check(5, 9, 0);
        $clock = factory::create_clock_with_time_offset(12 * DAYSECS);
        $check(17, 0, 3);
    }

    /**
     * Tests the placeholder::format_duration method.
     *
     * @covers ::format_duration
     */
    public function test_placeholder_duration_in_days_formatter(): void {
        $now = time();
        $tests = [
            0 => 0,
            HOURSECS => 0,
            ((8 * HOURSECS) - 10) => 0,
            (8 * HOURSECS) => 1,
            DAYSECS => 1,
            (DAYSECS + HOURSECS) => 1,
            (DAYSECS + (8 * HOURSECS)) => 2,
            (DAYSECS * 3) => 3,
            WEEKSECS => 7
        ];
        foreach ($tests as $delta => $days) {
            $this->assertEquals($days, placeholder::format_duration($now, $now + $delta));
        }
    }
}
