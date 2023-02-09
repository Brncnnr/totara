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
 * @author Ben Fesili <ben.fesili@totaralearning.com>
 * @package mod_perform
 * @category totara_notification
 */

use core\json_editor\helper\document_helper;
use core\json_editor\node\paragraph;
use core_phpunit\testcase;
use core_user\totara_notification\placeholder\user as user_placeholder;
use mod_perform\constants;
use mod_perform\entity\activity\activity;
use mod_perform\entity\activity\track as track_entity;
use mod_perform\expand_task;
use mod_perform\models\activity\notification;
use mod_perform\models\activity\track;
use mod_perform\state\activity\active;
use mod_perform\state\activity\draft;
use mod_perform\task\service\manual_participant_progress;
use mod_perform\task\service\subject_instance_creation;
use mod_perform\testing\generator as perform_generator;
use mod_perform\totara_notification\placeholder\participant_instance as participant_instance_placeholder;
use mod_perform\totara_notification\placeholder\perform_activity as perform_activity_placeholder;
use mod_perform\totara_notification\recipient\participant_selector_subject;
use mod_perform\totara_notification\resolver\participant_selection_resolver;
use totara_core\extended_context;
use totara_core\relationship\relationship;
use totara_core\relationship\relationship as relationship_model;
use totara_job\job_assignment;
use totara_notification\entity\notifiable_event_queue;
use totara_notification\entity\notification_queue;
use totara_notification\json_editor\node\placeholder;
use totara_notification\local\schedule_helper;
use totara_notification\schedule\schedule_after_event;
use totara_notification\task\process_event_queue_task as notifications_task;
use totara_notification\task\process_scheduled_event_task;
use totara_notification\testing\generator as notification_generator;

defined('MOODLE_INTERNAL') || die();

class participant_selection_resolver_test extends testcase {
    private $generator = null;
    private $perform_generator = null;
    private $notification_generator = null;
    private $message_sink = null;
    private $user = null;
    private $manager = null;

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

