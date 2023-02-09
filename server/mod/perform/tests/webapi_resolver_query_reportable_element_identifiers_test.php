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
* @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package mod_perform
 */

use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\query\reportable_element_identifiers
 *
 * @group perform
 */
class mod_perform_webapi_resolver_query_reportable_element_identifiers_testcase extends advanced_testcase {
    private const QUERY = 'mod_perform_reportable_element_identifiers';

    use webapi_phpunit_helper;

    public function test_get_identifiers_for_normal_user(): void {
        $user = $this->getDataGenerator()->create_user();
        self::setAdminUser();
        $this->create_test_data();
        self::setUser($user);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage("You do not have permission to view reporting identifiers");
        $this->resolve_graphql_query(self::QUERY, []);
    }

    public function test_get_identifiers_for_report_admin(): void {
        $user = $this->getDataGenerator()->create_user();
        // The capability is added to the role in the system context.
        $sys_context = context_system::instance();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('mod/perform:report_on_all_subjects_responses', CAP_ALLOW, $roleid, $sys_context);

        // The role is granted in the user's own context.
        $user_context = \context_user::instance($user->id);
        role_assign($roleid, $user->id, $user_context);
        self::setAdminUser();
        $data = $this->create_test_data();
        self::setUser($user);

        $returned_identifiers = $this->resolve_graphql_query(self::QUERY, []);
        $this->assertEqualsCanonicalizing(
            [$data->identifier1->identifier, $data->identifier2->identifier],
            [$returned_identifiers->first()->identifier, $returned_identifiers->last()->identifier]
        );
    }

    public function test_successful_ajax_call(): void {
        self::setAdminUser();

        $data = $this->create_test_data();

        $result = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assert_webapi_operation_successful($result);

        $returned_identifiers = $this->resolve_graphql_query(self::QUERY, []);
        $this->assertEqualsCanonicalizing(
            [$data->identifier1->identifier, $data->identifier2->identifier],
            [$returned_identifiers->first()->identifier, $returned_identifiers->last()->identifier]
        );
    }

    public function test_failed_ajax_query(): void {
        self::setAdminUser();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();
        $element = $perform_generator->create_element();

        $args = [];

        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
        advanced_feature::enable($feature);

        self::setUser();
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'not logged in');
    }

    private function create_test_data(): stdClass {
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();

        $data = new stdClass();
        $data->identifier1 = $perform_generator->create_element_identifier('test_identifier_1');
        $data->identifier2 = $perform_generator->create_element_identifier('test_identifier_2');
        $data->identifier3 = $perform_generator->create_element_identifier('test_identifier_3');

        $data->activity1 = $perform_generator->create_full_activities()->first();
        $section = $data->activity1->sections->first();

        $element1 = $perform_generator->create_element(['identifier'=>'test_identifier_1' ]);
        $element2 = $perform_generator->create_element(['identifier'=>'test_identifier_2' ]);
        $data->section_element = $perform_generator->create_section_element($section, $element1);
        $data->section_element = $perform_generator->create_section_element($section, $element2);

        return $data;
    }
}