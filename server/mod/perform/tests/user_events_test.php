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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

use core\entity\user;
use mod_perform\constants;
use mod_perform\entity\activity\participant_instance;
use mod_perform\entity\activity\subject_instance;
use mod_perform\models\activity\participant_source;
use mod_perform\state\participant_instance\availability_not_applicable as participant_instance_availability_not_applicable;
use mod_perform\state\participant_instance\closed as participant_instance_availability_closed;
use mod_perform\state\participant_instance\open as participant_instance_availability_open;
use mod_perform\state\participant_section\availability_not_applicable as participant_section_availability_not_applicable;
use mod_perform\state\participant_section\closed as participant_section_availability_closed;
use mod_perform\state\participant_section\open as participant_section_availability_open;
use mod_perform\state\subject_instance\closed as subject_instance_availability_closed;
use mod_perform\state\subject_instance\open as subject_instance_availability_open;
use mod_perform\state\subject_instance\pending;
use mod_perform\testing\activity_generator_configuration;
use totara_core\advanced_feature;

require_once __DIR__ . '/../../../user/lib.php';

/**
 * @group perform
 */
class mod_perform_user_events_testcase extends advanced_testcase {

    /**
     * @dataProvider deleting_or_suspending_user_closes_participant_instances_provider
     */
    public function test_deleting_or_suspending_user_closes_subject_instances(
        closure $action,
        bool $should_close = true
    ): void {
        self::setAdminUser();

        $perform_generator = \mod_perform\testing\generator::instance();

        $configuration = activity_generator_configuration::new()
            ->set_number_of_activities(3)
            ->set_number_of_users_per_user_group_type(3)
            ->set_number_of_tracks_per_activity(1)
            ->set_number_of_sections_per_activity(3)
            ->set_cohort_assignments_per_activity(1)
            ->set_number_of_elements_per_section(4)
            ->set_relationships_per_section(
                [
                    constants::RELATIONSHIP_SUBJECT,
                    constants::RELATIONSHIP_MANAGER,
                    constants::RELATIONSHIP_APPRAISER
                ]
            )
            ->enable_manager_for_each_subject_user()
            ->enable_appraiser_for_each_subject_user();

        $perform_generator->create_full_activities($configuration);

        /** @var subject_instance $subject_instance */
        $subject_instance = subject_instance::repository()
            ->order_by('id')
            ->first();

        self::assertEquals(subject_instance_availability_open::get_code(), $subject_instance->availability);

        foreach ($subject_instance->participant_instances()->get() as $participant_instance) {
            self::assertEquals(participant_instance_availability_open::get_code(), $participant_instance->availability);
            foreach ($participant_instance->participant_sections()->get() as $participant_section) {
                self::assertEquals(participant_section_availability_open::get_code(), $participant_instance->availability);
            }
        }

        // Get another control instance
        $subject_instance2 = subject_instance::repository()
            ->where('subject_user_id', '<>', $subject_instance->subject_user_id)
            ->order_by('id')
            ->first();

        self::assertEquals(subject_instance_availability_open::get_code(), $subject_instance2->availability);

        // DELETE/SUSPEND the user. This should close all their subject instances
        $action($subject_instance->subject_user);

        $subject_instance->refresh();

        if ($should_close) {
            self::assertEquals(subject_instance_availability_closed::get_code(), $subject_instance->availability);

            foreach ($subject_instance->participant_instances()->get() as $participant_instance) {
                self::assertEquals(participant_instance_availability_closed::get_code(), $participant_instance->availability);
                foreach ($participant_instance->participant_sections()->get() as $participant_section) {
                    self::assertEquals(participant_section_availability_closed::get_code(), $participant_instance->availability);
                }
            }
        } else {
            self::assertEquals(subject_instance_availability_open::get_code(), $subject_instance->availability);
            foreach ($subject_instance->participant_instances()->get() as $participant_instance) {
                self::assertEquals(participant_instance_availability_open::get_code(), $participant_instance->availability);
                foreach ($participant_instance->participant_sections()->get() as $participant_section) {
                    self::assertEquals(participant_section_availability_open::get_code(), $participant_instance->availability);
                }
            }
        }

        // The other instance should not be affected
        $subject_instance2->refresh();

        self::assertEquals(subject_instance_availability_open::get_code(), $subject_instance2->availability);
    }

