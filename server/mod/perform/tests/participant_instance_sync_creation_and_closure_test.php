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
use mod_perform\entity\activity\participant_instance;
use mod_perform\entity\activity\subject_instance;
use mod_perform\models\activity\activity_setting;
use mod_perform\models\activity\participant_instance as participant_instance_model;
use mod_perform\models\activity\subject_instance as subject_instance_model;
use mod_perform\state\participant_instance\complete;
use mod_perform\state\participant_instance\not_started;
use mod_perform\state\participant_instance\not_submitted;
use mod_perform\state\participant_instance\progress_not_applicable;
use mod_perform\state\participant_section\in_progress;
use mod_perform\state\subject_instance\complete as subject_instance_complete;
use mod_perform\state\subject_instance\in_progress as subject_instance_in_progress;
use mod_perform\state\subject_instance\not_started as subject_instance_not_started;
use mod_perform\task\sync_participant_instances_task;
use totara_job\job_assignment;

require_once(__DIR__ . '/participant_instance_sync_testcase.php');

/**
 * Class participant_instance_creation_service_test
 *
 * @group perform
 */
class mod_perform_participant_instance_sync_creation_and_closure_testcase extends mod_perform_participant_instance_sync_testcase {

    public function test_added_when_new_user_enters_relationships(): void {
        set_config('perform_sync_participant_instance_creation', 1);

        $data = $this->create_activity_with_relationships();
        $participants = $data['participants'];
        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);

        // Give the subject user another manager
        [$second_manager, ] = $this->add_manager_for_user($subject_id);

        $this->flag_all();

        (new sync_participant_instances_task())->execute();

