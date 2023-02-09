<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @package pathway_perform_rating
 */

use core_phpunit\testcase;
use mod_perform\constants;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\models\activity\element;
use mod_perform\models\activity\section;
use mod_perform\models\activity\section_element as section_element_model;
use mod_perform\testing\generator as perform_generator;
use performelement_linked_review\models\linked_review_content;
use totara_competency\testing\generator as competency_generator;
use totara_hierarchy\entity\competency as competency_entity;
use totara_job\job_assignment;
use totara_userdata\testing\generator as user_data_generator;
use totara_userdata\userdata\target_user;

abstract class perform_rating_base_testcase extends testcase {

    protected function create_data(array $configuration_data = []) {
        $this->setAdminUser();

        // As we want to check the details with deleted users we need a purge type
        $generator = user_data_generator::instance();
        $purge_type = $generator->create_purge_type(
            [
                'userstatus' => target_user::STATUS_DELETED,
                'allowmanual' => 1,
                'allowdeleted' => 1,
                'items' => 'core_user-names,core_user-username,core_user-email'
            ]
        );

        set_config('defaultdeletedpurgetypeid', $purge_type->id, 'totara_userdata');

        $another_user = $this->getDataGenerator()->create_user(['firstname' => 'Another', 'lastname' => 'User']);
        $manager_user = $this->getDataGenerator()->create_user(['firstname' => 'Manager', 'lastname' => 'User']);
        $subject_user = $this->getDataGenerator()->create_user(['firstname' => 'Subject', 'lastname' => 'User']);

        $managerja = job_assignment::create([
            'userid' => $manager_user->id,
            'idnumber' => 'ja02',
        ]);

        job_assignment::create([
            'userid' => $subject_user->id,
            'idnumber' => 'ja01',
            'managerjaid' => $managerja->id
        ]);

        $competency_generator = competency_generator::instance();
        $competency = $competency_generator->create_competency('Test competency');
        $competency_assignment = $competency_generator->assignment_generator()
            ->create_user_assignment($competency->id, $subject_user->id);

        $perform_generator = perform_generator::instance();
        $activity = $perform_generator->create_activity_in_container(['activity_name' => 'Test activity']);
        $section = $perform_generator->create_section($activity);
        $manager_section_relationship = $perform_generator->create_section_relationship(
            $section,
            ['relationship' => constants::RELATIONSHIP_MANAGER]
        );
        $subject_section_relationship = $perform_generator->create_section_relationship(
            $section,
            ['relationship' => constants::RELATIONSHIP_SUBJECT]
        );
        $rating_relationship_id = $configuration_data['rating_relationship'] ?? constants::RELATIONSHIP_MANAGER;
        $element = element::create($activity->get_context(), 'linked_review', 'title', '', json_encode([
            'content_type' => 'totara_competency',
            'content_type_settings' => [
                'enable_rating' => true,
                'rating_relationship' => $perform_generator->get_core_relationship($rating_relationship_id)->id
            ],
            'selection_relationships' => [$manager_section_relationship->core_relationship_id],
        ]));

        $section_element = $perform_generator->create_section_element($section, $element);

        $subject_instance1 = $perform_generator->create_subject_instance([
            'activity_id' => $activity->id,
            'subject_user_id' => $subject_user->id
        ]);

        $subject_instance2 = $perform_generator->create_subject_instance([
            'activity_id' => $activity->id,
            'subject_user_id' => $subject_user->id
        ]);

        $participant_section1 = $perform_generator->create_participant_instance_and_section(
            $activity,
            $manager_user,
            $subject_instance1->id,
            $section,
            $manager_section_relationship->core_relationship->id
        );

        $participant_section2 = $perform_generator->create_participant_instance_and_section(
            $activity,
            $manager_user,
            $subject_instance2->id,
            $section,
            $manager_section_relationship->core_relationship->id
        );

        $subject_participant_section1 = $perform_generator->create_participant_instance_and_section(
            $activity,
            $subject_user,
            $subject_instance1->id,
            $section,
            $subject_section_relationship->core_relationship->id
        );
        $subject_participant_section2 = $perform_generator->create_participant_instance_and_section(
            $activity,
            $subject_user,
            $subject_instance2->id,
            $section,
            $subject_section_relationship->core_relationship->id
        );

        $linked_assignment1 = linked_review_content::create(
            $competency_assignment->id, $section_element->id, $participant_section1->participant_instance_id, false
        );
        $linked_assignment2 = linked_review_content::create(
            $competency_assignment->id, $section_element->id, $participant_section2->participant_instance_id, false
        );

        $data = new class {
            public $another_user;
            public $manager_user;
            public $subject_user;
            /** @var participant_instance_entity */
            public $participant_instance1;
            /** @var participant_instance_entity */
            public $participant_instance2;
            /** @var participant_instance_entity */
            public $subject_participant_instance1;
            /** @var participant_instance_entity */
            public $subject_participant_instance2;
            /** @var competency_entity */
            public $competency;
            /** @var section_element_model */
            public $section_element;
            /** @var section */
            public $section;
            /** @var linked_review_content */
            public $linked_assignment1;
            /** @var linked_review_content */
            public $linked_assignment2;
        };

        $data->another_user = $another_user;
        $data->manager_user = $manager_user;
        $data->subject_user = $subject_user;
        $data->participant_instance1 = $participant_section1->participant_instance;
        $data->participant_instance2 = $participant_section2->participant_instance;
        $data->subject_participant_instance1 = $subject_participant_section1->participant_instance;
        $data->subject_participant_instance2 = $subject_participant_section2->participant_instance;
        $data->competency = $competency;
        $data->section_element = $section_element;
        $data->section = $section;
        $data->linked_assignment1 = $linked_assignment1;
        $data->linked_assignment2 = $linked_assignment2;

        return $data;
    }

    /**
     * Delete the user and run purge
     *
     * @param $user
     */
    protected function delete_user($user) {
        user_delete_user($user);

        $this->expectOutputRegex("/Purge finished/");
        $task = new totara_userdata\task\purge_deleted();
        $task->execute();
    }

}