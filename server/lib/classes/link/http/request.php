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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core
 */
namespace core\link\http;

final class request {
    /**
     * @param string $url
     * @return response|null
     */
    private static function phpunit_get(string $url): ?response {
        if (class_exists('http_mock_request')) {
            $body = \http_mock_request::get_body($url);
            $headers = \http_mock_request::get_headers($url);

            if (null != $headers && null != $body) {
                // Code can be null, or empty.
                $code = \http_mock_request::get_code($url);
                return response::create_from_params($body, $headers, $code);
            }
        }

        return null;
    }

    /**
     * @param string $url
     * @param int $maxredirect
     * @param string|null $host optional, if IP is passed too it will use it for DNS resolving
     * @param string|null $ip option, if host is passed too it will use it for DNS resolving
     * @return response
     */
    public static function get(string $url, int $maxredirect = 3, string $host = null, string $ip = null): response {
        if (defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
            $response = static::phpunit_get($url);
            if (null !== $response) {
                return $response;
            }

            debugging("For a better phpunit test enviroment, please use 'http_mock_request' instead", DEBUG_DEVELOPER);
        }

        $ch = curl_init($url);
        if (!$ch) {
            throw new \coding_exception("Cannot access to the url");
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        if (!empty($host) && !empty($ip)) {
            curl_setopt($ch, CURLOPT_RESOLVE, ["{$host}:{$ip}"]);
        }

        if ($maxredirect > 0) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_MAXREDIRS, $maxredirect);
        }

        try {
            $result = curl_exec($ch);

            if (!$result) {
                throw new \coding_exception("Unable to query to url '{$url}'");
            }

            $headersize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

            $header = substr($result, 0, $headersize);
            $body = substr($result, $headersize);

            return response::create($body, $header);
        } finally {
            curl_close($ch);
        }
    }
}