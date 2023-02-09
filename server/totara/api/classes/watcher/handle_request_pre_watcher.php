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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_api
 */

namespace totara_api\watcher;

use core\session\manager;
use Psr\Http\Message\ServerRequestInterface;
use totara_api\exception\api_access_exception;
use totara_api\global_api_config;
use totara_api\model\client;
use totara_api\pdo\client_service_account;
use totara_core\http\util;
use totara_oauth2\entity\client_provider;
use totara_oauth2\io\request;
use totara_oauth2\server;
use totara_webapi\graphql;
use totara_webapi\hook\handle_request_pre_hook;

class handle_request_pre_watcher {

    /**
     * @param handle_request_pre_hook $hook
     * @return void
     */
    public static function watch(handle_request_pre_hook $hook): void {
        $execution_context = $hook->execution_context;
        if ($execution_context->get_type() != graphql::TYPE_EXTERNAL ||
            !empty(global_api_config::get_disable_oauth2_authentication())
        ) {
            return;
        }

        try {
            $request = self::validate_access_token();
            $client = self::get_client_by_request($request);
            self::login_api_user($client);

            $client_response_debug = $client->client_settings->response_debug;
            $response_debug = global_api_config::get_response_debug_flag($client_response_debug);
            $hook->server->set_debug($response_debug);

            // Send the oauth2 request, which includes the oauth_client_id property, through to the resolver.
            $execution_context->set_variable('oauth2_request', $request);

            // Send the client model, through to the resolver.
            $execution_context->set_variable('client', $client);

        } catch (\Exception $exception) {
            $hook->set_exception($exception);
        }
    }

    /**
     * @return ServerRequestInterface
     */
    private static function validate_access_token(): ServerRequestInterface {
        $headers = util::get_request_headers();
        if (empty($headers)) {
            throw new api_access_exception('Missing or invalid HTTP request headers');
        }

        // Check for missing Authorization header containing the bearer token. (Apache security default setting can strip this in requests!)
        $header_keys = array_keys($headers);
        $auth_header_found = false;
        $auth_header_wanted = "authorization";
        foreach ($header_keys as $header_key) {
            if (trim(strtolower($header_key)) === $auth_header_wanted) {
                $auth_header_found = true;
                break;
            }
        }

        if (!$auth_header_found) {
            throw new api_access_exception('The request did not contain the required Authorization header. Ensure you set the header in your request and that it is not being stripped by your server or proxy configuration.');
        }

        $oauth2_request = request::create_from_global(
            $_GET,
            $_POST,
            $headers,
            $_SERVER
        );

        $server = server::create();
        $request = $server->verify_request($oauth2_request);

        if (!$request) {
            throw new api_access_exception('Missing, expired or invalid access token. Ensure the Authorization header is set to a valid Bearer token.');
        }

        return $request;
    }

    /**
     * @param ServerRequestInterface $request
     * @return client
     */
    private static function get_client_by_request(ServerRequestInterface $request): client {
        $client_provider = client_provider::repository()->find_by_client_id($request->getAttribute('oauth_client_id'));

        if (is_null($client_provider)) {
            throw new api_access_exception('Couldn\'t identify OAuth2 client provider for this request.');
        }

        $client = $client_provider->clients()->one();
        if (is_null($client)) {
            throw new api_access_exception('Couldn\'t identify API client for this request.');
        }

        return client::load_by_entity($client);
    }

    /**
     * @param client $client
     * @return void
     */
    private static function login_api_user(client $client): void {
        $user = $client->user;
        if (is_null($user)) {
            throw new api_access_exception('Couldn\'t identify API user for this request.');
        }

        if (client_service_account::VALID != client::validate_api_user($user, $client->tenant_id)) {
            throw new api_access_exception('API user is invalid.');
        }

        manager::set_user($user->to_record());
    }

}