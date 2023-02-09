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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package engage_article
 */

use container_workspace\discussion\discussion_helper;
use container_workspace\local\workspace_helper;
use totara_comment\comment_helper;

defined('MOODLE_INTERNAL') || die();

class container_workspace_mentions_testcase extends advanced_testcase {
    public function test_mention_on_create_edit_discussion(): void {
        $user = $this->getDataGenerator()->create_user(['firstname' => 'Sender', 'lastname' => 'One']);
        $this->setUser($user);

        $user2 = $this->getDataGenerator()->create_user(['firstname' => 'Receiver', 'lastname' => 'Two']);
        $user3 = $this->getDataGenerator()->create_user(['firstname' => 'Another', 'lastname' => 'Three']);

        // Additional user to confirm that they won't receive notifications
        $this->getDataGenerator()->create_user(['firstname' => 'Non-Receiver', 'lastname' => 'Three']);

        $workspace = workspace_helper::create_workspace(
            'HELLO',
            $user->id,
            null,
            'SUMMARY',
            FORMAT_PLAIN
        );

        // Create discussion
        $sink = $this->redirectMessages();

        $discussion = discussion_helper::create_discussion(
            $workspace,
            '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"INITIAL DISCUSSION"},{"type":"mention","attrs":{"id":"' . $user2->id .
            '","display":"Receiver Two"}}]}]}',
            null,
            FORMAT_JSON_EDITOR,
            $user->id
        );

        $this->executeAdhocTasks();
        $messages = $sink->get_messages();
        $message = current($messages);
        $sink->close();

        $this->assertCount(1, $messages);
        $this->assertEquals('Sender', $message->fromfirstname);
        $this->assertEquals($user2->id, $message->useridto);
        $this->assertStringContainsString('/container/type/workspace/workspace.php?id=' . $workspace->get_id(), $message->contexturl);
        $this->assertStringContainsString('HELLO', $message->fullmessage);
        $this->assertStringContainsString('workspace', $message->fullmessage);
        $this->assertStringContainsString('INITIAL DISCUSSION', $message->fullmessage);

        // Update discussion.
        $sink = $this->redirectMessages();

        discussion_helper::update_discussion_content(
            $discussion->get_id(),
            '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"UPDATED DISCUSSION"},{"type":"mention","attrs":{"id":"' . $user2->id .
            '","display":"Receiver Two"}},{"type":"mention","attrs":{"id":"' . $user3->id . '","display":"Another Three"}}]}]}',
            null,
            FORMAT_JSON_EDITOR,
            $user->id
        );

        $this->executeAdhocTasks();
        $messages = $sink->get_messages();
        $message = current($messages);
        $sink->close();

        $this->assertCount(1, $messages);
        $this->assertEquals('Sender', $message->fromfirstname);
        $this->assertEquals($user3->id, $message->useridto);
        $this->assertStringContainsString('/container/type/workspace/workspace.php?id=' . $workspace->get_id(), $message->contexturl);
        $this->assertStringContainsString('HELLO', $message->fullmessage);
        $this->assertStringContainsString('workspace', $message->fullmessage);
        $this->assertStringContainsString('UPDATED DISCUSSION', $message->fullmessage);
    }

