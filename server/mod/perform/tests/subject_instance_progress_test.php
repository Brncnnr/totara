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
use mod_perform\constants;
use mod_perform\entity\activity\participant_instance;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use mod_perform\event\subject_instance_progress_updated;
use mod_perform\models\activity\subject_instance;
use mod_perform\models\response\participant_section;
use mod_perform\models\response\section_element_response;
use mod_perform\state\invalid_state_switch_exception;
use mod_perform\state\participant_instance\complete as complete_participant_instance;
use mod_perform\state\participant_instance\in_progress as in_progress_participant_instance;
use mod_perform\state\participant_instance\participant_instance_progress;
use mod_perform\state\participant_instance\not_started as not_started_participant_instance;
use mod_perform\state\participant_section\complete as complete_section;
use mod_perform\state\participant_section\in_progress as in_progress_section;
use mod_perform\state\state;
use mod_perform\state\state_helper;
use mod_perform\state\subject_instance\complete;
use mod_perform\state\subject_instance\condition\at_least_one_participant_instance_started;
use mod_perform\state\subject_instance\condition\all_participant_instances_complete;
use mod_perform\state\subject_instance\condition\not_all_participant_instances_complete;
use mod_perform\state\subject_instance\in_progress;
use mod_perform\state\subject_instance\not_started;
use mod_perform\state\subject_instance\not_submitted;
use mod_perform\state\subject_instance\subject_instance_progress;

require_once(__DIR__ . '/state_testcase.php');

/**
 * @group perform
 */
class mod_perform_subject_instance_progress_testcase extends state_testcase {

    protected static function get_object_type(): string {
        return 'subject_instance';
    }

    public function condition_all_participant_instances_data_provider() {
        $n = not_started_participant_instance::get_code();
        $i = in_progress_participant_instance::get_code();
        $c = complete_participant_instance::get_code();
        return [
            [$n, $n, false],
            [$n, $c, false],
            [$i, $i, false],
            [$c, $i, false],
            [$c, $c, true],
        ];
    }

    /**
     * @dataProvider condition_all_participant_instances_data_provider
     * @param $participant_instance1_progress
     * @param $participant_instance2_progress
     * @param $expected_result
     */
    public function test_condition_all_participant_instances_complete(
        $participant_instance1_progress,
        $participant_instance2_progress,
        $expected_result
    ) {
        $participant_instance1 = (object)['progress' => $participant_instance1_progress];
        $participant_instance2 = (object)['progress' => $participant_instance2_progress];

        $mock = $this->getMockBuilder(subject_instance::class)
            ->onlyMethods(['__get'])
            ->disableOriginalConstructor()
            ->getMock();

        $mock->expects($this->exactly(2))
            ->method('__get')
            ->with('participant_instances')
            ->willReturn(new collection([$participant_instance1, $participant_instance2]));

        $all_participant_instances_complete = new all_participant_instances_complete($mock);
        $this->assertEquals($expected_result, $all_participant_instances_complete->pass());

        $not_all_participant_instances_complete = new not_all_participant_instances_complete($mock);
        $this->assertEquals(!$expected_result, $not_all_participant_instances_complete->pass());
    }

    public function at_least_one_participant_instance_started_data_provider() {
        $n = not_started_participant_instance::get_code();
        $i = in_progress_participant_instance::get_code();
        $c = complete_participant_instance::get_code();
        return [
            [$n, $n, false],
            [$n, $i, true],
            [$n, $c, true],
            [$i, $c, true],
            [$c, $c, true],
        ];
    }

    /**
     * @dataProvider at_least_one_participant_instance_started_data_provider
     * @param $participant_instance1_progress
     * @param $participant_instance2_progress
     * @param $expected_result
     */
    public function test_condition_at_least_one_participant_instance_started(
        $participant_instance1_progress,
        $participant_instance2_progress,
        $expected_result
    ) {
        $participant_instance1 = (object)['progress' => $participant_instance1_progress];
        $participant_instance2 = (object)['progress' => $participant_instance2_progress];

        $mock = $this->getMockBuilder(subject_instance::class)
            ->onlyMethods(['__get'])
            ->disableOriginalConstructor()
            ->getMock();

        $mock->expects($this->once())
            ->method('__get')
            ->with('participant_instances')
            ->willReturn(new collection([$participant_instance1, $participant_instance2]));

        $at_least_one_participant_instance_started = new at_least_one_participant_instance_started($mock);
        $this->assertEquals($expected_result, $at_least_one_participant_instance_started->pass());
    }

