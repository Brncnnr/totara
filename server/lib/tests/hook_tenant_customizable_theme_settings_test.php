<?php
/*
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package core
 */

use core\hook\tenant_customizable_theme_settings as tenant_customizable_theme_settings_hook;

defined('MOODLE_INTERNAL') || die();

class core_hook_tenant_customizable_theme_settings_testcase extends advanced_testcase {
    public function test_hook() {

        $this->setAdminUser();
        $test_settings = [
            'all' => '*',
            'some' => ['one', 'two'],
        ];

        $theme_config = theme_config::load('ventura');

        $hook = new tenant_customizable_theme_settings_hook($test_settings, $theme_config);
        $this->assertEqualsCanonicalizing($test_settings, $hook->get_customizable_settings());
        $this->assertTrue($hook->is_tenant_customizable_category('all'));
        $this->assertTrue($hook->is_tenant_customizable_category('some'));
        $this->assertFalse($hook->is_tenant_customizable_category('none'));
        $this->assertTrue($hook->is_tenant_customizable_category_setting('all', 'something'));
        $this->assertTrue($hook->is_tenant_customizable_category_setting('all', 'else'));
        $this->assertTrue($hook->is_tenant_customizable_category_setting('some', 'one'));
        $this->assertTrue($hook->is_tenant_customizable_category_setting('some', 'two'));
        $this->assertFalse($hook->is_tenant_customizable_category_setting('some', 'three'));

        $new_settings = [
            'new' => ['A', 'B'],
        ];
        $hook->set_customizable_settings($new_settings);

        $this->assertFalse($hook->is_tenant_customizable_category('all'));
        $this->assertFalse($hook->is_tenant_customizable_category('some'));
        $this->assertFalse($hook->is_tenant_customizable_category('none'));
        $this->assertTrue($hook->is_tenant_customizable_category('new'));
        $this->assertFalse($hook->is_tenant_customizable_category_setting('all', 'something'));
        $this->assertFalse($hook->is_tenant_customizable_category_setting('some', 'one'));
        $this->assertTrue($hook->is_tenant_customizable_category_setting('new', 'A'));

        // Invalid
        $hook->set_customizable_settings(['string_only']);
        $this->assertFalse($hook->is_tenant_customizable_category('all'));
        $this->assertFalse($hook->is_tenant_customizable_category('string_only'));
        $this->assertFalse($hook->is_tenant_customizable_category('0'));

        // Check theme_config
        $this->assertInstanceOf('theme_config', $hook->get_theme_config());
        $this->assertEquals('ventura', $hook->get_theme_config()->name);
    }

    public function test_hook_with_legacy_signature() {

        $this->setAdminUser();
        $test_settings = [
            'all' => '*',
            'some' => ['one', 'two'],
        ];

        // No theme_config passed, nothing breaks.
        $hook = new tenant_customizable_theme_settings_hook($test_settings);
        $this->assertEqualsCanonicalizing($test_settings, $hook->get_customizable_settings());
        $this->assertNull($hook->get_theme_config());
    }
}
