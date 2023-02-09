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
use core_user\totara_engage\share\recipient\user as user_recipient;
use core\webapi\execution_context;
use totara_webapi\graphql;
use totara_engage\share\recipient\helper as recipient_helper;

class totara_playlist_share_graphql_testcase extends advanced_testcase {

    /**
     * Validate the following:
     *   1. We can share a playlist using the graphql query.
     */
    public function test_share_item() {
        $gen = $this->getDataGenerator();
        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');

        // Create users.
        $users = $playlistgen->create_users(3);

        // Create playlist.
        $this->setUser($users[0]);
        $playlist = $playlistgen->create_playlist([
            'access' => access::PUBLIC
        ]);

        // Set capabilities for all users.
        foreach ($users as $user) {
            $playlistgen->set_capabilities(CAP_ALLOW, $user->id, $playlist->get_context());
        }

        // Set user to someone other than the owner of the survey.
        $this->setUser($users[1]);

        // Create share via graphql.
        $ec = execution_context::create('ajax', 'totara_engage_share');
        $parameters = [
            'itemid' => $playlist->get_id(),
            'component' => 'totara_playlist',
            'recipients' => [
                [
                    'instanceid' => $users[2]->id,
                    'component' => recipient_helper::get_component(user_recipient::class),
                    'area' => user_recipient::AREA
                ]
            ]
        ];

        $result = graphql::execute_operation($ec, $parameters);
        $this->assertEmpty($result->errors, !empty($result->errors) ? $result->errors[0]->message: '');
        $this->assertNotEmpty($result->data);
        $this->assertArrayHasKey('shares', $result->data);

        $shares = $result->data['shares'];
        $this->assertArrayHasKey('sharedbycount', $shares);
        $this->assertEquals(1, $shares['sharedbycount']);
    }

    /**
     * Validate the following:
     *   1. We can share a playlist during update.
     */
    public function test_playlist_update() {
        $gen = $this->getDataGenerator();
        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');

        // Create users.
        $users = $playlistgen->create_users(3);

        // Create playlist.
        $this->setUser($users[0]);
        $playlist = $playlistgen->create_playlist([
            'access' => access::PUBLIC
        ]);

        // Set capabilities for all users.
        foreach ($users as $user) {
            $playlistgen->set_capabilities(CAP_ALLOW, $user->id, $playlist->get_context());
        }

        // Create share via graphql.
        $ec = execution_context::create('ajax', 'totara_playlist_update_playlist');
        $parameters = [
            'id' => $playlist->get_id(),
            'shares' => [
                [
                    'instanceid' => $users[2]->id,
                    'component' => recipient_helper::get_component(user_recipient::class),
                    'area' => user_recipient::AREA
                ],
                [
                    'instanceid' => $users[1]->id,
                    'component' => recipient_helper::get_component(user_recipient::class),
                    'area' => user_recipient::AREA
                ]
            ]
        ];

        $result = graphql::execute_operation($ec, $parameters);
        $this->assertEmpty($result->errors, !empty($result->errors) ? $result->errors[0]->message: '');
        $this->assertNotEmpty($result->data);
        $this->assertArrayHasKey('playlist', $result->data);

        $playlist = $result->data['playlist'];
        $this->assertArrayHasKey('sharedbycount', $playlist);

        // Since it is being shared by the same owner, therefore count will still be zero, as it
        // excludes the owner from the count.
        $this->assertEquals(0, $playlist['sharedbycount']);
    }

    /**
     * Validate the following:
     *   1. We can query share totals via graphql.
     */
    public function test_share_totals() {
        $gen = $this->getDataGenerator();
        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');

        // Create users.
        $users = $playlistgen->create_users(2);

        // Create playlist.
        $this->setUser($users[0]);
        $playlist = $playlistgen->create_playlist([
            'access' => access::PUBLIC
        ]);

        // Create recipients.
        $recipients = $playlistgen->create_user_recipients([$users[0]]);

        // Share playlist.
        $this->setUser($users[1]);
        $playlistgen->share_playlist($playlist, $recipients);

        // Get share totals.
        $ec = execution_context::create('ajax', 'totara_engage_share_totals');
        $parameters = [
            'itemid' => $playlist->get_id(),
            'component' => $playlist::get_resource_type()
        ];

        $result = graphql::execute_operation($ec, $parameters);
        $this->assertEmpty($result->errors, !empty($result->errors) ? $result->errors[0]->message: '');
        $this->assertNotEmpty($result->data);
        $this->assertArrayHasKey('shares', $result->data);

        $shares = $result->data['shares'];
        $this->assertEquals(1, $shares['totalrecipients']);

        $this->assertArrayHasKey('recipients', $shares);
        $recipients = $shares['recipients'];
        $this->assertEquals(1, sizeof($recipients));

        $recipient = reset($recipients);
        $this->assertEquals(user_recipient::AREA, $recipient['area']);
        $this->assertEquals(1, $recipient['total']);
    }

    /**
     * Validate the following:
     *   1. We can query recipients of a specific shared item.
     */
    public function test_recipients() {
        $gen = $this->getDataGenerator();
        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');

        // Create users.
        $users = $playlistgen->create_users(2);

        // Create playlist.
        $this->setUser($users[0]);
        $playlist = $playlistgen->create_playlist([
            'access' => access::PUBLIC
        ]);

        // Share playlist.
        $this->setUser($users[0]);
        $recipients = $playlistgen->create_user_recipients([$users[1]]);
        $playlistgen->share_playlist($playlist, $recipients);

        // Switch to admin user to not be blocked by privacy checks.
        $this->setUser(2);

        // Get recipients.
        $ec = execution_context::create('ajax', 'totara_engage_share_recipients');
        $parameters = [
            'itemid' => $playlist->get_id(),
            'component' => $playlist::get_resource_type(),
            'theme' => 'ventura',
        ];

        $result = graphql::execute_operation($ec, $parameters);
        $this->assertEmpty($result->errors, !empty($result->errors) ? $result->errors[0]->message: '');
        $this->assertNotEmpty($result->data);
        $this->assertArrayHasKey('recipients', $result->data);

        $recipients = $result->data['recipients'];
        $this->assertNotEmpty($recipients);
        $this->assertEquals(1, sizeof($recipients));
        $recipient = reset($recipients);
        $this->assertArrayHasKey('user', $recipient);
        $user = $recipient['user'];
        $this->assertArrayHasKey('fullname', $user);
        $this->assertEquals('Some2 Any2', $user['fullname']);
    }
}