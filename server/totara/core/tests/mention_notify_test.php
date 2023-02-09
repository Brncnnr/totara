
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
 * @package editor_weka
 */
defined('MOODLE_INTERNAL') || die();

use totara_core\content\content_handler;

class totara_core_mention_notify_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_notify_user_via_json_editor_content(): void {
        $this->setAdminUser();

        $user = $this->getDataGenerator()->create_user();
        $fullname = fullname($user);
        $json = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'mention',
                    'attrs' => [
                        'id' => $user->id,
                        'display' => $fullname
                    ]
                ]
            ],
        ];

        $handler = content_handler::create();
        $handler->handle_with_params(
            'test',
            json_encode($json),
            FORMAT_JSON_EDITOR,
            1,
            'editor_weka',
            'comment'
        );

        $sink = $this->redirectMessages();
        $this->executeAdhocTasks();

        $messages = $sink->get_messages();
        $this->assertCount(1, $messages);
    }

    /**
     * @return void
     */
    public function test_notify_user_via_text_content(): void {
        $this->setAdminUser();

        $user = $this->getDataGenerator()->create_user();
        $content = "Hello @{$user->username}, this is something random for you @{$user->username}";

        $handler = content_handler::create();
        $handler->handle_with_params('test', $content, FORMAT_PLAIN, 1,'totara_comment', 'view');

        $sink = $this->redirectMessages();
        $this->executeAdhocTasks();

        $messages = $sink->get_messages();
        $this->assertCount(1, $messages);
    }


    /**
     * @return void
     */
    public function test_notify_user_via_html_content(): void {
        $this->setAdminUser();

        $user = $this->getDataGenerator()->create_user();
        $content = /** @lang text */"
            <div>
                <p>This is the line number one, @{$user->username}</p>
            </div>
        ";

        $handler = content_handler::create();
        $handler->handle_with_params('test', $content, FORMAT_HTML, 1,'totara_comment', 'view');

        $sink = $this->redirectMessages();
        $this->executeAdhocTasks();

        $messages = $sink->get_messages();
        $this->assertCount(1, $messages);
    }

    /**
     * @return void
     */
    public function test_notify_user_only_once(): void {
        global $DB;

        $this->setAdminUser();
        $this->executeAdhocTasks();

        $user = $this->getDataGenerator()->create_user();
        $document = json_encode([
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'hello '
                        ],
                        [
                            'type' => 'mention',
                            'attrs' => [
                                'display' => fullname($user),
                                'id' => $user->id
                            ]
                        ]
                    ]
                ]
            ],
        ]);

        $handler = content_handler::create();

        $handler->handle_with_params('test', $document, FORMAT_JSON_EDITOR, 1, 'totara_core', 'view');

        // Simulate updating.
        $handler->handle_with_params('test', $document, FORMAT_JSON_EDITOR, 1, 'totara_core', 'view');

        $sink = $this->redirectMessages();
        $this->executeAdhocTasks();

        $messages = $sink->get_messages();
        $this->assertCount(1, $messages);
    }
}