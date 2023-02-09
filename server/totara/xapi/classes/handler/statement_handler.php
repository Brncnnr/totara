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
namespace totara_xapi\handler;

use coding_exception;
use core\entity\user;
use totara_oauth2\entity\access_token;
use totara_xapi\core_json\structure\xapi_statement as xapi_statement_structure;
use contentmarketplace_linkedin\exception\json_validation_exception;
use core\json\validation_adapter;
use totara_oauth2\server;
use totara_xapi\entity\xapi_statement as xapi_statement_entity;
use totara_xapi\model\xapi_statement;
use totara_xapi\request\request;
use totara_xapi\response\facade\result;
use totara_xapi\response\json_result;
use totara_oauth2\io\request as oauth2_request;

class statement_handler {
    /**
     * A request wrapper for xAPI statement.
     * @var request
     */
    protected $request;

    /**
     * @var int|null
     */
    protected $time_now;

    /**
     * @param request $request
     * @param int|null $time_now
     */
    public function __construct(request $request, ?int $time_now = null) {
        $this->request = $request;
        $this->time_now = $time_now ?? time();
    }

    /**
     * @param int $time_now
     * @return void
     */
    public function set_time_now(int $time_now): void {
        $this->time_now = $time_now;
    }

    /**
     * Authenticate the request, whether the user is logged in or logged out.
     * Or whether the request is genuinely the right ones.
     *
     * Returns result object with OAuth provider request body if everything is alright,
     * otherwise a result object to indicate that something went wrong.
     *
     * @return result
     */
    public function authenticate(): result {
        $oauth2_request = oauth2_request::create_from_global(
            $this->request->get_get_parameters(),
            $this->request->get_post_parameters(),
            $this->request->get_header_parameters(),
            $this->request->get_server_parameters()
        );

        $server = server::create($this->time_now);
        $request = $server->verify_request($oauth2_request);
        if (!is_null($request)) {
            return new json_result(['oauth_client_id' => $request->getAttribute('oauth_client_id')]);
        }

        return new json_result([
            "error" => "access_denied",
            "error_description" => get_string("access_denied", "totara_xapi")
        ]);
    }

    /**
     * Ensure that the xAPI statement data is valid, or throw an exception if it isn't.
     *
     * This only checks the most basic structure as required by the xAPI spec. Event
     * observers may apply their own more specific checks in addition to this.
     *
     * @param string $statement
     */
    public function validate_structure(string $statement): void {
        $validator = validation_adapter::create_default();

        $result = $validator->validate_by_structure_class_name(
            $statement,
            xapi_statement_structure::class
        );

        if (!$result->is_valid()) {
            throw new json_validation_exception(
                $result->get_error_message()
            );
        }
    }

    /**
     * Get the ID of the Totara user that this xAPI statement is about.
     *
     * @param string $statement
     * @return int
     */
    public function get_user_id(string $statement): int {
        $statement_data = json_decode($statement, true, 512, JSON_THROW_ON_ERROR);

        // Note that this functionality will change the behaviour when the SSO is enabled
        // in between totara and the linkedin learning.
        if (!isset($statement_data["actor"])) {
            throw new coding_exception("Invalid xAPI statement, cannot find attribute 'actor'");
        }

        if (!array_key_exists("mbox", $statement_data["actor"])) {
            // This is where we are going to check for SSO - but it is not yet implemented.
            throw new coding_exception("Unsupported feature to identify user");
        }

        $email = $statement_data["actor"]["mbox"];

        return user::repository()
            ->select('id')
            ->filter_by_email(str_replace("mailto:", "", $email))
            ->filter_by_not_deleted()
            ->order_by('id')
            ->first_or_fail()
            ->id;
    }

    /**
     * @param string|null $client_id
     * @return xapi_statement
     * @throws \JsonException
     * @throws coding_exception
     * @throws json_validation_exception
     */
    public function create_model_from_request(?string $client_id = null): xapi_statement {

        // Creating a new xapi_statement entity.
        $entity = new xapi_statement_entity();
        $entity->statement = $this->request->get_content();
        $this->validate_structure($entity->statement);
        $entity->user_id = $this->get_user_id($entity->statement);
        if (!is_null($client_id)) {
            $entity->client_id = $client_id;
        }
        $entity->save();

        return xapi_statement::load_by_entity($entity);
    }
}