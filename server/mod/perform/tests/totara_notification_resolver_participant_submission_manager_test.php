<?php
/**
 * This file is part of Totara Perform
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_perform
 */

use core_phpunit\testcase;
use core\collection;
use core\orm\entity\entity;
use core_user\totara_notification\placeholder\user as user_placeholder;
use core\json_editor\helper\document_helper;
use core\json_editor\node\paragraph;
use core\orm\query\builder;

use mod_perform\totara_notification\recipient\manager;
use mod_perform\totara_notification\resolver\participant_instance_completion_resolver;
use totara_core\extended_context;
use totara_job\job_assignment;
use totara_notification\entity\notifiable_event_queue;
use totara_notification\entity\notification_queue;
use totara_notification\json_editor\node\placeholder;
use totara_notification\task\process_event_queue_task;
use totara_notification\testing\generator as notification_generator;

use mod_perform\event\participant_instance_progress_updated;
use mod_perform\entity\activity\participant_section as participant_section_entity;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\models\response\section_element_response;
use mod_perform\models\response\participant_section;
use mod_perform\totara_notification\recipient\subject as subject_user_recipient;
use mod_perform\totara_notification\placeholder\participant_instance as participant_instance_placeholder;
use mod_perform\totara_notification\placeholder\perform_activity as perform_activity_placeholder;
use mod_perform\testing\generator as perform_generator;

defined('MOODLE_INTERNAL') || die();

/**
 * @group mod_perform
 * @group totara_notification
 */
class mod_perform_totara_notification_resolver_participant_submission_manager_testcase extends testcase {

    private $participant_section = null;
    private $user = null;

