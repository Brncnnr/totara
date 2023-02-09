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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_userstatus
 */

use totara_webapi\graphql;
use totara_webapi\request;
use totara_webapi\endpoint_type\factory as endpoint_type_factory;
use totara_webapi\endpoint_type\test_endpoint_type;
use totara_webapi\webapi_request_exception;

defined('MOODLE_INTERNAL') || die();

class totara_webapi_request_test extends advanced_testcase {

    public function test_request_empty_params() {
        $params = [];

        $request = new request(
            endpoint_type_factory::get_instance(graphql::TYPE_DEV),
            $params
        );

        $this->expectException(webapi_request_exception::class);
        $this->expectExceptionMessage('Invalid request, request cannot be empty');
        $request->validate();
    }

    public function test_no_support_for_traditional_query_id() {
        $unsupported_keys = ['queryId', 'documentid', 'id'];

        foreach ($unsupported_keys as $key) {
            $params = [
                'operationName' => 'my_operation_name',
                $key => 'my_persistent_query_id',
                'variables' => []
            ];

            try {
                $request = new request(
                    endpoint_type_factory::get_instance(graphql::TYPE_AJAX),
                    $params
                );
                $request->validate();
                $this->fail('Should have failed');
            } catch (webapi_request_exception $e) {
                $this->assertStringContainsString('Invalid request, we do not support standard persistent queries', $e->getMessage());
            }
        }
    }

    public function test_missing_operation_name() {
        $params = [
            'variables' => []
        ];

        $this->expectException(webapi_request_exception::class);
        $this->expectExceptionMessage('Invalid request, expecting at least operationName and variables');
        $request = new request(
            endpoint_type_factory::get_instance(graphql::TYPE_AJAX),
            $params
        );
        $request->validate();
    }

    public function test_missing_variables() {
        $params = [
            'operationName' => 'my_operation_name',
        ];

        $this->expectException(webapi_request_exception::class);
        $this->expectExceptionMessage('Invalid request, expecting at least operationName and variables');
        $request = new request(
            endpoint_type_factory::get_instance(graphql::TYPE_AJAX),
            $params
        );
        $request->validate();
    }

    public function test_non_dev_does_not_support_query_param() {
        $params = [
            'operationName' => '',
            'query' => 'something',
        ];

        $this->expectException(webapi_request_exception::class);
        $this->expectExceptionMessage('Direct GraphQL queries are not supported, only persistent queries.');
        $request = new request(
            endpoint_type_factory::get_instance(graphql::TYPE_AJAX),
            $params
        );
        $request->validate();
    }

    public function test_dev_type_does_support_query_param() {
        $params = [
            'operationName' => 'my_operations',
            'query' => 'something',
        ];

        $request = new request(
            endpoint_type_factory::get_instance(graphql::TYPE_DEV),
            $params
        );
        $request->validate();

        $this->assertEqualsCanonicalizing($params, $request->get_params());
    }

    public function test_dev_type_only_supports_query_param() {
        $params = [
            'operationName' => 'my_operations',
        ];

        $this->expectException(webapi_request_exception::class);
        $this->expectExceptionMessage('Query parameter is missing');
        $request = new request(
            endpoint_type_factory::get_instance(graphql::TYPE_DEV),
            $params
        );
        $request->validate();
    }

    public function test_validation() {
        global $CFG;
        require_once $CFG->dirroot.'/totara/webapi/tests/fixtures/endpoint_type/test_endpoint_type.php';

        $params = [
            'operationName' => 'my_operations',
        ];

        $test_endpoint_type = new test_endpoint_type();

        // Scenario 1 - Persistent queries not allowed
        try {
            $test_endpoint_type->allow_direct_queries = true;
            $test_endpoint_type->allow_persistent_queries = false;
            (new request($test_endpoint_type, $params))->validate();
            $this->fail('Request validation is expected to fail');
        } catch (Exception $e) {
            $this->assertEquals('Query parameter is missing', $e->getMessage());
        }

        // Scenario 2 - Direct queries not allowed
        $params['query'] = 'blah';
        try {
            $test_endpoint_type->allow_direct_queries = false;
            $test_endpoint_type->allow_persistent_queries = true;
            (new request($test_endpoint_type, $params))->validate();
            $this->fail('Request validation is expected to fail');
        } catch (Exception $e) {
            $this->assertEquals(
                'Direct GraphQL queries are not supported, only persistent queries.',
                $e->getMessage()
            );
        }
    }

    public function test_ajax_type_successful_validation() {
        $params = [
            'operationName' => 'my_operations',
            'variables' => [],
        ];

        // Here we have to assume that NO_MOODLE_COOKIES is set to true otherwise this would fail
        $this->assertTrue(NO_MOODLE_COOKIES);

        $request = new request(
            endpoint_type_factory::get_instance(graphql::TYPE_AJAX),
            $params
        );
        $request->validate();

        $this->assertEqualsCanonicalizing($params, $request->get_params());
        $this->assertFalse($request->is_batched());
    }

    public function test_batched_request_missing_required_operation_name() {
        $params = [
            [
                'operationName' => 'my_batched_operation_1',
                'variables' => [],
            ],
            [
                'variables' => [],
            ],
            [
                'operationName' => 'my_batched_operation_3',
                'variables' => [],
            ],
        ];

        $this->expectException(webapi_request_exception::class);
        $this->expectExceptionMessage('Invalid request, expecting at least operationName and variables');

        $request = new request(
            endpoint_type_factory::get_instance(graphql::TYPE_AJAX),
            $params
        );
        $request->validate();
    }

    public function test_batched_request_missing_required_variables() {
        $params = [
            [
                'operationName' => 'my_batched_operation_1',
                'variables' => [],
            ],
            [
                'operationName' => 'my_batched_operation_3',
            ],
        ];

        $this->expectException(webapi_request_exception::class);
        $this->expectExceptionMessage('Invalid request, expecting at least operationName and variables');

        $request = new request(
            endpoint_type_factory::get_instance(graphql::TYPE_AJAX),
            $params
        );
        $request->validate();
    }

    public function test_batched_request_successful_validation() {
        $params = [
            [
                'operationName' => 'my_batched_operation_1',
                'variables' => [],
            ],
            [
                'operationName' => 'my_batched_operation_2',
                'variables' => [],
            ],
            [
                'operationName' => 'my_batched_operation_3',
                'variables' => [],
            ],
        ];

        $request = new request(
            endpoint_type_factory::get_instance(graphql::TYPE_AJAX),
            $params
        );
        $request->validate();

        $this->assertEqualsCanonicalizing($params, $request->get_params());
        $this->assertTrue($request->is_batched());
    }

}