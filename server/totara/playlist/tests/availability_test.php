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

class totara_playlist_availability_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_deleted_user_should_make_playlist_unavailable(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist(['userid' => $user_one->id]);

        self::assertTrue($playlist->is_available());

        // Delete the user and check that if the playlist is available anymore
        delete_user($user_one);
        self::assertFalse($playlist->is_available());
    }

    /**
     * @return void
     */
    public function test_suspend_user_should_not_make_playlist_unavailable(): void {
        global $CFG;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist(['userid' => $user_one->id,]);

        self::assertTrue($playlist->is_available());

        // Suspend the user
        require_once("{$CFG->dirroot}/user/lib.php");
        user_suspend_user($user_one->id);

        // Suspend user should not make playlist unavailable.
        self::assertTrue($playlist->is_available());
    }
}