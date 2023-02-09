<?php

use core\collection;
use core\entity\user;
use mod_perform\constants;
use mod_perform\data_providers\response\participant_section;
use mod_perform\entity\activity\participant_instance;
use mod_perform\entity\activity\participant_section as participant_section_entity;

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
 */

use mod_perform\entity\activity\subject_instance;
use mod_perform\models\activity\participant_source;
use mod_perform\models\activity\subject_instance as subject_instance_model;
use mod_perform\models\response\participant_section as participant_section_model;
use mod_perform\state\participant_section\not_started;
use mod_perform\testing\activity_generator_configuration;
use mod_perform\testing\generator as perform_generator;
use totara_core\relationship\relationship as core_relationship;

/**
 * @covers \mod_perform\entity\activity\participant_section_repository
 * @group perform
*/
class mod_perform_participant_section_repository_test extends advanced_testcase {

    public function test_cant_get_another_users_section_and_responses(): void {
        $this->setAdminUser();

        $subject = $this->getDataGenerator()->create_user();
        $another_user = $this->getDataGenerator()->create_user();

        /** @var perform_generator $generator */
        $generator = perform_generator::instance();

        $generator->create_subject_instance([
            'subject_is_participating' => true,
            'subject_user_id' => $subject->id,
            'other_participant_id' => user::logged_in()->id,
            'include_questions' => true,
        ]);


        $participant_section = participant_section_entity::repository()
                ->with(['section_elements', 'participant_instance'])
                ->get()
                ->first();
        $fetched_participant_section = (new participant_section($another_user->id, participant_source::INTERNAL))->find_by_section_id($participant_section->id);

        $this->assertNull($fetched_participant_section);
    }

    public function test_hide_incomplete_with_not_submitted_sections(): void {
        set_config('perform_hide_incomplete_responses_closed_instances', 1);

        [$subject_user_id, $subject_participant_sections] = $this->create_activity_for_hide_incomplete();

        $participant_sections = participant_section_entity::repository()
            ->as('ps')
            ->hide_incomplete_when_configured($subject_user_id, participant_source::INTERNAL)
            ->get();
        $this->assertCount(4, $participant_sections);

        // Close the subject instance
        /** @var subject_instance $subject_instance_entity */
        $subject_instance_entity = subject_instance::repository()->one(true);
        $subject_instance_model = subject_instance_model::load_by_entity($subject_instance_entity);
        $subject_instance_model->manually_close();

        $participant_sections = participant_section_entity::repository()
            ->as('ps')
            ->hide_incomplete_when_configured($subject_user_id, participant_source::INTERNAL)
            ->get();
        $this->assertCount(2, $participant_sections);
        $this->assertEqualsCanonicalizing($subject_participant_sections->pluck('id'), $participant_sections->pluck('id'));

        // Switch the setting off and we expect all the sections to be returned again.
        set_config('perform_hide_incomplete_responses_closed_instances', 0);
        $participant_sections = participant_section_entity::repository()
            ->as('ps')
            ->hide_incomplete_when_configured($subject_user_id, participant_source::INTERNAL)
            ->get();
        $this->assertCount(4, $participant_sections);
    }

    public function test_hide_incomplete_with_complete_sections(): void {
        set_config('perform_hide_incomplete_responses_closed_instances', 1);

        [
            $subject_user_id,
            $subject_participant_sections,
            $manager_user_id,
            $manager_participant_sections
        ] = $this->create_activity_for_hide_incomplete();

        $first_subject_participant_section = $subject_participant_sections->first();
        $first_manager_participant_section = $manager_participant_sections->first();

        // Complete one section each.
        participant_section_model::load_by_entity($first_subject_participant_section)->complete();
        participant_section_model::load_by_entity($first_manager_participant_section)->complete();

        $result_sections = participant_section_entity::repository()
            ->as('ps')
            ->hide_incomplete_when_configured($subject_user_id, participant_source::INTERNAL)
            ->get();
        $this->assertCount(4, $result_sections);

        // Close the subject instance
        /** @var subject_instance $subject_instance_entity */
        $subject_instance_entity = subject_instance::repository()->one(true);
        $subject_instance_model = subject_instance_model::load_by_entity($subject_instance_entity);
        $subject_instance_model->manually_close();

        // Only the manager's "not submitted" instance should be hidden when the subject is viewing.
        $result_sections = participant_section_entity::repository()
            ->as('ps')
            ->hide_incomplete_when_configured($subject_user_id, participant_source::INTERNAL)
            ->get();
        $this->assertEqualsCanonicalizing(
            array_merge($subject_participant_sections->pluck('id'), [$first_manager_participant_section->id]),
            $result_sections->pluck('id')
        );

        // Only the subject's "not submitted" instance should be hidden when the manager is viewing.
        $result_sections = participant_section_entity::repository()
            ->as('ps')
            ->hide_incomplete_when_configured($manager_user_id, participant_source::INTERNAL)
            ->get();
        $this->assertEqualsCanonicalizing(
            array_merge($manager_participant_sections->pluck('id'), [$first_subject_participant_section->id]),
            $result_sections->pluck('id')
        );

        // Switch the setting off and we expect all the sections to be returned again.
        set_config('perform_hide_incomplete_responses_closed_instances', 0);
        $result_sections = participant_section_entity::repository()
            ->as('ps')
            ->hide_incomplete_when_configured($manager_user_id, participant_source::INTERNAL)
            ->get();
        $this->assertCount(4, $result_sections);
    }

    private function create_activity_for_hide_incomplete(): array {
        self::setAdminUser();
        $generator = perform_generator::instance();

        // Create an activity with two sections and subject/manager per section.
        $config = activity_generator_configuration::new()
            ->set_number_of_activities(1)
            ->set_number_of_sections_per_activity(2)
            ->set_number_of_users_per_user_group_type(1)
            ->set_relationships_per_section([constants::RELATIONSHIP_SUBJECT, constants::RELATIONSHIP_MANAGER])
            ->enable_manager_for_each_subject_user();
        $activity = $generator->create_full_activities($config)->first();

        // Make sure the set-up is as intended.
        $this->assertCount(2, participant_instance::repository()->get());
        $this->assertCount(4, participant_section_entity::repository()->get());
        $this->assertCount(4,
            participant_section_entity::repository()
                ->where('progress', not_started::get_code())
                ->get()
        );

        /** @var participant_instance $subject_participant_instance */
        $subject_participant_instance = participant_instance::repository()
            ->where('core_relationship_id', core_relationship::load_by_idnumber('subject')->id)
            ->one(true);
        $subject_user_id = $subject_participant_instance->participant_id;
        $subject_participant_sections = participant_section_entity::repository()
            ->where('participant_instance_id', $subject_participant_instance->id)
            ->get();
        $this->assertCount(2, $subject_participant_sections);

        /** @var participant_instance $manager_participant_instance */
        $manager_participant_instance = participant_instance::repository()
            ->where('core_relationship_id', core_relationship::load_by_idnumber('manager')->id)
            ->one(true);
        $manager_user_id = $manager_participant_instance->participant_id;
        $manager_participant_sections = participant_section_entity::repository()
            ->where('participant_instance_id', $manager_participant_instance->id)
            ->get();
        $this->assertCount(2, $manager_participant_sections);

        return [
            $subject_user_id,
            $subject_participant_sections,
            $manager_user_id,
            $manager_participant_sections
        ];
    }
}