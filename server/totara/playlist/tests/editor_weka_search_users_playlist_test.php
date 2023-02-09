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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_playlist
 */

defined('MOODLE_INTERNAL') || die();

use container_course\course;
use editor_weka\hook\search_users_by_pattern;
use totara_playlist\playlist;
use totara_playlist\watcher\editor_weka_watcher;

class totara_playlist_editor_weka_search_users_playlist_testcase extends advanced_testcase {

    /**
     * @return void
     */
    public function test_search_for_users() {
        $generator = $this->getDataGenerator();

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();

        $this->setUser($user1);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');

        $public_playlist = $playlist_generator->create_public_playlist(['userid' => $user1->id]);
        $restricted_playlist = $playlist_generator->create_restricted_playlist(['userid' => $user1->id]);

        // Without the correct area we ignore this
        $hook = search_users_by_pattern::create(
            playlist::get_resource_type(),
            'idontexist',
            "",
            context_user::instance($user1->id)->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertFalse($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());

        $hook = search_users_by_pattern::create(
            playlist::get_resource_type(),
            playlist::SUMMARY_AREA,
            "",
            context_user::instance($user1->id)->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertTrue($hook->is_db_run());
        $this->assertCount(4, $hook->get_users());
        $this->assertEqualsCanonicalizing(
            [
                get_admin()->id,
                $user1->id,
                $user2->id,
                $user3->id,
            ],
            array_column($hook->get_users(), 'id')
        );

        // Try the system context
        $hook = search_users_by_pattern::create(
            playlist::get_resource_type(),
            playlist::SUMMARY_AREA,
            "",
            context_system::instance()->id
        );

        // Now try another user
        $this->setUser($user2);

        $hook = search_users_by_pattern::create(
            playlist::get_resource_type(),
            playlist::SUMMARY_AREA,
            "",
            context_user::instance($user2->id)->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertTrue($hook->is_db_run());
        $this->assertCount(4, $hook->get_users());
        $this->assertEqualsCanonicalizing(
            [
                get_admin()->id,
                $user1->id,
                $user2->id,
                $user3->id,
            ],
            array_column($hook->get_users(), 'id')
        );

        $hook = search_users_by_pattern::create(
            playlist::get_resource_type(),
            playlist::SUMMARY_AREA,
            "",
            context_user::instance($user2->id)->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertTrue($hook->is_db_run());
        $this->assertCount(4, $hook->get_users());
        $this->assertEqualsCanonicalizing(
            [
                get_admin()->id,
                $user1->id,
                $user2->id,
                $user3->id,
            ],
            array_column($hook->get_users(), 'id')
        );

        // Try the system context, should be ignored for content
        $hook = search_users_by_pattern::create(
            playlist::get_resource_type(),
            playlist::SUMMARY_AREA,
            "",
            context_system::instance()->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertFalse($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());
    }

    /**
     * @return void
     */
    public function test_search_for_users_with_multitenancy(): void {
        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

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
        $miscellanous_context = context_coursecat::instance(course::get_default_category_id());

        $this->setUser($system_user);

        // A system users searching in the system playlist should find all existing users
        $hook = search_users_by_pattern::create(
            playlist::get_resource_type(),
            playlist::SUMMARY_AREA,
            "",
            context_user::instance($system_user->id)->id
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

        $this->setUser($tenant_participant);

        // A participant should see all users on a system playlist
        $hook = search_users_by_pattern::create(
            playlist::get_resource_type(),
            playlist::SUMMARY_AREA,
            "",
            context_user::instance($system_user->id)->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertFalse($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());

        // As participant should only see other tenant users on a tenant playlist
        $hook = search_users_by_pattern::create(
            playlist::get_resource_type(),
            playlist::SUMMARY_AREA,
            "",
            context_user::instance($tenant_participant->id)->id
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

        // If he is a member he should only see the tenant users
        $hook = search_users_by_pattern::create(
            playlist::get_resource_type(),
            playlist::SUMMARY_AREA,
            "",
            context_user::instance($user2_tenant1->id)->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertTrue($hook->is_db_run());
        $this->assertCount(5, $hook->get_users());
        $this->assertEqualsCanonicalizing(
            [
                $system_user->id,
                get_admin()->id,
                $tenant_participant->id,
                $user1_tenant1->id,
                $user2_tenant1->id,
            ],
            array_column($hook->get_users(), 'id')
        );

        // He should not be able to see any users with a workspace in a different tenant
        $hook = search_users_by_pattern::create(
            playlist::get_resource_type(),
            playlist::SUMMARY_AREA,
            "",
            context_user::instance($user1_tenant2->id)->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertFalse($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());

        // He should only see other tenant users
        $hook = search_users_by_pattern::create(
            playlist::get_resource_type(),
            playlist::SUMMARY_AREA,
            "",
            context_user::instance($user2_tenant1->id)->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertCount(5, $hook->get_users());
        $this->assertEqualsCanonicalizing(
            [
                get_admin()->id,
                $system_user->id,
                $tenant_participant->id,
                $user1_tenant1->id,
                $user2_tenant1->id,
            ],
            array_column($hook->get_users(), 'id')
        );

        // Even passing a different context should not matter
        $hook = search_users_by_pattern::create(
            playlist::get_resource_type(),
            playlist::SUMMARY_AREA,
            "",
            context_system::instance()->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertFalse($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());

        // Should always be the users context
        $hook = search_users_by_pattern::create(
            playlist::get_resource_type(),
            playlist::SUMMARY_AREA,
            "",
            context_coursecat::instance($tenant_one->categoryid)->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertFalse($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());
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
        $miscellanous_context = context_coursecat::instance(course::get_default_category_id());

        $this->setUser($system_user);

        // A system users searching in the system playlist should find all existing users
        $hook = search_users_by_pattern::create(
            playlist::get_resource_type(),
            playlist::SUMMARY_AREA,
            "",
            context_user::instance($system_user->id)->id
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

        $this->setUser($tenant_participant);

        // A participant should see all users on a system playlist
        $hook = search_users_by_pattern::create(
            playlist::get_resource_type(),
            playlist::SUMMARY_AREA,
            "",
            context_user::instance($system_user->id)->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertFalse($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());

        // As participant should only see other tenant users on a tenant playlist
        $hook = search_users_by_pattern::create(
            playlist::get_resource_type(),
            playlist::SUMMARY_AREA,
            "",
            context_user::instance($tenant_participant->id)->id
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

        // If he is a member he should only see the tenant users
        $hook = search_users_by_pattern::create(
            playlist::get_resource_type(),
            playlist::SUMMARY_AREA,
            "",
            context_user::instance($user2_tenant1->id)->id
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

        // He should not be able to see any users with a workspace in a different tenant
        $hook = search_users_by_pattern::create(
            playlist::get_resource_type(),
            playlist::SUMMARY_AREA,
            "",
            context_user::instance($user1_tenant2->id)->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertFalse($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());

        // He should only see other tenant users
        $hook = search_users_by_pattern::create(
            playlist::get_resource_type(),
            playlist::SUMMARY_AREA,
            "",
            context_user::instance($user2_tenant1->id)->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertCount(3, $hook->get_users());
        $this->assertEqualsCanonicalizing(
            [
                $tenant_participant->id,
                $user1_tenant1->id,
                $user2_tenant1->id,
            ],
            array_column($hook->get_users(), 'id')
        );

        // Even passing a different context should not matter
        $hook = search_users_by_pattern::create(
            playlist::get_resource_type(),
            playlist::SUMMARY_AREA,
            "",
            context_system::instance()->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertFalse($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());

        // Should always be the users context
        $hook = search_users_by_pattern::create(
            playlist::get_resource_type(),
            playlist::SUMMARY_AREA,
            "",
            context_coursecat::instance($tenant_one->categoryid)->id
        );

        editor_weka_watcher::on_search_users($hook);
        $this->assertFalse($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());
    }

}