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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use core\collection;
use core\json_editor\helper\document_helper;
use core\json_editor\node\paragraph;
use core\task\adhoc_task;
use core\task\manager;
use mod_perform\constants;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use mod_perform\entity\activity\track_user_assignment as track_user_assignment_entity;
use mod_perform\event\subject_instance_progress_updated;
use mod_perform\hook\participant_instances_created;
use mod_perform\models\activity\activity as activity_model;
use mod_perform\models\activity\notification;
use mod_perform\models\activity\subject_instance as subject_instance_model;
use mod_perform\notification\factory;
use mod_perform\notification\recipient;
use mod_perform\notification\trigger;
use mod_perform\observers\notification as notification_observer;
use mod_perform\state\activity\draft;
use mod_perform\state\subject_instance\complete;
use mod_perform\state\subject_instance\pending;
use mod_perform\task\send_participant_instance_creation_notifications_task;
use mod_perform\task\service\participant_instance_dto;
use mod_perform\totara_notification\recipient\subject;
use mod_perform\totara_notification\resolver\participant_completion_resolver;
use mod_perform\totara_notification\resolver\participant_instance_created_resolver;
use mod_perform\watcher\notification as notification_watcher;
use totara_core\extended_context;
use totara_job\job_assignment;
use totara_notification\json_editor\node\placeholder;
use totara_notification\task\process_event_queue_task;
use totara_notification\testing\generator as notification_generator;

require_once(__DIR__ . '/notification_testcase.php');

/**
 * @group perform
 * @group perform_notifications
 */
class mod_perform_notification_watcher_testcase extends mod_perform_notification_testcase {
    /** @var stdClass */
    private $user;

    /** @var stdClass */
    private $manager;

    /** @var stdClass */
    private $appraiser;

    /** @var activity_model */
    private $activity1;

    /** @var subject_instance_entity */
    private $subject_instance1;

    /** @var array<string, participant_instance_entity> */
    private $participant_instances1;

    /** @var activity_model */
    private $activity2;

    /** @var subject_instance_entity */
    private $subject_instance2;

    /** @var array<string, participant_instance_entity> */
    private $participant_instances2;

    public function setUp(): void {
        parent::setUp();
        $this->mock_loader([
            'instance_created' => [
                'class' => mod_perform_mock_broker_one::class,
                'name' => 'instance creation notification',
                'trigger_type' => trigger::TYPE_ONCE,
                'recipients' => recipient::ALL,
            ],
            'completion' => [
                'class' => mod_perform_mock_broker_two::class,
                'name' => 'instance completion notification',
                'trigger_type' => trigger::TYPE_ONCE,
                'recipients' => recipient::ALL,
            ],
        ]);
        $this->setAdminUser();
        $this->message_sink = self::redirectMessages();

        $this->user = $this->getDataGenerator()->create_user(['username' => 'subject']);
        $this->manager = $this->getDataGenerator()->create_user(['username' => 'manager']);
        $this->appraiser = $this->getDataGenerator()->create_user(['username' => 'appraiser']);
        $userja = job_assignment::create_default($this->user->id, ['appraiserid' => $this->appraiser->id]);
        job_assignment::create_default($this->manager->id, ['managerjaid' => $userja->id]);

        [$this->activity1, $this->subject_instance1, $this->participant_instances1] = $this->create_data([
            constants::RELATIONSHIP_SUBJECT => $this->user,
            constants::RELATIONSHIP_MANAGER => $this->manager,
            constants::RELATIONSHIP_APPRAISER => $this->appraiser,
        ]);
        [$this->activity2, $this->subject_instance2, $this->participant_instances2] = $this->create_data([
            constants::RELATIONSHIP_SUBJECT => $this->user,
            constants::RELATIONSHIP_MANAGER => $this->manager,
        ]);
    }

    public function tearDown(): void {
        parent::tearDown();
        $this->user = $this->manager = $this->appraiser = null;
        $this->activity1 = $this->subject_instance1 = $this->participant_instances1 = null;
        $this->activity2 = $this->subject_instance2 = $this->participant_instances2 = null;
    }

