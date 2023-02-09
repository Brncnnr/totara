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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_playlist
 */

defined('MOODLE_INTERNAL') || die();

use totara_engage\access\access;
use totara_engage\entity\share as share_entity;
use totara_engage\entity\share_recipient as recipient_entity;
use totara_engage\share\provider as share_provider;
use totara_engage\share\share as share_model;
use totara_engage\share\manager as share_manager;
use totara_playlist\playlist;
use totara_engage\repository\share_repository;
use totara_engage\repository\share_recipient_repository;
use core_user\totara_engage\share\recipient\user as user_recipient;
use totara_engage\access\access_manager;

class totara_playlist_share_testcase extends advanced_testcase {

    /**
     * Validate the following:
     *   1. Playlist can be shared (no capability validation).
     *   2. Sharing an playlist creates a database record.
     *   3. Sharing record contains the correct sharer and recipient details.
     *   4. Playlist can be constructed from share record.
     */
    public function test_create_share() {
        $gen = $this->getDataGenerator();
        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');

        // Create users.
        $users = $playlistgen->create_users(2);

        // Create playlist.
        $this->setUser($users[0]);
        $playlist = $playlistgen->create_playlist([
            'name' => 'Playing with lists'
        ]);

        // Setup recipients.
        $recipients = $playlistgen->create_user_recipients([$users[1]]);

        // Share playlist.
        $this->setUser($users[0]);
        $shares = $playlistgen->share_playlist($playlist, $recipients);
        $this->assertNotEmpty($shares);
        $this->assertEquals(1, sizeof($shares));
        $share = reset($shares);

        // Load the share recipient_entity from the DB. This should fail if record not found.
        $recipient_entity = new recipient_entity($share->get_recipient_id());

        // Confirm that sharer is correct.
        $this->assertEquals($users[0]->id, $recipient_entity->sharerid);
        $this->assertEquals($share->get_sharer_id(), $recipient_entity->sharerid);

        // Confirm that the recipient is correct.
        $this->assertEquals($users[1]->id, $recipient_entity->instanceid);
        $this->assertEquals($share->get_recipient_instanceid(), $recipient_entity->instanceid);
        $this->assertEquals($share->get_recipient_area(), $recipient_entity->area);

        // Fetch playlist from the share.
        $provider = share_provider::create($share->get_component());

        /** @var playlist $instance */
        $instance = $provider->get_item_instance($share->get_item_id());

        // Confirm that the instance fetched is playlist.
        $this->assertInstanceOf(playlist::class, $instance);
        $this->assertEquals('Playing with lists', $instance->get_name());
    }

    /**
     * Validate the following:
     *   1. Same playlist can be shared with/by multiple users.
     *   2. Total sharers should only be the number of unique users who shared the item.
     */
    public function test_total_sharers() {
        $gen = $this->getDataGenerator();
        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');

        // Create users.
        $users = $playlistgen->create_users(3);

        // Create playlist.
        $this->setUser($users[0]);
        $playlist1 = $playlistgen->create_playlist([
            'name' => 'Playlist 1'
        ]);
        $playlist2 = $playlistgen->create_playlist([
            'name' => 'Playlist 2'
        ]);

        // Share playlist.
        $this->setUser($users[1]);
        $playlistgen->share_playlist($playlist1, $playlistgen->create_user_recipients([$users[2]]));
        $this->setUser($users[2]);
        $playlistgen->share_playlist($playlist1, $playlistgen->create_user_recipients([$users[1]]));
        $playlistgen->share_playlist($playlist2, $playlistgen->create_user_recipients([$users[1]]));

        /** @var share_repository $repo */
        $repo = share_entity::repository();

        // Get total number of unique sharers.
        $total1 = $repo->get_total_sharers($playlist1->get_id(), playlist::get_resource_type());
        $total3 = $repo->get_total_sharers($playlist2->get_id(), playlist::get_resource_type());

        $this->assertEquals(2, $total1);
        $this->assertEquals(1, $total3);
    }

