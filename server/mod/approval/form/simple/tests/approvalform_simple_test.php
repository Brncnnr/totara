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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package approvalform_simple
 */

use core_phpunit\testcase;
use mod_approval\form_schema\form_schema;
use mod_approval\model\form\approvalform_base;

/**
 * @coversDefaultClass \approvalform_simple\simple
 *
 * @group approval_workflow
 */
class approvalform_simple_testcase extends testcase {

    /**
     * @covers ::from_plugin_name
     */
    public function test_instantiation(): void {
        $simple_plugin = approvalform_base::from_plugin_name('simple');
        $this->assertInstanceOf('\approvalform_simple\simple', $simple_plugin);

        // Test plugininfo getter.
        $this->assertEquals('approvalform', $simple_plugin->type);
        $this->assertEquals('simple', $simple_plugin->name);
    }

    /**
     * @covers ::get_form_schema_json
     */
    public function test_get_form_schema_json(): void {
        global $CFG;
        $simple_plugin = approvalform_base::from_plugin_name('simple');
        $form_schema = form_schema::from_json(file_get_contents($CFG->dirroot . '/mod/approval/form/simple/form.json'));
        $this->assertEquals($form_schema->to_json(), $simple_plugin->get_form_schema_json());
    }

    /**
     * @covers ::get_form_version
     */
    public function test_get_form_version(): void {
        $simple_plugin = approvalform_base::from_plugin_name('simple');
        $form_schema = $simple_plugin->get_form_schema();
        $this->assertEquals($form_schema->get_version(), $simple_plugin->get_form_version());
    }

    /**
     * @covers ::is_enabled
     */
    public function test_is_enabled(): void {
        $simple_plugin = approvalform_base::from_plugin_name('simple');
        $this->assertTrue($simple_plugin->is_enabled());
        \mod_approval\plugininfo\approvalform::disable_plugin('simple');
        $this->assertFalse($simple_plugin->is_enabled());
    }

    /**
     * @covers ::default_version_status
     */
    public function test_default_version_status(): void {
        $simple_plugin = approvalform_base::from_plugin_name('simple');
        $this->assertEquals(\mod_approval\model\status::ACTIVE, $simple_plugin->default_version_status());
    }
}