    /**
     * @param array<string, stdClass> $relationships
     * @return array of [activity, subject_instance, participant_instances]
     */
    private function create_data(array $relationships): array {
        $activity = $this->create_activity(['activity_status' => draft::get_code()]);
        $section = $this->create_section($activity);
        $this->create_section_relationships($section);
        $track = $this->perfgen->create_activity_tracks($activity, 1)->first();

        $user_assignment = new track_user_assignment_entity();
        $user_assignment->track_id = $track->id;
        $user_assignment->subject_user_id = $this->user->id;
        $user_assignment->deleted = false;
        $user_assignment->save();

        $subject_instance = new subject_instance_entity();
        $subject_instance->track_user_assignment_id = $user_assignment->id;
        $subject_instance->subject_user_id = $this->user->id;
        $subject_instance->status = pending::get_code();
        $subject_instance->save();
        $subject_instance->refresh();

        $notifications = [
            notification::load_by_activity_and_class_key($activity, 'instance_created')->activate(),
            notification::load_by_activity_and_class_key($activity, 'completion')->activate(),
        ];

        $participant_instances = [];
        foreach ($relationships as $idnumber => $user) {
            $relationship = $this->get_core_relationship($idnumber);
            $participant_instances[$idnumber] = $this->perfgen->create_participant_instance($user, $subject_instance->id, $relationship->id);
            foreach ($notifications as $notification) {
                $this->toggle_recipients($notification, [$idnumber => true]);
            }
        }
        return [$activity, $subject_instance, $participant_instances];
    }

    /**
     * @covers \mod_perform\watcher\notification::create_participant_instances
     */
    public function test_create_participant_instances_queues_adhoc_tasks() {
        $hook = new participant_instances_created(new collection([
            participant_instance_dto::create_from_data([
                'id' => $this->participant_instances1[constants::RELATIONSHIP_SUBJECT]->id,
                'activity_id' => $this->activity1->id,
                'core_relationship_id' => $this->participant_instances1[constants::RELATIONSHIP_SUBJECT]->core_relationship_id,
            ]),
            participant_instance_dto::create_from_data([
                'id' => $this->participant_instances1[constants::RELATIONSHIP_MANAGER]->id,
                'activity_id' => $this->activity1->id,
                'core_relationship_id' => $this->participant_instances1[constants::RELATIONSHIP_MANAGER]->core_relationship_id,
            ]),
            participant_instance_dto::create_from_data([
                'id' => $this->participant_instances1[constants::RELATIONSHIP_APPRAISER]->id,
                'activity_id' => $this->activity1->id,
                'core_relationship_id' => $this->participant_instances1[constants::RELATIONSHIP_APPRAISER]->core_relationship_id,
            ]),
            participant_instance_dto::create_from_data([
                'id' => $this->participant_instances2[constants::RELATIONSHIP_SUBJECT]->id,
                'activity_id' => $this->activity2->id,
                'core_relationship_id' => $this->participant_instances1[constants::RELATIONSHIP_SUBJECT]->core_relationship_id,
            ]),
            participant_instance_dto::create_from_data([
                'id' => $this->participant_instances2[constants::RELATIONSHIP_MANAGER]->id,
                'activity_id' => $this->activity2->id,
                'core_relationship_id' => $this->participant_instances1[constants::RELATIONSHIP_MANAGER]->core_relationship_id,
            ]),
        ]));
        notification_watcher::create_participant_instances($hook);

        $custom_data = [
            'participant_instance_ids' => $hook->get_dtos()->pluck('id')
        ];
        $adhoc_task = $this->get_mod_perform_adhoc_task_with_data($custom_data);
        $this->assertNotNull($adhoc_task);
    }

    /**
     * Gets specific adhoc task with data.
     *
     * @param array $custom_data
     * @return stdClass|null
     */
    private function get_mod_perform_adhoc_task_with_data(array $custom_data): ?adhoc_task {
        $tasks = manager::get_adhoc_tasks(send_participant_instance_creation_notifications_task::class);
        $custom_data = json_encode($custom_data);

        foreach ($tasks as $task) {
            if ($task->get_custom_data_as_string() === $custom_data) {
                return $task;
            }
        }
        return null;
    }

