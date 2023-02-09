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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package core_role
 */

use core_phpunit\testcase;
use core\orm\query\builder;
use core_role\hook\core_role_potential_assignees_container;

global $CFG;
require_once($CFG->dirroot . '/' . $CFG->admin . '/roles/lib.php');

class core_role_potential_assignees_container_testcase extends testcase {

    public function test_potential_assignees_container_hook() {
        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);
        // Create the user selector objects.
        $editing_trainer_role = builder::table('role')->where('shortname', 'editingteacher')->one();
        $options = ['context' => $context, 'roleid' => $editing_trainer_role->id];

        $sink = $this->redirectHooks();
        $sink->clear();
        $hooks = $sink->get_hooks();
        $this->assertCount(0, $hooks);

        $potentialuserselector = core_role_get_potential_user_selector($context, 'addselect', $options);
        $hooks = $sink->get_hooks();
        $this->assertCount(1, $hooks);
        /** @var core_role_potential_assignees_container $hook */
        $hook = reset($hooks);
        $this->assertTrue($hook instanceof core_role_potential_assignees_container);
        $this->assertEquals($hook->get_context(), $context);
        $this->assertEquals($hook->get_control_name(), 'addselect');
        $this->assertEquals($hook->get_options(), $options);
    }
}