        // Verify that a participant instance for the manager has been created.
        $participants[$second_manager->id] = constants::RELATIONSHIP_MANAGER;
        $this->assert_open_participant_instances($subject_id, $participants);
        $this->assert_participant_instances_count($subject_id, 6);
        $this->assert_all_unflagged();
    }

    public function test_added_when_multiple_users_enter_relationships(): void {
        set_config('perform_sync_participant_instance_creation', 1);

        $data = $this->create_activity_with_relationships();
        $participants = $data['participants'];
        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);

        // Add users for all the roles
        [$second_direct_report] = $this->add_direct_report_for_user($subject_id);
        [$second_manager, $second_manager_ja] = $this->add_manager_for_user($subject_id);
        [$second_managers_manager] = $this->add_manager_for_job_assignment($second_manager_ja);
        [$second_appraiser] = $this->add_appraiser_for_user($subject_id);

        $this->flag_all();

        (new sync_participant_instances_task())->execute();

        // Verify that participant instances for all the additional participants have been created.
        $participants[$second_manager->id] = constants::RELATIONSHIP_MANAGER;
        $participants[$second_managers_manager->id] = constants::RELATIONSHIP_MANAGERS_MANAGER;
        $participants[$second_direct_report->id] = constants::RELATIONSHIP_DIRECT_REPORT;
        $participants[$second_appraiser->id] = constants::RELATIONSHIP_APPRAISER;
        $this->assert_open_participant_instances($subject_id, $participants);
        $this->assert_participant_instances_count($subject_id, 9);
        $this->assert_all_unflagged();
    }

    public function test_added_when_multiple_users_enter_same_relationship(): void {
        set_config('perform_sync_participant_instance_creation', 1);

        $data = $this->create_activity_with_relationships();
        $participants = $data['participants'];
        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);

        // Add two direct reports
        [$second_direct_report] = $this->add_direct_report_for_user($subject_id);
        [$third_direct_report] = $this->add_direct_report_for_user($subject_id);

        $this->flag_all();

        (new sync_participant_instances_task())->execute();

        // Verify that participant instances for all the additional participants have been created.
        $participants[$second_direct_report->id] = constants::RELATIONSHIP_DIRECT_REPORT;
        $participants[$third_direct_report->id] = constants::RELATIONSHIP_DIRECT_REPORT;
        $this->assert_open_participant_instances($subject_id, $participants);
        $this->assert_participant_instances_count($subject_id, 7);
        $this->assert_all_unflagged();
    }

    public function test_added_when_same_user_enters_multiple_relationships(): void {
        set_config('perform_sync_participant_instance_creation', 1);

        $data = $this->create_activity_with_relationships();
        $participants = $data['participants'];

        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);

        // Add the same user as direct report and appraiser.
        [$second_direct_report] = $this->add_direct_report_for_user($subject_id);
        job_assignment::create_default($subject_id, [
            'appraiserid' => $second_direct_report->id
        ]);

        $this->flag_all();

        (new sync_participant_instances_task())->execute();

        $participants[$second_direct_report->id] = [
            constants::RELATIONSHIP_DIRECT_REPORT,
            constants::RELATIONSHIP_APPRAISER,
        ];
        $this->assert_open_participant_instances($subject_id, $participants);
        $this->assert_participant_instances_count($subject_id, 7);
        $this->assert_all_unflagged();
    }

    public function test_reopened(): void {
        set_config('perform_sync_participant_instance_creation', 1);

        $data = $this->create_activity_with_relationships();
        $participants = $data['participants'];
        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);
        $manager_id = array_search(constants::RELATIONSHIP_MANAGER, $participants);

        /** @var participant_instance $participant_instance_entity */
        $participant_instance_entity = participant_instance::repository()
            ->where('participant_id', $manager_id)
            ->one(true);

        $participant_instance = participant_instance_model::load_by_entity($participant_instance_entity);
        $participant_instance->manually_close();

        $this->assert_closed_participant_instances($subject_id, [$manager_id => constants::RELATIONSHIP_MANAGER]);

        $this->flag_all();

        (new sync_participant_instances_task())->execute();

        $this->assert_closed_participant_instances($subject_id, []);
        $this->assert_open_participant_instances($subject_id, $participants);
        $this->assert_participant_instances_count($subject_id, 5);
        $this->assert_all_unflagged();
    }

    public function test_not_reopened_when_complete(): void {
        set_config('perform_sync_participant_instance_creation', 1);

        $data = $this->create_activity_with_relationships();
        $participants = $data['participants'];
        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);
        $manager_id = array_search(constants::RELATIONSHIP_MANAGER, $participants);

        /** @var participant_instance $participant_instance_entity */
        $participant_instance_entity = participant_instance::repository()
            ->where('participant_id', $manager_id)
            ->one(true);

        $participant_instance = participant_instance_model::load_by_entity($participant_instance_entity);
        $participant_instance->manually_close();
        $participant_instance_entity->progress = complete::get_code();
        $participant_instance_entity->save();

        $this->assert_closed_participant_instances($subject_id, [$manager_id => constants::RELATIONSHIP_MANAGER]);

        $this->flag_all();

        (new sync_participant_instances_task())->execute();

        $this->assert_closed_participant_instances($subject_id, [$manager_id => constants::RELATIONSHIP_MANAGER]);
        unset($participants[$manager_id]);
        $this->assert_open_participant_instances($subject_id, $participants);
        $this->assert_participant_instances_count($subject_id, 5);
        $this->assert_all_unflagged();
    }

    public function test_added_and_reopened_multiple_mixed(): void {
        set_config('perform_sync_participant_instance_creation', 1);

        $data = $this->create_activity_with_relationships();
        $participants = $data['participants'];
        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);
        $manager_id = array_search(constants::RELATIONSHIP_MANAGER, $participants);
        $managers_manager_id = array_search(constants::RELATIONSHIP_MANAGERS_MANAGER, $participants);

        // Close two participant instances.
        participant_instance::repository()
            ->where_in('participant_id', [$manager_id, $managers_manager_id])
            ->get()
            ->map(function (participant_instance $participant_instance_entity) {
                $participant_instance = participant_instance_model::load_by_entity($participant_instance_entity);
                $participant_instance->manually_close();
            });

        // Let two more users enter the relationships.
        [$second_manager] = $this->add_manager_for_user($subject_id);
        [$second_direct_report] = $this->add_direct_report_for_user($subject_id);

        $this->assert_closed_participant_instances($subject_id, [
            $manager_id => constants::RELATIONSHIP_MANAGER,
            $managers_manager_id => constants::RELATIONSHIP_MANAGERS_MANAGER,
        ]);

        $this->flag_all();

        (new sync_participant_instances_task())->execute();

        // Everything should be open.
        $this->assert_closed_participant_instances($subject_id, []);
        $participants[$second_direct_report->id] = constants::RELATIONSHIP_DIRECT_REPORT;
        $participants[$second_manager->id] = constants::RELATIONSHIP_MANAGER;
        $this->assert_open_participant_instances($subject_id, $participants);
        $this->assert_participant_instances_count($subject_id, 7);
        $this->assert_all_unflagged();
    }

    public function test_view_only_participant(): void {
        set_config('perform_sync_participant_instance_creation', 1);

        $data = $this->create_activity_with_relationships(null, [
            constants::RELATIONSHIP_APPRAISER,
            constants::RELATIONSHIP_DIRECT_REPORT
        ]);
        $participants = $data['participants'];
        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);
        $appraiser_id = array_search(constants::RELATIONSHIP_APPRAISER, $participants);
        $direct_report_id = array_search(constants::RELATIONSHIP_DIRECT_REPORT, $participants);

        [$second_direct_report] = $this->add_direct_report_for_user($subject_id);
        [$second_appraiser] = $this->add_appraiser_for_user($subject_id);

        $this->flag_all();

        (new sync_participant_instances_task())->execute();

        $all_view_only_participants = [
            $appraiser_id => constants::RELATIONSHIP_APPRAISER,
            $second_appraiser->id => constants::RELATIONSHIP_APPRAISER,
            $direct_report_id => constants::RELATIONSHIP_DIRECT_REPORT,
            $second_direct_report->id => constants::RELATIONSHIP_DIRECT_REPORT,
        ];

        $this->assert_view_only_participant_instances($subject_id, $all_view_only_participants);
        $this->assert_open_participant_instances($subject_id, array_diff($participants, $all_view_only_participants));
        $this->assert_participant_instances_count($subject_id, 7);
        $this->assert_all_unflagged();
    }

    public function test_unflagged_after_sync(): void {
        set_config('perform_sync_participant_instance_creation', 1);

        $this->create_activity_with_relationships();

        $this->flag_all();

        (new sync_participant_instances_task())->execute();

        $this->assert_all_unflagged();
    }

    public function test_not_added_when_not_flagged(): void {
        set_config('perform_sync_participant_instance_creation', 1);

        $data = $this->create_activity_with_relationships();
        $participants = $data['participants'];
        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);

        // Give the subject user another manager
        $this->add_manager_for_user($subject_id);

        $this->unflag_all();

        (new sync_participant_instances_task())->execute();

        // No change expected because the creation setting was off.
        $this->assert_open_participant_instances($subject_id, $participants);
        $this->assert_participant_instances_count($subject_id, 5);
        $this->assert_all_unflagged();
    }

    public function test_not_added_when_setting_is_off(): void {
        // The creation setting should be off by default.
        $this->assertEquals(0, get_config(null, 'perform_sync_participant_instance_creation'));

        $data = $this->create_activity_with_relationships();
        $participants = $data['participants'];
        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);

        // Give the subject user another manager
        $this->add_manager_for_user($subject_id);

        $this->flag_all();

        (new sync_participant_instances_task())->execute();

        // No change expected because the creation setting was off.
        $this->assert_open_participant_instances($subject_id, $participants);
        $this->assert_participant_instances_count($subject_id, 5);
        $this->assert_all_unflagged();
    }

    public function test_added_as_first_participant_for_a_relationship(): void {
        set_config('perform_sync_participant_instance_creation', 1);

        $data = $this->create_activity_with_relationships([
            constants::RELATIONSHIP_SUBJECT => true,
            constants::RELATIONSHIP_MANAGER => false,
        ]);
        $participants = $data['participants'];
        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);

        // Give the subject user a manager
        [$first_ever_manager, ] = $this->add_manager_for_user($subject_id);

        $this->flag_all();

        (new sync_participant_instances_task())->execute();

        // Verify that a participant instance for the manager has been created.
        $participants[$first_ever_manager->id] = constants::RELATIONSHIP_MANAGER;
        $this->assert_open_participant_instances($subject_id, $participants);
        $this->assert_participant_instances_count($subject_id, 2);
        $this->assert_all_unflagged();
    }

    public function test_not_synced_when_subject_instance_is_closed(): void {
        set_config('perform_sync_participant_instance_creation', 1);

        $data = $this->create_activity_with_relationships();
        $participants = $data['participants'];
        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);

        // Give the subject user another manager
        $this->add_manager_for_user($subject_id);

        // Close the subject instance.
        /** @var subject_instance $subject_instance_entity */
        $subject_instance_entity = subject_instance::repository()
            ->where('subject_user_id', $subject_id)
            ->one(true);
        (subject_instance_model::load_by_entity($subject_instance_entity))->manually_close();

        $this->assert_closed_participant_instances($subject_id, $participants);
        $this->assert_participant_instances_count($subject_id, 5);

        $this->flag_all();

        (new sync_participant_instances_task())->execute();

        // No change expected because the subject instance was closed.
        $this->assert_closed_participant_instances($subject_id, $participants);
        $this->assert_participant_instances_count($subject_id, 5);
    }

    public function test_closed_when_user_leaves_relationship(): void {
        set_config('perform_sync_participant_instance_closure', 1);

        $data = $this->create_activity_with_relationships();
        $participants = $data['participants'];

        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);
        $old_appraiser_id = array_search(constants::RELATIONSHIP_APPRAISER, $participants);

        // Remove the appraiser for the subject user.
        $this->remove_appraiser($subject_id);

        $this->flag_all();

        (new sync_participant_instances_task())->execute();

        // Verify the participant instance for the old appraiser has been closed.
        unset($participants[$old_appraiser_id]);
        $this->assert_open_participant_instances($subject_id, $participants);
        $this->assert_closed_participant_instances($subject_id, [$old_appraiser_id => constants::RELATIONSHIP_APPRAISER]);
        $this->assert_participant_instances_count($subject_id, 5);
        $this->assert_all_unflagged();
    }

    public function test_closed_when_multiple_users_leave_relationship(): void {
        set_config('perform_sync_participant_instance_closure', 1);

        $data = $this->create_activity_with_relationships();
        $participants = $data['participants'];

        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);
        $old_manager_id = array_search(constants::RELATIONSHIP_MANAGER, $participants);
        $old_managers_manager_id = array_search(constants::RELATIONSHIP_MANAGERS_MANAGER, $participants);
        $direct_report_id = array_search(constants::RELATIONSHIP_DIRECT_REPORT, $participants);

        // Remove the manager for the subject user.
        $this->remove_manager($subject_id);
        // Remove the direct report for the subject user.
        $this->remove_manager($direct_report_id);

        $this->flag_all();

        (new sync_participant_instances_task())->execute();

        // Removing the subject's manager also removed the link to the manager's manager,
        // so both participant instances should be closed.
        unset(
            $participants[$old_manager_id],
            $participants[$old_managers_manager_id],
            $participants[$direct_report_id],
        );
        $this->assert_open_participant_instances($subject_id, $participants);
        $this->assert_closed_participant_instances($subject_id, [
            $old_manager_id => constants::RELATIONSHIP_MANAGER,
            $old_managers_manager_id => constants::RELATIONSHIP_MANAGERS_MANAGER,
            $direct_report_id => constants::RELATIONSHIP_DIRECT_REPORT,
        ]);
        $this->assert_participant_instances_count($subject_id, 5);
        $this->assert_all_unflagged();
    }

    public function test_not_closed_when_not_flagged(): void {
        set_config('perform_sync_participant_instance_closure', 1);

        $data = $this->create_activity_with_relationships();
        $participants = $data['participants'];

        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);
        $old_appraiser_id = array_search(constants::RELATIONSHIP_APPRAISER, $participants);

        // Remove the appraiser for the subject user.
        $this->remove_appraiser($subject_id);

        $this->unflag_all();

        (new sync_participant_instances_task())->execute();

        // No change expected because subject instance was not flagged.
        $this->assert_open_participant_instances($subject_id, $participants);
        $this->assert_closed_participant_instances($subject_id, []);
        $this->assert_participant_instances_count($subject_id, 5);
        $this->assert_all_unflagged();
    }

    public function test_not_closed_when_setting_is_off(): void {
        // The closure setting should be off by default.
        $this->assertEquals(0, get_config(null, 'perform_sync_participant_instance_closure'));

        $data = $this->create_activity_with_relationships();
        $participants = $data['participants'];
        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);

        $this->flag_all();

        (new sync_participant_instances_task())->execute();

        // No change expected because the closure setting was off.
        $this->assert_open_participant_instances($subject_id, $participants);
        $this->assert_closed_participant_instances($subject_id, []);
        $this->assert_participant_instances_count($subject_id, 5);
        $this->assert_all_unflagged();
    }

    public function progress_status_data_provider(): array {
        return [
            [not_started::get_code(), true],
            [in_progress::get_code(), false],
            [complete::get_code(), false],
            [not_submitted::get_code(), false], // implies that the participant instance is already closed
            [progress_not_applicable::get_code(), false], // means view-only
        ];
    }

    /**
     * @dataProvider progress_status_data_provider
     * @param int $progress_status
     * @param bool $should_close
     * @return void
     */
    public function test_closed_depending_on_progress_status(int $progress_status, bool $should_close): void {
        set_config('perform_sync_participant_instance_closure', 1);

        $data = $this->create_activity_with_relationships();
        $participants = $data['participants'];

        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);
        $old_appraiser_id = array_search(constants::RELATIONSHIP_APPRAISER, $participants);

        /** @var participant_instance $participant_instance_entity */
        $participant_instance_entity = participant_instance::repository()
            ->where('participant_id', $old_appraiser_id)
            ->one(true);
        $participant_instance_entity->progress = $progress_status;
        $participant_instance_entity->save();

        // Remove the appraiser for the subject user.
        $this->remove_appraiser($subject_id);

        $this->flag_all();

        (new sync_participant_instances_task())->execute();

        $closed_participant_instances = [];
        if ($should_close) {
            unset($participants[$old_appraiser_id]);
            $closed_participant_instances[$old_appraiser_id] = constants::RELATIONSHIP_APPRAISER;
        }
        $this->assert_open_participant_instances($subject_id, $participants);
        $this->assert_closed_participant_instances($subject_id, $closed_participant_instances);
        $this->assert_participant_instances_count($subject_id, 5);
        $this->assert_all_unflagged();
    }

    public function test_combined_replace_relationship(): void {
        set_config('perform_sync_participant_instance_creation', 1);
        set_config('perform_sync_participant_instance_closure', 1);

        $data = $this->create_activity_with_relationships();
        $participants = $data['participants'];

        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);
        $old_appraiser_id = array_search(constants::RELATIONSHIP_APPRAISER, $participants);

        $new_appraiser = self::getDataGenerator()->create_user();
        $this->replace_appraiser($subject_id, $new_appraiser->id);

        $this->flag_all();

        (new sync_participant_instances_task())->execute();

        unset($participants[$old_appraiser_id]);
        $participants[$new_appraiser->id] = constants::RELATIONSHIP_APPRAISER;
        $this->assert_open_participant_instances($subject_id, $participants);
        $this->assert_closed_participant_instances($subject_id, [$old_appraiser_id => constants::RELATIONSHIP_APPRAISER]);
        $this->assert_participant_instances_count($subject_id, 6);
        $this->assert_all_unflagged();
    }

    public function test_closing_updates_progress_status(): void {
        set_config('perform_sync_participant_instance_closure', 1);

        $data = $this->create_activity_with_relationships(
            [
                constants::RELATIONSHIP_SUBJECT => true,
                constants::RELATIONSHIP_APPRAISER => true,
                constants::RELATIONSHIP_MANAGER => true,
            ],
            [
                // Make subject view-only
                constants::RELATIONSHIP_SUBJECT
            ]
        );
        $participants = $data['participants'];
        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);
        $appraiser_id = array_search(constants::RELATIONSHIP_APPRAISER, $participants);
        $manager_id = array_search(constants::RELATIONSHIP_MANAGER, $participants);

        $this->assertEquals(subject_instance_not_started::get_code(), $this->get_subject_instance($subject_id)->progress);

        // Complete the participant instance for the appraiser.
        $this->complete_participant_instance($appraiser_id);

        $this->assertEquals(subject_instance_in_progress::get_code(), $this->get_subject_instance($subject_id)->progress);

        // Remove the manager and trigger sync.
        $this->remove_manager($subject_id);

        $this->flag_all();

        (new sync_participant_instances_task())->execute();

        // Removing the manager must lead to updating the subject instance progress to 'complete'.
        $this->assertEquals(subject_instance_complete::get_code(), $this->get_subject_instance($subject_id)->progress);

        $this->assert_view_only_participant_instances($subject_id, [$subject_id => constants::RELATIONSHIP_SUBJECT]);
        $this->assert_open_participant_instances($subject_id, [$appraiser_id => constants::RELATIONSHIP_APPRAISER]);
        $this->assert_closed_participant_instances($subject_id, [$manager_id => constants::RELATIONSHIP_MANAGER]);
        $this->assert_participant_instances_count($subject_id, 3);
        $this->assert_all_unflagged();
    }

    public function test_creates_and_closes_in_correct_order(): void {
        set_config('perform_sync_participant_instance_creation', 1);
        set_config('perform_sync_participant_instance_closure', 1);

        $data = $this->create_activity_with_relationships(
            [
                constants::RELATIONSHIP_SUBJECT => true,
                constants::RELATIONSHIP_APPRAISER => true,
                constants::RELATIONSHIP_MANAGER => true,
            ],
            [
                // Make subject view-only
                constants::RELATIONSHIP_SUBJECT
            ]
        );
        $participants = $data['participants'];
        $subject_id = array_search(constants::RELATIONSHIP_SUBJECT, $participants);
        $appraiser_id = array_search(constants::RELATIONSHIP_APPRAISER, $participants);
        $manager_id = array_search(constants::RELATIONSHIP_MANAGER, $participants);

        // Complete the participant instance for the appraiser.
        $this->complete_participant_instance($appraiser_id);
        $this->assertEquals(subject_instance_in_progress::get_code(), $this->get_subject_instance($subject_id)->progress);

        // Replace the manager and trigger sync.
        $new_manager = self::getDataGenerator()->create_user();
        $this->replace_manager($subject_id, $new_manager->id);

        $this->flag_all();

        (new sync_participant_instances_task())->execute();

        // Replacing the manager should leave subject instance 'in progress'.
        $this->assertEquals(subject_instance_in_progress::get_code(), $this->get_subject_instance($subject_id)->progress);

        $this->assert_view_only_participant_instances($subject_id, [$subject_id => constants::RELATIONSHIP_SUBJECT]);
        $this->assert_open_participant_instances($subject_id, [
            $appraiser_id => constants::RELATIONSHIP_APPRAISER,
            $new_manager->id => constants::RELATIONSHIP_MANAGER,
        ]);
        $this->assert_closed_participant_instances($subject_id, [$manager_id => constants::RELATIONSHIP_MANAGER]);
        $this->assert_participant_instances_count($subject_id, 4);
        $this->assert_all_unflagged();
    }

    /**
     * @dataProvider override_config_data_provider
     * @param array $config_settings
     * @param bool $creation_expected
     * @param bool $closure_expected
     * @return void
     */
    public function test_observes_all_config_settings(array $config_settings, bool $creation_expected, bool $closure_expected): void {
        if (isset($config_settings['global_creation'])) {
            set_config('perform_sync_participant_instance_creation', $config_settings['global_creation']);
        }
        if (isset($config_settings['global_closure'])) {
            set_config('perform_sync_participant_instance_closure', $config_settings['global_closure']);
        }

        $data = $this->create_activity_with_relationships(
            [
                constants::RELATIONSHIP_SUBJECT => true,
                constants::RELATIONSHIP_APPRAISER => true,
            ]
        );
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
        $old_appraiser_id = array_search(constants::RELATIONSHIP_APPRAISER, $participants);

        $new_appraiser = self::getDataGenerator()->create_user();
        $this->replace_appraiser($subject_id, $new_appraiser->id);

        $this->flag_all();

        (new sync_participant_instances_task())->execute();

        $expected_open_participant_instances = [
            $subject_id => constants::RELATIONSHIP_SUBJECT,
            $old_appraiser_id => constants::RELATIONSHIP_APPRAISER,
        ];
        $expected_closed_participant_instances = [];
        $expected_participant_instances_count = 2;

        if ($closure_expected) {
            $expected_open_participant_instances = [
                $subject_id => constants::RELATIONSHIP_SUBJECT,
            ];
            $expected_closed_participant_instances = [
                $old_appraiser_id => constants::RELATIONSHIP_APPRAISER
            ];
        }
        if ($creation_expected) {
            $expected_open_participant_instances[$new_appraiser->id] = constants::RELATIONSHIP_APPRAISER;
            $expected_participant_instances_count ++;
        }
        $this->assert_open_participant_instances($subject_id, $expected_open_participant_instances);
        $this->assert_closed_participant_instances($subject_id, $expected_closed_participant_instances);
        $this->assert_participant_instances_count($subject_id, $expected_participant_instances_count);
        $this->assert_all_unflagged();
    }

    private function flag_all(): void {
        subject_instance::repository()->update(['needs_sync' => 1]);
    }

    private function unflag_all(): void {
        subject_instance::repository()->update(['needs_sync' => 0]);
    }

    private function assert_all_unflagged(): void {
        $this->assertFalse(
            subject_instance::repository()
                ->where('needs_sync', 1)
                ->exists()
        );
    }
}
