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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

use core\collection;
use core\entity\user;
use core\orm\entity\entity;
use mod_perform\entity\activity\activity as activity_entity;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\participant_instance;
use mod_perform\models\response\participant_section;
use mod_perform\models\response\section_element_response;
use mod_perform\state\invalid_state_switch_exception;
use mod_perform\state\participant_instance\complete as complete_instance;
use mod_perform\state\participant_instance\condition\all_sections_complete;
use mod_perform\state\participant_instance\condition\at_least_one_section_started;
use mod_perform\state\participant_instance\condition\not_all_sections_complete;
use mod_perform\state\participant_instance\in_progress as in_progress_instance;
use mod_perform\state\participant_instance\not_started as not_started_instance;
use mod_perform\state\participant_instance\not_submitted as not_submitted_instance;
use mod_perform\state\participant_instance\participant_instance_progress;
use mod_perform\state\participant_instance\progress_not_applicable as progress_not_applicable_instance;
use mod_perform\state\participant_section\complete as complete_section;
use mod_perform\state\participant_section\in_progress as in_progress_section;
use mod_perform\state\participant_section\not_started as not_started_section;
use mod_perform\state\state_helper;

require_once(__DIR__ . '/state_testcase.php');

/**
 * @group perform
 */
class mod_perform_participant_instance_progress_testcase extends state_testcase {

    protected static function get_object_type(): string {
        return 'participant_instance';
    }

    public function condition_all_sections_data_provider(): array {
        $n = not_started_section::get_code();
        $i = in_progress_section::get_code();
        $c = complete_section::get_code();
        return [
            [$n, $n, false],
            [$n, $c, false],
            [$i, $i, false],
            [$c, $i, false],
            [$c, $c, true],
        ];
    }

    /**
     * @dataProvider condition_all_sections_data_provider
     * @param $section1_progress
     * @param $section2_progress
     * @param $expected_result
     */
    public function test_condition_all_sections_complete($section1_progress, $section2_progress, $expected_result) {
        $section1 = (object)['progress' => $section1_progress];
        $section2 = (object)['progress' => $section2_progress];

        $mock = $this->getMockBuilder(participant_instance::class)
            ->onlyMethods(['__get'])
            ->disableOriginalConstructor()
            ->getMock();

        $mock->expects($this->exactly(2))
            ->method('__get')
            ->with('participant_sections')
            ->willReturn(new collection([$section1, $section2]));

        $all_sections_complete = new all_sections_complete($mock);
        $this->assertEquals($expected_result, $all_sections_complete->pass());

        $not_all_sections_complete = new not_all_sections_complete($mock);
        $this->assertEquals(!$expected_result, $not_all_sections_complete->pass());
    }

    public function at_least_one_section_started_data_provider() {
        $n = not_started_section::get_code();
        $i = in_progress_section::get_code();
        $c = complete_section::get_code();
        return [
            [$n, $n, false],
            [$n, $i, true],
            [$n, $c, true],
            [$i, $c, true],
            [$c, $c, true],
        ];
    }

    /**
     * @dataProvider at_least_one_section_started_data_provider
     * @param $section1_progress
     * @param $section2_progress
     * @param $expected_result
     */
    public function test_condition_at_least_one_section_started($section1_progress, $section2_progress, $expected_result) {
        $section1 = (object)['progress' => $section1_progress];
        $section2 = (object)['progress' => $section2_progress];

        $mock = $this->getMockBuilder(participant_instance::class)
            ->onlyMethods(['__get'])
            ->disableOriginalConstructor()
            ->getMock();

        $mock->expects($this->once())
            ->method('__get')
            ->with('participant_sections')
            ->willReturn(new collection([$section1, $section2]));

        $at_least_one_section_started = new at_least_one_section_started($mock);
        $this->assertEquals($expected_result, $at_least_one_section_started->pass());
    }


