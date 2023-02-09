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
defined('MOODLE_INTERNAL') || die();

use core_phpunit\testcase;
use ml_service\api;
use totara_core\http\clients\curl_client;
use totara_core\http\response;

class ml_service_api_testcase extends testcase {

    /**
     * @return void
     */
    public function test_get(): void {
        global $CFG;

        $curl_client = $this->createMock(curl_client::class);
        $curl_client->method('execute')
            ->willReturnCallback(function ($request) {
                // To assist testing, we'll stick the request URL in the response body
                return new response('url: ' . $request->get_url(), 200, []);
            });
        $api = api::make($curl_client);

        $CFG->ml_service_key = 'abc123';
        $CFG->ml_service_url = 'http://example1.com';
        $response = $api->get('/my/test.php');
        self::assertEquals('url: http://example1.com/my/test.php', $response->get_body());

        $CFG->ml_service_url = 'http://example.com/';
        $response = $api->get('/my/test.php');
        self::assertEquals('url: http://example.com/my/test.php', $response->get_body());

        self::expectException(coding_exception::class);
        self::expectExceptionMessage('No ml_service_url was defined, cannot call the machine learning service.');
        $CFG->ml_service_url = null;
        $api->get('/my/test.php');
    }
}