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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

use core_phpunit\testcase;
use mod_approval\model\application\action\action;
use mod_approval\model\application\action\approve;
use mod_approval\model\application\action\reject;
use mod_approval\model\application\action\withdraw_before_submission;
use mod_approval\model\application\action\withdraw_in_approvals;
use mod_approval\model\application\application;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\application\action\action
 */
class mod_approval_application_action_action_testcase extends testcase {
    /**
     * @covers ::is_valid
     */
    public function test_is_valid(): void {
        $this->assertTrue(action::is_valid(approve::get_code()));
        $this->assertFalse(action::is_valid(-1));
    }

    /**
     * @covers ::get_class_map
     */
    public function test_get_class_map(): void {
        $method = new ReflectionMethod(action::class, 'get_class_map');
        $method->setAccessible(true);
        $classes = $method->invoke(null);
        $this->assertEquals(reject::class, $classes[0]);
        $this->assertEquals(approve::class, $classes[1]);
        $this->assertEquals(withdraw_in_approvals::class, $classes[2]);
        $this->assertEquals(withdraw_before_submission::class, $classes[3]);
    }

    /**
     * @covers ::from_code
     */
    public function test_from_code(): void {
        $this->assertInstanceOf(reject::class, action::from_code(0));
        $this->assertInstanceOf(approve::class, action::from_code(1));
        $this->assertInstanceOf(withdraw_in_approvals::class, action::from_code(2));
        $this->assertInstanceOf(withdraw_before_submission::class, action::from_code(3));
    }

    /**
     * @covers ::from_enum
     */
    public function test_from_status(): void {
        $this->assertInstanceOf(reject::class, action::from_enum('REJECT'));
        $this->assertInstanceOf(approve::class, action::from_enum('APPROVE'));
        $this->assertInstanceOf(withdraw_in_approvals::class, action::from_enum('WITHDRAW_IN_APPROVALS'));
        $this->assertInstanceOf(withdraw_before_submission::class, action::from_enum('WITHDRAW_BEFORE_SUBMISSION'));
    }

    public function test_status_must_be_unique(): void {
        $method = new ReflectionMethod(action::class, 'get_class_map');
        $method->setAccessible(true);
        $enums = [];
        foreach ($method->invoke(null) as $class) {
            $enum = $class::get_enum();
            if (strtoupper($enum) !== $enum) {
                $this->fail("Enum {$enum} must be CAPITAL");
            }
            if (isset($enums[$enum])) {
                $this->fail("Enum {$enum} is already taken by {$enums[$enum]}");
            }
            $enums[$enum] = $class;
        }
    }
}
