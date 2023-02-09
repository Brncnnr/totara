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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group perform
 * @coversDefaultClass \mod_perform\webapi\resolver\query\responding_relationships_involved_in_subject_instance
 */
class mod_perform_webapi_resolver_query_responding_relationships_involved_in_subject_instance_testcase extends advanced_testcase {

    private const QUERY = 'mod_perform_responding_relationships_involved_in_subject_instance';

    use webapi_phpunit_helper;

    public function test_query_successful(): void {
        self::setAdminUser();

        $subject_instance = $this->create_test_data();

        $args = [
            'subject_instance_id' => $subject_instance->id
        ];

        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);

        $actual = $this->get_webapi_operation_data($result);

        foreach ($actual as $i => $actual_relationship) {
            unset($actual[$i]['id']);
        }

        $expected_result = [
            [
                'name' => 'Subject',
                'idnumber' => 'subject',
            ],
            [
                'name' => 'Appraiser',
                'idnumber' => 'appraiser',
            ],
        ];

        // Order also matters here.
        $this->assertEquals($expected_result, $actual);
    }

    public function test_failed_ajax_query(): void {
        self::setAdminUser();

        $subject_instance = $this->create_test_data();

        $args = [
            'subject_instance_id' => $subject_instance->id
        ];

        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
        advanced_feature::enable($feature);

        self::setUser();
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'not logged in');

        self::setGuestUser();
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'You do not have permission to view relationships');
    }

    private function create_test_data(): subject_instance_entity {
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();

        $config = new \mod_perform\testing\activity_generator_configuration();
        $config->set_relationships_per_section(['subject', 'appraiser']);

        $perform_generator->create_full_activities($config)->first();

        return subject_instance_entity::repository()->order_by('id')->first();
    }

}
