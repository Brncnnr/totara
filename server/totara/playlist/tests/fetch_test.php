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
use totara_playlist\query\option\playlist_source;
use totara_playlist\playlist;
use totara_engage\bookmark\bookmark;

class totara_playlist_fetch_testcase extends advanced_testcase {
    /**
     * @var string
     */
    private const PLAYLIST_NAME = 'Playlist';

    /**
     * @param int $user_id
     * @return playlist[]
     */
    private function create_different_accesses_playlist(int $user_id): array {
        $gen = $this->getDataGenerator();

        /** @var \totara_playlist\testing\generator $playlist_gen */
        $playlist_gen = $gen->get_plugin_generator('totara_playlist');
        $accesses = [
            access::PUBLIC,
            access::RESTRICTED,
            access::PRIVATE
        ];

        $name = static::PLAYLIST_NAME;
        $playlists = [];

        foreach ($accesses as $access) {
            $playlists[] = $playlist_gen->create_playlist([
                'name' => "$name {$user_id}__{$access}",
                'access' => $access,
                'userid' => $user_id
            ]);
        }

        return $playlists;
    }

    /**
     * Given nine playlists of three different users:
     * + Playlist {user_id}__1 - User one - public access
     * + Playlist {user_id}__2 - User one - restricted access
     * + Playlist {user_id}__0 - User one - private access
     *
     * + Playlist {user_id}__1 - User two - public access
     * + Playlist {user_id}__2 - User two - restricted access
     * + Playlist {user_id}__0 - User two - private access
     *
     * + Playlist {user_id}__1 - User three - public access
     * + Playlist {user_id}__2 - User three - restricted access
     * + Playlist {user_id}__0 - User three - private access
     *
     * The actor is User one, and this test is to check if the query return owned playlists.
     * Expecting that the query will just return the 3 playlists of user one.
     *
     * @return void
     */
    public function test_get_own_playlists(): void {
        $gen = $this->getDataGenerator();

        $user_one = $gen->create_user();
        $this->create_different_accesses_playlist($user_one->id);
        $this->setUser($user_one);

        $user_two = $gen->create_user();
        $this->create_different_accesses_playlist($user_two->id);

        $user_three = $gen->create_user();
        $this->create_different_accesses_playlist($user_three->id);

        $query = new playlist_query($user_one->id);
        $query->set_source(playlist_source::OWN);

        $result = playlist_loader::get_playlists($query);

        $this->assertEquals(3, $result->get_total());
    }

    /**
     * Given nine playlists of three different users:
     * + Playlist {user_id}__1 - User one - public access
     * + Playlist {user_id}__2 - User one - restricted access
     * + Playlist {user_id}__0 - User one - private access
     *
     * + Playlist {user_id}__1 - User two - public access
     * + Playlist {user_id}__2 - User two - restricted access
     * + Playlist {user_id}__0 - User two - private access
     *
     * + Playlist {user_id}__1 - User three - public access
     * + Playlist {user_id}__2 - User three - restricted access
     * + Playlist {user_id}__0 - User three - private access
     *
     * The actor is User one, and this test is to check if the query return all the playlists.
     * Expecting that the query will just return the 5 playlists, which it is excluding the two private playlists
     * and excluding the two restricted playlists that are not share with the user one.
     *
     * @return void
     */
    public function test_get_all_playlists_that_user_can_see(): void {
        $gen = $this->getDataGenerator();

        $user_one = $gen->create_user();
        $this->create_different_accesses_playlist($user_one->id);
        $this->setUser($user_one);

        $user_two = $gen->create_user();
        $this->create_different_accesses_playlist($user_two->id);

        $user_three = $gen->create_user();
        $this->create_different_accesses_playlist($user_three->id);

        $query = new playlist_query($user_one->id);
        $result = playlist_loader::get_playlists($query);

        $this->assertEquals(5, $result->get_total());
        $playlists = $result->get_items()->all();

        foreach ($playlists as $playlist) {
            $this->assertInstanceOf(playlist::class, $playlist);
        }
    }

