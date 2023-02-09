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
use totara_playlist\playlist;
use totara_comment\exception\comment_exception;
use totara_engage\access\access;
use core_user\totara_engage\share\recipient\user;

class totara_playlist_webapi_fetch_replies_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_fetch_replies_of_private_playlist_by_viewer(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist([
            'userid' => $user_one->id,
            'access' => access::PRIVATE
        ]);

        $this->setUser($user_one);

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment = $comment_generator->create_comment(
            $playlist->get_id(),
            playlist::get_resource_type(),
            playlist::COMMENT_AREA
        );

        // Log in as second user to fetch the replies.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $this->expectException(comment_exception::class);
        $this->expectExceptionMessage(get_string('error:accessdenied', 'totara_comment'));

        $this->resolve_graphql_query(
            'totara_comment_replies',
            ['commentid' => $comment->get_id()]
        );
    }

    /**
     * @return void
     */
    public function test_fetch_replies_of_public_playlist_by_viewer(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist(['access' => access::PUBLIC]);

        // Create comments so that user two can fetch the replies.
        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment = $comment_generator->create_comment(
            $playlist->get_id(),
            playlist::get_resource_type(),
            playlist::COMMENT_AREA
        );

        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $replies = $this->resolve_graphql_query(
            'totara_comment_replies',
            ['commentid' => $comment->get_id()]
        );

        self::assertIsArray($replies);
        self::assertEmpty($replies);
    }

    /**
     * @return void
     */
    public function test_fetch_replies_of_restricted_playlist_by_non_shared_user(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist(['access' => access::RESTRICTED]);

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment = $comment_generator->create_comment(
            $playlist->get_id(),
            playlist::get_resource_type(),
            playlist::COMMENT_AREA
        );

        // Log in as second user to fetch the replies.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $this->expectException(comment_exception::class);
        $this->expectExceptionMessage(get_string('error:accessdenied', 'totara_comment'));

        $this->resolve_graphql_query(
            'totara_comment_replies',
            ['commentid' => $comment->get_id()]
        );
    }


    /**
     * @return void
     */
    public function test_fetch_replies_of_restricted_playlist_by_shared_user(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist(['access' => access::RESTRICTED]);

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment = $comment_generator->create_comment(
            $playlist->get_id(),
            playlist::get_resource_type(),
            playlist::COMMENT_AREA
        );


        $user_two = $generator->create_user();
        $playlist_generator->share_playlist($playlist, [new user($user_two->id)]);

        // Log in as second user to fetch the replies.
        $this->setUser($user_two);

        $replies = $this->resolve_graphql_query(
            'totara_comment_replies',
            ['commentid' => $comment->get_id()]
        );

        self::assertIsArray($replies);
        self::assertEmpty($replies);
    }
}