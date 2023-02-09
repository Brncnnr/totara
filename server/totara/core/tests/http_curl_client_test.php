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

use totara_core\http\clients\curl_client;
use totara_core\http\request;

global $CFG;
require_once($CFG->dirroot.'/lib/filelib.php');

class totara_core_http_curl_client_testcase extends advanced_testcase {
    /** @var curl_client */
    private $client;

    /** @var mock_curl */
    private $curl;

    public function setUp(): void {
        $this->client = new curl_client();
        $this->curl = new mock_curl();
        $prop = new ReflectionProperty($this->client, 'curl');
        $prop->setAccessible(true);
        $prop->setValue($this->client, $this->curl);
    }

    public function tearDown(): void {
        $this->client = null;
        $this->curl = null;
    }

    public function test_execute_get(): void {
        $this->curl->mock_response2('https://example.com/api/test', ['CURLOPT_HTTPGET' => 1], ['http_code' => 302, 'content_type' => 'text/plain'], 'Test response');
        $request = request::get('https://example.com/api/test');
        $response = $this->client->execute($request);
        $this->assertEquals(302, $response->get_http_code());
        $this->assertEquals('text/plain', $response->get_content_type());
        $this->assertEquals('Test response', $response->get_body());
    }

    public function test_execute_head(): void {
        $this->curl->mock_response2('https://example.com/api/test/people/me', ['CURLOPT_HTTPGET' => 0, 'CURLOPT_HEADER' => 1, 'CURLOPT_NOBODY' => 1], [], 'Hooray!');
        $request = request::head('https://example.com/api/test/people/me');
        $response = $this->client->execute($request);
        $this->assertEquals('Hooray!', $response->get_body());
    }

    public function test_execute_post(): void {
        $this->curl->mock_response2('https://example.com/api/test/people', ['CURLOPT_POST' => 1, 'CURLOPT_POSTFIELDS' => 'kia=ora&kia=kaha'], ['http_code' => 418], '{"error": "I\'m a teapot"}');
        $request = request::post('https://example.com/api/test/people', 'kia=ora&kia=kaha');
        $response = $this->client->execute($request);
        $this->assertEquals(418, $response->get_http_code());
        $this->assertEquals(['error' => "I'm a teapot"], $response->get_body_as_json(true));
    }

    public function test_execute_put(): void {
        $this->curl->mock_response2('https://example.com/api/test/docs', ['CURLOPT_CUSTOMREQUEST' => 'PUT', 'CURLOPT_POSTFIELDS' => 'New File'], ['http_code' => 201], '<p>New File</p>');
        $request = request::put('https://example.com/api/test/docs', 'New File');
        $response = $this->client->execute($request);
        $this->assertEquals(201, $response->get_http_code());
        $this->assertEquals('<p>New File</p>', $response->get_body());
    }

    public function test_execute_delete(): void {
        $this->curl->mock_response2('https://example.com/api/test/people/me', ['CURLOPT_CUSTOMREQUEST' => 'DELETE', 'CURLOPT_USERPWD' => ''], ['http_code' => 204], '');
        $request = request::delete('https://example.com/api/test/people/me', ['Authorization' => 'bear k!lLm3']);
        $response = $this->client->execute($request);
        $this->assertEquals(204, $response->get_http_code());
        $this->assertEquals('', $response->get_body());
    }

    public function test_execute_patch(): void {
        $this->curl->mock_response2('https://example.com/api/test/people/bob', ['CURLOPT_CUSTOMREQUEST' => 'PATCH', 'CURLOPT_POSTFIELDS' => '{"age":432}'], ['http_code' => 400], 'vampire?');
        $request = request::patch('https://example.com/api/test/people/bob', ['age' => 432]);
        $response = $this->client->execute($request);
        $this->assertEquals(400, $response->get_http_code());
        $this->assertEquals('vampire?', $response->get_body());
    }
}


/**
 * curl with better mock.
 */
class mock_curl extends curl {
    /** @var array */
    private $mocks = [];

    /**
     * @param string $expected_url
     * @param array $expected_options
     * @param array $info
     * @param mixed $response
     */
    public function mock_response2(string $expected_url, array $expected_options, array $info, $response): void {
        array_push($this->mocks, [$expected_url, $expected_options, $info, $response]);
    }

    /**
     * @inheritDoc
     */
    protected function request($url, $options = array()) {
        if (!$this->mocks) {
            throw new coding_exception('no more mocks');
        }
        [$expected_url, $expected_options, $info, $response] = array_shift($this->mocks);
        \core_phpunit\testcase::assertSame($expected_url, $url, "requested URL do not match");
        foreach ($expected_options as $name => $value) {
            \core_phpunit\testcase::assertArrayHasKey($name, $options, "requested options do not have key");
            \core_phpunit\testcase::assertSame($value, $options[$name], "requested options do not match");
        }
        $this->info = array_merge(['http_code' => 200], $info);
        return $response;
    }
}
