<?php
/**
 * This file is part of Totara Learn
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
 * @package contentmarketplace_linkedin
 */
namespace contentmarketplace_linkedin\oauth;

use coding_exception;
use contentmarketplace_linkedin\config;
use DateTime;
use totara_contentmarketplace\exception\cannot_obtain_token;
use totara_contentmarketplace\oauth\oauth_2 as base;
use totara_contentmarketplace\token\token;
use totara_core\http\formdata;
use totara_core\http\request;
use totara_core\http\response;

class oauth_2 extends base {
    /**
     * @var string
     */
    protected const CLIENT_CREDENTIALS = 'client_credentials';

    /**
     * @var string
     */
    private $client_secret;

    /**
     * @var string
     */
    private $client_id;

    /**
     * @var int
     */
    private $time_now;

    /**
     * oauth_2 constructor.
     * @param string   $client_secret
     * @param string   $client_id
     * @param int|null $time_now
     */
    public function __construct(string $client_secret, string $client_id, ?int $time_now = null) {
        $this->client_secret = $client_secret;
        $this->client_id = $client_id;
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
     * @return oauth_2
     */
    public static function create_from_config(): oauth_2 {
        $secret = config::client_secret();
        $id = config::client_id();

        if (empty($secret) || empty($id)) {
            throw new coding_exception("Either the client's secret or client's id was not set up correctly");
        }

        return new static($secret, $id);
    }

    /**
     * @return request
     */
    public function prepare_request(): request {
        // See https://docs.microsoft.com/en-us/linkedin/learning/getting-started/authentication for more information.
        // about the post data type.
        $post_data = new formdata([
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => static::CLIENT_CREDENTIALS,
        ]);

        return request::post(
            config::access_token_endpoint(),
            $post_data,
            ['Content-Type' => 'application/x-www-form-urlencoded']
        );
    }

    /**
     * @param response $response
     * @return token
     */
    public function process_token_response(response $response): token {
        // We are expecting json response.
        $json_data = $response->get_body_as_json(true, true);

        if (!isset($json_data['access_token'])) {
            throw cannot_obtain_token::on_no_provided_key('access_token');
        } else if (!isset($json_data['expires_in'])) {
            throw cannot_obtain_token::on_no_provided_key('expires_in');
        }

        // Clean access token value.
        $access_token = clean_param($json_data['access_token'], PARAM_ALPHANUMEXT);
        if (empty($access_token)) {
            throw cannot_obtain_token::on_invalid_value('access_token');
        }

        // Clean time expires value.
        $expires_in = clean_param($json_data['expires_in'], PARAM_INT);
        if (empty($expires_in)) {
            throw cannot_obtain_token::on_invalid_value('expires_in');
        }

        $time_response = $this->time_now;
        $response_date = $response->get_response_header('date');

        if (!empty($response_date)) {
            // If we have a response date, then it should be the time for us to check against.
            // Because based on LiL documentation, the field expires_in is the  number of seconds remaining,
            // from the time it was requested before the token will expire.
            $response_date = new DateTime($response_date);
            $time_response = $response_date->getTimestamp();
        }

        $time_expired = ($time_response + $expires_in);

        config::save_access_token($access_token);
        config::save_access_token_expiry($time_expired);

        return new token($access_token, $time_expired);
    }

    /**
     * @return token|null
     */
    public function get_current_token(): ?token {
        $value = config::access_token();
        if (empty($value)) {
            return null;
        }

        return new token($value, config::access_token_expiry());
    }

    /**
     * @return void
     */
    public function invalidate_current_token(): void {
        config::save_access_token(null);
        config::save_access_token_expiry(null);
    }
}