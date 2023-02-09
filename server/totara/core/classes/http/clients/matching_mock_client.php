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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\http\clients;

use coding_exception;
use totara_core\http\client;
use totara_core\http\request;
use totara_core\http\response;

/**
 * A mock client that provides pattern matching for testing.
 */
class matching_mock_client implements client {
    /**
     * @var array
     */
    private $responses = [];

    /**
     * @var request[]
     */
    private $requests = [];

    /**
     * @param string $url
     * @param response $response
     * @param string|null $method GET, POST, etc. defined in the method class
     */
    public function add_response(string $url, response $response, string $method = null): void {
        $callable = function (request $request) use ($url, $method) {
            if ($method !== null && $method !== $request->get_method()) {
                return false;
            }
            return $url === $request->get_url();
        };
        $this->add_response_callable($callable, $response);
    }

    /**
     * @param string $regexp
     * @param response $response
     * @param string|null $method GET, POST, etc. defined in the method class
     */
    public function add_response_regexp(string $regexp, response $response, string $method = null): void {
        $callable = function (request $request) use ($regexp, $method) {
            if ($method !== null && $method !== $request->get_method()) {
                return false;
            }
            return preg_match($regexp, $request->get_url());
        };
        $this->add_response_callable($callable, $response);
    }

    /**
     * @param callable $callback function(request): bool
     * @param response $response
     */
    public function add_response_callable(callable $callback, response $response): void {
        $this->responses[] = [$callback, $response];
    }

    /**
     * @return request[]
     */
    public function get_requests(): array {
        return $this->requests;
    }

    /**
     * @return void
     */
    public function reset(): void {
        $this->requests = [];
    }

    /**
     * @return void
     */
    public function reset_responses(): void {
        $this->responses = [];
    }

    /**
     * @param int $timeout
     * @return client
     */
    public function set_connect_timeout(int $timeout): client {
        return $this;
    }

    /**
     * @param int $timeout
     * @return client
     */
    public function set_timeout(int $timeout): client {
        return $this;
    }

    /**
     * @param request $request
     * @return response
     */
    public function execute(request $request): response {
        $this->requests[] = $request;
        if (empty($this->responses)) {
            throw new coding_exception('no mock response found');
        }
        foreach ($this->responses as $i => [$callable, $response]) {
            if (!call_user_func($callable, $request)) {
                continue;
            }
            array_splice($this->responses, $i, 1);
            return $response;
        }
        throw new coding_exception('no matching mock response found');
    }
}
