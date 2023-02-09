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
use totara_playlist\playlist;

class totara_playlist_delete_check_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_check_can_delete_playlist_by_other_user(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $playlist = playlist::create(
            'Playlist 101',
            access::PUBLIC,
            null,
            $user_one->id
        );

        self::assertFalse($playlist->can_delete($user_two->id));
        self::assertTrue($playlist->can_delete($user_one->id));
    }

    /**
     * @return void
     */
    public function test_check_can_delete_playlist_by_user_with_capability(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $playlist = playlist::create(
            'Playlist 201',
            access::PUBLIC,
            null,
            $user_one->id
        );

        self::assertFalse($playlist->can_delete($user_two->id));
        self::assertTrue($playlist->can_delete($user_one->id));

        // Assign user two to a site manager role.
        $roles = get_archetype_roles('manager');
        $context_system = context_system::instance();

        foreach ($roles as $role) {
            role_assign($role->id, $user_two->id, $context_system->id);
        }

        self::assertTrue($playlist->can_delete($user_two->id));
        self::assertTrue($playlist->can_delete($user_one->id));
    }
}