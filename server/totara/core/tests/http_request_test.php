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

use totara_core\http\formdata;
use totara_core\http\method;
use totara_core\http\request;

class totara_core_http_request_testcase extends advanced_testcase {
    /** @var request */
    private $request;

    public function setUp(): void {
        $this->request = new request('https://example.com/wine/bar');
    }

    public function tearDown(): void {
        $this->request = null;
    }

    public function test_constructor() {
        $request = new request('https://example.com/foo/bar');
        $this->assertEquals('https://example.com/foo/bar', $request->get_url());
        $this->assertEquals(method::GET, $request->get_method());
        $this->assertEquals([], $request->get_headers());
        $this->assertSame('', $request->get_post_data());
    }

    public function test_method() {
        // GET
        $this->request->set_method(method::GET);
        $result = $this->request->get_method();
        $this->assertEquals(method::GET, $result);
        // POST
        $this->request->set_method('post');
        $result = $this->request->get_method();
        $this->assertEquals(method::POST, $result);
        // HEAD
        $this->request->set_method('Head');
        $result = $this->request->get_method();
        $this->assertEquals(method::HEAD, $result);
        // PUT
        $this->request->set_method('pUT');
        $result = $this->request->get_method();
        $this->assertEquals(method::PUT, $result);
        // PATCH
        $this->request->set_method('PatCH');
        $result = $this->request->get_method();
        $this->assertEquals(method::PATCH, $result);
        // DELETE
        $this->request->set_method('DELETE');
        $result = $this->request->get_method();
        $this->assertEquals(method::DELETE, $result);
        // Undefined
        try {
            $this->request->set_method('panepane');
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
        }
    }

    public function test_header_no_data() {
        // set_header() makes a header name Title-Case.
        $this->request->set_header('user-agent', 'TotaraBot/1.0');
        $result = $this->request->get_headers();
        $this->assertEquals(['User-Agent: TotaraBot/1.0'], $result);
        $this->request->set_header('User-Agent', 'TotaraBot/2.0');
        $result = $this->request->get_headers();
        $this->assertEquals(['User-Agent: TotaraBot/2.0'], $result);
    }

    public function test_header_post_data() {
        $this->request->set_post_data('kia ora koutou');
        $result = $this->request->get_headers();
        $this->assertEquals([], $result);
        // application/x-www-form-urlencoded has charset
        $this->request->set_post_data(new formdata(['kia' => 'ora', 'kou' => 'tou']));
        $result = $this->request->get_headers();
        $this->assertEquals(['Content-Type: application/x-www-form-urlencoded; charset=utf-8'], $result);
        // application/json does not have charset
        $this->request->set_post_data((object)['kia' => 'ora', 'kou' => 'tou']);
        $result = $this->request->get_headers();
        $this->assertEquals(['Content-Type: application/json'], $result);
        $this->request->set_header('Content-Type', 'application/x-totara');
        $result = $this->request->get_headers();
        $this->assertEquals(['Content-Type: application/x-totara'], $result);
    }

    public function test_post_data() {
        // post plain text
        $this->request->set_post_data('kia ora koutou');
        $result = $this->request->get_post_data();
        $this->assertEquals('kia ora koutou', $result);
        // post application/x-www-form-urlencoded
        $this->request->set_post_data(new formdata(['kia' => 'ora', 'kou' => 'tou']));
        $result = $this->request->get_post_data();
        $this->assertEquals('kia=ora&kou=tou', $result);
        // post application/json
        $this->request->set_post_data((object)['kia' => 'ora', 'kou' => 'tou']);
        $result = $this->request->get_post_data();
        $this->assertEquals('{"kia":"ora","kou":"tou"}', $result);
    }
}
