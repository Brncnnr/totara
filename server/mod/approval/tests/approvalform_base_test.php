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
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 * @package mod_approval
 */

use core_phpunit\testcase;
use mod_approval\form_schema\form_schema;
use mod_approval\model\form\approvalform_base;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\form\approvalform_base
 */
class mod_approval_approvalform_base_testcase extends testcase {
    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    /**
     * @covers ::from_plugin_name
     */
    public function test_from_plugin_name(): void {
        $plugin = approvalform_base::from_plugin_name('simple');
        $this->assertInstanceOf(approvalform_simple\simple::class, $plugin);
    }

    /**
     * @covers ::get_form_schema
     */
    public function test_get_form_schema(): void {
        global $CFG;
        $plugin = approvalform_base::from_plugin_name('simple');
        $this->overrideLangString('pluginname', 'approvalform_simple', 'Hooray!');
        $json_data = file_get_contents($CFG->dirroot . '/mod/approval/tests/fixtures/form/test_form_help.json');
        $form_schema = form_schema::from_json($json_data);
        $prop = new ReflectionProperty(approvalform_base::class, 'form_schema');
        $prop->setAccessible(true);
        $prop->setValue($plugin, $form_schema);

        $field = $plugin->get_form_schema()->get_field('agency_code');
        $this->assertFalse($field->disabled);
        $this->assertEquals('<div class="text_to_html">Hooray!</div>', $field->help_html);
    }
}
