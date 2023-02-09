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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package mod_perform
 */

use core\collection;
use core\entity\user;
use mod_perform\constants;
use mod_perform\entity\activity\manual_relationship_selection_progress;
use mod_perform\entity\activity\manual_relationship_selector;
use mod_perform\entity\activity\participant_instance;
use mod_perform\entity\activity\subject_instance_manual_participant;
use mod_perform\event\subject_instance_manual_participants_selected;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\subject_instance;
use mod_perform\models\activity\participant_instance as participant_instance_model;
use mod_perform\models\activity\participant_source;
use mod_perform\state\subject_instance\active;
use mod_perform\state\subject_instance\pending;
use totara_core\relationship\relationship;
use totara_job\job_assignment;

/**
 * @group perform
 */
class mod_perform_subject_instance_set_participant_users_testcase extends advanced_testcase {

    /**
     * @var \mod_perform\testing\generator
     */
    private $generator;

    protected function setUp(): void {
        $this->generator = \mod_perform\testing\generator::instance();
    }

    protected function tearDown(): void {
        parent::tearDown();
        $this->generator = null;
    }

    public function test_set_participant_users_successfully(): void {
        $data = $this->generate_test_data();

        // Relationships
        $peer_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_PEER);
        $mentor_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_MENTOR);
        $reviewer_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_REVIEWER);
        $external_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_EXTERNAL);

        $external_users = [
            ['name' => 'Mark Metcalfe', 'email' => 'mark.metcalfe@totaralearning.com'],
            ['name' => 'Some Guy', 'email' => 'some.guy@example.com'],
        ];

        $this->assertEquals(pending::get_code(), $data->act1_user1_subject_instance->status);
        $this->assertCount(0, $data->act1_user1_subject_instance->participant_instances);

        // All selections are pending, with no users selected yet.
        $this->assert_pending_progress_records_count(4, $data->act1_user1_subject_instance->id);
        $this->assert_pending_selector_user_count(4, $data->act1_user1_subject_instance->id);
        $this->assert_selected_users_count(0, $data->act1_user1_subject_instance->id);

        // Set participants as manager user
        self::setUser($data->manager_user);
        $data->act1_user1_subject_instance->set_participant_users($data->manager_user->id, [
            [
                'manual_relationship_id' => $mentor_relationship->id,
                'users' => [
                    ['user_id' => $data->manager_user->id]
                ],
            ],
            [
                'manual_relationship_id' => $reviewer_relationship->id,
                'users' => [
                    ['user_id' => $data->appraiser_user->id],
                    ['user_id' => $data->user2->id]
                ],
            ],
            [
                'manual_relationship_id' => $external_relationship->id,
                'users' => $external_users,
            ],
        ]);

        // Not activated and no participant instances yet as the subject user still needs to set participants
        $this->assertEquals(pending::get_code(), $data->act1_user1_subject_instance->status);
        $this->assertCount(0, $data->act1_user1_subject_instance->participant_instances);

        // There should now be selection records.
        $this->assert_pending_progress_records_count(1, $data->act1_user1_subject_instance->id);
        $this->assert_pending_selector_user_count(1, $data->act1_user1_subject_instance->id);
        $this->assert_selected_users_count(5, $data->act1_user1_subject_instance->id);

        // Set participants as user1 (subject user)
        self::setUser($data->user1);
        $data->act1_user1_subject_instance->set_participant_users($data->user1->id, [
            [
                'manual_relationship_id' => $peer_relationship->id,
                'users' => [
                    ['user_id' => $data->user2->id],
                ],
            ],
        ]);

        // All relationships have had participants selected for them so should be active and participants created.
        $this->assertEquals(active::get_code(), $data->act1_user1_subject_instance->status);

        // Selection records no longer needed and should now be deleted.
        $this->assert_pending_progress_records_count(0, $data->act1_user1_subject_instance->id);
        $this->assert_pending_selector_user_count(0, $data->act1_user1_subject_instance->id);
        $this->assert_selected_users_count(0, $data->act1_user1_subject_instance->id);

        // 4 participant instances - 1 each for manager, appraiser, and 2 for user2
        /** @var participant_instance[]|collection $participants */
        $participants = $data->act1_user1_subject_instance->participant_instances;

        $this->assertCount(6, $participants);

        // Make sure user2 has 2 participant instances, 1 for the peer relationship and 1 for the reviewer relationship.
        $user2_participant_instances = $participants
            ->filter('participant_id', $data->user2->id)
            ->sort('core_relationship_id');
        $this->assertCount(2, $user2_participant_instances);
        $this->assertEquals($peer_relationship->id, $user2_participant_instances->first()->core_relationship_id);
        $this->assertEquals($reviewer_relationship->id, $user2_participant_instances->last()->core_relationship_id);

        // Make sure the manager user has 1 participant instance for the mentor relationship.
        $manager_participant_instances = $participants->filter('participant_id', $data->manager_user->id);
        $this->assertCount(1, $manager_participant_instances);
        $this->assertEquals($mentor_relationship->id, $manager_participant_instances->first()->core_relationship_id);

        // Make sure the appraiser user has 1 participant instance for the reviewer relationship.
        $appraiser_participant_instances = $participants->filter('participant_id', $data->appraiser_user->id);
        $this->assertCount(1, $appraiser_participant_instances);
        $this->assertEquals($reviewer_relationship->id, $appraiser_participant_instances->first()->core_relationship_id);

        // Assert external participant instances are properly created.
        $actual_externals = $participants
            ->filter('participant_source', participant_source::EXTERNAL)
            ->map(function (participant_instance_model $participant_instance): array {
                $participant = $participant_instance->participant;
                return ['name' => $participant->fullname, 'email' => $participant->email];
            })
            ->all();
        $this->assertEqualsCanonicalizing($external_users, $actual_externals);

        // Make sure participants can only be set once - next attempt gets an exception.
        $this->expectException(coding_exception::class);
        $data->act1_user1_subject_instance->set_participant_users($data->user1->id, [
            [
                'manual_relationship_id' => $mentor_relationship->id,
                'users' => [
                    ['user_id' => $data->manager_user->id],
                ],
            ],
        ]);
    }

    public function test_events_are_fired(): void {
        $data = $this->generate_test_data();

        // Relationships
        $peer_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_PEER);
        $mentor_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_MENTOR);
        $reviewer_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_REVIEWER);
        $external_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_EXTERNAL);

        // Set participants as manager user
        self::setUser($data->manager_user);
        $sink = $this->redirectEvents();
        $data->act1_user1_subject_instance->set_participant_users($data->manager_user->id, [
            [
                'manual_relationship_id' => $mentor_relationship->id,
                'users' => [
                    ['user_id' => $data->manager_user->id],
                ],
            ],
            [
                'manual_relationship_id' => $reviewer_relationship->id,
                'users' => [
                    ['user_id' => $data->appraiser_user->id],
                    ['user_id' => $data->user2->id],
                ],
            ],
            [
                'manual_relationship_id' => $external_relationship->id,
                'users' => [
                    ['name' => 'Mark Metcalfe', 'email' => 'mark.metcalfe@totaralearning.com'],
                ],
            ],
        ]);
        $events = $sink->get_events();
        $sink->close();
        $event = reset($events);

        // Make sure the event and it's description has quite a bit of information in it!
        $this->assertInstanceOf(subject_instance_manual_participants_selected::class, $event);
        $this->assertEquals(
            get_string('event_subject_instance_manual_participants_selected', 'mod_perform'),
            $event::get_name()
        );
        $event_description = $event->get_description();
        $this->assertStringContainsString("Selector user with id {$data->manager_user->id}", $event_description);
        $this->assertStringContainsString("subject instance with id {$data->act1_user1_subject_instance->id}", $event_description);
        $this->assertStringContainsString("Relationship with id {$mentor_relationship->id}", $event_description);
        $this->assertStringContainsString("user with id {$data->manager_user->id}", $event_description);
        $this->assertStringContainsString("user with id {$data->appraiser_user->id}", $event_description);
        $this->assertStringContainsString("user with id {$data->user2->id}", $event_description);
        $this->assertStringContainsString(
            "user with email mark.metcalfe@totaralearning.com and name 'Mark Metcalfe'", $event_description
        );

        // Set participants as user1 (subject user)
        self::setUser($data->user1);
        $sink = $this->redirectEvents();
        $data->act1_user1_subject_instance->set_participant_users($data->user1->id, [
            [
                'manual_relationship_id' => $peer_relationship->id,
                'users' => [
                    ['user_id' => $data->user2->id],
                ],
            ],
        ]);
        $events = $sink->get_events();
        $sink->close();
        $event = reset($events);

        // Make sure event was fired.
        $this->assertInstanceOf(subject_instance_manual_participants_selected::class, $event);
        $this->assertStringContainsString("Selector user with id {$data->user1->id}", $event->get_description());
        $this->assertStringContainsString("user with id {$data->user2->id}", $event->get_description());
    }

    public function test_no_users_specified(): void {
        $data = $this->generate_test_data();

        self::setUser($data->manager_user);

        $valid_relationship_id = relationship::load_by_idnumber(constants::RELATIONSHIP_PEER)->id;

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Must specify at least one user to create a manual participant record.');

        $data->act2_user1_subject_instance
            ->set_participant_users($data->manager_user->id, [
                [
                    'manual_relationship_id' => $valid_relationship_id,
                    'users' => [],
                ],
            ]);
    }

    public function test_invalid_relationship_id(): void {
        $data = $this->generate_test_data();

        self::setUser($data->manager_user);

        $valid_relationship_id = relationship::load_by_idnumber(constants::RELATIONSHIP_PEER)->id;

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            "The relationship IDs specified [-1] do not match the required IDs [{$valid_relationship_id}]"
        );

        $data->act2_user1_subject_instance
            ->set_participant_users($data->manager_user->id, [
                [
                    'manual_relationship_id' => -1,
                    'users' => [],
                ],
            ]);
    }

    public function test_no_pending_selections(): void {
        $data = $this->generate_test_data();

        $admin_user = get_admin();
        self::setUser($admin_user);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            'User id ' . $admin_user->id . ' does not have any pending selections for subject instance ' .
            $data->act1_user1_subject_instance->id
        );

        $data->act1_user1_subject_instance
            ->set_participant_users($admin_user->id, []);
    }

    public function test_no_pending_selections_for_closed_subject_instance(): void {
        $data = $this->generate_test_data();

        $mentor_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_MENTOR);

        // Set participants as manager user
        self::setUser($data->manager_user);
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            'User id ' . $data->manager_user->id . ' does not have any pending selections for subject instance ' .
            $data->act1_user1_subject_instance->id
        );

        $data->act1_user1_subject_instance->manually_close(true);
        $data->act1_user1_subject_instance->set_participant_users($data->manager_user->id, [
            [
                'manual_relationship_id' => $mentor_relationship->id,
                'users' => [
                    ['user_id' => $data->manager_user->id],
                ],
            ],
        ]);
    }

    public function test_set_when_active(): void {
        $admin_user = get_admin();
        self::setUser($admin_user);
        $activity = $this->generator->create_activity_in_container();
        $subject_instance = $this->generator->create_subject_instance([
            'activity_id' => $activity->id, 'subject_user_id' => $admin_user->id,
        ]);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Subject instance {$subject_instance->id} is not pending.");

        subject_instance::load_by_entity($subject_instance)
            ->set_participant_users($admin_user->id, []);
    }

    public function test_invalid_external_user_input(): void {
        self::setAdminUser();
        $user1 = self::getDataGenerator()->create_user();

        $subject_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_SUBJECT);
        $external_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_EXTERNAL);

        $activity1 = $this->generator->create_activity_in_container(['activity_name' => 'Activity One']);
        $this->generator->create_manual_relationships_for_activity($activity1, [
            ['selector' => $subject_relationship->id, 'manual' => $external_relationship->id],
        ]);

        $subject_instance = $this->generator->create_subject_instance_with_pending_selections(
            $activity1, $user1, [$external_relationship]
        );
        $subject_instance = subject_instance::load_by_entity($subject_instance);

        // Make bad strings cant be saved.
        try {
            $subject_instance->set_participant_users($user1->id, [
                [
                    'manual_relationship_id' => $external_relationship->id,
                    'users' => [
                        ['name' => 'Mark <script>Bad</script>Metcalfe', 'email' => 'mark.metcalfe@totaralearning.com'],
                    ]
                ],
            ]);
            $this->fail('Expected validation coding exception');
        } catch (coding_exception $e) {
            $this->assertStringContainsString("Invalid user properties", $e->getMessage());
        }

        // Make sure an invalid email can not be saved.
        $invalid_email = 'not_an_email';
        try {
            $subject_instance->set_participant_users($user1->id, [
                [
                    'manual_relationship_id' => $external_relationship->id,
                    'users' => [
                        ['name' => 'Mark Metcalfe', 'email' => $invalid_email],
                    ]
                ],
            ]);
            $this->fail('Expected invalid email address coding exception');
        } catch (coding_exception $e) {
            $this->assertStringContainsString("Invalid email address specified: '$invalid_email'", $e->getMessage());
        }

        // Make sure there can't be duplicate email addresses.
        $duplicate_email = 'mark.metcalfe@totaralearning.com';
        try {
            $subject_instance->set_participant_users($user1->id, [
                [
                    'manual_relationship_id' => $external_relationship->id,
                    'users' => [
                        ['name' => 'Mark Metcalfe', 'email' => $duplicate_email],
                        ['name' => 'Not Mark Metcalfe', 'email' => $duplicate_email],
                    ]
                ],
            ]);
            $this->fail('Expected duplicate email address coding exception');
        } catch (coding_exception $e) {
            $this->assertStringContainsString(
                "Can not create multiple participant records for user with email $duplicate_email", $e->getMessage()
            );
        }
    }

    /**
     * Assert the number of users that have already been selected for a subject instance.
     *
     * @param int $expected_count
     * @param int $subject_instance_id
     */
    private function assert_selected_users_count(int $expected_count, int $subject_instance_id): void {
        $actual_count = subject_instance_manual_participant::repository()
            ->where('subject_instance_id', $subject_instance_id)
            ->count();

        $this->assertEquals($expected_count, $actual_count);
    }

    /**
     * Assert the number of pending progress records for a subject instance.
     *
     * @param int $expected_count
     * @param int $subject_instance_id
     */
    private function assert_pending_progress_records_count(int $expected_count, int $subject_instance_id): void {
        $actual_count = manual_relationship_selection_progress::repository()
            ->where('subject_instance_id', $subject_instance_id)
            ->where('status', manual_relationship_selection_progress::STATUS_PENDING)
            ->count();

        $this->assertEquals($expected_count, $actual_count);
    }

    /**
     * Assert the number of users that need to make a manual selection (per relationship) for a subject instance.
     *
     * @param int $expected_count
     * @param int $subject_instance_id
     */
    private function assert_pending_selector_user_count(int $expected_count, int $subject_instance_id): void {
        $actual_count = manual_relationship_selector::repository()
            ->join([manual_relationship_selection_progress::TABLE, 'progress'], 'manual_relation_select_progress_id', 'id')
            ->where('progress.subject_instance_id', $subject_instance_id)
            ->where('progress.status', manual_relationship_selection_progress::STATUS_PENDING)
            ->count();

        $this->assertEquals($expected_count, $actual_count);
    }

    private function generate_test_data(): manual_participant_selector_test_data {
        self::setAdminUser();
        $data = new manual_participant_selector_test_data();
        $data->create_data();
        self::setUser();
        return $data;
    }

}

