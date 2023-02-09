<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package mod_perform
 * @category totara_notification
 */

use core\json_editor\helper\document_helper;
use core\json_editor\node\paragraph;
use core\orm\query\builder;
use core_phpunit\testcase;
use core_user\totara_notification\placeholder\user as user_placeholder;
use mod_perform\task\send_participant_instance_creation_notifications_task as participants_task;
use mod_perform\testing\generator as perform_generator;
use mod_perform\totara_notification\recipient\participant as participant_recipient;
use mod_perform\totara_notification\resolver\participant_instance_created_resolver;
use mod_perform\totara_notification\placeholder\participant_instance as participant_instance_placeholder;
use mod_perform\totara_notification\placeholder\perform_activity as perform_activity_placeholder;
use totara_core\extended_context;
use totara_job\job_assignment;
use totara_notification\entity\notifiable_event_queue;
use totara_notification\entity\notification_queue;
use totara_notification\json_editor\node\placeholder;
use totara_notification\task\process_event_queue_task as notifications_task;
use totara_notification\testing\generator as notification_generator;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_notification
 */
class mod_perform_totara_notification_resolver_instance_creation_resolver_testcase extends testcase {

    private $generator = null;
    private $perform_generator = null;
    private $notification_generator = null;
    private $message_sink = null;
    private $user = null;
    private $manager = null;

    /**
     * @return void
     * @throws coding_exception
     */
    protected function setUp(): void {
        global $DB;

        parent::setUp();

        // Get the generators.
        $this->generator = self::getDataGenerator();
        $this->perform_generator = perform_generator::instance();
        $this->notification_generator = notification_generator::instance();

        // Ensure all are empty.
        $DB->delete_records('notifiable_event_queue');
        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        self::setAdminUser();
        $this->message_sink = self::redirectMessages();

        // Create some users.
        $this->user = $this->generator->create_user(['firstname' => 'Uma', 'lastname' => 'Thurman']);
        $this->manager = $this->generator->create_user(['firstname' => 'John' , 'lastname' => 'Travolta']);

        // Assign the manager to the user.
        $manager_job = job_assignment::create(['userid' => $this->manager->id, 'idnumber' => 'job1']);
        job_assignment::create([
            'userid' => $this->user->id,
            'idnumber' => 'userjob1',
            'managerjaid' => $manager_job->id
        ]);
    }

    /**
     * @return void
     */
    public function tearDown(): void {

        $this->generator = null;
        $this->perform_generator = null;
        $this->notification_generator = null;
        $this->message_sink = null;
        $this->user = null;
        $this->manager = null;

        parent::tearDown();
    }

    /**
     * Test that notificatios are created on event.
     */
    public function test_notification_resolver_instance_creation_on_event() {
        global $DB;

        // Delete built-in notifications.
        builder::table('notification_preference')->delete();

        // Create a participant instance creation notification.
        $this->system_custom = $this->notification_generator->create_notification_preference(
            participant_instance_created_resolver::class,
            extended_context::make_system(),
            [
                'schedule_offset' => 0,
                'recipient' => participant_recipient::class,
                'recipients' => [participant_recipient::class],
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('Instances created for appraisal participants'),
                        paragraph::create_json_node_with_content_nodes([
                            placeholder::create_node_from_key_and_label('recipient:full_name', 'John Smith'),
                            placeholder::create_node_from_key_and_label('subject_user:last_name', 'Thurman'),
                            placeholder::create_node_from_key_and_label('participant_instance:participant_full_name','Leo Learner'),
                            placeholder::create_node_from_key_and_label('perform_activity:name','Mid-year appraisal'),
                            placeholder::create_node_from_key_and_label('perform_activity:type','Appraisal'),
                        ]),
                    ])
                ),
                'subject' => 'Appraisal participant instance created',
                'subject_format' => FORMAT_PLAIN,
                'additional_criteria' => '{"recipients":["subject","manager"]}',
            ]
        );

        // Create the perform instances
        $this->perform_generator->create_subject_instance([
            'activity_name' => 'Notifiable appraisal',
            'subject_user_id' => $this->user->id,
            'other_participant_id' => $this->manager->id,
            'subject_is_participating' => true,
        ]);

        $instance_ids = $DB->get_records('perform_participant_instance', [], '', 'id');
        $task = participants_task::create_for_new_participants(array_keys($instance_ids));
        $task->execute();

        self::assertEquals(2, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $task = new notifications_task();
        $task->execute();

        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $messages = $this->message_sink->get_messages();
        // Default notifications have already been removed.
        self::assertCount(2, $messages);

        foreach ($messages as $message) {
            self::assertTrue(in_array($message->useridto, [$this->user->id, $this->manager->id]));
            self::assertSame($message->subject, 'Appraisal participant instance created');
        }
    }

    /**
     * Test that the scheduler is working as expected.
     */
    public function test_notification_resolver_instance_creation_schedule() {
        $now = time();

        // Create a participant instance creation notification.
        $this->system_custom = $this->notification_generator->create_notification_preference(
            participant_instance_created_resolver::class,
            extended_context::make_system(),
            [
                'schedule_offset' => 0,
                'recipient' => participant_recipient::class,
                'recipients' => [participant_recipient::class],
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('Instances created for appraisal participants'),
                        paragraph::create_json_node_with_content_nodes([
                            placeholder::create_node_from_key_and_label('recipient:full_name', 'John Smith'),
                            placeholder::create_node_from_key_and_label('subject_user:last_name', 'Thurman'),
                            placeholder::create_node_from_key_and_label('participant_instance:participant_full_name','Leo Learner'),
                            placeholder::create_node_from_key_and_label('perform_activity:name','Mid-year appraisal'),
                            placeholder::create_node_from_key_and_label('perform_activity:type','Appraisal'),
                        ]),
                    ])
                ),
                'subject' => 'Appraisal participant instance created',
                'subject_format' => FORMAT_PLAIN,
                'additional_criteria' => '{"recipients":["subject","manager"]}',
            ]
        );

        // Create the perform instances
        $subject = $this->perform_generator->create_subject_instance([
            'activity_name' => 'Notifiable appraisal',
            'subject_user_id' => $this->user->id,
            'other_participant_id' => $this->manager->id,
            'subject_is_participating' => true,
        ]);

        $items = participant_instance_created_resolver::get_scheduled_events($now + MINSECS, $now + DAYSECS)->to_array();
        self::assertEmpty($items);

        $items = participant_instance_created_resolver::get_scheduled_events($now - DAYSECS, $now - MINSECS)->to_array();
        self::assertEmpty($items);

        $items = participant_instance_created_resolver::get_scheduled_events($now - MINSECS, $now + MINSECS)->to_array();
        self::assertCount(2, $items);

        foreach ($items as $item) {
            // Check the expected values.
            self::assertTrue(in_array($item->participant_id, [$this->user->id, $this->manager->id]));
            self::assertEquals($subject->id, $item->subject_instance_id);
            self::assertEquals($this->user->id, $item->subject_user_id);
            // Just check these are here.
            self::assertNotEmpty($item->participant_instance_id);
            self::assertNotEmpty($item->activity_id);
        }
    }
}
