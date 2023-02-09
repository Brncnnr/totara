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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_approval
 */

use core\json_editor\helper\document_helper;
use core\json_editor\node\paragraph;
use mod_approval\model\application\application;
use mod_approval\model\application\application_state;
use mod_approval\totara_notification\recipient\applicant_manager;
use mod_approval\totara_notification\resolver\stage_comment_created as stage_comment_created_resolver;
use totara_comment\comment_helper as comment_helper;
use totara_comment\totara_notification\placeholder\comment;
use totara_core\extended_context;
use totara_notification\entity\notifiable_event_queue;
use totara_notification\entity\notification_queue;
use totara_notification\task\process_event_queue_task;
use totara_notification\task\process_notification_queue_task;
use totara_notification\testing\generator as notification_generator;
use totara_notification\json_editor\node\placeholder;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/totara_notification_stage_base.php');

/**
 * @group approval_workflow
 * @group totara_notification
 */
class mod_approval_totara_notification_stage_comment_created_testcase extends mod_approval_totara_notification_stage_base_testcase {

    public function test_custom_notification(): void {
        global $DB;

        $data = $this->setup_applications();

        // Create a custom notification in event context.
        $extended_context = extended_context::make_with_context(
            $data->application1->workflow_version->workflow->get_context()
        );
        $notification_generator = notification_generator::instance();
        $notification_generator->create_notification_preference(
            stage_comment_created_resolver::class,
            $extended_context,
            [
                'schedule_offset' => 0,
                'recipient' => applicant_manager::class,
                'recipients' => [applicant_manager::class],
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('Test create notification body'),
                        paragraph::create_json_node_with_content_nodes([
                            placeholder::create_node_from_key_and_label('recipient:last_name', 'Recipient last name'),
                            placeholder::create_node_from_key_and_label('applicant:full_name', 'Applicant full name'),
                            placeholder::create_node_from_key_and_label('application:title', 'Application title'),
                            placeholder::create_node_from_key_and_label(
                                'applicant_job_assignment_organisation:full_name',
                                'Organisation full name'
                            ),
                            placeholder::create_node_from_key_and_label(
                                'workflow_stage:name',
                                'Workflow stage name'
                            ),
                            placeholder::create_node_from_key_and_label(
                                'comment:content_text',
                                'Comment Text'
                            ),
                        ]),
                    ])
                ),
                'subject' => 'Test create notification subject',
                'subject_format' => FORMAT_PLAIN,
            ]
        );

        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Trigger the event for user1 and application1.

        // Mark it not draft.
        /** @var application $application1 */
        $application1 = $data->application1;
        $application1->set_current_state(new application_state($application1->current_state->get_stage_id()));

        $comment = comment_helper::create_comment(
            'mod_approval',
            'comment',
            $application1->id,
            'This is some comment',
            FORMAT_PLAIN
        );

        self::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Redirect messages.
        $sink = self::redirectMessages();

        // Run tasks.
        $task = new process_event_queue_task();
        $task->execute();

        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $messages = $sink->get_messages();
        self::assertCount(1, $messages);
        $message = reset($messages);

        self::assertEquals('Test create notification subject', $message->subject);
        self::assertStringContainsString('Test create notification body', $message->fullmessage);
        self::assertStringContainsString($data->applicant1_manager->lastname, $message->fullmessage);
        self::assertStringContainsString($data->applicant1->firstname, $message->fullmessage);
        self::assertStringContainsString($data->application1->title, $message->fullmessage);
        self::assertStringContainsString($data->framework->agency->subagency_a->program_a->fullname, $message->fullmessage);
        self::assertStringContainsString($data->workflow->versions->first()->stages->first()->name, $message->fullmessage);
        self::assertStringContainsString($comment->get_content_text(), $message->fullmessage);
        self::assertEquals($data->applicant1_manager->id, $message->userto->id);
    }
}
