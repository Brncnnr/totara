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
use mod_approval\model\application\action\approve;
use mod_approval\model\application\action\reject;
use mod_approval\model\application\action\reset_approvals;
use mod_approval\model\application\action\withdraw_in_approvals;
use mod_approval\model\workflow\stage_type\approvals;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\workflow\stage_type\approvals
 */
class mod_approval_workflow_stage_type_approvals_testcase extends testcase {

    public function test_get_label() {
        $this->assertEquals('Approver', approvals::get_label());
    }

    public function test_get_code() {
        $this->assertEquals(20, approvals::get_code());
    }

    public function test_get_enum() {
        $this->assertEquals('APPROVALS', approvals::get_enum());
    }

    public function test_get_sort_order() {
        $this->assertEquals(20, approvals::get_sort_order());
    }

    public function test_get_available_actions() {
        $expected = [
            approve::class,
            reject::class,
            withdraw_in_approvals::class,
            reset_approvals::class,
        ];
        $this->assertEquals($expected, approvals::get_available_actions());
    }
}
