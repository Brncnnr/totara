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
use mod_approval\model\assignment\assignment_type\provider;
use mod_approval\model\assignment\assignment_type\organisation;
use mod_approval\model\assignment\assignment_type\cohort;
use mod_approval\model\assignment\assignment_type\position;

/**
 * @group approval_workflow
 * @covers \mod_approval\model\assignment\assignment_type\provider
 */
class mod_approval_assignment_assignment_type_provider_testcase extends testcase {

    public function test_get_all() {
        $assignment_types = provider::get_all();
        $this->assertCount(3, $assignment_types);

        $expected = [
            10 => organisation::class,
            20 => position::class,
            30 => cohort::class,
        ];
        $this->assertEquals($expected, $assignment_types);
    }

    public function test_get_by_code() {
        $this->assertEquals(
            cohort::class,
            provider::get_by_code(cohort::get_code())
        );

        $this->expectException(model_exception::class);
        $this->expectExceptionMessage("Unknown assignment type code: 0");
        provider::get_by_code(0);
    }

    public function test_get_by_enum() {
        $this->assertEquals(
            cohort::class,
            provider::get_by_enum(cohort::get_enum())
        );

        $this->expectException(model_exception::class);
        $this->expectExceptionMessage("Unknown assignment type enum: TOTARA");
        provider::get_by_enum("TOTARA");
    }
}
