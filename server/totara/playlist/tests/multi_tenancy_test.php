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
 * @package totara_playlist
 */
defined('MOODLE_INTERNAL') || die();

use totara_engage\access\access;
use totara_playlist\query\playlist_query;
use totara_playlist\loader\playlist_loader;
use core_user\totara_engage\share\recipient\user;

class totara_playlist_multi_tenancy_testcase extends advanced_testcase {
    /**
     * Test to assure that a user from one tenants cannot see a resource from another tenant.
     *
     * @return void
     */
    public function test_user_tenant_cannot_see_playlists_from_other_tenant(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $this->setAdminUser();

        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');
        $topics = [];

        for ($i = 0; $i < 2; $i++) {
            $topic = $topic_generator->create_topic();
            $topics[] = $topic->get_id();
        }

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        // Assign user_one to tenant one and user two to tenant two
        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_two->id);

        // Now log in as user two and make sure that all user two had created
        // several playlists.
        $this->setUser($user_two);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');

        for ($i = 0; $i < 5; $i++) {
            $playlist_generator->create_playlist([
                'access' => access::PUBLIC,
                'topics' => $topics
            ]);
        }

        $this->setUser($user_one);

        $query = new playlist_query($user_one->id);
        $paginator = playlist_loader::get_playlists($query);

