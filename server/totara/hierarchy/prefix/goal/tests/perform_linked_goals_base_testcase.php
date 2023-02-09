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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package hierarchy_goal
 */

use core_phpunit\testcase;
use hierarchy_goal\entity\company_goal_assignment;
use hierarchy_goal\entity\scale;
use hierarchy_goal\entity\scale_value;
use hierarchy_goal\personal_goal_assignment_type;
use mod_perform\constants;
use mod_perform\models\activity\element;
use mod_perform\testing\generator as perform_generator;
use performelement_linked_review\models\linked_review_content;
use totara_hierarchy\testing\generator as hierarchy_generator;
use totara_job\job_assignment;

global $CFG;
require_once($CFG->dirroot . '/totara/hierarchy/prefix/goal/lib.php');

abstract class perform_linked_goals_base_testcase extends testcase {

    protected function setUp(): void {
        if (!core_component::get_plugin_directory('mod', 'perform')
            || !core_component::get_plugin_directory('performelement', 'linked_review')
        ) {
            self::markTestSkipped('Perform or the linked review element plugin is not installed');
        }
        parent::setUp();
    }

    protected function create_activity_data(int $goal_type, string $status_change_relationship = 'manager'): stdClass {
        self::setAdminUser();

        $another_user = self::getDataGenerator()->create_user(['firstname' => 'Another', 'lastname' => 'User']);
        $manager_user = self::getDataGenerator()->create_user(['firstname' => 'Manager', 'lastname' => 'User']);
        $subject_user = self::getDataGenerator()->create_user(['firstname' => 'Subject', 'lastname' => 'User']);

        /** @var job_assignment $manager_ja */
        $manager_ja = job_assignment::create([
            'userid' => $manager_user->id,
            'idnumber' => 'ja02',
        ]);

        job_assignment::create([
            'userid' => $subject_user->id,
            'idnumber' => 'ja01',
            'managerjaid' => $manager_ja->id
        ]);

        [, $goal1, $goal2, $scale] = ($goal_type === goal::SCOPE_PERSONAL)
            ? $this->create_personal_goals($subject_user)
            : $this->create_company_goals($subject_user);

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
        $appraiser_section_relationship = $perform_generator->create_section_relationship(
            $section,
            ['relationship' => constants::RELATIONSHIP_APPRAISER]
        );
        $element = element::create($activity->get_context(), 'linked_review', 'title', '', json_encode([
            'content_type' => 'personal_goal',
            'content_type_settings' => [
                'enable_status_change' => true,
                'status_change_relationship' => $perform_generator->get_core_relationship($status_change_relationship)->id
            ],
            'selection_relationships' => [$subject_section_relationship->core_relationship_id],
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

        $manager_participant_section1 = $perform_generator->create_participant_instance_and_section(
            $activity,
            $manager_user,
            $subject_instance1->id,
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
        $perform_generator->create_participant_instance_and_section(
            $activity,
            $subject_user,
            $subject_instance1->id,
            $section,
            $appraiser_section_relationship->core_relationship->id
        );
        $subject_participant_section2 = $perform_generator->create_participant_instance_and_section(
            $activity,
            $subject_user,
            $subject_instance2->id,
            $section,
            $subject_section_relationship->core_relationship->id
        );

        if ($goal_type === goal::SCOPE_PERSONAL) {
            $goal1_assignment = $goal1;
            $goal2_assignment = $goal2;
        } else {
            /** @var company_goal_assignment $goal_assignment_goal1 */
            $goal1_assignment = company_goal_assignment::repository()
                ->where('userid', $subject_user->id)
                ->where('goalid', $goal1->id)
                ->one(true);
            /** @var company_goal_assignment $goal_assignment_goal2 */
            $goal2_assignment = company_goal_assignment::repository()
                ->where('userid', $subject_user->id)
                ->where('goalid', $goal2->id)
                ->one(true);
        }

        $linked_assignment1 = linked_review_content::create(
            $goal1_assignment->id, $section_element->id, $subject_participant_section1->participant_instance_id, false
        );
        $linked_assignment2 = linked_review_content::create(
            $goal2_assignment->id, $section_element->id, $subject_participant_section1->participant_instance_id, false
        );

        $data = new stdClass();
        $data->another_user = $another_user;
        $data->manager_user = $manager_user;
        $data->subject_user = $subject_user;
        $data->activity = $activity;
        $data->subject_instance1 = $subject_instance1;
        $data->subject_participant_instance1 = $subject_participant_section1->participant_instance;
        $data->manager_participant_instance1 = $manager_participant_section1->participant_instance;
        $data->manager_participant_section1 = $manager_participant_section1;
        $data->section_element = $section_element;
        $data->section = $section;
        $data->linked_assignment1 = $linked_assignment1;
        $data->linked_assignment2 = $linked_assignment2;
        $data->goal1 = $goal1;
        $data->goal2 = $goal2;
        $data->goal1_assignment = $goal1_assignment;
        $data->goal2_assignment = $goal2_assignment;
        $data->scale = scale::repository()->find($scale->id);

        return $data;
    }

    /**
     * @param stdClass|null $user
     * @return array
     */
    protected function create_personal_goals(stdClass $user = null): array {
        self::setAdminUser();

        $generator = self::getDataGenerator();
        /** @var hierarchy_generator $hierarchy_generator */
        $hierarchy_generator = $generator->get_plugin_generator('totara_hierarchy');

        $type = personal_goal_assignment_type::self()->get_value();

        $user = $user ?? $generator->create_user();

        $scale = $this->create_scale($hierarchy_generator);
        $goal1 = $hierarchy_generator->create_personal_goal(
            $user->id,
            [
                'name' => "goal1",
                'assigntype' => $type,
                'scaleid' => $scale->id,
                'scalevalueid' => scale_value::repository()->where('name', 'Created')->one(true)->id,
                'targetdate' => time() + (10 * DAYSECS),
            ],
            true
        );

        // goal2 doesn't have a scale or target date.
        $goal2 = $hierarchy_generator->create_personal_goal($user->id, [
            'name' => "goal1",
            'assigntype' => $type,
            'targetdate' => 0,
        ]);

        return [$user, $goal1, $goal2, $scale];
    }

    /**
     * @param hierarchy_generator $hierarchy_generator
     * @return stdClass
     */
    protected function create_scale(hierarchy_generator $hierarchy_generator): stdClass {
        $scale_values = [
            1 => ['name' => 'Finished', 'proficient' => 1, 'sortorder' => 1, 'default' => 0],
            2 => ['name' => 'Started', 'proficient' => 0, 'sortorder' => 2, 'default' => 0],
            3 => ['name' => 'Created', 'proficient' => 0, 'sortorder' => 3, 'default' => 1]
        ];
        return $hierarchy_generator->create_scale('goal', ['name' => 'goal_custom_scale'], $scale_values);
    }

    /**
     * @param stdClass|null $user1
     * @return array
     */
    protected function create_company_goals(stdClass $user1 = null): array {
        $generator = self::getDataGenerator();
        /** @var hierarchy_generator $hierarchy_generator */
        $hierarchy_generator = $generator->get_plugin_generator('totara_hierarchy');
        $user1 = $user1 ?? $generator->create_user();
        $user2 = $generator->create_user();

        $scale = $this->create_scale($hierarchy_generator);
        $framework = $hierarchy_generator->create_goal_frame(['name' => 'frame1', 'scale' => 'goal_custom_scale']);

        $goal1 = $hierarchy_generator->create_goal(['fullname' => 'goal1', 'frameworkid' => $framework->id]);
        // Goal 2 doesn't have a target date.
        $goal2 = $hierarchy_generator->create_goal(['fullname' => 'goal2', 'frameworkid' => $framework->id, 'targetdate' => 0]);
        $hierarchy_generator->goal_assign_individuals($goal1->id, [$user1->id, $user2->id]);
        $hierarchy_generator->goal_assign_individuals($goal2->id, [$user1->id, $user2->id]);

        // Update the scale value for user1.
        $hierarchy_generator->update_company_goal_user_scale_value(
            $user1->id,
            $goal1->id,
            scale_value::repository()->where('name', 'Started')->one(true)->id
        );

        return [$user1, $goal1, $goal2, $scale];
    }
}