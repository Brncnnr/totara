<?php
/**
 * This file is part of Totara LMS
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_playlist
 */

defined('MOODLE_INTERNAL') || die();

use totara_catalog\provider_handler;
use totara_engage\access\access;
use totara_playlist\playlist;
use totara_playlist\event\playlist_updated;

class totara_playlist_catalog_visibility_testcase extends advanced_testcase {

    private $users = null;
    private $playlists = null;

    public function setUp(): void {
        $generator = $this->getDataGenerator();
        $playlistgen = $generator->get_plugin_generator('totara_playlist');

        // Create some test users.
        $this->users = [];
        for ($i = 1; $i <= 4; $i++) {
            $this->users[$i] = $generator->create_user();
        }

        // Create a couple of Private playlists as controls.
        $this->playlists = [];
        $params = [
            'name' => 'Private Playlist 2',
            'userid' => $this->users[2]->id,
            'contextid' => \context_user::instance($this->users[2]->id)->id,
            'access' => access::PRIVATE,
            'summary' => 'Playlist 2 description'
        ];
        $this->playlists[2] = $playlistgen->create_playlist($params);

        $params = [
            'name' => 'Private Playlist 3',
            'userid' => $this->users[3]->id,
            'contextid' => \context_user::instance($this->users[3]->id)->id,
            'access' => access::PRIVATE,
            'summary' => 'Playlist 3 description'
        ];
        $this->playlists[3] = $playlistgen->create_playlist($params);
    }

    public function tearDown(): void {
        $this->users = null;
        $this->playlists = null;
    }

    private function setup_object($id) {
        $object = new stdClass();
        $object->objectid = $id;
        return [$object];
    }

    /**
     * @return void
     */
    public function test_playlist_cache_all_visibility(): void {
        // Set up an playlist with open access to everyone.
        $generator = $this->getDataGenerator();
        $playlistgen = $generator->get_plugin_generator('totara_playlist');

        $params = [
            'name' => 'Public Playlist 1',
            'userid' => $this->users[1]->id,
            'contextid' => \context_user::instance($this->users[1]->id)->id,
            'access' => access::PUBLIC,
            'summary' => 'Playlist 1 description'
        ];
        $playlist = $playlistgen->create_playlist($params);

        // First get the catalog provider for engage playlists.
        $providerhandler = provider_handler::instance();
        $provider = $providerhandler->get_provider('playlist');

        // Next prime the caches to make sure the data is there.
        $this->setUser($this->users[1]->id);
        $provider->prime_provider_cache();

        // Finally run through the can_see() function to check expected results.
        $u1data = [
            $playlist->get_id() => true,
            $this->playlists[2]->get_id() => false,
            $this->playlists[3]->get_id() => false,
        ];

        foreach ($u1data as $playlistid => $expectation) {
            $cansee = $provider->can_see($this->setup_object($playlistid));
            $this->assertSame($expectation, $cansee[$playlistid]);
        }

        $this->setUser($this->users[2]->id);
        $provider->prime_provider_cache();

        $u2data = [
            $playlist->get_id() => true,
            $this->playlists[2]->get_id() => true,
            $this->playlists[3]->get_id() => false,
        ];

        foreach ($u2data as $playlistid => $expectation) {
            $cansee = $provider->can_see($this->setup_object($playlistid));
            $this->assertSame($expectation, $cansee[$playlistid]);
        }

        $this->setUser($this->users[3]->id);
        $provider->prime_provider_cache();

        $u3data = [
            $playlist->get_id() => true,
            $this->playlists[2]->get_id() => false,
            $this->playlists[3]->get_id() => true,
        ];

        foreach ($u3data as $playlistid => $expectation) {
            $cansee = $provider->can_see($this->setup_object($playlistid));
            $this->assertSame($expectation, $cansee[$playlistid]);
        }
    }

