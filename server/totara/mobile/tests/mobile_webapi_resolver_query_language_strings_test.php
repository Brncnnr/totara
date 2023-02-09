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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package totara_mobile
 */

use totara_api\global_api_config;
use GraphQL\Executor\ExecutionResult;

defined('MOODLE_INTERNAL') || die();

/**
 * Test GraphQL resolver of mobile queries
 */
class totara_mobile_webapi_resolver_query_language_strings_testcase extends advanced_testcase {
    /**
     * Test the results of the embedded mobile query through the GraphQL stack.
     */
    public function test_embedded_query() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $result = \totara_webapi\graphql::execute_operation(
            \core\webapi\execution_context::create('mobile', 'totara_mobile_language_strings'),
            ['lang' => 'en']
        );

        $data = $result->toArray()['data'];

        $this->assertNotEmpty($data['json_string']);
        $strings = json_decode($data['json_string']);
        $this->assertNotEmpty($strings);
    }

    /**
     * For the Mobile API, max_query_depth validation rules should NOT be applied.
     * @return void
     */
    public function test_mobile_api_max_query_depth() : void {
        // Set up
        $original_config = global_api_config::get_max_query_depth();
        // Set this to a ridiculous value so we can see clearly the config is not being used for the Mobile API.
        set_config('max_query_depth', '-1', 'totara_api');

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $exec_context =  \core\webapi\execution_context::create('mobile', 'totara_mobile_language_strings');
        $result = \totara_webapi\graphql::execute_operation(
            $exec_context,
            ['lang' => 'en']
        );

        $this->assertEmpty($exec_context->get_endpoint_type()->get_validation_rules());
        $this->assertInstanceOf(ExecutionResult::class, $result);
        $this->assertEmpty($result->errors, 'Unexpected errors found in request');

        // Tear down
        set_config('max_query_depth', $original_config, 'totara_api');
    }
}