    public function test_deleting_user_deletes_pending_subject_instances() {
        self::setAdminUser();

        $perform_generator = \mod_perform\testing\generator::instance();

        // Also make sure we have at least one pending subject instance
        $configuration = \mod_perform\testing\activity_generator_configuration::new()
            ->set_number_of_activities(1)
            ->set_number_of_users_per_user_group_type(3)
            ->set_number_of_tracks_per_activity(1)
            ->set_number_of_sections_per_activity(3)
            ->set_cohort_assignments_per_activity(1)
            ->set_number_of_elements_per_section(4)
            ->set_relationships_per_section(
                [
                    constants::RELATIONSHIP_SUBJECT,
                    constants::RELATIONSHIP_MANAGER,
                    constants::RELATIONSHIP_EXTERNAL
                ]
            )
            ->enable_manager_for_each_subject_user()
            ->enable_appraiser_for_each_subject_user();

        $perform_generator->create_full_activities($configuration);

        $this->assertTrue(subject_instance::repository()->where('status', pending::get_code())->exists());

        /** @var subject_instance $subject_instance */
        $subject_instance = subject_instance::repository()
            ->order_by('id')
            ->first();

        $this->assertEquals(subject_instance_availability_open::get_code(), $subject_instance->availability);

        foreach ($subject_instance->participant_instances()->get() as $participant_instance) {
            $this->assertEquals(participant_instance_availability_open::get_code(), $participant_instance->availability);
            foreach ($participant_instance->participant_sections()->get() as $participant_section) {
                $this->assertEquals(participant_section_availability_open::get_code(), $participant_instance->availability);
            }
        }

        // Get another control instance
        $subject_instance2 = subject_instance::repository()
            ->where('subject_user_id', '<>', $subject_instance->subject_user_id)
            ->order_by('id')
            ->first();

        $this->assertEquals(subject_instance_availability_open::get_code(), $subject_instance2->availability);

        // DELETE the user. This should close all their subject instances
        delete_user($subject_instance->subject_user->to_record());

        // The subject instance got deleted
        $this->assertFalse(subject_instance::repository()->where('id', $subject_instance->id)->exists());

        // The other instance should not be affected
        $subject_instance2->refresh();

        $this->assertEquals(subject_instance_availability_open::get_code(), $subject_instance2->availability);
    }

    /**
     * @dataProvider deleting_or_suspending_user_closes_participant_instances_provider
     */
    public function test_deleting_and_suspending_effect_on_participant_instance_state(
        closure $action,
        bool $should_close = true
    ): void {
        self::setAdminUser();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();

        $configuration = activity_generator_configuration::new()
            ->set_number_of_activities(3)
            ->set_number_of_users_per_user_group_type(3)
            ->set_number_of_tracks_per_activity(1)
            ->set_number_of_sections_per_activity(3)
            ->set_cohort_assignments_per_activity(1)
            ->set_number_of_elements_per_section(4)
            ->set_relationships_per_section(
                [
                    constants::RELATIONSHIP_SUBJECT,
                    constants::RELATIONSHIP_MANAGER,
                    constants::RELATIONSHIP_APPRAISER
                ]
            )
            ->enable_manager_for_each_subject_user()
            ->enable_appraiser_for_each_subject_user();

        $perform_generator->create_full_activities($configuration);

        /** @var subject_instance $subject_instance */
        $subject_instance = subject_instance::repository()
            ->order_by('id')
            ->first();

        /** @var participant_instance $participant_instance */
        $participant_instance = $subject_instance->participant_instances()
            ->where('participant_id', '<>', $subject_instance->subject_user_id)
            ->where('participant_source', participant_source::INTERNAL)
            ->order_by('id')
            ->first();

        self::assertEquals(participant_instance_availability_open::get_code(), $participant_instance->availability);

        foreach ($participant_instance->participant_sections()->get() as $participant_section) {
            self::assertEquals(participant_section_availability_open::get_code(), $participant_instance->availability);
        }

        // Get another control instance
        /** @var participant_instance $participant_instance2 */
        $participant_instance2 = $subject_instance->participant_instances()
            ->where('participant_id', '<>', $subject_instance->subject_user_id)
            ->where('participant_id', '<>', $participant_instance->participant_id)
            ->where('participant_source', participant_source::INTERNAL)
            ->order_by('id')
            ->first();

        self::assertEquals(participant_instance_availability_open::get_code(), $participant_instance2->availability);

        // DELETE/SUSPEND the user. This should close all their participant instances
        $action($participant_instance->participant_user);

        $participant_instances = participant_instance::repository()
            ->where('participant_id', $participant_instance->participant_id)
            ->where('participant_source', participant_source::INTERNAL)
            ->get();

        foreach ($participant_instances as $participant_instance) {
            if ($should_close) {
                self::assertEquals(participant_instance_availability_closed::get_code(), $participant_instance->availability);
            } else {
                self::assertNotEquals(participant_instance_availability_closed::get_code(), $participant_instance->availability);
            }

        }

        // The other instance should not be affected
        $participant_instance2->refresh();

        if ($should_close) {
            self::assertEquals(participant_instance_availability_open::get_code(), $participant_instance2->availability);
        } else {
            self::assertNotEquals(participant_instance_availability_closed::get_code(), $participant_instance->availability);
        }
    }

