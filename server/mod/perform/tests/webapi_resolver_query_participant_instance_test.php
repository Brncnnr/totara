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
 */

use mod_perform\models\activity\participant_instance;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;

require_once(__DIR__ . '/subject_instance_testcase.php');

/**
 * @group perform
 */
class mod_perform_webapi_resolver_query_participant_instance_testcase extends mod_perform_subject_instance_testcase {
    private const QUERY = 'mod_perform_participant_instance';

    use webapi_phpunit_helper;

    public function test_query_successful(): void {
        self::setAdminUser();

        /** @var participant_instance $subject_participant_instance */
        $subject_participant_instance = self::$about_user_and_participating->participant_instances->find(
            function (participant_instance $pi) {
                return (int) $pi->participant_id === (int) self::$about_user_and_participating->subject_user->id;
            }
        );

        $user_fullname = self::$about_user_and_participating->subject_user->fullname;

        $args = [
            'participant_instance_id' => $subject_participant_instance->get_id()
        ];

        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);
        $actual = $this->get_webapi_operation_data($result);

        $expected = [
            'participant' => [
                'fullname' => $user_fullname,
            ],
            'subject_instance' => [
                'subject_user' => [
                    'fullname' => $user_fullname,
                ],
            ],
            'core_relationship' => [
                'name' => 'Subject',
            ],
        ];

        self::assertEquals($expected, $this->strip_expected_dates($actual));
    }

    public function test_get_as_participation_manager(): void {
        /** @var participant_instance $subject_participant_instance */
        $subject_participant_instance = self::$about_user_and_participating->participant_instances->find(
            function (participant_instance $pi) {
                return (int) $pi->participant_id === (int) self::$about_user_and_participating->subject_user->id;
            }
        );

        $args = [
            'participant_instance_id' => $subject_participant_instance->get_id()
        ];

        $manager = self::getDataGenerator()->create_user();
        $employee = self::$about_user_but_not_participating->subject_user;

        self::setUser($manager);

        $context = $this->create_webapi_context(self::QUERY);
        $context->set_relevant_context(self::$about_user_and_participating->get_context());

        $returned_participant_instance = $this->resolve_graphql_query(self::QUERY, $args);
        self::assertNull($returned_participant_instance);

        $this->setup_manager_employee_job_assignment($manager, $employee);

        $returned_participant_instance = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assertEquals($subject_participant_instance->id, $returned_participant_instance->id);
    }

    public function test_get_as_participant(): void {
        /** @var participant_instance $subject_participant_instance */
        $subject_participant_instance = self::$about_user_and_participating->participant_instances->find(
            function (participant_instance $pi) {
                return (int) $pi->participant_id === (int) self::$about_user_and_participating->subject_user->id;
            }
        );

        $args = [
            'participant_instance_id' => $subject_participant_instance->get_id()
        ];

        self::setUser($subject_participant_instance->participant_id);

        $returned_participant_instance = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assertEquals($subject_participant_instance->id, $returned_participant_instance->id);
    }

    public function test_failed_ajax_query(): void {
        /** @var participant_instance $subject_participant_instance */
        $subject_participant_instance = self::$about_user_and_participating->participant_instances->find(
            function (participant_instance $pi) {
                return (int) $pi->participant_id === (int) self::$about_user_and_participating->subject_user->id;
            }
        );

        $args = [
            'participant_instance_id' => $subject_participant_instance->get_id()
        ];

        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
        advanced_feature::enable($feature);

        $result = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assert_webapi_operation_failed($result, 'participant_instance_id');

        self::setUser();
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'not logged in');
    }

}