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
 * @package totara_core
 */
defined('MOODLE_INTERNAL') || die();

use totara_core\content\content;
use totara_core\content\processor\mention_processor;
use core\json_editor\node\paragraph;
use core\json_editor\node\mention;
use core\json_editor\node\text;

class totara_core_content_mention_processor_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_process_json_editor_content_that_has_mention(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $content = content::create(
            'This is title',
            json_encode([
                'type' => 'doc',
                'content' => [
                    [
                        'type' => paragraph::get_type(),
                        'content' => [
                            text::create_json_node_from_text('This is for the mention test'),
                            mention::create_raw_node($user_two->id)
                        ]
                    ]
                ]
            ]),
            FORMAT_JSON_EDITOR,
            42,
            'totara_core',
            'core_area'
        );

        $content->set_user_id($user_one->id);

        // Clear any adhoc tasks first.
        $this->executeAdhocTasks();
        $message_sink = $this->redirectMessages();

        $processor = new mention_processor();
        $processor->process_format_json_editor($content);

        $this->executeAdhocTasks();
        $messages = $message_sink->get_messages();

        self::assertCount(1, $messages);
        $message = reset($messages);

        self::assertIsObject($message);
        self::assertObjectHasAttribute('useridfrom', $message);
        self::assertObjectHasAttribute('useridto', $message);

        self::assertEquals($user_two->id, $message->useridto);
        self::assertEquals($user_one->id, $message->useridfrom);
    }

    /**
     * @return void
     */
    public function test_process_json_editor_content_that_has_mention_with_guest_user_in_session(): void {
        global $USER;
        $this->setGuestUser();

        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $content = content::create(
            'This is title',
            json_encode([
                'type' => 'doc',
                'content' => [
                    [
                        'type' => paragraph::get_type(),
                        'content' => [
                            text::create_json_node_from_text('This is for the mention --- '),
                            mention::create_raw_node($user_two->id)
                        ]
                    ]
                ]
            ]),
            FORMAT_JSON_EDITOR,
            42,
            'totara_core',
            'core_area'
        );

        $content->set_user_id($user_one->id);

        // Clear any adhoc tasks first.
        $this->executeAdhocTasks();
        $message_sink = $this->redirectMessages();

        $processor = new mention_processor();
        $processor->process_format_json_editor($content);

        $this->executeAdhocTasks();
        $messages = $message_sink->get_messages();

        self::assertCount(1, $messages);
        $message = reset($messages);

        self::assertIsObject($message);
        self::assertObjectHasAttribute('useridfrom', $message);
        self::assertObjectHasAttribute('useridto', $message);

        self::assertNotEquals($USER->id, $message->useridfrom);

        self::assertEquals($user_two->id, $message->useridto);
        self::assertEquals($user_one->id, $message->useridfrom);
    }

    /**
     * @return void
     */
    public function test_process_json_editor_content_that_has_mention_with_user_in_session(): void {
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $content = content::create(
            'This is title',
            json_encode([
                'type' => 'doc',
                'content' => [
                    [
                        'type' => paragraph::get_type(),
                        'content' => [
                            text::create_json_node_from_text('Mention this'),
                            mention::create_raw_node($user_one->id)
                        ]
                    ]
                ]
            ]),
            FORMAT_JSON_EDITOR,
            42,
            'totara_core',
            'core_area'
        );

        // Clear any adhoc tasks first.
        $this->executeAdhocTasks();
        $message_sink = $this->redirectMessages();

        $processor = new mention_processor();
        $processor->process_format_json_editor($content);

        $this->executeAdhocTasks();
        $messages = $message_sink->get_messages();

        self::assertCount(1, $messages);
        $message = reset($messages);

        self::assertIsObject($message);
        self::assertObjectHasAttribute('useridfrom', $message);
        self::assertObjectHasAttribute('useridto', $message);

        self::assertEquals(get_admin()->id, $message->useridfrom);
        self::assertEquals($user_one->id, $message->useridto);
    }

    /**
     * @return void
     */
    public function test_process_json_editor_content_without_mention(): void {
        $content = content::create(
            'this is title',
            json_encode([
                'type' => 'doc',
                'content' => [paragraph::create_json_node_from_text('Wohoo')]
            ]),
            FORMAT_JSON_EDITOR,
            42,
            'totara_core',
            'core_area'
        );

        // Clear adhoc tasks first.
        $this->executeAdhocTasks();
        $message_sink = $this->redirectMessages();

        $processor = new mention_processor();
        $processor->process_format_json_editor($content);

        $this->executeAdhocTasks();
        $messages = $message_sink->get_messages();

        self::assertEmpty($messages);
    }

    /**
     * @return void
     */
    public function test_process_text_content_without_mention(): void {
        $this->setAdminUser();
        $content = content::create(
            'this is title',
            'this is content',
            FORMAT_PLAIN,
            42,
            'totara_core',
            'core_area'
        );

        $this->executeAdhocTasks();
        $message_sink = $this->redirectMessages();

        $processor = new mention_processor();
        $processor->process_format_text($content);

        $this->executeAdhocTasks();
        $messages = $message_sink->get_messages();

        self::assertEmpty($messages);
    }

    /**
     * @return void
     */
    public function test_process_text_content_with_mention(): void {
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $content = content::create(
            'Thisis title',
            "hello @{$user_one->username}, this is to mention you",
            FORMAT_PLAIN,
            42,
            'totara_core',
            'core_area'
        );

        // Clear out the adhoc tasks first.
        $this->executeAdhocTasks();
        $message_sink = $this->redirectMessages();

        $processor = new mention_processor();
        $processor->process_format_text($content);

        $this->executeAdhocTasks();
        $messages = $message_sink->get_messages();

        self::assertCount(1, $messages);
        $message = reset($messages);

        self::assertIsObject($message);
        self::assertObjectHasAttribute('useridto', $message);
        self::assertObjectHasAttribute('useridfrom', $message);

        self::assertEquals(get_admin()->id, $message->useridfrom);
        self::assertEquals($user_one->id, $message->useridto);
    }
}