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
 * @author Qingyang liu <qingyang.liu@totaralearning.com>
 * @package totara_playlist
 */
defined('MOODLE_INTERNAL') || die();

use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_engage\access\access;
use totara_playlist\playlist;

class totara_playlist_webapi_get_playlist_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_get_non_exist_playlist(): void {
        $this->setup_user();
        $this->create_playlist();
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('No playlist found');
        $this->execute_query(['id' => 3]);
    }

    /**
     * @return void
     */
    public function test_get_public_playlist(): void {
        $this->setup_user();
        $playlist = $this->create_playlist(['access' => access::PUBLIC]);

        $result = $this->execute_query(['id' => $playlist->get_id()]);
        self::assertNotEmpty($result);
        self::assertEquals($playlist->get_id(), $result->get_id());

        // Anyone can get public playlist.
        $this->setup_user();
        $result = $this->execute_query(['id' => $playlist->get_id()]);
        self::assertNotEmpty($result);
        self::assertEquals($playlist->get_id(), $result->get_id());
    }

    /**
     * @return void
     */
    public function test_get_private_playlist(): void {
        $this->setup_user();
        $playlist = $this->create_playlist();

        $result = $this->execute_query(['id' => $playlist->get_id()]);
        self::assertNotEmpty($result);
        self::assertEquals($playlist->get_id(), $result->get_id());

        // Admin can get playlist.
        $this->setAdminUser();
        $result = $this->execute_query(['id' => $playlist->get_id()]);
        self::assertNotEmpty($result);
        self::assertEquals($playlist->get_id(), $result->get_id());

        // Non-creator can not get playlist.
        $this->setup_user();
        $this->expectException(coding_exception::class);
        $this->execute_query(['id' => $playlist->get_id()]);
    }

    /**
     * @return void
     */
    public function test_get_restrict_playlist(): void {
        $this->setup_user();
        $playlist = $this->create_playlist(['access' => access::RESTRICTED]);

        $result = $this->execute_query(['id' => $playlist->get_id()]);
        self::assertNotEmpty($result);
        self::assertEquals($playlist->get_id(), $result->get_id());

        // Admin can get playlist.
        $this->setAdminUser();
        $result = $this->execute_query(['id' => $playlist->get_id()]);
        self::assertNotEmpty($result);
        self::assertEquals($playlist->get_id(), $result->get_id());

        // Non-creator can not get playlist.
        $this->setup_user();
        $this->expectException(coding_exception::class);
        $this->execute_query(['id' => $playlist->get_id()]);
    }

    /**
     * @return void
     */
    public function test_get_playlist_by_recipient(): void {
        $user = $this->setup_user();
        $playlist = $this->create_playlist(['access' => access::RESTRICTED]);

        $recipient = $this->getDataGenerator()->create_user();

        // Setup recipients.
        $playlistgen = $this->getDataGenerator()->get_plugin_generator('totara_playlist');
        $recipients = $playlistgen->create_user_recipients([$recipient]);

        $this->setUser($user);
        $playlistgen->share_playlist($playlist, $recipients);

        $this->setUser($recipient);
        $result = $this->execute_query(['id' => $playlist->get_id()]);
        self::assertNotEmpty($result);
        self::assertEquals($playlist->get_id(), $result->get_id());
    }

    private function execute_query(array $args) {
        return $this->resolve_graphql_query('totara_playlist_instance', $args);
    }

    private function setup_user() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        return $user;
    }

    private function create_playlist(?array $params = []): playlist {
        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $this->getDataGenerator()->get_plugin_generator('totara_playlist');
        return $playlist_generator->create_playlist($params);
    }
}