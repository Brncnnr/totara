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
 * @author  Michael Ivanov <michael.ivanov@totaralearning.com>
 * @package totara_webapi
 */

namespace totara_webapi\hook;

use core\webapi\execution_context;
use totara_core\hook\base;
use GraphQL\Executor\ExecutionResult;
use totara_webapi\server;

class handle_request_post_hook extends base {

    /**
     * @var ExecutionResult
     */
    public $result;

    /**
     * @var execution_context
     */
    public $execution_context;

    /**
     * @var server
     */
    public $server;

    /**
     * @param ExecutionResult|ExecutionResult[] $result
     * @param execution_context $execution_context
     * @param server $server
     */
    public function __construct($result, execution_context $execution_context, server $server) {
        $this->result = $result;
        $this->execution_context = $execution_context;
        $this->server = $server;
    }
}