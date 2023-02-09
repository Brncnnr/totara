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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use core\collection;
use core\entity\user;
use mod_perform\entity\activity\participant_instance;
use mod_perform\entity\activity\participant_section as participant_section_entity;
use mod_perform\entity\activity\section_element;
use mod_perform\event\participant_section_progress_updated;
use mod_perform\event\participant_section_saved_as_draft;
use mod_perform\entity\activity\element_response as element_response_entity;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\section;
use mod_perform\models\response\participant_section;
use mod_perform\state\participant_section\closed;
use mod_perform\state\participant_section\complete;
use mod_perform\state\participant_section\not_started;
use mod_perform\testing\generator as perform_generator;
use performelement_short_text\answer_length_exceeded_error;
use performelement_short_text\short_text;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;
use mod_perform\models\activity\activity_setting;

/**
 * @group perform
 */
class mod_perform_webapi_resolver_mutation_update_section_responses_testcase extends advanced_testcase {
    private const MUTATION = 'mod_perform_update_section_responses';

    use webapi_phpunit_helper;

    public function test_successful_create_and_update(): void {
        self::setAdminUser();

        $subject = self::getDataGenerator()->create_user();

        /** @var perform_generator $generator */
        $generator = perform_generator::instance();

        $subject_instance = $generator->create_subject_instance([
            'subject_is_participating' => true,
            'subject_user_id' => $subject->id,
            'other_participant_id' => user::logged_in()->id,
        ]);

        /** @var participant_instance $other_participant_instance */
        $other_participant_instance = $subject_instance->participant_instances()
            ->where('participant_id', user::logged_in()->id)
            ->one();

        /** @var participant_section_entity $participant_section */
        $participant_section =  $other_participant_instance->participant_sections()->one();

        /** @var collection|section_element[] $section_elements */
        $section_elements = $participant_section->section_elements()->get();

        /** @var section_element $section_element1 */
        $section_element1 = $section_elements->first();

        /** @var section_element $section_element2 */
        $section_element2 = $section_elements->last();

        $encoded_response1 = json_encode('A quick brown fox');
        $encoded_response2 = json_encode('Answer 2');

        $args = [
            'input' => [
                'participant_section_id' => $participant_section->id,
                'update' => [
                    [
                        'section_element_id' => $section_element1->id,
                        'response_data' => $encoded_response1,
                    ],
                    [
                        'section_element_id' => $section_element2->id,
                        'response_data' => $encoded_response2,
                    ],
                ],
            ],
        ];

        $sink = $this->redirectEvents();
        $context = $this->create_webapi_context(self::MUTATION);

        // Initial save of responses.
        /** @var participant_section $initial_save_result */
        $initial_save_result = $this->resolve_graphql_mutation(self::MUTATION, $args)['participant_section'];

        self::assertEquals('COMPLETE', $initial_save_result->get_progress_status());
        self::assertCount(2, $initial_save_result->get_section_element_responses());

        self::assertEquals(
            $encoded_response1,
            $initial_save_result->get_section_element_responses()->first()->response_data,
            'Expected result response to match update'
        );

        self::assertEquals(
            $encoded_response2,
            $initial_save_result->get_section_element_responses()->last()->response_data,
            'Expected result response to match update'
        );

        self::assertEquals(
            $encoded_response1,
            element_response_entity::repository()->order_by('id')->get()->first()->response_data,
            'Expected saved response to match initial save'
        );

        self::assertEquals(
            $encoded_response2,
            element_response_entity::repository()->order_by('id')->get()->last()->response_data,
            'Expected saved response to match initial save'
        );

        $events = $sink->get_events();
        $sink->clear();

        self::assertInstanceOf(
            participant_section_progress_updated::class,
            reset($events),
            'Expected progress updated event to be fired'
        );

        $participant_section->refresh();
        self::assertEquals(
            complete::get_code(),
            $participant_section->progress,
            'Expected participant section to change to progress status "complete"'
        );

        $encoded_response1_modified = json_encode('Changed answer one');

        $args = [
            'input' => [
                'participant_section_id' => $participant_section->id,
                'update' => [
                    [
                        'section_element_id' => $section_element1->id,
                        'response_data' => $encoded_response1_modified,
                    ],
                ],
            ],
        ];

        // Re-save/update.
        [$result, ] = $this->parsed_graphql_operation(self::MUTATION, $args);
        $update_save_result = $result['participant_section'];
        self::assertEquals('Part one', $update_save_result['section']['display_title']);

        // Everything is always returned, despite only patching one question.
        self::assertCount(2, $update_save_result['section_element_responses']);

        $participant_section->refresh();
        self::assertEquals(
            complete::get_code(),
            $participant_section->progress,
            'Expected participant section progress status to remain "complete"'
        );

        self::assertEqualsCanonicalizing(
            [$encoded_response1_modified, $encoded_response2],
            [
                element_response_entity::repository()->order_by('id')->get()->first()->response_data,
                element_response_entity::repository()->order_by('id')->get()->last()->response_data,
            ],
            'Expected saved response to match update'
        );

        $events = $sink->get_events();

        self::assertInstanceOf(
            participant_section_progress_updated::class,
            reset($events),
            'Expected progress updated event to be fired'
        );
    }