    public function test_resolver() {
        global $DB;
        $this->message_sink->clear();

        // Create a participant selection notification.
        $this->system_custom = $this->notification_generator->create_notification_preference(
            participant_selection_resolver::class,
            extended_context::make_system(),
            [
                'schedule_offset' => 0,
                'recipient' => participant_selector_subject::class,
                'recipients' => [participant_selector_subject::class],
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('Participant selection is required'),
                        paragraph::create_json_node_with_content_nodes([
                            placeholder::create_node_from_key_and_label('recipient:full_name', 'John Smith'),
                            placeholder::create_node_from_key_and_label('subject_user:last_name', 'Thurman'),
                            placeholder::create_node_from_key_and_label('perform_activity:name', 'Mid-year appraisal'),
                            placeholder::create_node_from_key_and_label('perform_activity:type', 'Appraisal'),
                        ]),
                    ])
                ),
                'subject' => 'Participant Selection Required',
                'subject_format' => FORMAT_PLAIN,
            ]
        );

        $data = $this->create_data(true, true);
        $progress_service = new manual_participant_progress();
        $progress_service->generate();

        // The uses_on_event_queue is set to false. The scheduled task handles both on-event and scheduled events.
        $now = time();
        $schedule_task = new process_scheduled_event_task();
        set_config(process_scheduled_event_task::LAST_RUN_TIME_NAME, $now - MINSECS * 2, 'totara_notification');
        $schedule_task->set_time_now($now + MINSECS * 2);
        $schedule_task->execute();

        $messages = $this->message_sink->get_messages();
        // Participant selection default notification is enabled.
        self::assertCount(6, $messages);

        $this->message_sink->clear();
        foreach ($messages as $message) {
            self::assertTrue(in_array($message->useridto, [$data->user1->id, $data->user2->id, $data->manager1->id]));
            // Participant selection default notification is enabled.
            if ($message->subject != 'Participant Selection Required') {
                self::assertSame($message->subject, 'Select participants for test performance activity Appraisal');
            }
        }
    }

    public function test_resolver_schedule_notification() {
        global $DB;
        $this->message_sink->clear();

        // Create a participant selection notification.
        $this->system_custom = $this->notification_generator->create_notification_preference(
            participant_selection_resolver::class,
            extended_context::make_system(),
            [
                'schedule_offset' => schedule_after_event::default_value(1),
                'recipient' => participant_selector_subject::class,
                'recipients' => [participant_selector_subject::class],
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('Participant selection is required'),
                        paragraph::create_json_node_with_content_nodes([
                            placeholder::create_node_from_key_and_label('recipient:full_name', 'John Smith'),
                            placeholder::create_node_from_key_and_label('subject_user:last_name', 'Thurman'),
                            placeholder::create_node_from_key_and_label('perform_activity:name', 'Mid-year appraisal'),
                            placeholder::create_node_from_key_and_label('perform_activity:type', 'Appraisal'),
                        ]),
                    ])
                ),
                'subject' => 'Reminder: Participant Selection Required',
                'subject_format' => FORMAT_PLAIN,
            ]
        );

        $data = $this->create_data(true, true);
        $progress_service = new manual_participant_progress();
        $progress_service->generate();

        $now = time();
        $task = new process_scheduled_event_task();
        $task->set_time_now($now);

        // Nothing to match with the sending preference.
        $task->execute();
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Clear notifiable event queue.
        $DB->delete_records('notifiable_event_queue');

        // Move the time now to 1 days up.
        $task->set_time_now($now + schedule_helper::days_to_seconds(1) + MINSECS);

        // The last run time is today. Five minutes were added to avoid the current task since the on-event is handled through the schedule.
        set_config(process_scheduled_event_task::LAST_RUN_TIME_NAME, $now + MINSECS * 5, 'totara_notification');
        $task->execute();

        $messages = $this->message_sink->get_messages();
        self::assertCount(3, $messages);

        $this->message_sink->clear();

        foreach ($messages as $message) {
            self::assertTrue(in_array($message->useridto, [$data->user1->id, $data->user2->id, $data->manager1->id]));
            // Participant selection default notification is enabled.
            if ($message->subject != 'Participant Selection Required') {
                self::assertSame($message->subject, 'Reminder: Participant Selection Required');
            }
        }
    }

    /**
     * @param bool $use_per_job_creation
     * @param bool $with_manual_relatioship
     * @return object
     * @throws coding_exception
     * @throws moodle_exception
     */
    protected function create_data(bool $use_per_job_creation = false, bool $with_manual_relatioship = true) {
        $data = new class {
            public $activity1;
            public $track1;
            public $manager1;
            public $manager2;
            public $manager3;
            public $user1;
            public $user2;
            public $job1;
            public $job2;
            public $job3;
        };

        $this->setAdminUser();

        $generator = mod_perform\testing\generator::instance();

        $data->activity1 = $generator->create_activity_in_container([
            'create_track' => true,
            'create_section' => false,
            'activity_status' => draft::get_code()
        ]);
        notification::load_by_activity_and_class_key($data->activity1, 'participant_selection')->activate();
        $manual_relationships = $data->activity1->manual_relationships->all();
        // Update manual relationships to manager.
        $updated_manual_relationships = [];
        $manager_relationship_id = relationship_model::load_by_idnumber(constants::RELATIONSHIP_MANAGER)->id;
        foreach ($manual_relationships as $manual_relationship) {
            $updated_manual_relationships[] = [
                'manual_relationship_id' => $manual_relationship->manual_relationship_id,
                'selector_relationship_id' => $manager_relationship_id,
            ];
        }
        $data->activity1->update_manual_relationship_selections($updated_manual_relationships);

        // Update the activity to active state.
        activity::repository()
            ->where('id', $data->activity1->id)
            ->update(['status' => active::get_code()]);

        /** @var track $track1 */
        $data->track1 = track::load_by_activity($data->activity1)->first();

        $section1 = $generator->create_section($data->activity1, ['title' => 'Section 1']);
        $section2 = $generator->create_section($data->activity1, ['title' => 'Section 2']);
        $section3 = $generator->create_section($data->activity1, ['title' => 'Section 3']);

        $generator->create_section_relationship($section1, ['relationship' => constants::RELATIONSHIP_MANAGER]);
        $generator->create_section_relationship($section1, ['relationship' => constants::RELATIONSHIP_SUBJECT]);

        $generator->create_section_relationship($section2, ['relationship' => constants::RELATIONSHIP_SUBJECT]);
        if ($with_manual_relatioship) {
            $generator->create_section_relationship($section2, ['relationship' => constants::RELATIONSHIP_PEER]);
        }

        $generator->create_section_relationship($section3, ['relationship' => constants::RELATIONSHIP_MANAGER]);
        if ($with_manual_relatioship) {
            $generator->create_section_relationship($section3, ['relationship' => constants::RELATIONSHIP_PEER]);
        }

        if ($use_per_job_creation) {
            set_config('totara_job_allowmultiplejobs', 1);

            $track = new track_entity($data->track1->id);
            $track->subject_instance_generation = track_entity::SUBJECT_INSTANCE_GENERATION_ONE_PER_JOB;
            $track->save();
        }

        $data->manager1 = $this->getDataGenerator()->create_user();
        $manager_job1 = job_assignment::create(['userid' => $data->manager1->id, 'idnumber' => 'jm1']);
        $data->manager2 = $this->getDataGenerator()->create_user();
        $manager_job2 = job_assignment::create(['userid' => $data->manager2->id, 'idnumber' => 'jm2']);
        $data->manager3 = $this->getDataGenerator()->create_user();
        $manager_job3 = job_assignment::create(['userid' => $data->manager3->id, 'idnumber' => 'jm3']);

        $data->user1 = $this->getDataGenerator()->create_user();
        $data->job1 = job_assignment::create(
            [
                'userid' => $data->user1->id,
                'idnumber' => "for-user-{$data->user1->id}",
                'managerjaid' => $manager_job1->id
            ]
        );

        // User two has two job assignments with different managers
        $data->user2 = $this->getDataGenerator()->create_user();
        $data->job2 = job_assignment::create(
            [
                'userid' => $data->user2->id,
                'idnumber' => "for-user-{$data->user2->id}",
                'managerjaid' => $manager_job2->id
            ]
        );
        $data->job3 = job_assignment::create(
            [
                'userid' => $data->user2->id,
                'idnumber' => "for-user-{$data->user2->id}-2",
                'managerjaid' => $manager_job3->id
            ]
        );

        $cohort = $generator->create_cohort_with_users([$data->user1->id, $data->user2->id]);

        $generator->create_track_assignments_with_existing_groups($data->track1, [$cohort->id]);

        /** @var \mod_perform\entity\activity\manual_relationship_selection[] $relationship_selections */
        $relationship_selections = \mod_perform\entity\activity\manual_relationship_selection::repository()->where('activity_id', $data->activity1->get_id())->get();
        $selector_relationship_id = relationship::load_by_idnumber(constants::RELATIONSHIP_SUBJECT)->id;
        foreach ($relationship_selections as $relationship_selection) {
            $relationship_selection->selector_relationship_id = $selector_relationship_id;
            $relationship_selection->save();
        }

        expand_task::create()->expand_all();

        // Generate the subject instances first, they now should be pending
        $subject_instance_service = new subject_instance_creation();
        $subject_instance_service->generate_instances();

        return $data;
    }

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
        $this->manager = $this->generator->create_user(['firstname' => 'John', 'lastname' => 'Travolta']);

        // Assign the manager to the user.
        $manager_job = job_assignment::create(['userid' => $this->manager->id, 'idnumber' => 'job1']);
        job_assignment::create([
            'userid' => $this->user->id,
            'idnumber' => 'userjob1',
            'managerjaid' => $manager_job->id
        ]);
    }

}