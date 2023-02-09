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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_program
 */

use core_phpunit\testcase;
use totara_notification\manager\event_queue_manager;
use totara_notification\manager\notification_queue_manager;
use totara_program\testing\generator as program_generator;

class totara_program_totara_notification_send_assigned_testcase extends testcase {
    /**
     * Test to make sure that the safe html will not be encoded.
     * @return void
     */
    public function test_send_assignment_notification(): void {
        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $program_generator = program_generator::instance();
        $program = $program_generator->create_program();

        $sink = self::redirectMessages();
        self::assertEmpty($sink->get_messages());

        $program_generator->assign_program($program->id, [$user_one->id]);

        $event_queue_manager = new event_queue_manager();
        $event_queue_manager->process_queues();

        $notification_manager = new notification_queue_manager();
        $notification_manager->dispatch_queues();

        $messages = $sink->get_messages();
        self::assertNotEmpty($messages);
        self::assertCount(1, $messages);

        $first_message = reset($messages);

        self::assertIsObject($first_message);
        self::assertObjectHasAttribute('fullmessage', $first_message);
        self::assertObjectHasAttribute('fullmessagehtml', $first_message);

        $url = new moodle_url("/totara/program/view.php", ['id' => $program->id]);
        self::assertEquals(
            implode(
                "\n\n",
                [
                    "You are now assigned on program {$program->fullname}.",
                    "Go to {$program->fullname} [1]\n",
                    "Links:\n------\n[1] {$url->out()}\n",
                ]
            ),
            $first_message->fullmessage
        );

        self::assertEquals(
            implode(
                "",
                [
                    "<p>You are now assigned on program {$program->fullname}.</p>",
                    "<p>Go to " . html_writer::tag('a', $program->fullname, ['href' => $url->out()]) . "</p>"
                ],
            ),
            $first_message->fullmessagehtml
        );
    }
}