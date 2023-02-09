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
 * @package totara_xapi
 */
namespace totara_xapi\controller;

use context;
use context_system;
use totara_mvc\controller;
use totara_xapi\event\xapi_statement_created;
use totara_xapi\handler\statement_handler;
use totara_xapi\request\request;
use totara_xapi\response\facade\result;
use moodle_url;
use totara_xapi\response\json_result;

class receiver_controller extends controller {
    /**
     * @var request
     */
    private $request;

    /**
     * @var int
     */
    private $time_now;

    /**
     * @param request|null $request
     * @param int|null $time_now
     */
    public function __construct(?request $request = null, ?int $time_now = null) {
        parent::__construct();

        $this->request = $request ?? request::create_from_global();

        // The current of processing time.
        $this->time_now = $time_now ?? time();
        $this->require_login = false;

        $this->url = new moodle_url(
            "/totara/xapi/receiver.php"
        );
    }

    /**
     * @return context
     */
    protected function setup_context(): context {
        return context_system::instance();
    }

    /**
     * @return result
     */
    public function action(): result {
        $statement_handler = new statement_handler($this->request);

        // Authenticate the request.
        $result_response = $statement_handler->authenticate();
        if (array_key_exists('error', $result_response->get_data())) {
            // Response appears to be an issue. Hence we are going to returns the response, back to
            // the client, instead of processing it.
            return $result_response;
        }

        $data = $result_response->get_data();
        // This also checks the statement is valid and stores the xapi statement in the database.
        $statement_model = $statement_handler->create_model_from_request(
            isset($data['oauth_client_id']) ? $data['oauth_client_id'] : null
        );

        // Trigger event to be observed by interested components.
        $event = xapi_statement_created::create_from_xapi_statement($statement_model);
        $event->trigger();

        return new json_result(["success" => true]);
    }

    /**
     * @return void
     */
    protected function authorize(): void {
        // NOTE: we do not authorize for this controller, instead it is handled in the action.
        // Despite the flag $this->require_login is already set to FALSE, there is also
        // another flag from global $CFG (which is forcelogin) that can make this function run.
    }
}