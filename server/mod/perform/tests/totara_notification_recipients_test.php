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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 * @category totara_notification
 */

use core\testing\generator as core_generator;
use core_phpunit\testcase;
use mod_perform\constants;
use mod_perform\entity\activity\external_participant;
use mod_perform\entity\activity\participant_instance;
use mod_perform\entity\activity\subject_instance;
use mod_perform\models\activity\participant_source;
use mod_perform\models\activity\subject_instance as subject_instance_model;
use mod_perform\state\activity\active;
use mod_perform\task\service\manual_participant_progress;
use mod_perform\task\service\subject_instance_creation;
use mod_perform\testing\activity_generator_configuration;
use mod_perform\testing\generator as perform_generator;
use mod_perform\totara_notification\recipient\appraiser;
use mod_perform\totara_notification\recipient\direct_report;
use mod_perform\totara_notification\recipient\manager;
use mod_perform\totara_notification\recipient\managers_manager;
use mod_perform\totara_notification\recipient\participant_relationship_recipient;
use mod_perform\totara_notification\recipient\perform_mentor;
use mod_perform\totara_notification\recipient\perform_peer;
use mod_perform\totara_notification\recipient\perform_reviewer;
use totara_core\relationship\relationship;
use totara_core\totara_user as ext_user;
use totara_job\entity\job_assignment as job_assignment_entity;
use totara_job\job_assignment;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_notification
 */
class mod_perform_totara_notification_recipients_testcase extends testcase {

    /**
     * Not in this list are "subject" (tested in separate test case) and "external" (tested separately further below)
     *
     * @return string[][]
     */
    public function recipient_classes_data_provider(): array {
        return [
            [manager::class],
            [managers_manager::class],
            [appraiser::class],
            [direct_report::class],
            [perform_peer::class],
            [perform_mentor::class],
            [perform_reviewer::class],
        ];
    }

