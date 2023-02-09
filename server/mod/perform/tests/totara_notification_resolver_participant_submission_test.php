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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 * @category totara_notification
 */

use core\entity\user as user_entity;
use core\json_editor\helper\document_helper;
use core\json_editor\node\paragraph;
use core\testing\generator as core_generator;
use core_phpunit\testcase;
use core_user\totara_notification\placeholder\user as user_placeholder;
use mod_perform\constants;
use mod_perform\entity\activity\activity;
use mod_perform\entity\activity\external_participant;
use mod_perform\entity\activity\participant_instance;
use mod_perform\entity\activity\subject_instance;
use mod_perform\event\participant_instance_progress_updated;
use mod_perform\models\activity\participant_instance as participant_instance_model;
use mod_perform\models\activity\participant_source;
use mod_perform\models\activity\subject_instance as subject_instance_model;
use mod_perform\state\activity\active;
use mod_perform\state\participant_instance\complete;
use mod_perform\task\service\manual_participant_progress;
use mod_perform\task\service\subject_instance_creation;
use mod_perform\testing\activity_generator_configuration;
use mod_perform\testing\generator as perform_generator;
use mod_perform\totara_notification\placeholder\participant_instance as participant_instance_placeholder;
use mod_perform\totara_notification\placeholder\perform_activity as perform_activity_placeholder;
use mod_perform\totara_notification\recipient\appraiser;
use mod_perform\totara_notification\recipient\direct_report;
use mod_perform\totara_notification\recipient\manager;
use mod_perform\totara_notification\recipient\managers_manager;
use mod_perform\totara_notification\recipient\perform_mentor;
use mod_perform\totara_notification\recipient\perform_peer;
use mod_perform\totara_notification\recipient\perform_reviewer;
use mod_perform\totara_notification\recipient\subject;
use mod_perform\totara_notification\resolver\participant_instance_completion_resolver;
use totara_core\extended_context;
use totara_core\relationship\relationship;
use totara_job\entity\job_assignment as job_assignment_entity;
use totara_job\job_assignment;
use totara_notification\entity\notifiable_event_queue;
use totara_notification\entity\notification_preference;
use totara_notification\entity\notification_queue;
use totara_notification\json_editor\node\placeholder;
use totara_notification\task\process_event_queue_task;
use totara_notification\testing\generator as notification_generator;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_notification
 */
