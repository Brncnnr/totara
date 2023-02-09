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

use container_course\course;
use container_workspace\discussion\discussion;
use container_workspace\member\member;
use container_workspace\watcher\editor_weka_watcher;
use container_workspace\workspace;
use editor_weka\hook\search_users_by_pattern;

class container_workspace_editor_weka_search_users_testcase extends advanced_testcase {
    /**
     * @param int $number_of_users
     * @return stdClass[]
     */
    private function create_users(int $number_of_users = 2): array {
        $generator = $this->getDataGenerator();
        $users = [];

        for ($i = 0; $i < $number_of_users; $i++) {
            $users[] = $generator->create_user([
                'firstname' => uniqid('user_'),
                'lastname' => uniqid('user_')
            ]);
        }

        return $users;
    }

    /**
     * @return \container_workspace\testing\generator
     */
    private function get_workspace_generator(): \container_workspace\testing\generator {
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        return $workspace_generator;
    }

    /**
     * @return void
     */
    public function test_search_for_users_with_invalid_context(): void {
        [$user_one] = $this->create_users(1);
        $hook = search_users_by_pattern::create(
            workspace::get_type(),
            discussion::AREA,
            "",
            context_system::instance()->id,
            $user_one->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertDebuggingCalled("Context level is not support by container_workspace");

        self::assertFalse($hook->is_db_run());
    }

    /**
     * @return void
     */
    public function test_search_for_users_with_multitenancy(): void {
        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        $system_user = $generator->create_user();
        $tenant_participant = $generator->create_user();

        $tenant_generator->set_user_participation($tenant_participant->id, [$tenant_one->id, $tenant_two->id]);

        $user1_tenant1 = $generator->create_user(['tenantid' => $tenant_one->id]);
        $user2_tenant1 = $generator->create_user(['tenantid' => $tenant_one->id]);
        $user1_tenant2 = $generator->create_user(['tenantid' => $tenant_two->id]);
        $user2_tenant2 = $generator->create_user(['tenantid' => $tenant_two->id]);

        $this->setUser($system_user);
        $system_workspace = $workspace_generator->create_workspace();

        $miscellanous_context = context_coursecat::instance(course::get_default_category_id());

        $this->setUser($user1_tenant1);
        $workspace1 = $workspace_generator->create_workspace();
        $workspace1_hidden = $workspace_generator->create_hidden_workspace();
        $workspace_generator->add_member($workspace1_hidden, $user2_tenant1->id);

        $this->setUser($user1_tenant2);
        $workspace2 = $workspace_generator->create_workspace();

        $this->setUser($system_user);

        // A system users searching in the system workspace should find all existing users
        $hook = search_users_by_pattern::create(
            workspace::get_type(),
            discussion::AREA,
            "",
            context_course::instance($system_workspace->id)->id,
            $system_user->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertTrue($hook->is_db_run());
        $this->assertCount(7, $hook->get_users());
        $this->assertEqualsCanonicalizing(
            [
                get_admin()->id,
                $system_user->id,
                $tenant_participant->id,
                $user1_tenant1->id,
                $user2_tenant1->id,
                $user1_tenant2->id,
                $user2_tenant2->id,
            ],
            array_column($hook->get_users(), 'id')
        );

        // System user is not a member of the workspace so should not find anyone
        $hook = search_users_by_pattern::create(
            workspace::get_type(),
            discussion::AREA,
            "",
            context_course::instance($workspace1->id)->id,
            $system_user->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertTrue($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());

        $this->setUser($tenant_participant);

        // A tenant participant who is not a member of the workspace should not find anything
        $hook = search_users_by_pattern::create(
            workspace::get_type(),
            discussion::AREA,
            "",
            context_course::instance($workspace1->id)->id,
            $tenant_participant->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertTrue($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());

        $workspace_generator->add_member($workspace1, $tenant_participant->id, $user1_tenant1->id);

        // If the participant is a member they should only see other tenant users
        $hook = search_users_by_pattern::create(
            workspace::get_type(),
            discussion::AREA,
            "",
            context_course::instance($workspace1->id)->id,
            $tenant_participant->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertTrue($hook->is_db_run());
        $this->assertCount(3, $hook->get_users());
        $this->assertEqualsCanonicalizing(
            [
                $tenant_participant->id,
                $user1_tenant1->id,
                $user2_tenant1->id,
            ],
            array_column($hook->get_users(), 'id')
        );

        // Remove that user again from the workspace
        member::from_user($tenant_participant->id, $workspace1->id)->delete($user1_tenant1->id);

        // Not a member on system workspace, so should not see anyone
        $hook = search_users_by_pattern::create(
            workspace::get_type(),
            discussion::AREA,
            "",
            context_course::instance($system_workspace->id)->id,
            $tenant_participant->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertTrue($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());

        $workspace_generator->add_member($system_workspace, $tenant_participant->id, $system_user->id);

        // A system user (even participant) being a member in a system workspace should see all users
        $hook = search_users_by_pattern::create(
            workspace::get_type(),
            discussion::AREA,
            "",
            context_course::instance($system_workspace->id)->id,
            $tenant_participant->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertTrue($hook->is_db_run());
        $this->assertCount(7, $hook->get_users());
        $this->assertEqualsCanonicalizing(
            [
                get_admin()->id,
                $system_user->id,
                $tenant_participant->id,
                $user1_tenant1->id,
                $user2_tenant1->id,
                $user1_tenant2->id,
                $user2_tenant2->id,
            ],
            array_column($hook->get_users(), 'id')
        );

        $this->setUser($user2_tenant1);

        // A tenant user who is not a member should not see ayone
        $hook = search_users_by_pattern::create(
            workspace::get_type(),
            discussion::AREA,
            "",
            context_course::instance($workspace1->id)->id,
            $user2_tenant1->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertTrue($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());

        $workspace_generator->add_member($workspace1, $user2_tenant1->id, $user1_tenant1->id);

        // If he is a member he should only see the tenant users
        $hook = search_users_by_pattern::create(
            workspace::get_type(),
            discussion::AREA,
            "",
            context_course::instance($workspace1->id)->id,
            $user2_tenant1->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertTrue($hook->is_db_run());
        $this->assertCount(3, $hook->get_users());
        $this->assertEqualsCanonicalizing(
            [
                $tenant_participant->id,
                $user1_tenant1->id,
                $user2_tenant1->id,
            ],
            array_column($hook->get_users(), 'id')
        );

        // If he is a member of a hidden workspace he should only see the workspace members
        $hook = search_users_by_pattern::create(
            workspace::get_type(),
            discussion::AREA,
            "",
            context_course::instance($workspace1_hidden->id)->id,
            $user2_tenant1->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertTrue($hook->is_db_run());
        $this->assertCount(2, $hook->get_users());
        $this->assertEqualsCanonicalizing(
            [
                $user1_tenant1->id,
                $user2_tenant1->id,
            ],
            array_column($hook->get_users(), 'id')
        );

        // He should not be able to see any users with a workspace in a different tenant
        $hook = search_users_by_pattern::create(
            workspace::get_type(),
            discussion::AREA,
            "",
            context_course::instance($workspace2->id)->id,
            $user2_tenant1->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertTrue($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());

        // He should not be able to see any users with on a system workspace he is not a member of
        $hook = search_users_by_pattern::create(
            workspace::get_type(),
            discussion::AREA,
            "",
            context_course::instance($system_workspace->id)->id,
            $user2_tenant1->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertTrue($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());

        $workspace_generator->add_member($system_workspace, $user2_tenant1->id, $system_user->id);

        // As a member in a system workspace a tenant member should only see other workspace members
        $hook = search_users_by_pattern::create(
            workspace::get_type(),
            discussion::AREA,
            "",
            context_course::instance($system_workspace->id)->id,
            $user2_tenant1->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertTrue($hook->is_db_run());
        $this->assertCount(3, $hook->get_users());
        $this->assertEqualsCanonicalizing(
            [
                $system_user->id,
                $tenant_participant->id,
                $user2_tenant1->id,
            ],
            array_column($hook->get_users(), 'id')
        );

        // In a course category we make sure you only find users in the same tenant
        $hook = search_users_by_pattern::create(
            workspace::get_type(),
            discussion::AREA,
            "",
            context_coursecat::instance($tenant_one->categoryid)->id,
            $user2_tenant1->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertTrue($hook->is_db_run());
        $this->assertCount(3, $hook->get_users());
        $this->assertEqualsCanonicalizing(
            [
                $tenant_participant->id,
                $user1_tenant1->id,
                $user2_tenant1->id,
            ],
            array_column($hook->get_users(), 'id')
        );

        // It will fail though if we use the system context
        $hook = search_users_by_pattern::create(
            workspace::get_type(),
            discussion::AREA,
            "",
            context_system::instance()->id,
            $user2_tenant1->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertFalse($hook->is_db_run());
        $this->assertEmpty($hook->get_users());
        $this->assertDebuggingCalled('Context level is not support by container_workspace');

        // It will fail though if we use any context which is outside of the given tenant
        $hook = search_users_by_pattern::create(
            workspace::get_type(),
            discussion::AREA,
            "",
            $miscellanous_context->id,
            $user2_tenant1->id
        );

        try {
            editor_weka_watcher::on_search_users($hook);
            $this->fail('Expected an exception');
        } catch (coding_exception $exception) {
            $this->assertStringContainsString('User is not allowed to load users for the given context', $exception->getMessage());
        }
    }

    /**
     * @return void
     */
    public function test_search_for_users_with_multitenancy_with_isolation(): void {
        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        set_config('tenantsisolated', 1);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        $system_user = $generator->create_user();
        $tenant_participant = $generator->create_user();

        $tenant_generator->set_user_participation($tenant_participant->id, [$tenant_one->id, $tenant_two->id]);

        $user1_tenant1 = $generator->create_user(['tenantid' => $tenant_one->id]);
        $user2_tenant1 = $generator->create_user(['tenantid' => $tenant_one->id]);
        $user1_tenant2 = $generator->create_user(['tenantid' => $tenant_two->id]);
        $user2_tenant2 = $generator->create_user(['tenantid' => $tenant_two->id]);

        $this->setUser($system_user);
        $system_workspace = $workspace_generator->create_workspace();

        $miscellanous_context = context_coursecat::instance(course::get_default_category_id());

        $this->setUser($user1_tenant1);
        $workspace1 = $workspace_generator->create_workspace();
        $workspace1_hidden = $workspace_generator->create_hidden_workspace();
        $workspace_generator->add_member($workspace1_hidden, $user2_tenant1->id);

        $this->setUser($user1_tenant1);
        $workspace2 = $workspace_generator->create_workspace();

        $this->setUser($system_user);

        // A system users searching in the system workspace should find all existing users
        $hook = search_users_by_pattern::create(
            workspace::get_type(),
            discussion::AREA,
            "",
            context_course::instance($system_workspace->id)->id,
            $system_user->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertTrue($hook->is_db_run());
        $this->assertCount(3, $hook->get_users());
        $this->assertEqualsCanonicalizing(
            [
                get_admin()->id,
                $system_user->id,
                $tenant_participant->id,
            ],
            array_column($hook->get_users(), 'id')
        );

        // System user is not a member of the workspace so should not find anyone
        $hook = search_users_by_pattern::create(
            workspace::get_type(),
            discussion::AREA,
            "",
            context_course::instance($workspace1->id)->id,
            $system_user->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertTrue($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());

        $this->setUser($tenant_participant);

        // A tenant participant who is not a member of the workspace should not find anything
        $hook = search_users_by_pattern::create(
            workspace::get_type(),
            discussion::AREA,
            "",
            context_course::instance($workspace1->id)->id,
            $tenant_participant->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertTrue($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());

        $workspace_generator->add_member($workspace1, $tenant_participant->id, $user1_tenant1->id);

        // If the participant is a member they should only see other tenant users
        $hook = search_users_by_pattern::create(
            workspace::get_type(),
            discussion::AREA,
            "",
            context_course::instance($workspace1->id)->id,
            $tenant_participant->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertTrue($hook->is_db_run());
        $this->assertCount(3, $hook->get_users());
        $this->assertEqualsCanonicalizing(
            [
                $tenant_participant->id,
                $user1_tenant1->id,
                $user2_tenant1->id,
            ],
            array_column($hook->get_users(), 'id')
        );

        // Remove that user again from the workspace
        member::from_user($tenant_participant->id, $workspace1->id)->delete($user1_tenant1->id);

        // Not a member on system workspace, so should not see anyone
        $hook = search_users_by_pattern::create(
            workspace::get_type(),
            discussion::AREA,
            "",
            context_course::instance($system_workspace->id)->id,
            $tenant_participant->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertTrue($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());

        $workspace_generator->add_member($system_workspace, $tenant_participant->id, $system_user->id);

        // A system user (even participant) being a member in a system workspace should see all users
        $hook = search_users_by_pattern::create(
            workspace::get_type(),
            discussion::AREA,
            "",
            context_course::instance($system_workspace->id)->id,
            $tenant_participant->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertTrue($hook->is_db_run());
        $this->assertCount(3, $hook->get_users());
        $this->assertEqualsCanonicalizing(
            [
                get_admin()->id,
                $system_user->id,
                $tenant_participant->id,
            ],
            array_column($hook->get_users(), 'id')
        );

        $this->setUser($user2_tenant1);

        // A tenant user who is not a member should not see ayone
        $hook = search_users_by_pattern::create(
            workspace::get_type(),
            discussion::AREA,
            "",
            context_course::instance($workspace1->id)->id,
            $user2_tenant1->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertTrue($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());

        $workspace_generator->add_member($workspace1, $user2_tenant1->id, $user1_tenant1->id);

        // If he is a member he should only see the tenant users
        $hook = search_users_by_pattern::create(
            workspace::get_type(),
            discussion::AREA,
            "",
            context_course::instance($workspace1->id)->id,
            $user2_tenant1->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertTrue($hook->is_db_run());
        $this->assertCount(3, $hook->get_users());
        $this->assertEqualsCanonicalizing(
            [
                $tenant_participant->id,
                $user1_tenant1->id,
                $user2_tenant1->id,
            ],
            array_column($hook->get_users(), 'id')
        );

        // If he is a member of a hidden workspace he should only see the workspace members
        $hook = search_users_by_pattern::create(
            workspace::get_type(),
            discussion::AREA,
            "",
            context_course::instance($workspace1_hidden->id)->id,
            $user2_tenant1->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertTrue($hook->is_db_run());
        $this->assertCount(2, $hook->get_users());
        $this->assertEqualsCanonicalizing(
            [
                $user1_tenant1->id,
                $user2_tenant1->id,
            ],
            array_column($hook->get_users(), 'id')
        );

        // He should not be able to see any users with a workspace in a different tenant
        $hook = search_users_by_pattern::create(
            workspace::get_type(),
            discussion::AREA,
            "",
            context_course::instance($workspace2->id)->id,
            $user2_tenant1->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertTrue($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());

        // He should not be able to see any users with on a system workspace he is not a member of
        $hook = search_users_by_pattern::create(
            workspace::get_type(),
            discussion::AREA,
            "",
            context_course::instance($system_workspace->id)->id,
            $user2_tenant1->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertTrue($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());

        // In a course category we make sure you only find users in the same tenant
        $hook = search_users_by_pattern::create(
            workspace::get_type(),
            discussion::AREA,
            "",
            context_coursecat::instance($tenant_one->categoryid)->id,
            $user2_tenant1->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertTrue($hook->is_db_run());
        $this->assertCount(3, $hook->get_users());
        $this->assertEqualsCanonicalizing(
            [
                $tenant_participant->id,
                $user1_tenant1->id,
                $user2_tenant1->id,
            ],
            array_column($hook->get_users(), 'id')
        );

        // It will fail though if we use the system context
        $hook = search_users_by_pattern::create(
            workspace::get_type(),
            discussion::AREA,
            "",
            context_system::instance()->id,
            $user2_tenant1->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertFalse($hook->is_db_run());
        $this->assertEmpty($hook->get_users());
        $this->assertDebuggingCalled('Context level is not support by container_workspace');

        // It will fail though if we use any context which is outside of the given tenant
        $hook = search_users_by_pattern::create(
            workspace::get_type(),
            discussion::AREA,
            "",
            $miscellanous_context->id,
            $user2_tenant1->id
        );

        try {
            editor_weka_watcher::on_search_users($hook);
            $this->fail('Expected an exception');
        } catch (coding_exception $exception) {
            $this->assertStringContainsString('User is not allowed to load users for the given context', $exception->getMessage());
        }
    }

    /**
     * @return void
     */
    public function test_search_for_users_in_hidden_workspace_only(): void {
        [$user_one, $user_two, $user_three] = $this->create_users(3);
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($user_one);
        $workspace = $workspace_generator->create_hidden_workspace();

        $workspace_generator->add_member($workspace, $user_two->id, $user_one->id);
        $hook = search_users_by_pattern::create(
            workspace::get_type(),
            discussion::AREA,
            "",
            $workspace->get_context()->id,
            $user_one->id
        );

        self::assertEmpty($hook->get_users());
        self::assertFalse($hook->is_db_run());

        editor_weka_watcher::on_search_users($hook);
        self::assertTrue($hook->is_db_run());

        $users = $hook->get_users();
        self::assertNotEmpty($users);
        self::assertCount(2, $users);

        foreach ($users as $user) {
            self::assertContainsEquals($user->id, [$user_one->id, $user_two->id]);
            self::assertNotEquals($user_three->id, $user->id);
        }
    }

    /**
     * @return void
     */
    public function test_search_for_non_member_users_in_public_workspace(): void {
        [$user_one, $user_two] = $this->create_users();
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($user_one);
        $workspace = $workspace_generator->create_workspace();

        $hook = search_users_by_pattern::create(
            workspace::get_type(),
            discussion::AREA,
            $user_two->firstname,
            $workspace->get_context()->id,
            $user_one->id
        );

        editor_weka_watcher::on_search_users($hook);
        self::assertTrue($hook->is_db_run());

        $users = $hook->get_users();
        self::assertNotEmpty($users);
        self::assertCount(1, $users);

        $fetched_user = reset($users);
        self::assertEquals($user_two->id, $fetched_user->id);
    }

    /**
     * @return void
     */
    public function test_search_for_non_member_users_in_private_workspace(): void {
        [$user_one, $user_two] = $this->create_users();
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($user_one);
        $workspace = $workspace_generator->create_private_workspace();

        $hook = search_users_by_pattern::create(
            workspace::get_type(),
            discussion::AREA,
            $user_two->firstname,
            $workspace->get_context()->id,
            $user_one->id
        );

        editor_weka_watcher::on_search_users($hook);
        self::assertTrue($hook->is_db_run());

        $users = $hook->get_users();
        self::assertNotEmpty($users);
        self::assertCount(1, $users);

        $fetched_user = reset($users);
        self::assertEquals($user_two->id, $fetched_user->id);
    }
}