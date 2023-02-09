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
 * @package mod_approval
 */

use core_phpunit\testcase;
use totara_webapi\phpunit\webapi_phpunit_helper;
use mod_approval\testing\approval_workflow_test_setup;
use core\orm\query\builder;
use container_approval\approval as approval_container;
use mod_approval\model\assignment\assignment;
use core_role\hook\core_role_potential_assignees_container;

global $CFG;
require_once($CFG->dirroot . '/' . $CFG->admin . '/roles/lib.php');

/**
 * @group approval_workflow
 */
class mod_approval_core_role_container_testcase extends testcase {

    use webapi_phpunit_helper;
    use approval_workflow_test_setup;

    /**
     * Gets the generator instance
     *
     * @return \mod_approval\testing\generator
     */
    protected function generator(): \mod_approval\testing\generator {
        return \mod_approval\testing\generator::instance();
    }

    public function test_core_role_get_potential_user_selector(): void {
        $generator = $this->getDataGenerator();
        [$workflow, $framework, $default_assignment, $override_assignments] = $this->generate_data();

        $system_user = $generator->create_user();
        $all_users = $this->generate_users();

        // Create a system workflow admin.
        $context = approval_container::get_default_category_context();
        $manager_role = builder::table('role')->where('shortname', 'manager')->one();
        role_assign($manager_role->id, $system_user->id, $context, 'mod_approval');

        $this->setUser($system_user);

        // Create the user selector objects.
        $workflow_manager_role = builder::table('role')->where('shortname', 'approvalworkflowmanager')->one();
        $options = ['context' => $context, 'roleid' => $workflow_manager_role->id];
        $potentialuserselector = core_role_get_potential_user_selector($context, 'addselect', $options);
        $this->assertTrue($potentialuserselector instanceof core_role_potential_assignees_course_and_above);
    }

    public function test_hook_is_triggered(): void {
        $generator = $this->getDataGenerator();
        [$workflow, $framework, $default_assignment, $override_assignments] = $this->generate_data();

        $system_user = $generator->create_user();
        $all_users = $this->generate_users();

        // Create a system workflow admin.
        $assignment = assignment::load_by_entity($default_assignment);
        $context = $assignment->get_context();    //approval_container::get_default_category_context();
        $manager_role = builder::table('role')->where('shortname', 'manager')->one();
        role_assign($manager_role->id, $system_user->id, $context, 'mod_approval');

        $this->setUser($system_user);

        // Create the user selector objects.
        $workflow_manager_role = builder::table('role')->where('shortname', 'approvalworkflowmanager')->one();
        $options = ['context' => $context, 'roleid' => $workflow_manager_role->id];
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

    private function generate_data(): array {
        [$workflow, $framework, $default_assignment, $override_assignments] = $this->create_workflow_and_assignment();
        return [$workflow, $framework, $default_assignment, $override_assignments];
    }

    private function generate_users(): array {
        static $users = [];
        if ($users) {
            return $users;
        }
        $generator = $this->getDataGenerator();
        for ($i = 1; $i < 11; $i++) {
            $user = $generator->create_user();
            $users[$user->id] = $user->username;
        }
        return $users;
    }
}