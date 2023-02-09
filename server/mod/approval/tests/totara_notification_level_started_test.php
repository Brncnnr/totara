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

use core\entity\user;
use core\json_editor\helper\document_helper;
use core\json_editor\node\paragraph;
use mod_approval\entity\application\application_activity as application_activity_entity;
use mod_approval\event\level_started as level_started_event;
use mod_approval\model\application\activity\level_started as level_started_activity;
use mod_approval\model\application\application_activity as application_activity_model;
use mod_approval\model\workflow\workflow;
use mod_approval\totara_notification\recipient\applicant_manager;
use mod_approval\totara_notification\resolver\level_started as level_started_resolver;
use totara_core\extended_context;
use totara_notification\entity\notifiable_event_queue;
use totara_notification\entity\notification_queue;
use totara_notification\task\process_event_queue_task;
use totara_notification\task\process_notification_queue_task;
use totara_notification\testing\generator as notification_generator;
use totara_notification\json_editor\node\placeholder;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/totara_notification_level_base.php');

/**
 * @group approval_workflow
 * @group totara_notification
 */
class mod_approval_totara_notification_level_started_testcase extends mod_approval_totara_notification_level_base_testcase {

    public function test_custom_notification(): void {
        global $DB;

        $data = $this->setup_applications();
        $workflow = workflow::load_by_entity($data->workflow);
        $stage_1 = $workflow->latest_version->stages->first();
        $stage_2 = $workflow->latest_version->get_next_stage($stage_1->id);
        $stage_2_approval_level_1 = $stage_2->approval_levels->first();

        $extended_context = extended_context::make_with_context(
            $workflow->get_context(),
            'mod_approval',
            'workflow_stage',
            $stage_2->id
        );

        // Create a custom notification in workflow stage context.
        $notification_generator = notification_generator::instance();
        $notification_generator->create_notification_preference(
            level_started_resolver::class,
            $extended_context,
            [
                'schedule_offset' => 0,
                'recipient' => applicant_manager::class,
                'recipients' => [applicant_manager::class],
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('Test notification body'),
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
                                'approval_level:name',
                                'Approval level name'
                            ),
                        ]),
                    ])
                ),
                'additional_criteria' => json_encode([
                    'approval_level_id' => $stage_2_approval_level_1->id,
                ]),
                'subject' => 'Test notification subject',
                'subject_format' => FORMAT_PLAIN,
                'enabled' => true,
            ]
        );

        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Trigger the event for user1 and application1.
        $level_started_event = level_started_event::create_from_application($data->application1);
        $level_started_event->trigger();

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
        // Only one notification was processed, because the other built-in notifs were disabled.
        self::assertCount(1, $messages);
        $message = reset($messages);

        self::assertEquals('Test notification subject', $message->subject);
        self::assertStringContainsString('Test notification body', $message->fullmessage);
        self::assertStringContainsString($data->applicant1_manager->lastname, $message->fullmessage);
        self::assertStringContainsString($data->applicant1->firstname, $message->fullmessage);
        self::assertStringContainsString($data->application1->title, $message->fullmessage);
        self::assertStringContainsString($data->framework->agency->subagency_a->program_a->fullname, $message->fullmessage);
        self::assertStringContainsString($stage_2->name, $message->fullmessage);
        self::assertStringContainsString(
            $stage_2_approval_level_1->name,
            $message->fullmessage
        );
        self::assertEquals($data->applicant1_manager->id, $message->userto->id);
    }

    public function test_get_scheduled_events(): void {
        global $DB;

        $resolver_class_name = level_started_resolver::class;

        $data = $this->setup_applications();
        $workflow = workflow::load_by_entity($data->workflow);
        $stage_1 = $workflow->latest_version->stages->first();
        $stage_2 = $workflow->latest_version->get_next_stage($stage_1->id);
        $stage_2_approval_level_1 = $stage_2->approval_levels->first();

        $actor1 = new user($this->getDataGenerator()->create_user());
        $actor2 = new user($this->getDataGenerator()->create_user());

        $now = time();
        // No scheduled events because no activity.
        self::assert_scheduled_events($resolver_class_name, 0, $now + 1, []);

        // Add application1 activity - current time.
        application_activity_model::create(
            $data->application1,
            $actor1->id,
            level_started_activity::class
        );
        $activity_record = $DB->get_record(application_activity_entity::TABLE, ['user_id' => $actor1->id]);
        $activity_record->timestamp = $now;
        $DB->update_record(application_activity_entity::TABLE, $activity_record);

        // Add application2 activity - one hour ago.
        application_activity_model::create(
            $data->application2,
            $actor2->id,
            level_started_activity::class
        );
        $activity_record = $DB->get_record(application_activity_entity::TABLE, ['user_id' => $actor2->id]);
        $activity_record->timestamp = $now - HOURSECS;
        $DB->update_record(application_activity_entity::TABLE, $activity_record);

        // Empty result for min_time after activity time.
        self::assert_scheduled_events($resolver_class_name, $now + 1, $now + 2, []);
        // Empty result for max_time before activity time.
        self::assert_scheduled_events($resolver_class_name, $now - MINSECS, $now - 1, []);
        // Empty result for max_time = time.
        self::assert_scheduled_events($resolver_class_name, $now - MINSECS, $now, []);
        // Result expected for min_time = time.
        self::assert_scheduled_events($resolver_class_name, $now, $now + 1, [
            [
                'application_id' => $data->application1->id,
                'workflow_stage_id' => $stage_2->id,
                'approval_level_id' => $stage_2_approval_level_1->id,
                'time_started' => $now,
            ],
        ]);
        // Result expected for min_time < activity time.
        self::assert_scheduled_events($resolver_class_name, $now - 1, $now + 1, [
            [
                'application_id' => $data->application1->id,
                'workflow_stage_id' => $stage_2->id,
                'approval_level_id' => $stage_2_approval_level_1->id,
                'time_started' => $now,
            ],
        ]);

        // Only application2 exists.
        self::assert_scheduled_events($resolver_class_name, $now - DAYSECS, $now - 1, [
            [
                'application_id' => $data->application2->id,
                'workflow_stage_id' => $stage_2->id,
                'approval_level_id' => $stage_2_approval_level_1->id,
                'time_started' => $now - HOURSECS,
            ],
        ]);

        // Both applications included in time period.
        self::assert_scheduled_events($resolver_class_name, $now - DAYSECS, $now + 1, [
            [
                'application_id' => $data->application1->id,
                'workflow_stage_id' => $stage_2->id,
                'approval_level_id' => $stage_2_approval_level_1->id,
                'time_started' => $now,
            ],
            [
                'application_id' => $data->application2->id,
                'workflow_stage_id' => $stage_2->id,
                'approval_level_id' => $stage_2_approval_level_1->id,
                'time_started' => $now - HOURSECS,
            ],
        ]);
    }
}