class mod_perform_totara_notification_resolver_participant_submission_appraiser_testcase extends testcase {
    /**
     * @return void
     * @throws coding_exception
     */
    protected function setUp(): void {
        global $DB;

        parent::setUp();

        // Ensure all notification queues are empty.
        $DB->delete_records('notifiable_event_queue');
        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Create a custom notification in system context.
        $notification_generator = notification_generator::instance();
        $notification_generator->create_notification_preference(
            participant_instance_completion_resolver::class,
            extended_context::make_system(),
            [
                'schedule_offset' => 0,
                'recipient' => subject::class,
                'recipients' => [
                    manager::class,
                    managers_manager::class,
                    subject::class
                ],
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
                'additional_criteria' => '{"submitted_by":["appraiser"]}',
            ]
        );

        $notification_generator->create_notification_preference(
            participant_instance_completion_resolver::class,
            extended_context::make_system(),
            [
                'schedule_offset' => 0,
                'recipient' => subject::class,
                'recipients' => [
                    manager::class,
                    subject::class
                ],
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('Hi John Smith, Your Manager\'s manager Boss One has submitted their responses to your Test activity Appraisal. You can review the activity through this link: Year end'),
                        paragraph::create_json_node_with_content_nodes([
                            placeholder::create_node_from_key_and_label('recipient:full_name', 'John Smith'),
                            placeholder::create_node_from_key_and_label('subject_user:last_name', 'Thurman'),
                            placeholder::create_node_from_key_and_label('participant_user:full_name','Leo Learner'),
                            placeholder::create_node_from_key_and_label('perform_activity:name','Mid-year appraisal'),
                            placeholder::create_node_from_key_and_label('perform_activity:type','Appraisal'),
                        ]),
                    ])
                ),
                'subject' => 'Manager has submitted their responses to their Test activity Appraisal',
                'subject_format' => FORMAT_PLAIN,
                'additional_criteria' => '{"submitted_by":["managers_manager"]}',
            ]
        );

        $notification_generator->create_notification_preference(
            participant_instance_completion_resolver::class,
            extended_context::make_system(),
            [
                'schedule_offset' => 0,
                'recipient' => subject::class,
                'recipients' => [
                    manager::class,
                    subject::class
                ],
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('Hi John Smith, Your Direct report Directreport One has submitted their responses to your Test activity Appraisal. You can review the activity through this link: Year end'),
                        paragraph::create_json_node_with_content_nodes([
                            placeholder::create_node_from_key_and_label('recipient:full_name', 'John Smith'),
                            placeholder::create_node_from_key_and_label('subject_user:last_name', 'Thurman'),
                            placeholder::create_node_from_key_and_label('participant_user:full_name','Leo Learner'),
                            placeholder::create_node_from_key_and_label('perform_activity:name','Mid-year appraisal'),
                            placeholder::create_node_from_key_and_label('perform_activity:type','Appraisal'),
                        ]),
                    ])
                ),
                'subject' => 'Your Direct report has submitted their responses to your Test activity Appraisal',
                'subject_format' => FORMAT_PLAIN,
                'additional_criteria' => '{"submitted_by":["direct_report"]}',
            ]
        );

        $notification_generator->create_notification_preference(
            participant_instance_completion_resolver::class,
            extended_context::make_system(),
            [
                'schedule_offset' => 0,
                'recipient' => subject::class,
                'recipients' => [
                    subject::class
                ],
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('Hi John Smith, Your Peer Peer One has submitted their responses to your Activity One Appraisal. You can review the activity through this link: Year end'),
                        paragraph::create_json_node_with_content_nodes([
                            placeholder::create_node_from_key_and_label('recipient:full_name', 'John Smith'),
                            placeholder::create_node_from_key_and_label('subject_user:last_name', 'Thurman'),
                            placeholder::create_node_from_key_and_label('participant_user:full_name','Leo Learner'),
                            placeholder::create_node_from_key_and_label('perform_activity:name','Mid-year appraisal'),
                            placeholder::create_node_from_key_and_label('perform_activity:type','Appraisal'),
                        ]),
                    ])
                ),
                'subject' => 'Your Peer has submitted their responses to your Activity One Appraisal',
                'subject_format' => FORMAT_PLAIN,
                'additional_criteria' => '{"submitted_by":["perform_peer"]}',
            ]
        );

        $notification_generator->create_notification_preference(
            participant_instance_completion_resolver::class,
            extended_context::make_system(),
            [
                'schedule_offset' => 0,
                'recipient' => subject::class,
                'recipients' => [
                    subject::class
                ],
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('Hi John Smith, Your Mentor Mentor One has submitted their responses to your Activity One Appraisal. You can review the activity through this link: Year end'),
                        paragraph::create_json_node_with_content_nodes([
                            placeholder::create_node_from_key_and_label('recipient:full_name', 'John Smith'),
                            placeholder::create_node_from_key_and_label('subject_user:last_name', 'Thurman'),
                            placeholder::create_node_from_key_and_label('participant_user:full_name','Leo Learner'),
                            placeholder::create_node_from_key_and_label('perform_activity:name','Mid-year appraisal'),
                            placeholder::create_node_from_key_and_label('perform_activity:type','Appraisal'),
                        ]),
                    ])
                ),
                'subject' => 'Your Mentor has submitted their responses to your Activity One Appraisal',
                'subject_format' => FORMAT_PLAIN,
                'additional_criteria' => '{"submitted_by":["perform_mentor"]}',
            ]
        );

        $notification_generator->create_notification_preference(
            participant_instance_completion_resolver::class,
            extended_context::make_system(),
            [
                'schedule_offset' => 0,
                'recipient' => subject::class,
                'recipients' => [
                    subject::class
                ],
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('Hi John Smith, Your Reviewer Reviewer One has submitted their responses to your Activity One Appraisal. You can review the activity through this link: Year end'),
                        paragraph::create_json_node_with_content_nodes([
                            placeholder::create_node_from_key_and_label('recipient:full_name', 'John Smith'),
                            placeholder::create_node_from_key_and_label('subject_user:last_name', 'Thurman'),
                            placeholder::create_node_from_key_and_label('participant_user:full_name','Leo Learner'),
                            placeholder::create_node_from_key_and_label('perform_activity:name','Mid-year appraisal'),
                            placeholder::create_node_from_key_and_label('perform_activity:type','Appraisal'),
                        ]),
                    ])
                ),
                'subject' => 'Your Reviewer has submitted their responses to your Activity One Appraisal',
                'subject_format' => FORMAT_PLAIN,
                'additional_criteria' => '{"submitted_by":["perform_reviewer"]}',
            ]
        );

        $notification_generator->create_notification_preference(
            participant_instance_completion_resolver::class,
            extended_context::make_system(),
            [
                'schedule_offset' => 0,
                'recipient' => subject::class,
                'recipients' => [
                    subject::class
                ],
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('Hi John Smith, Your External respondent has submitted their responses to your Example activity Appraisal. You can review the activity through this link: Year end'),
                        paragraph::create_json_node_with_content_nodes([
                            placeholder::create_node_from_key_and_label('recipient:full_name', 'John Smith'),
                            placeholder::create_node_from_key_and_label('subject_user:last_name', 'Thurman'),
                            placeholder::create_node_from_key_and_label('participant_user:full_name','Leo Learner'),
                            placeholder::create_node_from_key_and_label('perform_activity:name','Mid-year appraisal'),
                            placeholder::create_node_from_key_and_label('perform_activity:type','Appraisal'),
                        ]),
                    ])
                ),
                'subject' => 'Your External respondent has submitted their responses to your Example activity Appraisal',
                'subject_format' => FORMAT_PLAIN,
                'additional_criteria' => '{"submitted_by":["perform_external"]}',
            ]
        );
    }

    public function test_available_recipients() {
        self::assertEqualsCanonicalizing(
            [
                appraiser::class,
                direct_report::class,
                manager::class,
                managers_manager::class,
                perform_mentor::class,
                perform_peer::class,
                perform_reviewer::class,
                subject::class,
            ],
            participant_instance_completion_resolver::get_notification_available_recipients()
        );
    }

    public function test_resolver_participant_submission_appraiser(): void {
        $data = $this->set_up_activity_with_job_assignment_based_relationships();
        $messages = $this->complete_participant_instance_as_user($data['appraiser2_id']);

        // The two managers and the subject are expected as recipients.
        self::assert_messages(
            [
                [
                    'recipient_id' => $data['subject1_id'],
                    'subject' => 'Leo Learner has submitted their responses to their Year End Appraisal',
                    'body' => 'You can review the activity through this link',
                ],
                [
                    'recipient_id' => $data['manager1_id'],
                    'subject' => 'Leo Learner has submitted their responses to their Year End Appraisal',
                    'body' => 'You can review the activity through this link',
                ],
                [
                    'recipient_id' => $data['manager2_id'],
                    'subject' => 'Leo Learner has submitted their responses to their Year End Appraisal',
                    'body' => 'You can review the activity through this link',
                ],
                [
                    'recipient_id' => $data['managers_manager1_id'],
                    'subject' => 'Leo Learner has submitted their responses to their Year End Appraisal',
                    'body' => 'You can review the activity through this link',
                ],
                [
                    'recipient_id' => $data['managers_manager2_id'],
                    'subject' => 'Leo Learner has submitted their responses to their Year End Appraisal',
                    'body' => 'You can review the activity through this link',
                ],
            ],
            $messages
        );
    }

    public function test_resolver_participant_submission_managers_manager(): void {
        $data = $this->set_up_activity_with_job_assignment_based_relationships();
        $messages = $this->complete_participant_instance_as_user($data['managers_manager1_id']);

        // The two managers and the subject are expected as recipients.
        self::assert_messages(
            [
                [
                    'recipient_id' => $data['subject1_id'],
                    'subject' => 'Manager has submitted their responses to their Test activity Appraisal',
                    'body' => 'Your Manager\'s manager Boss One has submitted their responses to your Test activity Appraisal',
                ],
                [
                    'recipient_id' => $data['manager1_id'],
                    'subject' => 'Manager has submitted their responses to their Test activity Appraisal',
                    'body' => 'Your Manager\'s manager Boss One has submitted their responses to your Test activity Appraisal',
                ],
                [
                    'recipient_id' => $data['manager2_id'],
                    'subject' => 'Manager has submitted their responses to their Test activity Appraisal',
                    'body' => 'Your Manager\'s manager Boss One has submitted their responses to your Test activity Appraisal',
                ],
            ],
            $messages
        );
    }

    public function test_resolver_participant_submission_direct_report(): void {
        $data = $this->set_up_activity_with_job_assignment_based_relationships();
        $messages = $this->complete_participant_instance_as_user($data['direct_report1_id']);

        // The two managers and the subject are expected as recipients.
        self::assert_messages(
            [
                [
                    'recipient_id' => $data['subject1_id'],
                    'subject' => 'Your Direct report has submitted their responses to your Test activity Appraisal',
                    'body' => 'Your Direct report Directreport One has submitted their responses to your Test activity Appraisal',
                ],
                [
                    'recipient_id' => $data['manager1_id'],
                    'subject' => 'Your Direct report has submitted their responses to your Test activity Appraisal',
                    'body' => 'Your Direct report Directreport One has submitted their responses to your Test activity Appraisal',
                ],
                [
                    'recipient_id' => $data['manager2_id'],
                    'subject' => 'Your Direct report has submitted their responses to your Test activity Appraisal',
                    'body' => 'Your Direct report Directreport One has submitted their responses to your Test activity Appraisal',
                ],
            ],
            $messages
        );
    }

    public function test_resolver_participant_submission_peer(): void {
        $data = $this->set_up_activity_with_selection_relationships();
        $messages = $this->complete_participant_instance_as_user($data['peer1_id'], true);

        // The subject is expected as recipient.
        self::assert_messages(
            [
                [
                    'recipient_id' => $data['subject1_id'],
                    'subject' => 'Your Peer has submitted their responses to your Activity One Appraisal',
                    'body' => 'Your Peer Peer One has submitted their responses to your Activity One Appraisal',
                ],
            ],
            $messages
        );
    }

    public function test_resolver_participant_submission_mentor(): void {
        $data = $this->set_up_activity_with_selection_relationships();
        $messages = $this->complete_participant_instance_as_user($data['mentor1_id'], true);

        // The subject is expected as recipient.
        self::assert_messages(
            [
                [
                    'recipient_id' => $data['subject1_id'],
                    'subject' => 'Your Mentor has submitted their responses to your Activity One Appraisal',
                    'body' => 'Your Mentor Mentor One has submitted their responses to your Activity One Appraisal',
                ],
            ],
            $messages
        );
    }

    public function test_resolver_participant_submission_reviewer(): void {
        $data = $this->set_up_activity_with_selection_relationships();
        $messages = $this->complete_participant_instance_as_user($data['reviewer1_id'], true);

        // The subject is expected as recipient.
        self::assert_messages(
            [
                [
                    'recipient_id' => $data['subject1_id'],
                    'subject' => 'Your Reviewer has submitted their responses to your Activity One Appraisal',
                    'body' => 'Your Reviewer Reviewer One has submitted their responses to your Activity One Appraisal',
                ],
            ],
            $messages
        );
    }

    public function test_resolver_participant_submission_external_participant(): void {
        $data = $this->set_up_activity_with_external_relationship();
        $this->complete_participant_instance_as_user($data['external_user1_id'], true, true);
    }


    /**
     * @param int $user_id
     * @param bool $trigger_event_directly
     * @param bool $external_user
     * @return array
     */
    private function complete_participant_instance_as_user(
        int $user_id,
        bool $trigger_event_directly = false,
        bool $external_user = false
    ): array {
        global $DB;

        self::setUser($external_user ? null : $user_id);

        $participant_source = $external_user ? participant_source::EXTERNAL : participant_source::INTERNAL;

        // Complete the participant instance.
        /** @var participant_instance $participant_instance_entity */
        $participant_instance_entity = participant_instance::repository()
            ->where('participant_id', $user_id)
            ->where('participant_source', $participant_source)
            ->one(true);
        $participant_instance = participant_instance_model::load_by_entity($participant_instance_entity);

        // Depending on the set-up we either trigger directly or do it indirectly by completing the sections.
        if ($trigger_event_directly) {
            $participant_instance_entity->progress = complete::get_code();
            $participant_instance_entity->update();
            $participant_instance_entity->refresh();
            self::assertTrue($participant_instance->is_complete());
            $event = participant_instance_progress_updated::create_from_participant_instance($participant_instance);
            $event->trigger();
        } else {
            $sections = $participant_instance->get_participant_sections();
            foreach ($sections as $section) {
                $completion_success = $section->complete();
                self::assertTrue($completion_success);
            }
        }

        $records = $DB->get_records(notifiable_event_queue::TABLE);
        $participant_selections_queued = 0;
        foreach ($records as $record) {
            if ($record->resolver_class_name === participant_instance_completion_resolver::class) {
                $participant_selections_queued++;
            }
        }

        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $sink = self::redirectMessages();
        $task = new process_event_queue_task();
        $task->execute();

        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        return $sink->get_messages();
    }

    /**
     * @param array $expected_messages
     * @param array $actual_messages
     * @return void
     */
    private static function assert_messages(array $expected_messages, array $actual_messages): void {
        self::assertCount(count($expected_messages), $actual_messages);
        foreach ($expected_messages as $expected_message) {
            foreach ($actual_messages as $actual_message) {
                if ((int)$actual_message->useridto === (int)$expected_message['recipient_id']) {
                    self::assertStringContainsString($expected_message['subject'], $actual_message->subject);
                    self::assertStringContainsString($expected_message['body'], $actual_message->fullmessage);
                    continue 2;
                }
            }
            self::fail("Failed to find expected recipient {$expected_message['recipient_id']} in sent notifications.");
        }
    }

    /**
     * Set up an activity with all job assignment based relationships, create two users per relationship.
     */
    private function set_up_activity_with_job_assignment_based_relationships(): array {
        self::setAdminUser();

        $generator = core_generator::instance();
        $perform_generator = perform_generator::instance();

        // Create an activity with two subjects.
        $config = activity_generator_configuration::new()
            ->set_number_of_activities(1)
            ->set_number_of_users_per_user_group_type(2)
            ->set_relationships_per_section([
                constants::RELATIONSHIP_SUBJECT,
                constants::RELATIONSHIP_MANAGER,
                constants::RELATIONSHIP_APPRAISER,
                constants::RELATIONSHIP_MANAGERS_MANAGER,
                constants::RELATIONSHIP_DIRECT_REPORT,
            ])
            ->enable_manager_for_each_subject_user()
            ->enable_appraiser_for_each_subject_user()
            ->disable_subject_instances();

        /** @var \mod_perform\models\activity\activity $activity */
        $activity = $perform_generator->create_full_activities($config)->first();
        activity::repository()
            ->where('id', $activity->id)
            ->update(['name' => 'Test activity', 'type_id' => 1]);

        // Pick one of the generated subject users and give them a second manager and a second appraiser.
        $subject_jas = job_assignment_entity::repository()->where_not_null('managerjaid')->get();
        self::assertCount(2, $subject_jas);

        /** @var job_assignment_entity $subject1_ja1 */
        $subject1_ja1 = $subject_jas->first();
        $subject1_id = $subject1_ja1->userid;
        $managers = job_assignment::get_all_manager_userids($subject1_id);
        self::assertCount(1, $managers);
        $manager1_id = (int) reset($managers);
        /** @var job_assignment_entity $appraiser1_ja */
        $appraiser1_ja = job_assignment_entity::repository()
            ->where('userid', $subject1_id)
            ->where_not_null('appraiserid')
            ->one(true);
        $appraiser1_id = $appraiser1_ja->appraiserid;
        $subject2_id = $subject_jas->last()->userid;
        $appraiser2_id = $generator->create_user(['firstname' => 'Appraiser', 'lastname' => 'Two'])->id;
        $manager2_id = $generator->create_user()->id;
        /** @var job_assignment $subject1_ja2 */
        $subject1_ja2 = job_assignment::create([
            'userid' => $subject1_id,
            'idnumber' => 'app/no_cohort/' . $subject1_id,
            'appraiserid' => $appraiser2_id,
            'managerjaid' => job_assignment::create_default($manager2_id)->id,
        ]);

        // Add two manager's managers.
        $managers_manager1_id = $generator->create_user(['firstname' => 'Boss', 'lastname' => 'One'])->id;
        $managers_manager2_id = $generator->create_user(['firstname' => 'Boss', 'lastname' => 'Two'])->id;
        job_assignment_entity::repository()
            ->where('id', $subject1_ja1->managerjaid)
            ->update(['managerjaid' => job_assignment::create_default($managers_manager1_id)->id]);
        job_assignment_entity::repository()
            ->where('id', $subject1_ja2->managerjaid)
            ->update(['managerjaid' => job_assignment::create_default($managers_manager2_id)->id]);

        // Add two direct reports.
        $direct_report1_id = $generator->create_user(['firstname' => 'Directreport', 'lastname' => 'One'])->id;
        $direct_report2_id = $generator->create_user(['firstname' => 'Directreport', 'lastname' => 'Two'])->id;
        job_assignment::create([
            'userid' => $direct_report1_id,
            'idnumber' => 'app/no_cohort/' . $direct_report1_id,
            'managerjaid' => $subject1_ja1->id,
        ]);
        job_assignment::create([
            'userid' => $direct_report2_id,
            'idnumber' => 'app/no_cohort/' . $direct_report2_id,
            'managerjaid' => $subject1_ja1->id,
        ]);

        (new subject_instance_creation())->generate_instances();

        // Make sure the set-up is as intended.
        self::assertCount(2, subject_instance::repository()->get());
        self::assertCount(12, participant_instance::repository()->get());

        user_entity::repository()
            ->where('id', $subject1_id)
            ->update(['firstname' => 'Subject', 'lastname' => 'One']);

        return [
            'subject1_id' => $subject1_id,
            'manager1_id' => $manager1_id,
            'manager2_id' => $manager2_id,
            'appraiser1_id' => $appraiser1_id,
            'appraiser2_id' => $appraiser2_id,
            'managers_manager1_id' => $managers_manager1_id,
            'managers_manager2_id' => $managers_manager2_id,
            'direct_report1_id' => $direct_report1_id,
            'direct_report2_id' => $direct_report2_id,
        ];
    }

    /**
     * Set up an activity with all selection based relationships, create two users per relationship.
     */
    private function set_up_activity_with_selection_relationships(): array {
        self::setAdminUser();

        $generator = core_generator::instance();
        $perform_generator = perform_generator::instance();

        $subject_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_SUBJECT);
        $peer_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_PEER);
        $mentor_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_MENTOR);
        $reviewer_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_REVIEWER);

        $activity1 = $perform_generator->create_activity_in_container(['activity_name' => 'Activity One']);
        $perform_generator->create_manual_relationships_for_activity($activity1, [
            ['selector' => $subject_relationship->id, 'manual' => $peer_relationship->id],
            ['selector' => $subject_relationship->id, 'manual' => $reviewer_relationship->id],
            ['selector' => $subject_relationship->id, 'manual' => $mentor_relationship->id],
        ]);

        $subject_user1 = $generator->create_user();
        $subject_user2 = $generator->create_user();
        $mentor1 = $generator->create_user(['firstname' => 'Mentor', 'lastname' => 'One']);
        $mentor2 = $generator->create_user();
        $mentor3 = $generator->create_user();
        $peer1 = $generator->create_user(['firstname' => 'Peer', 'lastname' => 'One']);
        $peer2 = $generator->create_user();
        $peer3 = $generator->create_user();
        $reviewer1 = $generator->create_user(['firstname' => 'Reviewer', 'lastname' => 'One']);
        $reviewer2 = $generator->create_user();
        $reviewer3 = $generator->create_user();

        $subject_instance1 = $perform_generator->create_subject_instance_with_pending_selections(
            $activity1, $subject_user1, [$peer_relationship, $reviewer_relationship, $mentor_relationship]
        );
        $subject_instance1->save();
        $subject_instance1 = subject_instance_model::load_by_entity($subject_instance1);

        $subject_instance2 = $perform_generator->create_subject_instance_with_pending_selections(
            $activity1, $subject_user2, [$peer_relationship, $reviewer_relationship, $mentor_relationship]
        );
        $subject_instance2->save();
        $subject_instance2 = subject_instance_model::load_by_entity($subject_instance2);

        // Set participants as subject 1
        self::setUser($subject_user1);
        $subject_instance1->set_participant_users($subject_user1->id, [
            [
                'manual_relationship_id' => $mentor_relationship->id,
                'users' => [
                    ['user_id' => $mentor1->id],
                    ['user_id' => $mentor2->id],
                ],
            ],
            [
                'manual_relationship_id' => $reviewer_relationship->id,
                'users' => [
                    ['user_id' => $reviewer1->id],
                    ['user_id' => $reviewer2->id],
                ],
            ],
            [
                'manual_relationship_id' => $peer_relationship->id,
                'users' => [
                    ['user_id' => $peer1->id],
                    ['user_id' => $peer2->id],
                ],
            ],
        ]);

        // Also for subject instance 2 as control.
        self::setUser($subject_user2);
        $subject_instance2->set_participant_users($subject_user2->id, [
            [
                'manual_relationship_id' => $mentor_relationship->id,
                'users' => [
                    ['user_id' => $mentor3->id],
                ],
            ],
            [
                'manual_relationship_id' => $reviewer_relationship->id,
                'users' => [
                    ['user_id' => $reviewer3->id],
                ],
            ],
            [
                'manual_relationship_id' => $peer_relationship->id,
                'users' => [
                    ['user_id' => $peer3->id],
                ],
            ],
        ]);

        return [
            'subject1_id' => $subject_user1->id,
            'subject_instance1_id' => $subject_instance1->id,
            'subject_instance2_id' => $subject_instance2->id,
            'peer1_id' => $peer1->id,
            'peer2_id' => $peer2->id,
            'peer3_id' => $peer3->id,
            'mentor1_id' => $mentor1->id,
            'mentor2_id' => $mentor2->id,
            'mentor3_id' => $mentor3->id,
            'reviewer1_id' => $reviewer1->id,
            'reviewer2_id' => $reviewer2->id,
            'reviewer3_id' => $reviewer3->id,
        ];
    }

    private function set_up_activity_with_external_relationship(): array {
        $this->setAdminUser();
        $generator = perform_generator::instance();

        // Create 2 activities, each with 2 subjects, 2 managers and 4 external users (2 per subject instance).
        $configuration = activity_generator_configuration::new()
            ->set_activity_status(active::get_code())
            ->enable_manager_for_each_subject_user()
            ->set_number_of_users_per_user_group_type(2)
            ->set_relationships_per_section(
                [
                    constants::RELATIONSHIP_EXTERNAL,
                    constants::RELATIONSHIP_SUBJECT,
                    constants::RELATIONSHIP_MANAGER
                ]
            );
        $activity1 = $generator->create_full_activities($configuration)->first();
        $activity2 = $generator->create_full_activities($configuration)->first();

        // Make sure the progress records are there and add the external users.
        (new manual_participant_progress())->generate();
        $generator->create_manual_users_for_activity($activity1, [constants::RELATIONSHIP_EXTERNAL], 2);
        $generator->create_manual_users_for_activity($activity2, [constants::RELATIONSHIP_EXTERNAL], 2);

        // Verify generated data is as expected.
        self::assertEquals(16, participant_instance::repository()->count());
        self::assertEquals(4, subject_instance::repository()->count());
        $subject_instances_activity1 = subject_instance::repository()
            ->filter_by_activity_id($activity1->id)
            ->order_by('id');
        self::assertEquals(2, $subject_instances_activity1->count());

        /** @var subject_instance $subject_instance1 */
        $subject_instance1 = $subject_instances_activity1->first();

        // Get the external participant instances for the subject instance we picked.
        $external_participant_instances = participant_instance::repository()
            ->where('subject_instance_id', $subject_instance1->id)
            ->where('participant_source', participant_source::EXTERNAL)
            ->order_by('id')
            ->get();

        /** @var participant_instance $manager_participant_instance */
        $manager_participant_instance = participant_instance::repository()
            ->where('subject_instance_id', $subject_instance1->id)
            ->where('core_relationship_id', relationship::load_by_idnumber(constants::RELATIONSHIP_MANAGER)->id)
            ->one(true);

        self::assertEquals(2, $external_participant_instances->count());

        /** @var participant_instance $external_participant_instance1 */
        $external_participant_instance1 = $external_participant_instances->first();
        /** @var participant_instance $external_participant_instance2 */
        $external_participant_instance2 = $external_participant_instances->last();

        /** @var external_participant $external_participant1 */
        $external_participant1 = external_participant::repository()->find($external_participant_instance1->participant_id);
        /** @var external_participant $external_participant2 */
        $external_participant2 = external_participant::repository()->find($external_participant_instance2->participant_id);


        $subject_id = $subject_instance1->subject_user_id;
        user_entity::repository()
            ->where('id', $subject_id)
            ->update(['firstname' => 'Subject', 'lastname' => 'One']);

        activity::repository()
            ->where('id', $activity1->id)
            ->update(['name' => 'Example activity', 'type_id' => 1]);

        return [
            'subject_instance_id' => $subject_instance1->id,
            'subject_id' => $subject_id,
            'manager_id' => $manager_participant_instance->participant_id,
            'external_user1_id' => $external_participant1->id,
            'external_user1_email' => $external_participant1->email,
            'external_user2_email' => $external_participant2->email,
            'external_user1_name' => $external_participant1->name,
        ];
    }
}
