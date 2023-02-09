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

use totara_core\http\response;

class totara_core_http_response_testcase extends advanced_testcase {
    public function test_is_ok() {
        $response = new response('', 200, [], null);
        $this->assertTrue($response->is_ok());
        $response = new response('', 201, [], null);
        $this->assertTrue($response->is_ok());
        $response = new response('', 302, [], null);
        $this->assertFalse($response->is_ok());
        $response = new response('', 401, [], null);
        $this->assertFalse($response->is_ok());
        $response = new response('', 403, [], null);
        $this->assertFalse($response->is_ok());
        $response = new response('', 404, [], null);
        $this->assertFalse($response->is_ok());
        $response = new response('', 500, [], null);
        $this->assertFalse($response->is_ok());
        $response = new response('', 502, [], null);
        $this->assertFalse($response->is_ok());
    }

    public function test_has_body() {
        $response = new response('', 200, [], null);
        $this->assertFalse($response->has_body());
        $response = new response('kia ora', 400, [], null);
        $this->assertTrue($response->has_body());
    }

    public function test_get_body_as_json_success() {
        $response = new response('{"kia":"ora"}', 200, [], null);
        $result = $response->get_body_as_json();
        $this->assertEquals((object)['kia' => 'ora'], $result);
        $result = $response->get_body_as_json(true);
        $this->assertEquals(['kia' => 'ora'], $result);
    }

    public function data_get_body_as_json_failure() {
        return [
            [''], ['invalid json']
        ];
    }

    /**
     * @dataProvider data_get_body_as_json_failure
     */
    public function test_get_body_as_json_failure($body) {
        $this->expectException('totara_core\http\exception\bad_format_exception');

        $response = new response($body, 200, [], null);
        $response->get_body_as_json(false, true);
    }

    public function test_throw_if_error_success() {
        $response = new response('', 200, [], null);
        $response->throw_if_error();
    }

    public function data_throw_if_error_failure_auth() {
        return [
            [400], [401]
        ];
    }

    /**
     * @dataProvider data_throw_if_error_failure_auth
     */
    public function test_throw_if_error_failure_auth($code) {
        $this->expectException('totara_core\http\exception\auth_exception');
        $response = new response('', $code, [], null);
        $response->throw_if_error();
    }

    public function data_throw_if_error_failure_request() {
        return [
            [302], [403], [404], [500]
        ];
    }

    /**
     * @dataProvider data_throw_if_error_failure_request
     */
    public function test_throw_if_error_failure_request($code) {
        $this->expectException('totara_core\http\exception\request_exception');

        $response = new response('', $code, [], null);
        $response->throw_if_error();
    }

    public function test_get_response_header() {
        $response = new response('', 200, ['Content-type' => 'text/html']);
        $this->assertEquals('text/html', $response->get_response_header('Content-type'));
        $this->assertEquals('text/html', $response->get_response_header('CONTENT-TYPE'));
        $this->assertEquals('text/html', $response->get_response_header('content-type'));
        $this->assertFalse($response->get_response_header('kontent-type'));
    }

    public function data_try_get_error_message(): array {
        return [
            ['', 200, ''],
            ['kiaora', 400, null],
            ['{"kia":"ora"}', 401, null],
            ['{"error":"error code with no description"}', 402, 'error code with no description'],
            ['{"error":"error code","error_description":"description"}', 403, 'error code: description'],
            ['{"error":{"kia":"ora"}}', 500, null],
            ['{"error":{"message":"error message"}}', 500, 'error message'],
            ['{"error":{"message":"error message","code":"error code"}}', 500, 'error code: error message'],
        ];
    }

    /**
     * @param string $body
     * @param integer $code
     * @param string|null $expected
     * @dataProvider data_try_get_error_message
     */
    public function test_try_get_error_message(string $body, int $code, ?string $expected) {
        $response = new response($body, $code, []);
        $this->assertSame($expected, $response->try_get_error_message());
    }
}
