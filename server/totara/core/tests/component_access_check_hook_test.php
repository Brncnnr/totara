<?php

use core_phpunit\testcase;
use totara_core\hook\component_access_check;

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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_core
 */

class component_access_check_hook_testcase extends testcase {

    public function test_hook_is_triggered() {
        $hook_sink = self::redirectHooks();

        $hook_sink->clear();
        $hooks = $hook_sink->get_hooks();
        $this->assertCount(0, $hooks);

        $hook = new component_access_check('test_component', 1, 2, null);
        $hook->execute();

        $hooks = $hook_sink->get_hooks();
        self::assertCount(1, $hooks);

        $hook = reset($hooks);
        self::assertTrue($hook instanceof component_access_check);
        self::assertSame('test_component', $hook->get_component_name());
        self::assertFalse($hook->has_permission());

        $hook->give_permission();
        self::assertTrue($hook->has_permission());
    }
}