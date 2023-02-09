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

use totara_playlist\playlist;
use totara_engage\access\access;

class totara_playlist_share_check_testcase extends advanced_testcase {
    /**
     * Checking whether user has the capability to share or not.
     * This does not apply whether the playlist is share-able or not.
     *
     * @return void
     */
    public function test_check_can_share_of_private_playlist(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $user_one_context = context_user::instance($user_one->id);
        $playlist = playlist::create(
            "This is playlist",
            access::PRIVATE,
            $user_one_context->id,
            $user_one->id
        );

        self::assertTrue($playlist->can_share($user_two->id));
        self::assertTrue($playlist->can_share($user_one->id));

        // Remove capability for user two in user one context.
        $roles = get_archetype_roles('user');
        $role = reset($roles);

        // This will also affect the owner.
        assign_capability('totara/playlist:share', CAP_PREVENT, $role->id, $user_one_context->id);
        self::assertFalse($playlist->can_share($user_two->id));
        self::assertFalse($playlist->can_share($user_one->id));
    }

    /**
     * Checking whether user has the capability to share or not.
     * This does not apply whether the playlist is share-able or not.
     *
     * @return void
     */
    public function test_check_can_share_of_public_playlist(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $user_one_context = context_user::instance($user_one->id);
        $playlist = playlist::create(
            "This is playlist",
            access::PUBLIC,
            $user_one_context->id,
            $user_one->id
        );

        self::assertTrue($playlist->can_share($user_two->id));
        self::assertTrue($playlist->can_share($user_one->id));

        // Remove capability for user two in user one context.
        $roles = get_archetype_roles('user');
        $role = reset($roles);

        // This will also affect the owner.
        assign_capability('totara/playlist:share', CAP_PREVENT, $role->id, $user_one_context->id);
        self::assertFalse($playlist->can_share($user_two->id));
        self::assertFalse($playlist->can_share($user_one->id));
    }
}