    /**
     * Check interactions between participant instance and subject instance states.
     */
    public function test_participant_instance_switch_triggers_subject_instance_switch(): void {
        $this->setAdminUser();

        $configuration = \mod_perform\testing\activity_generator_configuration::new()
            ->set_relationships_per_section([constants::RELATIONSHIP_SUBJECT, constants::RELATIONSHIP_APPRAISER])
            ->enable_appraiser_for_each_subject_user()
            ->set_number_of_users_per_user_group_type(2)
            ->set_number_of_elements_per_section(1);

        /** @var \mod_perform\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_perform');
        $generator->create_full_activities($configuration);

        // Now we have 2 subject instances with 2 participant instances each.

        $subject_instances = subject_instance_entity::repository()->get()->all();
        $this->assertCount(2, $subject_instances);
        /** @var subject_instance_entity $subject_instance1 */
        $subject_instance1 = $subject_instances[0];
        /** @var subject_instance_entity $subject_instance2 */
        $subject_instance2 = $subject_instances[1];
        $this->assert_subject_instance_progress([
            $subject_instance1->id => not_started::get_code(),
            $subject_instance2->id => not_started::get_code(),
        ]);

        // We concentrate on subject_instance1 and make sure the other one is not affected.
        // Get the 2 participant instances for the subject instance.
        $participant_instances = participant_instance::repository()
            ->where('subject_instance_id', $subject_instance1->id)
            ->get()
            ->all();
        $this->assertCount(2, $participant_instances);
        $participant_instance1 = $participant_instances[0];
        $participant_instance2 = $participant_instances[1];
        /** @var user $subject_user1 */
        $subject_user1 = user::repository()->find($participant_instance1->participant_id);
        $this->setUser($subject_user1->to_record());

        // Make one participant instance go to in_progress.
        $this->proceed_participant_instance_progress($participant_instance1, in_progress_participant_instance::class);
        $this->assert_subject_instance_progress([
            $subject_instance1->id => in_progress::get_code(),
            $subject_instance2->id => not_started::get_code(),
        ]);

        // Complete the same participant instance. Should not affect the current subject instance state.
        $this->proceed_participant_instance_progress($participant_instance1, complete_participant_instance::class);
        $this->assert_subject_instance_progress([
            $subject_instance1->id => in_progress::get_code(),
            $subject_instance2->id => not_started::get_code(),
        ]);

        // Complete the other participant instance. That should make the subject instance complete, too.
        $this->proceed_participant_instance_progress($participant_instance2, complete_participant_instance::class);
        $this->assert_subject_instance_progress([
            $subject_instance1->id => complete::get_code(),
            $subject_instance2->id => not_started::get_code(),
        ]);
    }

    public function subject_instance_progress_updated_event_data_provider() {
        return [
            [in_progress_participant_instance::class, in_progress::class],
            [complete_participant_instance::class, complete::class],
        ];
    }

