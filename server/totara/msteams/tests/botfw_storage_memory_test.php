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

use totara_msteams\botfw\storage\memory_storage;
use totara_msteams\botfw\storage\storage;

class totara_msteams_botfw_storage_memory_testcase extends advanced_testcase {
    /** @var storage */
    private $storage;

    public function setUp(): void {
        $this->storage = new memory_storage();
    }

    public function tearDown(): void {
        $this->storage = null;
    }

    public function test_bot_load_store() {
        $this->assertNull($this->storage->bot_load('test'));
        $source = new stdClass;
        $source->foo = 'bar';
        $this->storage->bot_store('test', $source);
        $source->foo = 'baz';
        $destination = $this->storage->bot_load('test');
        $this->assertNotEmpty($destination);
        $this->assertEquals('bar', $destination->foo);
    }

    public function test_user_load_store_success() {
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $this->assertNull($this->storage->user_load($user1->id, 'test'));
        $this->assertNull($this->storage->user_load($user2->id, 'test'));
        $source = new stdClass;
        $source->foo = 'bar';
        $this->storage->user_store($user1->id, 'test', $source);
        $source->foo = 'baz';
        $destination = $this->storage->user_load($user1->id, 'test');
        $this->assertNotEmpty($destination);
        $this->assertEquals('bar', $destination->foo);
        $this->assertNull($this->storage->user_load($user2->id, 'test'));
    }

    public function test_user_load_failure() {
        $this->expectException(\coding_exception::class);
        $this->storage->user_load(0, 'test');
    }

    public function test_user_store_failure() {
        $this->expectException(\coding_exception::class);
        $this->storage->user_store(0, 'test', (object)['foo' => 'bar']);
    }
}