    /**
     * Check interactions between section and instance states.
     */
    public function test_section_switch_triggers_instance_switch(): void {
        $this->setAdminUser();

        $configuration = \mod_perform\testing\activity_generator_configuration::new()
            ->set_number_of_sections_per_activity(2)
            ->set_number_of_users_per_user_group_type(2)
            ->set_number_of_elements_per_section(1);

        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();
        $generator->create_full_activities($configuration);
        $participant_instances = participant_instance_entity::repository()->get()->all();
        $this->assertCount(2, $participant_instances);
        $participant_instance1 = $participant_instances[0];
        $participant_instance2 = $participant_instances[1];
        /** @var user $subject_user1 */
        $subject_user1 = user::repository()->find($participant_instance1->participant_id);
        /** @var user $subject_user2 */
        $subject_user2 = user::repository()->find($participant_instance2->participant_id);

        // We have two sections and two participant_instances. We only progress for one participant_instance
        // and check that the other one is not affected.
        $participant_sections = $participant_instance1->participant_sections->all();
        $this->assertCount(2, $participant_sections);
        $participant_section1_entity = $participant_sections[0];
        $participant_section2_entity = $participant_sections[1];

        // Make sure we actually have two different users for the two instances.
        $this->assertNotEquals($subject_user1->id, $subject_user2->id);
        $this->assert_participant_instance_progress([
            $participant_instance1->id => not_started_instance::get_code(),
            $participant_instance2->id => not_started_instance::get_code(),
        ]);

        $participant_section1 = participant_section::load_by_entity($participant_section1_entity);
        $participant_section2 = participant_section::load_by_entity($participant_section2_entity);

        $this->setUser($subject_user1->to_record());

        // Progress section1 to in_progress - instance is expected to be in_progress as a result.
        $participant_section1->switch_state(in_progress_section::class);
        $this->assert_participant_instance_progress([
            $participant_instance1->id => in_progress_instance::get_code(),
            $participant_instance2->id => not_started_instance::get_code(),
        ]);

        // Progress section1 to complete - no change to instance (still in_progress).
        $this->mark_answers_complete($participant_section1);
        $participant_section1->switch_state(complete_section::class);
        $this->assert_participant_instance_progress([
            $participant_instance1->id => in_progress_instance::get_code(),
            $participant_instance2->id => not_started_instance::get_code(),
        ]);

        // Progress section2 to in_progress - no change to instance (still in_progress).
        $participant_section2->switch_state(in_progress_section::class);
        $this->assert_participant_instance_progress([
            $participant_instance1->id => in_progress_instance::get_code(),
            $participant_instance2->id => not_started_instance::get_code(),
        ]);

        // Progress section2 to complete - instance should progress to complete.
        $this->mark_answers_complete($participant_section2);
        $participant_section2->switch_state(complete_section::class);
        $this->assert_participant_instance_progress([
            $participant_instance1->id => complete_instance::get_code(),
            $participant_instance2->id => not_started_instance::get_code(),
        ]);

        // Add a new (not_started) participant section to the participant instance.
        $generator->create_participant_section(
            $participant_section2->section->get_activity(),
            $participant_instance1,
            false
        );
        $participant_instance1_model = participant_instance::load_by_id($participant_instance1->id);
        // Adding a new section doesn't trigger any event (yet), so we call the update method manually.
        $participant_instance1_model->update_progress_status();
        // Participant instance should be regressed back to "in_progress".
        $this->assert_participant_instance_progress([
            $participant_instance1->id => in_progress_instance::get_code(),
            $participant_instance2->id => not_started_instance::get_code(),
        ]);
    }

    private function mark_answers_complete(participant_section $participant_section): void {
        $section_elements = $participant_section->get_section()->get_section_elements();

        $responses = new collection();
        foreach ($section_elements as $section_element) {
            $responses->append($this->create_valid_element_response());
        }

        $participant_section->set_section_element_responses($responses);
    }

    private function create_valid_element_response(): section_element_response {
        return new class extends section_element_response {
            public $was_saved = false;

            public function __construct() {
            }

            public function save(): section_element_response {
                $this->was_saved = true;
                return $this;
            }

            public function validate_response($is_draft_validation = false): bool {
                $this->validation_errors = new collection();
                return true;
            }
        };
    }

