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

use totara_core\entity\virtual_meeting as virtual_meeting_entity;
use totara_core\entity\virtual_meeting_auth as virtual_meeting_auth_entity;
use totara_core\virtualmeeting\dto\meeting_dto;
use totara_core\virtualmeeting\dto\meeting_edit_dto;
use totara_core\virtualmeeting\exception\auth_exception;
use totara_core\virtualmeeting\storage;
use virtualmeeting_poc_app\poc_factory;

/**
 * @group virtualmeeting
 */
class totara_core_virtual_meeting_meeting_dto_testcase extends advanced_testcase {
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
    }

    public function tearDown(): void {
        parent::tearDown();
        $this->user = null;
        $this->vm1 = null;
        $this->vm2 = null;
    }

    /**
     * @covers totara_core\virtualmeeting\dto\meeting_dto::__construct
     */
    public function test_constructor(): void {
        new meeting_dto($this->vm1);
        new meeting_dto($this->vm2);
        $entity = new virtual_meeting_entity();
        try {
            new meeting_dto($entity);
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('entity does not exist', $ex->getMessage());
        }
        $vm1 = clone $this->vm1;
        $vm1->userid = 0;
        try {
            new meeting_dto($vm1);
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('userid cannot be empty', $ex->getMessage());
        }
        // Uh-oh, no checks for plugin's existence or availability
        poc_factory::toggle('poc_app', false);
        new meeting_dto($this->vm1);
        $this->vm2->plugin = 'poc_duh';
        $this->vm2->save();
        new meeting_dto($this->vm2);
    }

    /**
     * @covers totara_core\virtualmeeting\dto\meeting_dto::get_id
     */
    public function test_get_id(): void {
        $dto1 = new meeting_dto($this->vm1);
        $dto2 = new meeting_dto($this->vm2);
        $this->assertEquals($this->vm1->id, $dto1->get_id());
        $this->assertEquals($this->vm2->id, $dto2->get_id());
    }

    /**
     * @covers totara_core\virtualmeeting\dto\meeting_dto::get_userid
     */
    public function test_get_userid(): void {
        $user2 = $this->getDataGenerator()->create_user();
        $this->vm2->userid = $user2->id;
        $this->vm2->save();
        $dto1 = new meeting_dto($this->vm1);
        $dto2 = new meeting_dto($this->vm2);
        $this->assertEquals($this->user->id, $dto1->get_userid());
        $this->assertEquals($user2->id, $dto2->get_userid());
    }

    /**
     * @covers totara_core\virtualmeeting\dto\meeting_dto::get_user
     */
    public function test_get_user(): void {
        $dto = new meeting_dto($this->vm1);
        try {
            $dto->get_user();
            $this->fail('auth_exception expected');
        } catch (auth_exception $ex) {
            $this->assertStringContainsString('user is not authorised', $ex->getMessage());
        }
        $this->vm1->plugin = 'poc_user';
        $this->vm1->save();
        $dto = new meeting_dto($this->vm1);
        try {
            $dto->get_user();
            $this->fail('auth_exception expected');
        } catch (auth_exception $ex) {
            $this->assertStringContainsString('user is not authorised', $ex->getMessage());
        }
        $auth = new virtual_meeting_auth_entity();
        $auth->plugin = 'poc_user';
        $auth->userid = $this->user->id;
        $auth->access_token = 'k1AK4ha';
        $auth->refresh_token = 'aO7eAR04';
        $auth->timeexpiry = time() + HOURSECS;
        $auth->save();
        $dto = new meeting_dto($this->vm1);
        $userauth = $dto->get_user();
        $this->assertEquals($this->user->id, $userauth->get_userid());
        $this->assertEquals('k1AK4ha', $userauth->get_token());
    }

    /**
     * @covers totara_core\virtualmeeting\dto\meeting_dto::get_storage
     */
    public function test_get_storage(): void {
        $dto = new meeting_dto($this->vm1);
        $storage = $dto->get_storage();
        $prop = new ReflectionProperty(storage::class, 'virtualmeetingid');
        $prop->setAccessible(true);
        $this->assertEquals($this->vm1->id, $prop->getValue($storage));
    }

    /**
     * @covers totara_core\virtualmeeting\dto\meeting_edit_dto::__construct
     */
    public function test_edit_constructor(): void {
        new meeting_edit_dto($this->vm1, 'test meeting', new DateTime('+1 hour'), new DateTime('+2 hour'));
        try {
            new meeting_edit_dto($this->vm1, 'test meeting', new DateTime('+2 hour'), new DateTime('+1 hour'));
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('timefinish cannot precede timestart', $ex->getMessage());
        }
    }

    /**
     * @covers totara_core\virtualmeeting\dto\meeting_edit_dto::get_name
     */
    public function test_edit_get_name(): void {
        $dto = new meeting_edit_dto($this->vm1, 'test meeting', new DateTime('+1 hour'), new DateTime('+2 hour'));
        $this->assertEquals('test meeting', $dto->get_name());
    }

    /**
     * @covers totara_core\virtualmeeting\dto\meeting_edit_dto::get_timestart
     */
    public function test_edit_get_timestart(): void {
        $start = new DateTime('+1 hour');
        $end = new DateTime('+2 hour');
        $dto = new meeting_edit_dto($this->vm1, 'test meeting', $start, $end);
        $this->assertEquals($start->getTimestamp(), $dto->get_timestart()->getTimestamp());
    }

    /**
     * @covers totara_core\virtualmeeting\dto\meeting_edit_dto::get_timefinish
     */
    public function test_edit_get_timefinish(): void {
        $start = new DateTime('+1 hour');
        $end = new DateTime('+2 hour');
        $dto = new meeting_edit_dto($this->vm1, 'test meeting', $start, $end);
        $this->assertEquals($end->getTimestamp(), $dto->get_timefinish()->getTimestamp());
    }
}