    public function test_playlist_object_update(): void {
        global $DB;

        // Set up an playlist with open access to everyone.
        $generator = $this->getDataGenerator();
        $playlistgen = $generator->get_plugin_generator('totara_playlist');

        $params = [
            'name' => 'Public Playlist 1',
            'userid' => $this->users[1]->id,
            'contextid' => \context_user::instance($this->users[1]->id)->id,
            'access' => access::PUBLIC,
            'summary' => 'Playlist 1 description'
        ];
        $playlist = $playlistgen->create_playlist($params);

        // First check we have the expected number of items and the test item has the expected data.
        $items = $DB->get_records('catalog', ['objecttype' => 'playlist']);
        $this->assertCount(3, $items);
        foreach ($items as $item) {
            if ($item->objectid == $playlist->get_id()) {
                $this->assertSame($params['name'], $item->ftshigh);
            }
        }

        // Now manually update the item
        $DB->set_field('playlist', 'name', 'Updated Content 1', ['id' => $playlist->get_id()]);

        // Check that it hasn't updated the catalog record
        $items = $DB->get_records('catalog', ['objecttype' => 'playlist']);
        $this->assertCount(3, $items);
        foreach ($items as $item) {
            if ($item->objectid == $playlist->get_id()) {
                $this->assertSame($params['name'], $item->ftshigh);
            }
        }

        // Trigger the update event
        $a1 = playlist::from_id($playlist->get_id());
        $e1 = playlist_updated::from_playlist($a1);
        $e1->trigger();

        // Finally check that the catalog record has been updated
        $items = $DB->get_records('catalog', ['objecttype' => 'playlist']);
        $this->assertCount(3, $items);
        foreach ($items as $item) {
            if ($item->objectid == $playlist->get_id()) {
                $this->assertSame('Updated Content 1', $item->ftshigh);
            }
        }
    }

    /**
     * @return void
     */
    public function test_playlist_cache_update(): void {
        global $DB;

        // Set up an playlist with open access to everyone.
        $generator = $this->getDataGenerator();
        $playlistgen = $generator->get_plugin_generator('totara_playlist');

        $params = [
            'name' => 'Public Playlist 1',
            'userid' => $this->users[1]->id,
            'contextid' => \context_user::instance($this->users[1]->id)->id,
            'access' => access::PUBLIC,
            'summary' => 'Playlist 1 description'
        ];
        $playlist = $playlistgen->create_playlist($params);
        $playlist2 = $this->playlists[2];

        // First get the catalog provider for engage playlists.
        $providerhandler = provider_handler::instance();
        $provider = $providerhandler->get_provider('playlist');

        // Next prime the caches to make sure the data is there.
        $this->setUser($this->users[1]->id);
        $provider->prime_provider_cache();

        // Check the initial visibilty.
        $u1data = [
            $playlist->get_id() => true,
            $playlist2->get_id() => false,
            $this->playlists[3]->get_id() => false,
        ];

        foreach ($u1data as $playlistid => $expectation) {
            $cansee = $provider->can_see($this->setup_object($playlistid));
            $this->assertSame($expectation, $cansee[$playlistid]);
        }

        $this->setUser($this->users[2]->id);
        $provider->prime_provider_cache();
        $u2data = [
            $playlist->get_id() => true,
            $playlist2->get_id() => true,
            $this->playlists[3]->get_id() => false,
        ];

        foreach ($u2data as $playlistid => $expectation) {
            $cansee = $provider->can_see($this->setup_object($playlistid));
            $this->assertSame($expectation, $cansee[$playlistid]);
        }

        // Update visibility and trigger events
        $DB->set_field('playlist', 'access', access::PRIVATE, ['id' => $playlist->get_id()]);
        $p1 = playlist::from_id($playlist->get_id());
        $e1 = playlist_updated::from_playlist($p1);
        $e1->trigger();

        $DB->set_field('playlist', 'access', access::PUBLIC, ['id' => $playlist2->get_id()]);
        $p2 = playlist::from_id($playlist2->get_id());
        $e2 = playlist_updated::from_playlist($p2);
        $e2->trigger();

        $this->setUser($this->users[1]->id);
        $provider->prime_provider_cache();

        $cansee = $provider->can_see($this->setup_object($playlist->get_id()));
        $this->assertSame($cansee[$playlist->get_id()], true);

        $cansee = $provider->can_see($this->setup_object($playlist2->get_id()));
        $this->assertSame($cansee[$playlist2->get_id()], true);

        $this->setUser($this->users[2]->id);
        $provider->prime_provider_cache();
        $cansee = $provider->can_see($this->setup_object($playlist->get_id()));
        $this->assertSame($cansee[$playlist->get_id()], false);

        $cansee = $provider->can_see($this->setup_object($playlist2->get_id()));
        $this->assertSame($cansee[$playlist2->get_id()], true);
    }

