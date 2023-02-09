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

use container_workspace\member\member;
use container_workspace\discussion\discussion_helper;
use core\json_editor\node\paragraph;
use core\json_editor\node\text;
use core\json_editor\node\mention;

class container_workspace_discussion_with_mention_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_create_discussion_with_mention(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        // Create a workspace as user one to be exact owner.
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Join user two to the workspace.
        $user_two = $generator->create_user();
        member::added_to_workspace($workspace, $user_two->id, false, $user_one->id);

        // Clear adhoc tasks first.
        $this->executeAdhocTasks();
        $message_sink = $this->redirectMessages();

        // Now create a discussion that mention user two.
        discussion_helper::create_discussion(
            $workspace,
            json_encode([
                'type' => 'doc',
                'content' => [
                    [
                        'type' => paragraph::get_type(),
                        'content' => [
                            text::create_json_node_from_text("This is mention in discussion"),
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
        self::assertCount(2, $messages);
        $message = reset($messages);

        self::assertIsObject($message);
        self::assertObjectHasAttribute('useridfrom', $message);
        self::assertObjectHasAttribute('useridto', $message);
        self::assertEquals('mention', $message->eventtype);

        self::assertEquals($user_one->id, $message->useridfrom);
        self::assertEquals($user_two->id, $message->useridto);

        $message1 = next($messages);

        self::assertIsObject($message1);
        self::assertObjectHasAttribute('useridfrom', $message1);
        self::assertObjectHasAttribute('useridto', $message1);
        self::assertEquals('create_new_discussion', $message1->eventtype);

        self::assertEquals($user_two->id, $message1->useridto);
        self::assertEquals(core_user::get_noreply_user()->id, $message1->useridfrom);
    }

    /**
     * @return void
     */
    public function test_create_discussion_with_mention_when_guest_user_is_in_session(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        // Create a workspace as user one to be exact owner.
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Join user two to the workspace.
        $user_two = $generator->create_user();
        member::added_to_workspace($workspace, $user_two->id, false, $user_one->id);

        // Clear adhoc tasks first.
        $this->executeAdhocTasks();
        $message_sink = $this->redirectMessages();

        $guest_user = guest_user();
        $this->setUser($guest_user);

        // Now create a discussion that mention user two.
        discussion_helper::create_discussion(
            $workspace,
            json_encode([
                'type' => 'doc',
                'content' => [
                    [
                        'type' => paragraph::get_type(),
                        'content' => [
                            text::create_json_node_from_text("BOoM! "),
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

        self::assertCount(2, $messages);
        $message = reset($messages);

        self::assertIsObject($message);
        self::assertObjectHasAttribute('useridfrom', $message);
        self::assertObjectHasAttribute('useridto', $message);
        self::assertEquals('mention', $message->eventtype);
        self::assertNotEquals($guest_user->id, $message->useridfrom);

        self::assertEquals($user_one->id, $message->useridfrom);
        self::assertEquals($user_two->id, $message->useridto);

        $message1 = next($messages);

        self::assertIsObject($message1);
        self::assertObjectHasAttribute('useridfrom', $message1);
        self::assertObjectHasAttribute('useridto', $message1);
        self::assertEquals('create_new_discussion', $message1->eventtype);
        self::assertNotEquals($guest_user->id, $message1->useridfrom);

        self::assertEquals($user_two->id, $message1->useridto);
        self::assertEquals(core_user::get_noreply_user()->id, $message1->useridfrom);
    }

    /**
     * @return void
     */
    public function test_update_discussion_with_mention(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Create a discussion.
        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        // Add user two to the workspace.
        $user_two = $generator->create_user();
        member::added_to_workspace($workspace, $user_two->id, false, $user_one->id);

        // Clear some adhoc tasks first.
        $this->executeAdhocTasks();
        $message_sink = $this->redirectMessages();

        // Now update the discussion content, with user two being mentioned in the content.
        discussion_helper::update_discussion_content(
            $discussion->get_id(),
            json_encode([
                'type' => 'doc',
                'content' => [
                    [
                        'type' => paragraph::get_type(),
                        'content' => [
                            text::create_json_node_from_text('This is mentioning '),
                            mention::create_raw_node($user_two->id)
                        ]
                    ]
                ]
            ]),
            null,
            FORMAT_JSON_EDITOR,
            $user_one->id
        );

        // Run adhoc tasks.
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
    public function test_update_discussion_with_mention_and_guest_user_in_session(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Create a discussion.
        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        // Add user two to the workspace.
        $user_two = $generator->create_user();
        member::added_to_workspace($workspace, $user_two->id, false, $user_one->id);

        $guest_user = guest_user();
        $this->setUser($guest_user);

        // Clear some adhoc tasks first.
        $this->executeAdhocTasks();
        $message_sink = $this->redirectMessages();

        // Now update the discussion content, with user two being mentioned in the content.
        discussion_helper::update_discussion_content(
            $discussion->get_id(),
            json_encode([
                'type' => 'doc',
                'content' => [
                    [
                        'type' => paragraph::get_type(),
                        'content' => [
                            text::create_json_node_from_text('This is mentioning '),
                            mention::create_raw_node($user_two->id)
                        ]
                    ]
                ]
            ]),
            null,
            FORMAT_JSON_EDITOR,
            $user_one->id
        );

        // Run adhoc tasks.
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
}