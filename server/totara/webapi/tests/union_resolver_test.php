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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_webapi
 */

use core\webapi\execution_context;
use GraphQL\Error\DebugFlag;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Server\OperationParams;
use GraphQL\Server\StandardServer;
use totara_webapi\default_resolver;
use totara_webapi\endpoint_type\dev;
use totara_webapi\graphql;
use totara_webapi\local\util;
use totara_webapi\schema_builder;
use totara_webapi\schema_file_loader;
use totara_webapi\webapi\resolver\union\test_schema_union;

defined('MOODLE_INTERNAL') || die();

class totara_webapi_union_resolver_testcase extends advanced_testcase {

    public function test_union_type_resolving() {
        $type = graphql::TYPE_AJAX;
        $operation_name = 'totara_webapi_test_union';

        $server = $this->create_server($type, $operation_name);

        $operations = OperationParams::create([
            'operationName' => $operation_name,
            'queryId' => $operation_name,
            'webapi_type' => $type,
            'variables' => [
                'type' => 'type1'
            ]
        ]);

        $result = $server->executeRequest($operations);
        $this->assertInstanceOf(ExecutionResult::class, $result);
        $this->assertEmpty($result->errors);
        $data = $result->data[$operation_name];
        $this->assertEquals('totara_webapi_test_schema_type1', $data['__typename']);
        $this->assertEquals('type1', $data['name']);
        $this->assertEquals(true, $data['is_type1']);
        $this->assertArrayNotHasKey('is_type2', $data);

        $operations = OperationParams::create([
            'operationName' => $operation_name,
            'queryId' => $operation_name,
            'webapi_type' => $type,
            'variables' => [
                'type' => 'type2'
            ]
        ]);

        $result = $server->executeRequest($operations);
        $this->assertInstanceOf(ExecutionResult::class, $result);
        $this->assertEmpty($result->errors);
        $data = $result->data[$operation_name];
        $this->assertEquals('totara_webapi_test_schema_type2', $data['__typename']);
        $this->assertEquals('type2', $data['name']);
        $this->assertEquals(true, $data['is_type2']);
        $this->assertArrayNotHasKey('is_type1', $data);
    }

    public function test_union_type_resolving_non_existent_class() {
        $type = graphql::TYPE_AJAX;
        $operation_name = 'totara_webapi_test_union';

        $server = $this->create_server($type, $operation_name);

        $operations = OperationParams::create([
            'operationName' => $operation_name,
            'queryId' => $operation_name,
            'webapi_type' => $type,
            'variables' => [
                'type' => 'invalid'
            ]
        ]);

        $result = $server->executeRequest($operations);
        $this->assertInstanceOf(ExecutionResult::class, $result);
        $this->assertEmpty($result->data[$operation_name]);
        $this->assertNotEmpty($result->errors);
        $this->assertStringContainsString('Invalid type resolver class returned', $result->errors[0]);
    }

    public function test_union_type_resolving_no_type_resolver() {
        $type = graphql::TYPE_AJAX;
        $operation_name = 'totara_webapi_test_union';

        $server = $this->create_server($type, $operation_name);

        $operations = OperationParams::create([
            'operationName' => $operation_name,
            'queryId' => $operation_name,
            'webapi_type' => $type,
            'variables' => [
                'type' => 'no_type_resolver'
            ]
        ]);

        $result = $server->executeRequest($operations);
        $this->assertInstanceOf(ExecutionResult::class, $result);
        $this->assertEmpty($result->data[$operation_name]);
        $this->assertNotEmpty($result->errors);
        $this->assertStringContainsString('Invalid type resolver class returned', $result->errors[0]);
    }

    public function test_union_type_resolving_undefined_type() {
        $type = graphql::TYPE_AJAX;
        $operation_name = 'totara_webapi_test_union';

        $server = $this->create_server($type, $operation_name);

        $operations = OperationParams::create([
            'operationName' => $operation_name,
            'queryId' => $operation_name,
            'webapi_type' => $type,
            'variables' => [
                'type' => 'undefined_type'
            ]
        ]);

        $result = $server->executeRequest($operations);
        $this->assertInstanceOf(ExecutionResult::class, $result);
        $this->assertEmpty($result->data[$operation_name]);
        $this->assertNotEmpty($result->errors);
        $this->assertStringContainsString(
            "Concrete type 'totara_webapi_test_schema_type3' returned by GraphQL union resolver '".
            test_schema_union::class."' is not defined in GraphQL schema",
            $result->errors[0]
        );
    }

    private function create_server(string $type, string $operation_name) {
        set_config('cache_graphql_schema', false);

        global $CFG;
        require_once $CFG->dirroot.'/totara/webapi/tests/fixtures/resolver/query/test_union.php';
        require_once $CFG->dirroot.'/totara/webapi/tests/fixtures/resolver/union/test_schema_union.php';

        $schema_files = [
            'test_union.graphqls' => file_get_contents(__DIR__.'/fixtures/webapi/test_union.graphqls'),
        ];

        // Mocking the file loader as a growing schema would automatically affect this test.
        // This keeps the impact to the minimum and allows us to test not building the real whole schema
        // but just the logic to build the schema itself
        $schema_file_loader = $this->getMockBuilder(schema_file_loader::class)
            ->setConstructorArgs([new dev()])
            ->getMock();

        $schema_file_loader->expects($this->any())
            ->method('load')
            ->willReturn($schema_files);

        $schema_builder = new class($schema_file_loader) extends schema_builder {
            protected function load_union_classes(): array {
                return [
                    test_schema_union::class
                ];
            }
        };
        $schema = $schema_builder->build();

        return new StandardServer([
            'persistentQueryLoader' => function ($operation_name, OperationParams $operation_params) {
                return file_get_contents(__DIR__.'/fixtures/ajax/test_union.graphql');
            },
            'queryBatching' => true,
            'debugFlag' => DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE,
            'schema' => $schema,
            'fieldResolver' => new default_resolver(),
            'rootValue' => graphql::get_server_root($schema),
            'context' => execution_context::create($type, $operation_name),
            'errorsHandler' => [util::class, 'graphql_error_handler'],
        ]);
    }

}