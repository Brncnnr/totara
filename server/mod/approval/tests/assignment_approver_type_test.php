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
use mod_approval\exception\model_exception;
use mod_approval\model\assignment\approver_type\relationship;
use mod_approval\model\assignment\approver_type\user;
use mod_approval\model\assignment\assignment_approver_type;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\assignment\assignment_approver_type
 */
class mod_approval_assignment_approver_type_testcase extends testcase {

    /**
     * @covers \mod_approval\model\assignment\assignment_approver_type::is_valid
     */
    public function test_is_valid() {
        $this->assertTrue(assignment_approver_type::is_valid(user::TYPE_IDENTIFIER));
        $this->assertTrue(assignment_approver_type::is_valid(relationship::TYPE_IDENTIFIER));
        $this->assertFalse(assignment_approver_type::is_valid(-1));
    }

    /**
     * @covers \mod_approval\model\assignment\assignment_approver_type::get_instance
     */
    public function test_get_instance() {
        // user
        $user_instance = assignment_approver_type::get_instance(user::TYPE_IDENTIFIER);
        $this->assertInstanceOf(user::class, $user_instance);

        // relationship
        $user_instance = assignment_approver_type::get_instance(relationship::TYPE_IDENTIFIER);
        $this->assertInstanceOf(relationship::class, $user_instance);

        // unknown
        try {
            $unknown_instance = assignment_approver_type::get_instance(-1);
            $this->fail('Expected model_exception');
        } catch (model_exception $e) {
            $this->assertEquals('Unknown assignment_approver type code', $e->debuginfo);
        }
    }

    /**
     * @covers \mod_approval\model\assignment\assignment_approver_type::get_list
     */
    public function test_get_list() {
        $list = assignment_approver_type::get_list();
        $this->assertCount(2, $list);
    }
}
