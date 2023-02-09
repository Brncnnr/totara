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
 * @package mod_approval
 */

use core\orm\collection;
use core_phpunit\testcase;
use mod_approval\plugininfo\approvalform;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\plugininfo\approvalform
 */
class mod_approval_plugininfo_approvalform_testcase extends testcase {
    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    /**
     * @covers ::get_enabled_plugins
     */
    public function test_get_enabled_plugins(): void {
        global $CFG;
        unset($CFG->approvalform_plugins);
        $this->assertCount(0, approvalform::get_enabled_plugins());
        $CFG->approvalform_plugins = 'he_who_must_not_exist';
        $this->assertCount(0, approvalform::get_enabled_plugins());
        $CFG->approvalform_plugins = 'simple,he_who_must_not_exist,simple,simple';
        $this->assertCount(1, approvalform::get_enabled_plugins());
    }

    /**
     * @covers ::enable_plugin
     * @covers ::toggle_plugin
     */
    public function test_enable_plugin(): void {
        global $CFG;
        unset($CFG->approvalform_plugins);
        $this->assertCount(0, approvalform::get_enabled_plugins());
        $this->assertTrue(approvalform::enable_plugin('simple'));
        $this->assertCount(1, approvalform::get_enabled_plugins());
        $this->assertFalse(approvalform::enable_plugin('simple'));
        $this->assertCount(1, approvalform::get_enabled_plugins());
        unset($CFG->approvalform_plugins);
        try {
            approvalform::enable_plugin('simple,he_who_must_not_exist');
            $this->fail('Expected exception when enabling unknown plugin');
        } catch (Throwable $e) {
            $this->assertStringContainsString('Tried to enable unknown approvalform plugin', $e->getMessage());
        }
        $this->assertCount(0, approvalform::get_enabled_plugins());
    }

    /**
     * @covers ::disable_plugin
     * @covers ::toggle_plugin
     */
    public function test_disable_plugin(): void {
        global $CFG;
        unset($CFG->approvalform_plugins);
        $CFG->approvalform_plugins = 'simple';
        $this->assertCount(1, approvalform::get_enabled_plugins());
        $this->assertTrue(approvalform::disable_plugin('simple'));
        $this->assertCount(0, approvalform::get_enabled_plugins());
        $this->assertFalse(approvalform::disable_plugin('simple'));
        $this->assertCount(0, approvalform::get_enabled_plugins());;
        $CFG->approvalform_plugins = 'simple,he_who_must_not_exist';
        $this->assertFalse(approvalform::disable_plugin('he_who_must_not_exist'));
        $this->assertCount(1, approvalform::get_enabled_plugins());
    }

    /**
     * @covers ::get_all_plugins
     */
    public function test_get_all_plugins(): void {
        $method = new ReflectionMethod(approvalform::class, 'get_all_plugins');
        $method->setAccessible(true);
        $plugins = [];
        foreach (core_plugin_manager::instance()->get_installed_plugins('approvalform') as $plugin => $unused) {
            $plugins[] = $plugin;
        }
        $this->assertEquals($plugins, $method->invoke(null));
    }

    /**
     * @covers ::from_plugin_name
     */
    public function test_from_plugin_name(): void {
        $plugin = approvalform::from_plugin_name('simple');
        $this->assertInstanceOf(approvalform::class, $plugin);
    }

    public function test_init_plugin_settings(): void {
        $this->setAdminUser();
        $admin_root = admin_get_root(true, false);

        // Assert approvalform settings folder
        $approvalforms_folder = $this->get_approvalform_folder_category($admin_root);
        $this->assertGreaterThanOrEqual(1, count($approvalforms_folder->get_children()));
        $this->assertEquals('approvalformsfolder', $approvalforms_folder->name);
        $this->assertFalse($approvalforms_folder->hidden);

        // Assert approvalform plugins page.
        $this->assertEquals('approvalformplugins', $approvalforms_folder->get_children()[0]->name);
        $this->assertFalse($approvalforms_folder->get_children()[0]->hidden);
    }

    /**
     * @param admin_root $admin_root
     * @return admin_category
     */
    private function get_approvalform_folder_category(admin_root $admin_root): admin_category {
        /** @var admin_category $modules */
        $modules = collection::new($admin_root->get_children())->find('name', 'modules');
        if (is_null($modules)) {
            $this->fail('modules not found in admin root.');
        }

        /** @var admin_category $mod_approval_folder */
        $approvalforms_folder = collection::new($modules->get_children())->find('name', 'approvalformsfolder');
        if (is_null($approvalforms_folder)) {
            $this->fail('approvalformsfolder not found in modules root.');
        }

        return $approvalforms_folder;
    }
}
