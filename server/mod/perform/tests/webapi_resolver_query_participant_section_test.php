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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_perform
 */

use core\entity\user;
use mod_perform\constants;
use mod_perform\entity\activity\participant_instance;
use mod_perform\entity\activity\subject_instance;
use mod_perform\entity\activity\element_response;
use mod_perform\entity\activity\section_element;
use mod_perform\models\activity\section;
use mod_perform\models\activity\activity;
use mod_perform\entity\activity\participant_section as participant_section_entity;
use mod_perform\testing\activity_generator_configuration;
use totara_core\advanced_feature;
use totara_job\job_assignment;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\query\participant_section
 *
 * @group perform
 */
class mod_perform_webapi_resolver_query_participant_section_testcase extends advanced_testcase {
    private const QUERY = 'mod_perform_participant_section';

    use webapi_phpunit_helper;

    public function test_get_participant_section_by_participant_instance_id(): void {
        [$participant_sections, $section_element, $static_section_element] = $this->create_test_data();

        foreach ($participant_sections as $participant_section) {
            self::setUser($participant_section->participant_instance->participant_user->id);

            $args = ['participant_instance_id' => $participant_section->participant_instance->id];

            $this->assert_query_success($args, $participant_section, $section_element, $static_section_element);
        }
    }

    public function test_get_participant_section_by_participant_section_id(): void {
        [$participant_sections, $section_element, $static_section_element] = $this->create_test_data();

        foreach ($participant_sections as $participant_section) {
            self::setUser($participant_section->participant_instance->participant_user->id);

            $args = ['participant_section_id' => $participant_section->id];

            $this->assert_query_success($args, $participant_section, $section_element, $static_section_element);
        }
    }

    public function test_get_participant_section_by_participant_section_id_and_participant_instance_id(): void {
        [$participant_sections, $section_element, $static_section_element] = $this->create_test_data();

        foreach ($participant_sections as $participant_section) {
            self::setUser($participant_section->participant_instance->participant_user->id);

            $args = [
                'participant_instance_id' => $participant_section->participant_instance->id,
                'participant_section_id'  => $participant_section->id,
            ];

            $this->assert_query_success($args, $participant_section, $section_element, $static_section_element);
        }
    }

    public function test_response_data_formats(): void {
        [$participant_sections, ,] = $this->create_test_data();

        $raw_response = '<script>alert(1)</script><b>bold</b>';

        $participant_section = $participant_sections->first();

        /** @var section_element $section_element */
        foreach ($participant_section->section_elements as $section_element) {
            $element_response = new element_response();
            $element_response->participant_instance_id = $participant_section->participant_instance_id;
            $element_response->section_element_id = $section_element->id;

            $element_response->response_data = json_encode($raw_response);
            $element_response->save();
        }

        self::setUser($participant_section->participant_instance->participant_user->id);

        $args = ['participant_section_id' => $participant_section->id];

        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);
        $result = $this->get_webapi_operation_data($result);
        $section_element_responses = $result['section_element_responses'];

