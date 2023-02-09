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

use core\webapi\execution_context;
use GraphQL\Error\DebugFlag;

/**
 * This class handles GraphQL requests executed internally, by page controllers for example.
 *
 * @package totara_webapi
 */
class serverless {

    /**
     * Execute graphql single or batch operation.
     * Throws exception on error during execution.
     *
     * @param string|null $operation_name
     * @param array $variables
     * @param int|null $debug
     * @return array
     */
    public static function execute_operation(?string $operation_name = null, array $variables = [], $debug = null): array {
        if (is_null($debug)) {
            $debug = DebugFlag::RETHROW_INTERNAL_EXCEPTIONS;
        }

        return is_null($operation_name)
            ? self::execute_batch_operations($variables, $debug)
            : self::execute_single_operation($operation_name, $variables, $debug);
    }

    /**
     * Execute a single graphql operation.
     *
     * @param string $operation_name
     * @param array $variables
     * @param bool|int $debug
     * @return array
     */
    private static function execute_single_operation(string $operation_name, array $variables, $debug): array {
        $execution_context = execution_context::create(graphql::TYPE_AJAX, $operation_name);
        $params = [
            'operationName' => $execution_context->get_operationname(),
            'variables' => $variables,
        ];
        $request = new request($execution_context->get_endpoint_type(), $params);

        return processor::instance(
            $execution_context,
            $debug
        )->process_request($request)
            ->toArray($debug);
    }

    /**
     * Helper method to execute batched operations.
     *
     * @param array $operations Contains a list of arrays each with operationName and variables keys.
     * @param bool|int $debug
     * @return array
     */
    private static function execute_batch_operations(array $operations, $debug): array {
        $execution_context = execution_context::create(graphql::TYPE_AJAX);
        $batched_request = new request($execution_context->get_endpoint_type(), $operations);

        $execution_results = processor::instance($execution_context, $debug)->process_request($batched_request);

        return array_map(function ($execution_result) use ($debug) {
            return $execution_result->toArray($debug);
        }, $execution_results);
    }
}