    public function test_mention_on_create_edit_comment(): void {
        $user = $this->getDataGenerator()->create_user(['firstname' => 'Sender', 'lastname' => 'One']);
        $this->setUser($user);

        $user2 = $this->getDataGenerator()->create_user(['firstname' => 'Receiver', 'lastname' => 'Two']);
        $user3 = $this->getDataGenerator()->create_user(['firstname' => 'Another', 'lastname' => 'Three']);

        // Additional user to confirm that they won't receive notifications
        $this->getDataGenerator()->create_user(['firstname' => 'Non-Receiver', 'lastname' => 'Three']);

        $workspace = workspace_helper::create_workspace(
            'HELLO',
            $user->id,
            null,
            'SUMMARY',
            FORMAT_PLAIN
        );
        $discussion = discussion_helper::create_discussion(
            $workspace,
            'SOME DISCUSSION',
            null,
            FORMAT_PLAIN,
            $user->id
        );
        $this->executeAdhocTasks();

        // Create comment
        $sink = $this->redirectMessages();
        $comment = comment_helper::create_comment(
            'container_workspace',
            'discussion',
            $discussion->get_id(),
            '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"INITIAL COMMENT"},{"type":"mention","attrs":{"id":"' . $user2->id . '","display":"Receiver Two"}}]}]}',
            FORMAT_JSON_EDITOR,
            null,
            $user->id
        );
        $this->executeAdhocTasks();
        $messages = $sink->get_messages();
        $message = current($messages);
        $sink->close();

        $this->assertCount(1, $messages);
        $this->assertEquals('Sender', $message->fromfirstname);
        $this->assertEquals($user2->id, $message->useridto);
        $this->assertStringContainsString('/container/type/workspace/workspace.php?id=' . $workspace->get_id(), $message->contexturl);
        $this->assertStringContainsString('HELLO', $message->fullmessage);
        $this->assertStringContainsString('mentioned', $message->fullmessage);
        $this->assertStringContainsString('workspace', $message->fullmessage);
        $this->assertStringContainsString('INITIAL COMMENT', $message->fullmessage);

        // Create Reply
        $sink = $this->redirectMessages();
        $reply = comment_helper::create_reply(
            $comment->get_id(),
            '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"INITIAL REPLY"},{"type":"mention","attrs":{"id":"' . $user2->id . '","display":"Receiver Two"}}]}]}',
            null,
            FORMAT_JSON_EDITOR,
            $user->id
        );
        $this->executeAdhocTasks();
        $messages = $sink->get_messages();
        $message = current($messages);
        $sink->close();

        $this->assertCount(1, $messages);
        $this->assertEquals('Sender', $message->fromfirstname);
        $this->assertEquals($user2->id, $message->useridto);
        $this->assertStringContainsString('/container/type/workspace/workspace.php?id=' . $workspace->get_id(), $message->contexturl);
        $this->assertStringContainsString('HELLO', $message->fullmessage);
        $this->assertStringContainsString('mentioned', $message->fullmessage);
        $this->assertStringContainsString('workspace', $message->fullmessage);
        $this->assertStringContainsString('INITIAL REPLY', $message->fullmessage);

        // Update comment
        $sink = $this->redirectMessages();
        comment_helper::update_content(
            $comment->get_id(),
            '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"UPDATED COMMENT"},{"type":"mention","attrs":{"id":"' . $user2->id . '","display":"Receiver Two"}},{"type":"mention","attrs":{"id":"' . $user3->id . '","display":"Another Three"}}]}]}',
            null,
            FORMAT_JSON_EDITOR,
            $user->id
        );
        $this->executeAdhocTasks();
        $messages = $sink->get_messages();
        $message = current($messages);
        $sink->close();

        $this->assertCount(1, $messages);
        $this->assertEquals('Sender', $message->fromfirstname);
        $this->assertEquals($user3->id, $message->useridto);
        $this->assertStringContainsString('/container/type/workspace/workspace.php?id=' . $workspace->get_id(), $message->contexturl);
        $this->assertStringContainsString('HELLO', $message->fullmessage);
        $this->assertStringContainsString('mentioned', $message->fullmessage);
        $this->assertStringContainsString('workspace', $message->fullmessage);
        $this->assertStringContainsString('UPDATED COMMENT', $message->fullmessage);

        // Update reply
        $sink = $this->redirectMessages();
        comment_helper::update_content(
            $reply->get_id(),
            '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"UPDATED REPLY"},{"type":"mention","attrs":{"id":"' . $user2->id . '","display":"Receiver Two"}},{"type":"mention","attrs":{"id":"' . $user3->id . '","display":"Another Three"}}]}]}',
            null,
            FORMAT_JSON_EDITOR,
            $user->id
        );
        $this->executeAdhocTasks();
        $messages = $sink->get_messages();
        $message = current($messages);
        $sink->close();

        $this->assertCount(1, $messages);
        $this->assertEquals('Sender', $message->fromfirstname);
        $this->assertEquals($user3->id, $message->useridto);
        $this->assertStringContainsString('/container/type/workspace/workspace.php?id=' . $workspace->get_id(), $message->contexturl);
        $this->assertStringContainsString('HELLO', $message->fullmessage);
        $this->assertStringContainsString('mentioned', $message->fullmessage);
        $this->assertStringContainsString('workspace', $message->fullmessage);
        $this->assertStringContainsString('UPDATED REPLY', $message->fullmessage);
    }
}