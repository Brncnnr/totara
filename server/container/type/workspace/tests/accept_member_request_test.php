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

use container_workspace\member\member_request;
use container_workspace\loader\member\loader;

class container_workspace_accept_member_request_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_accept_member_request_trigger_message(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $this->setUser($user_one);

        $workspace = $workspace_generator->create_private_workspace();

        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $member_request = member_request::create($workspace->get_id(), $user_two->id);

        // Clear all the adhoc tasks first before execute one
        $this->executeAdhocTasks();

        // Log in as first user and accept the member request.
        $this->setUser($user_one);
        $member_request->accept();

        $sink = $this->redirectMessages();
        $sink->clear();
        $this->executeAdhocTasks();

        $messages = $sink->get_messages();
        $this->assertCount(1, $messages);

        $message = reset($messages);
        $this->assertObjectHasAttribute('subject', $message);
        $this->assertObjectHasAttribute('useridfrom', $message);
        $this->assertObjectHasAttribute('useridto', $message);
        $this->assertObjectHasAttribute('fullmessage', $message);
        $this->assertObjectHasAttribute('fullmessagehtml', $message);

        $this->assertEquals($user_one->id, $message->useridfrom);
        $this->assertEquals($user_two->id, $message->useridto);
        $this->assertEquals(
            get_string('approved_request_title', 'container_workspace'),
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

        // Check if the requester is now a part of workspace's member.
        $this->assertNotNull(loader::get_for_user($user_two->id, $workspace->get_id()));
    }
}