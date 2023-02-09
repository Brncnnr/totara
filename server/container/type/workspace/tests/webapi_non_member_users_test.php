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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use container_workspace\member\member;
use totara_webapi\phpunit\webapi_phpunit_helper;
use container_workspace\workspace;
use core_phpunit\testcase;

/**
 * @deprecated since Totara 16. The container_workspace_non_member_users query is
 * no longer used.
 *
 * @group container_workspace
 */
class container_workspace_webapi_non_member_users_testcase extends testcase {
    use webapi_phpunit_helper;


    public function test_public_member() {
        [$workspace, $member] = $this->prepare(false);
        $this->setUser($member);
        $this->assert_positive($workspace);
    }

    public function test_public_non_member() {
        [$workspace, $member, $nonmember] = $this->prepare(false);
        $this->setUser($nonmember);
        $this->assert_positive($workspace);
    }

    public function test_private_member() {
        [$workspace, $member] = $this->prepare(true);
        $this->setUser($member);
        $this->assert_positive($workspace);
    }

    public function test_private_non_member() {
        [$workspace, $member, $nonmember] = $this->prepare(true);
        $this->setUser($nonmember);
        $this->assert_negative($workspace);
    }

    public function test_private_non_member_admin() {
        [$workspace, $member] = $this->prepare(true);
        $this->setAdminUser();
        $this->assert_positive($workspace);
    }

    public function test_private_tenant_manager() {
        [$workspace, $member, $nonmember, $tenant] = $this->prepare(false, true);

        $tenant_context = context_tenant::instance($tenant->id);
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('totara/engage:manage', CAP_ALLOW, $roleid, $tenant_context);
        role_assign($roleid, $nonmember->id, $tenant_context);

        $this->setUser($nonmember);
        $this->assert_positive($workspace, true);
    }

    public function test_private_other_tenant_manager() {
        [$workspace, $member, $nonmember] = $this->prepare(false, true);

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_gen =  \totara_tenant\testing\generator::instance();
        $tenant2 = $tenant_gen->create_tenant();
        $tenant_gen->migrate_user_to_tenant($nonmember->id, $tenant2->id);
        $nonmember->tenantid = $tenant2->id;

        $tenant2_context = context_tenant::instance($tenant2->id);
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('totara/engage:manage', CAP_ALLOW, $roleid, $tenant2_context);
        role_assign($roleid, $nonmember->id, $tenant2_context);

        $this->setUser($nonmember);
        $this->assert_negative($workspace);
    }

    public function test_member_moved_to_other_tenant() {
        [$workspace, $member] = $this->prepare(false, true);

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_gen =  \totara_tenant\testing\generator::instance();
        $tenant2 = $tenant_gen->create_tenant();
        $tenant_gen->migrate_user_to_tenant($member->id, $tenant2->id);
        $member->tenantid = $tenant2->id;

        $this->setUser($member);
        $this->assert_negative($workspace);
    }

    public function test_user_from_other_tenant() {
        [$workspace, $member, $nonmember] = $this->prepare(false, true);

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_gen =  \totara_tenant\testing\generator::instance();
        $tenant2 = $tenant_gen->create_tenant();
        $tenant_gen->migrate_user_to_tenant($nonmember->id, $tenant2->id);
        $nonmember->tenantid = $tenant2->id;

        $this->setUser($member);
        $result = $this->execute_graphql_operation(
            'container_workspace_non_member_users',
            [
                'workspace_id' => $workspace->get_id(),
                'search_term' => ''
            ]
        );
        $this->assert_deprecated();

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);

        $this->assertArrayHasKey('users', $result->data);
        $this->assertArrayHasKey('cursor', $result->data);

