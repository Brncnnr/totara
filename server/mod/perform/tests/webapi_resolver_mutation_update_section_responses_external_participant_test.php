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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use mod_perform\constants;
use mod_perform\entity\activity\element_response as element_response_entity;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\entity\activity\participant_section as participant_section_entity;
use mod_perform\event\participant_section_saved_as_draft;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\activity_setting;
use mod_perform\models\activity\participant_source;
use mod_perform\models\response\participant_section;
use mod_perform\state\subject_instance\closed;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group perform
 */
class mod_perform_webapi_resolver_mutation_update_section_responses_external_participant_testcase extends advanced_testcase {
    private const MUTATION = 'mod_perform_update_section_responses_external_participant';
    private const MUTATION_NOSESSION = self::MUTATION.'_nosession';

    use webapi_phpunit_helper;

    public function test_resolve_mutation(): void {
        $external_section = $this->create_test_data();

        $this->setUser(null);

        $user = self::getDataGenerator()->create_user();

        $section_elements = $external_section->section_elements;

        $token = $external_section->participant_instance->external_participant->token;

        $expected_responses = [];
        $answers = [];
        $i = 1;
        foreach ($section_elements as $section_element) {
            $encoded_response = json_encode('response '.$i);
            $expected_responses[] = $encoded_response;
            $answers[] = [
                'section_element_id' => $section_element->id,
                'response_data' => $encoded_response,
            ];
            $i++;
        }

        $args = [
            'input' => [
                'participant_section_id' => $external_section->id,
                'token' => $token,
                'update' => $answers,
            ],
        ];

        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $this->assertArrayHasKey('participant_section', $result);
        $result = $result['participant_section'];
        $this->assertInstanceOf(participant_section::class, $result);
        $this->assertEquals('COMPLETE', $result->get_progress_status());
        $this->assertEquals('OPEN', $result->get_availability_status());
        $this->assertCount(2, $result->get_section_element_responses());

        $actual_responses = $result->get_section_element_responses()->pluck('response_data');
        $this->assertEqualsCanonicalizing($expected_responses, $actual_responses);

        $actual_responses = element_response_entity::repository()
            ->where('participant_instance_id', $external_section->participant_instance_id)
            ->get()
            ->pluck('response_data');
        $this->assertEqualsCanonicalizing($expected_responses, $actual_responses);


        // Empty token
        $args = [
            'input' => [
                'participant_section_id' => $external_section->id,
                'token' => '',
                'update' => $answers,
            ],
        ];

        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $this->assertNull($result);


        // Invalid token
        $args = [
            'input' => [
                'participant_section_id' => $external_section->id,
                'token' => 'idontexist',
                'update' => $answers,
            ],
        ];

        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $this->assertNull($result);

        /** @var participant_section_entity $external_section2 */
        $external_section2 = participant_section_entity::repository()
            ->join([participant_instance_entity::TABLE, 'pi'], 'participant_instance_id', 'id')
            ->where('pi.participant_source', participant_source::EXTERNAL)
            ->where('pi.subject_instance_id', '<>', $external_section->participant_instance->subject_instance_id)
            ->order_by('id')
            ->first();

        // Token does not match the section
        $args = [
            'input' => [
                'participant_section_id' => $external_section2->id,
                'token' => $token,
                'update' => $answers,
            ],
        ];

        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $this->assertNull($result);


        // Should only work for not logged in users
        $this->setUser($user);

        $args = [
            'input' => [
                'participant_section_id' => $external_section->id,
                'token' => $token,
                'update' => $answers,
            ],
        ];

        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $this->assertNull($result);
    }

    public function test_successful_draft_saving(): void {
        $external_section = $this->create_test_data();

        $this->setUser(null);

        $section_elements = $external_section->section_elements;

        $token = $external_section->participant_instance->external_participant->token;

        $expected_responses = [];
        $answers = [];
        $i = 1;
        foreach ($section_elements as $section_element) {
            $encoded_response = json_encode('response '.$i);
            $expected_responses[] = $encoded_response;
            $answers[] = [
                'section_element_id' => $section_element->id,
                'response_data' => $encoded_response,
            ];
            $i++;
        }

        $args = [
            'input' => [
                'participant_section_id' => $external_section->id,
                'is_draft' => true,
                'token' => $token,
                'update' => $answers,
            ],
        ];

        $sink = $this->redirectEvents();

        $participant_section = participant_section::load_by_entity($external_section);
        $participant_section->get_progress_state()->on_participant_access();

        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $this->assertArrayHasKey('participant_section', $result);
        $result = $result['participant_section'];
        $this->assertInstanceOf(participant_section::class, $result);
        $this->assertEquals('IN_PROGRESS', $result->get_progress_status());
        $this->assertEquals('OPEN', $result->get_availability_status());
        $this->assertCount(2, $result->get_section_element_responses());

        $actual_responses = $result->get_section_element_responses()->pluck('response_data');
        $this->assertEqualsCanonicalizing($expected_responses, $actual_responses);

        $actual_responses = element_response_entity::repository()
            ->where('participant_instance_id', $external_section->participant_instance_id)
            ->get()
            ->pluck('response_data');
        $this->assertEqualsCanonicalizing($expected_responses, $actual_responses);

        $events = array_filter(
            $sink->get_events(),
            function ($event): bool {
                return $event instanceof participant_section_saved_as_draft;
            }
        );
        $this->assertCount(1, $events);

        $event = reset($events);
        $participant_instance = $participant_section->participant_instance;
        $this->assertEquals($external_section->id, $event->objectid, 'wrong object id');
        $this->assertEquals($participant_instance->participant_id, $event->relateduserid, 'wrong relateduserid');
        $this->assertFalse($event->other['anonymous'], 'wrong anonymous');
        $this->assertEquals(
            $participant_instance->participant_source,
            $event->other['participant_source'],
            'wrong participant source'
        );

        $sink->close();
    }

