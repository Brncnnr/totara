<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @package totara_playlist
 */
defined('MOODLE_INTERNAL') || die();

use totara_engage\access\access;
use totara_catalog\catalog_retrieval;
use core_user\totara_engage\share\recipient\user;

class totara_playlist_catalog_multi_tenancy_testcase extends advanced_testcase {
    /**
     * Test to assure that a user from one tenant should not be able to see any playlists
     * from other different tenant via totara_catalog
     *
     * @return void
     */
    public function test_user_should_not_see_playlists_from_different_tenant_via_catalog(): void {
        $generator = $this->getDataGenerator();

        // We will have to create several topics first.
        $topic_ids = [];
        $this->setAdminUser();

        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');
        for ($i = 0; $i < 2; $i++) {
            $topic = $topic_generator->create_topic();
            $topic_ids[] = $topic->get_id();
        }

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        // Add user one/two to tenant one/two.
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_two->id);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $this->setUser($user_two);

        // Create playlists for user two and make them as public.
        for ($i = 0; $i < 5; $i++) {
            $playlist_generator->create_playlist(
                [
                    'access' => access::PUBLIC,
                    'topics' => $topic_ids
                ]
            );
        }

        // Fetch catalog for the user two and make sure that this user's two is able to see
        // self own playlists within the catalog.
        $this->executeAdhocTasks();

        $retriever = new catalog_retrieval();
        $user_two_result = $retriever->get_page_of_objects(50, 0);

        $this->assertObjectHasAttribute('objects', $user_two_result);
        $this->assertCount(5, $user_two_result->objects);

        // Now set it as user one and see that if user one is able to retrieve those playlists via
        // catalog or not.
        $this->setUser($user_one);