        $this->assertCount(1, $result->data['users']);
        foreach ($result->data['users'] as $user) {
            $this->assertContains($user['fullname'], ['Stranger Stranger']);
            $this->assertNotContainsEquals($user['fullname'], ['Another Another', 'Admin User']);
        }
    }

    protected function prepare(bool $is_private = false, bool $is_tenants = false): array {
        // Public workspace
        $generator = $this->getDataGenerator();

        $owner = $generator->create_user(['firstname' => 'Owner', 'lastname' => 'Owner']);
        $member = $generator->create_user(['firstname' => 'Member', 'lastname' => 'Member']);
        $nonmember = $generator->create_user(['firstname' => 'Another', 'lastname' => 'Another']);
        $stranger = $generator->create_user(['firstname' => 'Stranger', 'lastname' => 'Stranger']);

        $tenant = null;
        if ($is_tenants) {
            /** @var \totara_tenant\testing\generator $tenant_generator */
            $tenant_gen = $generator->get_plugin_generator('totara_tenant');
            $tenant_gen->enable_tenants();

            $tenant = $tenant_gen->create_tenant();
            $tenant_gen->migrate_user_to_tenant($owner->id, $tenant->id);
            $owner->tenantid = $tenant->id;

            $tenant_gen->migrate_user_to_tenant($member->id, $tenant->id);
            $member->tenantid = $tenant->id;

            $tenant_gen->migrate_user_to_tenant($nonmember->id, $tenant->id);
            $nonmember->tenantid = $tenant->id;

            $tenant_gen->migrate_user_to_tenant($stranger->id, $tenant->id);
            $stranger->tenantid = $tenant->id;
        }

        $this->setUser($owner);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        if ($is_private) {
            $workspace = $workspace_generator->create_private_workspace();
        } else {
            $workspace = $workspace_generator->create_workspace();
        }

        member::added_to_workspace($workspace, $member->id);

        return [$workspace, $member, $nonmember, $tenant];
    }

    protected function assert_positive(workspace $workspace, bool $is_tenant = false) {
        $result = $this->execute_graphql_operation(
            'container_workspace_non_member_users',
            [
                'workspace_id' => $workspace->get_id(),
                'search_term' => ''
            ]
        );
        $this->assert_deprecated();

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);

        $this->assertArrayHasKey('users', $result->data);
        $this->assertArrayHasKey('cursor', $result->data);

        $count = ($is_tenant) ? 2 : 3; // Admin is not in tenancy.
        $this->assertCount($count, $result->data['users']);
        foreach ($result->data['users'] as $user) {
            $this->assertContains($user['fullname'], ['Admin User', 'Stranger Stranger', 'Another Another']);
        }

        // Test search
        $result = $this->execute_graphql_operation(
            'container_workspace_non_member_users',
            [
                'workspace_id' => $workspace->get_id(),
                'search_term' => 'St'
            ]
        );
        $this->assert_deprecated();
        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);

        $this->assertArrayHasKey('users', $result->data);
        $this->assertArrayHasKey('cursor', $result->data);

        $this->assertCount(1, $result->data['users']);
        $user = current($result->data['users']);
        $this->assertEquals('Stranger Stranger', $user['fullname']);
    }

    public function assert_negative(workspace $workspace) {
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage("You don't have permission to view this page.");

        // Test search
        $result = $this->resolve_graphql_query(
            'container_workspace_non_member_users',
            [
                'workspace_id' => $workspace->get_id(),
                'search_term' => 'St'
            ]
        );
    }

    private function assert_deprecated(): void {
        $messages = [
            'The non_member_users_cursor query is deprecated; use container_workspace_non_members instead',
            'Class non_member_query is deprecated; use container_workspace\data_providers\non_members instead',
            'Class non_member_loader is deprecated; use container_workspace\data_providers\non_members instead',
            'The container_workspace_non_member_users query is deprecated; use container_workspace_non_members instead',
            'Class non_member_query is deprecated; use container_workspace\data_providers\non_members instead',  // Repeated
            'Class non_member_loader is deprecated; use container_workspace\data_providers\non_members instead', // Repeated
        ];

        $this->assertDebuggingCalled($messages, DEBUG_DEVELOPER);
    }
}
