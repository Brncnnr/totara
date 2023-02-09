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
use totara_comment\comment;
use totara_core\advanced_feature as advanced_feature;
use totara_playlist\watcher\editor_weka_watcher;

class totara_playlist_editor_weka_search_users_comment_testcase extends advanced_testcase {

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

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment1 = $comment_generator->create_comment(
            $public_playlist->get_id(),
            $public_playlist::get_resource_type(),
            'comment'
        );

        $reply1 = $comment_generator->create_reply($comment1->get_id());

        $comment2 = $comment_generator->create_comment(
            $restricted_playlist->get_id(),
            $restricted_playlist::get_resource_type(),
            'comment'
        );

        // Without the correct component or area we ignore this
        $hook = search_users_by_pattern::create(
            'idontexist',
            comment::COMMENT_AREA,
            "",
            context_user::instance($user1->id)->id
        );
        $hook->set_instance_id($comment1->get_id());

        editor_weka_watcher::on_search_users($hook);
        $this->assertFalse($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());

        $hook = search_users_by_pattern::create(
            comment::get_component_name(),
            'idontexist',
            "",
            context_user::instance($user1->id)->id
        );
        $hook->set_instance_id($comment1->get_id());

        editor_weka_watcher::on_search_users($hook);
        $this->assertFalse($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());

        // Now try one with a non-existent comment
        $hook = search_users_by_pattern::create(
            comment::get_component_name(),
            comment::COMMENT_AREA,
            "",
            context_user::instance($user1->id)->id
        );
        $hook->set_instance_id(999);

        editor_weka_watcher::on_search_users($hook);
        $this->assertFalse($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());

        $hook = search_users_by_pattern::create(
            comment::get_component_name(),
            comment::REPLY_AREA,
            "",
            context_user::instance($user1->id)->id
        );
        $hook->set_instance_id(999);

        editor_weka_watcher::on_search_users($hook);
        $this->assertFalse($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());

        // Now try one which exists
        $hook = search_users_by_pattern::create(
            comment::get_component_name(),
            comment::COMMENT_AREA,
            "",
            context_user::instance($user1->id)->id
        );
        $hook->set_instance_id($comment1->get_id());

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

