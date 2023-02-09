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
use totara_reaction\exception\reaction_exception;
use totara_webapi\phpunit\webapi_phpunit_helper;
use core_user\totara_engage\share\recipient\user;

class totara_playlist_webapi_fetch_reactions_of_comment_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_fetch_reactions_of_comment_in_private_playlist_by_viewer(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist(['access' => access::PRIVATE]);

        // Create a comment for the playlist.
        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment = $comment_generator->create_comment(
            $playlist->get_id(),
            playlist::get_resource_type(),
            playlist::COMMENT_AREA
        );

        // Log in as second user to fetch the reactions of comment.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $this->expectException(reaction_exception::class);
        $this->expectExceptionMessage(get_string('error:view', 'totara_reaction'));

        $this->resolve_graphql_query(
            'totara_reaction_reactions',
            [
                'instanceid' => $comment->get_id(),
                'component' => $comment::get_component_name(),
                'area' => $comment->get_area()
            ]
        );
    }

    /**
     * @return void
     */
    public function test_fetch_reactions_of_comment_in_restricted_playlist_by_non_shared_user(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist(['access' => access::RESTRICTED]);

        // Create a comment for the playlist.
        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment = $comment_generator->create_comment(
            $playlist->get_id(),
            playlist::get_resource_type(),
            playlist::COMMENT_AREA
        );

        // Log in as second user to fetch the reactions of comment.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $this->expectException(reaction_exception::class);
        $this->expectExceptionMessage(get_string('error:view', 'totara_reaction'));

        $this->resolve_graphql_query(
            'totara_reaction_reactions',
            [
                'instanceid' => $comment->get_id(),
                'component' => $comment::get_component_name(),
                'area' => $comment->get_area()
            ]
        );
    }

    /**
     * @return void
     */
    public function test_fetch_reactions_of_comment_in_public_playlist_by_viewer(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist(['access' => access::PUBLIC]);

        // Create a comment for the playlist.
        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment = $comment_generator->create_comment(
            $playlist->get_id(),
            playlist::get_resource_type(),
            playlist::COMMENT_AREA
        );

        // Log in as second user to fetch the reactions of comment.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $reactions = $this->resolve_graphql_query(
            'totara_reaction_reactions',
            [
                'instanceid' => $comment->get_id(),
                'component' => $comment::get_component_name(),
                'area' => $comment->get_area()
            ]
        );

        self::assertIsArray($reactions);
        self::assertEmpty($reactions);
    }

    /**
     * @return void
     */
    public function test_fetch_reactions_of_comment_in_restricted_platlist_by_shared_user(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist(['access' => access::RESTRICTED]);

        // Create a comment for the playlist.
        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment = $comment_generator->create_comment(
            $playlist->get_id(),
            playlist::get_resource_type(),
            playlist::COMMENT_AREA
        );

        // Log in as second user to fetch the reactions of comment.
        $user_two = $generator->create_user();
        $playlist_generator->share_playlist($playlist, [new user($user_two->id)]);

        $this->setUser($user_two);

        $reactions = $this->resolve_graphql_query(
            'totara_reaction_reactions',
            [
                'instanceid' => $comment->get_id(),
                'component' => $comment::get_component_name(),
                'area' => $comment->get_area()
            ]
        );

        self::assertIsArray($reactions);
        self::assertEmpty($reactions);
    }
}