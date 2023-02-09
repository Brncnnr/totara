<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @package virtualmeeting_poc_app
 */

use totara_core\entity\virtual_meeting;
use totara_core\http\clients\simple_mock_client;
use totara_core\virtualmeeting\dto\meeting_edit_dto;
use totara_core\virtualmeeting\exception\unsupported_exception;
use totara_core\virtualmeeting\plugin\feature;
use totara_core\virtualmeeting\plugin\provider\provider;
use virtualmeeting_poc_app\poc_factory;
use virtualmeeting_poc_app\poc_service_provider;

/**
 * @group virtualmeeting
 * @coversDefaultClass virtualmeeting_poc_app\poc_factory
 */
class virtualmeeting_poc_app_poc_testcase extends advanced_testcase {

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    /**
     * @covers ::toggle
     * @covers ::is_available
     */
    public function test_toggle_availability(): void {
        $factory = new virtualmeeting_poc_m0ck4te5t_factory();
        poc_factory::toggle('poc_m0ck4te5t', true);
        $this->assertTrue($factory->is_available());
        poc_factory::toggle('poc_m0ck4te5t', false);
        $this->assertFalse($factory->is_available());
        try {
            poc_factory::toggle('oops', false);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('invalid plugin name', $ex->getMessage());
        }
    }

    /**
     * @covers ::toggle_info
     * @covers virtualmeeting_poc_app\poc_service_provider::get_info
     */
    public function test_toggle_info(): void {
        $userid = $this->getDataGenerator()->create_user(['username' => 'organiser'])->id;
        $provider = new poc_service_provider('m0ck4te5t', false);
        $entity = new virtual_meeting();
        $entity->plugin = 'm0ck4te5t';
        $entity->userid = $userid;
        $entity->save();
        $dto = new meeting_edit_dto($entity, 'test meeting', new DateTime('tomorrow 6am'), new DateTime('tomorrow 9am'));
        $provider->create_meeting($dto);
        poc_factory::toggle_info('poc_m0ck4te5t', provider::INFO_HOST_URL, true);
        $this->assertNotEmpty($provider->get_info($dto, provider::INFO_HOST_URL));
        poc_factory::toggle_info('poc_m0ck4te5t', provider::INFO_HOST_URL, false);
        try {
            $provider->get_info($dto, provider::INFO_HOST_URL);
            $this->fail('unsupported_exception expected');
        } catch (unsupported_exception $ex) {
            $this->assertStringContainsString('info unsupported by plugin: poc_m0ck4te5t', $ex->getMessage());
        }
        poc_factory::toggle_info('poc_m0ck4te5t', provider::INFO_INVITATION, true);
        $this->assertStringContainsString('invitation from organiser', $provider->get_info($dto, provider::INFO_INVITATION));
        poc_factory::toggle_info('poc_m0ck4te5t', provider::INFO_INVITATION, false);
        try {
            $provider->get_info($dto, provider::INFO_INVITATION);
            $this->fail('unsupported_exception expected');
        } catch (unsupported_exception $ex) {
            $this->assertStringContainsString('info unsupported by plugin: poc_m0ck4te5t', $ex->getMessage());
        }
        poc_factory::toggle_info('poc_m0ck4te5t', provider::INFO_PREVIEW, true);
        $this->assertStringContainsString('info from organiser', $provider->get_info($dto, provider::INFO_PREVIEW));
        poc_factory::toggle_info('poc_m0ck4te5t', provider::INFO_PREVIEW, false);
        try {
            $provider->get_info($dto, provider::INFO_PREVIEW);
            $this->fail('unsupported_exception expected');
        } catch (unsupported_exception $ex) {
            $this->assertStringContainsString('info unsupported by plugin: poc_m0ck4te5t', $ex->getMessage());
        }
        try {
            poc_factory::toggle_info('oops', 'wow', false);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('invalid plugin name', $ex->getMessage());
        }
    }

    /**
     * @covers ::toggle_feature
     * @covers ::get_feature
     */
    public function test_toggle_feature(): void {
        $factory = new virtualmeeting_poc_m0ck4te5t_factory();
        poc_factory::toggle_feature('poc_m0ck4te5t', feature::LOSSY_UPDATE, poc_factory::FEATURE_YES);
        $this->assertTrue($factory->get_feature(feature::LOSSY_UPDATE));
        poc_factory::toggle_feature('poc_m0ck4te5t', feature::LOSSY_UPDATE, poc_factory::FEATURE_NO);
        $this->assertFalse($factory->get_feature(feature::LOSSY_UPDATE));
        poc_factory::toggle_feature('poc_m0ck4te5t', feature::LOSSY_UPDATE, poc_factory::FEATURE_UNKNOWN);
        try {
            $factory->get_feature(feature::LOSSY_UPDATE);
            $this->fail('unsupported_exception expected');
        } catch (unsupported_exception $ex) {
            $this->assertStringContainsString('feature unsupported by plugin: m0ck4te5t', $ex->getMessage());
        }
        try {
            poc_factory::toggle_feature('oops', 'wow', 42);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('invalid plugin name', $ex->getMessage());
        }
    }

    /**
     * @covers ::create_service_provider
     */
    public function test_create_service_provider(): void {
        $factory = new virtualmeeting_poc_m0ck4te5t_factory();
        $provider = $factory->create_service_provider(new simple_mock_client());
        $rp = new ReflectionProperty($provider, 'name');
        $rp->setAccessible(true);
        $this->assertSame('m0ck4te5t', $rp->getValue($provider));
    }

    /**
     * @covers ::create_setting_page
     */
    public function test_create_setting_page(): void {
        $factory = new virtualmeeting_poc_m0ck4te5t_factory();
        $page = $factory->create_setting_page('x', 'y', true, false);
        $this->assertNotNull($page);
        foreach ((array)$page->settings as $setting) {
            /** @var admin_setting $setting */
            $this->assertSame('virtualmeeting_poc_m0ck4te5t', $setting->plugin);
        }
    }
}

/**
 * mock factory for testing
 */
class virtualmeeting_poc_m0ck4te5t_factory extends poc_factory {
    protected const NAME = 'm0ck4te5t';
    protected const DESC = 'mock factory for testing';
    protected const USER_AUTH = false;
}
