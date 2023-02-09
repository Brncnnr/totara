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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package container_workspace
 */

defined('MOODLE_INTERNAL') || die();

use container_workspace\member\member_request;
use container_workspace\task\notify_decline_join_request_task;
use core\orm\query\builder;
use core_phpunit\testcase;
use container_workspace\output\decline_request_join_notification;

class container_workspace_notify_decline_join_request_task_testcase extends testcase {
    /**
     * @return void
     */
    public function test_execute_task_without_member_id(): void {
        $task = new notify_decline_join_request_task();

        $this->expectExceptionMessage("Required member request id was missing");
        $this->expectException(coding_exception::class);

        $task->execute();
    }

    /**
     * @return void
     */
    public function test_sending_message_to_request_user(): void {
        global $OUTPUT;

        $generator = self::getDataGenerator();
        $user = $generator->create_user();

        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        self::setAdminUser();
        $workspace = $workspace_generator->create_workspace(
            "This is workspace 1010",
            null,
            FORMAT_PLAIN,
            null,
            true
        );

        self::setUser($user);
        $request = member_request::create($workspace->get_id(), $user->id);
        self::assertFalse($request->is_accepted());
        self::assertFalse($request->is_declined());
        self::assertFalse($request->is_cancelled());

        // Clear adhoc tasks.
        self::executeAdhocTasks();

        self::setAdminUser();
        $request->decline(get_admin()->id, time(), 'Test decline');

        // Start the sink.
        $message_sink = self::redirectMessages();

        // Execute the adhoc tasks.
        self::executeAdhocTasks();

        $messages = $message_sink->get_messages();
        self::assertCount(1, $messages);

        $message = reset($messages);

        self::assertObjectHasAttribute('fullmessage', $message);
        self::assertObjectHasAttribute('fullmessagehtml', $message);
        self::assertObjectHasAttribute('useridto', $message);

        self::assertEquals($user->id, $message->useridto);
        $template = decline_request_join_notification::create($request);
        $rendered_content = $OUTPUT->render($template);

        self::assertSame($rendered_content, $message->fullmessagehtml);
    }

    /**
     * @return void
     */
    public function test_sending_message_with_user_removed(): void {
        $generator = self::getDataGenerator();
        $user = $generator->create_user();
        $owner = $generator->create_user();

        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        self::setUser($owner);
        $workspace = $workspace_generator->create_workspace(
            "This is workspace",
            null,
            FORMAT_PLAIN,
            null,
            true
        );

        self::setUser($user);
        $request = member_request::create($workspace->get_id(), $user->id);

        // Clear adhoc tasks.
        self::executeAdhocTasks();

        self::setUser($owner);
        $request->decline($owner->id, time(), 'Test decline');

        builder::get_db()->delete_records('user', array('id' => $user->id));

        $message_sink = self::redirectMessages();
        self::executeAdhocTasks();

        self::assertDebuggingCalled('Skipped sending notification to non-existent user with id ' . $user->id);

        $messages = $message_sink->get_messages();
        self::assertCount(0, $messages);
    }
}