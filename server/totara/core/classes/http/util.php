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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\http;

/**
 * A helper class for http.
 */
final class util {

    /**
     * Get the HTTP request headers.
     *
     * The function uses getallheaders() if available.
     * Otherwise the polyfill fills only the following headers.
     * - Host
     * - User-Agent
     * - Authorization
     * - Content-Type
     * - Content-Length
     *
     * @return array|false
     */
    public static function get_request_headers() {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }

        return self::getallheaders_polyfill();
    }

    /**
     * Simulates the getallheaders() function.
     *
     * @return array|false
     */
    private static function getallheaders_polyfill() {
        $mappings = [
            'Host' => 'HTTP_HOST',
            'User-Agent' => 'HTTP_USER_AGENT',
            'Authorization' => 'HTTP_AUTHORIZATION',
            'Content-Type' => 'CONTENT_TYPE',
            'Content-Length' => 'CONTENT_LENGTH',
        ];
        $headers = [];
        foreach ($mappings as $header => $index) {
            if (isset($_SERVER[$index])) {
                $headers[$header] = $_SERVER[$index];
            }
        }
        if (empty($headers)) {
            return false;
        }
        return $headers;
    }

}
