<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_competency
 */

use totara_competency\plugin_types;

/**
 * @group totara_competency
 */
class totara_competency_plugin_types_testcase extends \advanced_testcase {

    /**
     * Data provider for test_get_all_installed_plugins.
     */
    public function data_provider_test_get_all_data() {
        return [
            [
                'plugintype' => 'criteria',
                'configsetting' => 'totara_criteria',
            ],
            [
                'plugintype' => 'pathway',
                'configsetting' => 'totara_competency',
            ],
            [
                'plugintype' => 'aggregation',
                'configsetting' => 'totara_competency',
            ],
        ];
    }

    /**
     * Test get_all_plugins
     *
     * @dataProvider data_provider_test_get_all_data
     *
     * @param $plugin_type
     * @param $config_setting
     */
    public function test_get_installed_plugins($plugin_type, $config_setting) {
        $disabled_setting = $plugin_type . '_types_disabled';

        $plugininfos = \core_plugin_manager::instance()->get_plugins_of_type($plugin_type);
        $plugins = array_keys($plugininfos);

        // Initially there are no config for enabled plugins
        $configvalue = get_config($config_setting, $disabled_setting);
        $this->assertFalse($configvalue);

        // Initially all plugins are set to be enabled except learning_plan
        $installed = plugin_types::get_installed_plugins($plugin_type, $config_setting);
        foreach ($plugins as $plugin) {
            // All plugins should be returned as a type
            // All plugins/types should be 'enabled'
            $this->assertTrue(array_key_exists($plugin, $installed));
            $this->assertTrue($installed[$plugin]->enabled);
        }
    }

    /**
     * Test enabling and disabling of plugins
     *
     * @dataProvider data_provider_test_get_all_data
     */
    public function test_enable_and_disable($plugin_type, $config_setting) {
        $plugininfos = \core_plugin_manager::instance()->get_plugins_of_type($plugin_type);
        $all_plugins = array_keys($plugininfos);

        $this->assertEqualsCanonicalizing($all_plugins, plugin_types::get_enabled_plugins($plugin_type, $config_setting));
        $this->assertEmpty(plugin_types::get_disabled_plugins($plugin_type, $config_setting));

        $to_disable = reset($all_plugins);
        $updatedtypes = plugin_types::disable_plugin($to_disable, $plugin_type, $config_setting);
        foreach ($all_plugins as $plugin) {
            $this->assertEquals($plugin != $to_disable, in_array($plugin, $updatedtypes));
            $this->assertEquals($plugin != $to_disable, plugin_types::is_plugin_enabled($plugin, $plugin_type, $config_setting));
            $this->assertEquals($plugin == $to_disable, plugin_types::is_plugin_disabled($plugin, $plugin_type, $config_setting));
        }

        $expected_enabled = array_diff($all_plugins, [$to_disable]);
        $this->assertEqualsCanonicalizing($expected_enabled, plugin_types::get_enabled_plugins($plugin_type, $config_setting));
        $this->assertEquals([$to_disable], plugin_types::get_disabled_plugins($plugin_type, $config_setting));


        // Enable one not disabled
        $to_enable = reset($expected_enabled);

        $updatedtypes = plugin_types::enable_plugin($to_enable, $plugin_type, $config_setting);
        foreach ($all_plugins as $plugin) {
            $this->assertEquals($plugin != $to_disable, in_array($plugin, $updatedtypes));
            $this->assertEquals($plugin != $to_disable, plugin_types::is_plugin_enabled($plugin, $plugin_type, $config_setting));
            $this->assertEquals($plugin == $to_disable, plugin_types::is_plugin_disabled($plugin, $plugin_type, $config_setting));
        }

        $expected_enabled = array_diff($all_plugins, [$to_disable]);
        $this->assertEqualsCanonicalizing($expected_enabled, plugin_types::get_enabled_plugins($plugin_type, $config_setting));
        $this->assertEquals([$to_disable], plugin_types::get_disabled_plugins($plugin_type, $config_setting));


        // Now enable one previously disabled
        $updatedtypes = plugin_types::enable_plugin($to_disable, $plugin_type, $config_setting);
        foreach ($all_plugins as $plugin) {
            $this->assertTrue(in_array($plugin, $updatedtypes));
            $this->assertTrue(plugin_types::is_plugin_enabled($plugin, $plugin_type, $config_setting));
            $this->assertFalse(plugin_types::is_plugin_disabled($plugin, $plugin_type, $config_setting));
        }
    }
}
