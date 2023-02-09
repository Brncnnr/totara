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

use totara_webapi\local\util;
use totara_webapi\endpoint_type\base as endpoint_type;
use totara_webapi\endpoint_type\factory as endpoint_type_factory;

class request {

    /**
     * @var endpoint_type
     */
    protected $endpoint_type;

    /**
     * @var bool
     */
    protected $batched = false;

    /**
     * @var array|null
     */
    protected $params;

    /**
     * @param string|endpoint_type $endpoint_type
     * @param array|null $params
     */
    public function __construct($endpoint_type, array $params = null) {
        if (!($endpoint_type instanceof endpoint_type)) {
            debugging(
                "Parameter type string has been deprecated and needs to be of type totara_webapi\\type\base",
                DEBUG_DEVELOPER
            );
            $endpoint_type = endpoint_type_factory::get_instance($endpoint_type);
        }
        $this->endpoint_type = $endpoint_type;
        if ($params === null) {
            $params = util::parse_http_request();
        }
        $this->params = $params;
    }

    /**
     * @return array|null
     */
    public function get_params(): ?array {
        return $this->params;
    }

    /**
     * Validate the request, making sure that we have all the mandatory fields in it.
     *
     * @throws webapi_request_exception
     */
    public function validate() {
        if (empty($this->params)) {
            throw new webapi_request_exception('Invalid request, request cannot be empty');
        }

        if (!array_key_exists('operationName', $this->params) && !array_key_exists('query', $this->params)) {
            $this->batched = true;
            $params = $this->params;
        } else {
            $params = [$this->params];
        }

        foreach ($params as $op) {
            if (!empty($op['queryId']) || !empty($op['id']) || !empty($op['documentid'])) {
                throw new webapi_request_exception('Invalid request, we do not support standard persistent queries');
            }

            // Query parameter set/not set.
            if (!empty($op['query'])) {
                // Since the query parameter is set we need to check if direct queries are allowed.
                if (!$this->endpoint_type->allow_direct_queries()) {
                    throw new webapi_request_exception('Direct GraphQL queries are not supported, only persistent queries.');
                }
            } else {
                // Since the query parameter is not set we need to check if persistent queries are allowed.
                if (!$this->endpoint_type->allow_persistent_queries()) {
                    throw new webapi_request_exception('Query parameter is missing');
                }
            }

            // If direct queries are not allowed then the operation name and variables need to be set.
            if (!$this->endpoint_type->allow_direct_queries()) {
                if (empty($op['operationName']) || !isset($op['variables']) || !is_array($op['variables'])) {
                    throw new webapi_request_exception('Invalid request, expecting at least operationName and variables');
                }
            }

            // Validate the format of the operation.
            if (!empty($op['operationName']) && $op['operationName'] !== 'IntrospectionQuery' && !preg_match('/^[a-z][a-z0-9_]+$/D', $op['operationName'])) {
                throw new webapi_request_exception('Invalid request, validation of operationName failed');
            }
        }
    }

    /**
     * Is this a batched request?
     *
     * @return bool
     */
    public function is_batched(): bool {
        return $this->batched;
    }

}