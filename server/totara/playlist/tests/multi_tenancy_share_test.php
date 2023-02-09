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
use core_user\totara_engage\share\recipient\user;
use totara_engage\query\query as card_query;
use totara_engage\card\card_loader;

class totara_playlist_multi_tenancy_share_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_user_cannot_see_playlists_shared_by_ex_member(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_one->id);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');

        $topic_ids = [];
        $this->setAdminUser();

        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');
        for ($i = 0; $i < 2; $i++) {
            $topic = $topic_generator->create_topic();
            $topic_ids[] = $topic->get_id();
        }

        // Login as second users, create playlists and share to user one.
        $this->setUser($user_two);
        $recipient = new user($user_one->id);

        for ($i = 0; $i < 5; $i++) {
            $playlist = $playlist_generator->create_playlist([
                'access' => access::RESTRICTED,
                'topics' => $topic_ids
            ]);

            $playlist_generator->share_playlist($playlist, [$recipient]);
        }

        // Log in as user one and see if user one is able to see playlists via share.
        $this->setUser($user_one);

        $query = new card_query();
        $query->set_userid($user_one->id);
        $query->set_area('shared');

        $loader = new card_loader($query);
        $same_paginator = $loader->fetch();

        $this->assertEquals(5, $same_paginator->get_total());
        $this->assertCount(5, $same_paginator->get_items()->all());

        // Now move user two to tenant two
        $tenant_two = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_two->id);

        $new_paginator = $loader->fetch();
        $this->assertEmpty($new_paginator->get_items()->all());
        $this->assertEquals(0, $new_paginator->get_total());
    }
}