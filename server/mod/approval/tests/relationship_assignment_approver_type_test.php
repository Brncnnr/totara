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

use core_phpunit\testcase;
use mod_approval\model\assignment\approver_type\relationship;
use totara_core\entity\relationship as relationship_entity;
use totara_core\relationship\relationship as relationship_model;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\assignment\approver_type\relationship
 */
class mod_approval_relationship_assignment_approver_type_testcase extends testcase {

    /**
     * @covers \mod_approval\model\assignment\approver_type\relationship::entity
     */
    public function test_entity() {
        $relationship_approver_type_instance = new relationship();
        $allowed_relationships = $this->get_allowed_relationships();

        if (empty($allowed_relationships)) {
            $this->fail('No allowed approver types relationships specified');
        }

        $id_number = $allowed_relationships[0];
        $relationship_entity_from_db = relationship_entity::repository()
            ->where('idnumber', $id_number)
            ->get()
            ->first();
        $relationship_model = $relationship_approver_type_instance->entity($relationship_entity_from_db->id);
        $this->assertInstanceOf(relationship_model::class, $relationship_model);
    }

    /**
     * @covers \mod_approval\model\assignment\approver_type\relationship::is_valid
     */
    public function test_is_valid() {
        $allowed_relationships = $this->get_allowed_relationships();

        $instance = new relationship();
        $this->assertFalse($instance->is_valid(-10));

        if (empty($allowed_relationships)) {
            $this->fail('No allowed approver types relationships specified');
        }

        $id_number = $allowed_relationships[0];
        $relationship_entity_from_db = relationship_entity::repository()
            ->where('idnumber', $id_number)
            ->get()
            ->first();

        $this->assertTrue($instance->is_valid($relationship_entity_from_db->id));
    }

    /**
     * Uses reflections to get the allowed relationships defined in the relationship class.
     * @return array
     */
    private function get_allowed_relationships(): array {
        $reflection = new ReflectionClass(relationship::class);

        return $reflection->getConstant('ALLOWED_RELATIONSHIPS');
    }

    /**
     * @covers \mod_approval\model\assignment\approver_type\relationship::entity_name
     */
    public function test_entity_name() {
        $relationship_approver_type_instance = new relationship();

        $relationship_model = relationship_model::load_by_idnumber('manager');
        $relationship_name = $relationship_approver_type_instance->entity_name($relationship_model->id);
        $this->assertEquals('Manager', $relationship_name);
    }

    /**
     * @covers \mod_approval\model\assignment\approver_type\relationship::label
     */
    public function test_label() {
        $relationship_label = (new relationship())->label();
        $this->assertEquals('Relationship', $relationship_label);
    }
}
