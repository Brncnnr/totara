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

use core\entity\user as user_entity;
use core_phpunit\testcase;
use mod_approval\model\assignment\approver_type\user;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\assignment\approver_type\user
 */
class mod_approval_user_assignment_approver_type_testcase extends testcase {

    /**
     * @covers \mod_approval\model\assignment\approver_type\user::entity
     */
    public function test_entity() {
        $user = $this->getDataGenerator()->create_user();
        $user_entity = new user_entity($user);
        $user_approver_type = new user();
        $this->assertEquals($user_entity, $user_approver_type->entity($user->id));
    }

    /**
     * @covers \mod_approval\model\assignment\approver_type\user::entity_name
     */
    public function test_entity_name() {
        $user = $this->getDataGenerator()->create_user();
        $user_entity = new user_entity($user);
        $user_approver_type = new user();
        $this->assertEquals($user_entity->fullname, $user_approver_type->entity_name($user->id));
    }

    /**
     * @covers \mod_approval\model\assignment\approver_type\user::label
     */
    public function test_label() {
        $user_approver_type = new user();
        $this->assertEquals('Individual', $user_approver_type->label());
    }

    /**
     * @covers \mod_approval\model\assignment\approver_type\user::is_valid
     */
    public function test_is_valid() {
        $instance = new user();
        $this->assertFalse($instance->is_valid(-10));

        $user = $this->getDataGenerator()->create_user();
        $this->assertTrue($instance->is_valid($user->id));
    }
}
