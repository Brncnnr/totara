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

use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\query\element
 *
 * @group perform
 */
class mod_perform_webapi_resolver_query_element_testcase extends advanced_testcase {
    private const QUERY = 'mod_perform_element';

    use webapi_phpunit_helper;

    public function test_get_element(): void {
        self::setAdminUser();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();
        $element = $perform_generator->create_element();

        $returned_element = $this->resolve_graphql_query(self::QUERY, ['element_id' => $element->id]);

        self::assertEquals($element->id, $returned_element->id);
    }

    public function test_successful_ajax_call(): void {
        self::setAdminUser();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();
        $element = $perform_generator->create_element();

        $result = $this->parsed_graphql_operation(self::QUERY, ['element_id' => $element->id]);
        $this->assert_webapi_operation_successful($result);

        $returned_element = $this->get_webapi_operation_data($result);
        self::assertEquals($element->title, $returned_element['title']);
    }

    public function test_failed_ajax_query(): void {
        self::setAdminUser();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();
        $element = $perform_generator->create_element();

        $args = ['element_id' => $element->id];

        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
        advanced_feature::enable($feature);

        self::setUser();
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'not logged in');

        $normal_user = self::getDataGenerator()->create_user();
        self::setUser($normal_user->id);

        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'You do not have permission to view this element');
    }
}