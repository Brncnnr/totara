<?php
/*
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
 * @author  Petr Skoda <petr.skoda@totaralearning.com>
 * @package core
 */

use core\redis\util;

defined('MOODLE_INTERNAL') || die();

class core_redis_util_testcase extends advanced_testcase {
    public function test_parse_host() {

        // IPv4 and host names.

        $expected = ['host' => 'localhost', 'port' => 26379];
        $this->assertSame($expected, util::parse_host('localhost', 26379));
        $this->assertSame($expected, util::parse_host('localhost', 26379, false));
        $this->assertSame($expected, util::parse_host('localhost', 26379, true));

        $expected = ['host' => 'localhost', 'port' => 26379];
        $this->assertSame($expected, util::parse_host('localhost:26379', 6379));
        $this->assertSame($expected, util::parse_host('localhost:26379', 6379, false));
        $this->assertSame($expected, util::parse_host('localhost:26379', 6379, true));

        $expected = ['host' => '127.0.0.1', 'port' => 26379];
        $this->assertSame($expected, util::parse_host('127.0.0.1', 26379));
        $this->assertSame($expected, util::parse_host('127.0.0.1', 26379, false));
        $this->assertSame($expected, util::parse_host('127.0.0.1', 26379, true));

        $expected = ['host' => '127.0.0.1', 'port' => 6379];
        $this->assertSame($expected, util::parse_host('127.0.0.1:6379', 6379));
        $this->assertSame($expected, util::parse_host('127.0.0.1:6379', 6379, false));
        $this->assertSame($expected, util::parse_host('127.0.0.1:6379', 6379, true));

        $expected = ['host' => 'www.example.com', 'port' => 6379];
        $this->assertSame($expected, util::parse_host('www.example.com', 6379));
        $this->assertSame($expected, util::parse_host('www.example.com', 6379, false));
        $this->assertSame($expected, util::parse_host('www.example.com', 6379, true));

        $expected = ['host' => 'www.example.com', 'port' => 6379];
        $this->assertSame($expected, util::parse_host('www.example.com:6379', 6379));
        $this->assertSame($expected, util::parse_host('www.example.com:6379', 6379, false));
        $this->assertSame($expected, util::parse_host('www.example.com:6379', 6379, true));

        $expected = ['host' => 'localhost', 'port' => 26379];
        $this->assertSame($expected, util::parse_host(' localhost ', 26379));

        $expected = ['host' => 'localhost', 'port' => 26379];
        $this->assertSame($expected, util::parse_host(' localhost:26379 ', 26379));

        $expected = ['host' => 'localhost', 'port' => 123];
        $this->assertSame($expected, util::parse_host('localhost', 123));

        // IPv6.

        $expected = ['host' => '::1', 'port' => 6379];
        $this->assertSame($expected, util::parse_host('[::1]', 6379));
        $this->assertSame($expected, util::parse_host('[::1]', 6379, false));
        $this->assertSame($expected, util::parse_host('[::1]', 6379, true));

        $expected = ['host' => '::1', 'port' => 6379];
        $this->assertSame($expected, util::parse_host('[::1]:6379', 26379));

        $expected = ['host' => '2001:db8::8a2e:370:7334', 'port' => 26379];
        $this->assertSame($expected, util::parse_host('[2001:db8::8a2e:370:7334]', 26379));

        $expected = ['host' => '2001:db8::8a2e:370:7334', 'port' => 26379];
        $this->assertSame($expected, util::parse_host('[2001:db8::8a2e:370:7334]:26379', 26379));
        $this->assertSame($expected, util::parse_host('[2001:db8::8a2e:370:7334]:26379', 26379, false));
        $this->assertSame($expected, util::parse_host('[2001:db8::8a2e:370:7334]:26379', 26379, true));

        $expected = ['host' => '::1', 'port' => 6379];
        $this->assertSame($expected, util::parse_host(' [::1] ', 6379));

        $expected = ['host' => '::1', 'port' => 6379];
        $this->assertSame($expected, util::parse_host(' [::1]:6379 ', 6379));

        // Unix socket.

        $expected = ['host' => '/var/whatever', 'port' => null];
        $this->assertSame($expected, util::parse_host('/var/whatever', 6379, true));
        $this->assertNull(util::parse_host('/var/whatever', 6379, false));
        $this->assertNull(util::parse_host('/var/whatever', 6379));

        // Malformed.

        $this->assertNull(util::parse_host('', 6379));
        $this->assertNull(util::parse_host(' ', 6379));
        $this->assertNull(util::parse_host('local:host', 6379));
        $this->assertNull(util::parse_host('local]host', 6379));
        $this->assertNull(util::parse_host('local:host:26379', 6379));
        $this->assertNull(util::parse_host('::1', 6379));
        $this->assertNull(util::parse_host('2001:db8::8a2e:370:7334', 6379));
        $this->assertNull(util::parse_host('2001:db8::8a 2e:370:7334', 6379));
        $this->assertNull(util::parse_host('local host', 6379));
        $this->assertNull(util::parse_host('[2001:db8]::8a2e:370:7334]', 6379));
        $this->assertNull(util::parse_host('[2001:db8]::8a2e:370:7334];6379', 6379));
        $this->assertNull(util::parse_host('2001:[db8]::8a2e:370:7334', 6379));
    }

    public function test_parse_redis_hosts() {
        $hosts = '';
        $expected = [];
        $this->assertSame($expected, util::parse_redis_hosts($hosts));

        $hosts = ' ';
        $expected = [];
        $this->assertSame($expected, util::parse_redis_hosts($hosts));

        $hosts = '127.0.0.1';
        $expected = [['host' => '127.0.0.1', 'port' => 6379]];
        $this->assertSame($expected, util::parse_redis_hosts($hosts));

        $hosts = '127.0.0.1:123, localhost,www.example.com,[::1],,::3,/var/x';
        $expected = [
            ['host' => '127.0.0.1', 'port' => 123],
            ['host' => 'localhost', 'port' => 6379],
            ['host' => 'www.example.com', 'port' => 6379],
            ['host' => '::1', 'port' => 6379],
        ];
        $this->assertSame($expected, util::parse_redis_hosts($hosts));
        $this->assertSame($expected, util::parse_redis_hosts($hosts, false));

        $expected = [
            ['host' => '127.0.0.1', 'port' => 123],
            ['host' => 'localhost', 'port' => 6379],
            ['host' => 'www.example.com', 'port' => 6379],
            ['host' => '::1', 'port' => 6379],
            ['host' => '/var/x', 'port' => null],
        ];
        $this->assertSame($expected, util::parse_redis_hosts($hosts, true));
    }

    public function test_parse_sentinel_hosts() {
        $hosts = '';
        $expected = [];
        $this->assertSame($expected, util::parse_sentinel_hosts($hosts));

        $hosts = ' ';
        $expected = [];
        $this->assertSame($expected, util::parse_sentinel_hosts($hosts));

        $hosts = '127.0.0.1';
        $expected = [['host' => '127.0.0.1', 'port' => 26379]];
        $this->assertSame($expected, util::parse_sentinel_hosts($hosts));

        $hosts = '127.0.0.1:123, localhost,www.example.com,[::1],,::3';
        $expected = [
            ['host' => '127.0.0.1', 'port' => 123],
            ['host' => 'localhost', 'port' => 26379],
            ['host' => 'www.example.com', 'port' => 26379],
            ['host' => '::1', 'port' => 26379],
        ];
        $this->assertSame($expected, util::parse_sentinel_hosts($hosts));
    }
}
