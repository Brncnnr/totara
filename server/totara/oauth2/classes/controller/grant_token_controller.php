<?php
/**
 * This file is part of Totara Core
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_oauth2
 */
namespace totara_oauth2\controller;

use context;
use context_system;
use totara_mvc\controller;
use totara_oauth2\facade\request_interface;
use totara_oauth2\io\request;
use moodle_url;
use totara_oauth2\server;

class grant_token_controller extends controller {
    /**
     * @var request_interface
     */
    private $request;

    /**
     * The current time within system.
     * @var int
     */
    private $time_now;

    /**
     * @param request_interface $request_interface
     * @param int               $time_now
     */
    public function __construct(request_interface $request_interface, int $time_now) {
        parent::__construct();

        $this->request = $request_interface;
        $this->time_now = $time_now;

        $this->require_login = false;
        $this->url = new moodle_url("/totara/oauth2/token.php");
    }

    /**
     * Create the controller from global variables scope.
     *
     * @return grant_token_controller
     */
    public static function create_from_global(): grant_token_controller {
        $request = request::create_from_global();
        return new static($request, time());
    }

    /**
     * @return context
     */
    protected function setup_context(): context {
        // This controller is being used at context system.
        return context_system::instance();
    }

    /**
     * @return void
     */
    protected function authorize(): void {
        // Note that for oauth2 server controller, we do not want the required login
        // to be run, hence this function was overridden to leave that part out.
        // Despite the flag $this->require_login is already set to FALSE, there is also
        // another flag from global $CFG (which is forcelogin) that can make this function run.
    }

    /**
     * @return string
     */
    public function action(): string {
        $server = server::create($this->time_now);

        // Due to the constraint from totara_mvc, we cannot send the custom headers that was processed
        // from the server yet. For example, a custom response status code, which is 400 and it identifies
        // that the request client does not provide enough information.
        $response = $server->handle_token_request($this->request);
        return $response->getBody()->__toString();
    }
}