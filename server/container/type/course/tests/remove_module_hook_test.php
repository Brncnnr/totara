<?php
/**
 * This file is part of Totara Core
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_course
 */

use container_course\hook\remove_module_hook;
use core_phpunit\testcase;

class container_course_remove_module_hook_testcase extends testcase {
    /**
     * @return void
     */
    public function test_remove_invalid_module(): void {
        $hook = new remove_module_hook(['ccc' => 'ddd']);
        $hook->remove_module('ddd');

        self::assertTrue($hook->has_module('ccc'));

        $modules = $hook->get_modules();
        self::assertArrayHasKey('ccc', $modules);
        self::assertDebuggingCalled("The  module 'ddd' does not exist in the list");
    }

    /**
     * @return void
     */
    public function test_remove_module(): void {
        $hook = new remove_module_hook(['ddd' => 'ccc']);
        $hook->remove_module('ddd');

        self::assertFalse($hook->has_module('ddd'));

        $modules = $hook->get_modules();
        self::assertArrayNotHasKey('ddd', $modules);
        self::assertEmpty($modules);
    }
}