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
use mod_perform\entity\activity\section as section_entity;
use mod_perform\models\activity\section;
use mod_perform\models\activity\activity;
use mod_perform\entity\activity\participant_section as participant_section_entity;
use mod_perform\models\response\view_only_section;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\query\view_only_section_responses
 *
 * @group perform
 */
class mod_perform_webapi_resolver_query_view_only_section_responses_testcase extends advanced_testcase {
    private const QUERY = 'mod_perform_view_only_section_responses';

    use webapi_phpunit_helper;

    public function test_successful_query(): void {
        [$participant_sections, $section_element, $static_section_element] = $this->create_test_data();

        self::setAdminUser();

        /** @var participant_section_entity $participant_section */
        $participant_section = $participant_sections->first();

        $args = [
            'section_id' => $participant_section->section_id,
            'subject_instance_id' => $participant_section->participant_instance->subject_instance_id,
        ];

        $subject_participant_user = user::repository()->find($participant_section->participant_instance->participant_id);

        $this->assert_query_success(
            $args,
            $subject_participant_user,
            $participant_section->section,
            $section_element,
            $static_section_element
        );
    }

    public function test_without_explicit_section_id(): void {
        [$participant_sections, , ] = $this->create_test_data();

        self::setAdminUser();

        /** @var participant_section_entity $participant_section */
        $participant_section = $participant_sections->first();

        $args = [
            'subject_instance_id' => $participant_section->participant_instance->subject_instance_id,
        ];

        $result = $this->resolve_graphql_query(self::QUERY, $args);

        self::assertInstanceOf(view_only_section::class, $result);
        self::assertEquals($participant_section->section_id, $result->get_id());
    }

    /**
     * @param bool $report_on_all
     * @dataProvider permissions_provider
     */
    public function test_permissions_checks(bool $report_on_all): void {
        [$participant_sections,] = $this->create_test_data();

        /** @var participant_section_entity $participant_section */
        $participant_section = $participant_sections->first();

        $reporter = self::getDataGenerator()->create_user();
        $subject = $participant_section->participant_instance->subject_instance->subject_user->to_record();

        $args = [
            'section_id' => $participant_section->section_id,
            'subject_instance_id' => $participant_section->participant_instance->subject_instance_id,
        ];

        self::setUser($reporter);

        $result = $this->resolve_graphql_query(self::QUERY, $args);
        self::assertNull($result);

        if ($report_on_all) {
            $this->assign_report_on_all_role($reporter);
        } else {
            $this->assign_reporter_cap_over_subject($reporter, $subject);
        }

        $result = $this->resolve_graphql_query(self::QUERY, $args);
        self::assertInstanceOf(view_only_section::class, $result);
        self::assertEquals($participant_section->section_id, $result->get_id());
    }

    public function permissions_provider(): array {
        return [
            'report on subject' => [false],
            'report on all' => [true],
        ];
    }

    public function test_failed_ajax_query(): void {
        [$participant_sections,] = $this->create_test_data();

        /** @var participant_section_entity $participant_section */
        $participant_section = $participant_sections->first();

        $args = [
            'section_id' => $participant_section->section_id,
            'subject_instance_id' => $participant_section->participant_instance->subject_instance_id,
        ];

        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
        advanced_feature::enable($feature);

        // If the section does not exist we return an empty result
        $not_existing_section_args = [
            'section_id' => '900',
            'subject_instance_id' => $participant_section->participant_instance->subject_instance_id,
        ];

        $result = $this->parsed_graphql_operation(self::QUERY, $not_existing_section_args);
        $this->assert_webapi_operation_successful($result);
        [$data,] = $result;
        $this->assertNull($data);

        $this->setUser();
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'not logged in');
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

    private function create_section_element_response(int $section_element_id, user $subject_participant_user): array {
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
                    'is_required' => false,
                    'is_respondable' => true,
                    'displays_responses' => true,
                    'children' => [],
                    '__typename' => 'mod_perform_element',
                ],
            'sort_order' => 1,
            'other_responder_groups' => $this->create_other_responder_groups($subject_participant_user),
        ];
    }

    private function create_static_section_element_response(int $section_element_id, user $subject_participant_user): array {
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
                    'is_required' => false,
                    'is_respondable' => false,
                    'displays_responses' => false,
                    'children' => [],
                    '__typename' => 'mod_perform_element',
                ],
            'sort_order' => 2,
            'other_responder_groups' => [],
        ];
    }

    private function create_other_responder_groups(user $subject_participant_user): array {
        $profile_image_small_url = (new user_picture(
            $subject_participant_user->to_record(),
            0
        ))->get_url($GLOBALS['PAGE'])->out(false);

        return [
            [
                'relationship_name' => 'Subject',
                'responses' => [
                    [
                        'participant_instance' => [
                            'participant' => [
                                'profileimageurlsmall' => $profile_image_small_url,
                                'fullname' => $subject_participant_user->fullname,
                                'profileimagealt' => $subject_participant_user->fullname,
                            ]
                        ],
                        'response_data' => null,
                        'response_data_formatted_lines' => [],
                    ]
                ]
            ]
        ];
    }

    /**
     * Check participant section query success
     *
     * @param array $args
     * @param user $subject_participant_user
     * @param section_entity $section
     * @param $section_element
     * @param $static_section_element
     */
    private function assert_query_success(
        array $args,
        user $subject_participant_user,
        section_entity $section,
        $section_element,
        $static_section_element
    ): void {
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertSame($section->title, $result['section']['display_title']);

        $this->assertEquals([
            [
                'id' => $section->id,
                'display_title' => $section->title,
            ]
        ], $result['siblings']);

        $section_element_responses = $result['section_element_responses'];

        $this->assertCount(
            2,
            $section_element_responses,
            'Expected one section element'
        );

        $this->assertEquals(
            $this->create_section_element_response($section_element->id, $subject_participant_user),
            $section_element_responses[0]
        );

        $this->assertEquals(
            $this->create_static_section_element_response($static_section_element->id, $subject_participant_user),
            $section_element_responses[1]
        );
    }

    /**
     * @param stdClass $reporter
     * @param stdClass $subject
     */
    private function assign_reporter_cap_over_subject(stdClass $reporter, stdClass $subject): void {
        $reporter_role_id = create_role(
            'Perform Reporter Role',
            'perform_reporter_role',
            'Can report on perform data'
        );

        $system_context = context_system::instance();
        assign_capability(
            'mod/perform:report_on_subject_responses',
            CAP_ALLOW,
            $reporter_role_id,
            $system_context
        );

        self::getDataGenerator()->role_assign(
            $reporter_role_id,
            $reporter->id,
            context_user::instance($subject->id)
        );
    }

    private function assign_report_on_all_role(stdClass $reporter): void {
        $sys_context = context_system::instance();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('mod/perform:report_on_all_subjects_responses', CAP_ALLOW, $roleid, $sys_context);

        // The role is granted in the user's own context.
        $user_context = context_user::instance($reporter->id);
        role_assign($roleid, $reporter->id, $user_context);
    }

}