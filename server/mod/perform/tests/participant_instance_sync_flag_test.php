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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\constants;
use mod_perform\entity\activity\subject_instance;
use mod_perform\models\activity\activity_setting;
use totara_job\entity\job_assignment as job_assignment_entity;
use totara_job\job_assignment;

require_once(__DIR__ . '/participant_instance_sync_testcase.php');

/**
 * Class participant_instance_creation_service_test
 *
 * @group perform
 */
class mod_perform_participant_instance_sync_flag_testcase extends mod_perform_participant_instance_sync_testcase {

    protected function setUp(): void {
        parent::setUp();
        set_config('perform_sync_participant_instance_creation', 1);
        set_config('perform_sync_participant_instance_closure', 1);
    }

    public function test_create_new_ja_without_relevant_changes(): void {
        $data = $this->create_activity_with_relationships(self::$all_relationships, [], true);
        $participants = $data['participants'];
        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);

        /** @var job_assignment $subject_ja */
        $subject_ja = job_assignment::create_default($subject_id,
            [
                'managerjaid' => null,
                'appraiserid' => null,
            ]
        );

        // No subject instance should be flagged.
        $this->assert_no_subject_instances_flagged();
    }

    public function test_create_new_ja_with_new_manager(): void {
        $data = $this->create_activity_with_relationships(self::$all_relationships, [], true);
        $participants = $data['participants'];
        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);

        $this->assert_no_subject_instances_flagged();

        $this->add_manager_for_user($subject_id);

        // Subject instance for the subject user should be flagged because a new manager was added.
        $this->assert_subject_instances_flagged([$subject_id]);
    }

    public function test_create_new_ja_with_new_appraiser(): void {
        $data = $this->create_activity_with_relationships(self::$all_relationships, [], true);
        $participants = $data['participants'];
        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);

        $this->assert_no_subject_instances_flagged();

        $this->add_appraiser_for_user($subject_id);

        // Subject instance for the subject user should be flagged because a new appraiser was added.
        $this->assert_subject_instances_flagged([$subject_id]);
    }

    public function test_create_new_ja_with_new_direct_report(): void {
        $data = $this->create_activity_with_relationships(self::$all_relationships, [], true);
        $participants = $data['participants'];
        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);

        $this->assert_no_subject_instances_flagged();

        $this->add_direct_report_for_user($subject_id);

        // Subject instance for the subject user should be flagged because a new direct report was added.
        $this->assert_subject_instances_flagged([$subject_id]);
    }

    public function test_create_new_ja_with_other_subject_as_manager(): void {
        $data = $this->create_activity_with_relationships(self::$all_relationships, [], true);
        $participants = $data['participants'];
        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);
        $other_subject_id = $data['other_subject_id'];
        /** @var job_assignment $new_boss_ja */
        $other_subject_ja = job_assignment::create_default($other_subject_id);

        $this->assert_no_subject_instances_flagged();

        job_assignment::create_default($subject_id,
            [
                'managerjaid' => $other_subject_ja->id,
            ]
        );

        /*
         * Subject instance for both the subject user and the other subject user should be flagged because:
         *  - subject user has a new manager
         *  - other subject user has a new direct report
         */
        $this->assert_subject_instances_flagged([$subject_id, $other_subject_id]);
    }

    public function test_create_new_ja_with_other_subject_as_appraiser(): void {
        $data = $this->create_activity_with_relationships(self::$all_relationships, [], true);
        $participants = $data['participants'];
        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);
        $other_subject_id = $data['other_subject_id'];

        $this->assert_no_subject_instances_flagged();

        job_assignment::create_default($subject_id,
            [
                'appraiserid' => $other_subject_id,
            ]
        );

        /*
         * Subject instance for the subject user should be flagged because a new appraiser was added.
         * The other subject user should not get a flag because becoming an appraiser cannot change any relationship
         * in someone's own subject instance.
         */
        $this->assert_subject_instances_flagged([$subject_id]);
    }

    public function test_create_new_ja_with_new_manager_for_existing_manager(): void {
        $data = $this->create_activity_with_relationships(self::$all_relationships, [], true);
        $participants = $data['participants'];
        $manager_id = array_search(constants::RELATIONSHIP_MANAGER, $participants);

        $new_boss = self::getDataGenerator()->create_user();
        /** @var job_assignment $new_boss_ja */
        $new_boss_ja = job_assignment::create_default($new_boss->id);

        $this->assert_no_subject_instances_flagged();

        job_assignment::create_default($manager_id,
            [
                'managerjaid' => $new_boss_ja->id,
            ]
        );

        /*
         * Note that even though we just created a new manager for the subject's existing manager, this does NOT mean
         * the subject has a new "Manager's manager" because it's only considered a "Manager's manager" if the link from
         * "subject > manager > manager's manager" is through the SAME job assignment. But we created a new one. For
         * the difference, see following test_update_ja_replacing_manager().
         *
         * So we don't expect the subject user's instance to be flagged. However, the manager's subject instance
         * should be flagged because he got a new manager.
         */
        $this->assert_subject_instances_flagged([$manager_id]);
    }

    public function test_update_ja_replacing_manager(): void {
        $data = $this->create_activity_with_relationships(self::$all_relationships, [], true);
        $participants = $data['participants'];
        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);
        $manager_id = array_search(constants::RELATIONSHIP_MANAGER, $participants);
        $previous_managers_manager_id = array_search(constants::RELATIONSHIP_MANAGERS_MANAGER, $participants);
        /** @var job_assignment_entity $manager_ja */
        $manager_ja = job_assignment_entity::repository()
            ->where('userid', $manager_id)
            ->one(true);

        $new_managers_manager = self::getDataGenerator()->create_user();
        /** @var job_assignment $new_managers_manager_ja */
        $new_managers_manager_ja = job_assignment::create_default($new_managers_manager->id);

        $this->assert_no_subject_instances_flagged();

        $job_assignment = job_assignment::from_entity($manager_ja);
        $job_assignment->update([
            'managerjaid' => $new_managers_manager_ja->id
        ]);

        /*
         * We expect to be flagged:
         *  - subject's subject instance because a new manager's manager was added
         *  - manager's subject instance because a new manager was added
         *  - manager's manager's subject instance because a new manager was added
         */
        $this->assert_subject_instances_flagged([
            $subject_id,
            $manager_id,
            $previous_managers_manager_id
        ]);
    }

    public function test_update_ja_adding_manager(): void {
        $data = $this->create_activity_with_relationships(
            [
                constants::RELATIONSHIP_SUBJECT => true,
                constants::RELATIONSHIP_APPRAISER => true,
                constants::RELATIONSHIP_DIRECT_REPORT => true,
                constants::RELATIONSHIP_MANAGER => false,
            ],
            [],
            true
        );
        $participants = $data['participants'];
        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);
        $direct_report_id = array_search(constants::RELATIONSHIP_DIRECT_REPORT, $participants);

        $this->assert_no_subject_instances_flagged();

        /** @var job_assignment_entity $subject_ja_entity */
        $subject_ja_entity = job_assignment_entity::repository()->where('userid', $subject_id)->one(true);
        $this->add_manager_for_job_assignment(job_assignment::from_entity($subject_ja_entity));

        $this->assert_subject_instances_flagged([
            $subject_id,
            $direct_report_id
        ]);
    }

    public function test_update_ja_removing_manager(): void {
        $data = $this->create_activity_with_relationships(self::$all_relationships, [], true);
        $participants = $data['participants'];
        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);
        $direct_report_id = array_search(constants::RELATIONSHIP_DIRECT_REPORT, $participants);
        $manager_id = array_search(constants::RELATIONSHIP_MANAGER, $participants);

        $this->assert_no_subject_instances_flagged();

        $this->remove_manager($subject_id);

        $this->assert_subject_instances_flagged([
            $subject_id,
            $direct_report_id,
            $manager_id,
        ]);
    }

    public function test_update_ja_replacing_appraiser(): void {
        $data = $this->create_activity_with_relationships(self::$all_relationships, [], true);
        $participants = $data['participants'];
        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);
        $appraiser_id = array_search(constants::RELATIONSHIP_APPRAISER, $participants);

        $new_appraiser = self::getDataGenerator()->create_user();

        $this->assert_no_subject_instances_flagged();

        $this->replace_appraiser($subject_id, $new_appraiser->id);

        $this->assert_subject_instances_flagged([$subject_id]);
    }

    public function test_update_ja_removing_appraiser(): void {
        $data = $this->create_activity_with_relationships(self::$all_relationships, [], true);
        $participants = $data['participants'];
        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);

        $this->assert_no_subject_instances_flagged();

        $this->remove_appraiser($subject_id);

        $this->assert_subject_instances_flagged([$subject_id]);
    }

    public function test_update_ja_adding_appraiser(): void {
        $data = $this->create_activity_with_relationships(
            [
                constants::RELATIONSHIP_SUBJECT => true,
                constants::RELATIONSHIP_APPRAISER => false
            ],
            [],
            true
        );
        $participants = $data['participants'];
        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);

        $this->assert_no_subject_instances_flagged();

        $this->add_appraiser_for_user($subject_id);

        $this->assert_subject_instances_flagged([$subject_id]);
    }

    public function test_delete_existing_subject_ja(): void {
        $data = $this->create_activity_with_relationships(self::$all_relationships, [], true);
        $participants = $data['participants'];
        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);
        $manager_id = array_search(constants::RELATIONSHIP_MANAGER, $participants);
        $managers_manager_id = array_search(constants::RELATIONSHIP_MANAGERS_MANAGER, $participants);
        $direct_report_id = array_search(constants::RELATIONSHIP_DIRECT_REPORT, $participants);

        /** @var job_assignment_entity $subject_ja */
        $subject_ja = job_assignment_entity::repository()
            ->where('userid', $subject_id)
            ->one(true);
        $job_assignment = job_assignment::from_entity($subject_ja);
        job_assignment::delete($job_assignment);

        $this->assert_subject_instances_flagged([
            $subject_id,
            $manager_id,
            $direct_report_id
        ]);
    }

    /**
     * @dataProvider override_config_data_provider
     * @param array $config_settings
     * @param bool $flag_creation_expected
     * @param bool $flag_closure_expected
     * @return void
     */
    public function test_observes_all_config_settings_create_new_job_assignment(
        array $config_settings,
        bool $flag_creation_expected,
        bool $flag_closure_expected
    ): void {
        if (isset($config_settings['global_creation'])) {
            set_config('perform_sync_participant_instance_creation', $config_settings['global_creation']);
        }
        if (isset($config_settings['global_closure'])) {
            set_config('perform_sync_participant_instance_closure', $config_settings['global_closure']);
        }

        $data = $this->create_activity_with_relationships(self::$all_relationships, [], true);
        $activity = $data['activity'];
        $participants = $data['participants'];
        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);

        if (isset($config_settings['activity_override'])) {
            activity_setting::create($activity, 'override_global_participation_settings', $config_settings['activity_override']);
        }
        if (isset($config_settings['activity_creation'])) {
            activity_setting::create($activity, 'sync_participant_instance_creation', $config_settings['activity_creation']);
        }
        if (isset($config_settings['activity_closure'])) {
            activity_setting::create($activity, 'sync_participant_instance_closure', $config_settings['activity_closure']);
        }

        $this->assert_no_subject_instances_flagged();

        $this->add_appraiser_for_user($subject_id);

        $expected_subject_instance_flagged = $flag_creation_expected ? [$subject_id] : [];

        $this->assert_subject_instances_flagged($expected_subject_instance_flagged);
    }

    /**
     * @dataProvider override_config_data_provider
     * @param array $config_settings
     * @param bool $flag_creation_expected
     * @param bool $flag_closure_expected
     * @return void
     */
    public function test_observes_all_config_settings_update_job_assignment(
        array $config_settings,
        bool $flag_creation_expected,
        bool $flag_closure_expected
    ): void {
        if (isset($config_settings['global_creation'])) {
            set_config('perform_sync_participant_instance_creation', $config_settings['global_creation']);
        }
        if (isset($config_settings['global_closure'])) {
            set_config('perform_sync_participant_instance_closure', $config_settings['global_closure']);
        }

        $data = $this->create_activity_with_relationships(self::$all_relationships, [], true);
        $activity = $data['activity'];

        if (isset($config_settings['activity_override'])) {
            activity_setting::create($activity, 'override_global_participation_settings', $config_settings['activity_override']);
        }
        if (isset($config_settings['activity_creation'])) {
            activity_setting::create($activity, 'sync_participant_instance_creation', $config_settings['activity_creation']);
        }
        if (isset($config_settings['activity_closure'])) {
            activity_setting::create($activity, 'sync_participant_instance_closure', $config_settings['activity_closure']);
        }

        $participants = $data['participants'];
        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);
        $manager_id = array_search(constants::RELATIONSHIP_MANAGER, $participants);
        $previous_managers_manager_id = array_search(constants::RELATIONSHIP_MANAGERS_MANAGER, $participants);
        /** @var job_assignment_entity $manager_ja */
        $manager_ja = job_assignment_entity::repository()
            ->where('userid', $manager_id)
            ->one(true);

        $new_managers_manager = self::getDataGenerator()->create_user();
        /** @var job_assignment $new_managers_manager_ja */
        $new_managers_manager_ja = job_assignment::create_default($new_managers_manager->id);

        $this->assert_no_subject_instances_flagged();

        $job_assignment = job_assignment::from_entity($manager_ja);
        $job_assignment->update([
            'managerjaid' => $new_managers_manager_ja->id
        ]);

        $expected_subject_instances = [];
        // For both cases we expect all 3 to be flagged.
        if ($flag_creation_expected || $flag_closure_expected) {
            $expected_subject_instances = [
                $subject_id,
                $manager_id,
                $previous_managers_manager_id
            ];
        }

        $this->assert_subject_instances_flagged($expected_subject_instances);
    }

    private function assert_no_subject_instances_flagged(): void {
        $this->assert_subject_instances_flagged([]);
    }

    /**
     * @param array $subject_user_ids
     * @return void
     */
    private function assert_subject_instances_flagged(array $subject_user_ids): void {
        $this->assertEqualsCanonicalizing(
            $subject_user_ids,
            subject_instance::repository()
                ->where('needs_sync', 1)
                ->get()
                ->pluck('subject_user_id')
        );
    }
}
