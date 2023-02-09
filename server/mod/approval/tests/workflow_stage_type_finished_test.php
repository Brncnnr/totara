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
use mod_approval\model\workflow\stage_type\finished;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\workflow\stage_type\finished
 */
class mod_approval_workflow_stage_type_finished_testcase extends testcase {

    public function test_get_label() {
        $this->assertEquals('End', finished::get_label());
    }

    public function test_get_code() {
        $this->assertEquals(30, finished::get_code());
    }

    public function test_get_enum() {
        $this->assertEquals('FINISHED', finished::get_enum());
    }

    public function test_get_sort_order() {
        $this->assertEquals(40, finished::get_sort_order());
    }

    public function test_get_available_actions() {
        $expected = [];
        $this->assertEquals($expected, finished::get_available_actions());
    }
}
