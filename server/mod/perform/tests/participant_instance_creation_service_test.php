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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package mod_perform
 */

use core\collection;
use core\json_editor\helper\document_helper;
use core\json_editor\node\paragraph;
use core\task\manager;
use mod_perform\constants;
use mod_perform\entity\activity\activity as activity_entity;
use mod_perform\entity\activity\external_participant;
use mod_perform\entity\activity\participant_instance;
use mod_perform\entity\activity\participant_section;
use mod_perform\entity\activity\section;
use mod_perform\entity\activity\section_relationship;
use mod_perform\entity\activity\subject_instance;
use mod_perform\entity\activity\track as track_entity;
use mod_perform\entity\activity\track_user_assignment;
use mod_perform\event\participant_instance_manually_added;
use mod_perform\expand_task;
use mod_perform\models\activity\participant_instance as participant_instance_model;
use mod_perform\models\activity\participant_source;
use mod_perform\models\activity\subject_instance_manual_participant;
use mod_perform\state\activity\draft;
use mod_perform\state\participant_instance\closed as participant_instance_closed;
use mod_perform\state\participant_instance\complete as participant_instance_complete;
use mod_perform\state\subject_instance\closed;
use mod_perform\state\subject_instance\complete;
use mod_perform\state\subject_instance\in_progress;
use mod_perform\state\subject_instance\open;
use mod_perform\task\send_participant_instance_creation_notifications_task;
use mod_perform\task\service\participant_instance_creation;
use mod_perform\task\service\subject_instance_creation;
use mod_perform\task\service\subject_instance_dto;
use mod_perform\totara_notification\recipient\subject;
use mod_perform\totara_notification\resolver\participant_instance_created_resolver;
use totara_core\entity\relationship as relationship_entity;
use totara_core\extended_context;
use totara_core\relationship\relationship;
use totara_core\relationship\relationship_provider;
use totara_job\job_assignment;
use totara_notification\json_editor\node\placeholder;
use totara_notification\task\process_event_queue_task;
use totara_notification\testing\generator as notification_generator;

/**
 * Class participant_instance_creation_service_test
 *
 * @group perform
 */
class mod_perform_participant_instance_creation_service_testcase extends advanced_testcase {

    private const JOB_ASSIGNMENTS_PER_USER = 2;
    private const USERS_PER_RELATIONSHIP = 2;
    private const SUBJECTS_PER_ACTIVITY = 3;
    private const NO_OF_ACTIVITIES = 2;

    private const CALCULATED_RELATIONSHIPS = [
        constants::RELATIONSHIP_APPRAISER,
        constants::RELATIONSHIP_MANAGER
    ];

    private const MANUAL_RELATIONSHIPS = [
        constants::RELATIONSHIP_MENTOR,
        constants::RELATIONSHIP_EXTERNAL
    ];

    private const EXTERNAL_USER_NAME = "John Doe";
    private const EXTERNAL_USER_EMAIL = "jd@example.com";

    /**
     * External user tokens.
     *
     * @var array
     */
    private $tokens;

    /**
     * Manually selected internal user.
     *
     * @var int
     */
    private $internal_manual_user;

    /**
     * Data of Activities used.
     *
     * @var array
     */
    private $activity_trees;

    /**
     * Test participant_instances watcher processes on subject_instance_creation hook call.
     *
     * @dataProvider creation_mode_provider
     * @param bool $expand_per_job_assignment
     * @return void
     */
    public function test_subject_instance_creation_hook(bool $expand_per_job_assignment): void {
        $this->setup_assignments($expand_per_job_assignment);

        $track_user_assignments = $this->get_track_user_assignments();

        $subject_instance_service = new subject_instance_creation();
        $subject_instance_service->generate_instances();

        $this->assert_participant_instances_created($track_user_assignments, $expand_per_job_assignment);
    }

    /**
     * Tests participants are created successfully.
     *
     * @dataProvider creation_mode_provider
     * @param bool $expand_per_job_assignment
     * @return void
     */
    public function test_create_participant_instances(bool $expand_per_job_assignment): void {
        $with_manual_relationships = true;
        $this->setup_assignments($expand_per_job_assignment, $with_manual_relationships);

        $track_user_assignments = $this->get_track_user_assignments();
        $subject_instance_dto_collection = $this->create_subject_instances($track_user_assignments);
        $this->setup_manual_participants($subject_instance_dto_collection);

        $participant_instance_service = new participant_instance_creation();
        $participant_instance_service->generate_instances($subject_instance_dto_collection);

        $this->assert_participant_instances_created(
            $track_user_assignments, $expand_per_job_assignment, $with_manual_relationships
        );
    }

    public function creation_mode_provider(): array {
        return [
            'Expand one per user mode' => [true],
            'Expand one per job mode' => [false],
        ];
    }

