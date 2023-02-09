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

use container_workspace\task\notify_discussion_new_comment_task;
use totara_comment\comment_helper;
use container_workspace\member\member;
use container_workspace\discussion\discussion;
use container_workspace\output\comment_on_discussion;
use container_workspace\notification\workspace_notification;

class container_workspace_notify_discussion_new_comment_testcase extends advanced_testcase {
    use \core_phpunit\language_pack_faker_trait;
    /**
     * @return void
     */
    public function test_execute_task_without_comment_id(): void {
        $task = new notify_discussion_new_comment_task();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("No comment's id was set");

        $task->execute();
    }

    /**
     * @return void
     */
    public function test_execute_task_for_comment_not_for_workspace(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        $comment = comment_helper::create_comment(
            'totara_comment',
            'comment',
            42,
            "This is the content"
        );

        $comment_id = $comment->get_id();
        $task = notify_discussion_new_comment_task::from_comment($comment_id);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Expecting comment to be a part workspace's discussion");

        $task->execute();
    }

    /**
     * @return void
     */
    public function test_sending_message_to_discussion_owner(): void {
        global $OUTPUT;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Join the workspace as user two and create a discussion.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        member::join_workspace($workspace);
        $user_two_discussion = $workspace_generator->create_discussion($workspace->get_id());

        // Make sure that we cleared the adhoc tasks first.
        $this->executeAdhocTasks();

        // Now log in as user one and create a comment on the discussion.
        $this->setUser($user_one);

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment = $comment_generator->create_comment(
            $user_two_discussion->get_id(),
            'container_workspace',
            discussion::AREA
        );

        // Start the sink.
        $message_sink = $this->redirectMessages();

        // Execute the adhoc tasks.
        $this->executeAdhocTasks();

        // There should be a message sent out to user two - as the comment was created by user one.
        $messages = $message_sink->get_messages();

        $this->assertCount(1, $messages);
        $message = reset($messages);

        $this->assertObjectHasAttribute('fullmessage', $message);
        $this->assertObjectHasAttribute('fullmessagehtml', $message);
        $this->assertObjectHasAttribute('useridto', $message);

        $this->assertEquals($user_two->id, $message->useridto);

        $author_name = fullname($user_one);
        $this->assertStringContainsString($author_name, $message->fullmessage);
        $this->assertStringContainsString($author_name, $message->fullmessagehtml);

        $template = comment_on_discussion::create($user_two_discussion, $comment);
        $rendered_content = $OUTPUT->render($template);

        $this->assertSame($rendered_content, $message->fullmessagehtml);
    }

    /**
     * @return void
     */
    public function test_sending_message_to_discussion_owner_when_notification_is_off_for_owner(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Log in as user two and join the workspace and create a discussion.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        member::join_workspace($workspace, $user_two->id);

        // Create the discussion as user two.
        $workspace_id = $workspace->get_id();
        $user_two_discussion = $workspace_generator->create_discussion($workspace_id);

        // Turn off the notification for user two in this workspace.
        workspace_notification::off($workspace_id, $user_two->id);

        // Run any adhoc tasks just before we are creating any new ones.
        $this->executeAdhocTasks();

        // Log in as user one and create the comment to the discussion.
        // Then run the adhoc tasks.
        $this->setUser($user_one);

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment_generator->create_comment(
            $user_two_discussion->get_id(),
            'container_workspace',
            discussion::AREA
        );

        // Run adhoc tasks and check the message - since user two had turned off the notification
        // for this specific workspace - hence we should not expect any message(s) sent to the user two.
        $message_sink = $this->redirectMessages();
        $this->executeAdhocTasks();

        $messages = $message_sink->get_messages();
        $this->assertEmpty($messages);
    }

    /**
     * @return void
     */
    public function test_sending_message_to_discussion_owner_that_is_same_owner_with_comment(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Create a discussion by this user.
        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        // Clean up the adhoc tasks first.
        $this->executeAdhocTasks();

        // Create comment for the discussion.
        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment_generator->create_comment(
            $discussion->get_id(),
            'container_workspace',
            discussion::AREA
        );

        $message_sink = $this->redirectMessages();
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
                    'comment_on_discussion_title' => 'Fake language subject string'
                ]
            ]
        );

        $user_one = $generator->create_user();
        self::setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Join the workspace as user two and create a discussion.
        $user_two = $generator->create_user(['lang' => $fake_language]);
        self::setUser($user_two);
        member::join_workspace($workspace);
        $user_two_discussion = $workspace_generator->create_discussion($workspace->get_id());

        // Clear adhoc tasks.
        $this->executeAdhocTasks();

        // As user one, create a comment on the discussion.
        self::setUser($user_one);
        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment_generator->create_comment(
            $user_two_discussion->get_id(),
            'container_workspace',
            discussion::AREA
        );

        // Start the sink.
        $message_sink = $this->redirectMessages();

        // Execute the adhoc tasks.
        $this->executeAdhocTasks();

        // There should be a message sent out to user two - as the comment was created by user one.
        $messages = $message_sink->get_messages();

        self::assertCount(1, $messages);
        $message = reset($messages);

        self::assertEquals($user_two->id, $message->useridto);
        self::assertEquals('Fake language subject string', $message->subject);
    }
}