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
use core_phpunit\testcase;
use mod_contentmarketplace\watcher\remove_module_watcher;

/**
 * @group totara_contentmarketplace
 */
class mod_contentmarketplace_remove_module_watcher_testcase extends testcase {
    /**
     * @return void
     */
    public function test_remove_non_existing_module(): void {
        $hook = new remove_module_hook(['dc' => 'better than marvel']);
        remove_module_watcher::watch($hook);

        self::assertTrue($hook->has_module('dc'));
        $modules = $hook->get_modules();

        self::assertNotEmpty($modules);
        self::assertCount(1, $modules);
        self::assertDebuggingNotCalled("The module 'contentmarketplace' does not exist in the list");
    }

    /**
     * @return void
     */
    public function test_remove_existing_module(): void {
        $hook = new remove_module_hook(['contentmarketplace' => 'Bob is designing it']);
        remove_module_watcher::watch($hook);

        self::assertFalse($hook->has_module('contentmarketplace'));

        $modules = $hook->get_modules();
        self::assertEmpty($modules);
    }
}