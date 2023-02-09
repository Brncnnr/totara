<?php
/*
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
 */

use mod_perform\constants;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\entity\activity\participant_section as participant_section_entity;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\participant_source;
use mod_perform\models\activity\section;
use mod_perform\models\response\participant_section as participant_section_model;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\query\participant_section_external_participant
 *
 * @group perform
 */
class mod_perform_webapi_resolver_query_participant_section_external_participant_testcase extends advanced_testcase {
    private const QUERY = 'mod_perform_participant_section_external_participant';
    private const QUERY_NOSESSION = self::QUERY . '_nosession';

    use webapi_phpunit_helper;

    protected function get_participant_section_data(): array {
        [$external_section, , ] = $this->create_test_data();

        /** @var participant_section_entity $external_section2 */
        $external_section2 = participant_section_entity::repository()
            ->join([participant_instance_entity::TABLE, 'pi'], 'participant_instance_id', 'id')
            ->where('pi.participant_source', participant_source::EXTERNAL)
            ->where('pi.subject_instance_id', '<>', $external_section->participant_instance->subject_instance_id)
            ->order_by('id')
            ->first();

        /** @var participant_section_entity $closed_section */
        $closed_section = participant_section_entity::repository()
            ->join([participant_instance_entity::TABLE, 'pi'], 'participant_instance_id', 'id')
            ->where('pi.participant_source', participant_source::EXTERNAL)
            ->where('pi.subject_instance_id', '<>', $external_section->participant_instance->subject_instance_id)
            ->where('pi.subject_instance_id', '<>', $external_section2->participant_instance->subject_instance_id)
            ->order_by('id')
            ->first();

        $closed_section_model = participant_section_model::load_by_entity($closed_section);
        $closed_section_model->participant_instance->subject_instance->manually_close();

        $external_token_closed = $closed_section->participant_instance->external_participant->token;

        /** @var $internal_section participant_section_entity */
        $internal_section = participant_section_entity::repository()
            ->join([participant_instance_entity::TABLE, 'pi'], 'participant_instance_id', 'id')
            ->where('pi.participant_source', participant_source::INTERNAL)
            ->order_by('id')
            ->first();

        $external_token = $external_section->participant_instance->external_participant->token;

        $user = $this->getDataGenerator()->create_user();

        return [
            'instance - successful' => [(int) $external_section->participant_instance_id, null, $external_token, null, (int) $external_section->id],
            'instance - closed' => [(int) $closed_section->participant_instance_id, null, $external_token_closed, null, null],
            'instance - token and instance dont match' => [(int)$external_section2->participant_instance_id, null, $external_token, null, null],
            'instance - empty token' => [(int) $external_section->participant_instance_id, null, '', null, null],
            'instance - invalid token' => [(int) $external_section->participant_instance_id, null, 'idontexist', null, null],
            'instance - logged in user' => [(int) $external_section->participant_instance_id, null, $external_token, $user, null],
            'instance - token does not match instance id' => [(int) $internal_section->participant_instance_id, null, $external_token, null, null],
            'section - successful' => [null, (int) $external_section->id, $external_token, null, (int) $external_section->id],
            'section - closed' => [null, (int) $closed_section->id, $external_token_closed, null, null],
            'section - empty token' => [null, (int) $external_section->id, '', null, null],
            'section - invalid token' => [null, (int) $external_section->id, 'idontexist', null, null],
            'section - logged in user' => [null, (int) $external_section->id, $external_token, $user, null],
            'section - token does not match instance id' => [null, (int) $internal_section->id, $external_token, null, null],
            'both - successful' => [(int) $external_section->participant_instance_id, (int)$external_section->id, $external_token, null, (int) $external_section->id],
            'both - closed' => [(int) $closed_section->participant_instance_id, (int) $closed_section->id, $external_token_closed, null, null],
            'both - token and instance dont match' => [(int) $external_section2->participant_instance_id, (int) $external_section2->id, $external_token, null, null],
            'both - empty token' => [(int) $external_section->participant_instance_id, (int) $external_section->id, '', null, null],
            'both - invalid token' => [(int) $external_section->participant_instance_id, (int) $external_section->id, 'idontexist', null, null],
            'both - logged in user' => [(int) $external_section->participant_instance_id, (int) $external_section->id, $external_token, $user, null],
            'both - token does not match instance id' => [(int) $external_section->participant_instance_id, (int) $internal_section->id, $external_token, null, null],
        ];
    }

