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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use core\collection;
use core\entity\user;
use core\orm\entity\entity;
use mod_perform\entity\activity\activity as activity_entity;
use mod_perform\entity\activity\participant_section as participant_section_entity;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\entity\activity\section_element;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\participant_instance;
use mod_perform\models\activity\section_element as section_element_model;
use mod_perform\models\response\section_element_response;
use mod_perform\models\response\element_validation_error;
use mod_perform\models\response\participant_section;
use mod_perform\state\invalid_state_switch_exception;
use mod_perform\state\participant_section\complete;
use mod_perform\state\participant_section\in_progress;
use mod_perform\state\participant_section\not_started;
use mod_perform\state\participant_section\not_submitted;
use mod_perform\state\participant_section\participant_section_progress;
use mod_perform\state\participant_section\progress_not_applicable;
use mod_perform\state\state_helper;

require_once(__DIR__ . '/relationship_testcase.php');
require_once(__DIR__ . '/state_testcase.php');

/**
 * @group perform
 */
class mod_perform_participant_section_progress_testcase extends state_testcase {

    protected static function get_object_type(): string {
        return 'participant_section';
    }

    public function state_transitions_data_provider(): array {
        // Where the transition is false, the condition should indicate the state of data which you would
        // expect would be required in order to enter the target state. For example, complete => not_started
        // is not possible, and you would expect not_started to normally require condition NONE_COMPLETE.
        return [
            'Not started to not started' =>
                [not_started::class, not_started::class, false, 'NONE_COMPLETE'],
            'Not started to in progress' =>
                [not_started::class, in_progress::class, true, 'SOME_INCOMPLETE'],
            'Not started to complete' =>
                [not_started::class, complete::class, true, 'ALL_COMPLETE'],
            'Not started to not submitted' =>
                [not_started::class, not_submitted::class, true, 'NONE_COMPLETE'],
            'Not started to not applicable' =>
                [not_started::class, progress_not_applicable::class, false, 'NONE_COMPLETE'],

            'In progress to in progress' =>
                [in_progress::class, in_progress::class, false, 'SOME_INCOMPLETE'],
            'In progress to not started' =>
                [in_progress::class, not_started::class, false, 'NONE_COMPLETE'],
            'In progress to complete' =>
                [in_progress::class, complete::class, true, 'ALL_COMPLETE'],
            'In progress to not submitted' =>
                [in_progress::class, not_submitted::class, true, 'NONE_COMPLETE'],
            'In progress to not applicable' =>
                [in_progress::class, progress_not_applicable::class, false, 'NONE_COMPLETE'],

            'Complete to complete' =>
                [complete::class, complete::class, true, 'ALL_COMPLETE'],
            'Complete to not started' =>
                [complete::class, not_started::class, false, 'NONE_COMPLETE'],
            'Complete to in progress' =>
                [complete::class, in_progress::class, true, 'SOME_INCOMPLETE'],
            'Complete to not submitted' =>
                [complete::class, not_submitted::class, false, 'NONE_COMPLETE'],
            'Complete to not applicable' =>
                [complete::class, progress_not_applicable::class, false, 'NONE_COMPLETE'],

            'Not submitted to not submitted' =>
                [not_submitted::class, not_submitted::class, false, 'NONE_COMPLETE'],
            'Not submitted to not started' =>
                [not_submitted::class, not_started::class, true, 'NONE_COMPLETE'],
            'Not submitted to in progress' =>
                [not_submitted::class, in_progress::class, true, 'SOME_INCOMPLETE'],
            'Not submitted to complete' =>
                [not_submitted::class, complete::class, false, 'ALL_COMPLETE'],
            'Not submitted to not applicable' =>
                [not_submitted::class, progress_not_applicable::class, false, 'NONE_COMPLETE'],

            'Not applicable to not started' =>
                [progress_not_applicable::class, not_started::class, false, 'NONE_COMPLETE'],
            'Not applicable to in progress' =>
                [progress_not_applicable::class, in_progress::class, false, 'SOME_INCOMPLETE'],
            'Not applicable to complete' =>
                [progress_not_applicable::class, complete::class, false, 'ALL_COMPLETE'],
            'Not applicable to not submitted' =>
                [progress_not_applicable::class, not_submitted::class, false, 'NONE_COMPLETE'],
            'Not applicable to not applicable' =>
                [progress_not_applicable::class, progress_not_applicable::class, false, 'NONE_COMPLETE'],
        ];
    }

