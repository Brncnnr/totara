<?php
/**
 * This file is part of Totara Perform
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_perform
 */

use core_phpunit\testcase;
use mod_perform\entity\activity\subject_instance;
use mod_perform\testing\generator;
use mod_perform\testing\activity_generator_configuration;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\mutation\manually_delete_subject_instance
 *
 * @group perform
 */
class mod_perform_webapi_resolver_mutation_manually_delete_subject_instance_testcase extends testcase {
    private const MUTATION = 'mod_perform_manually_delete_subject_instance';

    use webapi_phpunit_helper;

    public function test_manually_delete_subject_instance_opened(): void {
        [$subject_instance, $args] = $this->generate_data();

        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        self::assertTrue($result);
        self::assertNull(subject_instance::repository()->find($subject_instance->id));
    }

    public function test_manually_delete_subject_instance_closed(): void {

        [$subject_instance, $args] = $this->generate_data();

        // Set to closed.
        $input = [
            'input' => [
                'subject_instance_id' => $subject_instance->id,
                'availability' => 'CLOSED',
            ],
        ];
        $result = $this->parsed_graphql_operation('mod_perform_manually_change_subject_instance', $input);
        $this->assert_webapi_operation_successful($result);

        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        self::assertTrue($result);
        self::assertNull(subject_instance::repository()->find($subject_instance->id));
    }

    /**
     * Test the mutation through the GraphQL stack.
     */
    public function test_execute_query_successful(): void {
        [$subject_instance, $args] = $this->generate_data();

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        self::assertTrue($result);
        self::assertNull(subject_instance::repository()->find($subject_instance->id));
    }

    public function test_failed_ajax_query_id_null(): void {
        $result = $this->parsed_graphql_operation(self::MUTATION, []);
        $this->assert_webapi_operation_failed(
            $result,
            'Variable "$input" of required type "mod_perform_manually_delete_subject_input!'
        );
    }

    public function test_failed_ajax_query_not_logged_in(): void {
        [$subject_instance, $args] = $this->generate_data();

        $this->setUser(0);
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed(
            $result,
            'Course or activity not accessible. (You are not logged in)'
        );
    }

    public function test_failed_ajax_query_id_zero(): void {
        [$subject_instance, $args] = $this->generate_data();

        $args['input']['subject_instance_id'] = 0;
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed(
            $result,
            'Invalid parameter value detected (invalid subject instance id)'
        );
    }

    public function test_failed_ajax_query_id_999(): void {
        [$subject_instance, $args] = $this->generate_data();

        $args['input']['subject_instance_id'] = 999;
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed(
            $result,
            'Invalid activity'
        );
    }

    public function test_failed_ajax_query_guestuser(): void {
        [$subject_instance, $args] = $this->generate_data();

        self::setGuestUser();
        $args['input']['subject_instance_id'] = $subject_instance->id;
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed(
            $result,
            'Course or activity not accessible. (Must be an authenticated user)'
        );
    }

    public function test_failed_ajax_query_learner_user(): void {
        [$subject_instance, $args] = $this->generate_data();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $args['input']['subject_instance_id'] = $subject_instance->id;
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed(
            $result,
            'You do not have permission to manage participation of the subject'
        );
    }

    private function generate_data() {
        $this->setAdminUser();

        $configuration = activity_generator_configuration::new()
            ->set_number_of_activities(1)
            ->set_number_of_sections_per_activity(1)
            ->set_relationships_per_section(['subject'])
            ->set_number_of_users_per_user_group_type(1)
            ->set_number_of_elements_per_section(0);

        /** @var \mod_perform\testing\generator $generator */
        $generator = generator::instance();
        $generator->create_full_activities($configuration);

        /** @var subject_instance $subject_instance */
        $subject_instance = subject_instance::repository()->get()->first();

        $args = [
            'input' => [
                'subject_instance_id' => $subject_instance->id
            ],
        ];
        return [$subject_instance, $args];
    }
}