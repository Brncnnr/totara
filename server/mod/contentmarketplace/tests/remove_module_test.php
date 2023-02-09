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
 * @package mod_contentmarketplace
 */

use container_course\hook\remove_module_hook;
use core_container\hook\module_supported_in_container;
use core_phpunit\testcase;
use container_course\course_helper;

/**
 * @group totara_contentmarketplace
 */
class mod_contentmarketplace_remove_module_testcase extends testcase {
    /**
     * @return void
     */
    public function test_execute_remove_module_hook(): void {
        $hook_sink = self::redirectHooks();
        self::assertEquals(0, $hook_sink->count());
        self::assertEmpty($hook_sink->get_hooks());

        $modules = course_helper::get_all_modules(false);
        self::assertArrayNotHasKey('contenttmarketplace', $modules);

        $hooks = $hook_sink->get_hooks();
        self::assertNotEmpty($hooks);
        self::assertCount(2, $hooks);

        [$first_hook, $second_hook] = $hooks;

        self::assertInstanceOf(module_supported_in_container::class, $first_hook);
        self::assertInstanceOf(remove_module_hook::class, $second_hook);
    }

    /**
     * @return void
     */
    public function test_skip_remove_module_hook(): void {
        $hook_sink = self::redirectHooks();
        self::assertEquals(0, $hook_sink->count());
        self::assertEmpty($hook_sink->get_hooks());

        $modules = course_helper::get_all_modules(true, true, false);
        self::assertArrayHasKey('contentmarketplace', $modules);

        $hooks = $hook_sink->get_hooks();
        self::assertNotEmpty($hooks);
        self::assertCount(1, $hooks);

        [$hook] = $hooks;
        self::assertInstanceOf(module_supported_in_container::class, $hook);
    }
}