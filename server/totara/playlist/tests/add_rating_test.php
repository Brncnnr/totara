<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_playlist
 */
defined('MOODLE_INTERNAL') || die();

use core\webapi\execution_context;
use totara_webapi\graphql;
use totara_engage\access\access;
use totara_playlist\playlist;
use totara_engage\share\manager as share_manager;
use totara_engage\share\recipient\manager as recipient_manager;
use totara_engage\share\recipient\helper as recipient_helper;
use core_user\totara_engage\share\recipient\user as user_recipient;

class totara_playlist_add_rating_test extends advanced_testcase {

    /**
     * @return void
     */
    public function test_add_rating(): void {
        global $DB;
        [$playlist, $user1, $user2] = $this->prepare();

        $this->setUser($user2);
        $result = graphql::execute_operation(execution_context::create('ajax', 'totara_playlist_add_rating'),
            [
                'playlistid' => $playlist->get_id(),
                'ratingarea' => 'playlist',
                'rating' => 2
            ]
        );
        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);

        $params = [
            'component' => $playlist::get_resource_type(),
            'area' => playlist::RATING_AREA,
            'instanceid' => $playlist->get_id()
        ];

        $record = $DB->get_record('engage_rating', $params, '*', MUST_EXIST);
        $this->assertEquals(2, $record->rating);
    }

    public function test_rate_validation_low() {
        [$playlist, $user1, $user2] = $this->prepare();

        $this->setUser($user2);

        $result = graphql::execute_operation(execution_context::create('ajax', 'totara_playlist_add_rating'),
            [
                'playlistid' => $playlist->get_id(),
                'ratingarea' => 'playlist',
                'rating' => -1
            ]
        );
        $this->assertStringContainsString('Rating is out of boundaries', $result->errors[0]->getMessage());
    }

    public function test_rate_validation_high() {
        [$playlist, $user1, $user2] = $this->prepare();

        $this->setUser($user2);
        $result = graphql::execute_operation(execution_context::create('ajax', 'totara_playlist_add_rating'),
            [
                'playlistid' => $playlist->get_id(),
                'ratingarea' => 'playlist',
                'rating' => 6
            ]
        );
        $this->assertStringContainsString('Rating is out of boundaries', $result->errors[0]->getMessage());
    }

    public function test_rate_validation_area() {
        [$playlist, $user1, $user2] = $this->prepare();

        $this->setUser($user2);
        $result = graphql::execute_operation(execution_context::create('ajax', 'totara_playlist_add_rating'),
            [
                'playlistid' => $playlist->get_id(),
                'ratingarea' => 'somewhere',
                'rating' => 3
            ]
        );
        $this->assertStringContainsString('Wrong area used for rating', $result->errors[0]->getMessage());
    }

    public function test_rate_own() {
        [$playlist, $user1, $user2] = $this->prepare();

        $this->setUser($user1);
        $result = graphql::execute_operation(execution_context::create('ajax', 'totara_playlist_add_rating'),
            [
                'playlistid' => $playlist->get_id(),
                'ratingarea' => 'playlist',
                'rating' => 3
            ]
        );
        $this->assertStringContainsString('User can not rate this playlist', $result->errors[0]->getMessage());
    }

    public function test_double_rate() {
        [$playlist, $user1, $user2] = $this->prepare();

        $this->setUser($user2);
        graphql::execute_operation(execution_context::create('ajax', 'totara_playlist_add_rating'),
            [
                'playlistid' => $playlist->get_id(),
                'ratingarea' => 'playlist',
                'rating' => 3
            ]
        );

        $result = graphql::execute_operation(execution_context::create('ajax', 'totara_playlist_add_rating'),
            [
                'playlistid' => $playlist->get_id(),
                'ratingarea' => 'playlist',
                'rating' => 5
            ]
        );
        $this->assertStringContainsString('User can not rate this playlist', $result->errors[0]->getMessage());

    }

    public function test_rate_no_access_private() {
        $gen = $this->getDataGenerator();
        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');

        // Create two users.
        $user1 = $gen->create_user();
        $user2 = $gen->create_user();

        $playlist = $playlistgen->create_playlist([
            'access' => access::PRIVATE,
            'userid' => $user1->id // Owner
        ]);

        $this->setUser($user2);

        $result = graphql::execute_operation(execution_context::create('ajax', 'totara_playlist_add_rating'),
            [
                'playlistid' => $playlist->get_id(),
                'ratingarea' => 'playlist',
                'rating' => 5
            ]
        );
        $this->assertStringContainsString('Access denied', $result->errors[0]->getMessage());
    }

    public function test_rate_no_access_restricted_not_shared() {
        $gen = $this->getDataGenerator();
        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');

        $user1 = $gen->create_user();
        $user2 = $gen->create_user();
        $user3 = $gen->create_user();

        $this->engage_capabilize($user1);
        $this->setUser($user1);
        $playlist = $playlistgen->create_playlist([
            'access' => access::RESTRICTED,
            'userid' => $user1->id // Owner
        ]);
        // Share with user2
        $recipient = recipient_manager::create(
            $user2->id,
            recipient_helper::get_component(user_recipient::class),
            user_recipient::AREA
        );
        share_manager::share_to_recipient($playlist, $recipient);

        // Try to access as user3
        $this->setUser($user3);
        $result = graphql::execute_operation(execution_context::create('ajax', 'totara_playlist_add_rating'),
            [
                'playlistid' => $playlist->get_id(),
                'ratingarea' => 'playlist',
                'rating' => 5
            ]
        );
        $this->assertStringContainsString('Access denied', $result->errors[0]->getMessage());
    }

    public function test_rate_restricted_shared() {
        global $DB;

        $gen = $this->getDataGenerator();
        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');

        $user1 = $gen->create_user();
        $user2 = $gen->create_user();

        $this->engage_capabilize($user1);
        $this->setUser($user1);
        $playlist = $playlistgen->create_playlist([
            'access' => access::RESTRICTED,
            'userid' => $user1->id // Owner
        ]);

        // Share with user2
        $recipient = recipient_manager::create(
            $user2->id,
            recipient_helper::get_component(user_recipient::class),
            user_recipient::AREA
        );
        share_manager::share_to_recipient($playlist, $recipient);

        // Try to access as user2
        $this->setUser($user2);
        graphql::execute_operation(execution_context::create('ajax', 'totara_playlist_add_rating'),
            [
                'playlistid' => $playlist->get_id(),
                'ratingarea' => 'playlist',
                'rating' => 5
            ]
        );

        $params = [
            'component' => $playlist::get_resource_type(),
            'area' => playlist::RATING_AREA,
            'instanceid' => $playlist->get_id()
        ];

        $record = $DB->get_record('engage_rating', $params, '*', MUST_EXIST);
        $this->assertEquals(5, $record->rating);
    }

    /**
     * Create common instances
     * @return array
     */
    private function prepare() {
        $gen = $this->getDataGenerator();
        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');

        // Create two users.
        $user1 = $gen->create_user();
        $user2 = $gen->create_user();

        $playlist = $playlistgen->create_playlist([
            'access' => access::PUBLIC,
            'userid' => $user1->id // Owner
        ]);
        return [$playlist, $user1, $user2];
    }

    private function engage_capabilize($user) {
        $roleid = $this->getDataGenerator()->create_role();
        $syscontext = context_system::instance();
        assign_capability('moodle/user:viewdetails', CAP_ALLOW, $roleid, $syscontext);
        role_assign($roleid, $user->id, $syscontext);
    }
}