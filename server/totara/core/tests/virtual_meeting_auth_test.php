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
use core\plugininfo\virtualmeeting as virtualmeeting_plugininfo;
use totara_core\entity\virtual_meeting_auth as virtual_meeting_auth_entity;
use totara_core\http\clients\simple_mock_client;
use totara_core\virtualmeeting\exception\auth_exception;
use totara_core\virtualmeeting\exception\unsupported_exception;
use totara_core\virtualmeeting\virtual_meeting_auth;
use virtualmeeting_poc_app\poc_auth_provider;
use virtualmeeting_poc_app\poc_factory;

/**
 * @group virtualmeeting
 * @coversDefaultClass totara_core\virtualmeeting\virtual_meeting_auth
 */
class totara_core_virtual_meeting_auth_testcase extends advanced_testcase {
    /** @var ReflectionProperty */
    private $plugininfo_prop;

    /** @var ReflectionProperty */
    private $auth_prop;

    public function setUp(): void {
        parent::setUp();
        $this->plugininfo_prop = new ReflectionProperty(virtual_meeting_auth::class, 'plugininfo');
        $this->auth_prop = new ReflectionProperty(virtual_meeting_auth::class, 'auth');
        $this->plugininfo_prop->setAccessible(true);
        $this->auth_prop->setAccessible(true);
    }

    public function tearDown(): void {
        parent::tearDown();
        $prop = new ReflectionProperty(core_plugin_manager::class, 'singletoninstance');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
        $this->plugininfo_prop = null;
        $this->auth_prop = null;
    }

    /**
     * @param stdClass|array $record
     * @param array|null $options
     * @return user_entity
     */
    private function create_user($record = null, array $options = null): user_entity {
        return user_entity::repository()->find_or_fail($this->getDataGenerator()->create_user($record, $options)->id);
    }

    /**
     * @param string $plugin
     * @param integer|null $userid
     * @param integer|null $expiry
     * @param string|null $token
     * @return virtual_meeting_auth_entity
     */
    private function create_entity(string $plugin, int $userid = null, int $expiry = null, string $token = null): virtual_meeting_auth_entity {
        $entity = new virtual_meeting_auth_entity();
        $entity->plugin = $plugin;
        $entity->userid = $userid ?? 2;
        $entity->access_token = $token ?? 'kia';
        $entity->refresh_token = 'ora';
        $entity->timeexpiry = $expiry ?? (time() + 1000);
        $entity->save();
        return $entity;
    }

    /**
     * @covers ::load_by_entity
     */
    public function test_load_by_entity(): void {
        $client = new simple_mock_client();
        $entity = $this->create_entity('poc_user');
        $model = virtual_meeting_auth::load_by_entity($entity, $client);
        $this->assertEquals($entity->id, $model->id);
        $this->assertEquals('poc_user', $model->plugin);
        $this->assertEquals(2, $model->userid);
        $this->assertEquals('poc_user', $this->plugininfo_prop->getValue($model)->name);
        $this->assertInstanceOf(poc_auth_provider::class, $this->auth_prop->getValue($model));
        poc_factory::toggle('poc_user', false);
        try {
            virtual_meeting_auth::load_by_entity($entity, $client);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('plugin not available: poc_user', $ex->getMessage());
        }
        $entity = $this->create_entity('poc_app');
        try {
            virtual_meeting_auth::load_by_entity($entity);
            $this->fail('unsupported_exception expected');
        } catch (unsupported_exception $ex) {
        }
    }

    /**
     * @covers ::load_by_id
     */
    public function test_load_by_id(): void {
        $client = new simple_mock_client();
        $entity = $this->create_entity('poc_user');
        $model = virtual_meeting_auth::load_by_id($entity->id, $client);
        $this->assertEquals($entity->id, $model->id);
        $this->assertEquals('poc_user', $model->plugin);
        $this->assertEquals(2, $model->userid);
        $this->assertEquals('poc_user', $this->plugininfo_prop->getValue($model)->name);
        $this->assertInstanceOf(poc_auth_provider::class, $this->auth_prop->getValue($model));
        poc_factory::toggle('poc_user', false);
        try {
            virtual_meeting_auth::load_by_id($entity->id, $client);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('plugin not available: poc_user', $ex->getMessage());
        }
        $entity = $this->create_entity('poc_app');
        try {
            virtual_meeting_auth::load_by_id($entity->id);
            $this->fail('unsupported_exception expected');
        } catch (unsupported_exception $ex) {
        }
    }

