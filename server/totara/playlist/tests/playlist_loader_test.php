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
use totara_engage\bookmark\bookmark;
use totara_playlist\query\playlist_query;
use totara_playlist\loader\playlist_loader;
use totara_playlist\query\option\playlist_source;
use totara_playlist\playlist;

class totara_playlist_playlist_loader_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_fetch_bookmarked_playlists_from_deleted_user(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        // Create 2 playlists for user two.
        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');

        $playlist_one = $playlist_generator->create_playlist([
            'userid' => $user_two->id,
            'access' => access::PUBLIC
        ]);

        $playlist_two = $playlist_generator->create_playlist([
            'userid' => $user_two->id,
            'access' => access::PUBLIC
        ]);

        // Bookmark these playlist.
        $playlist_one_bookmark = new bookmark($user_one->id, $playlist_one->get_id(), 'totara_playlist');
        $playlist_two_bookmark = new bookmark($user_one->id, $playlist_two->get_id(), 'totara_playlist');

        $playlist_one_bookmark->add_bookmark();
        $playlist_two_bookmark->add_bookmark();

        // Fetch the bookmarks for the user one.
        $query = new playlist_query($user_one->id);
        $query->set_source(playlist_source::BOOKMARKED);

        $before_delete_result = playlist_loader::get_playlists($query);
        self::assertEquals(2, $before_delete_result->get_total());

        /** @var playlist[] $before_delete_playlists */
        $before_delete_playlists = $before_delete_result->get_items()->all();
        foreach ($before_delete_playlists as $playlist) {
            self::assertContains(
                $playlist->get_id(),
                [
                    $playlist_one->get_id(),
                    $playlist_two->get_id()
                ]
            );
        }

        // Delete user two.
        delete_user($user_two);
        $after_delete_result = playlist_loader::get_playlists($query);

        self::assertEquals(0, $after_delete_result->get_total());
    }

    /**
     * @return void
     */
    public function test_fetch_bookmarked_playlists_from_suspended_user(): void {
        global $CFG;
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        // Create 2 playlists for user two.
        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');

        $playlist_one = $playlist_generator->create_playlist([
            'userid' => $user_two->id,
            'access' => access::PUBLIC
        ]);

        $playlist_two = $playlist_generator->create_playlist([
            'userid' => $user_two->id,
            'access' => access::PUBLIC
        ]);

        // Bookmark these playlist.
        $playlist_one_bookmark = new bookmark($user_one->id, $playlist_one->get_id(), 'totara_playlist');
        $playlist_two_bookmark = new bookmark($user_one->id, $playlist_two->get_id(), 'totara_playlist');

        $playlist_one_bookmark->add_bookmark();
        $playlist_two_bookmark->add_bookmark();

        // Fetch the bookmarks for the user one.
        $query = new playlist_query($user_one->id);
        $query->set_source(playlist_source::BOOKMARKED);

        $before_suspended_result = playlist_loader::get_playlists($query);
        self::assertEquals(2, $before_suspended_result->get_total());

        /** @var playlist[] $before_suspended_playlists */
        $before_suspended_playlists = $before_suspended_result->get_items()->all();
        foreach ($before_suspended_playlists as $playlist) {
            self::assertContains(
                $playlist->get_id(),
                [
                    $playlist_one->get_id(),
                    $playlist_two->get_id()
                ]
            );
        }

        // Delete user two.
        require_once("{$CFG->dirroot}/user/lib.php");
        user_suspend_user($user_two->id);

        $after_suspended_result = playlist_loader::get_playlists($query);

        self::assertEquals(0, $after_suspended_result->get_total());
    }
}