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
 * @package engage_article
 */
defined('MOODLE_INTERNAL') || die();

use core\json_editor\node\paragraph;
use core\json_editor\node\text;
use core\json_editor\node\mention;
use engage_article\totara_engage\resource\article;

class engage_article_create_article_with_mention_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_create_article_with_mention_trigger_tasks(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $content = json_encode([
            'type' => 'doc',
            'content' => [
                paragraph::create_json_node_from_text('This is for someone in this post'),
                [
                    'type' => paragraph::get_type(),
                    'content' => [
                        text::create_json_node_from_text('someone is here '),
                        mention::create_raw_node($user_two->id)
                    ]
                ]
            ],
        ]);

        // Clear any adhoc tasks first.
        $this->executeAdhocTasks();

        article::create(
            [
                'content' => $content,
                'name' => 'Article 101',
                'format' => FORMAT_JSON_EDITOR
            ],
            $user_one->id
        );

        $message_sink = $this->redirectMessages();
        $this->executeAdhocTasks();

        // Once adhoc tasks are executed, there should be a message sending out to the user two as
        // the user had been mentioned in the content.
        $messages = $message_sink->get_messages();

        self::assertCount(1, $messages);
        $message = reset($messages);

        self::assertIsObject($message);

        self::assertObjectHasAttribute('useridto', $message);
        self::assertObjectHasAttribute('useridfrom', $message);

        self::assertEquals($user_two->id, $message->useridto);
        self::assertEquals($user_one->id, $message->useridfrom);
    }

    /**
     * @return void
     */
    public function test_create_article_with_mention_trigger_tasks_when_guest_user_is_in_session(): void {
        $this->setGuestUser();
        $guest_user_id = guest_user()->id;

        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $content = json_encode([
            'type' => 'doc',
            'content' => [
                [
                    'type' => paragraph::get_type(),
                    'content' => [
                        text::create_json_node_from_text('This is to mention second user'),
                        mention::create_raw_node($user_two->id)
                    ]
                ]
            ]
        ]);

        // Clear adhoc tasks first.
        $this->executeAdhocTasks();
        article::create(
            [
                'content' => $content,
                'format' => FORMAT_JSON_EDITOR,
                'name' => 'data wow'
            ],
            $user_one->id
        );

        $message_sink = $this->redirectMessages();

        // Trigger adhoc tasks, as there should be a message sending out to the user two.
        $this->executeAdhocTasks();
        $messages = $message_sink->get_messages();

        self::assertCount(1, $messages);

        $message = reset($messages);

        self::assertIsObject($message);
        self::assertObjectHasAttribute('useridto', $message);
        self::assertObjectHasAttribute('useridfrom', $message);

        // Check that we do not involve any guest user in here.
        self::assertNotEquals($guest_user_id, $message->useridfrom);
        self::assertNotEquals($guest_user_id, $message->useridto);

        self::assertEquals($user_one->id, $message->useridfrom);
        self::assertEquals($user_two->id, $message->useridto);
    }
}