    public function test_resolver_participant_submission_manager(): void {
        global $DB;

        $completion_success = $this->participant_section->complete();
        self::assertTrue($completion_success);

        // Ensure all are empty.
        $DB->delete_records('notifiable_event_queue');
        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $event = participant_instance_progress_updated::create_from_participant_instance($this->participant_section->participant_instance);
        $event->trigger();

        self::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE, ['resolver_class_name' => participant_instance_completion_resolver::class]));
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

        self::assertEquals('Leo Learner has submitted their responses to their Year End Appraisal', $message->subject);
        self::assertStringContainsString('Hi John Smith, Leo Learner has submitted their responses to their Year-end Appraisal. You can review the activity through this link: Year end', $message->fullmessage);
        self::assertStringContainsString('', $message->fullmessage);
        self::assertStringContainsString('Uma', $message->fullmessage);
        self::assertStringContainsString('Thurman', $message->fullmessage);
        self::assertStringContainsString('John', $message->fullmessage);
        self::assertStringContainsString('Travolta', $message->fullmessage);
        self::assertStringContainsString('Mid-year appraisal', $message->fullmessage);
        self::assertStringContainsString('Appraisal', $message->fullmessage); // Event
        self::assertEquals($this->user->id, $message->userto->id);
    }

    /**
     * @return void
     */
    public function tearDown(): void {

        $this->user = null;
        $this->participant_section = null;

        parent::tearDown();
    }

    /**
     * @return void
     * @throws coding_exception
     */
    protected function setUp(): void {
        parent::setUp();

        // Delete built-in notifications.
        builder::table('notification_preference')->delete();

        // Create a custom notification in system context.
        $notification_generator = notification_generator::instance();
        $notification_generator->create_notification_preference(
            participant_instance_completion_resolver::class,
            extended_context::make_system(),
            [
                'schedule_offset' => 0,
                'recipient' => subject_user_recipient::class,
                'recipients' => [subject_user_recipient::class],
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('Hi John Smith, Leo Learner has submitted their responses to their Year-end Appraisal. You can review the activity through this link: Year end'),
                        paragraph::create_json_node_with_content_nodes([
                            placeholder::create_node_from_key_and_label('recipient:full_name', 'John Smith'),
                            placeholder::create_node_from_key_and_label('subject_user:last_name', 'Thurman'),
                            placeholder::create_node_from_key_and_label('participant_user:full_name','Leo Learner'),
                            placeholder::create_node_from_key_and_label('perform_activity:name','Mid-year appraisal'),
                            placeholder::create_node_from_key_and_label('perform_activity:type','Appraisal'),
                        ]),
                    ])
                ),
                'subject' => 'Leo Learner has submitted their responses to their Year End Appraisal',
                'subject_format' => FORMAT_PLAIN,
                'additional_criteria' => '{"submitted_by":["manager"]}',
            ]
        );

        $notification_generator->create_notification_preference(
            participant_instance_completion_resolver::class,
            extended_context::make_system(),
            [
                'schedule_offset' => 0,
                'recipient' => manager::class,
                'recipients' => [manager::class],
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('Hi John Smith, Leo Learner has submitted their responses to their Year-end Appraisal. You can review the activity through this link: Year end'),
                        paragraph::create_json_node_with_content_nodes([
                            placeholder::create_node_from_key_and_label('recipient:full_name', 'John Smith'),
                            placeholder::create_node_from_key_and_label('subject_user:last_name', 'Thurman'),
                            placeholder::create_node_from_key_and_label('participant_user:full_name','Leo Learner'),
                            placeholder::create_node_from_key_and_label('perform_activity:name','Mid-year appraisal'),
                            placeholder::create_node_from_key_and_label('perform_activity:type','Appraisal'),
                        ]),
                    ])
                ),
                'subject' => 'Leo Learner has submitted their responses to their Year End Appraisal',
                'subject_format' => FORMAT_PLAIN,
                'additional_criteria' => '{"submitted_by":["subject"]}',
            ]
        );

        $this->participant_section = participant_section::load_by_entity($this->create_participant_section());

        $responses = new collection([
            $this->create_valid_element_response(),
            $this->create_valid_element_response(),
        ]);

        $this->participant_section->set_section_element_responses($responses);
    }

    /**
     * @param stdClass|null $subject_user
     * @param stdClass|null $other_participant
     * @return participant_section_entity
     */
    private function create_participant_section(
        stdClass $subject_user = null
    ): entity {

        $generator = self::getDataGenerator();

        /** @var mod_perform\testing\generator $perform_generator */
        $perform_generator = perform_generator::instance();

        $this->user = $generator->create_user(['firstname' => 'Uma', 'lastname' => 'Thurman']);
        // Create a manager.
        $manager = $generator->create_user(['firstname' => 'John' , 'lastname' => 'Travolta']);

        // Assign the manager to the user.
        /** @var job_assignment $manager1job */
        $manager1job = job_assignment::create(['userid' => $manager->id, 'idnumber' => 'job1']);
        job_assignment::create([
            'userid' => $this->user->id,
            'idnumber' => 'userjob1',
            'managerjaid' => $manager1job->id
        ]);

        self::setUser($manager->id);

        $subject_user_id = $subject_user ? $subject_user->id : $this->user->id;
        $other_participant_id = $manager->id;

        $perform_generator->create_subject_instance([
            'activity_name' => 'Mid-year appraisal',
            'subject_user_id' => $subject_user_id,
            'other_participant_id' => $other_participant_id,
            'subject_is_participating' => true,
        ]);

        return participant_section_entity::repository()
            ->join([participant_instance_entity::TABLE, 'pi'], 'participant_instance_id', '=', 'id')
            ->where('pi.participant_id', $other_participant_id)
            ->one();
    }

    private function create_valid_element_response(): section_element_response {
        return new class extends section_element_response {
            public $was_saved = false;

            public function __construct() {
            }

            public function save(): section_element_response {
                $this->was_saved = true;
                return $this;
            }

            public function validate_response($is_draft_validation = false): bool {
                $this->validation_errors = new collection();
                return true;
            }
        };
    }
}