    public function test_resolve_participant_section() {
        // Deliberately not using a PHPUnit dataprovider here as we
        // don't want the data to be reset after each dataset
        $datasets = $this->get_participant_section_data();

        foreach ($datasets as $name => $dataset) {
            $this->run_test_resolve_participant_section($name, ...$dataset);
        }
    }

    /**
     * @param string $dataset_name
     * @param int|null $participant_instance_id
     * @param int|null $participant_section_id
     * @param string|null $token
     * @param stdClass|null $logged_in_user
     * @param int|null $expected_result
     */
    public function run_test_resolve_participant_section(
        string $dataset_name,
        ?int $participant_instance_id,
        ?int $participant_section_id,
        ?string $token,
        ?stdClass $logged_in_user,
        ?int $expected_result
    ): void {
        $this->setUser($logged_in_user);

        $args = [];
        if ($participant_instance_id !== null) {
            $args['participant_instance_id'] = $participant_instance_id;
        }
        if ($participant_section_id !== null) {
            $args['participant_section_id'] = $participant_section_id;
        }
        if ($token !== null) {
            $args['token'] = $token;
        }

        $result = $this->resolve_graphql_query(self::QUERY, $args);
        if ($expected_result === null) {
            if ($result !== null) {
                $this->fail('Error in dataset \'' . $dataset_name . '\': Expected no return value.');
            }
        } else {
            $this->assertInstanceOf(
                participant_section_model::class,
                $result,
                'Error in dataset \'' . $dataset_name . '\': Expected section model instance.'
            );
            $this->assertEquals(
                $expected_result,
                $result->id,
                'Error in dataset \'' . $dataset_name . '\': Returned id does not match.'
            );
        }
    }

