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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package core
 */

use core_phpunit\testcase;

class core_hook_navigation_load_course_settings_testcase extends testcase {

    public function test_execute() {
        $course = $this->getDataGenerator()->create_course();

        $self = $this;
        $hook = function ($hook) use ($self, &$i, $course) {
            $self->assertInstanceOf(core\hook\navigation_load_course_settings::class, $hook);
            $self->assertInstanceOf(\stdClass::class, $hook->get_course());
            $self->assertSame($course, $hook->get_course());
        };

        $watchers = array(
            array(
                'hookname' => 'core\hook\navigation_load_course_module_settings',
                'callback' => $hook,
            ),
        );
        totara_core\hook\manager::phpunit_replace_watchers($watchers);

        $node = new \navigation_node(['text' => 'test']);

        $instance = new core\hook\navigation_load_course_settings($node, $course);
        $instance->execute();

        totara_core\hook\manager::phpunit_reset();
    }

}