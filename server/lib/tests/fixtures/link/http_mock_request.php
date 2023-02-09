<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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

use core\link\http\response;

class http_mock_request {
    /**
     * @var array
     */
    private static $defaulturls;

    /**
     * @param string $url
     * @param string $htmlfile
     * @param array $headers
     * @param int   $code
     *
     * @return void
     */
    public static function add_url(string $url, string $htmlfile,
                                   array $headers = [], int $code = response::HTTP_OK): void {
        if (!defined('PHPUNIT_TEST') || !PHPUNIT_TEST) {
            debugging("Not a unit test environment", DEBUG_DEVELOPER);
            return;
        }

        if (!isset(static::$defaulturls)) {
            static::$defaulturls = [];
        }

        if (isset(static::$defaulturls[$url])) {
            debugging("Resetting the url", DEBUG_DEVELOPER);
        }

        if (!isset($headers['content-type'])) {
            // Make text/html content-type as default header, so that the response can detect the page to be
            // a html page.
            $headers['content-type'] = 'text/html; charset=utf-8';
        }

        static::$defaulturls[$url] = [
            'htmlfile' => $htmlfile,
            'headers' => $headers,
            'code' => $code
        ];
    }

    /**
     * @param string $url
     * @return string
     */
    public static function get_body(string $url): ?string {
        if (!isset(static::$defaulturls[$url])) {
            return null;
        }

        $file = static::$defaulturls[$url]['htmlfile'];

        if (!file_exists($file)) {
            throw new \coding_exception("The file for html body is not existing: '{$file}'");
        }

        return file_get_contents($file);
    }

    /**
     * @param string $url
     * @return array|null
     */
    public static function get_headers(string $url): ?array {
        if (!isset(static::$defaulturls[$url])) {
            return null;
        }

        return static::$defaulturls[$url]['headers'];
    }

    /**
     * @param string $url
     * @return int|null
     */
    public static function get_code(string $url): ?int {
        if (!isset(static::$defaulturls[$url])) {
            return null;
        }

        return static::$defaulturls[$url]['code'];
    }

    /**
     * @return void
     */
    public static function clear(): void {
        static::$defaulturls = [];
    }
}