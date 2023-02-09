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

use core\plugininfo\virtualmeeting as plugininfo;
use totara_core\virtualmeeting\plugin\feature;
use virtualmeeting_poc_app\poc_factory;

/**
 * @group virtualmeeting
 * @coversDefaultClass core\plugininfo\virtualmeeting
 */
class totara_core_virtualmeeting_plugininfo_testcase extends advanced_testcase {
    /**
     * @covers ::is_uninstall_allowed
     */
    public function test_is_uninstall_allowed(): void {
        foreach (plugininfo::get_all_plugins() as $plugin) {
            $this->assertFalse($plugin->is_uninstall_allowed());
        }
    }

    /**
     * @covers ::get_settings_section_name
     */
    public function test_get_settings_section_name(): void {
        foreach (plugininfo::get_all_plugins() as $name => $plugin) {
            $expected = "virtualmeeting_{$name}";
            $this->assertEquals($expected, $plugin->get_settings_section_name());
        }
    }

    /**
     * @covers ::resolve_factory_class
     */
    public function test_resolve_factory_class(): void {
        $method = new ReflectionMethod(plugininfo::class, 'resolve_factory_class');
        $method->setAccessible(true);
        foreach (plugininfo::get_all_plugins() as $name => $plugin) {
            $expected = "virtualmeeting_{$name}_factory";
            $this->assertEquals($expected, $method->invoke($plugin));
        }
    }

    /**
     * @covers ::is_poc_available
     */
    public function test_is_poc_available(): void {
        $this->assertTrue(plugininfo::is_poc_available());
    }

    /**
     * @covers ::get_available_plugins
     */
    public function test_get_available_plugins(): void {
        $plugins = plugininfo::get_available_plugins();
        $this->assertCount(2, $plugins);
        $this->assertEqualsCanonicalizing(['poc_app', 'poc_user'], array_keys($plugins));
        poc_factory::toggle('poc_app', false);
        $plugins = plugininfo::get_available_plugins();
        $this->assertCount(1, $plugins);
        $this->assertEqualsCanonicalizing(['poc_user'], array_keys($plugins));
        poc_factory::toggle('poc_app', true);
        poc_factory::toggle('poc_user', false);
        $plugins = plugininfo::get_available_plugins();
        $this->assertCount(1, $plugins);
        $this->assertEqualsCanonicalizing(['poc_app'], array_keys($plugins));
    }

    /**
     * @covers ::load
     */
    public function test_load(): void {
        poc_factory::toggle('poc_app', false);
        poc_factory::toggle('poc_user', false);
        $plugin = plugininfo::load('poc_app');
        $this->assertEquals('poc_app', $plugin->name);
        $plugin = plugininfo::load('poc_user');
        $this->assertEquals('poc_user', $plugin->name);
        try {
            plugininfo::load('poc_none');
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('unknown plugin name: poc_none', $ex->getMessage());
        }
    }

    /**
     * @covers ::load_available
     */
    public function test_load_available(): void {
        $plugin = plugininfo::load_available('poc_app');
        $this->assertEquals('poc_app', $plugin->name);
        $plugin = plugininfo::load_available('poc_user');
        $this->assertEquals('poc_user', $plugin->name);
        poc_factory::toggle('poc_app', false);
        poc_factory::toggle('poc_user', false);
        try {
            plugininfo::load_available('poc_app');
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('plugin not available: poc_app', $ex->getMessage());
        }
        try {
            plugininfo::load_available('poc_user');
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('plugin not available: poc_user', $ex->getMessage());
        }
        try {
            plugininfo::load_available('poc_none');
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('unknown plugin name: poc_none', $ex->getMessage());
        }
    }

    /**
     * @covers ::create_factory
     */
    public function test_create_factory(): void {
        $plugin = plugininfo::load('poc_app');
        $factory = $plugin->create_factory();
        $this->assertInstanceOf(virtualmeeting_poc_app_factory::class, $factory);
        $plugin = plugininfo::load('poc_user');
        $factory = $plugin->create_factory();
        $this->assertInstanceOf(virtualmeeting_poc_user_factory::class, $factory);
        $plugin->name = 'he_who_must_not_exist';
        try {
            $factory = $plugin->create_factory();
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('plugin not found: he_who_must_not_exist', $ex->getMessage());
        }
    }

    /**
     * @covers ::is_available
     */
    public function test_is_available(): void {
        $plugin_app = plugininfo::load('poc_app');
        $plugin_user = plugininfo::load('poc_user');
        $this->assertTrue($plugin_app->is_available());
        $this->assertTrue($plugin_user->is_available());
        poc_factory::toggle('poc_app', false);
        $this->assertFalse($plugin_app->is_available());
        $this->assertTrue($plugin_user->is_available());
        poc_factory::toggle('poc_app', true);
        poc_factory::toggle('poc_user', false);
        $this->assertTrue($plugin_app->is_available());
        $this->assertFalse($plugin_user->is_available());
    }

    /**
     * @covers ::get_feature
     */
    public function test_get_feature(): void {
        $plugin_app = plugininfo::load('poc_app');
        $plugin_user = plugininfo::load('poc_user');
        $this->assertTrue($plugin_app->get_feature(feature::LOSSY_UPDATE));
        $this->assertTrue($plugin_user->get_feature(feature::LOSSY_UPDATE));
        poc_factory::toggle_feature('poc_app', feature::LOSSY_UPDATE, poc_factory::FEATURE_UNKNOWN);
        $this->assertFalse($plugin_app->get_feature(feature::LOSSY_UPDATE));
        $this->assertTrue($plugin_user->get_feature(feature::LOSSY_UPDATE));
        poc_factory::toggle_feature('poc_app', feature::LOSSY_UPDATE, poc_factory::FEATURE_NO);
        $this->assertFalse($plugin_app->get_feature(feature::LOSSY_UPDATE));
        $this->assertTrue($plugin_user->get_feature(feature::LOSSY_UPDATE));
        poc_factory::toggle_feature('poc_app', feature::LOSSY_UPDATE, poc_factory::FEATURE_YES);
        poc_factory::toggle_feature('poc_user', feature::LOSSY_UPDATE, poc_factory::FEATURE_NO);
        $this->assertTrue($plugin_app->get_feature(feature::LOSSY_UPDATE));
        $this->assertFalse($plugin_user->get_feature(feature::LOSSY_UPDATE));
    }

    /**
     * @covers ::get_enabled_plugins
     */
    public function test_get_enabled_plugins(): void {
        $plugins = plugininfo::get_enabled_plugins();
        $this->assertGreaterThanOrEqual(2, count($plugins));
        $this->assertEquals('poc_app', $plugins['poc_app']);
        $this->assertEquals('poc_user', $plugins['poc_user']);
    }

}
