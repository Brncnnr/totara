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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package message_totara_airnotifier
 */

namespace message_totara_airnotifier;

defined('MOODLE_INTERNAL') || die();

// Not for production use.
if (!defined('BEHAT_SITE_RUNNING') || !BEHAT_SITE_RUNNING) {
    die();
}

/**
 * Class airnotifier_mock_server implements methods for responding to AirNotifier requests as if we are a server.
 *
 * @package message_totara_airnotifier
 */
class airnotifier_mock_server {

    /**
     * Mock a register device response.
     *
     * @param \stdClass $request
     * @return void
     */
    public static function register_device(\stdClass $request): void {
        if (empty($request->device) || empty($request->token) || empty($request->channel)) {
            self::response('500');
        } else {
            self::response('200 OK', [
                'X-Powered-By: AirNotifier/1.0',
                'Content-Type: application/json; charset=utf-8',
                'Server: TornadoServer/2.2',
            ]);
        }
    }

    /**
     * Mock a delete_device response.
     *
     * @param \stdClass $request
     * @return void
     */
    public static function delete_device(\stdClass $request): void {
        self::response('200 OK', [
            'X-Powered-By: AirNotifier/1.0',
            'Content-Type: application/json; charset=utf-8',
            'Server: TornadoServer/2.2',
        ]);
    }

    /**
     * Mock a push request response.
     *
     * @param \stdClass $request
     * @return void
     */
    public static function push(\stdClass $request): void {
        if (empty($request->device) || empty($request->token) || empty($request->alert)) {
            self::response('500');
        } else {
            self::response('202 Accepted', [
                'Content-Type: application/json; charset=utf-8',
                'Server: TornadoServer/2.2',
            ]);
        }
    }

    /**
     * Generate an HTTP response
     *
     * @param string $http_response_code
     * @param array $headers
     */
    public static function response(string $http_response_code, array $headers = array()): void {
        header('HTTP/1.1 ' . $http_response_code);
        foreach ($headers as $header) {
            header($header);
        }
        exit('');
    }
}