    /**
     * @return void
     */
    public function test_playlist_cache_no_visibility(): void {
        // Set up an playlist with open access to everyone.
        $generator = $this->getDataGenerator();
        $playlistgen = $generator->get_plugin_generator('totara_playlist');

        $params = [
            'name' => 'Public Playlist 1',
            'userid' => $this->users[1]->id,
            'contextid' => \context_user::instance($this->users[1]->id)->id,
            'access' => access::PRIVATE,
            'summary' => 'Playlist 1 description'
        ];
        $playlist = $playlistgen->create_playlist($params);

        // First get the catalog provider for engage playlists.
        $providerhandler = provider_handler::instance();
        $provider = $providerhandler->get_provider('playlist');

        // Next prime the caches to make sure the data is there.
        $this->setUser($this->users[1]->id);
        $provider->prime_provider_cache();

        // Finally run through the can_see() function to check expected results.
        $u1data = [
            $playlist->get_id() => true,
            $this->playlists[2]->get_id() => false,
            $this->playlists[3]->get_id() => false,
        ];

        foreach ($u1data as $playlistid => $expectation) {
            $cansee = $provider->can_see($this->setup_object($playlistid));
            $this->assertSame($expectation, $cansee[$playlistid]);
        }

        $this->setUser($this->users[2]->id);
        $provider->prime_provider_cache();

        $u2data = [
            $playlist->get_id() => false,
            $this->playlists[2]->get_id() => true,
            $this->playlists[3]->get_id() => false,
        ];

        foreach ($u2data as $playlistid => $expectation) {
            $cansee = $provider->can_see($this->setup_object($playlistid));
            $this->assertSame($expectation, $cansee[$playlistid]);
        }

        $this->setUser($this->users[3]->id);
        $provider->prime_provider_cache();

        $u3data = [
            $playlist->get_id() => false,
            $this->playlists[2]->get_id() => false,
            $this->playlists[3]->get_id() => true,
        ];

        foreach ($u3data as $playlistid => $expectation) {
            $cansee = $provider->can_see($this->setup_object($playlistid));
            $this->assertSame($expectation, $cansee[$playlistid]);
        }
    }

    /**
     * @return void
     */
    public function test_playlist_cache_restricted_visibility(): void {
        global $DB;

        // Set up an playlist with open access to everyone.
        $generator = $this->getDataGenerator();
        $playlistgen = $generator->get_plugin_generator('totara_playlist');

        $params = [
            'name' => 'Public Playlist 1',
            'userid' => $this->users[1]->id,
            'contextid' => \context_user::instance($this->users[1]->id)->id,
            'access' => access::RESTRICTED,
            'summary' => 'Playlist 1 description'
        ];
        $playlist = $playlistgen->create_playlist($params);

        // Create recipients.
        $recipients = $playlistgen->create_user_recipients([$this->users[2], $this->users[4]]);

        // Share playlists.
        $this->setUser($this->users[1]);
        $playlistgen->share_playlist($playlist, $recipients);

        // First get the catalog provider for engage playlists.
        $providerhandler = provider_handler::instance();
        $provider = $providerhandler->get_provider('playlist');

        // Next prime the caches to make sure the data is there.
        $provider->prime_provider_cache();

        // Finally run through the can_see() function to check expected results.
        $this->setUser($this->users[1]->id);
        $u1data = [
            $playlist->get_id() => true,
            $this->playlists[2]->get_id() => false,
            $this->playlists[3]->get_id() => false,
        ];

        foreach ($u1data as $playlistid => $expectation) {
            $cansee = $provider->can_see($this->setup_object($playlistid));
            $this->assertSame($expectation, $cansee[$playlistid]);
        }

        $this->setUser($this->users[2]->id);
        $provider->prime_provider_cache();
        $u2data = [
            $playlist->get_id() => true,
            $this->playlists[2]->get_id() => true,
            $this->playlists[3]->get_id() => false,
        ];

        foreach ($u2data as $playlistid => $expectation) {
            $cansee = $provider->can_see($this->setup_object($playlistid));
            $this->assertSame($expectation, $cansee[$playlistid]);
        }

        $this->setUser($this->users[3]->id);
        $provider->prime_provider_cache();

        $u3data = [
            $playlist->get_id() => false,
            $this->playlists[2]->get_id() => false,
            $this->playlists[3]->get_id() => true,
        ];

        foreach ($u3data as $playlistid => $expectation) {
            $cansee = $provider->can_see($this->setup_object($playlistid));
            $this->assertSame($expectation, $cansee[$playlistid]);
        }
    }
}
