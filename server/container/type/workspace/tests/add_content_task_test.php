<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package container_workspace
 */

defined('MOODLE_INTERNAL') || die();

use container_workspace\task\add_content_task;
use container_workspace\testing\generator;
use container_workspace\member\member;
use container_workspace\notification\workspace_notification;
use core\task\manager;

class container_workspace_add_content_task_testcase extends advanced_testcase {
    use \core_phpunit\language_pack_faker_trait;

    public function test_recipients_language_setting_is_observed(): void {
        $generator = self::getDataGenerator();
        $fake_language = 'xo_ox';
        $this->add_fake_language_pack(
            $fake_language,
            [
                'container_workspace' => [
                    'message_content_added_subject' => 'Fake language subject string'
                ]
            ]
        );

        $user_one = $generator->create_user();
        self::setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = generator::instance();
        $workspace = $workspace_generator->create_workspace();

        // Create two more users and add them to the workspace.
        $user_two = $generator->create_user(['lang' => $fake_language]);
        $user_three = $generator->create_user();
        member::added_to_workspace($workspace, $user_two->id);
        member::added_to_workspace($workspace, $user_three->id);

        // Clear the adhoc tasks.
        $this->executeAdhocTasks();

        $task = new add_content_task();
        $task->set_component('totara_engage');
        $task->set_custom_data([
            'component' => 'resource',
            'workspace_id' => $workspace->get_id(),
            'sharer_id' => $user_one->id,
            'item_name' => 'Test resource name'
        ]);

        manager::queue_adhoc_task($task);

        // Start the sink and execute the adhoc tasks.
        $message_sink = $this->redirectMessages();
        $this->executeAdhocTasks();

        $messages = $message_sink->get_messages();
        self::assertCount(2, $messages);
        $message_data = [];
        foreach ($messages as $message) {
            $message_data[$message->useridto] = $message->subject;
        }

        // user_two should receive message in fake language.
        self::assertEquals('Fake language subject string', $message_data[$user_two->id]);
        // user_three should receive message in default (en).
        self::assertEquals('New resource has been shared to your workspace', $message_data[$user_three->id]);
    }

    public function test_muted_workspace_notifications(): void {
        $generator = self::getDataGenerator();

        $owner = $generator->create_user();
        self::setUser($owner);
        $workspace = generator::instance()->create_workspace();

        $user_one = $generator->create_user();
        member::added_to_workspace($workspace, $user_one->id);

        $user_two = $generator->create_user();
        member::added_to_workspace($workspace, $user_two->id);

        $this->executeAdhocTasks();

        $component = 'resource';
        $task = new add_content_task();
        $task->set_component('totara_engage');
        $task->set_custom_data([
            'component' => $component,
            'workspace_id' => $workspace->get_id(),
            'sharer_id' => $owner->id,
            'item_name' => 'Test resource name'
        ]);

        workspace_notification::off($workspace->id, $user_one->id);
        manager::queue_adhoc_task($task);

        $message_sink = $this->redirectMessages();
        $this->executeAdhocTasks();

        $expected_subject = get_string('message_content_added_subject', 'container_workspace', $component);
        $messages = array_filter(
            $message_sink->get_messages(),
            function ($message) use ($expected_subject): bool {
                return $message->subject === $expected_subject;
            }
        );

        $message_sink->close();
        $this->assertCount(1, $messages);

        $message = reset($messages);
        $this->assertEquals($user_two->id, $message->useridto);
    }
}