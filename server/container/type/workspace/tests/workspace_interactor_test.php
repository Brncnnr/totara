<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use container_workspace\member\member;
use container_workspace\interactor\workspace\interactor;
use core\orm\query\builder;
use core_phpunit\testcase;

/**
 * @group container_workspace
 * @group totara_engage
 */
class container_workspace_workspace_interactor_testcase extends testcase {
    /**
     * @return void
     */
    public function test_has_seen(): void {
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $this->setAdminUser();

        $workspace = $workspace_generator->create_workspace();
        $interactor = new interactor($workspace);

        $this->assertTrue(
            $interactor->has_seen(time() + 500),
            "The seen time is greater than the timestamp of workspace should " .
            "result in user had already seen the workspace"
        );

        $this->assertFalse(
            $interactor->has_seen(time() - 3600),
            "The seen time is less than the timestap of the workspace which it should result in " .
            "user had not seen the workspace"
        );
    }

    /**
     * @return void
     */
    public function test_check_owner(): void {
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $this->setUser($user_one);
        $workspace = $workspace_generator->create_workspace();

        $user_one_interactor = new interactor($workspace, $user_one->id);
        $user_two_interactor = new interactor($workspace, $user_two->id);

        $this->assertTrue($user_one_interactor->is_owner());
        $this->assertTrue($user_one_interactor->is_primary_owner());
        $this->assertDebuggingCalled('\container_workspace\interactor\workspace\interactor::is_primary_owner() has been deprecated, please use \container_workspace\interactor\workspace\interactor::is_owner() instead');

        $this->assertFalse($user_two_interactor->is_owner());
        $this->assertFalse($user_two_interactor->is_primary_owner());
        $this->assertFalse($user_two_interactor->is_joined());
        $this->assertDebuggingCalled('\container_workspace\interactor\workspace\interactor::is_primary_owner() has been deprecated, please use \container_workspace\interactor\workspace\interactor::is_owner() instead');
    }

    /**
     * @return void
     */
    public function test_check_join(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $user_two_interactor = new interactor($workspace, $user_two->id);
        $this->assertFalse($user_two_interactor->is_joined());

        member::join_workspace($workspace, $user_two->id);
        $this->assertTrue($user_two_interactor->is_joined());
    }

    /**
     * @return void
     */
    public function test_check_manage(): void {
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $this->setUser($user_one);
        $workspace = $workspace_generator->create_workspace();

        $user_one_interactor = new interactor($workspace, $user_one->id);
        $user_two_interactor = new interactor($workspace, $user_two->id);

        $this->assertTrue($user_one_interactor->can_manage());
        $this->assertFalse($user_two_interactor->can_manage());

        // Even after user two join the workspace, user two should not be able to manage the workspace.
        member::join_workspace($workspace, $user_two->id);
        $user_two_interactor->reload_workspace();

        $this->assertFalse($user_two_interactor->can_manage());
    }

    /**
     * Tests reason a user can not leave a workspace.
     *
     * @return void
     */
    public function test_cannot_leave_reason(): void {
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        $user_one = $generator->create_user();

        $this->setUser($user_one);
        $workspace = $workspace_generator->create_workspace();

        $interactor = new interactor($workspace, $user_one->id);
        $this->assertEquals(interactor::IS_OWNER, $interactor->cannot_leave_reason());

        // Non-member
        $user_two = $generator->create_user();
        $interactor = new interactor($workspace, $user_two->id);
        $this->assertEquals(interactor::NOT_A_MEMBER, $interactor->cannot_leave_reason());

        // Member: Regular members can leave.
        member::join_workspace($workspace, $user_two->id);
        $interactor = new interactor($workspace, $user_two->id);
        $this->assertNull($interactor->cannot_leave_reason());

        // Member has Audience membership
        $cohort = $generator->create_cohort();
        cohort_add_member($cohort->id, $user_two->id);
        container_workspace\enrol\manager::from_workspace($workspace)->enrol_audiences([$cohort->id]);
        $this->executeAdhocTasks();

        $interactor = new interactor($workspace, $user_two->id);
        $this->assertEquals(interactor::AUDIENCE_MEMBERSHIP, $interactor->cannot_leave_reason());
    }