    public function test_add_participants() {
        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();
        $data = $this->generate_test_data_for_adding_participants([
            constants::RELATIONSHIP_SUBJECT,
            constants::RELATIONSHIP_APPRAISER,
            constants::RELATIONSHIP_MANAGER
        ]);
        $this->assertEquals(4, participant_instance::repository()->count());
        $this->assertEquals(4, participant_section::repository()->count());

        $subject_relationship = $generator->get_core_relationship(constants::RELATIONSHIP_SUBJECT);
        $appraiser_relationship = $generator->get_core_relationship(constants::RELATIONSHIP_APPRAISER);
        /** @var participant_instance $participant_instance_for_subject_user1 */
        $participant_instance_for_subject_user1 = participant_instance::repository()
            ->where('subject_instance_id', $data->subject_instance1_id)
            ->one(true);
        $this->assertEquals($subject_relationship->id, $participant_instance_for_subject_user1->core_relationship_id);
        $this->assertEquals($data->subject_user1_id, $participant_instance_for_subject_user1->participant_id);

        $new_participant_user = $this->getDataGenerator()->create_user();
        $returned_instances = (new participant_instance_creation())->add_instances(
            [$data->subject_instance1_id],
            [
                [
                    'participant_id' => $new_participant_user->id,
                    'core_relationship_id' => $appraiser_relationship->id,
                ]
            ]
        );

        $this->assertEquals(5, participant_instance::repository()->count());
        $this->assertEquals(5, participant_section::repository()->count());
        /** @var participant_instance $new_instance */
        $new_instance = participant_instance::repository()
            ->where('participant_id', $new_participant_user->id)
            ->one(true);
        $this->assertEquals($appraiser_relationship->id, $new_instance->core_relationship_id);
        $this->assertEquals($data->subject_instance1_id, $new_instance->subject_instance_id);

        // Check return value.
        $this->assertCount(1, $returned_instances);
        $returned_instance = $returned_instances->first();
        $this->assertInstanceOf(participant_instance_model::class, $returned_instance);
        $this->assertEquals($new_instance->id, $returned_instance->id);
    }

    public function test_add_participants_multiple_subject_instances() {
        $this->setAdminUser();

        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();

        // Create an activity with 2 tracks.
        $config = \mod_perform\testing\activity_generator_configuration::new()
            ->set_number_of_tracks_per_activity(2)
            ->set_number_of_users_per_user_group_type(2)
            ->set_relationships_per_section([constants::RELATIONSHIP_SUBJECT, constants::RELATIONSHIP_APPRAISER]);
        $generator->create_full_activities($config)->first();

        $this->assertEquals(4, participant_instance::repository()->count());
        $this->assertEquals(4, participant_section::repository()->count());
        $subject_instance_ids = subject_instance::repository()->get()->pluck('id');
        $this->assertCount(4, $subject_instance_ids);

        $appraiser_relationship = $generator->get_core_relationship(constants::RELATIONSHIP_APPRAISER);
        $new_participant_user = $this->getDataGenerator()->create_user();
        $returned_instances = (new participant_instance_creation())->add_instances(
            $subject_instance_ids,
            [
                [
                    'participant_id' => $new_participant_user->id,
                    'core_relationship_id' => $appraiser_relationship->id,
                ]
            ]
        );

        $this->assertEquals(8, participant_instance::repository()->count());
        $this->assertEquals(8, participant_section::repository()->count());
        $new_instance_ids = [];
        foreach ($subject_instance_ids as $subject_instance_id) {
            /** @var participant_instance $new_instance */
            $new_instance = participant_instance::repository()
                ->where('participant_id', $new_participant_user->id)
                ->where('subject_instance_id', $subject_instance_id)
                ->one(true);
            $this->assertEquals($appraiser_relationship->id, $new_instance->core_relationship_id);
            $new_instance_ids[] = $new_instance->id;
        }

        // Check return value.
        $this->assertEqualsCanonicalizing($new_instance_ids, $returned_instances->pluck('id'));
    }

    public function test_add_participants_multiple_relationships() {
        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();
        $data = $this->generate_test_data_for_adding_participants([
            constants::RELATIONSHIP_SUBJECT,
            constants::RELATIONSHIP_APPRAISER,
            constants::RELATIONSHIP_MANAGER
        ]);

        $appraiser_relationship = $generator->get_core_relationship(constants::RELATIONSHIP_APPRAISER);
        $manager_relationship = $generator->get_core_relationship(constants::RELATIONSHIP_MANAGER);
        $new_appraiser = $this->getDataGenerator()->create_user();
        $new_manager = $this->getDataGenerator()->create_user();

        $returned_instances = (new participant_instance_creation())->add_instances(
            [$data->subject_instance1_id],
            [
                [
                    'participant_id' => $new_appraiser->id,
                    'core_relationship_id' => $appraiser_relationship->id,
                ],
                [
                    'participant_id' => $new_manager->id,
                    'core_relationship_id' => $manager_relationship->id,
                ],
            ]
        );

        $this->assertEquals(6, participant_instance::repository()->count());
        $this->assertEquals(6, participant_section::repository()->count());

        /** @var participant_instance $new_appraiser_instance */
        $new_appraiser_instance = participant_instance::repository()
            ->where('participant_id', $new_appraiser->id)
            ->one(true);
        $this->assertEquals($appraiser_relationship->id, $new_appraiser_instance->core_relationship_id);
        $this->assertEquals($data->subject_instance1_id, $new_appraiser_instance->subject_instance_id);

        /** @var participant_instance $new_manager_instance */
        $new_manager_instance = participant_instance::repository()
            ->where('participant_id', $new_manager->id)
            ->one(true);
        $this->assertEquals($manager_relationship->id, $new_manager_instance->core_relationship_id);
        $this->assertEquals($data->subject_instance1_id, $new_manager_instance->subject_instance_id);

        // Check return value.
        $this->assertEqualsCanonicalizing(
            [$new_appraiser_instance->id, $new_manager_instance->id],
            $returned_instances->pluck('id')
        );
    }

