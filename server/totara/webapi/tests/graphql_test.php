<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_webapi
 */

use core\webapi\execution_context;
use totara_webapi\graphql;

defined('MOODLE_INTERNAL') || die();

class totara_webapi_graphql_testcase extends advanced_testcase {

    /**
     * @return array
     */
    public function standard_operation_types() {
        return [['ajax'], ['external'], ['mobile']];
    }

    /**
     * @dataProvider standard_operation_types
     * @param $type
     */
    public function test_get_role_capabilities($type) {
        $oeprationcapabilities = graphql::get_role_capabilities($type);
        $this->assertIsArray($oeprationcapabilities);
        foreach ($oeprationcapabilities as $operationname => $capabilities) {
            $this->assertMatchesRegularExpression('/^[a-z]+_[a-z0-9_]+$/D', $operationname);
            $this->assertIsArray($capabilities);
        }
    }

    public function test_get_server_root() {
        // We do not need the full schema, so mock it
        $schema = $this->getMockBuilder(\GraphQL\Type\Schema ::class)
            ->disableOriginalConstructor()
            ->getMock();

        $root = graphql::get_server_root($schema);
        $this->assertSame([], $root);
    }

    public function test_execute_operation() {
        // Any operation will do here.
        $result = graphql::execute_operation(
            execution_context::create('ajax', 'core_lang_strings_nosession'),
            [
                'lang' => 'en',
                'ids' => [
                    'edit,core'
                ]
            ]
        );
        $this->assertInstanceOf('GraphQL\Executor\ExecutionResult', $result);
        $result = $result->toArray(true);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame([
            'lang_strings' => [
                [
                    'lang' => 'en',
                    'identifier' => 'edit',
                    'component' => 'core',
                    'string' => 'Edit'
                ]
            ]
        ], $result['data']);
    }

    public function test_execute_operation_with_performance_metrics() {
        // Any operation will do here.
        $result = graphql::execute_operation(
            execution_context::create('ajax', 'core_lang_strings_nosession'),
            [
                'lang' => 'en',
                'ids' => [
                    'edit,core'
                ]
            ]
        );
        $this->assertInstanceOf('GraphQL\Executor\ExecutionResult', $result);
        $result = $result->toArray(true);
        $this->assertArrayNotHasKey('extensions', $result);

        // Activate performance config
        set_config('perfdebug', 15);

        // Any operation will do here.
        $result = graphql::execute_operation(
            execution_context::create('ajax', 'core_lang_strings_nosession'),
            [
                'lang' => 'en',
                'ids' => [
                    'edit,core'
                ]
            ]
        );
        $this->assertInstanceOf('GraphQL\Executor\ExecutionResult', $result);
        $result = $result->toArray(true);
        $this->assertArrayHasKey('extensions', $result);
        $this->assertArrayHasKey('performance_data', $result['extensions']);
        $this->assertArrayNotHasKey('query_complexity', $result['extensions']);
    }

    public function test_execute_operation_with_error_result() {
        $result = graphql::execute_operation(
            execution_context::create('ajax', 'core_lang_strings_nosession'),
            [
                'xlang' => 'en',
                'ids' => [
                    'edit,core'
                ]
            ]
        );
        $this->assertInstanceOf('GraphQL\Executor\ExecutionResult', $result);
        $result = $result->toArray();
        $this->assertArrayNotHasKey('data', $result);
        $this->assertArrayHasKey('errors', $result);
    }

    public function test_execute_operation_with_invalid_name() {
        $result = graphql::execute_operation(
            execution_context::create('ajax', 'xxxcore_lang_strings_nosession'),
            [
                'lang' => 'en',
                'ids' => [
                    'edit,core'
                ]
            ]
        );

        $this->assertNotEmpty($result->errors);
    }

}