    public function test_get_all_translated() {
        $this->assertEqualsCanonicalizing([
            70 => 'n/a (view only)',
            50 => 'Not submitted',
            20 => 'Complete',
            10 => 'In progress',
            0 => 'Not started',
        ], state_helper::get_all_display_names('participant_instance', participant_instance_progress::get_type()));
    }

    /**
     * @param array $instance_states
     */
    private function assert_participant_instance_progress(array $instance_states) {
        foreach ($instance_states as $instance_id => $state_code) {
            $participant_instance = participant_instance::load_by_id($instance_id);
            $this->assertEquals($state_code, $participant_instance->progress);
        }
    }

    public function state_transitions_data_provider(): array {
        return [
            'Not started to not started' =>
                [not_started_instance::class, not_started_instance::class, false, 'NONE_COMPLETE'],
            'Not started to in progress' =>
                [not_started_instance::class, in_progress_instance::class, true, 'SOME_COMPLETE'],
            'Not started to complete' =>
                [not_started_instance::class, complete_instance::class, true, 'ALL_COMPLETE'],
            'Not started to not submitted' =>
                [not_started_instance::class, not_submitted_instance::class, true, 'NONE_COMPLETE'],
            'Not started to not applicable' =>
                [not_started_instance::class, progress_not_applicable_instance::class, false, 'NONE_COMPLETE'],

            'In progress to in progress' =>
                [in_progress_instance::class, in_progress_instance::class, false, 'SOME_COMPLETE'],
            'In progress to not started' =>
                [in_progress_instance::class, not_started_instance::class, false, 'NONE_COMPLETE'],
            'In progress to complete' =>
                [in_progress_instance::class, complete_instance::class, true, 'ALL_COMPLETE'],
            'In progress to not submitted' =>
                [in_progress_instance::class, not_submitted_instance::class, true, 'SOME_COMPLETE'],
            'In progress to not applicable' =>
                [in_progress_instance::class, progress_not_applicable_instance::class, false, 'NONE_COMPLETE'],

            'Complete to complete' =>
                [complete_instance::class, complete_instance::class, true, 'ALL_COMPLETE'],
            'Complete to not started' =>
                [complete_instance::class, not_started_instance::class, true, 'NONE_COMPLETE'],
            'Complete to in progress' =>
                [complete_instance::class, in_progress_instance::class, true, 'SOME_COMPLETE'],
            'Complete to not submitted' =>
                [complete_instance::class, not_submitted_instance::class, false, 'ALL_COMPLETE'],
            'Complete to not applicable' =>
                [complete_instance::class, progress_not_applicable_instance::class, false, 'NONE_COMPLETE'],

            'Not submitted to not submitted' =>
                [not_submitted_instance::class, not_submitted_instance::class, false, 'SOME_COMPLETE'],
            'Not submitted to not started' =>
                [not_submitted_instance::class, not_started_instance::class, true, 'NONE_COMPLETE'],
            'Not submitted to in progress' =>
                [not_submitted_instance::class, in_progress_instance::class, true, 'SOME_COMPLETE'],
            'Not submitted to complete' =>
                [not_submitted_instance::class, complete_instance::class, false, 'SOME_COMPLETE'],
            'Not submitted to not applicable' =>
                [not_submitted_instance::class, progress_not_applicable_instance::class, false, 'NONE_COMPLETE'],

            'Not applicable to not started' =>
                [progress_not_applicable_instance::class, not_started_instance::class, false, 'NONE_COMPLETE'],
            'Not applicable to in progress' =>
                [progress_not_applicable_instance::class, in_progress_instance::class, false, 'NONE_COMPLETE'],
            'Not applicable to complete' =>
                [progress_not_applicable_instance::class, complete_instance::class, false, 'NONE_COMPLETE'],
            'Not applicable to not submitted' =>
                [progress_not_applicable_instance::class, not_submitted_instance::class, false, 'NONE_COMPLETE'],
            'Not applicable to not applicable' =>
                [progress_not_applicable_instance::class, progress_not_applicable_instance::class, false, 'NONE_COMPLETE'],
        ];
    }