    /**
     * Tests exception is thrown when updating a closed participant section.
     *
     * @return void
     */
    public function test_can_not_update_closed_subject_instance() {
        $external_section = $this->create_test_data(true);

        // Just close the subject instance directly as using the manual close
        // would close the sections as well, which is already covered
        $subject_instance = $external_section->participant_instance->subject_instance;
        $subject_instance->availability = closed::get_code();
        $subject_instance->save();

        $this->setUser(null);

        $section_elements = $external_section->section_elements;

        $token = $external_section->participant_instance->external_participant->token;

        $answers = [];
        $i = 1;
        foreach ($section_elements as $section_element) {
            $encoded_response = json_encode('response '.$i);
            $answers[] = [
                'section_element_id' => $section_element->id,
                'response_data' => $encoded_response,
            ];
            $i++;
        }

        $args = [
            'input' => [
                'participant_section_id' => $external_section->id,
                'token' => $token,
                'update' => $answers,
            ],
        ];

        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $this->assertNull($result);
    }


    /**
     * Tests exception is thrown when updating a closed participant section.
     *
     * @return void
    */
    public function test_can_not_update_closed_participant_section_response() {
        $external_section = $this->create_test_data(true);

        $this->setUser(null);

        $section_elements = $external_section->section_elements;

        $token = $external_section->participant_instance->external_participant->token;

        $answers = [];
        $i = 1;
        foreach ($section_elements as $section_element) {
            $encoded_response = json_encode('response '.$i);

            $answers[] = [
                'section_element_id' => $section_element->id,
                'response_data' => $encoded_response,
            ];
            $i++;
        }

        $args = [
            'input' => [
                'participant_section_id' => $external_section->id,
                'token' => $token,
                'update' => $answers,
            ],
        ];

        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $this->assertArrayHasKey('participant_section', $result);
        /** @var participant_section $result */
        $result = $result['participant_section'];
        $this->assertInstanceOf(participant_section::class, $result);
        $this->assertEquals('CLOSED', $result->get_availability_status());

        // Ok so now try to close it again

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Can not update response to a closed participant section');

        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    public function test_success_ajax_query(): void {
        $external_section = $this->create_test_data(true);

        $this->setUser(null);

        $section_elements = $external_section->section_elements;

        $token = $external_section->participant_instance->external_participant->token;

        $answers = [];
        $i = 1;
        foreach ($section_elements as $section_element) {
            $encoded_response = json_encode('response '.$i);
            $answers[] = [
                'section_element_id' => $section_element->id,
                'response_data' => $encoded_response,
            ];
            $i++;
        }

        $args = [
            'input' => [
                'participant_section_id' => $external_section->id,
                'token' => $token,
                'update' => $answers,
            ],
        ];

        $result = $this->parsed_graphql_operation(self::MUTATION_NOSESSION, $args);
        $this->assert_webapi_operation_successful($result);
        $result = $this->get_webapi_operation_data($result);
        $this->assertArrayHasKey('participant_section', $result);
        $result = $result['participant_section'];
        $this->assertEquals($external_section->id, $result['id']);
        $this->assertEquals('COMPLETE', $result['progress_status']);
        $this->assertEquals('CLOSED', $result['availability_status']);
        $this->assertArrayHasKey('section', $result);
        $this->assertArrayHasKey('section_element_responses', $result);
        $this->assertCount(2, $result['section_element_responses']);
    }

    public function test_failed_ajax_query(): void {
        $external_section = $this->create_test_data(true);

        $this->setUser(null);

        $token = $external_section->participant_instance->external_participant->token;

        $args = [
            'input' => [
                'participant_section_id' => $external_section->id,
                'token' => $token,
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
        $result = $this->parsed_graphql_operation(self::MUTATION_NOSESSION, $args);
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
        advanced_feature::enable($feature);
    }

    private function create_test_data(bool $close_on_completion = false) {
        self::setAdminUser();

        $data_generator = self::getDataGenerator();
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = $data_generator->get_plugin_generator('mod_perform');

        $configuration = \mod_perform\testing\activity_generator_configuration::new()
            ->enable_creation_of_manual_participants()
            ->set_number_of_elements_per_section(2)
            ->set_relationships_per_section([constants::RELATIONSHIP_SUBJECT, constants::RELATIONSHIP_EXTERNAL]);

        /** @var activity $activity */
        $activities = $perform_generator->create_full_activities($configuration);
        foreach ($activities as $activity) {
            $activity->settings->update([activity_setting::CLOSE_ON_COMPLETION => $close_on_completion]);
        }

        /** @var participant_section_entity $external_section */
        $external_section = participant_section_entity::repository()
            ->join([participant_instance_entity::TABLE, 'pi'], 'participant_instance_id', 'id')
            ->where('pi.participant_source', participant_source::EXTERNAL)
            ->order_by('id')
            ->first();

        return $external_section;
    }

}