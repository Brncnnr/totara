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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Marco Song <marco.song@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\hook\pre_section_element_deleted;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group perform
 * @group perform_element
 */
class webapi_resolver_query_element_deletion_validation_testcase extends advanced_testcase {
    const QUERY = "mod_perform_element_deletion_validation";

    use webapi_phpunit_helper;

    /**
     * @return \mod_perform\testing\generator
     */
    protected function perform_generator() {
        return \mod_perform\testing\generator::instance();
    }

    public function test_query_successful() {
        self::setAdminUser();
        $test_data = $this->create_test_data();

        $hook_sink = $this->redirectHooks();
        $hook_sink->clear();
        $hooks = $hook_sink->get_hooks();

        $this->assertCount(0, $hooks);

        $args = ['input' => ['section_element_id' => $test_data->activity1_section1_section_element1->id]];

        $this->resolve_graphql_query(self::QUERY, $args);

        $hooks = $hook_sink->get_hooks();
        $hook_exists = false;
        foreach ($hooks as $hook) {
            if ($hook instanceof pre_section_element_deleted) {
                $hook_exists = true;
                break;
            }
        }
        $this->assertTrue($hook_exists);
    }

    public function test_failed_without_correct_advanced_feature() {
        self::setAdminUser();
        $test_data = $this->create_test_data();

        advanced_feature::disable('performance_activities');

        $this->expectExceptionMessage('Feature performance_activities is not available');

        $args = ['input', ['section_element_id' => $test_data->activity1_section1_section_element1->id]];
        $this->resolve_graphql_query(self::QUERY, $args);
    }

    public function test_failed_without_logging_in() {
        self::setAdminUser();
        $test_data = $this->create_test_data();
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (You are not logged in)');

        $args = ['input', ['section_element_id' => $test_data->activity1_section1_section_element1->id]];
        $this->setUser(null);
        $this->resolve_graphql_query(self::QUERY, $args);
    }

    /**
     * @return object
     */
    protected function create_test_data() {
        self::setAdminUser();
        $data = new stdClass();

        $perform_generator = $this->perform_generator();
        $data->activity1 = $perform_generator->create_activity_in_container(['activity_name' => 'Activity 1']);
        $data->activity1_section1 = $perform_generator->create_section($data->activity1, ['title' => 'Activity 1 section 1']);
        $data->activity1_section1_element1 = $perform_generator->create_element(['title' => 'Question one']);
        $data->activity1_section1_section_element1 = $perform_generator->create_section_element(
            $data->activity1_section1,
            $data->activity1_section1_element1
        );
        return $data;
    }
}