    /**
     * @covers ::load_by_plugin_user
     */
    public function test_load_by_plugin_user(): void {
        $user1 = $this->create_user(['username' => 'bob']);
        $user2 = $this->create_user(['username' => 'ann']);
        $entity = $this->create_entity('poc_user', $user1->id);
        $this->create_entity('poc_app', $user1->id);
        $model = virtual_meeting_auth::load_by_plugin_user('poc_user', $user1, true);
        $this->assertEquals($entity->id, $model->id);
        $this->assertEquals('poc_user', $model->plugin);
        $this->assertEquals($user1->id, $model->userid);
        $this->assertEquals('poc_user', $this->plugininfo_prop->getValue($model)->name);
        $this->assertInstanceOf(poc_auth_provider::class, $this->auth_prop->getValue($model));
        $plugin = virtualmeeting_plugininfo::load('poc_user');
        $model = virtual_meeting_auth::load_by_plugin_user($plugin, $user1, true);
        $this->assertEquals($entity->id, $model->id);
        $this->assertEquals('poc_user', $model->plugin);
        $this->assertEquals($user1->id, $model->userid);
        $this->assertEquals('poc_user', $this->plugininfo_prop->getValue($model)->name);
        $this->assertInstanceOf(poc_auth_provider::class, $this->auth_prop->getValue($model));
        $this->assertNull(virtual_meeting_auth::load_by_plugin_user('poc_duh', $user1, false));
        try {
            virtual_meeting_auth::load_by_plugin_user('poc_duh', $user1, true);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('unknown plugin name: poc_duh', $ex->getMessage());
        }
        $this->assertNull(virtual_meeting_auth::load_by_plugin_user('poc_user', $user2, false));
        try {
            virtual_meeting_auth::load_by_plugin_user('poc_user', $user2, true);
            $this->fail('record_not_found_exception expected');
        } catch (record_not_found_exception $ex) {
        }
        $this->assertNull(virtual_meeting_auth::load_by_plugin_user('poc_app', $user1, false));
        try {
            virtual_meeting_auth::load_by_plugin_user('poc_app', $user1, true);
            $this->fail('unsupported_exception expected');
        } catch (unsupported_exception $ex) {
        }
        poc_factory::toggle('poc_user', false);
        $this->assertNull(virtual_meeting_auth::load_by_plugin_user('poc_user', $user1, false));
        try {
            virtual_meeting_auth::load_by_plugin_user('poc_user', $user1, true);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('plugin not available: poc_user', $ex->getMessage());
        }
        $this->assertNull(virtual_meeting_auth::load_by_plugin_user($plugin, $user1, false));
        try {
            virtual_meeting_auth::load_by_plugin_user($plugin, $user1, true);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('plugin not available: poc_user', $ex->getMessage());
        }
    }

    /**
     * @covers ::delete
     * @covers ::logout
     */
    public function test_delete(): void {
        $client = new simple_mock_client();
        $entity = $this->create_entity('poc_user');
        $model = virtual_meeting_auth::load_by_entity($entity, $client);
        $model->delete();
        try {
            $model->logout();
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
        }
    }

    /**
     * @covers ::get_authentication_endpoint
     */
    public function test_get_authentication_endpoint(): void {
        global $CFG;
        $url = virtual_meeting_auth::get_authentication_endpoint('poc_user');
        $this->assertEquals($CFG->wwwroot.'/integrations/virtualmeeting/poc_user/index.php?redirect_uri='.rawurlencode($CFG->wwwroot.'/integrations/virtualmeeting/auth_callback.php/poc_user'), $url);
        try {
            virtual_meeting_auth::get_authentication_endpoint('poc_app');
            $this->fail('unsupported_exception expected');
        } catch (unsupported_exception $ex) {
        }
    }

    /**
     * @covers ::is_expired
     */
    public function test_is_expired(): void {
        $time = time();
        $client = new simple_mock_client();
        $user1 = $this->create_user();
        $user2 = $this->create_user();
        $user3 = $this->create_user();
        $model1 = virtual_meeting_auth::load_by_entity($this->create_entity('poc_user', $user1->id, $time - 1), $client);
        $model2 = virtual_meeting_auth::load_by_entity($this->create_entity('poc_user', $user2->id, $time / 1), $client);
        $model3 = virtual_meeting_auth::load_by_entity($this->create_entity('poc_user', $user3->id, $time + 1), $client);
        $time -= virtual_meeting_auth_entity::CLOCK_SKEW;
        $this->assertTrue($model1->is_expired($time), sprintf('%d < %d', $time, $model1->timeexpiry));
        $this->assertTrue($model2->is_expired($time), sprintf('%d < %d', $time, $model2->timeexpiry));
        $this->assertFalse($model3->is_expired($time), sprintf('%d < %d', $time, $model3->timeexpiry));
    }

    /**
     * @covers ::get_user_profile
     * @covers ::create_auth_provider
     * @covers ::create_auth_provider_of_plugin
     */
    public function test_get_user_profile(): void {
        $client = new simple_mock_client();
        $user1 = $this->create_user([
            'username' => 'bob1',
            'email' => 'bob1@example.com',
            'firstname' => 'Bob',
            'lastname' => 'Bobby',
            'firstnamephonetic' => '',
            'lastnamephonetic' => '',
            'middlename' => 'Bobbin',
            'alternatename' => 'Bo',
        ]);
        $user2 = $this->create_user([
            'username' => 'bob2',
            'email' => 'bob2@example.com',
            'firstname' => 'Bob',
            'lastname' => 'Bobby',
            'firstnamephonetic' => '',
            'lastnamephonetic' => '',
            'middlename' => 'Bobbin',
            'alternatename' => '',
        ]);
        $user3 = $this->create_user(['username' => 'fail']);
        $model1 = virtual_meeting_auth::load_by_entity($this->create_entity('poc_user', $user1->id, null, 'bob1'), $client);
        $model2 = virtual_meeting_auth::load_by_entity($this->create_entity('poc_user', $user2->id, null, 'bob2'), $client);
        $model3 = virtual_meeting_auth::load_by_entity($this->create_entity('poc_user', $user3->id, null, 'fail'), $client);
        $profile = $model1->get_user_profile();
        $expected = [
            'name' => 'bob1',
            'email' => 'bob1@example.com',
            'friendly_name' => 'Bo'
        ];
        $this->assertEquals($expected, $profile);
        $profile = $model2->get_user_profile();
        $expected = [
            'name' => 'bob2',
            'email' => 'bob2@example.com',
            'friendly_name' => 'Bob Bobby'
        ];
        $this->assertEquals($expected, $profile);
        $this->assertEquals([], $model3->get_user_profile(false, false));
        try {
            $this->assertEquals([], $model3->get_user_profile(false, true));
            $this->fail('auth_exception expected');
        } catch (auth_exception $ex) {
        }
    }
}
