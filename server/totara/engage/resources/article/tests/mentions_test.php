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
defined('MOODLE_INTERNAL') || die();

use totara_comment\comment_helper;
use totara_engage\timeview\time_view;
use engage_article\totara_engage\resource\article;
use totara_engage\access\access;

class engage_article_mentions_testcase extends advanced_testcase {
    public function test_mention_on_create_edit_article(): void {
        $user = $this->getDataGenerator()->create_user(['firstname' => 'Sender', 'lastname' => 'One']);
        $this->setUser($user);

        $user2 = $this->getDataGenerator()->create_user(['firstname' => 'Receiver', 'lastname' => 'Two']);
        $user3 = $this->getDataGenerator()->create_user(['firstname' => 'Another', 'lastname' => 'Three']);

        // Additional user to confirm that they won't receive notifications
        $this->getDataGenerator()->create_user(['firstname' => 'Non-Receiver', 'lastname' => 'Three']);

        $data = [
            'name' => 'Hello',
            'content' => '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"FYI "},{"type":"mention","attrs":{"id":"' . $user2->id . '","display":"Receiver Two"}}]}]}',
            'format' => FORMAT_JSON_EDITOR,
            'timeview' => time_view::LESS_THAN_FIVE,
            'access' => access::PRIVATE
        ];

        $sink = $this->redirectMessages();

        $article = article::create($data);

        $this->executeAdhocTasks();
        // Set current user again because tasks use cron_setup_user().
        $this->setUser($user);

        $messages = $sink->get_messages();
        $message = current($messages);
        $sink->close();

        $this->assertCount(1, $messages);
        $this->assertEquals('Sender', $message->fromfirstname);
        $this->assertEquals($user2->id, $message->useridto);
        $this->assertStringContainsString('totara/engage/resources/article/index.php?id=', $message->contexturl);
        $this->assertStringContainsString('Hello', $message->fullmessage);
        $this->assertStringContainsString('resource', $message->fullmessage);
        $this->assertStringContainsString('FYI', $message->fullmessage);

        // Update article
        $updatedata = ['content' => '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"UPDATED "},{"type":"mention","attrs":{"id":"' . $user2->id . '","display":"Receiver Two"}},{"type":"mention","attrs":{"id":"' . $user3->id . '","display":"Another Three"}}]}]}'];

        $sink = $this->redirectMessages();

        $article->update($updatedata);

        $this->executeAdhocTasks();
        $messages = $sink->get_messages();
        $message = current($messages);
        $sink->close();

        $this->assertCount(1, $messages);
        $this->assertEquals('Sender', $message->fromfirstname);
        $this->assertEquals($user3->id, $message->useridto);
        $this->assertStringContainsString('totara/engage/resources/article/index.php?id=', $message->contexturl);
        $this->assertStringContainsString('Hello', $message->fullmessage);
        $this->assertStringContainsString('resource', $message->fullmessage);
        $this->assertStringContainsString('UPDATED', $message->fullmessage);

    }

    public function test_mention_on_create_edit_comment(): void {
        $user = $this->getDataGenerator()->create_user(['firstname' => 'Sender', 'lastname' => 'One']);
        $this->setUser($user);

        $user2 = $this->getDataGenerator()->create_user(['firstname' => 'Receiver', 'lastname' => 'Two']);
        $user3 = $this->getDataGenerator()->create_user(['firstname' => 'Another', 'lastname' => 'Three']);

        // Additional user to confirm that they won't receive notifications
        $this->getDataGenerator()->create_user(['firstname' => 'Non-Receiver', 'lastname' => 'Three']);

        $data = [
            'name' => 'Hello',
            'content' => '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"ARTICLE "},{"type":"mention","attrs":{"id":"' . $user2->id . '","display":"Receiver Two"}}]}]}',
            'format' => FORMAT_JSON_EDITOR,
            'timeview' => time_view::LESS_THAN_FIVE,
            'access' => access::PRIVATE
        ];

        $article = article::create($data);
        $this->executeAdhocTasks();

        // Add comment
        $sink = $this->redirectMessages();
        $comment = comment_helper::create_comment(
            'engage_article',
            'comment',
            $article->get_id(),
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
        $this->assertStringContainsString('totara/engage/resources/article/index.php?id=', $message->contexturl);
        $this->assertStringContainsString('Hello', $message->fullmessage);
        $this->assertStringContainsString('commented', $message->fullmessage);
        $this->assertStringContainsString('INITIAL COMMENT', $message->fullmessage);

        // Add Reply
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
        $this->assertStringContainsString('totara/engage/resources/article/index.php?id=' . $article->get_id(), $message->contexturl);
        $this->assertStringContainsString('Hello', $message->fullmessage);
        $this->assertStringContainsString('commented', $message->fullmessage);
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
        $this->assertStringContainsString('totara/engage/resources/article/index.php?id=', $message->contexturl);
        $this->assertStringContainsString('Hello', $message->fullmessage);
        $this->assertStringContainsString('commented', $message->fullmessage);
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
        $this->assertStringContainsString('totara/engage/resources/article/index.php?id=', $message->contexturl);
        $this->assertStringContainsString('Hello', $message->fullmessage);
        $this->assertStringContainsString('commented', $message->fullmessage);
        $this->assertStringContainsString('UPDATED REPLY', $message->fullmessage);
    }
}