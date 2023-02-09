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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_webapi
 */

namespace totara_webapi;

use Closure;
use coding_exception;
use core\webapi\execution_context;
use core\webapi\middleware;
use core\webapi\middleware_group;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;
use GraphQL\Executor\Executor;
use GraphQL\Type\Definition\ResolveInfo;
use totara_webapi\hook\api_hook;

/**
 * This represents our default resolver. It takes the request information
 * and tries to resolve the operation to the proper query, type or mutation resolver.
 *
 * All resolvers are stored in classes/webapi/resolver/* folders.
 *
 * @package totara_webapi
 */
class default_resolver {

    /** @var string */
    protected $component;

    /**
     * This class can be used as a callable. The GraphQL library will call it when it tries
     * to resolve the operation given to it.
     *
     * @param mixed $source
     * @param mixed $variables
     * @param execution_context $ec
     * @param ResolveInfo $info
     *
     * @return mixed|null
     */
    public function __invoke($source, $variables, execution_context $ec, ResolveInfo $info) {
        // phpcs:disable Totara.NamingConventions.ValidVariableName.LowerCaseUnderscores
        $ec->set_resolve_info($info);

        $variables = (array) $variables;
        list($classname, $this->component) = resolver_helper::get_resolver_classname_and_component($info, $ec);

        $payload = payload::create($variables, $ec);

        $middleware_chain = function (payload $payload) use ($classname, $info, $source, $ec) {
            if ($info->parentType->name === 'Query' || $info->parentType->name === 'Mutation') {
                // Query or mutation
                if ($ec->get_endpoint_type()->support_query_complexity()) {
                    $cost = $classname::cost_per_call();
                    $payload->get_execution_context()->increment_query_complexity_cost($cost);
                }
                $result = $classname::resolve($payload->get_variables(), $payload->get_execution_context());
            } elseif (!is_null($classname) && class_exists($classname)) {
                if ($ec->get_endpoint_type()->support_query_complexity()) {
                    $cost = $classname::cost_per_call();
                    $payload->get_execution_context()->increment_query_complexity_cost($cost);
                }
                // Regular data type
                $result = $classname::resolve(
                    $info->fieldName,
                    $source,
                    $payload->get_variables(),
                    $payload->get_execution_context()
                );
            } else {
                // Something else
                // TODO increment cost? By what value?
                $result = Executor::defaultFieldResolver(
                    $source,
                    $payload->get_variables(),
                    $payload->get_execution_context(),
                    $info
                );
            }
            // Wrapping the result to make sure the middleware has a specific return type
            return new result($result);
        };

        // Some types don't have resolver classes because resolving his handled via the Query.
        if (!is_null($classname) && class_exists($classname)) {
            $resolver_middleware = array_values(array_reverse($classname::get_middleware()));
        } else {
            $resolver_middleware = [];
        }

        $api_hook = new api_hook($resolver_middleware, $ec->get_endpoint_type(), $this->component, $classname);
        $api_hook->execute();
        $resolver_middleware = $api_hook->middleware;

        $middleware_chain = $this->create_chain_recursively($resolver_middleware, $middleware_chain);
        return $middleware_chain($payload)->get_data();
        // phpcs:enable
    }

    /**
     * Create a middleware chain recursively
     *
     * @param $middleware
     * @param Closure $middleware_chain
     * @param execution_context $ec
     * @return Closure
     */
    private function create_chain_recursively(
        $middleware,
        Closure $middleware_chain
    ): Closure {
        foreach ($middleware as $current_middleware) {
            // This middleware could be a middleware group, in this case get all middleware
            // from it and add them to the chain as well
            if (is_subclass_of($current_middleware, middleware_group::class)) {
                // This is just the class name, so let's instantiate it
                if (is_string($current_middleware)) {
                    $current_middleware = new $current_middleware();
                }

                /** @var middleware_group $current_middleware */
                $middleware_group_items = array_values(array_reverse($current_middleware->get_middleware()));
                $middleware_chain = $this->create_chain_recursively($middleware_group_items, $middleware_chain);
                continue;
            }

            // Middleware can be instances or class names, both would work
            if (!is_subclass_of($current_middleware, middleware::class)) {
                throw new coding_exception('Expecting an array of middleware instances only');
            }

            $middleware_chain = function (payload $payload) use ($middleware_chain, $current_middleware) {
                // This is just the class name, so let's instantiate it
                if (is_string($current_middleware)) {
                    $current_middleware = new $current_middleware();
                }
                return $current_middleware->handle($payload, $middleware_chain);
            };
        }

        return $middleware_chain;
    }

}