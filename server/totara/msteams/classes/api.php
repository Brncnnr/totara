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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_msteams
 */
namespace totara_msteams;

use moodle_url;
use totara_core\http\client;
use totara_core\http\clients\curl_client;
use totara_core\http\formdata;
use totara_core\http\request;
use totara_core\http\response;

final class api {
    /**
     * @var client
     */
    protected $client;

    /**
     * api constructor.
     * @param client|null $client
     */
    public function __construct(?client $client = null) {
        if (is_null($client)) {
            $this->client = new curl_client();
        } else {
            $this->client = $client;
        }
    }

    /**
     * @param string $domain
     * @return string
     */
    public function get_tenant_id_endpoint_url(string $domain): string {
        return "https://login.microsoftonline.com/{$domain}/.well-known/openid-configuration";
    }

    /**
     * @param string|moodle_url $url
     * @param string|formdata|array|object $post_data
     * @param string[]|null $headers
     *
     * @return response
     */
    public function post($url, $post_data, ?array $headers = null): response {
        $request = request::post($url, $post_data, $headers);
        return $this->client->execute($request);
    }

    /**
     * @param string|moodle_url $url
     * @param string[]|null $headers
     *
     * @return response
     */
    public function get($url, ?array $headers = null): response {
        $request = request::get($url, $headers);
        return $this->client->execute($request);
    }

    /**
     * @return string
     */
    public function get_gateway_url(): string {
        global $CFG;
        return $CFG->msteams_gateway_url;
    }
}