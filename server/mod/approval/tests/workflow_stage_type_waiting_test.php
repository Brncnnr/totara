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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 */

use core_phpunit\testcase;
use mod_approval\model\workflow\stage_type\waiting;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\workflow\stage_type\waiting
 */
class mod_approval_workflow_stage_type_waiting_testcase extends testcase {

    public function test_get_label() {
        $this->assertEquals('Waiting', waiting::get_label());
    }

    public function test_get_code() {
        $this->assertEquals(25, waiting::get_code());
    }

    public function test_get_enum() {
        $this->assertEquals('WAITING', waiting::get_enum());
    }

    public function test_get_sort_order() {
        $this->assertEquals(30, waiting::get_sort_order());
    }
}