class manual_participant_selector_test_data {
    /** @var user|object */
    public $user1;
    /** @var user|object */
    public $user2;
    /** @var user|object */
    public $manager_user;
    /** @var user|object */
    public $appraiser_user;
    /** @var activity */
    public $activity1;
    /** @var activity */
    public $activity2;
    /** @var activity */
    public $activity3;
    /** @var subject_instance */
    public $act1_user1_subject_instance;
    /** @var subject_instance */
    public $act2_user1_subject_instance;
    /** @var subject_instance */
    public $act3_user1_subject_instance;
    /** @var subject_instance */
    public $act1_user2_subject_instance;
    /** @var subject_instance */
    public $act2_user2_subject_instance;
    /** @var subject_instance */
    public $act3_user2_subject_instance;

    /** @var \core\testing\generator */
    private $generator;

    /** @var \mod_perform\testing\generator */
    private $perform_generator;

    public function __construct() {
        $this->generator = \core\testing\generator::instance();
        $this->perform_generator = \mod_perform\testing\generator::instance();
    }

    public function create_data(): void {
        $this->create_users();
        $this->create_activities_and_instances();
    }

    private function create_users(): void {
        $this->manager_user = $this->generator->create_user();
        $manager_ja = job_assignment::create_default($this->manager_user->id);
        $this->appraiser_user = $this->generator->create_user();

        $this->user1 = $this->generator->create_user();
        job_assignment::create([
            'userid' => $this->user1->id, 'managerjaid' => $manager_ja->id, 'idnumber' => 1
        ]);
        job_assignment::create([
            'userid' => $this->user1->id, 'appraiserid' => $this->appraiser_user->id, 'idnumber' => 2
        ]);

        $this->user2 = $this->generator->create_user();
        job_assignment::create([
            'userid' => $this->user2->id, 'appraiserid' => $this->appraiser_user->id, 'idnumber' => 4
        ]);
    }

