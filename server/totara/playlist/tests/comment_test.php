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

use totara_comment\comment_helper;
use core\json_editor\node\mention;
use core\json_editor\node\paragraph;
use core\json_editor\node\text;
use totara_engage\access\access;
use totara_playlist\playlist;

class totara_playlist_comment_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_create_comment_with_mention(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist([
            'access' => access::PUBLIC,
            'userid' => $user_one->id
        ]);

        self::assertEquals($user_one->id, $playlist->get_userid());

        // Add comment to the playlist that has mention to user two, which it should trigger tasks.
        // But first clear the adhoc tasks list.
        $this->executeAdhocTasks();
        $message_sink = $this->redirectMessages();

        comment_helper::create_comment(
            'totara_playlist',
            playlist::COMMENT_AREA,
            $playlist->get_id(),
            json_encode([
                'type' => 'doc',
                'content' => [
                    [
                        'type' => paragraph::get_type(),
                        'content' => [
                            text::create_json_node_from_text('This comment is dedicated to user '),
                            mention::create_raw_node($user_two->id)
                        ]
                    ]
                ]
            ]),
            FORMAT_JSON_EDITOR,
            null,
            $user_one->id
        );

        // Execute the adhoc tasks.
        $this->executeAdhocTasks();
        $messages = $message_sink->get_messages();

        self::assertCount(1, $messages);
        $message = reset($messages);

        self::assertIsObject($message);
        self::assertObjectHasAttribute('useridfrom', $message);
        self::assertObjectHasAttribute('useridto', $message);

        self::assertEquals($user_one->id, $message->useridfrom);
        self::assertEquals($user_two->id, $message->useridto);
    }

    /**
     * @return void
     */
    public function test_create_comment_with_mention_and_guest_user_in_session(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist([
            'access' => access::PUBLIC,
            'userid' => $user_one->id
        ]);

        self::assertEquals($user_one->id, $playlist->get_userid());

        // Set up the guest user - so that we can be sure that the process is respecting the arguement all
        // the way thru to observers.
        $guest_user = guest_user();
        $this->setUser($guest_user);

        // Add comment to the playlist that has mention to user two, which it should trigger tasks.
        // But first clear the adhoc tasks list.
        $this->executeAdhocTasks();
        $message_sink = $this->redirectMessages();

        comment_helper::create_comment(
            'totara_playlist',
            playlist::COMMENT_AREA,
            $playlist->get_id(),
            json_encode([
                'type' => 'doc',
                'content' => [
                    [
                        'type' => paragraph::get_type(),
                        'content' => [
                            text::create_json_node_from_text('This comment is dedicated to user two: '),
                            mention::create_raw_node($user_two->id)
                        ]
                    ]
                ]
            ]),
            FORMAT_JSON_EDITOR,
            null,
            $user_one->id
        );

        // Execute the adhoc tasks.
        $this->executeAdhocTasks();
        $messages = $message_sink->get_messages();

        self::assertCount(1, $messages);
        $message = reset($messages);

        self::assertIsObject($message);
        self::assertObjectHasAttribute('useridfrom', $message);
        self::assertObjectHasAttribute('useridto', $message);

        self::assertNotEquals($guest_user->id, $message->useridfrom);

        self::assertEquals($user_one->id, $message->useridfrom);
        self::assertEquals($user_two->id, $message->useridto);
    }

    /**
     * @return void
     */
    public function test_update_comment_with_mention(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist([
            'access' => access::PUBLIC,
            'userid' => $user_one->id
        ]);

        self::assertEquals($user_one->id, $playlist->get_userid());

        // Add a comment
        $comment = comment_helper::create_comment(
            'totara_playlist',
            playlist::COMMENT_AREA,
            $playlist->get_id(),
            'This is content',
            FORMAT_PLAIN,
            null,
            $user_one->id
        );

        // Create a user two and mention user two in comment.
        $user_two = $generator->create_user();

        // Clear up adhoc tasks.
        $this->executeAdhocTasks();
        $message_sink = $this->redirectMessages();

        comment_helper::update_content(
            $comment->get_id(),
            json_encode([
                'type' => 'doc',
                'content' => [
                    [
                        'type' => paragraph::get_type(),
                        'content' => [
                            text::create_json_node_from_text('This comment is for user '),
                            mention::create_raw_node($user_two->id)
                        ]
                    ]
                ]
            ]),
            null,
            FORMAT_JSON_EDITOR,
            $user_one->id
        );

        $this->executeAdhocTasks();
        $messages = $message_sink->get_messages();

        self::assertCount(1, $messages);
        $message = reset($messages);

        self::assertIsObject($message);
        self::assertObjectHasAttribute('useridfrom', $message);
        self::assertObjectHasAttribute('useridto', $message);

        self::assertEquals($user_one->id, $message->useridfrom);
        self::assertEquals($user_two->id, $message->useridto);
    }

    /**
     * @return void
     */
    public function test_update_comment_with_mention_with_guest_user_in_session(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist([
            'access' => access::PUBLIC,
            'userid' => $user_one->id
        ]);

        self::assertEquals($user_one->id, $playlist->get_userid());

        // Add a comment
        $comment = comment_helper::create_comment(
            'totara_playlist',
            playlist::COMMENT_AREA,
            $playlist->get_id(),
            'This is content',
            FORMAT_PLAIN,
            null,
            $user_one->id
        );

        // Create a user two and mention user two in comment.
        $user_two = $generator->create_user();

        // Clear up adhoc tasks.
        $this->executeAdhocTasks();
        $message_sink = $this->redirectMessages();

        // Set the guest user in session so that we can test whether the process is
        // is respecting the argument or not.
        $guest_user = guest_user();
        $this->setUser($guest_user);

        comment_helper::update_content(
            $comment->get_id(),
            json_encode([
                'type' => 'doc',
                'content' => [
                    [
                        'type' => paragraph::get_type(),
                        'content' => [
                            text::create_json_node_from_text('This comment is for user '),
                            mention::create_raw_node($user_two->id)
                        ]
                    ]
                ]
            ]),
            null,
            FORMAT_JSON_EDITOR,
            $user_one->id
        );

        $this->executeAdhocTasks();
        $messages = $message_sink->get_messages();

        self::assertCount(1, $messages);
        $message = reset($messages);

        self::assertIsObject($message);
        self::assertObjectHasAttribute('useridfrom', $message);
        self::assertObjectHasAttribute('useridto', $message);

        self::assertNotEquals($guest_user, $message->useridfrom);

        self::assertEquals($user_one->id, $message->useridfrom);
        self::assertEquals($user_two->id, $message->useridto);
    }
}