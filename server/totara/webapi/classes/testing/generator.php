<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Scott Davies <scott.davies@totaralearning.com>
 * @package totara_webapi
 */

namespace totara_webapi\testing;

use GraphQL\Error\DebugFlag;
use core\testing\component_generator;
use totara_webapi\controllers\external;
use totara_webapi\controllers\api_controller;
use totara_webapi\server;
use totara_webapi\request;

/**
 * Data generator class for PHPUnit for the totara/webapi area.
 */
class generator extends component_generator {
    /**
     * @param string $query The GraphQL mutation or query to run using the External API.
     * @return api_controller
     */
    public function create_external_instance(string $query=''): api_controller {
        $class = new class() extends external {
            /**
             * @var string
             */
            private $query;

            /**
             * @param string $query
             * @return void
             */
            public function set_query(string $query) : void {
                $this->query = $query;
            }

            /**
             * @return void
             */
            public function action_graphql_request(): void {
                $execution_context = $this->get_execution_context();
                $request = new request(
                    $execution_context->get_endpoint_type(),
                    [
                        'operationName' => null,
                        'query' => $this->query
                    ]
                );
                $server = new server($execution_context, DebugFlag::INCLUDE_DEBUG_MESSAGE);
                $result = $server->handle_request($request);
                $server->send_response($result, false);
            }
        };

        $external = new $class(false);
        $external->set_query($query);
        return $external;
    }

}
