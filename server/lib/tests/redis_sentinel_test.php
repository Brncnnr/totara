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

use core\redis\sentinel;

defined('MOODLE_INTERNAL') || die();

/**
 * Redis Sentinel tests.
 *
 * NOTE: in order to execute this test you need to set up Sentinel instances
 *       and Redis stores with replication.
 *
 * Configuration example:
 *   define('TEST_CORE_REDIS_SENTINEL_HOSTS', '192.168.96.231,192.168.96.232:26379,192.168.96.233');
 *   define('TEST_CORE_REDIS_SENTINEL_PASSWORD', '');
 *   define('TEST_CORE_REDIS_SENTINEL_MASTERNAME', 'mymaster');
 *   define('TEST_CORE_REDIS_SENTINEL_MASTERPASSWORD', '');
 */
class core_redis_sentinel_testcase extends advanced_testcase {
    protected $oldlog;
    protected $newlog;

    protected function setUp(): void {
        global $CFG;

        parent::setUp();
        // Discard error logs.
        $this->oldlog = ini_get('error_log');
        $this->newlog = "$CFG->dataroot/testlog.log";
        @unlink($this->newlog);
        touch($this->newlog);
        ini_set('error_log', $this->newlog);
    }

    protected function tearDown(): void {
        ini_set('error_log', $this->oldlog);
        $this->oldlog = null;
        $this->newlog = null;
        parent::tearDown();
    }

    public function test_resolve_master() {
        $hosts = (defined('TEST_CORE_REDIS_SENTINEL_HOSTS') ? TEST_CORE_REDIS_SENTINEL_HOSTS : '');
        $password = (defined('TEST_CORE_REDIS_SENTINEL_PASSWORD') ? TEST_CORE_REDIS_SENTINEL_PASSWORD : '');
        $mastername = (defined('TEST_CORE_REDIS_SENTINEL_MASTERNAME') ? TEST_CORE_REDIS_SENTINEL_MASTERNAME : '');
        $masterpassword = (defined('TEST_CORE_REDIS_SENTINEL_MASTERPASSWORD') ? TEST_CORE_REDIS_SENTINEL_MASTERPASSWORD : '');

        if (!$hosts || !$mastername) {
            $this->markTestSkipped('Redis Sentinel test configuration missing');
        }
        if (!sentinel::is_supported()) {
            $this->markTestSkipped('Redis Sentinel not supported');
        }

        $redis = sentinel::resolve_master($hosts, $password, $mastername, $masterpassword);
        $this->assertInstanceOf(\Redis::class, $redis);
        $redis->close();
        $this->assertSame('', file_get_contents($this->newlog));

        $redis = sentinel::resolve_master($hosts, $password, $mastername, $masterpassword);
        $this->assertInstanceOf(\Redis::class, $redis);
        $redis->close();
        $this->assertSame('', file_get_contents($this->newlog));

        $redis = sentinel::resolve_master($hosts, $password, $mastername, $masterpassword, true);
        $this->assertIsArray($redis);
        $this->assertArrayHasKey('host', $redis);
        $this->assertArrayHasKey('port', $redis);
        $this->assertSame('', file_get_contents($this->newlog));
    }
}