        $respondable_element_response = $section_element_responses[0];
        self::assertEquals('"alert(1)bold"', $respondable_element_response['response_data']);
        self::assertEquals('"<script>alert(1)<\/script><b>bold<\/b>"', $respondable_element_response['response_data_raw']);
        self::assertEquals('alert(1)bold', $respondable_element_response['response_data_formatted_lines'][0]);
    }

    public function test_failed_ajax_query(): void {
        [$participant_sections,] = $this->create_test_data();
        $participant_instance = $participant_sections->first()->participant_instance;
        $args = ['participant_instance_id' => $participant_instance->id];

        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
        advanced_feature::enable($feature);

        // fail if does not provide any parameter
        $result = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assert_webapi_operation_failed(
            $result, 'At least one parameter is required, either participant_instance_id or participant_section_id'
        );

        // fail if participant_instance_id is invalid
        $result = $this->parsed_graphql_operation(self::QUERY, ['participant_instance_id' => 0]);
        $this->assert_webapi_operation_failed(
            $result, 'At least one parameter is required, either participant_instance_id or participant_section_id'
        );

        // fail if participant_section_id is invalid
        $result = $this->parsed_graphql_operation(self::QUERY, ['participant_section_id' => 0]);
        $this->assert_webapi_operation_failed(
            $result, 'At least one parameter is required, either participant_instance_id or participant_section_id'
        );

        // fail if not participant section related to the participant_instance_id
        $result = $this->parsed_graphql_operation(self::QUERY, ['participant_instance_id' => 1293]);
        $this->assert_webapi_operation_failed($result, "No participant section");

        // If the section does not exist we return an empty result
        $result = $this->parsed_graphql_operation(self::QUERY, ['participant_section_id' => 1293]);
        $this->assert_webapi_operation_successful($result);
        [$data, ] = $result;
        self::assertNull($data);

        self::setUser();
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'not logged in');
    }

    public function delete_or_suspend_data_provider(): array {
        return [
            'Deleted user should be hidden' => [
                static function (user $user) {
                    delete_user($user->to_record());
                }
            ],
            'Suspended user should be hidden with "hide_suspended_users" switched on' => [
                static function (user $user) {
                    set_config('perform_hide_suspended_users', 1);
                    user_suspend_user($user->id);
                }
            ],
            'Suspended user should NOT be hidden with "hide_suspended_users" switched off' => [
                static function (user $user) {
                    set_config('perform_hide_suspended_users', 0);
                    user_suspend_user($user->id);
                },
                false
            ],
            'The setting "perform_close_suspended_user_instances" should not have any effect' => [
                static function (user $user) {
                    set_config('perform_close_suspended_user_instances', 1);
                    user_suspend_user($user->id);
                },
                false
            ],
        ];
    }

    /**
     * @dataProvider delete_or_suspend_data_provider
     * @param closure $delete_or_suspend
     * @param bool $should_be_hidden
     * @return void
     */
    public function test_other_responses_if_participant_got_deleted_or_suspended(
        closure $delete_or_suspend,
        bool $should_be_hidden = true
    ): void {
        self::setAdminUser();

        $data_generator = self::getDataGenerator();
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = $data_generator->get_plugin_generator('mod_perform');

        $configuration = activity_generator_configuration::new()
            ->set_number_of_activities(1)
            ->set_number_of_tracks_per_activity(1)
            ->set_cohort_assignments_per_activity(1)
            ->set_number_of_users_per_user_group_type(1)
            ->set_number_of_elements_per_section(3)
            ->enable_appraiser_for_each_subject_user()
            ->enable_manager_for_each_subject_user()
            ->set_relationships_per_section(
                [
                    constants::RELATIONSHIP_SUBJECT,
                    constants::RELATIONSHIP_MANAGER,
                    constants::RELATIONSHIP_APPRAISER
                ]
            );

        $perform_generator->create_full_activities($configuration)->first();

        // Find the participant_section for the subject user.
        /** @var subject_instance $subject_instance */
        $subject_instance = subject_instance::repository()->one();
        /** @var participant_instance $participant_instance */
        $participant_instance = participant_instance::repository()
            ->where('participant_id', $subject_instance->subject_user_id)
            ->one();
        /** @var participant_section_entity $participant_section */
        $participant_section = participant_section_entity::repository()
            ->where('participant_instance_id', $participant_instance->id)
            ->one();

        $args = ['participant_section_id' => $participant_section->id];

        $participant = $participant_section->participant_instance->participant_user;
        self::setUser($participant->to_record());

        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);

        $section_element_responses = $result['section_element_responses'];
        self::assertCount(3, $section_element_responses);
        foreach ($section_element_responses as $section_element_response) {
            // Manager and appraiser both show up
            self::assertCount(2, $section_element_response['other_responder_groups']);
            foreach ($section_element_response['other_responder_groups'] as $other_responder_groups) {
                self::assertArrayHasKey('relationship_name', $other_responder_groups);
                self::assertArrayHasKey('responses', $other_responder_groups);
                self::assertNotEmpty($other_responder_groups['responses']);
            }
        }

        $job = job_assignment::get_first($participant->id);

        /** @var user $appraiser */
        $appraiser = user::repository()->find_or_fail($job->appraiserid);

        $delete_or_suspend($appraiser);

        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $section_element_responses = $result['section_element_responses'];
        self::assertCount(3, $section_element_responses);
        foreach ($section_element_responses as $section_element_response) {
            // Only one is left, the manager
            self::assertCount(2, $section_element_response['other_responder_groups']);
            foreach ($section_element_response['other_responder_groups'] as $other_responder_groups) {
                self::assertArrayHasKey('relationship_name', $other_responder_groups);
                self::assertArrayHasKey('responses', $other_responder_groups);
                if ($other_responder_groups['relationship_name'] === 'Manager' || !$should_be_hidden) {
                    self::assertNotEmpty($other_responder_groups['responses']);
                }
            }
        }
    }

    private function create_test_data(): array {
        self::setAdminUser();

        $data_generator = self::getDataGenerator();
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = $data_generator->get_plugin_generator('mod_perform');

        /** @var activity $activity */
        $activity = $perform_generator->create_full_activities()->first();
        /** @var section $section */
        $section = $activity->sections->first();

        $element = $perform_generator->create_element();
        $section_element = $perform_generator->create_section_element($section, $element);

        $static_element = $perform_generator->create_element(['plugin_name' => 'static_content']);
        $static_section_element = $perform_generator->create_section_element($section, $static_element);

        $participant_sections = participant_section_entity::repository()
            ->order_by('id', 'desc')
            ->get();

        return [$participant_sections, $section_element, $static_section_element];
    }

    private function create_response_format_test_data(): array {
        self::setAdminUser();

        $data_generator = self::getDataGenerator();
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = $data_generator->get_plugin_generator('mod_perform');

        /** @var activity $activity */
        $activity = $perform_generator->create_full_activities()->first();
        /** @var section $section */
        $section = $activity->sections->first();

        $element = $perform_generator->create_element();
        $section_element = $perform_generator->create_section_element($section, $element);

        $static_element = $perform_generator->create_element(['plugin_name' => 'static_content']);
        $static_section_element = $perform_generator->create_section_element($section, $static_element);

        $participant_sections = participant_section_entity::repository()
            ->order_by('id', 'desc')
            ->get();

        return [$participant_sections, $section_element, $static_section_element];
    }

    private function create_section_element_response(int $section_element_id): array {
        return [
            'section_element_id' => $section_element_id,
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
                    'title' => 'test element title',
                    'data' => null,
                    'children' => [],
                    '__typename' => 'mod_perform_element',
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
            'other_responder_groups' => [],
            'visible_to' => [],
        ];
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
            'response_data_formatted_lines' => [],
            'validation_errors' => [],
            'other_responder_groups' => [],
            'visible_to' => [],
        ];
    }

    /**
     * Check participant section query success
     *
     * @param array $args
     * @param $participant_section
     * @param $section_element
     * @param $static_section_element
     */
    private function assert_query_success(array $args, $participant_section, $section_element, $static_section_element): void {
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        self::assertEquals($participant_section->id, $result['id']);
        self::assertSame($participant_section->section->title, $result['section']['display_title']);
        self::assertSame('IN_PROGRESS', $result['progress_status']);


        self::assertCount(1, $result['answerable_participant_instances']);
        self::assertSame('Subject', $result['answerable_participant_instances'][0]['core_relationship']['name']);

        $section_element_responses = $result['section_element_responses'];

        self::assertCount(
            $participant_section->section->section_elements->count(),
            $section_element_responses,
            'Expected section elements count do not match'
        );
        self::assertEquals(
            $this->create_section_element_response($section_element->id),
            $section_element_responses[0]
        );
        self::assertEquals(
            $this->create_static_section_element_response($static_section_element->id),
            $section_element_responses[1]
        );
    }
}