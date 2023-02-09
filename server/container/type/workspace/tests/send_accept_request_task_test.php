<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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

use core\orm\query\exceptions\record_not_found_exception;
use container_workspace\task\send_accept_request_task;
use container_workspace\member\member_request;

class container_workspace_send_accept_request_task_testcase extends advanced_testcase {
    use \core_phpunit\language_pack_faker_trait;
    /**
     * @return void
     */
    public function test_execute_adhoc_task_without_actor_id(): void {
        $task = new send_accept_request_task();
        $task->set_member_request_id(42);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("No actor's id was set");
        $task->execute();
    }

    /**
     * @return void
     */
    public function test_execute_adhoc_task_without_member_request_id(): void {
        $task = new send_accept_request_task();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("No member request id was set");

        $task->execute();
    }

    /**
     * @return void
     */
    public function test_execute_adhoc_task_when_request_id_is_invalid(): void {
        global $USER;
        $this->setAdminUser();

        $task = new send_accept_request_task();
        $task->set_member_request_id(42);
        $task->set_userid($USER->id);

        $this->expectException(record_not_found_exception::class);
        $this->expectExceptionMessage("Can not find data record in database.");
        $task->execute();
    }

    /**
     * @return void
     */
    public function test_execute_adhoc_task_without_accepted_status(): void {
        global $USER;
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        $request = member_request::create($workspace->get_id(), $user_one->id);
        $task = new send_accept_request_task();
        $task->set_member_request_id($request->get_id());
        $task->set_userid($USER->id);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The member request was not yet accepted");

        $task->execute();
    }

    /**
     * @return void
     */
    public function test_execute_adhoc_task(): void {
        $this->setAdminUser();
        $admin_id = get_admin()->id;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        $request = member_request::create($workspace->get_id(), $user_one->id);
        $request->accept();

        $sink = $this->redirectMessages();
        $task = new send_accept_request_task();

        $task->set_userid($admin_id);
        $task->set_member_request_id($request->get_id());

        $task->execute();
        $messages = $sink->get_messages();

        $this->assertCount(1, $messages);
        $message = reset($messages);

        $this->assertObjectHasAttribute('fullmessage', $message);
        $this->assertObjectHasAttribute('fullmessagehtml', $message);
        $this->assertObjectHasAttribute('subject', $message);

        $this->assertStringContainsString(
            get_string('approved_request_title', 'container_workspace', $workspace->get_name()),
            $message->subject
        );

        $this->assertStringContainsString(
            get_string('approved_request_message', 'container_workspace', $workspace->get_name()),
            $message->fullmessage
        );

        $this->assertStringContainsString(
            get_string('approved_request_message', 'container_workspace', $workspace->get_name()),
            $message->fullmessagehtml
        );

        $this->assertStringContainsString(
            $workspace->get_workspace_url()->out(),
            $message->fullmessage
        );

        $this->assertStringContainsString(
            $workspace->get_workspace_url()->out(),
            $message->fullmessagehtml
        );

        $this->assertObjectHasAttribute('useridfrom', $message);
        $this->assertEquals($admin_id, $message->useridfrom);

        $this->assertObjectHasAttribute('useridto', $message);
        $this->assertEquals($user_one->id, $message->useridto);
    }

    public function test_recipients_language_setting_is_observed(): void {
        $generator = self::getDataGenerator();
        $fake_language = 'xo_ox';
        $this->add_fake_language_pack(
            $fake_language,
            [
                'container_workspace' => [
                    'approved_request_title' => 'Fake language subject string'
                ]
            ]
        );
        self::setAdminUser();

        $user_one = $generator->create_user(['lang' => $fake_language]);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        $request = member_request::create($workspace->get_id(), $user_one->id);
        $request->accept();

        $sink = $this->redirectMessages();
        $task = new send_accept_request_task();

        $task->set_userid(get_admin()->id);
        $task->set_member_request_id($request->get_id());

        $task->execute();
        $messages = $sink->get_messages();

        self::assertCount(1, $messages);
        $message = reset($messages);

        self::assertEquals($user_one->id, $message->useridto);
        self::assertEquals('Fake language subject string', $message->subject);
    }
}