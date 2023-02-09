<?php
/**
 * This file is part of Totara TXP
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Simon Chester <simon.chester@totara.com>
 * @package totara_cohort
 */

use core_phpunit\testcase;
use totara_cohort\hook\delete_affects;

class totara_cohort_hook_delete_affects_testcase extends testcase {
    /**
     * Test that the hook collects the affected area information given to it, and returns it later.
     */
    public function test_adding_affected_areas() {
        $hook = new delete_affects(99);

        $hook->add_affected('foo1', 'bar1', 'baz1', 11);
        $hook->add_affected('foo2', 'bar2', 'baz2', 12);

        $this->assertEquals(99, $hook->get_id());
        $this->assertEqualsCanonicalizing(
            [
                (object)['component' => 'foo1', 'area' => 'bar1', 'changes' => 'baz1', 'count' => 11],
                (object)['component' => 'foo2', 'area' => 'bar2', 'changes' => 'baz2', 'count' => 12]
            ],
            $hook->get_affected()
        );
    }
}