    /**
     * Check switching participant section states.
     *
     * @dataProvider state_transitions_data_provider
     * @param string|participant_section_progress $initial_state_class
     * @param string|participant_section_progress $target_state_class
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
        $entity = $this->create_participant_section($subject_user, $other_participant);
        $entity->progress = $initial_state_class::get_code();
        $entity->update();
        $participant_section = participant_section::load_by_entity($entity);
        $this->assertInstanceOf($initial_state_class, $participant_section->get_progress_state());

        // The generator creates two section elements, so we will provide two responses.
        switch ($condition) {
            case 'NONE_COMPLETE':
                $responses = new collection([
                ]);
                break;
            case 'SOME_INCOMPLETE':
                $responses = new collection([
                    $this->create_element_response_with_validation_errors(),
                    $this->create_valid_element_response(),
                ]);
                break;
            case 'ALL_COMPLETE':
                $responses = new collection([
                    $this->create_valid_element_response(),
                    $this->create_valid_element_response(),
                ]);
                break;
            default:
                throw new coding_exception('Unexpected condition');
        }
        $participant_section->set_section_element_responses($responses);

        $this->setUser($subject_user);
        $sink = $this->redirectEvents();

        if (!$transition_possible) {
            $this->expectException(invalid_state_switch_exception::class);
            $this->expectExceptionMessage('Cannot switch');
        }

        $participant_section->switch_state($target_state_class);

        /** @var participant_section_entity $participant_section_entity */
        $participant_section_entity = participant_section_entity::repository()->find($participant_section->get_id());
        $db_progress = $participant_section_entity->progress;
        $this->assertEquals($target_state_class::get_code(), $db_progress);
        $this->assertInstanceOf($target_state_class, $participant_section->get_progress_state());

