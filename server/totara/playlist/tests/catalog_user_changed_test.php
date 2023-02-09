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
use core\event\manager as event_manager;
use totara_catalog\task\refresh_catalog_adhoc;

class totara_playlist_catalog_user_changed_testcase extends advanced_testcase {
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
    public function test_user_delete_should_remove_catalog_items(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');

        // Create 3 playlists for the user one.
        for ($i = 0; $i < 3; $i++) {
            $playlist_generator->create_playlist([
                'userid' => $user_one->id,
                'access' => access::PUBLIC
            ]);
        }

        self::assertEquals(3, $DB->count_records('catalog'));

        // Delete the user to see if the record is still existing.
        delete_user($user_one);
        self::assertEquals(0, $DB->count_records('catalog'));
    }

    /**
     * @return void
     */
    public function test_user_suspended_should_not_remove_catalog_items(): void {
        global $DB, $CFG;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');

        // Create 3 playlists for user one.
        for ($i = 0; $i < 3; $i++) {
            $playlist_generator->create_playlist([
                'userid' => $user_one->id,
                'access' => access::PUBLIC
            ]);
        }

        self::assertEquals(3, $DB->count_records('catalog'));

        // Start suspend the user.
        require_once("{$CFG->dirroot}/user/lib.php");
        user_suspend_user($user_one->id);

        self::assertEquals(3, $DB->count_records('catalog'));
    }

    /**
     * @return void
     */
    public function test_reload_catalog_after_suspend_user(): void {
        global $DB, $CFG;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');

        // Create 3 playlists for the user one.
        for ($i = 0; $i < 3; $i++) {
            $playlist_generator->create_playlist([
                'userid' => $user_one->id,
                'access' => access::PUBLIC
            ]);
        }

        self::assertEquals(3, $DB->count_records('catalog'));

        // Suspend the user but remove the event observer so that we can test it correctly.
        event_manager::phpunit_replace_observers([]);
        require_once("{$CFG->dirroot}/user/lib.php");

        user_suspend_user($user_one->id);

        // Since we removed the events observer, the catalog table should not be updated.
        self::assertEquals(3, $DB->count_records('catalog'));

        // Run calculate. The catalog table should not be changed at all since the user is only suspended.
        // And suspended user will be keeping the cotnent.
        $task = new refresh_catalog_adhoc();
        $task->execute();

        self::assertEquals(3, $DB->count_records('catalog'));
    }

    /**
     * @return void
     */
    public function test_reload_catalog_after_delete_user(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');

        // Create 3 playlists for user one.
        for ($i = 0; $i < 3; $i++) {
            $playlist_generator->create_playlist([
                'userid' => $user_one->id,
                'access' => access::PUBLIC
            ]);
        }

        self::assertEquals(3, $DB->count_records('catalog'));

        // Delete the user, however we need to clear the event observers first so that we can be sure
        // that our recalculation is running correctly.
        event_manager::phpunit_replace_observers([]);
        delete_user($user_one);


        // After delete user, the catalog should not be updated right away since observers are wiped.
        self::assertEquals(3, $DB->count_records('catalog'));

        // Run task to refresh catalog.
        $task = new refresh_catalog_adhoc();
        $task->execute();

        self::assertEquals(0, $DB->count_records('catalog'));
    }
}