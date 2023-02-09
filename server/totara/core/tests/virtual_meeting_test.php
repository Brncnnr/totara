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
use core\plugininfo\virtualmeeting as virtualmeeting_plugininfo;
use core\orm\query\exceptions\record_not_found_exception;
use totara_core\entity\virtual_meeting as virtual_meeting_entity;
use totara_core\http\clients\curl_client;
use totara_core\http\clients\simple_mock_client;
use totara_core\virtualmeeting\user_auth;
use totara_core\virtualmeeting\virtual_meeting;

/**
 * @group virtualmeeting
 * @coversDefaultClass totara_core\virtualmeeting\virtual_meeting
 */
class totara_core_virtual_meeting_testcase extends advanced_testcase {
    /** @var ReflectionProperty */
    private $entity_prop;

    /** @var ReflectionProperty */
    private $plugininfo_prop;

    /** @var ReflectionProperty */
    private $client_prop;

    public function setUp(): void {
        parent::setUp();
        $this->entity_prop = new ReflectionProperty(virtual_meeting::class, 'entity');
        $this->plugininfo_prop = new ReflectionProperty(virtual_meeting::class, 'plugininfo');
        $this->client_prop = new ReflectionProperty(virtual_meeting::class, 'client');
        $this->entity_prop->setAccessible(true);
        $this->plugininfo_prop->setAccessible(true);
        $this->client_prop->setAccessible(true);
    }