    /**
     * Validate the following:
     *   1. We can share to users.
     *   2. Get correct recipient totals per recipient area.
     */
    public function test_total_recipients() {
        $gen = $this->getDataGenerator();
        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');

        // Create users.
        $users = $playlistgen->create_users(5);

        // Create playlist.
        $this->setUser($users[1]);
        $playlist1 = $playlistgen->create_playlist([
            'name' => 'Playlist 1'
        ]);
        $playlist2 = $playlistgen->create_playlist([
            'name' => 'Playlist 2'
        ]);

        $recipients = $playlistgen->create_user_recipients([$users[2], $users[3], $users[4]]);

        $this->setUser($users[0]);
        $shares1 = $playlistgen->share_playlist($playlist1, $recipients);

        $recipients = $playlistgen->create_user_recipients([$users[0], $users[2]]);

        $this->setUser($users[3]);
        $shares2 = $playlistgen->share_playlist($playlist1, $recipients);

        $recipients = $playlistgen->create_user_recipients([$users[0], $users[2]]);

        $this->setUser($users[3]);
        $shares3 = $playlistgen->share_playlist($playlist2, $recipients);

        // Shares 1 & 2 should all have the same id as they are sharing the same item.
        $id = $shares1[0]->get_id();
        foreach($shares1 as $share) {
            $this->assertEquals($id, $share->get_id());
        }
        foreach($shares2 as $share) {
            $this->assertEquals($id, $share->get_id());
        }

        // Shares 3 is sharing a different playlist so should have different id.
        foreach($shares3 as $share) {
            $this->assertNotEquals($id, $share->get_id());
        }

        /** @var share_recipient_repository $repo */
        $repo = recipient_entity::repository();

        // Get recipient totals.
        $totals = $repo->get_total_recipients_per_area($shares1[0]->get_id());

        // Expected totals.
        $t = [
            user_recipient::AREA => 4
        ];

        // Confirm the totals for each recipient area.
        foreach ($totals as $total) {
            $this->assertEquals($t[$total->area], $total->total);
        }

        // Get recipient totals.
        $totals = $repo->get_total_recipients_per_area($shares3[0]->get_id());

        // Expected totals.
        $t = [
            user_recipient::AREA => 2
        ];

        // Confirm the totals for each recipient area.
        foreach ($totals as $total) {
            $this->assertEquals($t[$total->area], $total->total);
        }
    }

    /**
     * Validate the following:
     *   1. The correct recipient information is saved and retrieved from database.
     */
    public function test_recipients() {
        $gen = $this->getDataGenerator();
        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');

        // Create users.
        $users = $playlistgen->create_users(2);

        // Create playlist.
        $this->setUser($users[1]);
        $playlist = $playlistgen->create_playlist();

        // Create recipients.
        $recipients = $playlistgen->create_user_recipients([$users[0]]);

        // Share playlist.
        $this->setUser($users[1]);
        $shares = $playlistgen->share_playlist($playlist, $recipients);

        $this->assertNotEmpty($shares);
        $this->assertEquals(1, sizeof($shares));
        $share = reset($shares);

        /** @var share_recipient_repository $repo */
        $repo = recipient_entity::repository();

        // Confirm the users.
        $recipients = $repo->get_recipients($share->get_id());

        // We should only have 1 recipient.
        $this->assertEquals(1, sizeof($recipients));

        // Recipient should be a user.
        $recipient = reset($recipients);
        $this->assertEquals(user_recipient::AREA, $recipient->area);

        // Recipient should match the user details.
        $user = \core_user::get_user($recipient->instanceid);
        $this->assertEquals('Some1', $user->firstname);
        $this->assertEquals('Any1', $user->lastname);
    }

