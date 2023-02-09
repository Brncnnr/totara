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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package container_workspace
 */

use container_workspace\member\member;
use container_workspace\testing\generator;
use core\collection;
use core\entity\tenant;
use core\entity\user;
use core_phpunit\testcase;
use totara_tenant\testing\generator as tenant_generator;

abstract class container_workspace_non_members_testcase extends testcase {
    /**
     * @inheritDoc
     */
    protected function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
    }

    /**
     * Generates test data.
     *
     * @param int $non_member_count other users to be generated.
     * @param int $member_count no of additional members in the workspace.
     * @param bool $private_workspace if true creates a private workspace.
     * @param bool $for_multitenancy if true creates users in a tenancy. Note if
     *        true, _does not add_ the admin user as the last member to the non
     *        member list.
     *
     * @return stdClass object with these fields:
     *         - int $id: generated workspace id
     *         - user $owner: the workspace owner
     *         - collection<user> $members: users who are workspace members.
     *           Note this also contains $owner.
     *         - user $member: one of the non owner users in $members.
     *         - collection<user> $non_members: users who are not members of the
     *           workspace. Note this will contain admin if $for_multitenancy is
     *           false.
     *         - user $non_member: one of the non admin users in $non_members.
     *         - user $admin: the admin user.
     *         - int $tenant_id: the tenant id this workspace belongs to or 0 if
     *           this is not a tenant workspace;
     */
    protected function create_workspace(
        int $non_member_count=5,
        int $member_count=3,
        bool $private_workspace=false,
        bool $for_multitenancy=false
    ): stdClass {
        $this->setAdminUser();

        $user_count = $member_count + $non_member_count + 1; // plus one owner.
        $users = $for_multitenancy
            ? $this->create_tenant_members($user_count)
            : $this->create_sys_users($user_count);

        $owner = array_shift($users);
        $this->setUser($owner);

        $generator = generator::instance();
        if ($private_workspace) {
            $workspace = $generator->create_private_workspace();
        } else {
            $workspace = $generator->create_workspace();
        }

        $members = collection::new(array_slice($users, 0, $member_count));
        member::added_to_workspace_in_bulk($workspace, $members->pluck('id'), false);
        $members->append($owner);

        $non_members = collection::new(
            array_slice($users, $member_count, $non_member_count)
        );

        $admin = new user(get_admin());
        if (!$for_multitenancy) {
            $non_members->append($admin);
        }

        return (object) [
            'id' => $workspace->id,
            'owner' => $owner,
            'members' => $members,
            'member' => $members->first(),
            'non_members' => $non_members,
            'non_member' => $non_members->first(),
            'admin' => $admin,
            'tenant_id' => $for_multitenancy ? $owner->tenantid : 0
        ];
    }

    /**
     * Generates 'normal' users.
     *
     * @param int $count no of users to generate.
     *
     * @return user[] a list of user_entity objects.
     */
    protected function create_sys_users(int $count = 10): array {
        $generator = $this->getDataGenerator();

        return collection::new(range(0, $count - 1))
            ->map_to(function (int $i) use ($generator): user {
                $user = $generator->create_user([
                    'firstname' => 'Test',
                    'lastname' => sprintf('User #%02d', $i)
                ]);

                return new user($user);
            })
            ->all();
    }

    /**
     * Generates members in a tenancy.
     *
     * @param int $count no of members to generate.
     *
     * @return user[] a list of user_entity objects.
     */
    protected function create_tenant_members(int $count = 10): array {
        $tenant_generator = tenant_generator::instance();
        $tenant_generator->enable_tenants();
        $tenant = new tenant($tenant_generator->create_tenant());

        $generator = $this->getDataGenerator();

        return collection::new(range(0, $count - 1))
            ->map_to(function (int $i) use ($generator, $tenant): user {
                $user = $generator->create_user([
                    'firstname' => 'Test',
                    'lastname' => sprintf('User #%02d', $i),
                    'tenantid' => $tenant->id
                ]);

                return new user($user);
            })
            ->all();
    }

    /**
     * Generates participants in a tenancy.
     *
     * @param int $tenant_id parent tenant.
     * @param int $count no of participants to generate.
     *
     * @return user[] a list of user_entity objects.
     */
    protected function create_tenant_participants(
        int $tenant_id,
        int $count = 10
    ): array {
        $participants = $this->create_sys_users($count);

        $tenant_generator = tenant_generator::instance();
        foreach ($participants as $participant) {
            $tenant_generator->set_user_participation($participant->id, [$tenant_id]);
        }

        return $participants;
    }

    /**
     * Appoints a user to become a tenant manager.
     *
     * @param int $tenant_id parent tenant.
     * @param user $user the user appointed as the tenant manager.
     *
     * @return user the appointed user.
     */
    protected function create_tenant_mgr(
        int $tenant_id,
        user $user
    ): user {
        $generator = $this->getDataGenerator();
        $role_id = $generator->create_role();

        $tenant_context = context_tenant::instance($tenant_id);
        assign_capability('totara/engage:manage', CAP_ALLOW, $role_id, $tenant_context);
        role_assign($role_id, $user->id, $tenant_context);

        return $user;
    }
}