    /**
     * Check that subject_instance_progress_updated_event is triggered both when transitioning to in_progress
     * and to complete.
     *
     * @dataProvider subject_instance_progress_updated_event_data_provider
     * @param string|state $participant_instance_progress_setup_state
     * @param string|state $subject_instance_progress_target_state
     */
    public function test_subject_instance_progress_updated_event(
        string $participant_instance_progress_setup_state,
        string $subject_instance_progress_target_state
    ): void {
        $this->setAdminUser();

        $configuration = \mod_perform\testing\activity_generator_configuration::new()
            ->set_number_of_users_per_user_group_type(1);

        /** @var \mod_perform\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_perform');
        $generator->create_full_activities($configuration);

        // Set participant instance progress directly in DB to enable direct switching of subject instance.
        /** @var participant_instance $participant_instance */
        $participant_instance = participant_instance::repository()->one(true);
        $participant_instance->progress = $participant_instance_progress_setup_state::get_code();
        $participant_instance->save();

        $sink = $this->redirectEvents();
        $subject_instance_model = subject_instance::load_by_entity($participant_instance->subject_instance);
        $previous_subject_progress = $subject_instance_model->progress_status;

        $subject_instance_model->switch_state($subject_instance_progress_target_state);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf(subject_instance_progress_updated::class, $event);
        $this->assertEquals($subject_instance_model->get_id(), $event->objectid);
        $this->assertEquals($subject_instance_model->subject_user->id, $event->relateduserid);
        $this->assertEquals(get_admin()->id, $event->userid);
        $this->assertEquals($subject_instance_model->get_context(), $event->get_context());
        $this->assertEquals($subject_instance_model->progress_status, $event->other['progress']);
        $this->assertEquals($previous_subject_progress, $event->other['previous_progress']);

        $sink->close();
    }

    public function test_completion_sets_completed_at_date() {
        $this->setAdminUser();

        $configuration = \mod_perform\testing\activity_generator_configuration::new()
            ->set_number_of_users_per_user_group_type(1);

        /** @var \mod_perform\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_perform');
        $generator->create_full_activities($configuration);

        // Set participant instance progress in DB so we can directly switch subject_instance to complete.
        /** @var participant_instance $participant_instance */
        $participant_instance = participant_instance::repository()->one(true);
        $participant_instance->progress = complete_participant_instance::get_code();
        $participant_instance->save();

        $this->assertNull($participant_instance->subject_instance->completed_at);
        $subject_instance_model = subject_instance::load_by_entity($participant_instance->subject_instance);
        $subject_instance_model->switch_state(complete::class);

        // Check that completed_at has ben set to current time (give a few seconds leeway just in case).
        $participant_instance->subject_instance->refresh();
        $this->assertGreaterThan(time() - 10, $participant_instance->subject_instance->completed_at);
        $this->assertLessThanOrEqual(time(), $participant_instance->subject_instance->completed_at);
    }

    public function test_get_all_translated() {
        $this->assertEqualsCanonicalizing([
            50 => 'Not submitted',
            20 => 'Complete',
            10 => 'In progress',
            0 => 'Not started',
        ], state_helper::get_all_display_names('subject_instance', subject_instance_progress::get_type()));
    }