    public function deleting_or_suspending_user_closes_participant_instances_provider(): array {
        return [
            'Deleting closes instances' => [
                static function (user $user) {
                    delete_user($user->to_record());
                }
            ],
            'Suspending with "close_suspended_user_instances" closes instances' => [
                static function (user $user) {
                    set_config('perform_hide_suspended_users', 0);
                    set_config('perform_close_suspended_user_instances', 1);
                    user_suspend_user($user->id);
                }
            ],
            'Suspending with "hide_suspended_users" closes instances' => [
                static function (user $user) {
                    set_config('perform_hide_suspended_users', 1);
                    set_config('perform_close_suspended_user_instances', 0);
                    user_suspend_user($user->id);
                }
            ],
            'Suspending without activating any of the 2 settings does nothing' => [
                static function (user $user) {
                    set_config('perform_hide_suspended_users', 0);
                    set_config('perform_close_suspended_user_instances', 0);
                    user_suspend_user($user->id);
                },
                false
            ],
        ];
    }

    /**
     * @dataProvider deleting_or_suspending_user_closes_participant_instances_provider
     */
    public function test_deleting_or_suspending_user_closes_only_open_participant_instances(
        closure $action,
        bool $should_close = true
    ): void {
        self::setAdminUser();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();

        $configuration = activity_generator_configuration::new()
            ->set_number_of_activities(3)
            ->set_number_of_users_per_user_group_type(3)
            ->set_number_of_tracks_per_activity(1)
            ->set_number_of_sections_per_activity(3)
            ->set_cohort_assignments_per_activity(1)
            ->set_number_of_elements_per_section(4)
            ->set_relationships_per_section(
                [
                    constants::RELATIONSHIP_SUBJECT,
                    constants::RELATIONSHIP_MANAGER,
                    constants::RELATIONSHIP_APPRAISER
                ]
            )
            ->set_view_only_relationships([constants::RELATIONSHIP_APPRAISER])
            ->enable_manager_for_each_subject_user()
            ->enable_appraiser_for_each_subject_user();

        $perform_generator->create_full_activities($configuration);

        /** @var subject_instance $subject_instance */
        $subject_instance = subject_instance::repository()
            ->order_by('id')
            ->first();

        /** @var participant_instance $participant_instance */
        $participant_instance = $subject_instance->participant_instances()
            ->where('participant_id', '<>', $subject_instance->subject_user_id)
            ->where('participant_source', participant_source::INTERNAL)
            ->where('core_relationship_id', $perform_generator->get_core_relationship(constants::RELATIONSHIP_APPRAISER)->id)
            ->order_by('id')
            ->first();

        self::assertEquals(participant_instance_availability_not_applicable::get_code(), $participant_instance->availability);

        foreach ($participant_instance->participant_sections()->get() as $participant_section) {
            self::assertEquals(participant_section_availability_not_applicable::get_code(), $participant_instance->availability);
        }

        // Get another control instance
        /** @var participant_instance $participant_instance2 */
        $participant_instance2 = $subject_instance->participant_instances()
            ->where('participant_id', '<>', $subject_instance->subject_user_id)
            ->where('participant_id', '<>', $participant_instance->participant_id)
            ->where('participant_source', participant_source::INTERNAL)
            ->order_by('id')
            ->first();

        self::assertEquals(participant_instance_availability_open::get_code(), $participant_instance2->availability);

        // DELETE/SUSPEND the user. As it's a view only user the participant instance should still be in the same state
        $action($participant_instance->participant_user);

        $participant_instances = participant_instance::repository()
            ->where('participant_id', $participant_instance->participant_id)
            ->where('participant_source', participant_source::INTERNAL)
            ->get();


        foreach ($participant_instances as $participant_instance) {
            self::assertEquals(
                participant_instance_availability_not_applicable::get_code(), $participant_instance->availability
            );
        }

        // The other instance should not be affected
        $participant_instance2->refresh();

        self::assertEquals(participant_instance_availability_open::get_code(), $participant_instance2->availability);
    }

}