    /**
     * @dataProvider recipient_classes_data_provider
     * @param string|participant_relationship_recipient $recipient_class
     * @return void
     */
    public function test_missing_subject_instance_id(string $recipient_class): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Missing subject_instance_id');
        $recipient_class::get_user_ids([]);
    }

    /**
     * @dataProvider recipient_classes_data_provider
     * @param string|participant_relationship_recipient $recipient_class
     * @return void
     */
    public function test_empty_result(string $recipient_class): void {
        self::assertEquals([], $recipient_class::get_user_ids(['subject_instance_id' => -123]));
    }

    /**
     * Test that all manager participants are returned.
     */
    public function test_recipient_manager(): void {
        $data = $this->set_up_activity_with_job_assignment_based_relationships();

        $subject_instance_id = $this->get_subject_instance_id_for_subject_id($data['subject1_id']);

        self::assertEqualsCanonicalizing(
            [$data['manager1_id'], $data['manager2_id']],
            manager::get_user_ids(['subject_instance_id' => $subject_instance_id])
        );

        // Now delete the job assignment data. The result should not change because the same managers are still
        // participating as managers in the activity.
        job_assignment_entity::repository()->delete();
        self::assertEqualsCanonicalizing(
            [$data['manager1_id'], $data['manager2_id']],
            manager::get_user_ids(['subject_instance_id' => $subject_instance_id])
        );
    }

    /**
     * Test that all appraiser participants are returned.
     */
    public function test_recipient_appraiser(): void {
        $data = $this->set_up_activity_with_job_assignment_based_relationships();

        $subject_instance_id = $this->get_subject_instance_id_for_subject_id($data['subject1_id']);

        self::assertEqualsCanonicalizing(
            [$data['appraiser1_id'], $data['appraiser2_id']],
            appraiser::get_user_ids(['subject_instance_id' => $subject_instance_id])
        );

        // Now delete the job assignment data. The result should not change because the same appraisers are still
        // participating as appraisers in the activity.
        job_assignment_entity::repository()->delete();
        self::assertEqualsCanonicalizing(
            [$data['appraiser1_id'], $data['appraiser2_id']],
            appraiser::get_user_ids(['subject_instance_id' => $subject_instance_id])
        );
    }

    /**
     * Test that all managers_manager participants are returned.
     */
    public function test_recipient_managers_manager(): void {
        $data = $this->set_up_activity_with_job_assignment_based_relationships();

        $subject_instance_id = $this->get_subject_instance_id_for_subject_id($data['subject1_id']);

        self::assertEqualsCanonicalizing(
            [$data['managers_manager1_id'], $data['managers_manager2_id']],
            managers_manager::get_user_ids(['subject_instance_id' => $subject_instance_id])
        );

        // Now delete the job assignment data. The result should not change because the same managers_managers are still
        // participating as managers_managers in the activity.
        job_assignment_entity::repository()->delete();
        self::assertEqualsCanonicalizing(
            [$data['managers_manager1_id'], $data['managers_manager2_id']],
            managers_manager::get_user_ids(['subject_instance_id' => $subject_instance_id])
        );
    }

    /**
     * Test that all direct_report participants are returned.
     */
    public function test_recipient_direct_report(): void {
        $data = $this->set_up_activity_with_job_assignment_based_relationships();

        $subject_instance_id = $this->get_subject_instance_id_for_subject_id($data['subject1_id']);

        self::assertEqualsCanonicalizing(
            [$data['direct_report1_id'], $data['direct_report2_id']],
            direct_report::get_user_ids(['subject_instance_id' => $subject_instance_id])
        );

        // Now delete the job assignment data. The result should not change because the same direct_reports are still
        // participating as direct_reports in the activity.
        job_assignment_entity::repository()->delete();
        self::assertEqualsCanonicalizing(
            [$data['direct_report1_id'], $data['direct_report2_id']],
            direct_report::get_user_ids(['subject_instance_id' => $subject_instance_id])
        );
    }

    /**
     * Test recipients for manual selection based relationships.
     */
    public function test_recipients_for_manual_selection_relationships(): void {
        $data = $this->set_up_activity_with_selection_relationships();

        self::assertEqualsCanonicalizing(
            [$data['peer1_id'], $data['peer2_id']],
            perform_peer::get_user_ids(['subject_instance_id' => $data['subject_instance1_id']])
        );
        self::assertEquals(
            [$data['peer3_id']],
            perform_peer::get_user_ids(['subject_instance_id' => $data['subject_instance2_id']])
        );
        
        self::assertEqualsCanonicalizing(
            [$data['mentor1_id'], $data['mentor2_id']],
            perform_mentor::get_user_ids(['subject_instance_id' => $data['subject_instance1_id']])
        );
        self::assertEquals(
            [$data['mentor3_id']],
            perform_mentor::get_user_ids(['subject_instance_id' => $data['subject_instance2_id']])
        );
        
        self::assertEqualsCanonicalizing(
            [$data['reviewer1_id'], $data['reviewer2_id']],
            perform_reviewer::get_user_ids(['subject_instance_id' => $data['subject_instance1_id']])
        );
        self::assertEquals(
            [$data['reviewer3_id']],
            perform_reviewer::get_user_ids(['subject_instance_id' => $data['subject_instance2_id']])
        );
    }

    public function test_external_missing_subject_instance_id(): void {
        self::markTestSkipped('Activate and adjust after TL-34958 is done');
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Missing subject_instance_id');
//        perform_external::get_user_objects([]);
    }

    public function test_external_empty_result(): void {
        self::markTestSkipped('Activate and adjust after TL-34958 is done');
//        self::assertEquals([], perform_external::get_user_objects(['subject_instance_id' => -123]));
    }

    public function test_recipient_external_participant() {
        self::markTestSkipped('Activate and adjust after TL-34958 is done');
        $data = $this->set_up_activity_with_external_relationship();

        // get_user_ids() always returns empty array.
//        self::assertEquals([], perform_external::get_user_ids(['subject_instance_id' => -123]));
//        self::assertEquals([], perform_external::get_user_ids(['subject_instance_id' => $data['subject_instance_id']]));

        // get_user_objects() is the relevant method.
//        $users = perform_external::get_user_objects(['subject_instance_id' => $data['subject_instance_id']]);
        $this->assertCount(2, $users);

        $result_emails = [];
        foreach ($users as $user) {
            $this->assertInstanceOf('stdClass', $user);
            $this->assertEquals(ext_user::EXTERNAL_USER, $user->id);
            $result_emails[] = $user->email;
        }
        $this->assertEqualsCanonicalizing(
            [
                $data['external_user1_email'],
                $data['external_user2_email'],
            ],
            $result_emails
        );
    }

    /**
     * @param int $subject_id
     * @return int
     */
    private function get_subject_instance_id_for_subject_id(int $subject_id): int {
        /** @var participant_instance $participant_instance */
        $participant_instance = participant_instance::repository()
            ->where('participant_id', $subject_id)
            ->one(true);
        return $participant_instance->subject_instance->id;
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

        $activity = $perform_generator->create_full_activities($config)->first();

        // Pick one of the generated subject users and give them a second manager and a second appraiser.
        $subject_jas = job_assignment_entity::repository()->where_not_null('managerjaid')->get();
        self::assertCount(2, $subject_jas);

        /** @var job_assignment_entity $subject1_ja1 */
        $subject1_ja1 = $subject_jas->first();
        $subject1_id = $subject1_ja1->userid;
        $managers = job_assignment::get_all_manager_userids($subject1_id);
        self::assertCount(1, $managers);
        $manager1_id = (int)reset($managers);
        /** @var job_assignment_entity $appraiser1_ja */
        $appraiser1_ja = job_assignment_entity::repository()
            ->where('userid', $subject1_id)
            ->where_not_null('appraiserid')
            ->one(true);
        $appraiser1_id = $appraiser1_ja->appraiserid;
        $subject2_id = $subject_jas->last()->userid;
        $appraiser2_id = $generator->create_user()->id;
        $manager2_id = $generator->create_user()->id;
        /** @var job_assignment $subject1_ja2 */
        $subject1_ja2 = job_assignment::create([
            'userid' => $subject1_id,
            'idnumber' => 'app/no_cohort/' . $subject1_id,
            'appraiserid' => $appraiser2_id,
            'managerjaid' => job_assignment::create_default($manager2_id)->id,
        ]);

        // Add two manager's managers.
        $managers_manager1_id = $generator->create_user()->id;
        $managers_manager2_id = $generator->create_user()->id;
        job_assignment_entity::repository()
            ->where('id', $subject1_ja1->managerjaid)
            ->update(['managerjaid' => job_assignment::create_default($managers_manager1_id)->id]);
        job_assignment_entity::repository()
            ->where('id', $subject1_ja2->managerjaid)
            ->update(['managerjaid' => job_assignment::create_default($managers_manager2_id)->id]);

        // Add two direct reports.
        $direct_report1_id = $generator->create_user()->id;
        $direct_report2_id = $generator->create_user()->id;
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
        $mentor1 = $generator->create_user();
        $mentor2 = $generator->create_user();
        $mentor3 = $generator->create_user();
        $peer1 = $generator->create_user();
        $peer2 = $generator->create_user();
        $peer3 = $generator->create_user();
        $reviewer1 = $generator->create_user();
        $reviewer2 = $generator->create_user();
        $reviewer3 = $generator->create_user();

        $subject_instance1 = $perform_generator->create_subject_instance_with_pending_selections(
            $activity1, $subject_user1, [$peer_relationship, $reviewer_relationship, $mentor_relationship]
        );
        $subject_instance1->created_at = strtotime('2020-01-01');
        $subject_instance1->save();
        $subject_instance1 = subject_instance_model::load_by_entity($subject_instance1);

        $subject_instance2 = $perform_generator->create_subject_instance_with_pending_selections(
            $activity1, $subject_user2, [$peer_relationship, $reviewer_relationship, $mentor_relationship]
        );
        $subject_instance2->created_at = strtotime('2020-04-01');
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

        self::assertEquals(2, $external_participant_instances->count());

        /** @var participant_instance $external_participant_instance1 */
        $external_participant_instance1 = $external_participant_instances->first();
        /** @var participant_instance $external_participant_instance2 */
        $external_participant_instance2 = $external_participant_instances->last();

        /** @var external_participant $external_participant1 */
        $external_participant1 = external_participant::repository()->find($external_participant_instance1->participant_id);
        /** @var external_participant $external_participant2 */
        $external_participant2 = external_participant::repository()->find($external_participant_instance2->participant_id);

        return [
            'subject_instance_id' => $subject_instance1->id,
            'external_user1_email' => $external_participant1->email,
            'external_user2_email' => $external_participant2->email,
        ];

    }
}