    /**
     * Given nine playlists of three different users:
     * + Playlist {user_id}__1 - User one - public access
     * + Playlist {user_id}__2 - User one - restricted access
     * + Playlist {user_id}__0 - User one - private access
     *
     * + Playlist {user_id}__1 - User two - public access
     * + Playlist {user_id}__2 - User two - restricted access
     * + Playlist {user_id}__0 - User two - private access
     *
     * + Playlist {user_id}__1 - User three - public access
     * + Playlist {user_id}__2 - User three - restricted access
     * + Playlist {user_id}__0 - User three - private access
     *
     * The restricted playlists of user two and three will be shared to user one.
     *
     * The actor is User one, and this test is to check if the query return all the shared playlists to the user one
     * or not. Expecting that the query will return the 7 playlists. 5 visible playlists plus 2 shared playlist.
     *
     * @return void
     */
    public function test_get_all_playlists_that_user_can_see_with_shared(): void {
        $gen = $this->getDataGenerator();

        /** @var \totara_engage\testing\generator $engage_gen */
        $engage_gen = $gen->get_plugin_generator('totara_engage');

        $user_one = $gen->create_user();
        $this->create_different_accesses_playlist($user_one->id);
        $this->setUser($user_one);

        $user_two = $gen->create_user();
        $user_two_playlists = $this->create_different_accesses_playlist($user_two->id);

        $user_three = $gen->create_user();
        $user_three_playlists = $this->create_different_accesses_playlist($user_three->id);

        $playlists = array_merge($user_two_playlists, $user_three_playlists);

        /** @var playlist $playlist */
        foreach ($playlists as $playlist) {
            if ($playlist->is_restricted()) {
                $recipient = $engage_gen->create_user_recipients([$user_one]);
                $engage_gen->share_item($playlist, $playlist->get_userid(), $recipient);
            }
        }

        $query = new playlist_query($user_one->id);
        $result = playlist_loader::get_playlists($query);

        $this->assertEquals(7, $result->get_total());
    }

    /**
     * Given nine playlists of three different users:
     * + Playlist {user_id}__1 - User one - public access
     * + Playlist {user_id}__2 - User one - restricted access
     * + Playlist {user_id}__0 - User one - private access
     *
     * + Playlist {user_id}__1 - User two - public access
     * + Playlist {user_id}__2 - User two - restricted access
     * + Playlist {user_id}__0 - User two - private access
     *
     * + Playlist {user_id}__1 - User three - public access
     * + Playlist {user_id}__2 - User three - restricted access
     * + Playlist {user_id}__0 - User three - private access
     *
     * The restricted playlists of user two and three will be shared to user one, and these playlists
     * are bookmark by user one. Plus that the public playlist of user three will be bookmark by user one.
     *
     * Expecting the user's one to see 3 playlists, 2 restricted that are bookmarked plus the public playlist.
     *
     * @return void
     */
    public function test_get_book_mark_only(): void {
        $gen = $this->getDataGenerator();

        /** @var \totara_engage\testing\generator $engage_gen */
        $engage_gen = $gen->get_plugin_generator('totara_engage');

        $user_one = $gen->create_user();
        $this->create_different_accesses_playlist($user_one->id);
        $this->setUser($user_one);

        $user_two = $gen->create_user();
        $user_two_playlists = $this->create_different_accesses_playlist($user_two->id);

        $user_three = $gen->create_user();
        $user_three_playlists = $this->create_different_accesses_playlist($user_three->id);

        $recipients = $engage_gen->create_user_recipients([$user_one]);

        foreach ($user_two_playlists as $playlist) {
            if ($playlist->is_restricted()) {
                $engage_gen->share_item($playlist, $playlist->get_userid(), $recipients);

                $bookmark = new bookmark($user_one->id, $playlist->get_id(), 'totara_playlist');
                $bookmark->add_bookmark();
            }
        }

        foreach ($user_three_playlists as $playlist) {
            if ($playlist->is_restricted() || $playlist->is_public()) {
                $engage_gen->share_item($playlist, $playlist->get_userid(), $recipients);

                $bookmark = new bookmark($user_one->id, $playlist->get_id(), 'totara_playlist');
                $bookmark->add_bookmark();
            }
        }

        $query = new playlist_query($user_one->id);
        $query->set_source(playlist_source::BOOKMARKED);

        $result = playlist_loader::get_playlists($query);
        $this->assertEquals(3, $result->get_total());
    }
}