        $this->assertEmpty($paginator->get_items()->all());
        $this->assertEquals(0, $paginator->get_total());
    }

    /**
     * Test to assure that a user that is not belonging to any tenant should not be-able to see
     * playlists at all - which the playlists belong to any tenant members.
     *
     * @return void
     */
    public function test_non_tenant_cannot_see_playlists_from_tenant_member(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');

        $tenant_generator->enable_tenants();
        $tenant_one = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);

        $this->setUser($user_one);

        // Creating several playlists, and log in as user two and check that if the user's two is able to see the playlists or not.
        for ($i = 0; $i < 5; $i++) {
            $playlist_generator->create_playlist(['access' => access::PUBLIC]);
        }

        $this->setUser($user_two);

        $query = new playlist_query($user_two->id);
        $paginator = playlist_loader::get_playlists($query);

        $this->assertEmpty($paginator->get_items()->all());
        $this->assertEquals(0, $paginator->get_total());
    }

    /**
     * Test to assure that tenant participant is able to see the playlists from the
     * tenant that a user is a participant of.
     *
     * @return void
     */
    public function test_participant_can_see_playlists_from_tenant_member(): void {
        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $tenant_generator->set_user_participation($user_two->id, [$tenant_one->id]);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $this->setUser($user_one);

        // Start creating several playlists from user_one within tenant one.
        // So that we can test that the tenant participant is able to see the playlist.
        for ($i = 0; $i < 5; $i++) {
            $playlist_generator->create_playlist(
                ['access' => access::PUBLIC]
            );
        }

        $this->setUser($user_two);

        $query = new playlist_query($user_two->id);
        $paginator = playlist_loader::get_playlists($query);

        $this->assertCount(5, $paginator->get_items()->all());
        $this->assertEquals(5, $paginator->get_total());
    }

    /**
     * Test to assure that a user cannot see what had been shared within the old tenants.
     * @return void
     */
    public function test_user_move_tenant_cannot_see_what_had_shared_within_old_tenant(): void {
        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        // First, we make two of the users to be a member of within one tenant
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_one->id);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');

        // Create several playlists by user two and share it to user one.
        $this->setUser($user_two);
        $user_recipient = new user($user_one->id);

        for ($i = 0; $i < 5; $i++) {
            $playlist = $playlist_generator->create_playlist(
                ['access' => access::RESTRICTED]
            );

            $playlist_generator->share_playlist($playlist, [$user_recipient]);
        }

        $this->setUser($user_one);
        $query = new playlist_query($user_one->id);
        $shared_paginator = playlist_loader::get_playlists($query);

        $this->assertEquals(5, $shared_paginator->get_total());
        $this->assertCount(5, $shared_paginator->get_items()->all());

        // Now move user one to different tenant
        $tenant_two = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_two->id);

        $new_paginator = playlist_loader::get_playlists($query);
        $this->assertEmpty($new_paginator->get_items()->all());
        $this->assertEquals(0, $new_paginator->get_total());
    }

    /**
     * Test to assure that a particpant user is able to see playlists within tenant and outside of tenant.
     * Where the playlists are created by system user.
     *
     * @return void
     */
    public function test_participant_user_is_able_to_see_playlists_from_tenant_and_outside_tenant(): void {
        $generator = $this->getDataGenerator();
        $this->setAdminUser();

        $topic_ids = [];

        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');
        for ($i = 0; $i < 5; $i++) {
            $topic = $topic_generator->create_topic();
            $topic_ids[] = $topic->get_id();
        }

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();
        $user_three = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant->id);
        $tenant_generator->set_user_participation($user_one->id, [$tenant->id]);

        $recipient = new user($user_one->id);

        // Log in as second user and create the playlists.
        $this->setUser($user_two);

        for ($i = 0; $i < 5; $i++) {
            $playlist = $playlist_generator->create_playlist([
                'access' => access::RESTRICTED,
                'topics' => $topic_ids
            ]);

            $playlist_generator->share_playlist($playlist, [$recipient]);
        }

        // Log in as third user and create the playlists.
        $this->setUser($user_three);
        for ($i = 0; $i < 5; $i++) {
            $playlist = $playlist_generator->create_playlist([
                'access' => access::PUBLIC,
                'topics' => $topic_ids
            ]);

            $playlist_generator->share_playlist($playlist, [$recipient]);
        }

        // Create new tenant, new user and add more playlists under this user.
        // So that we can be sure that this user's playlists are not appearing in the result.
        $user_four = $generator->create_user();
        $tenant_four = $tenant_generator->create_tenant();

        $tenant_generator->migrate_user_to_tenant($user_four->id, $tenant_four->id);
        $this->setUser($user_four);

        for ($i = 0; $i < 5; $i++) {
            $playlist_generator->create_playlist([
                'access' => access::PUBLIC,
                'topics' => $topic_ids
            ]);
        }


        // Now log in as user one and check if the user is able to view all 10 playlists
        // which 5 is from tenant user and 5 is from system user.
        $this->setUser($user_one);

        $query = new playlist_query($user_one->id);
        $paginator = playlist_loader::get_playlists($query);

        $this->assertEquals(10, $paginator->get_total());
        $this->assertCount(10, $paginator->get_items()->all());
    }

    /**
     * Test to assure that a participant user is able to see playlists that created by self and
     * able to see the playlists created by tenant's user that this participant is a part of.
     *
     * @return void
     */
    public function test_participant_view_self_playlists_among_with_tenant_playlists(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant->id);
        $tenant_generator->set_user_participation($user_one->id, [$tenant->id]);

        $topic_ids = [];
        $this->setAdminUser();

        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');
        for ($i = 0; $i < 2; $i++) {
            $topic = $topic_generator->create_topic();
            $topic_ids[] = $topic->get_id();
        }

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');

        // Log in as user two and create playlists which are shared to user one.
        $this->setUser($user_two);
        $recipient = new user($user_one->id);

        for ($i = 0; $i < 5; $i++) {
            $playlist = $playlist_generator->create_playlist([
                'access' => access::RESTRICTED,
                'topics' => $topic_ids
            ]);

            $playlist_generator->share_playlist($playlist, [$recipient]);
        }

        // Login as user one and create playlists.
        $this->setUser($user_one);

        for ($i = 0; $i < 5; $i++) {
            $playlist_generator->create_playlist([
                'access' => access::PRIVATE,
                'topics' => $topic_ids,
            ]);
        }

        $query = new playlist_query($user_one->id);
        $paginator = playlist_loader::get_playlists($query);

        $this->assertNotEmpty($paginator->get_items()->all());
        $this->assertEquals(10, $paginator->get_total());
    }

    /**
     * Test to assure that if a user member within same tenant is able to see each
     * other's contents.
     *
     * @return void
     */
    public function test_tenant_can_see_self_playlist_and_same_tenant_playlist(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant->id);
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant->id);

        $this->setAdminUser();

        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');
        $topic = $topic_generator->create_topic();

        $topics = [$topic->get_id()];

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');

        // Login as first user and created several playlists for this user.
        $this->setUser($user_one);
        for ($i = 0; $i < 5; $i++) {
            $playlist_generator->create_playlist([
                'access' => access::PRIVATE,
                'topics' => $topics
            ]);
        }

        // Log in as second user and created public playlists for this user.
        $this->setUser($user_two);
        for ($i = 0; $i < 5; $i++) {
            $playlist_generator->create_playlist([
                'access' => access::PUBLIC,
                'topics' => $topics
            ]);
        }

        // Login as first user again and check for the fetching.
        $this->setUser($user_one);

        $query = new playlist_query($user_one->id);
        $paginator = playlist_loader::get_playlists($query);

        $this->assertCount(10, $paginator->get_items()->all());
        $this->assertEquals(10, $paginator->get_total());
    }

    /**
     * @return void
     */
    public function test_tenant_cannot_see_private_playlists_within_same_tenant(): void {
        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant = $tenant_generator->create_tenant();

        $user_one = $generator->create_user();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant->id);

        $user_two = $generator->create_user();
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant->id);

        $this->setUser($user_two);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        for ($i = 0; $i < 5; $i++) {
            $playlist_generator->create_playlist(['access' => access::PRIVATE]);
        }

        $this->setUser($user_one);

        $query = new playlist_query($user_one->id);
        $paginator = playlist_loader::get_playlists($query);

        $this->assertEmpty($paginator->get_items()->all());
        $this->assertEquals(0, $paginator->get_total());
    }

    /**
     * @return void
     */
    public function test_system_level_users_can_see_each_other_playlists(): void {
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

        // Check the result as user one
        $this->setUser($user_one);

        $user_one_query = new playlist_query($user_one->id);
        $user_one_paginator = playlist_loader::get_playlists($user_one_query);

        $this->assertEquals(10, $user_one_paginator->get_total());
        $this->assertCount(10, $user_one_paginator->get_items()->all());

        // Check result as user two
        $this->setUser($user_two);

        $user_two_query = new playlist_query($user_two->id);
        $user_two_paginator = playlist_loader::get_playlists($user_two_query);

        $this->assertEquals(10, $user_two_paginator->get_total());
        $this->assertCount(10, $user_two_paginator->get_items()->all());

        // Check result as tenant user member.
        $this->setUser($user_three);
        $user_three_query = new playlist_query($user_three->id);
        $user_three_paginator = playlist_loader::get_playlists($user_three_query);

        // This is happening because isolation mode is off and there are 10 playlists created by
        // system level user and 5 playlists created by this specific users.
        $this->assertCount(15, $user_three_paginator->get_items()->all());
        $this->assertEquals(15, $user_three_paginator->get_total());
    }

    /**
     * Test to assure that a tenant member is able to view system level user's playlists when
     * full isolation mode is off and then turned on.
     *
     * @return void
     */
    public function test_tenant_member_view_system_level_user_playlists(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant->id);

        $topic_ids = [];
        $this->setAdminUser();

        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');
        for ($i = 0; $i < 2; $i++) {
            $topic = $topic_generator->create_topic();
            $topic_ids[] = $topic->get_id();
        }

        // Log in as first user and start creating playlists.
        $this->setUser($user_one);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        for ($i = 0; $i < 5; $i++) {
            $playlist_generator->create_playlist([
                'access' => access::PUBLIC,
                'topics' => $topic_ids
            ]);
        }

        // Login as second user and check if the user is able to see any playlists from
        // system level user.
        $this->setUser($user_two);

        $playlist_query = new playlist_query($user_two->id);
        $off_paginator = playlist_loader::get_playlists($playlist_query);

        $this->assertCount(5, $off_paginator->get_items()->all());
        $this->assertEquals(5, $off_paginator->get_total());

        // Turn on tenant isolated.
        set_config('tenantsisolated', 1);
        $on_paginator = playlist_loader::get_playlists($playlist_query);

        $this->assertEmpty($on_paginator->get_items()->all());
        $this->assertEquals(0, $on_paginator->get_total());
    }
}