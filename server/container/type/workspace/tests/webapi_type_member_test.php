<?php
/**
 * This file is part of Totara Learn
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package container_workspace
 */

use container_workspace\member\member;
use container_workspace\enrol\manager;
use container_workspace\webapi\resolver\type\member as member_type_resolver;
use core\entity\cohort as cohort_entity;
use core\task\manager as task_manager;
use core\webapi\execution_context;
use totara_cohort\task\enrol_audience_in_course_task;
use core_phpunit\testcase;

/**
 * @group container_workspace
 */
class container_workspace_webapi_type_resolver_member_testcase extends testcase {

    /**
     * Generates a workspace and adds members to it via an audience.
     *
     * @return array
     */
    private function create_workspace_and_users(): array {
        $generator = $this->getDataGenerator();
        $this->setAdminUser();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        $audience = $generator->create_cohort([
            'name' => 'Montessori',
            'idnumber' => 'mont',
        ]);

        $users = [];

        for ($i = 0; $i <= 3; $i++) {
            $user = $generator->create_user();
            cohort_add_member($audience->id, $user->id);
            $users[] = $user;
        }

        $enrol = manager::from_workspace($workspace);

        // Add audience to workspace.
        $enrol->enrol_audiences([$audience->id]);
        $tasks = task_manager::get_adhoc_tasks(enrol_audience_in_course_task::class);
        foreach ($tasks as $task) {
            $task->execute();
        }

        return [
            'users' => $users,
            'workspace' => $workspace,
        ];
    }

    /**
     * Test user without permissions can not see audiences members joined with.
     *
     * @return void
     */
    public function test_resolve_member_audiences_as_a_member(): void {
        $data = $this->create_workspace_and_users();
        $user = current($data['users']);

        $this->setUser($user);
        $workspace = $data['workspace'];
        $member = member::from_user($user->id, $workspace->id);

        $audiences = member_type_resolver::resolve('audiences', $member, [], $this->createMock(execution_context::class));
        $this->assertEmpty($audiences);
    }

    /**
     * Test user with permissions can see audiences members joined with.
     *
     * @return void
     */
    public function test_resolve_member_audiences_as_a_workspace_owner(): void {
        $data = $this->create_workspace_and_users();

        $this->setAdminUser();
        $workspace = $data['workspace'];
        $user = current($data['users']);
        $member = member::from_user($user->id, $workspace->id);

        /** @var cohort_entity[] $audiences*/
        $audiences = member_type_resolver::resolve('audiences', $member, [], $this->createMock(execution_context::class));
        $this->assertNotEmpty($audiences);
        $this->assertCount(1, $audiences);
        $this->assertEquals('Montessori', $audiences[0]->name);
    }
}