    public function test_add_participants_varying_section_relationships() {
        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();
        $data = $this->generate_test_data_for_adding_participants([
            constants::RELATIONSHIP_SUBJECT,
            constants::RELATIONSHIP_APPRAISER,
            constants::RELATIONSHIP_MANAGER
        ], 2);
        $this->assertEquals(4, participant_instance::repository()->count());
        $this->assertEquals(8, participant_section::repository()->count());

        $appraiser_relationship = $generator->get_core_relationship(constants::RELATIONSHIP_APPRAISER);
        $manager_relationship = $generator->get_core_relationship(constants::RELATIONSHIP_MANAGER);

        // The generator builds all the sections with the same list of relationships, so let's remove
        // the "manager" section relationship for one of the sections.
        $sections = section::repository()
            ->where('activity_id', $data->activity1->id)
            ->get()
            ->all();
        $this->assertCount(2, $sections);
        [$section1, $section2] = $sections;
        section_relationship::repository()
            ->where('section_id', $section1->id)
            ->where('core_relationship_id', $manager_relationship->id)
            ->delete();

        $new_appraiser = $this->getDataGenerator()->create_user();
        $new_manager = $this->getDataGenerator()->create_user();

        (new participant_instance_creation())->add_instances(
            [$data->subject_instance1_id],
            [
                [
                    'participant_id' => $new_appraiser->id,
                    'core_relationship_id' => $appraiser_relationship->id,
                ],
                [
                    'participant_id' => $new_manager->id,
                    'core_relationship_id' => $manager_relationship->id,
                ],
            ]
        );

        $this->assertEquals(6, participant_instance::repository()->count());
        $new_appraiser_participant_instance = participant_instance::repository()
            ->where('participant_id', $new_appraiser->id)
            ->where('core_relationship_id', $appraiser_relationship->id)
            ->where('subject_instance_id', $data->subject_instance1_id)
            ->one(true);
        $new_manager_participant_instance = participant_instance::repository()
            ->where('participant_id', $new_manager->id)
            ->where('core_relationship_id', $manager_relationship->id)
            ->where('subject_instance_id', $data->subject_instance1_id)
            ->one(true);

        // There should be 3 new participant sections. Make sure they are as expected.
        $this->assertEquals(11, participant_section::repository()->count());
        foreach ([$section1, $section2] as $section) {
            $this->assertEquals(1,
                participant_section::repository()
                    ->where('participant_instance_id', $new_appraiser_participant_instance->id)
                    ->where('section_id', $section->id)
                    ->count()
            );
        }
        $this->assertEquals(1,
            participant_section::repository()
                ->where('participant_instance_id', $new_manager_participant_instance->id)
                ->where('section_id', $section2->id)
                ->count()
        );
    }

    public function test_add_participants_invalid_participant_id() {
        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();
        $data = $this->generate_test_data_for_adding_participants([
            constants::RELATIONSHIP_SUBJECT,
            constants::RELATIONSHIP_APPRAISER,
            constants::RELATIONSHIP_MANAGER
        ]);

        $appraiser_relationship = $generator->get_core_relationship(constants::RELATIONSHIP_APPRAISER);
        $new_appraiser = $this->getDataGenerator()->create_user();

        $bad_user_id = -1;
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Users with these ids do not exist: ' . $bad_user_id);

        (new participant_instance_creation())->add_instances(
            [$data->subject_instance1_id],
            [
                [
                    'participant_id' => $new_appraiser->id,
                    'core_relationship_id' => $appraiser_relationship->id,
                ],
                [
                    'participant_id' => $bad_user_id,
                    'core_relationship_id' => $appraiser_relationship->id,
                ],
            ]
        );
    }

    public function test_add_participants_activity_must_be_active() {
        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();
        $data = $this->generate_test_data_for_adding_participants([
            constants::RELATIONSHIP_SUBJECT,
            constants::RELATIONSHIP_APPRAISER
        ]);

        $appraiser_relationship = $generator->get_core_relationship(constants::RELATIONSHIP_APPRAISER);
        $new_appraiser = $this->getDataGenerator()->create_user();

        activity_entity::repository()
            ->where('id', $data->activity1->id)
            ->update(['status' => draft::get_code()]);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Cannot add participant instances for inactive activity.');

        (new participant_instance_creation())->add_instances(
            [$data->subject_instance1_id],
            [
                [
                    'participant_id' => $new_appraiser->id,
                    'core_relationship_id' => $appraiser_relationship->id,
                ],
            ]
        );
    }

