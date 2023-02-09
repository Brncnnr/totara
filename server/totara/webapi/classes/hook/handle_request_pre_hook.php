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
use Exception;
use totara_core\hook\base;
use totara_webapi\request;
use totara_webapi\server;

class handle_request_pre_hook extends base {

    /**
     * @var request
     */
    public $request;

    /**
     * @var execution_context
     */
    public $execution_context;

    /**
     * @var server
     */
    public $server;

    /**
     * @var Exception|null
     */
    protected $exception;

    /**
     * @param request $request
     * @param execution_context $execution_context
     * @param server $server
     */
    public function __construct(request $request, execution_context $execution_context, server $server) {
        $this->request = $request;
        $this->execution_context = $execution_context;
        $this->server = $server;
        $this->exception = null;
    }

    /**
     * @return bool
     */
    public function has_error(): bool {
        return !empty($this->exception);
    }

    /**
     * @param Exception $exception
     * @return void
     */
    public function set_exception(Exception $exception): void {
        $this->exception = $exception;
    }

    /**
     * @return Exception|null
     */
    public function get_exception(): ?Exception {
        return $this->exception;
    }
}