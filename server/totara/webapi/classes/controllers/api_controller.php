<?php
/**
 * This file is part of Totara Learn
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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_webapi
 */

namespace totara_webapi\controllers;

use context;
use context_system;
use core\webapi\execution_context;
use GraphQL\Error\DebugFlag;
use Throwable;
use totara_mvc\controller;
use totara_webapi\exception\api_exception;
use totara_webapi\local\util;
use totara_webapi\server;

/**
 * Extend this controller if your page act as an API interface.
 *
 * @package totara_webapi
 */
abstract class api_controller extends controller {

    /** @var bool */
    private $stop_execution;

    /**
     * api_controller constructor.
     * @param bool|null $stop_execution Indicates if this request should die after sending a response.
     */
    public function __construct(?bool $stop_execution = true) {
        $this->stop_execution = $stop_execution;
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function require_login(): bool {
        // Login is determined by the resolvers.
        return false;
    }

    /**
     * @inheritDoc
     */
    protected function setup_context(): context {
        return context_system::instance();
    }

    /**
     * @inheridDoc
     */
    public function process(string $action = '') {
        set_exception_handler([util::class, 'exception_handler']);
        set_error_handler([util::class, 'error_handler'], E_ALL | E_STRICT);

        try {
            $this->init();
            $this->run_action($action);
        } catch (api_exception $e) {
            // Do nothing.
            // Our intention here is to just interrupt further processing of the request and return.
            // Other exception will not be caught here and will be handled by the API.
        } finally {
            // Make sure the previous error/exception handler has been restored
            // so normal error handling can continue.
            restore_error_handler();
            restore_exception_handler();
        }
    }

    /**
     * @param string $message
     * @return void
     */
    protected function send_bad_request_error(string $message) {
        util::send_error($message, 400, $this->stop_execution);

        // Throw an exception here to interrupt further processing.
        throw new api_exception();
    }

    /**
     * @param string $message
     * @return void
     */
    protected function send_internal_server_error(string $message): void {
        error_log("API error: exception during set up stage - $message");
        util::send_error('Unknown internal error', 500, $this->stop_execution);

        // Throw an exception here to interrupt further processing.
        throw new api_exception();
    }

    /**
     * Execute GraphQL operations.
     * This will be called via controller->execute('graphql_request').
     *
     * @return void
     */
    protected function action_graphql_request(): void {
        $server = new server($this->get_execution_context(), $this->get_debug_level());
        $result = $server->handle_request();
        $server->send_response($result, $this->stop_execution);
    }

    /**
     * Initialize environment.
     *
     * @return void
     */
    protected function init(): void {
        if (empty($_SERVER['REQUEST_METHOD']) or $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->send_bad_request_error(get_string('invalid_request_method', 'totara_webapi'));
        }
    }

    /**
     * @inheritDoc
     */
    protected function run_action(string $action = ''): void {
        $method_name = !empty($action) ? "action_{$action}" : 'action';
        if (!method_exists($this, $method_name)) {
            $this->send_internal_server_error("Missing action method {$method_name}");
        }
        $this->{$method_name}();
    }

    /**
     * @return execution_context
     */
    abstract protected function get_execution_context(): execution_context;

    /**
     * @return int
     */
    protected function get_debug_level(): int {
        global $CFG;

        if ((bool)$CFG->debugdeveloper) {
            return DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE;
        } else {
            return DebugFlag::NONE;
        }
    }

}