        // Check that event has been triggered.
        $this->assert_participant_section_updated_event(
            $sink,
            $participant_section,
            $subject_user->id,
            $initial_state_class::get_name()
        );
    }

    public function test_get_all_translated() {
        $this->assertEqualsCanonicalizing([
            70 => 'Not applicable',
            50 => 'Not submitted',
            20 => 'Complete',
            10 => 'In progress',
            0 => 'Not started',
        ], state_helper::get_all_display_names('participant_section', participant_section_progress::get_type()));
    }

    /**
     * Assert that the "participant section was update" event was fired
     *
     * @param \core_phpunit\event_sink $sink
     * @param participant_section $participant_section
     * @param int $user_id
     * @param string $previous_progress_status
     */
    private function assert_participant_section_updated_event(
        \core_phpunit\event_sink $sink,
        participant_section $participant_section,
        int $user_id,
        string $previous_progress_status
    ): void {
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf('mod_perform\event\participant_section_progress_updated', $event);
        $this->assertEquals($participant_section->get_id(), $event->objectid);
        $this->assertEquals($user_id, $event->relateduserid);
        $this->assertEquals($user_id, $event->userid);

        $this->assertEquals($participant_section->get_context(), $event->get_context());

        $only_activity = activity::load_by_entity(activity_entity::repository()->one());

        $this->assertEquals($only_activity->get_context(), $event->get_context());

        $participant_instance = $participant_section->participant_instance;

        $this->assertEquals($previous_progress_status, $event->other['previous_progress']);
        $this->assertEquals($participant_section->progress_status, $event->other['progress']);
        $this->assertEquals($participant_instance->anonymise_responses, $event->other['anonymous']);
        $this->assertEquals($participant_instance->participant_source, $event->other['participant_source']);

        $sink->close();
    }

    public function test_complete_success(): void {
        $participant_section = participant_section::load_by_entity($this->create_participant_section());

        $responses = new collection([
            $this->create_valid_element_response(),
            $this->create_valid_element_response(),
        ]);

        self::assertEquals(
            not_started::get_code(),
            $participant_section->progress,
            'Participant section should start with the in_progress status'
        );

        $participant_section->set_section_element_responses($responses);
        $completion_success = $participant_section->complete();

        self::assertTrue($completion_success);

        self::assertEquals(
            complete::get_code(),
            $participant_section->progress,
            'Participant section should have the complete status'
        );

        foreach ($responses as $response) {
            self::assertTrue($response->was_saved, 'All element responses should been saved');
        }
    }

    /**
     * @dataProvider invalid_responses_provider
     * @param section_element_response ...$element_responses
     */
    public function test_complete_with_validation_errors(section_element_response ...$element_responses): void {
        $participant_section = participant_section::load_by_entity($this->create_participant_section());

        self::assertEquals(
            not_started::get_code(),
            $participant_section->progress,
            'Participant section should start with the in_progress status'
        );

        $participant_section->set_section_element_responses(new collection($element_responses));
        $completion_success = $participant_section->complete();

        self::assertFalse($completion_success);

        self::assertEquals(
            not_started::get_code(),
            $participant_section->progress,
            'Participant section should have the complete status'
        );

        foreach ($element_responses as $response) {
            self::assertFalse($response->was_saved, 'No element responses should have been saved');
        }
    }

    public function invalid_responses_provider(): array {
        return [
            'Partial success' => [
                $this->create_valid_element_response(),
                $this->create_valid_element_response(),
                $this->create_element_response_with_validation_errors(),
            ],
            'No success' => [
                $this->create_element_response_with_validation_errors(),
            ],
            'Multiple errors' => [
                $this->create_element_response_with_validation_errors(),
                $this->create_element_response_with_validation_errors(),
                $this->create_element_response_with_validation_errors(),
            ],
        ];
    }

    public function test_set_response_data_from_request(): void {
        $participant_section = $this->create_section_element_with_empty_responses();

        $request_payload = [
            ['section_element_id' => 1, 'response_data' => 'answer 1'],
            ['section_element_id' => 2, 'response_data' => 'answer 2'],
        ];

        static::assertCount(2, $participant_section->get_section_element_responses());
        static::assertNull($participant_section->find_element_response(1)->response_data);
        static::assertNull($participant_section->find_element_response(2)->response_data);

        $participant_section->set_responses_data_from_request($request_payload);

        static::assertCount(2, $participant_section->get_section_element_responses());
        static::assertEquals('answer 1', $participant_section->find_element_response(1)->response_data);
        static::assertEquals('answer 2', $participant_section->find_element_response(2)->response_data);
    }

    public function test_set_response_data_from_request_partial_update(): void {
        $participant_section = $this->create_section_element_with_empty_responses();

        $request_payload = [
            ['section_element_id' => 2, 'response_data' => 'answer 2'],
        ];

        static::assertCount(2, $participant_section->get_section_element_responses());
        static::assertNull($participant_section->find_element_response(1)->response_data);
        static::assertNull($participant_section->find_element_response(2)->response_data);

        $participant_section->set_responses_data_from_request($request_payload);

        static::assertCount(2, $participant_section->get_section_element_responses());
        static::assertNull($participant_section->find_element_response(1)->response_data);
        static::assertEquals('answer 2', $participant_section->find_element_response(2)->response_data);
    }

    public function test_cant_set_response_data_from_request_on_non_existent_section_element(): void {
        $participant_section = $this->create_section_element_with_empty_responses();

        $request_payload = [
            ['section_element_id' => 3, 'response_data' => 'answer 1'],
        ];

        static::assertCount(2, $participant_section->get_section_element_responses());
        static::assertNull($participant_section->find_element_response(1)->response_data);
        static::assertNull($participant_section->find_element_response(2)->response_data);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Section element not found for id 3');

        $participant_section->set_responses_data_from_request($request_payload);
    }

    public function test_cant_set_response_data_from_request_on_non_respondable_section_element(): void {
        $participant_section = $this->create_section_element_with_empty_responses('static_content');

        $request_payload = [
            ['section_element_id' => 2, 'response_data' => 'answer 1'],
        ];

        static::assertCount(2, $participant_section->get_section_element_responses());
        static::assertNull($participant_section->find_element_response(1)->response_data);
        static::assertNull($participant_section->find_element_response(2)->response_data);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Section element with id 2 can not be responded to');

        $participant_section->set_responses_data_from_request($request_payload);
    }

    private function create_section_element_with_empty_responses($element_plugin = 'short_text'): participant_section {
        $participant_section_entity = $this->create_participant_section();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();

        $participant_instance = participant_instance::load_by_entity($participant_section_entity->participant_instance);

        $element1 = $perform_generator->create_element(['plugin_name' => $element_plugin]);
        $section_element1 = new section_element(['id' => 1, 'element_id' => $element1->id]);

        $element2 = $perform_generator->create_element(['plugin_name' => $element_plugin]);
        $section_element2 = new section_element(['id' => 2, 'element_id' => $element2->id]);

        $response1 = new section_element_response(
            $participant_instance,
            section_element_model::load_by_entity($section_element1),
            null,
            new collection()
        );

        $response2 = new section_element_response(
            $participant_instance,
            section_element_model::load_by_entity($section_element2),
            null,
            new collection()
        );

        $responses = new collection([$response1, $response2]);

        return (new participant_section($participant_section_entity))->set_section_element_responses($responses);
    }

    /**
     * @param stdClass|null $subject_user
     * @param stdClass|null $other_participant
     * @return participant_section_entity
     */
    private function create_participant_section(
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

        return participant_section_entity::repository()
            ->join([participant_instance_entity::TABLE, 'pi'], 'participant_instance_id', '=', 'id')
            ->where('pi.participant_id', $subject_user_id)
            ->one();
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

    private function create_element_response_with_validation_errors(): section_element_response {
        return new class extends section_element_response {
            public $was_saved = false;

            public function __construct() {
            }

            public function save(): section_element_response {
                $this->was_saved = true;
                return $this;
            }

            public function validate_response($is_draft_validation = false): bool {
                $error1 = new element_validation_error(1, 'one');
                $error2 = new element_validation_error(2, 'two');

                $this->validation_errors = new collection([$error1, $error2]);
                return false;
            }
        };
    }

}