    /**
     * Tests exception is thrown when updating a closed participant section.
     *
     * @return void
    */
    public function test_can_not_update_closed_participant_section_response() {

        self::setAdminUser();
        $subject = self::getDataGenerator()->create_user();

        /** @var perform_generator $generator */
        $generator = perform_generator::instance();
        $activity = $generator->create_activity_in_container();
        $activity->settings->update([activity_setting::CLOSE_ON_COMPLETION => true]);

        $subject_instance = $generator->create_subject_instance([
            'subject_is_participating' => true,
            'subject_user_id' => $subject->id,
            'other_participant_id' => user::logged_in()->id,
            'activity_id' => $activity->id,
        ]);

        /** @var participant_instance $other_participant_instance */
        $other_participant_instance = $subject_instance->participant_instances()
            ->where('participant_id', user::logged_in()->id)
            ->one();

        /** @var participant_section_entity $participant_section */
        $participant_section =  $other_participant_instance->participant_sections()->one();

        /** @var collection|section_element[] $section_elements */
        $section_elements = $participant_section->section_elements()->get();

        $args = [
            'input' => [
                'participant_section_id' => $participant_section->id,
                'update' => [
                    [
                        'section_element_id' => $section_elements->first()->id,
                        'response_data' => json_encode('A quick brown fox'),
                    ],
                    [
                        'section_element_id' => $section_elements->last()->id,
                        'response_data' => json_encode('Answer 2'),
                    ],
                ],
            ],
        ];

        /** @var participant_section $initial_save_result */
        $this->resolve_graphql_mutation(self::MUTATION, $args)['participant_section'];
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Can not update response to a closed participant section');
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    /**
     * Tests exception is thrown when updating a closed participant section.
     *
     * @return void
     */
    public function test_mix_of_respondable_and_non_respondable_elements_closes_sections() {

        self::setAdminUser();
        $subject = self::getDataGenerator()->create_user();

        /** @var perform_generator $generator */
        $generator = perform_generator::instance();
        $activity = $generator->create_activity_in_container();
        $activity->settings->update([activity_setting::CLOSE_ON_COMPLETION => true]);

        $subject_instance = $generator->create_subject_instance([
            'subject_is_participating' => true,
            'subject_user_id' => $subject->id,
            'other_participant_id' => user::logged_in()->id,
            'activity_id' => $activity->id,
        ]);

        /** @var participant_instance $other_participant_instance */
        $other_participant_instance = $subject_instance->participant_instances()
            ->where('participant_id', user::logged_in()->id)
            ->one();

        /** @var participant_section_entity $participant_section */
        $participant_section = $other_participant_instance->participant_sections()->one();

        // Create a static element which is not respondable
        $section = section::load_by_entity($participant_section->section);
        $element3 = $generator->create_element(['title' => 'Static element', 'plugin_name' => 'static_content']);
        $generator->create_section_element($section, $element3, 3);

        /** @var collection|section_element[] $section_elements */
        $section_elements = $participant_section->section_elements()->get();

        $args = [
            'input' => [
                'participant_section_id' => $participant_section->id,
                'update' => [
                    [
                        'section_element_id' => $section_elements->shift()->id,
                        'response_data' => json_encode('A quick brown fox'),
                    ],
                    [
                        'section_element_id' => $section_elements->shift()->id,
                        'response_data' => json_encode('Answer 2'),
                    ],
                ],
            ],
        ];

        /** @var participant_section $initial_save_result */
        $this->resolve_graphql_mutation(self::MUTATION, $args)['participant_section'];

        $participant_section->refresh();

        // Now the section should be closed
        $this->assertEquals(complete::get_code(), $participant_section->progress);
        $this->assertEquals(closed::get_code(), $participant_section->availability);
    }

    public function test_with_validation_errors(): void {
        self::setAdminUser();

        $subject = self::getDataGenerator()->create_user();

        $generator = perform_generator::instance();

        $subject_instance = $generator->create_subject_instance([
            'subject_is_participating' => true,
            'subject_user_id' => $subject->id,
            'other_participant_id' => user::logged_in()->id,
        ]);

        /** @var participant_instance $other_participant_instance */
        $other_participant_instance = $subject_instance->participant_instances()
            ->where('participant_id', user::logged_in()->id)
            ->one();

        /** @var participant_section_entity $participant_section */
        $participant_section =  $other_participant_instance->participant_sections()->one();

        /** @var collection|section_element[] $section_elements */
        $section_elements = $participant_section->section_elements()->get();

        /** @var section_element $section_element1 */
        $section_element1 = $section_elements->first();

        /** @var section_element $section_element2 */
        $section_element2 = $section_elements->last();

        $args = [
            'input' => [
                'participant_section_id' => $participant_section->id,
                'update' => [
                    [
                        'section_element_id' => $section_element1->id,
                        'response_data' => json_encode('This one is fine'),
                    ],
                    [
                        'section_element_id' => $section_element2->id,
                        'response_data' => json_encode(random_string(short_text::MAX_ANSWER_LENGTH + 1)),
                    ],
                ],
            ],
        ];

        // re-save
        [$result, ] = $this->parsed_graphql_operation(self::MUTATION, $args);
        $result = $result['participant_section'];

        self::assertEquals(
            not_started::get_code(),
            $participant_section->refresh()->progress,
            'Section should not have been completed'
        );

        self::assertCount(0, $result['section_element_responses'][0]['validation_errors']);

        self::assertEquals([
            'error_code' => answer_length_exceeded_error::LENGTH_EXCEEDED,
            'error_message' => 'Question text exceeds the maximum length',
        ], $result['section_element_responses'][1]['validation_errors'][0]);
    }

    public function test_can_not_update_another_persons_responses(): void {
        self::setAdminUser();

        $subject = self::getDataGenerator()->create_user();
        $other_participant = self::getDataGenerator()->create_user();

        /** @var perform_generator $generator */
        $generator = perform_generator::instance();

        $subject_instance = $generator->create_subject_instance([
            'subject_is_participating' => true,
            'subject_user_id' => $subject->id,
            'other_participant_id' => $other_participant->id,
        ]);

        /** @var participant_instance $other_participant_instance */
        $other_participant_instance = $subject_instance->participant_instances()
            ->where('participant_id', $other_participant->id)
            ->one();

        /** @var participant_section_entity $participant_section */
        $participant_section =  $other_participant_instance->participant_sections()->one();

        /** @var collection|section_element[] $section_elements */
        $section_elements = $participant_section->section_elements()->get();

        /** @var section_element $section_element1 */
        $section_element1 = $section_elements->first();

        /** @var section_element $section_element2 */
        $section_element2 = $section_elements->last();

        $args = [
            'input' => [
                'participant_section_id' => $participant_section->id,
                'update' => [
                    [
                        'section_element_id' => $section_element1->id,
                        'response_data' => json_encode('This one is fine'),
                    ],
                    [
                        'section_element_id' => $section_element2->id,
                        'response_data' => json_encode(random_string(short_text::MAX_ANSWER_LENGTH + 1)),
                    ],
                ],
            ],
        ];

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Participant section not found for id {$participant_section->id}");

        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    public function test_can_not_update_responses_for_a_participant_section_that_doesnt_exist(): void {
        self::setAdminUser();

        $args = [
            'input' => [
                'participant_section_id' => 1,
                'update' => [
                    [
                        'section_element_id' => 1,
                        'response_data' => json_encode('This one is fine'),
                    ],
                    [
                        'section_element_id' => 2,
                        'response_data' => json_encode(random_string(short_text::MAX_ANSWER_LENGTH + 1)),
                    ],
                ],
            ],
        ];

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Participant section not found for id 1');

        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    public function test_successful_draft_saving(): void {
        self::setAdminUser();

        $subject = self::getDataGenerator()->create_user();

        /** @var perform_generator $generator */
        $generator = perform_generator::instance();

        $subject_instance = $generator->create_subject_instance([
            'subject_is_participating' => true,
            'subject_user_id'          => $subject->id,
            'other_participant_id'     => user::logged_in()->id,
        ]);

        /** @var participant_instance $other_participant_instance */
        $other_participant_instance = $subject_instance->participant_instances()
            ->where('participant_id', user::logged_in()->id)
            ->one();

        /** @var participant_section_entity $participant_section */
        $participant_section =  $other_participant_instance->participant_sections()->one();

        /** @var collection|section_element[] $section_elements */
        $section_elements = $participant_section->section_elements()->get();

        /** @var section_element $section_element1 */
        $section_element1 = $section_elements->first();

        /** @var section_element $section_element2 */
        $section_element2 = $section_elements->last();

        $encoded_response1 = json_encode('A quick brown fox');
        participant_section::load_by_entity($participant_section)->get_progress_state()->on_participant_access();

        $args = [
            'input' => [
                'participant_section_id' => $participant_section->id,
                'is_draft' => true,
                'update' => [
                    [
                        'section_element_id' => $section_element1->id,
                        'response_data' => $encoded_response1,
                    ],
                    [
                        'section_element_id' => $section_element2->id,
                        'response_data' => "",
                    ],
                ],
            ],
        ];


        // Initial save of responses.
        $sink = $this->redirectEvents();
        /** @var participant_section $initial_save_result */
        $initial_save_result = $this->resolve_graphql_mutation(self::MUTATION, $args)['participant_section'];

        self::assertEquals('IN_PROGRESS', $initial_save_result->get_progress_status());
        self::assertCount(2, $initial_save_result->get_section_element_responses());

        $events = array_filter(
            $sink->get_events(),
            function ($event): bool {
                return $event instanceof participant_section_saved_as_draft;
            }
        );
        $this->assertCount(1, $events);

        $event = reset($events);
        $participant_instance = $participant_section->participant_instance;
        $this->assertEquals($participant_section->id, $event->objectid, 'wrong object id');
        $this->assertEquals($participant_instance->participant_id, $event->relateduserid, 'wrong relateduserid');
        $this->assertFalse($event->other['anonymous'], 'wrong anonymous');
        $this->assertEquals(
            $participant_instance->participant_source,
            $event->other['participant_source'],
            'wrong participant source'
        );

        $sink->close();
    }

    public function test_can_not_save_responses_for_section_elements_that_belong_to_a_different_section(): void {
        self::setAdminUser();

        $subject = self::getDataGenerator()->create_user();
        $other_participant = self::getDataGenerator()->create_user();

        /** @var perform_generator $generator */
        $generator = perform_generator::instance();

        $subject_instance = $generator->create_subject_instance([
            'subject_is_participating' => true,
            'subject_user_id' => $subject->id,
            'other_participant_id' => $other_participant->id,
        ]);

        /** @var participant_instance $subjects_participant_instance */
        $subjects_participant_instance = $subject_instance->participant_instances()
            ->where('participant_id', $subject->id)
            ->one();

        /** @var participant_section_entity $subjects_participants_section */
        $subjects_participants_section =  $subjects_participant_instance->participant_sections()->one();

        /** @var participant_instance $other_participant_instance */
        $other_participant_instance = $subject_instance->participant_instances()
            ->where('participant_id', $other_participant->id)
            ->one();

        $activity = new activity($subject_instance->activity());
        $section2 = $generator->create_section($activity, ['title' => 'Part one']);

        $element = $generator->create_element(['title' => 'Section two question one']);
        $generator->create_section_element($section2, $element);

        $other_participants_section = $generator->create_participant_section(
            $activity,
            $other_participant_instance,
            false,
            $section2
        );

        /** @var collection|section_element[] $section_elements */
        $other_participant_section_elements = $other_participants_section->section_elements()->get();

        $other_participants_section_element_id = $other_participant_section_elements->first()->id;

        $args = [
            'input' => [
                'participant_section_id' => $subjects_participants_section->id,
                'update' => [
                    [
                        'section_element_id' => $other_participants_section_element_id,
                        'response_data' => json_encode('This one belongs to the other participants section'),
                    ],
                ],
            ],
        ];

        self::setUser($subject); // 784003

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Section element not found for id {$other_participants_section_element_id}");

        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    public function test_can_not_save_responses_for_section_elements_that_dont_exist(): void {
        self::setAdminUser();

        $subject = self::getDataGenerator()->create_user();


        /** @var perform_generator $generator */
        $generator = perform_generator::instance();

        $subject_instance = $generator->create_subject_instance([
            'subject_is_participating' => true,
            'subject_user_id' => $subject->id,
            'other_participant_id' => user::logged_in()->id,
        ]);

        /** @var participant_instance $other_participant_instance */
        $other_participant_instance = $subject_instance->participant_instances()
            ->where('participant_id', user::logged_in()->id)
            ->one();

        /** @var participant_section_entity $participant_section */
        $participant_section =  $other_participant_instance->participant_sections()->one();

        /** @var collection|section_element[] $section_elements */
        $section_elements = $participant_section->section_elements()->get();

        /** @var section_element $section_element1 */
        $section_element1 = $section_elements->first();

        $args = [
            'input' => [
                'participant_section_id' => $participant_section->id,
                'update' => [
                    [
                        'section_element_id' => $section_element1->id,
                        'response_data' => json_encode('This one is fine'),
                    ],
                    [
                        'section_element_id' => 0,
                        'response_data' => json_encode("This section element doesn't exist at all"),
                    ],
                ],
            ],
        ];

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Section element not found for id 0');

        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    public function test_failed_ajax_query(): void {
        $args = [
            'input' => [
                'participant_section_id' => 123,
                'update' => [
                    [
                        'section_element_id' => 123,
                        'response_data' => '',
                    ]
                ],
            ],
        ];

        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
        advanced_feature::enable($feature);

        $this->setUser();
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'not logged in');
    }
}