    private function create_activities_and_instances(): void {
        $subject_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_SUBJECT);
        $manager_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_MANAGER);
        $appraiser_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_APPRAISER);
        $peer_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_PEER);
        $mentor_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_MENTOR);
        $reviewer_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_REVIEWER);
        $external_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_EXTERNAL);

        $this->activity1 = $this->perform_generator->create_activity_in_container(['activity_name' => 'Activity One']);
        $this->perform_generator->create_manual_relationships_for_activity($this->activity1, [
            ['selector' => $subject_relationship->id, 'manual' => $peer_relationship->id],
            ['selector' => $manager_relationship->id, 'manual' => $reviewer_relationship->id],
            ['selector' => $manager_relationship->id, 'manual' => $mentor_relationship->id],
            ['selector' => $manager_relationship->id, 'manual' => $external_relationship->id],
        ]);

        $this->activity2 = $this->perform_generator->create_activity_in_container(['activity_name' => 'Activity Two']);
        $this->perform_generator->create_manual_relationships_for_activity($this->activity2, [
            ['selector' => $manager_relationship, 'manual' => $peer_relationship],
            ['selector' => $appraiser_relationship, 'manual' => $reviewer_relationship],
            ['selector' => $appraiser_relationship, 'manual' => $external_relationship],
        ]);

        $this->activity3 = $this->perform_generator->create_activity_in_container(['activity_name' => 'Activity Three']);
        $this->perform_generator->create_manual_relationships_for_activity($this->activity3, [
            ['selector' => $subject_relationship, 'manual' => $reviewer_relationship],
            ['selector' => $appraiser_relationship, 'manual' => $peer_relationship],
            ['selector' => $appraiser_relationship, 'manual' => $external_relationship],
        ]);

        $this->act1_user1_subject_instance = $this->perform_generator->create_subject_instance_with_pending_selections(
            $this->activity1, $this->user1, [$peer_relationship, $reviewer_relationship, $mentor_relationship]
        );
        $this->act1_user1_subject_instance->created_at = strtotime('2020-01-01');
        $this->act1_user1_subject_instance->save();
        $this->act1_user1_subject_instance = subject_instance::load_by_entity($this->act1_user1_subject_instance);

        $this->act2_user1_subject_instance = $this->perform_generator->create_subject_instance_with_pending_selections(
            $this->activity2, $this->user1, [$peer_relationship, $reviewer_relationship, $external_relationship]
        );
        $this->act2_user1_subject_instance->created_at = strtotime('2020-02-01');
        $this->act2_user1_subject_instance->save();
        $this->act2_user1_subject_instance = subject_instance::load_by_entity($this->act2_user1_subject_instance);

        $this->act3_user1_subject_instance = $this->perform_generator->create_subject_instance_with_pending_selections(
            $this->activity3, $this->user1, [$reviewer_relationship, $peer_relationship, $external_relationship]
        );
        $this->act3_user1_subject_instance->created_at = strtotime('2020-03-01');
        $this->act3_user1_subject_instance->save();
        $this->act3_user1_subject_instance = subject_instance::load_by_entity($this->act3_user1_subject_instance);

        $this->act1_user2_subject_instance = $this->perform_generator->create_subject_instance_with_pending_selections(
            $this->activity1, $this->user2, [$peer_relationship, $mentor_relationship, $external_relationship]
        );
        $this->act1_user2_subject_instance->created_at = strtotime('2020-04-01');
        $this->act1_user2_subject_instance->save();
        $this->act1_user2_subject_instance = subject_instance::load_by_entity($this->act1_user2_subject_instance);

        $this->act2_user2_subject_instance = $this->perform_generator->create_subject_instance_with_pending_selections(
            $this->activity2, $this->user2, [$peer_relationship, $reviewer_relationship, $external_relationship]
        );
        $this->act2_user2_subject_instance->created_at = strtotime('2020-05-01');
        $this->act2_user2_subject_instance->save();
        $this->act2_user2_subject_instance = subject_instance::load_by_entity($this->act2_user2_subject_instance);

        $this->act3_user2_subject_instance = $this->perform_generator->create_subject_instance_with_pending_selections(
            $this->activity3, $this->user2, [$reviewer_relationship, $peer_relationship, $external_relationship]
        );
        $this->act3_user2_subject_instance->created_at = strtotime('2020-06-01');
        $this->act3_user2_subject_instance->save();
        $this->act3_user2_subject_instance = subject_instance::load_by_entity($this->act3_user2_subject_instance);
    }

}