    /**
     * @return void
     */
    public function test_check_against_deleted_workspace(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);


        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $interactor = new interactor($workspace, $user_one->id);

        self::assertTrue(
            $interactor->can_view_discussions(),
            "User should be able able to view discussions before workspace delete"
        );

        self::assertTrue(
            $interactor->can_view_members(),
            "User should be able to view members before workspace delete"
        );

        self::assertTrue(
            $interactor->can_view_workspace(),
            "User should be able to view the workspace before workspace delete"
        );

        self::assertTrue(
            $interactor->can_view_workspace_with_tenant_check(),
            "User should be able to the workspace before workspace delete"
        );

        self::assertTrue(
            $interactor->can_view_library(),
            "User should be able to view library before workspace delete"
        );

        self::assertTrue(
            $interactor->can_manage(),
            "User should be able to manage workspace before it get deleted"
        );

        self::assertTrue(
            $interactor->can_update(),
            "User should be able to update workspace before it get deleted"
        );

        self::assertTrue($interactor->can_delete(), "User should be able to delete workspace");
        self::assertFalse($interactor->can_join(), "User should not be able to join the workspace");
        self::assertTrue($interactor->can_remove_members(), "User should be able to remove the member of workspace");
        self::assertFalse(
            $interactor->can_decline_member_request(),
            "User should not be able to decline member request because workspace is a public"
        );

        self::assertTrue(
            $interactor->can_share_resources(),
            "User should be able to share the resources before workspace delete"
        );

        self::assertTrue(
            $interactor->can_unshare_resources(),
            "User should be able to share the resources before workspace delete"
        );

        self::assertTrue(
            $interactor->can_add_members(),
            "User should be able to add members to workspace before deletion"
        );

        self::assertTrue(
            $interactor->can_invite(),
            "User should be able to invite members to workspace before deletion"
        );

        $workspace->mark_to_be_deleted();
        $interactor->reload_workspace();

        self::assertFalse(
            $interactor->can_view_discussions(),
            "User should not be able able to view discussions after workspace delete"
        );

        self::assertFalse(
            $interactor->can_view_members(),
            "User should not be able to view members after workspace delete"
        );

        self::assertFalse(
            $interactor->can_view_workspace(),
            "User should not be able to view the workspace after workspace delete"
        );

        self::assertFalse(
            $interactor->can_view_workspace_with_tenant_check(),
            "User should not be able to the workspace after workspace delete"
        );

        self::assertFalse(
            $interactor->can_view_library(),
            "User should not be able to view library after workspace delete"
        );

        self::assertFalse(
            $interactor->can_manage(),
            "User should not be able to manage workspace after it get deleted"
        );

        self::assertFalse(
            $interactor->can_update(),
            "User should not be able to update workspace after it get deleted"
        );

        self::assertTrue(
            $interactor->can_delete(),
            "User should be able to delete workspace even after it get flag to deleted"
        );

        self::assertFalse(
            $interactor->can_join(),
            "User should not be able to join the workspace as user is already a member of the workspace"
        );

        self::assertTrue(
            $interactor->can_remove_members(),
            "User should be able to remove the member of workspace, as it does not care about deleted flag"
        );

        self::assertFalse(
            $interactor->can_decline_member_request(),
            "User should not be able to decline member request because workspace is a public"
        );

        self::assertFalse(
            $interactor->can_share_resources(),
            "User should not be able to share the resources after workspace delete"
        );

        self::assertTrue(
            $interactor->can_unshare_resources(),
            "User should be able to unshare the resources even the workspace is deleted"
        );