    /**
     * Check switching participant instance states.
     *
     * @dataProvider state_transitions_data_provider
     * @param string|participant_instance_progress $initial_state_class
     * @param string|participant_instance_progress $target_state_class
     * @param bool $transition_possible
     * @param string|null $condition
     */
    public function test_switch_state(
        string $initial_state_class,
        string $target_state_class,
        bool $transition_possible,
        string $condition
    ): void {
        $this->setAdminUser();
        $subject_user = self::getDataGenerator()->create_user();
        $other_participant = self::getDataGenerator()->create_user();

        // Set and verify initial state.
        $entity = $this->create_participant_instance($subject_user, $other_participant);
        $entity->progress = $initial_state_class::get_code();
        $entity->update();
        $participant_instance = participant_instance::load_by_entity($entity);
        $this->assertInstanceOf($initial_state_class, $participant_instance->get_progress_state());

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();

        switch ($condition) {
            case 'NONE_COMPLETE':
                // There is one section by default, incomplete.
                break;
            case 'SOME_COMPLETE':
                // There is one section by default, incomplete. Add another, complete.
                $participant_section = $perform_generator->create_participant_section(
                    $participant_instance->subject_instance->activity,
                    $entity
                );
                $participant_section->progress = complete_section::get_code();
                $participant_section->update();
                break;
            case 'ALL_COMPLETE':
                // There is one section by default, incomplete. Update it to complete.
                $participant_section = $entity->participant_sections()->first();
                $participant_section->progress = complete_section::get_code();
                $participant_section->update();
                break;
            default:
                throw new coding_exception('Unexpected condition');
        }

        $this->setUser($subject_user);
        $sink = $this->redirectEvents();

        if (!$transition_possible) {
            $this->expectException(invalid_state_switch_exception::class);
            $this->expectExceptionMessage('Cannot switch');
        }

        $participant_instance->switch_state($target_state_class);

        /** @var participant_instance_entity $participant_instance_entity */
        $participant_instance_entity = participant_instance_entity::repository()->find($participant_instance->get_id());
        $db_progress = $participant_instance_entity->progress;
        $this->assertEquals($target_state_class::get_code(), $db_progress);
        $this->assertInstanceOf($target_state_class, $participant_instance->get_progress_state());

        // Check that event has been triggered.
        $this->assert_participant_instance_updated_event($sink, $participant_instance, $subject_user->id);
    }

    /**
     * Assert that the "participant section was update" event was fired
     *
     * @param \core_phpunit\event_sink $sink
     * @param participant_instance $participant_instance
     * @param int $user_id
     */
    private function assert_participant_instance_updated_event(
        \core_phpunit\event_sink $sink,
        participant_instance $participant_instance,
        int $user_id
    ): void {
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf('mod_perform\event\participant_instance_progress_updated', $event);
        $this->assertEquals($participant_instance->get_id(), $event->objectid);
        $this->assertEquals($user_id, $event->relateduserid);

        $this->assertEquals($participant_instance->get_context(), $event->get_context());

        $only_activity = activity::load_by_entity(activity_entity::repository()->one());

        $this->assertEquals($only_activity->get_context(), $event->get_context());

        $sink->close();
    }

    /**
     * @param stdClass|null $subject_user
     * @param stdClass|null $other_participant
     * @return participant_instance_entity
     */
    private function create_participant_instance(
        stdClass $subject_user = null,
        stdClass $other_participant = null
    ): entity {
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();

        self::setAdminUser();

        $subject_user_id = $subject_user ? $subject_user->id : user::logged_in()->id;
        $other_participant_id = $other_participant ? $other_participant->id : null;

        $perform_generator->create_subject_instance([
            'subject_user_id' => $subject_user_id,
            'other_participant_id' => $other_participant_id,
            'subject_is_participating' => true,
        ]);

        return participant_instance_entity::repository()
            ->where('participant_id', $subject_user_id)
            ->one();
    }

}