        $user_one_result = $retriever->get_page_of_objects(50, 0);
        $this->assertObjectHasAttribute('objects', $user_one_result);
        $this->assertEmpty($user_one_result->objects);
    }

    /**
     * Test to assure that once the user from one tenant moved to different tenant
     * will cause the playlist shared with other user to be revoked
     *
     * @return void
     */
    public function test_migrated_tenant_member_cause_visibility_revoked(): void {
        $generator = $this->getDataGenerator();
        $this->setAdminUser();

        $topic_ids = [];

        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');
        for ($i = 0; $i < 2; $i++) {
            $topic = $topic_generator->create_topic();
            $topic_ids[] = $topic->get_id();
        }

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        // All of these two users will be member of the tenant_one.
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_one->id);

        // Log in as second user, and create the playlists which are shared to the user one.
        $this->setUser($user_two);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $recipient = new user($user_one->id);

        for ($i = 0; $i < 5; $i++) {
            $playlist = $playlist_generator->create_playlist([
                'access' => access::RESTRICTED,
                'topics' => $topic_ids
            ]);

            $playlist_generator->share_playlist($playlist, [$recipient]);
        }

        // Log in as first user and check that if this user is able to see the
        // playlists or not.
        $this->executeAdhocTasks();
        $this->setUser($user_one);

        $retrieval = new catalog_retrieval();
        $user_one_result = $retrieval->get_page_of_objects(50, 0);

        $this->assertObjectHasAttribute('objects', $user_one_result);
        $this->assertCount(5, $user_one_result->objects);

        // Move this user to different tenant.
        $this->setAdminUser();

        $tenant_two = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_two->id);

        // Check that if user one is still able to see the playlists via catalogue anymore.
        $this->setUser($user_one);
        $user_one_result_2 = $retrieval->get_page_of_objects(50, 0);

        $this->assertObjectHasAttribute('objects', $user_one_result_2);
        $this->assertEmpty($user_one_result_2->objects);
    }

    /**
     * Test to make sure that admin can see all the playlist even from private
     * to public, or from one tenant to another.
     *
     * @return void
     */
    public function test_admin_see_all(): void {
        $generator = $this->getDataGenerator();
        $this->setAdminUser();

        $topic_ids = [];

        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');

        for ($i = 0; $i < 2; $i++) {
            $topic = $topic_generator->create_topic();
            $topic_ids[] = $topic->get_id();
        }

        $user_one = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant->id);

        $this->setUser($user_one);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        for ($i = 0; $i < 5; $i++) {
            $access = access::PRIVATE;

            if (0 === $i % 2) {
                $access = access::PUBLIC;
            }

            $playlist_generator->create_playlist([
                'access' => $access,
                'topics' => $topic_ids
            ]);
        }

        // Now log in as admin and check if the admin user is able to see all five of the playlists.
        $this->setAdminUser();
        $retrieval = new catalog_retrieval();

        $result = $retrieval->get_page_of_objects(50, 0);
        $this->assertObjectHasAttribute('objects', $result);
        $this->assertCount(5, $result->objects);
    }

    /**
     * Test to make sure that participant user is able to see the system level user's playlists and
     * see the tenant member's playlists.
     *
     * @return void
     */
    public function test_participant_can_see_tenant_playlists_and_system_level_playlists(): void {
        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant = $tenant_generator->create_tenant();

        $user_one = $generator->create_user();
        $tenant_generator->set_user_participation($user_one->id, [$tenant->id]);

        $user_two = $generator->create_user();
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant->id);

        // User three is the system level user.
        $user_three = $generator->create_user();

        $topic_ids = [];
        $this->setAdminUser();

        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');

        for ($i = 0; $i < 2; $i++) {
            $topic = $topic_generator->create_topic();
            $topic_ids[] = $topic->get_id();
        }

        // Login as user two and create playlists
        $this->setUser($user_two);
        for ($i = 0; $i < 5; $i++) {
            $playlist_generator->create_playlist([
                'access' => access::PUBLIC,
                'topics' => $topic_ids
            ]);
        }

        // Log in as user three and create the playlists
        $this->setUser($user_three);
        for ($i = 0; $i < 5; $i++) {
            $playlist_generator->create_playlist([
                'access' => access::PUBLIC,
                'topics' => $topic_ids
            ]);
        }

        // Log in as user one and check if this user is able to see the playlists or not.
        $this->setUser($user_one);

        $retrieval = new catalog_retrieval();
        $user_one_result = $retrieval->get_page_of_objects(50, 0);

        $this->assertObjectHasAttribute('objects', $user_one_result);
        $this->assertCount(10, $user_one_result->objects);

        // Login as second user and check that if user two is able to see user three playlists or not.
        // User two is able to see self playlists and system level user's playlists.
        $this->setUser($user_two);
        $user_two_result = $retrieval->get_page_of_objects(50, 0);

        $this->assertObjectHasAttribute('objects', $user_two_result);
        $this->assertCount(10, $user_two_result->objects);
    }

    /**
     * Test to assure that the system level users are able to see each others playlists.
     * @return void
     */
    public function test_system_level_can_see_each_other_playlists(): void {
        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $topic_ids = [];
        $this->setAdminUser();

        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');
        for ($i = 0; $i < 2; $i++) {
            $topic = $topic_generator->create_topic();
            $topic_ids[] = $topic->get_id();
        }

        // Adding new user and set this user within a tenant. So that we can
        // be sure that two system level user is able to see each other's playlists only.
        $user_three = $generator->create_user();
        $tenant = $tenant_generator->create_tenant();

        $tenant_generator->migrate_user_to_tenant($user_three->id, $tenant->id);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');

        // Login as user three and create a public playlists.
        $this->setUser($user_three);
        for ($i = 0; $i < 5; $i++) {
            $playlist_generator->create_playlist([
                'access' => access::PUBLIC,
                'topics' => $topic_ids
            ]);
        }

        // Login as first user and create public playlists
        $this->setUser($user_one);
        for ($i = 0; $i < 5; $i++) {
            $playlist_generator->create_playlist([
                'access' => access::PUBLIC,
                'topics' => $topic_ids
            ]);
        }

        // Login as second user and create public playlists
        $this->setUser($user_two);
        for ($i = 0; $i < 5; $i++) {
            $playlist_generator->create_playlist([
                'access' => access::PUBLIC,
                'topics' => $topic_ids
            ]);
        }

        // Log in as first user and check if the user is able to see second user's playlists.
        $retrieval = new catalog_retrieval();

        $this->setUser($user_one);
        $user_one_result = $retrieval->get_page_of_objects(50, 0);

        $this->assertObjectHasAttribute('objects', $user_one_result);

        // This is to include self playlists.
        $this->assertCount(10, $user_one_result->objects);

        // Login as second user and check if the user is able to see first user's playlists.
        $user_two_result = $retrieval->get_page_of_objects(50, 0);

        $this->assertObjectHasAttribute('objects', $user_two_result);

        // This is to include self playlists.
        $this->assertCount(10, $user_two_result->objects);
    }
}