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
 * @author Gihan Hewaralalage <gihan.hewaralalage@totaralearning.com>
 * @package mod_perform
 * @category totara_notification
 */

use core\json_editor\helper\document_helper;
use core\json_editor\node\paragraph;
use core_user\totara_notification\placeholder\user as user_placeholder;
use core_phpunit\testcase;
use mod_perform\constants;
use mod_perform\event\subject_instance_progress_updated;
use mod_perform\models\activity\element;
use mod_perform\models\activity\section_element;
use mod_perform\models\activity\subject_instance as subject_instance_model;
use mod_perform\models\response\participant_section;
use mod_perform\state\subject_instance\complete;
use mod_perform\observers\notification as notification_observer;
use mod_perform\testing\generator as perform_generator;
use mod_perform\totara_notification\recipient\subject as subject_user_recipient;
use mod_perform\totara_notification\resolver\participant_completion_resolver;
use mod_perform\totara_notification\recipient\manager as manager_user_recipient;
use mod_perform\totara_notification\placeholder\participant_instance as participant_instance_placeholder;
use mod_perform\totara_notification\placeholder\perform_activity as perform_activity_placeholder;
use totara_core\extended_context;
use totara_core\relationship\relationship;
use totara_job\job_assignment;
use totara_notification\entity\notifiable_event_queue;
use totara_notification\entity\notification_queue;
use totara_notification\json_editor\node\placeholder;
use totara_notification\task\process_event_queue_task as notifications_task;
use totara_notification\testing\generator as notification_generator;

class mod_perform_totara_notification_resolver_participant_completion_resolver_testcase extends testcase {

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
        $this->user = $this->generator->create_user([
            'firstname' => 'Learner',
            'lastname' => 'Recipient',
            'idnumber' => 'user101'
        ]);

        $this->manager = $this->generator->create_user([
            'firstname' => 'Manager',
            'lastname' => 'Recipient',
            'idnumber' => 'user102',
        ]);

        // Assign the manager to the user.
        $manager_job = job_assignment::create(['userid' => $this->manager->id, 'idnumber' => 'job1']);
        job_assignment::create([
            'userid' => $this->user->id,
            'idnumber' => 'userjob1',
            'managerjaid' => $manager_job->id
        ]);

        // Create a participant_completion_resolver notification for subject.
        $this->system_custom = $this->notification_generator->create_notification_preference(
            participant_completion_resolver::class,
            extended_context::make_system(),
            [
                'schedule_offset' => 0,
                'recipients' => [subject_user_recipient::class],
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('Instances created for appraisal participants'),
                        paragraph::create_json_node_with_content_nodes([
                            placeholder::create_node_from_key_and_label('recipient:full_name', 'Learner Recipient'),
                            placeholder::create_node_from_key_and_label('subject_user:id_number', 'user101'),
                            placeholder::create_node_from_key_and_label('perform_activity:name','Notifiable appraisal'),
                            placeholder::create_node_from_key_and_label('perform_activity:type','Appraisal'),
                        ]),
                    ])
                ),
                'subject' => 'Participant completion of subject instance',
                'subject_format' => FORMAT_PLAIN,
            ]
        );

        // Create a participant_completion_resolver notification for manager.
        $this->system_custom = $this->notification_generator->create_notification_preference(
            participant_completion_resolver::class,
            extended_context::make_system(),
            [
                'schedule_offset' => 0,
                'recipients' => [manager_user_recipient::class],
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('Instances created for appraisal participants'),
                        paragraph::create_json_node_with_content_nodes([
                            placeholder::create_node_from_key_and_label('recipient:full_name', 'Manager Recipient'),
                            placeholder::create_node_from_key_and_label('subject_user:id_number', 'user101'),
                            placeholder::create_node_from_key_and_label('perform_activity:name','Notifiable appraisal'),
                            placeholder::create_node_from_key_and_label('perform_activity:type','Appraisal'),
                        ]),
                    ])
                ),
                'subject' => 'Participant completion of subject instance',
                'subject_format' => FORMAT_PLAIN,
            ]
        );
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
    public function test_notification_participant_completion_resolver_on_event() {
        global $DB;

        $subject_instance = $this->perform_generator->create_subject_instance([
            'activity_name' => 'Notifiable appraisal',
            'subject_user_id' => $this->user->id,
            'other_participant_id' => $this->manager->id,
            'subject_is_participating' => true,
        ]);

        $relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_SUBJECT);

        $activity = $this->perform_generator->create_activity_in_container();
        $section = $this->perform_generator->create_section($activity);

        $participant_section = $this->perform_generator->create_participant_instance_and_section(
            $activity, $this->user, $subject_instance->id, $section, $relationship->id
        );

        participant_section::load_by_entity($participant_section);
        $element = element::create($activity->get_context(), 'long_text', 'test element 1 title');
        section_element::create($section, $element, 123);
        $activity->activate();

        $participant_instances = $DB->get_records('perform_participant_instance');
        foreach ($participant_instances as $participant) {
            $this->assertEquals($subject_instance->id, $participant->subject_instance_id);
        }

        self::assertCount(3, $participant_instances);

        $event1 = subject_instance_progress_updated::create_from_subject_instance(subject_instance_model::load_by_entity($subject_instance));

        $this->message_sink->clear();
        $subject_instance->progress = complete::get_code();
        $subject_instance->save();

        notification_observer::send_completion_notification($event1);

        self::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $task = new notifications_task();
        $task->execute();

        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $messages = $this->message_sink->get_messages();
        self::assertCount(2, $messages);

        foreach ($messages as $message) {
            self::assertSame($message->subject, 'Participant completion of subject instance');
            self::assertTrue(in_array($message->useridto, [$this->user->id, $this->manager->id]));
            switch ($message->useridto) {
                case $this->user->id:
                    self::assertStringContainsString('Learner Recipient', $message->fullmessage);
                    self::assertStringContainsString('user101', $message->fullmessage);
                    self::assertStringContainsString('Notifiable appraisal', $message->fullmessage);
                    self::assertStringContainsString('Appraisal', $message->fullmessage);
                    break;
                case $this->manager->id:
                    self::assertStringContainsString('Manager Recipient', $message->fullmessage);
                    self::assertStringContainsString('user101', $message->fullmessage);
                    self::assertStringContainsString('Notifiable appraisal', $message->fullmessage);
                    self::assertStringContainsString('Appraisal', $message->fullmessage);
                    break;
                default:
                    self::fail('unexpected recipient');
            }
        }

        $this->message_sink->clear();
    }
}