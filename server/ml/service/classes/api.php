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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package ml_service
 */

namespace ml_service;

use coding_exception;
use ml_service\auth\token_manager;
use totara_core\http\clients\curl_client;
use totara_core\http\request;
use totara_core\http\response;

/**
 * API helper for calling the remote Totara machine learning service.
 *
 * @package ml_service
 */
class api {
    /**
     * @var curl_client
     */
    protected $client;

    /**
     * @var string|null
     */
    private $last_error;

    /**
     * api constructor.
     *
     * @param curl_client|null $client
     */
    public function __construct(?curl_client $client = null) {
        $this->client = $client ?? new curl_client();
        $this->last_error = null;
    }

    /**
     * @param curl_client|null $client
     * @return api
     */
    public static function make(?curl_client $client = null): api {
        return new self($client);
    }

    /**
     * Call the specific machine learning service endpoint
     *`
     *
     * @param string $endpoint
     * @return response
     */
    public function get(string $endpoint): response {
        $request = request::get($this->make_url($endpoint), $this->make_headers());

        // Keep the connection limits short, in case the ML service is non-responsive.
        $this->client->set_connect_timeout(1);
        $this->client->set_timeout(2);

        return $this->client->execute($request);
    }

    /**
     * Make a call to the ML service for the user recommendations.
     *
     * @param int|null $tenant_id
     * @param int $target_user_id
     * @param string $component
     * @param int|null $count
     * @return array|null
     */
    public function call_user_items(?int $tenant_id, int $target_user_id, string $component,
        ?int $count = null): ?array {
        $params = http_build_query([
            'totara_user_id' => $target_user_id,
            'item_type' => $component,
            'tenant' => $this->normalise_tenant_id($tenant_id),
            'n_items' => $count ?? 10,
        ]);

        return $this->normalise_items_response($this->get('/user-items?' . $params));
    }

    /**
     * Make a call to the similar items request.
     *
     * @param int|null $tenant_id
     * @param string $item_id Must be the component + item id concatenated.
     * @param int|null $count
     * @return null|array
     */
    public function call_similar_items(?int $tenant_id, string $item_id, ?int $count = null): ?array {
        $params = http_build_query([
            'totara_item_id' => $item_id,
            'tenant' => $this->normalise_tenant_id($tenant_id),
            'n_items' => $count ?? 10,
        ]);

        return $this->normalise_items_response($this->get('/similar-items?' . $params));
    }

    /**
     * @return string|null
     */
    public function get_last_error(): ?string {
        return $this->last_error;
    }

    /**
     * Return the full URL to the ML service
     *
     * @param string $endpoint
     * @return string
     */
    protected function make_url(string $endpoint): string {
        global $CFG;
        $service_url = $CFG->ml_service_url ? rtrim($CFG->ml_service_url, '/') : '';
        if (empty($service_url)) {
            throw new coding_exception('No ml_service_url was defined, cannot call the machine learning service.');
        }

        return $service_url . $endpoint;
    }

    /**
     * Attach any headers to the requests
     *
     * @return array
     */
    protected function make_headers(): array {
        $request_time = time();
        return [
            'X-Totara-Ml-Key' => token_manager::make_token($request_time),
            'X-Totara-Time' => $request_time,
        ];
    }

    /**
     * @param int|null $tenant_id
     * @return int
     */
    protected function normalise_tenant_id(?int $tenant_id): int {
        return $tenant_id ?? 0;
    }

    /**
     * Parse the response back, and return the successful items, or persist
     * the error message & return a null.
     *
     * @param response $response
     * @return array|null
     */
    private function normalise_items_response(response $response): ?array {
        if (200 !== $response->get_http_code()) {
            return null;
        }

        $body = $response->get_body_as_json(true);
        if ($body['success']) {
            // Successful response
            return $body['items'];
        }

        $this->last_error = $body['message'];
        return null;
    }
}