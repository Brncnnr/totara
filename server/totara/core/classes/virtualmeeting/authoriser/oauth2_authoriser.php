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
 * @author  Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\virtualmeeting\authoriser;

use core\plugininfo\virtualmeeting;
use totara_core\entity\virtual_meeting_auth;
use totara_core\virtualmeeting\exception\auth_exception;
use moodle_url;
use totara_core\http\client;
use totara_core\http\exception\bad_format_exception;
use totara_core\http\exception\http_exception;
use totara_core\http\formdata;
use totara_core\http\request;
use totara_core\http\response;

/**
 * Provides a generic OAuth2 implementation. (RFC 6749)
 */
final class oauth2_authoriser implements authoriser {

    /** default expiration: 2 seconds less than 1 hour */
    const DEFAULT_EXPIRATION = 3598;

    /** @var client */
    private $client;

    /** @var string */
    private $token_endpoint;

    /** @var formdata */
    private $formdata;

    /** @var null|array */
    private $headers;

    /**
     * Constructor.
     *
     * @param client $client
     * @param string $token_endpoint token endpoint URL
     * @param string $scope
     * @param array $params optional query parameters such as client ID and client secret
     * @param string[]|null $headers
     */
    public function __construct(client $client, string $token_endpoint, string $scope, array $params = [], ?array $headers = null) {
        $this->client = $client;
        $this->token_endpoint = $token_endpoint;
        $this->formdata = (new formdata($params));
        if ($scope != '') {
            $this->formdata->set('scope', $scope);
        }
        $this->headers = $headers;
    }

    /**
     * Get the URL of the common redirection point.
     *
     * @param string $pluginname
     * @return string
     */
    private static function get_auth_redirect_url(string $pluginname): string {
        global $CFG;
        return $CFG->wwwroot.'/integrations/virtualmeeting/auth_callback.php/'.$pluginname;
    }

    /**
     * Get the OAuth2 login URL.
     *
     * @param string $pluginname plugin name
     * @param string $auth_endpoint auth endpoint URL
     * @param string $client_id
     * @param string $scope
     * @param string[] $params optional query parameters such as client ID
     * @return string
     */
    public static function make_login_url(string $pluginname, string $auth_endpoint, string $client_id, string $scope, array $params = []): string {
        $plugin = virtualmeeting::load_available($pluginname);
        $params = array_merge($params, [
            'client_id' => $client_id,
            'response_type' => 'code',
            'redirect_uri' => self::get_auth_redirect_url($plugin->name),
        ]);
        if ($scope != '') {
            $params = array_merge($params, ['scope' => $scope]);
        }
        return (new moodle_url($auth_endpoint, $params))->out(false);
    }

    /**
     * Update the database record.
     *
     * @param virtual_meeting_auth $entity
     * @param response $response
     * @param integer $time
     */
    private function store(virtual_meeting_auth $entity, response $response, int $time): void {
        try {
            $response->throw_if_error();
            $json = $response->get_body_as_json(false, true);
            if (!isset($json->access_token) || !isset($json->refresh_token)) {
                throw new bad_format_exception('invalid access token response');
            }
            $expires = $json->expires_in ?? self::DEFAULT_EXPIRATION;
            $entity->access_token = $json->access_token;
            $entity->timeexpiry = $time + $expires;
            $entity->refresh_token = $json->refresh_token;
            $entity->save();
        } catch (http_exception $ex) {
            throw auth_exception::invalid_response($ex);
        }
    }

    /**
     * Handle authorisation response.
     *
     * @param virtual_meeting_auth $entity
     * @param string $code OAuth2 authorisation code
     */
    public function authorise(virtual_meeting_auth $entity, string $code): void {
        $formdata = (clone $this->formdata)
            ->set('code', $code)
            ->set('grant_type', 'authorization_code')
            ->set('redirect_uri', self::get_auth_redirect_url($entity->plugin));
        $request = request::post($this->token_endpoint, $formdata, $this->headers);
        $time = time();
        $response = $this->client->execute($request);
        $this->store($entity, $response, $time);
    }

    /**
     * Refresh the OAuth2 token of the user.
     *
     * @param virtual_meeting_auth $entity
     */
    public function refresh(virtual_meeting_auth $entity): void {
        $formdata = (clone $this->formdata)
            ->set('grant_type', 'refresh_token')
            ->set('refresh_token', $entity->refresh_token);
        $request = request::post($this->token_endpoint, $formdata, $this->headers);
        $time = time();
        $response = $this->client->execute($request);
        $this->store($entity, $response, $time);
    }
}
