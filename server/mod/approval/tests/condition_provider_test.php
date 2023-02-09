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
use mod_approval\model\form\field_conditions\condition_provider;
use mod_approval\model\form\field_conditions\equals;

/**
 * @group approval_workflow
 * @coversDefaultClass \mod_approval\model\form\field_conditions\condition_provider
 */
class mod_approval_condition_provider_testcase extends testcase {

    /**
     * @covers ::get_instance
     */
    public function test_get_instance_returns_correct_instance() {
        $equals_condition = condition_provider::get_instance(equals::IDENTIFIER);
        $this->assertInstanceOf(equals::class, $equals_condition);
    }

    /**
     * @covers ::get_instance
     */
    public function test_get_instance_throws_error_for_unknown_instance() {
        $unknown_identifier = 'unknown';
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("unknown condition identifier '$unknown_identifier'");
        condition_provider::get_instance($unknown_identifier);
    }
}