    /**
     * Set a participant_instance to a desired state by switching all its sections accordingly.
     *
     * @param participant_instance $participant_instance
     * @param string|participant_instance_progress $desired_state
     */
    private function proceed_participant_instance_progress(
        participant_instance $participant_instance,
        string $desired_state
    ) {
        $participant_sections = $participant_instance->participant_sections->all();
        switch ($desired_state) {
            case in_progress_participant_instance::class:
                foreach ($participant_sections as $participant_section) {
                    $participant_section_model = participant_section::load_by_entity($participant_section);
                    $participant_section_model->switch_state(in_progress_section::class);
                }
                break;
            case complete_participant_instance::class:
                foreach ($participant_sections as $participant_section) {
                    $participant_section_model = participant_section::load_by_entity($participant_section);
                    $this->mark_answers_complete($participant_section_model);
                    $participant_section_model->switch_state(complete_section::class);
                }
                break;
            default:
                $target_section_state = null;
                $this->fail("Can't switch participant_instance to {$desired_state}");
        }

        // Make sure it worked.
        $participant_instance->refresh();
        $this->assertEquals($desired_state::get_code(), $participant_instance->progress);
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

    /**
     * @param array $state_map
     */
    private function assert_subject_instance_progress(array $state_map) {
        foreach ($state_map as $instance_id => $state_code) {
            $subject_instance = subject_instance::load_by_id($instance_id);
            $this->assertEquals($state_code, $subject_instance->progress);
        }
    }

    public function state_transitions_data_provider(): array {
        return [
            'Not started to not started' => [not_started::class, not_started::class, false, 'NONE_COMPLETE'],
            'Not started to in progress' => [not_started::class, in_progress::class, true, 'SOME_COMPLETE'],
            'Not started to complete' => [not_started::class, complete::class, true, 'ALL_COMPLETE'],
            'Not started to not submitted' => [not_started::class, not_submitted::class, true, 'NONE_COMPLETE'],

            'In progress to in progress' => [in_progress::class, in_progress::class, false, 'SOME_COMPLETE'],
            'In progress to not started' => [in_progress::class, not_started::class, false, 'NONE_COMPLETE'],
            'In progress to complete' => [in_progress::class, complete::class, true, 'ALL_COMPLETE'],
            'In progress to not submitted' => [in_progress::class, not_submitted::class, true, 'SOME_COMPLETE'],

            'Complete to complete' => [complete::class, complete::class, true, 'ALL_COMPLETE'],
            'Complete to not started' => [complete::class, not_started::class, true, 'NONE_COMPLETE'],
            'Complete to in progress' => [complete::class, in_progress::class, true, 'SOME_COMPLETE'],
            'Complete to not submitted' => [complete::class, not_submitted::class, false, 'ALL_COMPLETE'],

            'Not submitted to not submitted' => [not_submitted::class, not_submitted::class, false, 'SOME_COMPLETE'],
            'Not submitted to not started' => [not_submitted::class, not_started::class, true, 'NONE_COMPLETE'],
            'Not submitted to in progress' => [not_submitted::class, in_progress::class, true, 'SOME_COMPLETE'],
            'Not submitted to complete' => [not_submitted::class, complete::class, false, 'SOME_COMPLETE'],
        ];
    }

    /**
     * Check switching subject instance states.
     *
     * @dataProvider state_transitions_data_provider
     * @param string|subject_instance_progress $initial_state_class
     * @param string|subject_instance_progress $target_state_class
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

        $configuration = \mod_perform\testing\activity_generator_configuration::new()
            ->set_relationships_per_section(['subject', 'appraiser'])
            ->enable_appraiser_for_each_subject_user()
            ->set_number_of_users_per_user_group_type(1)
            ->set_number_of_elements_per_section(0);

        /** @var \mod_perform\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_perform');
        $generator->create_full_activities($configuration)->first();

        /** @var subject_instance_entity $entity */
        $entity = subject_instance_entity::repository()->get()->first();
        $entity->progress = $initial_state_class::get_code();
        $entity->update();
        $subject_instance = subject_instance::load_by_entity($entity);
        $this->assertInstanceOf($initial_state_class, $subject_instance->get_progress_state());

        switch ($condition) {
            case 'NONE_COMPLETE':
                // There are two participant instances by default, incomplete.
                break;
            case 'SOME_COMPLETE':
                // There are two participant instances by default, incomplete. Mark one complete.
                /** @var participant_instance $participant_instance */
                $participant_instance = $entity->participant_instances()->first();
                $participant_instance->progress = complete_participant_instance::get_code();
                $participant_instance->update();
                break;
            case 'ALL_COMPLETE':
                // There are two participant instances by default, incomplete. Mark them both complete.
                /** @var participant_instance $participant_instance */
                foreach ($entity->participant_instances()->get() as $participant_instance) {
                    $participant_instance->progress = complete_participant_instance::get_code();
                    $participant_instance->update();
                }
                break;
            default:
                throw new coding_exception('Unexpected condition');
        }

        $this->setUser($subject_user);

        if (!$transition_possible) {
            $this->expectException(invalid_state_switch_exception::class);
            $this->expectExceptionMessage('Cannot switch');
        }

        $subject_instance->switch_state($target_state_class);

        /** @var subject_instance_entity $subject_instance_entity */
        $subject_instance_entity = subject_instance_entity::repository()->find($entity->id);
        $db_progress = $subject_instance_entity->progress;
        $this->assertEquals($target_state_class::get_code(), $db_progress);
        $this->assertInstanceOf($target_state_class, $subject_instance->get_progress_state());
    }

}