        self::assertFalse(
            $interactor->can_add_members(),
            "User should not be able to add members to workspace after deletion"
        );

        self::assertFalse(
            $interactor->can_invite(),
            "User should not be able to invite members to workspace after deletion"
        );
    }

    /**
     * @return void
     */
    public function test_check_owner_capability() {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $this->setUser($user_one);

        /** @var container_workspace_generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();
        $interactor = new interactor($workspace, $user_one->id);

        self::assertTrue(
            $interactor->can_add_members(),
            "Workspace owner should be capable to add members to workspace"
        );

        // Remove capability and confirm that workspace owner cannot add members.
        $this->remove_workspace_owner_capability("container/workspace:addmember");
        self::assertFalse(
            $interactor->can_add_members(),
            "Workspace owner should not be able to add members to workspace without capability"
        );

        self::assertTrue(
            $interactor->can_invite(),
            "Workspace owner should be capable to invite members to workspace"
        );

        // Remove capability and confirm that workspace owner cannot invite members.
        $this->remove_workspace_owner_capability("container/workspace:invite");
        self::assertFalse(
            $interactor->can_invite(),
            "Workspace owner should not be able to invite members to workspace without capability"
        );

        // Confirm that admin still has capability.
        $interactor = new interactor($workspace, get_admin()->id);
        self::assertTrue(
            $interactor->can_add_members(),
            "Admin should be capable to add members to workspace"
        );
        self::assertTrue(
            $interactor->can_invite(),
            "Admin should be capable to invite members to workspace"
        );
    }

    /**
     * @return void
     */
    public function test_check_if_user_can_add_audiences(): void {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        $this->setUser($user);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Admins always should be able to add audiences
        $this->setAdminUser();

        $interactor = new interactor($workspace);
        $this->assertTrue($interactor->can_add_audiences());

        $this->setUser($user);

        $interactor = new interactor($workspace, $user->id);
        $this->assertFalse($interactor->can_add_audiences());

        // Now give the user the capability
        $user_role = builder::table('role')->where('shortname', 'user')->one();
        assign_capability('moodle/cohort:view', CAP_ALLOW, $user_role->id, SYSCONTEXTID);

        $interactor = new interactor($workspace, $user->id);
        $this->assertTrue($interactor->can_add_audiences());
    }

    private function remove_workspace_owner_capability(string $capability) {
        $role = builder::table('role')->where('shortname', 'workspaceowner')->value('id');
        unassign_capability($capability, $role);
    }

    /**
     * @return void
     */
    public function test_authenticated_user_can_view_workspace(): void {
        $generator = $this->getDataGenerator();

        // Login as admin user.
        self::setAdminUser();
        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $user = $generator->create_user();
        $user_context = context_user::instance($user->id);

        // Login as system user.
        self::setUser($user);

        $interactor = new interactor($workspace, $user->id);
        self::assertTrue($interactor->can_view_workspace());

        // Remove capability.
        $roles = get_archetype_roles('user');
        $role = reset($roles);
        assign_capability('container/workspace:workspacesview', CAP_PREVENT, $role->id, $user_context->id);

        self::assertFalse($interactor->can_view_workspace());
    }

    /**
     * @return void
     */
    public function test_can_share_resources(): void {
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        // Login as system user.
        self::setUser($user);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $interactor = new interactor($workspace, $user->id);
        self::assertTrue($interactor->can_share_resources());

        // Remove capability.
        $roles = get_archetype_roles('user');
        $role = reset($roles);
        assign_capability('engage/survey:share', CAP_PREVENT, $role->id, $workspace->get_context()->id);
        assign_capability('engage/article:share', CAP_PREVENT, $role->id, $workspace->get_context()->id);
        assign_capability('totara/playlist:share', CAP_PREVENT, $role->id, $workspace->get_context()->id);

        self::assertFalse($interactor->can_share_resources());
    }

}