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

use container_workspace\task\notify_join_request_task;
use container_workspace\member\member_request;
use container_workspace\output\join_request_notification;
use container_workspace\notification\workspace_notification;
use totara_userdata\userdata\target_user;
use container_workspace\userdata\workspace as user_data_workspace;

class container_workspace_notify_join_request_testcase extends advanced_testcase {
    use \core_phpunit\language_pack_faker_trait;
    /**
     * @return void
     */
    public function test_task_without_member_request_id(): void {
        $task = new notify_join_request_task();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("No member request's id was added");

        $task->execute();
    }

    /**
     * @return void
     */
    public function test_notify_workspace_owner(): void {
        global $OUTPUT;

        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        $this->setUser($user_one);
        $workspace = $workspace_generator->create_private_workspace();

        // Log in as second user and request to join the workspace.
        $this->setUser($user_two);

        // Clear the adhoc tasks list first.
        $this->executeAdhocTasks();

        $workspace_id = $workspace->get_id();
        $member_request = member_request::create($workspace_id, $user_two->id);

        // Start the sink, then execute the adhoc tasks to check our message to the workspace owner.
        $message_sink = $this->redirectMessages();
        $this->executeAdhocTasks();

        $messages = $message_sink->get_messages();
        $this->assertCount(1, $messages);

        $message = reset($messages);

        $this->assertObjectHasAttribute('fullmessage', $message);
        $this->assertObjectHasAttribute('fullmessagehtml', $message);
        $this->assertObjectHasAttribute('useridto', $message);
        $this->assertObjectHasAttribute('subject', $message);

        $this->assertEquals($user_one->id, $message->useridto);
        $this->assertEquals(get_string('member_request_title', 'container_workspace'), $message->subject);

        $template = join_request_notification::create($member_request);
        $rendered_content = $OUTPUT->render($template);

        $this->assertEquals($rendered_content, $message->fullmessagehtml);
    }

    /**
     * @return void
     */
    public function test_notify_workspace_owner_when_notification_is_off(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        $this->setUser($user_one);
        $workspace = $workspace_generator->create_private_workspace();

        // Turn off the notification for user one on this specific notification.
        $workspace_id = $workspace->get_id();
        workspace_notification::off($workspace_id, $user_one->id);

        // Request to join the workspace as user two. But first we need to clear adhoc tasks first.
        $this->executeAdhocTasks();

        $this->setUser($user_two);
        member_request::create($workspace_id, $user_two->id);

        // Start the sink and execute the adhoc tasks
        $message_sink = $this->redirectMessages();
        $this->executeAdhocTasks();

        // There should be no messages sending out to the workspace owner as the notification had turned off
        // for the workspace owner.
        $messages = $message_sink->get_messages();
        $this->assertEmpty($messages);
    }

    /**
     * @return void
     */
    public function test_notify_workspace_owner_when_workspace_owner_is_purged(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        $this->setUser($user_one);
        $workspace = $workspace_generator->create_private_workspace();
        $workspace_id = $workspace->get_id();

        // Delete user and start purging the workspaces.
        delete_user($user_one);

        $user_one = core_user::get_user($user_one->id);
        $target_user = new target_user($user_one);

        // Start the purge.
        user_data_workspace::execute_purge($target_user, context_system::instance());

        // Log in as user two and request to join the workspace.
        // Since user one had been purged. Meaning that there will be no messages sending out to the
        // anyone.

        // But first clear the adhoc tasks.
        $this->executeAdhocTasks();

        $this->setUser($user_two);
        member_request::create($workspace_id, $user_two->id);

        // Start the sink and execute the adhoc tasks
        $message_sink = $this->redirectMessages();
        $this->executeAdhocTasks();

        // There should be no messages sending out to the workspace owner as the notification had turned off
        // for the workspace owner.
        $messages = $message_sink->get_messages();
        $this->assertEmpty($messages);
    }

    /**
     * @return void
     */
    public function test_notify_workspace_owner_when_member_request_is_canelled(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        // Log in as second user and create then cancel the member request to join the workspace above.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        // Clear the adhoc tasks.
        $this->executeAdhocTasks();

        $workspace_id = $workspace->get_id();
        $member_request = member_request::create($workspace_id, $user_two->id);

        $member_request->cancel();
        $message_sink = $this->redirectMessages();

        // Trigger adhoc tasks.
        $this->executeAdhocTasks();
        $messages = $message_sink->get_messages();

        $this->assertEmpty($messages);
    }

    public function test_recipients_language_setting_is_observed(): void {
        $generator = self::getDataGenerator();
        $fake_language = 'xo_ox';
        $this->add_fake_language_pack(
            $fake_language,
            [
                'container_workspace' => [
                    'member_request_title' => 'Fake language subject string'
                ]
            ]
        );

        $user_one = $generator->create_user(['lang' => $fake_language]);
        $user_two = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        self::setUser($user_one);
        $workspace = $workspace_generator->create_private_workspace();

        // Clear the adhoc tasks list.
        $this->executeAdhocTasks();

        // Log in as second user and request to join the workspace.
        self::setUser($user_two);

        $workspace_id = $workspace->get_id();
        member_request::create($workspace_id, $user_two->id);

        // Start the sink, then execute the adhoc tasks to check our message to the workspace owner.
        $message_sink = $this->redirectMessages();
        $this->executeAdhocTasks();

        $messages = $message_sink->get_messages();
        self::assertCount(1, $messages);

        $message = reset($messages);

        self::assertEquals($user_one->id, $message->useridto);
        self::assertEquals('Fake language subject string', $message->subject);
    }

}