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
 * @package totara_msteams
 */

namespace totara_msteams\botfw\util;

use totara_core\http\response_code;
use totara_core\http\util;

/**
 * A helper class for http.
 */
final class http {
    /** @deprecated since Totara 15.0 */
    const OK = response_code::OK;
    /** @deprecated since Totara 15.0 */
    const BAD_REQUEST = response_code::BAD_REQUEST;
    /** @deprecated since Totara 15.0 */
    const UNAUTHORIZED = response_code::UNAUTHORIZED;
    /** @deprecated since Totara 15.0 */
    const FORBIDDEN = response_code::FORBIDDEN;
    /** @deprecated since Totara 15.0 */
    const NOT_FOUND = response_code::NOT_FOUND;
    /** @deprecated since Totara 15.0 */
    const INTERNAL_SERVER_ERROR = response_code::INTERNAL_SERVER_ERROR;
    /** @deprecated since Totara 15.0 */
    const SERVICE_UNAVAILABLE = response_code::SERVICE_UNAVAILABLE;

    /**
     * @var string[]
     * @deprecated since Totara 15.0
     */
    static private $mapping = [
        'Host' => 'HTTP_HOST',
        'User-Agent' => 'HTTP_USER_AGENT',
        'Authorization' => 'HTTP_AUTHORIZATION',
        'Content-Type' => 'CONTENT_TYPE',
        'Content-Length' => 'CONTENT_LENGTH',
    ];

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
     * @deprecated since Totara 15.0
     */
    public static function get_request_headers() {
        debugging('\totara_msteams\botfw\util\http::get_request_headers is deprecated, please use \totara_core\http\util::get_request_headers instead.', DEBUG_DEVELOPER);
        return util::get_request_headers();
    }

    /**
     * Simulate getallheaders().
     *
     * @return array|false
     * @deprecated since Totara 15.0
     */
    private static function getallheaders_downlevel() {
        debugging('\totara_msteams\botfw\util\http::getallheaders_downlevel is deprecated, please use \totara_core\http\util::get_request_headers instead.', DEBUG_DEVELOPER);
        return util::get_request_headers();
    }

    /**
     * Send HTTP status code to inform an error.
     *
     * @param int $code HTTP status code
     * @return true
     */
    public static function send_error(int $code): bool {
        header('Content-type: application/json', true, $code);
        return true;
    }
}
