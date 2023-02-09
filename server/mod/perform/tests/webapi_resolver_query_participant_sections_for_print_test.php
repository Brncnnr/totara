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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\constants;
use mod_perform\models\activity\section;
use mod_perform\models\activity\activity;
use mod_perform\models\response\participant_section;
use totara_core\advanced_feature;
use totara_webapi\graphql;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group perform
 */
class mod_perform_webapi_resolver_query_participant_sections_for_print_testcase extends advanced_testcase {
    private const OPERATION = 'mod_perform_participant_sections_for_print';
    private const PARTICIPANT_SECTIONS_QUERY = 'mod_perform_participant_sections';

    use webapi_phpunit_helper;

    public function test_query_with_results(): void {
        // Creates 3 sections (1 respondable, 1 static, 1 of each).
        $participant_sections = $this->create_test_data();

        $participant_user_id = $participant_sections[0]->participant_instance->participant->id;
        self::setUser($participant_user_id);

        $participant_instance_id = $participant_sections[0]->participant_instance->id;

        $args = ['participant_instance_id' => $participant_instance_id];

        $result = $this->parsed_graphql_operation(self::OPERATION, $args, graphql::TYPE_AJAX, true);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);

        $participant_instance = $result['mod_perform_participant_instance'];

        self::assertEquals(constants::RELATIONSHIP_MANAGER, $participant_instance['core_relationship']['idnumber']);
        self::assertEquals('Manager', $participant_instance['core_relationship']['name']);

        $participant_sections = $result['mod_perform_participant_sections'];

        self::assertCount(3, $participant_sections);

        $element_responses1 =  $participant_sections[0]['section_element_responses'];
        self::assertCount(1, $element_responses1);
        self::assertTrue($element_responses1[0]['element']['is_respondable']);

        $element_responses2 =  $participant_sections[1]['section_element_responses'];
        self::assertCount(1, $element_responses2);
        self::assertFalse($element_responses2[0]['element']['is_respondable']);

        $element_responses3 =  $participant_sections[2]['section_element_responses'];
        self::assertCount(2, $element_responses3);
        self::assertTrue($element_responses3[0]['element']['is_respondable']);
        self::assertFalse($element_responses3[1]['element']['is_respondable']);
    }

    public function test_participant_sections_do_not_belong_to_user(): void {
        $participant_sections = $this->create_test_data();

        $user = self::getDataGenerator()->create_user();
        self::setUser($user);

        $participant_instance_id = $participant_sections[0]->participant_instance->id;

        $args = ['participant_instance_id' => $participant_instance_id];

        $result = $this->resolve_graphql_query(self::PARTICIPANT_SECTIONS_QUERY, $args);

        self::assertEquals([], $result);
    }

    public function test_subject_is_deleted(): void {
        // Creates 3 sections (1 respondable, 1 static, 1 of each).
        $participant_sections = $this->create_test_data();

        $subject_user = $participant_sections[0]->participant_instance->subject_instance->subject_user;
        delete_user($subject_user->get_user()->to_record());

        $participant_user_id = $participant_sections[0]->participant_instance->participant->id;
        self::setUser($participant_user_id);

        $participant_instance_id = $participant_sections[0]->participant_instance->id;

        $args = ['participant_instance_id' => $participant_instance_id];

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Invalid activity');
        $this->resolve_graphql_query(self::PARTICIPANT_SECTIONS_QUERY, $args);
    }

    public function test_failed_ajax_query(): void {
        $participant_sections = $this->create_test_data();

        $participant_instance = $participant_sections[0]->participant_instance;
        $args = ['participant_instance_id' => $participant_instance->id];

        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::OPERATION, $args, graphql::TYPE_AJAX, true);
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
        advanced_feature::enable($feature);

        self::setUser();
        $result = $this->parsed_graphql_operation(self::OPERATION, $args, graphql::TYPE_AJAX, true);
        $this->assert_webapi_operation_failed($result, 'not logged in');
    }

    /**
     * @return participant_section[]
     */
    private function create_test_data(): array {
        self::setAdminUser();

        $configuration = \mod_perform\testing\activity_generator_configuration::new()
            ->enable_appraiser_for_each_subject_user()
            ->enable_manager_for_each_subject_user()
            ->set_relationships_per_section(
                [
                    constants::RELATIONSHIP_SUBJECT,
                    constants::RELATIONSHIP_MANAGER,
                    constants::RELATIONSHIP_APPRAISER,
                ]
            )
            ->set_number_of_sections_per_activity(3);

        /** @var activity $activity */
        $activity = $this->get_perform_generator()->create_full_activities($configuration)->first();
        /** @var section $section */
        $sections = $activity->sections->all();

        $this->create_respondable_section_element($sections[0]);

        $this->create_static_section_element($sections[1]);

        $this->create_respondable_section_element($sections[2]);
        $this->create_static_section_element($sections[2]);

        return $activity->sections->map(function (section $section) {
            return $section->get_participant_sections()
                // Filter out the subject.
                ->filter(function (participant_section $participant_section) {
                    return $participant_section->participant_instance->core_relationship->idnumber === constants::RELATIONSHIP_MANAGER;
                })
                // Always get the same participant.
                ->sort('participant_instance_id')
                ->first();
        })->all();
    }

    private function create_respondable_section_element(section $section): void {
        $element = $this->get_perform_generator()->create_element();

        $this->get_perform_generator()->create_section_element($section, $element);
    }

    private function create_static_section_element(section $section): void {
        $element = $this->get_perform_generator()->create_element(['plugin_name' => 'static_content']);

        $this->get_perform_generator()->create_section_element($section, $element);
    }

    private function get_perform_generator(): \mod_perform\testing\generator {
        return \mod_perform\testing\generator::instance();
    }
}