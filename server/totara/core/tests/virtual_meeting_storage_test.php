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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_core
 */

use core\orm\query\builder;
use core\orm\query\exceptions\record_not_found_exception;
use totara_core\entity\virtual_meeting as virtual_meeting_entity;
use totara_core\entity\virtual_meeting_config as virtual_meeting_config_entity;
use totara_core\virtualmeeting\storage;

/**
 * @group virtualmeeting
 * @coversDefaultClass totara_core\virtualmeeting\storage
 */
class totara_core_virtual_meeting_storage_testcase extends advanced_testcase {
    /** @var stdClass */
    private $user;

    /** @var virtual_meeting_entity */
    private $vm1;

    /** @var virtual_meeting_entity */
    private $vm2;

    public function setUp(): void {
        parent::setUp();
        $this->user = $this->getDataGenerator()->create_user();
        $this->vm1 = new virtual_meeting_entity();
        $this->vm1->plugin = 'poc_app';
        $this->vm1->userid = $this->user->id;
        $this->vm1->save();
        $this->vm2 = new virtual_meeting_entity();
        $this->vm2->plugin = 'poc_app';
        $this->vm2->userid = $this->user->id;
        $this->vm2->save();
        $vmc1 = new virtual_meeting_config_entity();
        $vmc1->name = 'test';
        $vmc1->value = 'one';
        $vmc1->virtualmeetingid = $this->vm1->id;
        $vmc1->save();
        $vmc2 = new virtual_meeting_config_entity();
        $vmc2->name = 'test';
        $vmc2->value = 'two';
        $vmc2->virtualmeetingid = $this->vm2->id;
        $vmc2->save();
        $this->assertEquals(2, virtual_meeting_config_entity::repository()->count());
    }

    public function tearDown(): void {
        parent::tearDown();
        $this->user = null;
        $this->vm1 = null;
        $this->vm2 = null;
    }

    /**
     * @covers ::get
     * @covers ::__construct
     * @covers ::repository
     */
    public function test_get(): void {
        $storage = new storage($this->vm1);
        $this->assertEquals('one', $storage->get('test'));
        $storage = new storage($this->vm2);
        $this->assertEquals('two', $storage->get('test'));
        $this->assertNull($storage->get('he-who-must-not-exist'));
        try {
            $storage->get('he-who-must-not-exist', true);
            $this->fail('record_not_found_exception expected');
        } catch (record_not_found_exception $ex) {
        }
        $this->vm2->delete();
        $this->assertNull($storage->get('test'));
        try {
            $storage->get('test', true);
            $this->fail('record_not_found_exception expected');
        } catch (record_not_found_exception $ex) {
        }
    }

    /**
     * @covers ::set
     */
    public function test_set(): void {
        $storage = new storage($this->vm1);
        $storage->set('test', 'new one');
        $this->assertEquals(2, virtual_meeting_config_entity::repository()->count());
        $this->assertEquals('new one', virtual_meeting_config_entity::repository()->where('virtualmeetingid', $this->vm1->id)->where('name', 'test')->one(true)->value);
        $storage->set('test2', 'one more');
        $this->assertEquals(3, virtual_meeting_config_entity::repository()->count());
        $this->assertEquals('one more', virtual_meeting_config_entity::repository()->where('virtualmeetingid', $this->vm1->id)->where('name', 'test2')->one(true)->value);
    }

    /**
     * @covers ::delete
     */
    public function test_delete(): void {
        $storage = new storage($this->vm1);
        $storage->delete('test');
        $this->assertEquals(1, virtual_meeting_config_entity::repository()->count());
        $storage = new storage($this->vm2);
        $storage->delete('he-who-must-not-exist');
        $this->assertEquals(1, virtual_meeting_config_entity::repository()->count());
    }

    /**
     * @covers ::delete_all
     */
    public function test_delete_all(): void {
        $storage = new storage($this->vm1);
        $storage->delete_all();
        $this->assertEquals(1, virtual_meeting_config_entity::repository()->count());
        $this->assertEquals('two', virtual_meeting_config_entity::repository()->where('virtualmeetingid', $this->vm2->id)->where('name', 'test')->one(true)->value);
    }

    /**
     * @covers ::age
     */
    public function test_age(): void {
        // Simple test...
        $storage = new storage($this->vm1);
        $this->assertLessThanOrEqual(1, $storage->age('test'));
        $entity = virtual_meeting_config_entity::repository()->where('virtualmeetingid', $this->vm1->id)->where('name', 'test')->one(true);
        $entity->set_updated_timestamp();
        $this->assertLessThanOrEqual(1, $storage->age('test'));

        // Play with ages.
        $time = time();
        $storage = new storage($this->vm2);
        $this->assertLessThanOrEqual(1, $storage->age('test'));
        $record = builder::table('virtualmeeting_config')
            ->where('virtualmeetingid', $this->vm2->id)
            ->where('name', 'test');
        $record->update(['timecreated' => $time - 60]);
        $this->assertEquals(60, $storage->age('test', $time));
        $record->update(['timemodified' => $time - 30]);
        $this->assertEquals(30, $storage->age('test', $time));
        // Set created time to be in future
        $record->update(['timecreated' => $time + 60]);
        $this->assertEquals(30, $storage->age('test', $time));

        // Test not found
        $this->assertNull($storage->age('foo'));

        // Test updated in future
        $future_time = $time + 30;
        $record->update(['timemodified' => $future_time]);
        $this->assertEquals(-30, $storage->age('test', $time));

        // Test strict
        $this->assertNull($storage->age('not_there'));
        try {
            $storage->age('not_there', 0, true);
            $this->fail('Expected record_not_found_exception');
        } catch (record_not_found_exception $ex) {
        }
    }
}
