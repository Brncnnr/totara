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

use totara_engage\access\access_manager;
use totara_engage\access\access;

class totara_playlist_multi_tenancy_access_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_tenant_member_access_system_level_user_playlist(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant->id);

        $this->setAdminUser();

        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');
        $topic =  $topic_generator->create_topic();

        // Log in as user one and start creating the playlists
        $this->setUser($user_one);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist([
            'access' => access::PUBLIC,
            'topics' => [$topic->get_id()]
        ]);

        // User two should be able to access system level user's playlist
        $this->assertTrue(access_manager::can_access($playlist, $user_two->id));

        // Turn on isolation mode and user two should not be able to access any more.
        set_config('tenantsisolated', 1);
        $this->assertFalse(access_manager::can_access($playlist, $user_two->id));
    }

    /**
     * @return void
     */
    public function test_system_level_user_access_tenant_member_playlist(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant->id);

        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');
        $this->setAdminUser();

        $topic = $topic_generator->create_topic();

        // Log in as user two and create a public playlist.
        $this->setUser($user_two);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist([
            'access' => access::PUBLIC,
            'topics' => [$topic->get_id()]
        ]);

        // Login as user one to check if the user one is able to see the playlist or not.
        // User one is a system user, and by default system user should be able to access the
        // tenant user's resource.
        $this->setUser($user_one);
        $this->assertTrue(access_manager::can_access($playlist, $user_one->id));

        // Set isolation mode on to see if user_one is still able to see the playlist anymore.
        set_config('tenantsisolated', 1);
        $this->assertFalse(access_manager::can_access($playlist, $user_one->id));
    }

    /**
     * @return void
     */
    public function test_tenant_member_cannot_access_different_tenant_playlist(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_two->id);

        // Create topics with admin.
        $this->setAdminUser();

        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');
        $topic = $topic_generator->create_topic();

        // Log in as user one to create a public playlist.
        $this->setUser($user_one);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_public_playlist([
            'topics' => [$topic->get_id()],
        ]);

        // Log in as user two and check if user two is able to see the playlist.
        $this->setUser($user_two);
        self::assertFalse(access_manager::can_access($playlist, $user_two->id));

        set_config('tenantsisolated', 1);
        self::assertFalse(access_manager::can_access($playlist, $user_two->id));
    }
}