    public function test_add_participants_invalid_relationship() {
        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();
        $data = $this->generate_test_data_for_adding_participants([
            constants::RELATIONSHIP_SUBJECT,
            constants::RELATIONSHIP_MANAGER
        ]);

        $appraiser_relationship = $generator->get_core_relationship(constants::RELATIONSHIP_APPRAISER);
        $new_appraiser = $this->getDataGenerator()->create_user();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            "Relationships with these ids cannot be used in activity {$data->activity1->id}: {$appraiser_relationship->id}"
        );

        (new participant_instance_creation())->add_instances(
            [$data->subject_instance1_id],
            [
                [
                    'participant_id' => $new_appraiser->id,
                    'core_relationship_id' => $appraiser_relationship->id,
                ],
            ]
        );
    }

    public function test_add_participants_cannot_add_subject_relationship() {
        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();
        $data = $this->generate_test_data_for_adding_participants([constants::RELATIONSHIP_SUBJECT, constants::RELATIONSHIP_MANAGER]);

        $subject_relationship = $generator->get_core_relationship(constants::RELATIONSHIP_SUBJECT);
        $new_subject = $this->getDataGenerator()->create_user();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            "Relationships with these ids cannot be used in activity {$data->activity1->id}: {$subject_relationship->id}"
        );

        (new participant_instance_creation())->add_instances(
            [$data->subject_instance1_id],
            [
                [
                    'participant_id' => $new_subject->id,
                    'core_relationship_id' => $subject_relationship->id,
                ],
            ]
        );
    }

    public function test_add_participants_with_invalid_and_valid_relationships() {
        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();
        $data = $this->generate_test_data_for_adding_participants([constants::RELATIONSHIP_SUBJECT, constants::RELATIONSHIP_MANAGER]);

        $appraiser_relationship = $generator->get_core_relationship(constants::RELATIONSHIP_APPRAISER);
        $manager_relationship = $generator->get_core_relationship(constants::RELATIONSHIP_MANAGER);
        $subject_relationship = $generator->get_core_relationship(constants::RELATIONSHIP_SUBJECT);
        $new_appraiser = $this->getDataGenerator()->create_user();
        $new_manager = $this->getDataGenerator()->create_user();
        $new_subject = $this->getDataGenerator()->create_user();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            "Relationships with these ids cannot be used in activity {$data->activity1->id}: "
            . "{$subject_relationship->id},{$appraiser_relationship->id}"
        );

        (new participant_instance_creation())->add_instances(
            [$data->subject_instance1_id],
            [
                [
                    'participant_id' => $new_appraiser->id,
                    'core_relationship_id' => $appraiser_relationship->id,
                ],
                [
                    'participant_id' => $new_manager->id,
                    'core_relationship_id' => $manager_relationship->id,
                ],
                [
                    'participant_id' => $new_subject->id,
                    'core_relationship_id' => $subject_relationship->id,
                ],
            ]
        );
    }

    public function test_add_participants_invalid_subject_instance() {
        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();
        $data = $this->generate_test_data_for_adding_participants([constants::RELATIONSHIP_SUBJECT, constants::RELATIONSHIP_APPRAISER]);

        $appraiser_relationship = $generator->get_core_relationship(constants::RELATIONSHIP_APPRAISER);
        $new_appraiser = $this->getDataGenerator()->create_user();

        $activity2_subject_instance_ids = subject_instance::repository()
            ->filter_by_activity_id($data->activity2->id)
            ->get()
            ->pluck('id');

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            "Subject instances with these ids do not belong to activity"
        );

        // Mix subject instances of activity1 and activity2.
        (new participant_instance_creation())->add_instances(
            array_merge(
                [$data->subject_instance1_id, $data->subject_instance2_id],
                $activity2_subject_instance_ids
            ),
            [
                [
                    'participant_id' => $new_appraiser->id,
                    'core_relationship_id' => $appraiser_relationship->id,
                ],
            ]
        );
    }

    public function bad_subject_instances_data_provider() {
        return [
            'empty array not allowed' => [[]],
            'non-existing ids not allowed' => [[-1, -2]]
        ];
    }

    /**
     * @dataProvider bad_subject_instances_data_provider
     * @param array $subject_instance_ids
     */
    public function test_add_participants_must_pass_existing_subject_instance_ids(array $subject_instance_ids) {
        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();
        $this->generate_test_data_for_adding_participants([constants::RELATIONSHIP_SUBJECT, constants::RELATIONSHIP_MANAGER]);

        $subject_relationship = $generator->get_core_relationship(constants::RELATIONSHIP_SUBJECT);
        $new_subject = $this->getDataGenerator()->create_user();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            "Invalid subject_instance_ids detected"
        );

        (new participant_instance_creation())->add_instances(
            $subject_instance_ids,
            [
                [
                    'participant_id' => $new_subject->id,
                    'core_relationship_id' => $subject_relationship->id,
                ],
            ]
        );
    }

    public function test_add_participants_silently_ignored_if_already_existing() {
        /** @var \mod_perform\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_perform');
        $data = $this->generate_test_data_for_adding_participants([constants::RELATIONSHIP_SUBJECT, constants::RELATIONSHIP_APPRAISER]);
        $this->assertEquals(4, participant_instance::repository()->count());

        $appraiser_relationship = $generator->get_core_relationship(constants::RELATIONSHIP_APPRAISER);
        $new_appraiser = $this->getDataGenerator()->create_user();

        (new participant_instance_creation())->add_instances(
            [$data->subject_instance1_id],
            [
                [
                    'participant_id' => $new_appraiser->id,
                    'core_relationship_id' => $appraiser_relationship->id,
                ],
            ]
        );
        $this->assertEquals(5, participant_instance::repository()->count());

        // Try to add the same instance again. Should be silently ignored.
        (new participant_instance_creation())->add_instances(
            [$data->subject_instance1_id],
            [
                [
                    'participant_id' => $new_appraiser->id,
                    'core_relationship_id' => $appraiser_relationship->id,
                ],
            ]
        );
        $this->assertEquals(5, participant_instance::repository()->count());
    }

    public function reopen_and_uncomplete_data_provider() {
        return [
            ['open before adding' => open::get_code()],
            ['closed before adding' => closed::get_code()],
        ];
    }

    /**
     * For both closed and open subject instance we expect the same resulting states.
     *
     * @dataProvider reopen_and_uncomplete_data_provider
     */
    public function test_add_participants_reopens_and_uncompletes_subject_instance(int $state_before_adding) {
        /** @var \mod_perform\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_perform');
        $data = $this->generate_test_data_for_adding_participants([constants::RELATIONSHIP_SUBJECT, constants::RELATIONSHIP_APPRAISER]);

        $appraiser_relationship = $generator->get_core_relationship(constants::RELATIONSHIP_APPRAISER);
        $new_appraiser = $this->getDataGenerator()->create_user();

        /** @var subject_instance $subject_instance */
        $subject_instance = subject_instance::repository()->find($data->subject_instance1_id);
        $subject_instance->availability = $state_before_adding;
        $subject_instance->progress = complete::get_code();
        $subject_instance->save();

        // Also close existing participant_instance to make sure it stays closed.
        /** @var participant_instance $participant_instance */
        $participant_instance = participant_instance::repository()
            ->where('subject_instance_id', $subject_instance->id)
            ->one(true);
        $participant_instance->availability = participant_instance_closed::get_code();
        $participant_instance->progress = participant_instance_complete::get_code();
        $participant_instance->save();

        (new participant_instance_creation())->add_instances(
            [$data->subject_instance1_id],
            [
                [
                    'participant_id' => $new_appraiser->id,
                    'core_relationship_id' => $appraiser_relationship->id,
                ],
            ]
        );

        // Subject instance should be re-opened/un-completed.
        $subject_instance->refresh();
        $this->assertEquals(open::get_code(), $subject_instance->availability);
        $this->assertEquals(in_progress::get_code(), $subject_instance->progress);
        // Participant instance should not be re-opened or un-completed.
        $participant_instance->refresh();
        $this->assertEquals(participant_instance_closed::get_code(), $participant_instance->availability);
        $this->assertEquals(participant_instance_complete::get_code(), $participant_instance->progress);
    }

    public function test_manually_add_participants_event_is_triggered() {
        global $DB;

        $notification_generator = notification_generator::instance();
        $notification_generator->create_notification_preference(
            participant_instance_created_resolver::class,
            extended_context::make_system(),
            [
                'schedule_offset' => 0,
                'recipient' => subject::class,
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

        $relationships = collection::new([
            constants::RELATIONSHIP_APPRAISER,
            constants::RELATIONSHIP_MANAGER
        ]);
        $data = $this->generate_test_data_for_adding_participants($relationships->all());
        $subject_instance_id = $data->subject_instance1_id;

        $generator = $this->getDataGenerator();
        $perform_generator = $generator->get_plugin_generator('mod_perform');
        $relationship_participants = $relationships->reduce(
            function (array $accumulated, string $relationship) use ($generator, $perform_generator): array {
                $accumulated[] = [
                    'participant_id' => $generator->create_user()->id,
                    'core_relationship_id' => $perform_generator->get_core_relationship($relationship)->id
                ];

                return $accumulated;
            },
            []
        );

        $sink = $this->redirectEvents();

        $activity_admin = $generator->create_user();
        $this->setUser($activity_admin);

        $manual_participant_instances = (new participant_instance_creation())
            ->add_instances([$subject_instance_id], $relationship_participants)
            ->key_by('id');

        // run notification adhoc tasks.
        $notification_adhoc_tasks = manager::get_adhoc_tasks(send_participant_instance_creation_notifications_task::class);
        foreach ($notification_adhoc_tasks as $notification_task) {
            $notification_task->execute();
        }

        $this->assertCount(2, $DB->get_records('notifiable_event_queue'));

        // Process event queue.
        $task = new process_event_queue_task();
        $task->execute();

        $events = $sink->get_events();
        $events_perform = array_filter($events, function ($event) {
            return $event instanceof participant_instance_manually_added;
        });

        $this->assertCount(count($relationships), $sink->get_events());
        foreach ($events_perform as $event) {
            $expected = $manual_participant_instances->item($event->objectid);
            $this->assertNotNull($expected, "Unknown participant id: '$event->objectid'");
            $this->assertEquals($expected->participant_id, $event->relateduserid, 'wrong participant id');
            $this->assertEquals($activity_admin->id, $event->userid, 'wrong user id');
            $this->assertEquals($subject_instance_id, $event->other['subject_instance_id'], 'wrong subject instance id');
        }
        $sink->close();
    }

    /**
     * Create test data for manually adding participant instances.
     *
     * @param array $relationships
     * @param int $num_sections
     * @return stdClass
     */
    private function generate_test_data_for_adding_participants(array $relationships, int $num_sections = 1): stdClass {
        $this->setAdminUser();

        /** @var \mod_perform\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_perform');

        // Create 2 activities with 2 users each.
        $config = \mod_perform\testing\activity_generator_configuration::new()
            ->set_number_of_activities(2)
            ->set_number_of_users_per_user_group_type(2)
            ->set_number_of_sections_per_activity($num_sections)
            ->set_relationships_per_section($relationships);
        [$activity1, $activity2] = $generator->create_full_activities($config)->all();

        $activity1_subject_instances = subject_instance::repository()
            ->filter_by_activity_id($activity1->id)
            ->get()
            ->all();
        $subject_instance1_id = $activity1_subject_instances[0]->id;
        $subject_user1_id = $activity1_subject_instances[0]->subject_user_id;
        $subject_instance2_id = $activity1_subject_instances[1]->id;
        $subject_user2_id = $activity1_subject_instances[1]->subject_user_id;

        return (object)[
            'activity1' => $activity1,
            'activity2' => $activity2,
            'subject_user1_id' => $subject_user1_id,
            'subject_user2_id' => $subject_user2_id,
            'subject_instance1_id' => $subject_instance1_id,
            'subject_instance2_id' => $subject_instance2_id,
        ];
    }


    /**
     * Asserts participant instances created.
     *
     * @param collection $track_user_assignments
     * @param bool $expand_per_job_assignment
     * @param bool $with_manual_relationships
     * @return void
     */
    private function assert_participant_instances_created(
        collection $track_user_assignments,
        $expand_per_job_assignment = false,
        $with_manual_relationships = false
    ): void {
        $created_participants = participant_instance::repository()->get();

        $subject_count = $track_user_assignments->count();
        $expected_calculated_participants_created = $subject_count
            * count(self::CALCULATED_RELATIONSHIPS)
            * self::USERS_PER_RELATIONSHIP;

        if ($expand_per_job_assignment) {
            $expected_calculated_participants_created /= self::JOB_ASSIGNMENTS_PER_USER;
        }

        $expected_manual_participants = $with_manual_relationships
            ? $subject_count * count(self::MANUAL_RELATIONSHIPS)
            : 0;

        $expected_participants_created = $expected_calculated_participants_created + $expected_manual_participants;

        $this->assertEquals($expected_participants_created, $created_participants->count());

        $this->assert_each_participant_created_with_correct_data($created_participants, $expand_per_job_assignment);
    }

    /**
     * Asserts each participant instance is created with the right data.
     *
     * @param collection $created_participants
     * @param bool $expand_per_job_assignment
     * @return void
     */
    private function assert_each_participant_created_with_correct_data(
        collection $created_participants,
        $expand_per_job_assignment = false
    ): void {
        $relationship_ids = [];
        $subject_instances = subject_instance::repository()->get();
        $subject_instance_ids = $subject_instances->pluck('id');
        $managers_and_appraisers_list = $this->group_participant_job_assignments();
        $subject_instances_counter = [];

        $relationship_provider = new relationship_provider();
        $all_relationships = $relationship_provider
            ->get()
            ->key_by('id');

        foreach ($created_participants as $created_participant) {
            $participant_id = $created_participant->participant_id;
            $participant_source = (int)$created_participant->participant_source;

            $relationship_id = $created_participant->core_relationship_id;
            $relationship = $all_relationships->item($relationship_id);

            if ((int)$relationship->type === relationship_entity::TYPE_MANUAL) {
                if ($participant_source === participant_source::EXTERNAL) {
                    $this->assert_external_participant_created_with_correct_data(
                        $participant_id,
                        $relationship->idnumber
                    );
                } else {
                    $this->assert_manual_participant_created_with_correct_data(
                        $participant_id,
                        $relationship->idnumber
                    );
                }

                continue;
            }

            // All this is for calculated relationships.
            $activity_id = $subject_instances->find('id', $created_participant->subject_instance_id)->activity()->id;
            $this->assertArrayHasKey(
                $participant_id,
                $managers_and_appraisers_list[$activity_id],
                'Unknown participant stored.'
            );
            $managers_and_appraisers_list[$activity_id][$participant_id]['count']++;

            $this->assertContainsEquals(
                $created_participant->subject_instance_id,
                $subject_instance_ids,
                'unknown subject instance id stored'
            );
            if (!isset($subject_instances_counter[$created_participant->subject_instance_id])) {
                $subject_instances_counter[$created_participant->subject_instance_id] = 0;
            }
            $subject_instances_counter[$created_participant->subject_instance_id]++;

            $participant_relationship =
                $managers_and_appraisers_list[$activity_id][$participant_id]['relationship'];
            if (!isset($relationship_ids[$activity_id])) {
                $relationship_ids[$activity_id] = [
                    'manager' => null,
                    'appraiser' => null,
                ];
            }
            if (is_null($relationship_ids[$activity_id][$participant_relationship])) {
                $relationship_ids[$activity_id][$participant_relationship] = $relationship_id;
            }
            $this->assertEquals(
                $relationship_ids[$activity_id][$participant_relationship],
                $relationship_id
            );
        }

        $expected_calculated_participant_count = count($subject_instance_ids) / self::USERS_PER_RELATIONSHIP;

        if ($expand_per_job_assignment) {
            $expected_calculated_participant_count /= self::JOB_ASSIGNMENTS_PER_USER;
        }

        foreach ($managers_and_appraisers_list as $activity_participants) {
            foreach ($activity_participants as $actual_participant) {
                $this->assertEquals($expected_calculated_participant_count, $actual_participant['count']);
            }
        }

        $expected_subject_instance_count = count(self::CALCULATED_RELATIONSHIPS) * self::USERS_PER_RELATIONSHIP;

        if ($expand_per_job_assignment) {
            $expected_subject_instance_count /= self::JOB_ASSIGNMENTS_PER_USER;
        }

        foreach ($subject_instances_counter as $subject_instance_count) {
            $this->assertEquals($expected_subject_instance_count, $subject_instance_count);
        }
    }

    /**
     * Asserts an external participant instance is created with the right data.
     *
     * @param int $participant_id
     * @param string $relationship
     * @return void
     */
    private function assert_external_participant_created_with_correct_data(
        int $participant_id,
        string $relationship
    ): void {
        $this->assertEquals(constants::RELATIONSHIP_EXTERNAL, $relationship, 'wrong relationship');

        $external_participants = external_participant::repository()
            ->where('id', $participant_id)
            ->get();
        $this->assertCount(1, $external_participants, ">1 external participant with id $participant_id");

        $participant = $external_participants->first();
        $this->assertEquals(self::EXTERNAL_USER_NAME, $participant->name, 'wrong name');
        $this->assertEquals(self::EXTERNAL_USER_EMAIL, $participant->email, 'wrong email');

        $token = $participant->token;
        $this->assertNotContainsEquals($token, $this->tokens, 'duplicate external user token');
        $this->tokens[] = $token;
    }

    /**
     * Asserts a manual participant instance is created with the right data.
     *
     * @param int $participant_id
     * @param string $relationship
     * @return void
     */
    private function assert_manual_participant_created_with_correct_data(
        int $participant_id,
        string $relationship
    ): void {
        $this->assertEquals($this->internal_manual_user, $participant_id, 'wrong participant');
        $this->assertEquals(constants::RELATIONSHIP_MENTOR, $relationship, 'wrong relationship');
    }

    /**
     * Group participant instance job assignments
     *
     * @return array
     */
    private function group_participant_job_assignments(): array {
        $expected_participants = [];

        foreach ($this->activity_trees as $activity_id => $activity_tree) {
            foreach ($activity_tree->participant_job_assignments as $participant_job_assignment) {
                $expected_participants[$activity_id][$participant_job_assignment['manager']->get_data()->userid] = [
                    'relationship' => 'manager',
                    'count' => 0,
                ];
                $expected_participants[$activity_id][$participant_job_assignment['appraiser']] = [
                    'relationship' => 'appraiser',
                    'count' => 0,
                ];
            }
        }

        return $expected_participants;
    }

    /**
     * Gets loaded user assignments before subject instances are created.
     *
     * @return collection
     */
    private function get_track_user_assignments(): collection {
        return track_user_assignment::repository()
            ->filter_by_no_subject_instances()
            ->filter_by_active()
            ->filter_by_active_track_and_activity()
            ->with('track')
            ->get();
    }

    /**
     * Create subject instances for the track user assignments.
     *
     * @param collection | track_user_assignment[] $track_user_assignments
     * @return collection
     */
    private function create_subject_instances(collection $track_user_assignments): collection {
        $subject_instances = new collection();

        foreach ($track_user_assignments as $user_assignment) {
            $subject_instance = new subject_instance();
            $subject_instance->track_user_assignment_id = $user_assignment->id;
            $subject_instance->subject_user_id = $user_assignment->subject_user_id;
            $subject_instance->job_assignment_id = $user_assignment->job_assignment_id;
            $subject_instance->save();
            $subject_instances->append(subject_instance_dto::create_from_entity($subject_instance));
        }
        return $subject_instances;
    }

    /**
     * Sets up test pre-conditions.
     *
     * @param bool $expand_per_job_assignment
     * @param bool $add_manual_participants
     */
    protected function setup_assignments(
        bool $expand_per_job_assignment = false,
        bool $add_manual_participants = false
    ): void {
        $this->setup_config_values();
        $this->activity_trees = [];

        for ($i = 0; $i < self::NO_OF_ACTIVITIES; $i++) {
            $activity_tree = $this->setup_activity($i, $expand_per_job_assignment, $add_manual_participants);
            $activity_tree->identifier = $i;
            $this->activity_trees[$activity_tree->activity->id] = $this->setup_job_assignments($activity_tree);
        }
    }

    /**
     * Sets up configuration values which the test is based upon.
     *
     * @return void
     */
    protected function setup_config_values(): void {
        $this->setAdminUser();
        $this->tokens = [];
        $this->internal_manual_user = get_admin()->id;
    }

    /**
     * Setup Activity details.
     *
     * @param int $identifier For number of activities to create.
     * @param bool $expand_per_job_assignment
     * @param bool $add_manual_participants
     * @return stdClass
     * @throws coding_exception
     */
    protected function setup_activity(
        int $identifier,
        bool $expand_per_job_assignment = false,
        bool $add_manual_participants = false
    ): stdClass {
        /** @var \mod_perform\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_perform');
        $activity_tree = new stdClass();
        $activity_tree->activity = $generator->create_activity_in_container();

        //create sections and add relationships to activity:
        $activity_tree->section = $generator->create_section(
            $activity_tree->activity,
            ['title' => 'Test section for activity ' . $identifier]
        );

        foreach (self::CALCULATED_RELATIONSHIPS as $relationship) {
            $generator->create_section_relationship(
                $activity_tree->section,
                ['relationship' => $relationship]
            );
        }

        if ($add_manual_participants) {
            foreach (self::MANUAL_RELATIONSHIPS as $relationship) {
                $generator->create_section_relationship(
                    $activity_tree->section,
                    ['relationship' => $relationship]
                );
            }
        }

        $tracks = $generator->create_activity_tracks($activity_tree->activity);

        if ($expand_per_job_assignment) {
            set_config('totara_job_allowmultiplejobs', 1);

            foreach ($tracks as $track) {
                $track_entity = (new track_entity($track->id));
                $track_entity->subject_instance_generation = track_entity::SUBJECT_INSTANCE_GENERATION_ONE_PER_JOB;
                $track_entity->save();
            }
        }

        $activity_tree->track = $generator->create_track_assignments(
            $tracks->first(),
            0,
            0,
            0,
            self::SUBJECTS_PER_ACTIVITY
        );

        return $activity_tree;
    }

    /**
     * Setups job assignments and updates details in activity_tree for the test cases.
     *
     * @param stdClass $activity_tree
     * @return stdClass
     */
    private function setup_job_assignments(stdClass $activity_tree): stdClass {
        $participant_job_assignments = [];
        $data_generator = $this->getDataGenerator();

        for ($i = 0; $i < self::USERS_PER_RELATIONSHIP; $i++) {
            $participant_job_assignments[] = [
                'manager' => job_assignment::create_default($data_generator->create_user()->id),
                'appraiser' => $data_generator->create_user()->id,
            ];
        }
        $activity_tree->participant_job_assignments = $participant_job_assignments;

        $activity_user_job_assignments = [];
        foreach ($activity_tree->track->assignments as $assignment) {
            foreach ($participant_job_assignments as $key => $job_assignments) {
                $activity_user_job_assignments[] = job_assignment::create(
                    [
                        'userid' => $assignment->user_group_id,
                        'idnumber' => $assignment->id . $key . $activity_tree->identifier,
                        'managerjaid' => $job_assignments['manager']->id,
                        'appraiserid' => $job_assignments['appraiser'],
                    ]
                );
            }
        }

        expand_task::create()->expand_all();

        return $activity_tree;
    }

    /**
     * "Completes" manual participant selection.
     *
     * @param collection $subject_instances subject instances to use.
     */
    private function setup_manual_participants(collection $subject_instances): void {
        foreach ($subject_instances as $subject) {
            foreach (self::MANUAL_RELATIONSHIPS as $relationship) {
                $relationship_id = relationship::load_by_idnumber($relationship)->id;

                if ($relationship === constants::RELATIONSHIP_EXTERNAL) {
                    subject_instance_manual_participant::create_for_external(
                        $subject->id,
                        $subject->subject_user_id,
                        $relationship_id,
                        self::EXTERNAL_USER_EMAIL,
                        self::EXTERNAL_USER_NAME
                    );
                } else {
                    subject_instance_manual_participant::create_for_internal(
                        $subject->id,
                        $subject->subject_user_id,
                        $relationship_id,
                        $this->internal_manual_user
                    );
                }
            }
        }
    }

    /**
     * Cleans up test post-conditions.
     */
    protected function tearDown(): void {
        $this->activity_trees = null;
        $this->tokens = null;
        $this->internal_manual_user = null;
    }
}
