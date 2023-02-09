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
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use container_workspace\discussion\discussion_helper;
use core\json_editor\node\paragraph;
use totara_comment\comment_helper;
use container_workspace\workspace;
use container_workspace\member\member;
use container_workspace\discussion\discussion;
use core\json_editor\node\text;
use core\json_editor\node\mention;
use container_workspace\notification\workspace_notification;

class container_workspace_create_comment_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_create_comment_on_discussion_with_mention_test(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        // Create a workspace.
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Create a discussion in the workspace.
        $discussion = discussion_helper::create_discussion(
            $workspace,
            json_encode([
                'type' => 'doc',
                'content' => [paragraph::create_json_node_from_text("This is the discussion")],
            ]),
            null,
            FORMAT_JSON_EDITOR
        );

        // Now log in as second user and create a comment that mention user one.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        member::join_workspace($workspace, $user_two->id);

        // Clear the adhoc tasks first.
        $this->executeAdhocTasks();
        $message_sink = $this->redirectMessages();

        // Create comment with mention of user one.
        comment_helper::create_comment(
            workspace::get_type(),
            discussion::AREA,
            $discussion->get_id(),
            json_encode([
                'type' => 'doc',
                'content' => [
                    [
                        'type' => paragraph::get_type(),
                        'content' => [
                            text::create_json_node_from_text("This is the text"),
                            mention::create_raw_node($user_one->id)
                        ]
                    ]
                ]
            ]),
            FORMAT_JSON_EDITOR,
            null,
            $user_two->id
        );

        // Mute the notifications for user one in this workspace, so that we will only get 1 message which is the
        // mention message.
        workspace_notification::off($workspace->get_id(), $user_one->id);

        // Execute the adhoc tasks which it should send a message to user one.
        $this->executeAdhocTasks();
        $messages = $message_sink->get_messages();

        self::assertCount(1, $messages);
        $message = reset($messages);

        self::assertIsObject($message);
        self::assertObjectHasAttribute('useridfrom', $message);
        self::assertObjectHasAttribute('useridto', $message);

        self::assertEquals($user_one->id, $message->useridto);
        self::assertEquals($user_two->id,$message->useridfrom);
    }

    /**
     * @return void
     */
    public function test_create_comment_on_discussion_with_mention_when_guest_user_is_in_session(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace('workspace 101');

        // Create a discussion in the workspace.
        $discussion = discussion_helper::create_discussion(
            $workspace,
            json_encode([
                'type' => 'doc',
                'content' => [paragraph::create_json_node_from_text("Discussion 101")],
            ]),
            null,
            FORMAT_JSON_EDITOR
        );

        // Now log in as second user and create a comment that mention user one.
        $user_two = $generator->create_user();
        member::join_workspace($workspace, $user_two->id);

        // Clear the adhoc tasks first.
        $this->executeAdhocTasks();
        $message_sink = $this->redirectMessages();

        // Set the guest user so that we can check if the message sending out is working or not.
        $guest_user = guest_user();
        $this->setUser($guest_user);

        // Create comment with mention of user one.
        comment_helper::create_comment(
            workspace::get_type(),
            discussion::AREA,
            $discussion->get_id(),
            json_encode([
                'type' => 'doc',
                'content' => [
                    [
                        'type' => paragraph::get_type(),
                        'content' => [
                            text::create_json_node_from_text("Mention user one "),
                            mention::create_raw_node($user_one->id)
                        ]
                    ]
                ]
            ]),
            FORMAT_JSON_EDITOR,
            null,
            $user_two->id
        );

        // Mute the notifications for user one in this workspace, so that we will only get 1 message which is the
        // mention message.
        workspace_notification::off($workspace->get_id(), $user_one->id);

        // Execute the adhoc tasks which it should send a message to user one.
        $this->executeAdhocTasks();
        $messages = $message_sink->get_messages();

        self::assertCount(1, $messages);
        $message = reset($messages);

        self::assertIsObject($message);
        self::assertObjectHasAttribute('useridfrom', $message);
        self::assertObjectHasAttribute('useridto', $message);

        self::assertNotEquals($guest_user->id, $message->useridfrom);

        self::assertEquals($user_one->id, $message->useridto);
        self::assertEquals($user_two->id,$message->useridfrom);
    }

    /**
     * @return void
     */
    public function test_create_comment_on_discussion_with_mention_when_user_is_in_session(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        // Create a workspace.
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Create a discussion in the workspace.
        $discussion = discussion_helper::create_discussion(
            $workspace,
            json_encode([
                'type' => 'doc',
                'content' => [paragraph::create_json_node_from_text("Discussion !!!")],
            ]),
            null,
            FORMAT_JSON_EDITOR
        );

        // Now log in as second user and create a comment that mention user one.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        member::join_workspace($workspace, $user_two->id);

        // Clear the adhoc tasks first.
        $this->executeAdhocTasks();
        $message_sink = $this->redirectMessages();

        // Create comment with mention of user one.
        comment_helper::create_comment(
            workspace::get_type(),
            discussion::AREA,
            $discussion->get_id(),
            json_encode([
                'type' => 'doc',
                'content' => [
                    [
                        'type' => paragraph::get_type(),
                        'content' => [
                            text::create_json_node_from_text("This is the text"),
                            mention::create_raw_node($user_one->id)
                        ]
                    ]
                ]
            ]),
            FORMAT_JSON_EDITOR,
            null
        );

        // Mute the notifications for user one in this workspace, so that we will only get 1 message which is the
        // mention message.
        workspace_notification::off($workspace->get_id(), $user_one->id);

        // Execute the adhoc tasks which it should send a message to user one.
        $this->executeAdhocTasks();
        $messages = $message_sink->get_messages();

        self::assertCount(1, $messages);
        $message = reset($messages);

        self::assertIsObject($message);
        self::assertObjectHasAttribute('useridfrom', $message);
        self::assertObjectHasAttribute('useridto', $message);

        self::assertEquals($user_one->id, $message->useridto);
        self::assertEquals($user_two->id,$message->useridfrom);
    }

    /**
     * @return void
     */
    public function test_create_comment_on_discussion_not_change_discussion_modified_time(): void {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        $this->setUser($user);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace('workspace 101');

        // Create a discussion in the workspace.
        $discussion = $workspace_generator->create_discussion(
            $workspace->get_id(),
            'Discussion 101',
            null,
            FORMAT_JSON_EDITOR
        );

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment_generator->create_comment(
            $discussion->get_id(),
            workspace::get_type(),
            discussion::AREA
        );

        $discussion->reload();
        self::assertNull($discussion->get_time_modified());
    }
}