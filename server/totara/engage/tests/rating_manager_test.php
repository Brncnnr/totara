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
 * @package totara_engage
 */
defined('MOODLE_INTERNAL') || die();

use totara_engage\access\access;
use totara_engage\rating\rating_manager;

class totara_engage_rating_manager_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_add_rating(): void {
        global $DB;

        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $this->setUser($user);

        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');

        $playlist = $playlistgen->create_playlist([
            'access' => access::PUBLIC
        ]);

        $rating_manager = rating_manager::instance($playlist->get_id(), 'totara_playlist', 'playlist');

        /** @var \totara_engage\entity\rating $rating */
        $rating = $rating_manager->add(3);

        $this->assertEquals(3, $rating->rating);
        $this->assertEquals('playlist', $rating->area);
        $this->assertEquals('totara_playlist', $rating->component);
        $this->assertEquals($playlist->get_id(), $rating->instanceid);

    }

    /**
     * @return void
     */
    public function test_delete_rating(): void {
        global $DB;

        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $this->setUser($user);

        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');

        $playlist = $playlistgen->create_playlist([
            'access' => access::PUBLIC
        ]);

        $rating_manager = rating_manager::instance($playlist->get_id(), 'totara_playlist', 'playlist');
        $rating_manager->add(3);

        $rating_manager->delete();

        $sql = 'SELECT r.* 
                FROM {engage_rating} r 
                WHERE r.component = :component 
                AND r.instanceid = :instanceid 
                AND r.area = :area';

        $params = [
            'area' => 'playlist',
            'component' => 'totara_playlist',
            'instanceid' => $playlist->get_id()
        ];

        $this->assertFalse($DB->record_exists_sql($sql, $params));
        $this->assertFalse($DB->record_exists('engage_rating', ['instanceid' => $playlist->get_id()]));
    }

    /**
     * @return void
     */
    public function test_can_rate(): void {
        $gen = $this->getDataGenerator();

        // Owner of playlist.
        $owner = $gen->create_user();

        // Viewer of playlist.
        $viewer = $gen->create_user();
        $this->setUser($viewer);

        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');

        $playlist = $playlistgen->create_playlist([
            'access' => access::PUBLIC,
            'userid' => $owner->id
        ]);

        $rating_manager = rating_manager::instance($playlist->get_id(), 'totara_playlist', 'playlist');
        $viewer_actual = $rating_manager->can_rate($playlist->get_userid(), $viewer->id);
        $ower_actual = $rating_manager->can_rate($playlist->get_userid(), $owner->id);

        $this->assertNotEmpty($viewer_actual);
        $this->assertEquals(true, $viewer_actual);
        $this->assertEquals(false, $ower_actual);

        // Test viewer only can rate once.
        $rating_manager->add(3, $viewer->id);
        $viewer_actual = $rating_manager->can_rate($playlist->get_userid(), $viewer->id);
        $this->assertEquals(false, $ower_actual);
    }

    /**
     * @return void
     */
    public function test_get_rating(): void {
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $this->setUser($user);

        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');

        $playlist = $playlistgen->create_playlist([
            'access' => access::PUBLIC
        ]);

        $rating_manager = rating_manager::instance($playlist->get_id(), 'totara_playlist', 'playlist');
        $rating_manager->add(3);

        $ratings = $rating_manager->get();

        $this->assertNotEmpty($ratings);

        foreach ($ratings as $rating)  {
            $this->assertEquals(3, $rating->rating);
            $this->assertEquals('playlist', $rating->area);
            $this->assertEquals('totara_playlist', $rating->component);
            $this->assertEquals($playlist->get_id(), $rating->instanceid);
        }
    }

    /**
     * @return void
     */
    public function test_rating_count(): void {
        $gen = $this->getDataGenerator();
        $owner = $gen->create_user();
        $this->setUser($owner);

        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');
        $users = $playlistgen->create_users(3);

        $playlist = $playlistgen->create_playlist([
            'access' => access::PUBLIC,
            'userid' => $owner->id
        ]);

        $rating_manager = rating_manager::instance($playlist->get_id(), 'totara_playlist', 'playlist');
        foreach ($users as $user) {
            $rating_manager->add(3, $user->id);
        }

        $actual = $rating_manager->count();

        $this->assertNotEmpty($actual);
        $this->assertEquals(3, $actual);
    }

    /**
     * @return void
     */
    public function test_rating_summary(): void {
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $this->setUser($user);

        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');

        $playlist = $playlistgen->create_playlist([
            'access' => access::PUBLIC
        ]);

        $rating_manager = rating_manager::instance($playlist->get_id(), 'totara_playlist', 'playlist');
        $rating_manager->add(3);

        $actual = $rating_manager->summary();

        $this->assertEquals(true, $actual->rated);
        $this->assertEquals(3, $actual->rating);
        $this->assertEquals(1, $actual->count);
        $this->assertEquals($playlist->get_id(), $actual->itemid);
    }

    /**
     * @return void
     */
    public function test_rating_avg(): void {
        $gen = $this->getDataGenerator();
        $owner = $gen->create_user();
        $this->setUser($owner);

        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');
        $users = $playlistgen->create_users(3);

        $playlist = $playlistgen->create_playlist([
            'access' => access::PUBLIC,
            'userid' => $owner->id
        ]);

        $rating_manager = rating_manager::instance($playlist->get_id(), 'totara_playlist', 'playlist');

        $rating = 2;
        foreach ($users as $user) {
            $rating_manager->add($rating, $user->id);
            $rating++;
        }

        $actual = $rating_manager->avg();

        $this->assertNotEmpty($actual);
        $this->assertEquals(3, $actual);
    }
}