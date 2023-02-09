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
namespace contentmarketplace_linkedin\api\v2;

use contentmarketplace_linkedin\api\api as base;
use contentmarketplace_linkedin\api\response\result;
use contentmarketplace_linkedin\api\v2\service\service;
use contentmarketplace_linkedin\oauth\oauth_2;
use moodle_url;
use Throwable;
use totara_contentmarketplace\exception\invalid_token;
use totara_contentmarketplace\oauth\oauth_2_client;
use totara_core\http\client;
use totara_core\http\exception\auth_exception;
use totara_core\http\response;

/**
 * Note that this class does not
 */
class api extends base {
    /**
     * @var bool
     */
    private $retry_after_fail_auth;

    /**
     * @var Throwable[]
     */
    private $exceptions_catched_on_retry;

    /**
     * @return string
     */
    public static function get_version(): string {
        return 'v2';
    }

    /**
     * api constructor.
     * @param oauth_2_client $oauth_2_client
     * @param client|null    $client
     */
    protected function __construct(oauth_2_client $oauth_2_client, ?client $client = null) {
        parent::__construct($oauth_2_client, $client);
        $this->retry_after_fail_auth = true;
        $this->exceptions_catched_on_retry = [];
    }

    /**
     * @return Throwable[]
     */
    public function get_exception_catched_on_retry(): array {
        return $this->exceptions_catched_on_retry;
    }

    /**
     * @param client              $client
     * @param oauth_2_client|null $oauth_2_client
     * @return api
     */
    public static function create(client $client, ?oauth_2_client $oauth_2_client = null): api {
        if (null === $oauth_2_client) {
            $oauth = oauth_2::create_from_config();
            $oauth_2_client = new oauth_2_client($oauth, $client);
        }

        return new static($oauth_2_client, $client);
    }

    /**
     * @param bool $value
     * @return void
     */
    public function set_retry_after_auth(bool $value): void {
        $this->retry_after_fail_auth = $value;
    }

    /**
     * @param service $service
     * @return result
     */
    public function execute(service $service): result {
        $endpoint_url = $this->get_endpoint_url();
        $endpoint_url = $service->apply_to_url($endpoint_url);

        try {
            $response = $this->do_request($endpoint_url);
        } catch (invalid_token $invalid_token) {
            if (!$this->retry_after_fail_auth) {
                throw $invalid_token;
            }

            // Capture this exception, for debugging needs.
            $this->exceptions_catched_on_retry[] = $invalid_token;
            $response = $this->do_request($endpoint_url, true);
        } catch (auth_exception $auth_exception) {
            if (!$this->retry_after_fail_auth) {
                throw  $auth_exception;
            }

            // Capture this exception, for debugging needs.
            $this->exceptions_catched_on_retry[] = $auth_exception;
            $response = $this->do_request($endpoint_url, true);
        }

        return $service->wrap_response($response);
    }

    /**
     * @param moodle_url $url
     * @param bool       $renew_token
     *
     * @return response
     */
    protected function do_request(moodle_url $url, bool $renew_token = false): response {
        if ($renew_token) {
            $this->oauth_2_client->refresh_token();
        }

        $request = $this->prepare_get_request_from_url($url);
        $response = $this->client->execute($request);

        $response->throw_if_error();
        return $response;
    }
}