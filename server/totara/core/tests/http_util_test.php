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
 * @package totara_core
 */

use totara_core\http\util;

/**
 * @coversDefaultClass \totara_core\http\util
 */
class totara_core_http_util_testcase extends advanced_testcase {
    /**
     * @return array of [_SERVER, expected]
     */
    public function data_getallheaders(): array {
        return [
            'empty' => [[], false],
            'invalid indices' => [
                [
                    'HTTP_CONTENT_LENGTH' => '256',
                    'HTTP_CONTENT_TYPE' => 'application/json',
                ],
                false
            ],
            'get access' => [
                [
                    'HTTP_ACCEPT_ENCODING' => 'gzip, deflate',
                    'HTTP_ACCEPT_LANGUAGE' => 'en-NZ,en;q=0.5',
                    'HTTP_CONNECTION' => 'keep-alive',
                    'HTTP_COOKIE' => 'TotaraSession=K1aKah4N3wzEalAnDenv',
                    'HTTP_HOST' => 'totara.example.com',
                    'HTTP_REFERER' => 'https://totara.example.com/',
                    'HTTP_USER_AGENT' => 'TotaraBot/42',
                    'REQUEST_METHOD' => 'GET',
                ],
                [
                    'Host' => 'totara.example.com',
                    'User-Agent' => 'TotaraBot/42',
                ],
            ],
            'realistic bot access' => [
                [
                    'CONTENT_LENGTH' => '316',
                    'CONTENT_TYPE' => 'application/json',
                    'DOCUMENT_ROOT' => '/var/www/totara/',
                    'DOCUMENT_URI' => '/learn/server/totara/msteams/botindex.php',
                    'FCGI_ROLE' => 'RESPONDER',
                    'HTTPS' => 'on',
                    'HTTP_AUTHORIZATION' => 'Bearer eyJhbGciOiJub25lIn0.W10.bG9yZW1pcHN1bSE_',
                    'HTTP_HOST' => 'totara.example.com',
                    'HTTP_MS_CV' => 'kIa0RAkoUt0u+K47oA.3.1.4.159.2.6',
                    'HTTP_USER_AGENT' => 'Microsoft-SkypeBotApi (Microsoft-BotFramework/99.9)',
                    'LANG' => 'en_NZ.UTF-8',
                    'LANGUAGE' => 'en_NZ.UTF-8',
                    'PHP_SELF' => '/learn/server/totara/msteams/botindex.php',
                    'QUERY_STRING' => '',
                    'REMOTE_ADDR' => '192.168.555.1',
                    'REMOTE_PORT' => '99999',
                    'REQUEST_METHOD' => 'POST',
                    'REQUEST_SCHEME' => 'http',
                    'REQUEST_TIME' => '27182818',
                    'REQUEST_TIME_FLOAT' => '27182818.28459',
                    'REQUEST_URI' => '/learn/server/totara/msteams/botindex.php',
                    'SCRIPT_NAME' => '/learn/server/totara/msteams/botindex.php',
                    'SERVER_ADDR' => '192.168.555.12',
                    'SERVER_NAME' => 'totara.example.com',
                    'SERVER_PORT' => '8080',
                    'SERVER_PROTOCOL' => 'HTTP/1.1',
                    'SERVER_SOFTWARE' => 'TotaraServer/42.195',
                ],
                [
                    'Authorization' => 'Bearer eyJhbGciOiJub25lIn0.W10.bG9yZW1pcHN1bSE_',
                    'Content-Length' => '316',
                    'Content-Type' => 'application/json',
                    'Host' => 'totara.example.com',
                    'User-Agent' => 'Microsoft-SkypeBotApi (Microsoft-BotFramework/99.9)',
                ]
            ]
        ];
    }

    /**
     * @param array $input
     * @param array|false $expected
     * @dataProvider data_getallheaders
     * @covers ::getallheaders_downlevel
     */
    public function test_getallheaders(array $input, $expected) {
        foreach ($_SERVER as $key => $value) {
            unset($_SERVER[$key]);
        }
        foreach ($input as $key => $value) {
            $_SERVER[$key] = $value;
        }
        $method = new ReflectionMethod(util::class, 'getallheaders_polyfill');
        $method->setAccessible(true);
        $result = $method->invoke(null);
        if (is_array($result)) {
            ksort($result);
            $this->assertEquals($expected, $result);
        } else {
            $this->assertSame($expected, $result);
        }
    }
}