        // Now try the reply area
        $hook = search_users_by_pattern::create(
            comment::get_component_name(),
            comment::REPLY_AREA,
            "",
            context_user::instance($user1->id)->id
        );
        $hook->set_instance_id($comment1->get_id());

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
            comment::get_component_name(),
            comment::REPLY_AREA,
            "",
            context_system::instance()->id
        );
        $hook->set_instance_id($comment1->get_id());

        // We should ignore it
        editor_weka_watcher::on_search_users($hook);
        $this->assertFalse($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());

        // Try a different context than the playlist's one
        $hook = search_users_by_pattern::create(
            comment::get_component_name(),
            comment::REPLY_AREA,
            "",
            context_user::instance($user2->id)->id
        );
        $hook->set_instance_id($comment1->get_id());

        // We should ignore it
        editor_weka_watcher::on_search_users($hook);
        $this->assertFalse($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());

        // Now try another user
        $this->setUser($user2);

        $hook = search_users_by_pattern::create(
            comment::get_component_name(),
            comment::REPLY_AREA,
            "",
            context_user::instance($user1->id)->id
        );
        $hook->set_instance_id($comment1->get_id());

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

        // For a private one we should not get a result
        $hook = search_users_by_pattern::create(
            comment::get_component_name(),
            comment::REPLY_AREA,
            "",
            context_user::instance($user1->id)->id
        );
        $hook->set_instance_id($comment2->get_id());

        editor_weka_watcher::on_search_users($hook);
        $this->assertFalse($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());

        // Try a different context
        $hook = search_users_by_pattern::create(
            comment::get_component_name(),
            comment::REPLY_AREA,
            "",
            context_user::instance($user2->id)->id
        );
        $hook->set_instance_id($comment1->get_id());

        // We should ignore it
        editor_weka_watcher::on_search_users($hook);
        $this->assertFalse($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());

        // Disable the feature, should now return empty result
        advanced_feature::disable('engage_resources');

        $hook = search_users_by_pattern::create(
            comment::get_component_name(),
            comment::REPLY_AREA,
            "",
            context_user::instance($user1->id)->id
        );
        $hook->set_instance_id($comment1->get_id());

        editor_weka_watcher::on_search_users($hook);
        $this->assertFalse($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());
    }

    /**
     * @return void
     */
    public function test_search_for_users_with_multitenancy(): void {
        $generator = $this->getDataGenerator();

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');

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
        $system_playlist = $playlist_generator->create_public_playlist(['userid' => $system_user->id]);

        $system_comment = $comment_generator->create_comment(
            $system_playlist->get_id(),
            $system_playlist::get_resource_type(),
            'comment'
        );
        $system_comment_reply = $comment_generator->create_reply($system_comment->get_id());

        $miscellanous_context = context_coursecat::instance(course::get_default_category_id());

        $this->setUser($user1_tenant1);
        $tenant1_playlist = $playlist_generator->create_public_playlist(['userid' => $user1_tenant1->id]);

        $tenant1_playlist_comment1 = $comment_generator->create_comment(
            $tenant1_playlist->get_id(),
            $tenant1_playlist::get_resource_type(),
            'comment'
        );
        $tenant1_playlist_comment1_reply = $comment_generator->create_reply($tenant1_playlist_comment1->get_id());

        $this->setUser($user2_tenant1);
        $tenant1_playlist_comment2 = $comment_generator->create_comment(
            $tenant1_playlist->get_id(),
            $tenant1_playlist::get_resource_type(),
            'comment'
        );
        $tenant1_playlist_comment1_reply1 = $comment_generator->create_reply($tenant1_playlist_comment1->get_id());
        $tenant1_playlist_comment2_reply1 = $comment_generator->create_reply($tenant1_playlist_comment2->get_id());

        $this->setUser($user1_tenant2);
        $tenant2_playlist = $playlist_generator->create_public_playlist(['userid' => $user1_tenant2->id]);

        $tenant2_playlist_comment1 = $comment_generator->create_comment(
            $tenant2_playlist->get_id(),
            $tenant2_playlist::get_resource_type(),
            'comment'
        );
        $tenant2_playlist_comment1_reply = $comment_generator->create_reply($tenant2_playlist_comment1->get_id());

        $this->setUser($system_user);

        // A system users searching in the system playlist should find all existing users
        $hook = search_users_by_pattern::create(
            comment::get_component_name(),
            comment::COMMENT_AREA,
            "",
            context_user::instance($system_user->id)->id
        );
        $hook->set_instance_id($system_comment->get_id());

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
            comment::get_component_name(),
            comment::COMMENT_AREA,
            "",
            context_user::instance($system_user->id)->id
        );
        $hook->set_instance_id($system_comment->get_id());

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

        // As participant should only see other tenant users on a tenant playlist
        $hook = search_users_by_pattern::create(
            comment::get_component_name(),
            comment::COMMENT_AREA,
            "",
            context_user::instance($user1_tenant1->id)->id
        );
        $hook->set_instance_id($tenant1_playlist_comment1->get_id());

        editor_weka_watcher::on_search_users($hook);
        $this->assertTrue($hook->is_db_run());
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

        $this->setUser($user2_tenant1);

        // If the user is from a different tenant he should not get a result
        $hook = search_users_by_pattern::create(
            comment::get_component_name(),
            comment::COMMENT_AREA,
            "",
            context_user::instance($user1_tenant2->id)->id
        );
        $hook->set_instance_id($tenant2_playlist_comment1->get_id());

        editor_weka_watcher::on_search_users($hook);
        $this->assertFalse($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());

        // His own tenant is fine
        $hook = search_users_by_pattern::create(
            comment::get_component_name(),
            comment::COMMENT_AREA,
            "",
            context_user::instance($user1_tenant1->id)->id
        );
        $hook->set_instance_id($tenant1_playlist_comment1->get_id());

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

        // Also a system users playlist would work but in this case we limit it to the tenant users
        $hook = search_users_by_pattern::create(
            comment::get_component_name(),
            comment::COMMENT_AREA,
            "",
            context_user::instance($system_user->id)->id
        );
        $hook->set_instance_id($system_comment->get_id());

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

        // Any context other than the correct user context won't work
        $hook = search_users_by_pattern::create(
            comment::get_component_name(),
            comment::COMMENT_AREA,
            "",
            context_system::instance()->id
        );
        $hook->set_instance_id($tenant1_playlist_comment1->get_id());

        editor_weka_watcher::on_search_users($hook);
        $this->assertFalse($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());

        $hook = search_users_by_pattern::create(
            comment::get_component_name(),
            comment::COMMENT_AREA,
            "",
            context_coursecat::instance($tenant_one->categoryid)->id
        );
        $hook->set_instance_id($tenant1_playlist_comment1->get_id());

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

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');

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
        $system_playlist = $playlist_generator->create_public_playlist(['userid' => $system_user->id]);

        $system_comment = $comment_generator->create_comment(
            $system_playlist->get_id(),
            $system_playlist::get_resource_type(),
            'comment'
        );
        $system_comment_reply = $comment_generator->create_reply($system_comment->get_id());

        $miscellanous_context = context_coursecat::instance(course::get_default_category_id());

        $this->setUser($user1_tenant1);
        $tenant1_playlist = $playlist_generator->create_public_playlist(['userid' => $user1_tenant1->id]);

        $tenant1_playlist_comment1 = $comment_generator->create_comment(
            $tenant1_playlist->get_id(),
            $tenant1_playlist::get_resource_type(),
            'comment'
        );
        $tenant1_playlist_comment1_reply = $comment_generator->create_reply($tenant1_playlist_comment1->get_id());

        $this->setUser($user2_tenant1);
        $tenant1_playlist_comment2 = $comment_generator->create_comment(
            $tenant1_playlist->get_id(),
            $tenant1_playlist::get_resource_type(),
            'comment'
        );
        $tenant1_playlist_comment1_reply1 = $comment_generator->create_reply($tenant1_playlist_comment1->get_id());
        $tenant1_playlist_comment2_reply1 = $comment_generator->create_reply($tenant1_playlist_comment2->get_id());

        $this->setUser($user1_tenant2);
        $tenant2_playlist = $playlist_generator->create_public_playlist(['userid' => $user1_tenant2->id]);

        $tenant2_playlist_comment1 = $comment_generator->create_comment(
            $tenant2_playlist->get_id(),
            $tenant2_playlist::get_resource_type(),
            'comment'
        );
        $tenant2_playlist_comment1_reply = $comment_generator->create_reply($tenant2_playlist_comment1->get_id());

        $this->setUser($system_user);

        // A system users searching in the system playlist should find all existing users
        $hook = search_users_by_pattern::create(
            comment::get_component_name(),
            comment::COMMENT_AREA,
            "",
            context_user::instance($system_user->id)->id
        );
        $hook->set_instance_id($system_comment->get_id());

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
            comment::get_component_name(),
            comment::COMMENT_AREA,
            "",
            context_user::instance($system_user->id)->id
        );
        $hook->set_instance_id($system_comment->get_id());

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

        // As participant should only see other tenant users on a tenant playlist
        $hook = search_users_by_pattern::create(
            comment::get_component_name(),
            comment::COMMENT_AREA,
            "",
            context_user::instance($user1_tenant1->id)->id
        );
        $hook->set_instance_id($tenant1_playlist_comment1->get_id());

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

        $this->setUser($user2_tenant1);

        // If the user is from a different tenant he should not get a result
        $hook = search_users_by_pattern::create(
            comment::get_component_name(),
            comment::COMMENT_AREA,
            "",
            context_user::instance($user1_tenant2->id)->id
        );
        $hook->set_instance_id($tenant2_playlist_comment1->get_id());

        editor_weka_watcher::on_search_users($hook);
        $this->assertFalse($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());

        // His own tenant is fine
        $hook = search_users_by_pattern::create(
            comment::get_component_name(),
            comment::COMMENT_AREA,
            "",
            context_user::instance($user1_tenant1->id)->id
        );
        $hook->set_instance_id($tenant1_playlist_comment1->get_id());

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

        // Any context other than the correct user context won't work
        $hook = search_users_by_pattern::create(
            comment::get_component_name(),
            comment::COMMENT_AREA,
            "",
            context_system::instance()->id
        );
        $hook->set_instance_id($tenant1_playlist_comment1->get_id());

        editor_weka_watcher::on_search_users($hook);
        $this->assertFalse($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());

        $hook = search_users_by_pattern::create(
            comment::get_component_name(),
            comment::COMMENT_AREA,
            "",
            context_coursecat::instance($tenant_one->categoryid)->id
        );
        $hook->set_instance_id($tenant1_playlist_comment1->get_id());

        editor_weka_watcher::on_search_users($hook);
        $this->assertFalse($hook->is_db_run());
        $this->assertCount(0, $hook->get_users());
    }

}