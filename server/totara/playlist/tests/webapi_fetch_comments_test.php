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

use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_engage\access\access;
use totara_playlist\playlist;
use totara_comment\exception\comment_exception;
use core_user\totara_engage\share\recipient\user;

class totara_playlist_webapi_fetch_comments_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_fetch_comments_of_private_playlist_by_viewer(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist([
            'userid' => $user_one->id,
            'access' => access::PRIVATE
        ]);

        // Log in as different user and fetch the comments of this playlist.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $this->expectException(comment_exception::class);
        $this->expectExceptionMessage(get_string('error:accessdenied', 'totara_comment'));

        $this->resolve_graphql_query(
            'totara_comment_comments',
            [
                'instanceid' => $playlist->get_id(),
                'component' => playlist::get_resource_type(),
                'area' => playlist::COMMENT_AREA
            ]
        );
    }

    /**
     * @return void
     */
    public function test_fetch_comments_of_public_playlist_by_viewer(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist([
            'userid' => $user_one->id,
            'access' => access::PUBLIC
        ]);

        // Log in as second user to fetch the playlist.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $comments = $this->resolve_graphql_query(
            'totara_comment_comments',
            [
                'instanceid' => $playlist->get_id(),
                'component' => playlist::get_resource_type(),
                'area' => playlist::COMMENT_AREA
            ]
        );

        self::assertIsArray($comments);
        self::assertEmpty($comments);
    }

    /**
     * @return void
     */
    public function test_fetch_comments_of_restricted_playlist_by_non_shared_viewer(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist([
            'userid' => $user_one->id,
            'access' => access::RESTRICTED
        ]);

        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $this->expectException(comment_exception::class);
        $this->expectExceptionMessage(get_string('error:accessdenied', 'totara_comment'));

        $this->resolve_graphql_query(
            'totara_comment_comments',
            [
                'instanceid' => $playlist->get_id(),
                'component' => playlist::get_resource_type(),
                'area' => playlist::COMMENT_AREA
            ]
        );
    }

    /**
     * @return void
     */
    public function test_fetch_comments_of_restricted_playlist_by_shared_viewer(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist([
            'userid' => $user_one->id,
            'access' => access::RESTRICTED
        ]);

        // Share playlist to user two.
        $user_two = $generator->create_user();
        $playlist_generator->share_playlist($playlist, [new user($user_two->id)]);

        // Fetch the comments.
        $this->setUser($user_two);
        $comments = $this->resolve_graphql_query(
            'totara_comment_comments',
            [
                'instanceid' => $playlist->get_id(),
                'component' => playlist::get_resource_type(),
                'area' => playlist::COMMENT_AREA
            ]
        );

        self::assertIsArray($comments);
        self::assertEmpty($comments);
    }
}