    public function tearDown(): void {
        parent::tearDown();
        $prop = new ReflectionProperty(core_plugin_manager::class, 'singletoninstance');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
        $this->entity_prop = null;
        $this->plugininfo_prop = null;
        $this->client_prop = null;
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
     * @covers ::load_by_entity
     */
    public function test_load_by_entity(): void {
        $entity = new virtual_meeting_entity();
        $entity->plugin = 'poc_app';
        $entity->userid = 2;
        $entity->save();

        $model = virtual_meeting::load_by_entity($entity);
        $this->assertSame($entity->id, $this->entity_prop->getValue($model)->id);
        $this->assertNull($this->plugininfo_prop->getValue($model));
        $this->assertInstanceOf(curl_client::class, $this->client_prop->getValue($model));

        $client = new simple_mock_client();
        $model = virtual_meeting::load_by_entity($entity, $client);
        $this->assertSame($client, $this->client_prop->getValue($model));
    }

    /**
     * @covers ::load_by_id
     */
    public function test_load_by_id(): void {
        $entity = new virtual_meeting_entity();
        $entity->plugin = 'poc_app';
        $entity->userid = 2;
        $entity->save();

        $model = virtual_meeting::load_by_id($entity->id);
        $this->assertSame($entity->id, $this->entity_prop->getValue($model)->id);
        $this->assertNull($this->plugininfo_prop->getValue($model));
        $this->assertInstanceOf(curl_client::class, $this->client_prop->getValue($model));

        $client = new simple_mock_client();
        $model = virtual_meeting::load_by_id($entity->id, $client);
        $this->assertSame($client, $this->client_prop->getValue($model));

        try {
            virtual_meeting::load_by_id($entity->id + 1);
            $this->fail('record_not_found_exception expected');
        } catch (record_not_found_exception $ex) {
        }
    }

    /**
     * @covers ::create
     */
    public function test_create(): void {
        $user = $this->create_user(['username' => 'bob']);
        $model = virtual_meeting::create('poc_app', $user, 'test meeting', new DateTime('+1 hour'), new DateTime('+2 hour'));
        $this->assertTrue($this->entity_prop->getValue($model)->exists());
        $this->assertEquals('poc_app', $this->plugininfo_prop->getValue($model)->name);
        $this->assertInstanceOf(curl_client::class, $this->client_prop->getValue($model));

        $client = new simple_mock_client();
        $model = virtual_meeting::create('poc_app', $user, 'test meeting', new DateTime('+1 hour'), new DateTime('+2 hour'), $client);
        $this->assertEquals($user->id, $this->entity_prop->getValue($model)->userid);
        $this->assertSame($client, $this->client_prop->getValue($model));
        $model = virtual_meeting::create('poc_app', $user->to_record(), 'test meeting', new DateTime('+1 hour'), new DateTime('+2 hour'), $client);
        $this->assertEquals($user->id, $this->entity_prop->getValue($model)->userid);
        $model = virtual_meeting::create('poc_app', $user->id, 'test meeting', new DateTime('+1 hour'), new DateTime('+2 hour'), $client);
        $this->assertEquals($user->id, $this->entity_prop->getValue($model)->userid);

        $plugin = new virtualmeeting_plugininfo();
        $plugin->name = 'cop';
        try {
            $model = virtual_meeting::create($plugin, $user, 'test meeting', new DateTime('+1 hour'), new DateTime('+2 hour'));
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
        }
    }

    /**
     * @covers ::update
     */
    public function test_update(): void {
        $user = $this->create_user(['username' => 'bob']);
        $model = virtual_meeting::create('poc_app', $user, 'test meeting', new DateTime('+1 hour'), new DateTime('+2 hour'));
        $model->update('test update meeting', new DateTime('+3 hour'), new DateTime('+4 hour'));
        $model->delete();
        try {
            $model->update('test further update meeting', new DateTime('+5 hour'), new DateTime('+6 hour'));
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
        }
    }

    /**
     * @covers ::delete
     */
    public function test_delete(): void {
        $user = $this->create_user(['username' => 'bob']);
        $model = virtual_meeting::create('poc_app', $user, 'test meeting', new DateTime('+1 hour'), new DateTime('+2 hour'));
        $model->delete();
        try {
            $model->delete();
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
        }
    }

    /**
     * @covers ::get_join_url
     * @covers ::provider_getter_wrapper
     */
    public function test_get_join_url(): void {
        global $CFG;
        $user = $this->create_user(['username' => 'bob']);
        $model = virtual_meeting::create('poc_app', $user, 'test meeting', new DateTime('+1 hour'), new DateTime('+2 hour'));
        $url = $model->get_join_url();
        $this->assertStringStartsWith($CFG->wwwroot.'/integrations/virtualmeeting/poc_app/meet.php', $url);
        $model->update('test update meeting', new DateTime('+3 hour'), new DateTime('+4 hour'));
        $url = $model->get_join_url();
        $this->assertStringStartsWith($CFG->wwwroot.'/integrations/virtualmeeting/poc_app/meet.php', $url);
        $model->delete();
        $this->assertSame('', $model->get_join_url(false));
        try {
            $model->get_join_url();
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
        }
    }

    /**
     * @covers ::get_host_url
     * @covers ::provider_getter_wrapper
     */
    public function test_get_host_url(): void {
        global $CFG;
        $user = $this->create_user(['username' => 'bob']);
        $model = virtual_meeting::create('poc_app', $user, 'test meeting', new DateTime('+1 hour'), new DateTime('+2 hour'));
        $url = $model->get_host_url();
        $this->assertStringStartsWith($CFG->wwwroot.'/integrations/virtualmeeting/poc_app/meet.php', $url);
        $model->update('test update meeting', new DateTime('+3 hour'), new DateTime('+4 hour'));
        $url = $model->get_host_url();
        $this->assertStringStartsWith($CFG->wwwroot.'/integrations/virtualmeeting/poc_app/meet.php', $url);
        $model->delete();
        $this->assertSame('', $model->get_host_url(false));
        try {
            $model->get_host_url();
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
        }
    }

    /**
     * @covers ::get_invitation
     * @covers ::provider_getter_wrapper
     */
    public function test_get_invitation(): void {
        $user = $this->create_user(['username' => 'bob']);
        $model = virtual_meeting::create('poc_app', $user, 'test meeting', new DateTime('+1 hour'), new DateTime('+2 hour'));
        $html = $model->get_invitation();
        $this->assertEquals('<p>invitation from bob</p>', $html);
        $model->update('test update meeting', new DateTime('+3 hour'), new DateTime('+4 hour'));
        $html = $model->get_invitation();
        $this->assertEquals('<p>invitation from bob</p>', $html);
        $model->delete();
        $this->assertSame('', $model->get_invitation(false));
        try {
            $model->get_invitation();
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
        }
    }

    /**
     * @covers ::get_preview
     * @covers ::provider_getter_wrapper
     */
    public function test_get_preview(): void {
        $user = $this->create_user(['username' => 'bob']);
        $model = virtual_meeting::create('poc_app', $user, 'test meeting', new DateTime('+1 hour'), new DateTime('+2 hour'));
        $html = $model->get_preview();
        $this->assertEquals('<p>info from bob</p>', $html);
        $model->update('test update meeting', new DateTime('+3 hour'), new DateTime('+4 hour'));
        $html = $model->get_preview();
        $this->assertEquals('<p>info from bob</p>', $html);
        $model->delete();
        $this->assertSame('', $model->get_preview(false));
        try {
            $model->get_preview();
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
        }
    }

    /**
     * @covers ::can_manage
     * @covers ::is_user_auth_required
     */
    public function test_can_manage(): void {
        $user1 = $this->create_user(['username' => 'bob']);
        $user2 = $this->create_user(['username' => 'ann']);
        $model = virtual_meeting::create('poc_app', $user1, 'test meeting', new DateTime('+1 hour'), new DateTime('+2 hour'));
        $this->assertTrue($model->can_manage($user1->id));
        $this->assertTrue($model->can_manage($user2->id));
        $model->delete();
        $this->assertFalse($model->can_manage($user1->id));
        $this->assertFalse($model->can_manage($user2->id));
        user_auth::create('poc_user', $user1, 'bob', '', time() + HOURSECS);
        $model = virtual_meeting::create('poc_user', $user1, 'test meeting', new DateTime('+1 hour'), new DateTime('+2 hour'));
        $this->assertTrue($model->can_manage($user1->id));
        $this->assertFalse($model->can_manage($user2->id));
        $model->delete();
        $this->assertFalse($model->can_manage($user1->id));
        $this->assertFalse($model->can_manage($user2->id));
    }

    /**
     * @covers ::get_availale_plugins_info
     */
    public function test_get_availale_plugins_info(): void {
        global $CFG;
        $actual = virtual_meeting::get_availale_plugins_info();
        $expected = [
            'poc_app' => [
                'name' => 'Fake Dev App',
            ],
            'poc_user' => [
                'name' => 'Fake Dev User',
                'auth_endpoint' => $CFG->wwwroot.'/integrations/virtualmeeting/poc_user/index.php?redirect_uri='.rawurlencode($CFG->wwwroot.'/integrations/virtualmeeting/auth_callback.php/poc_user'),
            ]
        ];
        $this->assertEqualsCanonicalizing($expected, $actual);
    }
}
