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
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

use core\task\manager;
use totara_core\task\user_mention_notify_task;

class totara_core_mention_notify_task_testcase extends advanced_testcase {
    use \core_phpunit\language_pack_faker_trait;

    public function test_recipients_language_setting_is_observed(): void {
        $generator = self::getDataGenerator();
        $fake_language = 'xo_ox';
        $this->add_fake_language_pack(
            $fake_language,
            [
                'totara_core' => [
                    'mentiontitle:comment' => 'Fake language subject string'
                ]
            ]
        );

        $user_one = $generator->create_user(['lang' => $fake_language]);
        $user_two = $generator->create_user();
        $user_three = $generator->create_user();
        $course = $generator->create_course();

        $task = new user_mention_notify_task();
        $task->set_userid($user_three->id);
        $task->set_component('totara_core');

        $task->set_custom_data(
            [
                'area' => 'comment',
                'userids' => [$user_one->id, $user_two->id],
                'contextid' => context_course::instance($course->id)->id,
                'instanceid' => 12345,
                'title' => 'Test title',
                'content' => 'Test content',
                'url' => 'http://www.example.com'
            ]
        );

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

        // user_one should receive message in fake language.
        self::assertEquals('Fake language subject string', $message_data[$user_one->id]);
        // user_two should receive message in default (en).
        self::assertEquals(fullname($user_three) . ' has mentioned you in a comment', $message_data[$user_two->id]);
    }
}