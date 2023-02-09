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

use core\event\manager as event_manager;
use totara_engage\access\access;
use totara_catalog\catalog_retrieval;
use totara_playlist\playlist;

class totara_playlist_catalog_fetch_playlist_testcase extends advanced_testcase {
    /**
     * @return void
     */
    protected function tearDown(): void {
        parent::tearDown();
        event_manager::phpunit_reset();
    }

    /**
     * @return void
     */
    public function test_fetch_playlists_of_deleted_user(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlists = [];

        // Create 5 playlists for each of the users.
        for ($i = 0; $i < 5; $i++) {
            $user_one_playlist = $playlist_generator->create_playlist([
                'userid' => $user_one->id,
                'access' => access::PUBLIC
            ]);

            $user_two_playlist = $playlist_generator->create_playlist([
                'userid' => $user_two->id,
                'access' => access::PUBLIC
            ]);

            $playlists[] = $user_one_playlist->get_id();
            $playlists[] = $user_two_playlist->get_id();
        }

        // Set as user two to fetch the catalog.
        $this->setUser($user_two);

        $retrieval = new catalog_retrieval();
        $before_delete_result = $retrieval->get_page_of_objects(10, 0);

        self::assertObjectHasAttribute('objects', $before_delete_result);
        self::assertIsArray($before_delete_result->objects);

        // 10 of the articles in total.
        self::assertCount(10, $before_delete_result->objects);

        foreach ($before_delete_result->objects as $record) {
            self::assertContainsEquals($record->objectid, $playlists);

            $playlist = playlist::from_id($record->objectid);
            self::assertContainsEquals($playlist->get_userid(), [$user_one->id, $user_two->id]);
        }

        // Start deleting the user one to see if the catalog for playlist is fetching correctly.
        // But first, clear the event observers so that the test is accurate.
        event_manager::phpunit_replace_observers([]);

        delete_user($user_one);
        $after_delete_result = $retrieval->get_page_of_objects(10, 0);

        self::assertObjectHasAttribute('objects', $after_delete_result);
        self::assertIsArray($after_delete_result->objects);

        // Only 5 playlists left from user two after user one is deleted.
        self::assertCount(5, $after_delete_result->objects);

        foreach ($after_delete_result->objects as $record) {
            self::assertContainsEquals($record->objectid, $playlists);

            $playlist = playlist::from_id($record->objectid);

            self::assertEquals($user_two->id, $playlist->get_userid());
            self::assertNotEquals($user_one->id, $playlist->get_userid());
        }
    }

    /**
     * @return void
     */
    public function test_fetch_playlists_of_suspended_user(): void {
        global $CFG;
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlists = [];

        // Create 5 playlists for each of the users.
        for ($i = 0; $i < 5; $i++) {
            $user_one_playlist = $playlist_generator->create_playlist([
                'userid' => $user_one->id,
                'access' => access::PUBLIC
            ]);

            $user_two_playlist = $playlist_generator->create_playlist([
                'userid' => $user_two->id,
                'access' => access::PUBLIC
            ]);

            $playlists[] = $user_one_playlist->get_id();
            $playlists[] = $user_two_playlist->get_id();
        }

        // Set as user two to fetch the catalog.
        $this->setUser($user_two);

        $retrieval = new catalog_retrieval();
        $before_suspend_result = $retrieval->get_page_of_objects(10, 0);

        self::assertObjectHasAttribute('objects', $before_suspend_result);
        self::assertIsArray($before_suspend_result->objects);

        // 10 of the articles in total.
        self::assertCount(10, $before_suspend_result->objects);

        foreach ($before_suspend_result->objects as $record) {
            self::assertContainsEquals($record->objectid, $playlists);

            $playlist = playlist::from_id($record->objectid);
            self::assertContainsEquals($playlist->get_userid(), [$user_one->id, $user_two->id]);
        }

        // Start suspending the user.
        require_once("{$CFG->dirroot}/user/lib.php");
        user_suspend_user($user_one->id);

        // Suspend users should not enforce the catalog to remove the items from the list.
        $after_suspend_result = $retrieval->get_page_of_objects(10, 0);

        self::assertObjectHasAttribute('objects', $after_suspend_result);
        self::assertIsArray($after_suspend_result->objects);

        self::assertCount(10, $after_suspend_result->objects);

        foreach ($after_suspend_result->objects as $record) {
            self::assertContainsEquals($record->objectid, $playlists);

            $playlist = playlist::from_id($record->objectid);
            self::assertContainsEquals($playlist->get_userid(), [$user_one->id, $user_two->id]);
        }
    }
}