    public function test_successful_ajax_query(): void {
        /** @var $external_section participant_section_entity */
        [$external_section, $section_element, $static_section_element] = $this->create_test_data();

        $external_token = $external_section->participant_instance->external_participant->token;

        $this->setUser();

        $args = [
            'participant_instance_id' => $external_section->participant_instance_id,
            'token' => $external_token
        ];

        $result = $this->parsed_graphql_operation(self::QUERY_NOSESSION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertEquals($external_section->id, $result['id']);
        $this->assertSame($external_section->section->title, $result['section']['display_title']);
        $this->assertSame('IN_PROGRESS', $result['progress_status']);


        $this->assertCount(1, $result['answerable_participant_instances']);
        $this->assertSame('External respondent', $result['answerable_participant_instances'][0]['core_relationship']['name']);

        $section_element_responses = $result['section_element_responses'];

        $this->assertCount(
            $external_section->section->section_elements->count(),
            $section_element_responses,
            'Expected section elements count do not match'
        );

        $section_element_ids = array_column($section_element_responses, 'section_element_id');
        $this->assertContainsEquals($section_element->id, $section_element_ids);

        $subject_user = $external_section->participant_instance->subject_instance->subject_user;

        $expected = [
            'section_element_id' => $section_element->id,
            'element' =>
                [
                    'element_plugin' =>
                        [
                            'participant_form_component' =>
                                'performelement_short_text/components/ShortTextParticipantForm',
                            'participant_response_component' =>
                                'mod_perform/components/element/participant_form/ResponseDisplay',
                            'child_element_config' => [
                                'supports_child_elements' => false,
                                'supports_repeating_child_elements' => false,
                                'repeating_item_identifier' => null,
                                'child_element_responses_identifier' => null,
                            ],
                        ],
                    '__typename' => 'mod_perform_element',
                    'title' => 'test element title',
                    'data' => null,
                    'children' => [],
                    'is_required' => false,
                    'is_respondable' => true,
                    'displays_responses' => true,
                ],
            'sort_order' => 1,
            'can_respond' => true,
            'response_data' => null,
            'response_data_raw' => null,
            'response_data_formatted_lines' => [],
            'validation_errors' => [],
            'other_responder_groups' => [
                [
                    'relationship_name' => 'Subject',
                    'responses' => [
                        [
                            'participant_instance' => [
                                'participant' => [
                                    'fullname' => $subject_user->fullname,
                                    'profileimageurlsmall' => self::get_default_image_url(),
                                    'profileimagealt' => $subject_user->fullname,
                                ]
                            ],
                            'response_data' => null,
                            'response_data_formatted_lines' => [],
                        ]
                    ]
                ]
            ],
            'visible_to' => [],
        ];
        $this->assertEquals($expected, $section_element_responses[0]);
        $this->assertContainsEquals($static_section_element->id, $section_element_ids);

        $this->assertEquals($this->create_static_section_element_response($static_section_element->id), $section_element_responses[1]);
        $this->assertEquals((int)$section_element->id, (int)$section_element_responses[0]['section_element_id']);
    }

    private function create_static_section_element_response(int $section_element_id): array {
        return [
            'section_element_id' => $section_element_id,
            'element' =>
                [
                    'element_plugin' =>
                        [
                            'participant_form_component' =>
                                'performelement_static_content/components/StaticContentParticipantForm',
                            'participant_response_component' => null,
                            'child_element_config' => [
                                'supports_child_elements' => false,
                                'supports_repeating_child_elements' => false,
                                'repeating_item_identifier' => null,
                                'child_element_responses_identifier' => null,
                            ],
                        ],
                    'title' => 'test element title',
                    'data' => null,
                    'children' => [],
                    '__typename' => 'mod_perform_element',
                    'is_required' => false,
                    'is_respondable' => false,
                    'displays_responses' => false,
                ],
            'sort_order' => 2,
            'can_respond' => false,
            'response_data' => null,
            'response_data_raw' => null,
            'validation_errors' => [],
            'other_responder_groups' => [],
            'visible_to' => [],
            'response_data_formatted_lines' => [],
        ];
    }

    public function test_failed_ajax_query(): void {
        [$external_section, , ] = $this->create_test_data();

        $external_token = $external_section->participant_instance->external_participant->token;

        $user = $this->getDataGenerator()->create_user();

        $this->setUser();

        $args = [
            'participant_instance_id' => $external_section->participant_instance_id,
            'token' => $external_token
        ];

        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::QUERY_NOSESSION, $args);
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
        advanced_feature::enable($feature);

        $this->setUser($user);

        [$result, $errors] = $this->parsed_graphql_operation(self::QUERY_NOSESSION, $args);
        $this->assertNull($result);
        $this->assertNull($errors);
    }

    private function create_test_data(): array {
        self::setAdminUser();

        $data_generator = self::getDataGenerator();
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = $data_generator->get_plugin_generator('mod_perform');

        $configuration = \mod_perform\testing\activity_generator_configuration::new()
            ->enable_creation_of_manual_participants()
            ->set_relationships_per_section([constants::RELATIONSHIP_SUBJECT, constants::RELATIONSHIP_EXTERNAL]);

        /** @var activity $activity */
        $perform_generator->create_full_activities($configuration);

        /** @var participant_section_entity $external_section */
        $external_section = participant_section_entity::repository()
            ->join([participant_instance_entity::TABLE, 'pi'], 'participant_instance_id', 'id')
            ->where('pi.participant_source', participant_source::EXTERNAL)
            ->order_by('id')
            ->first();

        $section = section::load_by_entity($external_section->section);

        $element = $perform_generator->create_element();
        $section_element = $perform_generator->create_section_element($section, $element);

        $static_element = $perform_generator->create_element(['plugin_name' => 'static_content']);
        $static_section_element = $perform_generator->create_section_element($section, $static_element);

        return [$external_section, $section_element, $static_section_element];
    }

    private static function get_default_image_url(): moodle_url {
        global $PAGE;
        $renderer = $PAGE->get_renderer('core');
        return $renderer->image_url('u/f2');
    }

}