    /**
     * Validate the following:
     *   1. Shares can be cloned onto another share.
     */
    public function test_clone() {
        $gen = $this->getDataGenerator();
        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');

        // Create users.
        $users = $playlistgen->create_users(5);

        // Create playlists.
        $this->setUser($users[0]);
        $playlist1 = $playlistgen->create_playlist([
            'access' => access::RESTRICTED
        ]);
        $playlist2 = $playlistgen->create_playlist([
            'access' => access::RESTRICTED
        ]);

        // Create recipients.
        $recipients1 = $playlistgen->create_user_recipients([$users[1], $users[2]]);
        $recipients2 = $playlistgen->create_user_recipients([$users[1], $users[3], $users[4]]);

        // Share playlists.
        $this->engage_capabilize($users[0]);
        $this->setUser($users[0]);
        $shares1 = $playlistgen->share_playlist($playlist1, $recipients1);
        $shares2 = $playlistgen->share_playlist($playlist2, $recipients2);

        /** @var share_model $share1 */
        $share1 = reset($shares1);

        /** @var share_model $share2 */
        $share2 = reset($shares2);

        /** @var share_recipient_repository $repo */
        $repo = recipient_entity::repository();

        share_manager::clone_shares($playlist1, playlist::get_resource_type(), $share2->get_id());

        // Confirm that share1 has got at least the same shares as share2.
        $recipients = $repo->get_recipients($share1->get_id());

        // Recipients should include all from share1 and share2
        $recipients3 = array_merge($recipients1, $recipients2);

        /** @var user_recipient $recipient3 */
        foreach ($recipients3 as $recipient3) {
            $found = false;
            foreach ($recipients as $recipient) {
                if ($recipient3->get_id() == $recipient->instanceid) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, "Recipient not found after cloning");
        }
    }

    /**
     * Validate the following:
     *   1. Users are not allowed to share private playlists.
     *   2. Users should have the correct capabilities to share playlists.
     *   3. Only owners can share limited access playlists.
     */
    public function test_capabilities() {
        $gen = $this->getDataGenerator();
        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');

        $users = $playlistgen->create_users(2);

        // Create playlist.
        $this->setUser($users[0]);
        $playlist1 = $playlistgen->create_playlist([
            'access' => access::PUBLIC
        ]);
        $playlist2 = $playlistgen->create_playlist([
            'access' => access::PRIVATE
        ]);
        $playlist3 = $playlistgen->create_playlist([
            'access' => access::RESTRICTED
        ]);

        $context = $playlist1->get_context();

        // Set role capabilities and test expected outcome.
        $playlistgen->set_capabilities(CAP_PREVENT, $users[0]->id, $context);
        $this->assertFalse($playlist1->can_share($users[0]->id));
        $playlistgen->set_capabilities(CAP_ALLOW, $users[0]->id, $context);
        $this->assertTrue($playlist1->can_share($users[0]->id));

        // Anyone should be able to share public playlist.
        $this->setUser($users[0]);
        $shareable = $playlist1->get_shareable();
        $this->assertTrue($shareable->is_shareable());
        $this->setUser($users[1]);
        $shareable = $playlist1->get_shareable();
        $this->assertTrue($shareable->is_shareable());

        // Users should not be allowed to share private playlists.
        $this->setUser($users[0]);
        $shareable = $playlist2->get_shareable();
        $this->assertFalse($shareable->is_shareable());
        $this->assertEquals('error:shareprivate', $shareable->get_reason());

        // Only the owner can share limited access playlists.
        $this->setUser($users[0]);
        $shareable = $playlist3->get_shareable();
        $this->assertTrue($shareable->is_shareable());

        // Others cannot share limited access playlists.
        $this->setUser($users[1]);
        $shareable = $playlist3->get_shareable();
        $this->assertFalse($shareable->is_shareable());
        $this->assertEquals('error:sharerestricted', $shareable->get_reason());

        $access = access_manager::can_access($playlist3, $users[1]->id);
        $this->assertFalse($access);

        // Share playlist and see if user has access.
        $this->setUser($users[0]);
        $recipients = $playlistgen->create_user_recipients([$users[1]]);
        $shares = $playlistgen->share_playlist($playlist3, $recipients);
        $this->assertNotEmpty($shares);
        $this->assertEquals(1, sizeof($shares));

        $access = access_manager::can_access($playlist3, $users[1]->id);
        $this->assertTrue($access);
    }

    private function engage_capabilize($user) {
        $roleid = $this->getDataGenerator()->create_role();
        $syscontext = context_system::instance();
        assign_capability('moodle/user:viewdetails', CAP_ALLOW, $roleid, $syscontext);
        role_assign($roleid, $user->id, $syscontext);
    }
}