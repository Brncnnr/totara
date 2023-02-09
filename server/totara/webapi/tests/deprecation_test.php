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

defined('MOODLE_INTERNAL') || die();

class totara_webapi_deprecation_testcase extends advanced_testcase {

    public function test_deprecation() {
        $type = graphql::TYPE_AJAX;
        $operation_name = 'totara_webapi_test_deprecation';

        $execution_context = execution_context::create($type, $operation_name);

        $server = $this->create_server($execution_context);

        $operations = OperationParams::create([
            'operationName' => $operation_name,
            'queryId' => $operation_name,
            'webapi_type' => $type,
            'variables' => []
        ]);

        $result = $server->executeRequest($operations);
        $this->assertInstanceOf(ExecutionResult::class, $result);
        $this->assertEmpty($result->errors);
        $data = $result->data[$operation_name];

        $expected = [
            '__typename' => 'totara_webapi_test_deprecation',
            'nondeprecated' => 'not deprecated',
            'deprecated' => 'deprecated with a reason',
            'deprecated_without_reason' => 'deprecated without a reason',
        ];

        $this->assertEqualsCanonicalizing($expected, $data);

        $deprecation_warnings = $execution_context->get_deprecation_warnings();
        $this->assertArrayHasKey('totara_webapi_test_deprecation', $deprecation_warnings);
        $this->assertEqualsCanonicalizing(
            [
                'deprecated' => 'This field is now deprecated',
                'deprecated_without_reason' => \GraphQL\Type\Definition\Directive::DEFAULT_DEPRECATION_REASON
            ],
            $deprecation_warnings['totara_webapi_test_deprecation']
        );
    }

    private function create_server(execution_context $execution_context) {
        set_config('cache_graphql_schema', false);

        global $CFG;
        require_once $CFG->dirroot.'/totara/webapi/tests/fixtures/resolver/query/test_deprecation.php';

        $schema_files = [
            'test_deprecation.graphqls' => file_get_contents(__DIR__.'/fixtures/webapi/test_deprecation.graphqls'),
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

        // Override the union classes loaded so it does not load php type definitions not available in the
        // fixture schema files provided above.
        $schema_builder = new class($schema_file_loader) extends schema_builder {
            protected function load_union_classes(): array {
                return [];
            }
        };

        $schema = $schema_builder->build();

        return new StandardServer([
            'persistentQueryLoader' => function ($operation_name, OperationParams $operation_params) {
                return file_get_contents(__DIR__.'/fixtures/ajax/test_deprecation.graphql');
            },
            'queryBatching' => true,
            'debugFlag' => DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE,
            'schema' => $schema,
            'fieldResolver' => new default_resolver(),
            'rootValue' => graphql::get_server_root($schema),
            'context' => $execution_context,
            'errorsHandler' => [util::class, 'graphql_error_handler'],
        ]);
    }

}