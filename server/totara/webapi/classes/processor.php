<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

namespace totara_webapi;

use coding_exception;
use core\webapi\execution_context;
use GraphQL\Error\DebugFlag;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Server\OperationParams;
use GraphQL\Server\StandardServer;
use totara_webapi\local\util;

/**
 * This is the base implementation of the GraphQL StandardServer. It's used both by serverless and server classes.
 *
 * @package totara_webapi
 */
final class processor {

    /**
     * Execution context.
     *
     * @var execution_context
     */
    private $execution_context;

    /**
     * If debug mode is enabled in the server.
     *
     * @var bool
     */
    private $debug;

    /**
     * base_server constructor.
     *
     * @param execution_context $execution_context
     * @param int $debug
     */
    private function __construct(execution_context $execution_context, $debug) {
        $this->execution_context = $execution_context;
        $this->debug = $debug;
    }

    /**
     * Instance of base_server.
     *
     * @param execution_context $execution_context
     * @param int|null $debug
     *
     * @return processor
     */
    public static function instance(execution_context $execution_context, $debug = null): processor {
        global $CFG;

        if ($debug === null) {
            $debug = DebugFlag::NONE;
        }
        return new self($execution_context, $debug);
    }

    /**
     * Processes a graphql request.
     *
     * @param request $request
     * @return ExecutionResult|ExecutionResult[]
     */
    public function process_request(request $request) {
        $request->validate();
        $operations = $this->prepare_operations($request);
        $schema = graphql::get_schema($this->execution_context->get_endpoint_type());

        $server = new StandardServer([
            'persistentQueryLoader' => new persistent_operations_loader(),
            'queryBatching' => true,
            'debugFlag' => $this->debug,
            'schema' => $schema,
            'fieldResolver' => new default_resolver(),
            'rootValue' => graphql::get_server_root($schema),
            'context' => $this->execution_context,
            'errorsHandler' => [util::class, 'graphql_error_handler'],
            'errorFormatter' => [util::class, 'graphql_error_formatter'],
            'validationRules' => $this->execution_context->get_endpoint_type()->get_validation_rules(),
        ]);
        $result = $server->executeRequest($operations);
        $this->process_deprecation_warnings($result);

        return $result;
    }

    /**
     * Convert the request into OperationParams instances which the GraphQL library
     * needs for executing the request.
     *
     * @param request $request
     * @return OperationParams|OperationParams[]
     */
    private function prepare_operations(request $request) {
        if ($request->is_batched()) {
            // Operation name in the execution context should be null
            // as the execution context is used for all queries
            if ($this->execution_context->get_operationname() !== null) {
                throw new coding_exception('Expected operation name in execution context to be null for batched queries');
            }
            return array_map(function ($operation) {
                return $this->create_operation($operation);
            }, $request->get_params());
        } else {
            $params = $request->get_params();
            // We want to be sure that the operation name in the execution context matches the one in the request
            if ($this->execution_context->get_operationname() !== null
                && $this->execution_context->get_operationname() !== $params['operationName']
            ) {
                throw new coding_exception('Operation name mismatch, request has different value as the execution_context.');
            }
            $this->execution_context->set_operationname($params['operationName']);

            return $this->create_operation($params);
        }
    }

    /**
     * Create operation with specified parameters.
     *
     * @param array $params
     * @return OperationParams
     */
    private function create_operation(array $params) {
        // To be able to use the persistent query support built into
        // the GraphQL library we use the operation name for the queryId
        if ($this->execution_context->get_endpoint_type()->allow_persistent_queries() && !empty($params['operationName'])) {
            $params['queryId'] = $params['operationName'];
        }

        $params['webapi_type'] = $this->execution_context->get_endpoint_type()::get_name();
        $params = fix_utf8($params);
        return OperationParams::create($params);
    }

    /**
     * Process deprecation warnings for field triggered during the request
     * 1. Throws debugging messages for each one
     * 2. Appends all messages to the extensions
     *
     * @param ExecutionResult|ExecutionResult[] $results
     */
    private function process_deprecation_warnings($results) {
        global $CFG;

        $deprecation_warnings = $this->execution_context->get_deprecation_warnings();
        if (!empty($deprecation_warnings)) {
            foreach ($deprecation_warnings as $type => $warnings) {
                foreach ($warnings as $field => $message) {
                    debugging(
                        "Field '{$field}' of type '{$type}' is marked as deprecated: {$message}",
                        DEBUG_DEVELOPER
                    );
                }
            }

            if ($CFG->debugdeveloper) {
                // If this is a batched queries we will have multiple results
                // so go through them and add the deprecation warnings to them
                if (is_array($results)) {
                    foreach ($results as $result) {
                        $result->extensions['deprecation_warnings'] = $deprecation_warnings;
                    }
                } else if ($results instanceof ExecutionResult) {
                    $results->extensions['deprecation_warnings'] = $deprecation_warnings;
                }
            }
        }
    }
}