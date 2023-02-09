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
 * @coversDefaultClass \mod_perform\webapi\resolver\query\section_admin
 *
 * @group perform
 */
class mod_perform_webapi_resolver_query_section_testcase extends advanced_testcase {
    private const QUERY = 'mod_perform_section_admin';

    use webapi_phpunit_helper;

    public function test_get_section() {
        [$data, $args] = $this->get_test_data();

        $section = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assertSame($section->title, $data->section1->title);
    }

    public function test_successful_ajax_call(): void {
        [$data, $args,] = $this->get_test_data();

        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);

        $section = $this->get_webapi_operation_data($result);
        $this->assertSame($data->section1->display_title, $section['display_title'], 'wrong section title');
    }

    public function test_failed_ajax_query(): void {
        [, $args] = $this->get_test_data();

        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
        advanced_feature::enable($feature);

        $result = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assert_webapi_operation_failed($result, 'Variable "$section_id" of required type "core_id!" was not provided.');

        $result = $this->parsed_graphql_operation(self::QUERY, ['section_id' => 0]);
        $this->assert_webapi_operation_failed($result, 'Argument "section_id" of non-null type "core_id!" must not be null.');

        $id = 1293;
        $result = $this->parsed_graphql_operation(self::QUERY, ['section_id' => $id]);
        $this->assert_webapi_operation_failed($result, "Invalid activity");

        $this->setUser();
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'Course or activity not accessible. (You are not logged in)');
    }

    private function get_test_data() {
        $this->setAdminUser();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();

        $activity = $perform_generator->create_activity_in_container();
        $section = $perform_generator->create_section($activity, ['title' => 'Top Section']);

        $data = new \stdClass();
        $data->activity1 = $activity;
        $data->section1 = $section;

        $args = ['section_id' => $section->id];

        return [$data, $args];
    }
}