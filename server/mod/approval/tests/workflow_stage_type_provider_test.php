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
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\stage_type\finished;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\stage_type\provider;
use mod_approval\model\workflow\stage_type\waiting;
use mod_approval\model\workflow\workflow_stage;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\workflow\stage_type\provider
 */
class mod_approval_workflow_stage_type_provider_testcase extends testcase {

    public function test_get_types() {
        $types = provider::get_types();
        $this->assertCount(4, $types);

        // Test order:
        $this->assertEquals(form_submission::class, array_shift($types));
        $this->assertEquals(approvals::class, array_shift($types));
        $this->assertEquals(waiting::class, array_shift($types));
        $this->assertEquals(finished::class, array_shift($types));
    }

    public function test_get_by_code() {
        $this->assertEquals(form_submission::class, provider::get_by_code(10));
        $this->assertEquals(approvals::class, provider::get_by_code(20));
        $this->assertEquals(waiting::class, provider::get_by_code(25));
        $this->assertEquals(finished::class, provider::get_by_code(30));
    }

    public function test_get_by_invalid_code() {
        $mock_workflow_stage = $this->createStub(workflow_stage::class);
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Undefined code for stage type');
        provider::get_by_code(99);
    }

    public function test_get_by_enum() {
        $this->assertEquals(form_submission::class, provider::get_by_enum(form_submission::get_enum()));
        $this->assertEquals(approvals::class, provider::get_by_enum(approvals::get_enum()));
        $this->assertEquals(waiting::class, provider::get_by_enum(waiting::get_enum()));
        $this->assertEquals(finished::class, provider::get_by_enum(finished::get_enum()));
    }

    public function test_get_by_invalid_enum() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Undefined enum for stage type');
        provider::get_by_enum('00');
    }
}