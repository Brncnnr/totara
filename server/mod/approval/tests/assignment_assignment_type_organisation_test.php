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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

use core\orm\query\exceptions\record_not_found_exception;
use core\testing\generator;
use core_phpunit\testcase;
use hierarchy_organisation\entity\organisation as organisation_entity;
use mod_approval\model\assignment\assignment_type\organisation;

/**
 * @group approval_workflow
 * @covers \mod_approval\model\assignment\assignment_type\organisation
 */
class mod_approval_assignment_assignment_type_organisation_testcase extends testcase {

    public function test_get_label() {
        $this->assertEquals(
            get_string('model_assignment_type_organisation', 'mod_approval'),
            organisation::get_label()
        );
    }

    public function test_get_code() {
        $this->assertEquals(
            1,
            organisation::get_code()
        );
    }

    public function test_get_enum() {
        $this->assertEquals(
            'ORGANISATION',
            organisation::get_enum()
        );
    }

    public function test_get_sort_order() {
        $this->assertEquals(
            10,
            organisation::get_sort_order()
        );
    }

    public function test_instance_with_fullname_and_shortname() {
        $hierarchy_generator = generator::instance()->get_plugin_generator('totara_hierarchy');
        $framework = $hierarchy_generator->create_framework('organisation');
        $organisation = $hierarchy_generator->create_org(
            [
                'frameworkid' => $framework->id,
                'fullname' => 'Senior executive',
                'shortname' => 'se'
            ]
        );

        $assignment_type = organisation::instance($organisation->id);
        $this->assertInstanceOf(organisation::class, $assignment_type);
        $this->assertInstanceOf(organisation_entity::class, $assignment_type->get_entity());
        $this->assertEquals('Senior executive', $assignment_type->get_name());
        $this->assertEquals('se', $assignment_type->get_id_number());
    }

    public function test_instance_without_fullname_and_shortname() {
        $hierarchy_generator = generator::instance()->get_plugin_generator('totara_hierarchy');
        $framework = $hierarchy_generator->create_framework('organisation');
        $organisation = $hierarchy_generator->create_org(
            [
                'frameworkid' => $framework->id,
                'fullname' => '',
            ]
        );

        $assignment_type = organisation::instance($organisation->id);
        $this->assertInstanceOf(organisation::class, $assignment_type);
        $this->assertInstanceOf(organisation_entity::class, $assignment_type->get_entity());
        $this->assertEquals('Untitled organisation', $assignment_type->get_name());
        $this->assertEquals("ORGANISATION_$organisation->id", $assignment_type->get_id_number());
    }

    public function test_invalid_instance() {
        $this->expectException(record_not_found_exception::class);
        organisation::instance(-1);
    }
}
