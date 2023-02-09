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
 * A simple FIFO mock client for testing.
 */
class simple_mock_client implements client {
    /**
     * @var response[]
     */
    private $responses = [];

    /**
     * @var request[]
     */
    private $requests = [];

    /**
     * @param response $response
     */
    public function mock_queue(response $response): void {
        $this->responses[] = $response;
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
    public function reset_queue(): void {
        $this->responses = [];
    }

    /**
     * Returns the count of current list of mock responses
     * @return int
     */
    public function count_mock_responses(): int {
        return count($this->responses);
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
        return array_shift($this->responses);
    }
}