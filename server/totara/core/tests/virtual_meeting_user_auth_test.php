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

use core\entity\user as user_entity;
use core\orm\query\exceptions\record_not_found_exception;
use totara_core\entity\virtual_meeting_auth as virtual_meeting_auth_entity;
use totara_core\virtualmeeting\authoriser\mock_authoriser;
use totara_core\virtualmeeting\exception\auth_exception;
use totara_core\virtualmeeting\user_auth;
use virtualmeeting_poc_app\poc_factory;

/**
 * @group virtualmeeting
 * @coversDefaultClass totara_core\virtualmeeting\user_auth
 */
class totara_core_virtual_meeting_user_auth_testcase extends advanced_testcase {
    /** @var ReflectionProperty */
    private $entity_prop;

    /** @var user_entity */
    private $user;

    public function setUp(): void {
        parent::setUp();
        $this->entity_prop = new ReflectionProperty(user_auth::class, 'entity');
        $this->entity_prop->setAccessible(true);
        $this->user = new user_entity($this->getDataGenerator()->create_user()->id);
    }

    public function tearDown(): void {
        parent::tearDown();
        $this->entity_prop = null;
        $this->user = null;
    }

    /**
     * @covers ::load
     */
    public function test_load(): void {
        // Uh-oh, user_auth doesn't check the availability of plugin nor does it check an auth provider.
        poc_factory::toggle('poc_user', false);
        $this->assertNull(user_auth::load('poc_user', $this->user, false));
        try {
            user_auth::load('poc_user', $this->user, true);
            $this->fail('record_not_found_exception expected');
        } catch (record_not_found_exception $ex){
        }
        $entity = new virtual_meeting_auth_entity();
        $entity->plugin = 'poc_user';
        $entity->userid = $this->user->id;
        $entity->access_token = 'k1AK4ha';
        $entity->refresh_token = 'aO7eAR04';
        $entity->timeexpiry = time() + HOURSECS;
        $entity->save();
        $auth = user_auth::load('poc_user', $this->user, true);
        $this->assertEquals($entity->id, $this->entity_prop->getValue($auth)->id);
        $entity = new virtual_meeting_auth_entity();
        $entity->plugin = 'poc_app';
        $entity->userid = $this->user->id;
        $entity->access_token = 'B3K1nD';
        $entity->refresh_token = 'nE4ZeA1aNd';
        $entity->timeexpiry = time() + HOURSECS;
        $entity->save();
        $auth = user_auth::load('poc_app', $this->user, true);
        $this->assertEquals($entity->id, $this->entity_prop->getValue($auth)->id);
    }

    /**
     * @covers ::create
     */
    public function test_create(): void {
        // Uh-oh, user_auth doesn't check the availability of plugin nor does it check an auth provider.
        $auth = user_auth::create('poc_does_not_exist', $this->user, 'k1AK4ha', 'aO7eAR04', 314159265);
        /** @var virtual_meeting_auth_entity */
        $entity = $this->entity_prop->getValue($auth);
        $this->assertEquals($this->user->id, $entity->userid);
        $this->assertEquals('k1AK4ha', $entity->access_token);
        $this->assertEquals('aO7eAR04', $entity->refresh_token);
        $this->assertEquals(314159265, $entity->timeexpiry);
    }

    /**
     * @covers ::create_or_replace
     */
    public function test_create_or_replace(): void {
        // Uh-oh, user_auth doesn't check the availability of plugin nor does it check an auth provider.
        $auth = user_auth::create_or_replace(
            'poc_does_not_exist',
            $this->user,
            function (virtual_meeting_auth_entity $entity) {
                $entity->userid = $this->user->id;
                $entity->access_token = 'k1AK4ha';
                $entity->refresh_token = 'aO7eAR04';
                $entity->timeexpiry = 314159265;
            });
        /** @var virtual_meeting_auth_entity */
        $entity = $this->entity_prop->getValue($auth);
        $this->assertEquals($this->user->id, $entity->userid);
        $this->assertEquals('k1AK4ha', $entity->access_token);
        $this->assertEquals('aO7eAR04', $entity->refresh_token);
        $this->assertEquals(314159265, $entity->timeexpiry);
        $auth = user_auth::create_or_replace(
            'poc_does_not_exist',
            $this->user,
            function (virtual_meeting_auth_entity $entity) {
                $entity->userid = $this->user->id;
                $entity->access_token = 'B3K1nD';
                $entity->refresh_token = 'nE4ZeA1aNd';
                $entity->timeexpiry = 316227766;
            });
        /** @var virtual_meeting_auth_entity */
        $entity = $this->entity_prop->getValue($auth);
        $this->assertEquals($this->user->id, $entity->userid);
        $this->assertEquals('B3K1nD', $entity->access_token);
        $this->assertEquals('nE4ZeA1aNd', $entity->refresh_token);
        $this->assertEquals(316227766, $entity->timeexpiry);
    }

    /**
     * @covers ::get_userid
     */
    public function test_get_userid(): void {
        $auth = user_auth::create('poc_user', $this->user, 'k1AK4ha', 'aO7eAR04', 314159265);
        $this->assertEquals($this->user->id, $auth->get_userid());
    }

    /**
     * @covers ::get_token
     */
    public function test_get_token(): void {
        $auth = user_auth::create('poc_user', $this->user, 'k1AK4ha', 'aO7eAR04', time() + DAYSECS);
        $this->assertEquals('k1AK4ha', $auth->get_token());
        /** @var virtual_meeting_auth_entity */
        $entity = $this->entity_prop->getValue($auth);
        $entity->timeexpiry = time() - DAYSECS;
        $entity->save();
        try {
            $auth->get_token();
            $this->fail('auth_exception expected');
        } catch (auth_exception $ex) {
            $this->assertStringContainsString('expired token', $ex->getMessage());
        }
        $entity->delete();
        try {
            $auth->get_token();
            $this->fail('auth_exception expected');
        } catch (auth_exception $ex) {
            $this->assertStringContainsString('invalid token', $ex->getMessage());
        }
    }

    /**
     * @covers ::get_fresh_token
     */
    public function test_get_fresh_token(): void {
        $auth = user_auth::create('poc_user', $this->user, 'k1AK4ha', 'aO7eAR04', time() - DAYSECS);
        $time = time() + DAYSECS;
        $authoriser = new mock_authoriser($this->user->id, 'aO7eAR04', 'B3K1nD', 'nE4ZeA1aNd', $time);
        $this->assertEquals('B3K1nD', $auth->get_fresh_token($authoriser, false));
        /** @var virtual_meeting_auth_entity */
        $entity = $this->entity_prop->getValue($auth);
        $this->assertEquals($this->user->id, $entity->userid);
        $this->assertEquals('B3K1nD', $entity->access_token);
        $this->assertEquals('nE4ZeA1aNd', $entity->refresh_token);
        $this->assertEquals($time, $entity->timeexpiry);
        $authoriser = new mock_authoriser($this->user->id, 'nE4ZeA1aNd', 't0TAr4', 'LxP', time() - DAYSECS);
        try {
            $auth->get_fresh_token($authoriser, true);
            $this->fail('auth_exception expected');
        } catch (auth_exception $ex) {
            $this->assertStringContainsString('invalid token', $ex->getMessage());
        }
        $entity->delete();
        try {
            $auth->get_fresh_token($authoriser);
            $this->fail('auth_exception expected');
        } catch (auth_exception $ex) {
            $this->assertStringContainsString('invalid token', $ex->getMessage());
        }
    }
}