    /**
     * @covers \mod_perform\task\send_participant_instance_creation_notifications_task::execute
     * @covers \mod_perform\notification\factory::create_dealer_on_participant_instances
     */
    public function test_create_participant_instances() {
        global $DB;

        // Create a custom notification in system context.
        $notification_generator = notification_generator::instance();
        $notification_generator->create_notification_preference(
            participant_instance_created_resolver::class,
            extended_context::make_system(),
            [
                'schedule_offset' => 0,
                'recipient' => subject::class,
                'recipients' => [subject::class],
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('Participant test notification body'),
                        paragraph::create_json_node_with_content_nodes([
                            placeholder::create_node_from_key_and_label('recipient:first_name', 'Recipient last name'),
                            placeholder::create_node_from_key_and_label('subject_user:last_name', 'Subject last name'),
                        ]),
                    ])
                ),
                'subject' => 'Test participant notification subject',
                'subject_format' => FORMAT_PLAIN,
                'additional_criteria' => '{"recipients":[]}'
            ]
        );

        $hook = new participant_instances_created(new collection([
            participant_instance_dto::create_from_data([
                'id' => $this->participant_instances1[constants::RELATIONSHIP_SUBJECT]->id,
                'activity_id' => $this->activity1->id,
                'core_relationship_id' => $this->participant_instances1[constants::RELATIONSHIP_SUBJECT]->core_relationship_id,
            ]),
            participant_instance_dto::create_from_data([
                'id' => $this->participant_instances1[constants::RELATIONSHIP_MANAGER]->id,
                'activity_id' => $this->activity1->id,
                'core_relationship_id' => $this->participant_instances1[constants::RELATIONSHIP_MANAGER]->core_relationship_id,
            ]),
            participant_instance_dto::create_from_data([
                'id' => $this->participant_instances1[constants::RELATIONSHIP_APPRAISER]->id,
                'activity_id' => $this->activity1->id,
                'core_relationship_id' => $this->participant_instances1[constants::RELATIONSHIP_APPRAISER]->core_relationship_id,
            ]),
            participant_instance_dto::create_from_data([
                'id' => $this->participant_instances2[constants::RELATIONSHIP_SUBJECT]->id,
                'activity_id' => $this->activity2->id,
                'core_relationship_id' => $this->participant_instances1[constants::RELATIONSHIP_SUBJECT]->core_relationship_id,
            ]),
            participant_instance_dto::create_from_data([
                'id' => $this->participant_instances2[constants::RELATIONSHIP_MANAGER]->id,
                'activity_id' => $this->activity2->id,
                'core_relationship_id' => $this->participant_instances1[constants::RELATIONSHIP_MANAGER]->core_relationship_id,
            ]),
        ]));

        $this->message_sink->clear();
        notification_watcher::create_participant_instances($hook);

        $custom_data = [
            'participant_instance_ids' => $hook->get_dtos()->pluck('id')
        ];
        $notification_task = $this->get_mod_perform_adhoc_task_with_data($custom_data);
        $notification_task->execute();

        $this->assertEmpty($this->message_sink->get_messages());
        // Default notifications are enabled.
        $this->assertCount(5, $DB->get_records('notifiable_event_queue'));

        $task = new process_event_queue_task();
        $task->execute();

        // Default notifications are enabled.
        $this->assertCount(5, $this->message_sink->get_messages());
    }

    /**
     * @covers \mod_perform\observers\notification::send_completion_notification
     * @covers \mod_perform\notification\factory::create_dealer_on_subject_instance
     */
    public function test_send_completion_notification() {
        // Create a custom notification in system context.
        $notification_generator = notification_generator::instance();
        $notification_generator->create_notification_preference(
            participant_completion_resolver::class,
            extended_context::make_system(),
            [
                'schedule_offset' => 0,
                'recipients' => [subject::class],
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('Participant test notification body'),
                        paragraph::create_json_node_with_content_nodes([
                            placeholder::create_node_from_key_and_label('recipient:first_name', 'Recipient last name'),
                            placeholder::create_node_from_key_and_label('subject_user:last_name', 'Subject last name'),
                        ]),
                    ])
                ),
                'subject' => 'Test participant notification subject',
                'subject_format' => FORMAT_PLAIN
            ]
        );

        $sink = factory::create_sink();
        $event1 = subject_instance_progress_updated::create_from_subject_instance(subject_instance_model::load_by_entity($this->subject_instance1));
        $event2 = subject_instance_progress_updated::create_from_subject_instance(subject_instance_model::load_by_entity($this->subject_instance2));

        $sink->clear();
        notification_observer::send_completion_notification($event1);
        $this->assertCount(0, $sink->get_all());
        $sink->clear();
        notification_observer::send_completion_notification($event2);

        $this->assertCount(0, $sink->get_all());
        $this->subject_instance1->progress = complete::get_code();
        $this->subject_instance1->save();
        $this->subject_instance2->progress = complete::get_code();
        $this->subject_instance2->save();
        $sink->clear();

        $sink = $this->redirectEvents();

        notification_observer::send_completion_notification($event1);

        $this->message_sink->clear();
        $task = new process_event_queue_task();
        $task->execute();

        // All default notifications are disabled.
        $this->assertCount(1, $this->message_sink->get_messages());

        notification_observer::send_completion_notification($event2);

        $this->message_sink->clear();
        $task = new process_event_queue_task();
        $task->execute();

        // All default notifications are disabled.
        $this->assertCount(1, $this->message_sink->get_messages());
    }
}
