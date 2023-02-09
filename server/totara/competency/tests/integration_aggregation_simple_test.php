<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_competency
 */

use aggregation_latest_achieved\latest_achieved;
use pathway_learning_plan\learning_plan;
use pathway_manual\manual;
use pathway_manual\models\roles\manager;
use totara_competency\achievement_configuration;
use totara_competency\entity\competency_achievement;
use totara_competency\entity\configuration_change;
use totara_competency\entity\pathway as pathway_entity;
use totara_competency\entity\pathway_achievement as pathway_achievement_entity;
use totara_competency\expand_task;
use totara_competency\linked_courses;
use totara_competency\pathway;
use totara_core\advanced_feature;

global $CFG;
require_once($CFG->dirroot . '/totara/competency/tests/integration_aggregation_base_test.php');

/**
 * This class contains the simple integration test cases
 * Integration test file is split because of the sheer size of the file if all integration tests were to be placed in the same file
 *
 * @group totara_competency
 */
class totara_competency_integration_aggregation_simple_testcase extends totara_competency_integration_aggregation_base_testcase {

    /**
     * Test aggregation with a single onactivate criterion
     * @dataProvider task_to_execute_data_provider
     */
    public function test_aggregation_single_onactivate(string $task_to_execute) {
        $data = $this->setup_data();

        // Create a criteria_group on competency 1 and 2 with 1 onactivate criterion on the lowest scale
        $pathways = [];
        for ($i = 1; $i <= 2; $i++) {
            $criterion = $data->criteria_generator->create_onactivate(['competency' => $data->competencies[$i]->id]);
            $pathways[$i] = $data->competency_generator->create_criteria_group($data->competencies[$i],
                [$criterion],
                $data->scalevalues[5]->id
            );
        }

        // First run the task without any assignments - should have no effect
        (new $task_to_execute())->execute();
        $this->verify_item_records([]);
        $this->verify_pathway_achievements([]);
        $this->verify_competency_achievements([]);

        // Now assign some users to competencies with criteria and some to competencies without criteria
        $to_assign = [];
        for ($user_idx = 1; $user_idx <= 3; $user_idx++) {
            $to_assign[] = ['user_id' => $data->users[$user_idx]->id, 'competency_id' => $data->competencies[$user_idx]->id];
            $to_assign[] = ['user_id' => $data->users[$user_idx]->id, 'competency_id' => $data->competencies[$user_idx + 1]->id];
        }
        $this->assign_users_to_competencies($to_assign);

        (new $task_to_execute())->execute();
        $this->verify_item_records([
            ['item_id' => $data->competencies[1]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 1],
            ['item_id' => $data->competencies[2]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 1],
            ['item_id' => $data->competencies[2]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 1],
        ]);

        $pw_achievement_records = $this->verify_pathway_achievements([
            '1-1' => [
                'pathway_id' => $pathways[1]->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['onactivate'],
            ],
            '2-1' => [
                'pathway_id' => $pathways[2]->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['onactivate'],
            ],
            '2-2' => [
                'pathway_id' => $pathways[2]->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['onactivate'],
            ],
        ]);

        $this->verify_competency_achievements([
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['1-1']],
            ],
            [
                'competency_id' => $data->competencies[2]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['2-1']],
            ],
            [
                'competency_id' => $data->competencies[2]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['2-2']],
            ],
        ]);
    }

    /**
     * Test aggregation with a single onactivate criterion which gets archived
     *
     * @dataProvider task_to_execute_data_provider
     */
    public function test_aggregation_single_onactivate_with_subsequent_archiving(string $task_to_execute) {
        $data = $this->setup_data();

        // Create a criteria_group on competency 1 and 2 with 1 onactivate criterion on the lowest scale
        /** @var \pathway_criteria_group\criteria_group[] $pathways */
        $pathways = [];
        for ($i = 1; $i <= 2; $i++) {
            $criterion = $data->criteria_generator->create_onactivate(['competency' => $data->competencies[$i]->id]);
            $pathways[$i] = $data->competency_generator->create_criteria_group($data->competencies[$i],
                [$criterion],
                $data->scalevalues[5]->id
            );
        }

        // Now assign some users to competencies with criteria and some to competencies without criteria
        $to_assign = [];
        for ($user_idx = 1; $user_idx <= 3; $user_idx++) {
            $to_assign[] = ['user_id' => $data->users[$user_idx]->id, 'competency_id' => $data->competencies[$user_idx]->id];
            $to_assign[] = ['user_id' => $data->users[$user_idx]->id, 'competency_id' => $data->competencies[$user_idx + 1]->id];
        }
        $this->assign_users_to_competencies($to_assign);

        (new $task_to_execute())->execute();

        // Now archive one pathway
        $pathways[1]->delete();

        configuration_change::add_competency_entry(
            $data->competencies[1]->id,
            configuration_change::CHANGED_CRITERIA,
            time()
        );

        $current_pathway = new pathway_entity($pathways[1]->get_id());

        $this->assertEquals(pathway::PATHWAY_STATUS_ARCHIVED, $current_pathway->status);

        $pw_achievement_records = $this->verify_pathway_achievements([
            '1-1' => [
                'pathway_id' => $pathways[1]->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['onactivate'],
            ],
            '2-1' => [
                'pathway_id' => $pathways[2]->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['onactivate'],
            ],
            '2-2' => [
                'pathway_id' => $pathways[2]->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['onactivate'],
            ],
        ]);

        $this->verify_competency_achievements([
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['1-1']],
            ],
            [
                'competency_id' => $data->competencies[2]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['2-1']],
            ],
            [
                'competency_id' => $data->competencies[2]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['2-2']],
            ],
        ]);

        (new $task_to_execute())->execute();

        // The pathway achievements should now been archived
        $pw_achievement_records = $this->verify_pathway_achievements([
            '1-1' => [
                'pathway_id' => $pathways[1]->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement_entity::STATUS_ARCHIVED,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['onactivate'],
            ],
            '2-1' => [
                'pathway_id' => $pathways[2]->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['onactivate'],
            ],
            '2-2' => [
                'pathway_id' => $pathways[2]->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['onactivate'],
            ],
        ]);

        // The user should have lost the value as there was only one pathway
        $this->verify_competency_achievements([
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::SUPERSEDED,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['1-1']],
            ],
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => null,
                'proficient' => 0,
                'via' => null,
            ],
            [
                'competency_id' => $data->competencies[2]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['2-1']],
            ],
            [
                'competency_id' => $data->competencies[2]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['2-2']],
            ],
        ]);
    }

    /**
     * Test aggregation with a single onactivate criterion which gets archived
     *
     * @dataProvider task_to_execute_data_provider
     */
    public function test_aggregation_multiple_pathways_with_subsequent_archiving(string $task_to_execute) {
        $data = $this->setup_data();

        // Create a criteria_group on competency 1 with 1 coursecompletion criterion to complete course1
        // Assign users 1, 2 and 3
        // Users 1 and 3 completes the course
        $criterion = $data->criteria_generator->create_coursecompletion(['courseids' => [$data->courses[1]->id]]);
        $pathway1 = $data->competency_generator->create_criteria_group($data->competencies[1],
            [$criterion],
            $data->scalevalues[4]->id
        );

        $criterion = $data->criteria_generator->create_onactivate(['competency' => $data->competencies[1]->id]);
        $pathway2 = $data->competency_generator->create_criteria_group($data->competencies[1],
            [$criterion],
            $data->scalevalues[5]->id
        );

        // Now assign one user to competencies with criteria and some to competencies without criteria
        $this->assign_users_to_competencies([['user_id' => $data->users[1]->id, 'competency_id' => $data->competencies[1]->id]]);

        // Complete course for one user
        $completion = new completion_completion(['course' => $data->courses[1]->id, 'userid' => $data->users[1]->id]);
        $completion->mark_complete();

        (new $task_to_execute())->execute();

        $pw_achievement_records = $this->verify_pathway_achievements([
            '1-1' => [
                'pathway_id' => $pathway1->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[4]->id,
                'related_info' => ['coursecompletion'],
            ],
            '1-2' => [
                'pathway_id' => $pathway2->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['onactivate'],
            ]
        ]);

        $this->verify_competency_achievements([
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[4]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['1-1']],
            ],
        ]);

        // Now archive the course pathway which leaves only the onactivate
        $pathway1->delete();

        configuration_change::add_competency_entry(
            $data->competencies[1]->id,
            configuration_change::CHANGED_CRITERIA,
            time()
        );

        $current_pathway = new pathway_entity($pathway1->get_id());

        $this->assertEquals(pathway::PATHWAY_STATUS_ARCHIVED, $current_pathway->status);

        (new $task_to_execute())->execute();

        // The coursecompletion pathway achievement should now be archived
        $pw_achievement_records = $this->verify_pathway_achievements([
            '1-1' => [
                'pathway_id' => $pathway1->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement_entity::STATUS_ARCHIVED,
                'scale_value_id' => $data->scalevalues[4]->id,
                'related_info' => ['coursecompletion'],
            ],
            '1-2' => [
                'pathway_id' => $pathway2->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['onactivate'],
            ]
        ]);

        // Now the value should have changed to the onactivate pathway
        // as the other one got archived
        $this->verify_competency_achievements([
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['1-2']],
            ],
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::SUPERSEDED,
                'scale_value_id' => $data->scalevalues[4]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['1-1']],
            ],
        ]);
    }

    /**
     * Test aggregation with a single coursecompletion criterion
     * @dataProvider task_to_execute_data_provider
     */
    public function test_aggregation_single_coursecompletion(string $task_to_execute) {
        $data = $this->setup_data();

        // Create a criteria_group on competency 1 with 1 coursecompletion criterion to complete course1
        // Assign users 1, 2 and 3
        // Users 1 and 3 completes the course
        $criterion = $data->criteria_generator->create_coursecompletion(['courseids' => [$data->courses[1]->id]]);
        $pathway = $data->competency_generator->create_criteria_group($data->competencies[1],
            [$criterion],
            $data->scalevalues[4]->id
        );

        // Assigning users 1 to 3 to the competency1 and competency2 (competency2 intentionally without criteria)
        $to_assign = [];
        for ($user_idx = 1; $user_idx <= 3; $user_idx++) {
            $to_assign[] = ['user_id' => $data->users[$user_idx]->id, 'competency_id' => $data->competencies[1]->id];
            $to_assign[] = ['user_id' => $data->users[$user_idx]->id, 'competency_id' => $data->competencies[2]->id];
        }
        $this->assign_users_to_competencies($to_assign);

        // Mark users 1 and 3 to have completed the course
        foreach ([1, 3] as $user_idx) {
            $completion = new completion_completion(['course' => $data->courses[1]->id, 'userid' => $data->users[$user_idx]->id]);
            $completion->mark_complete();
        }

        // Now run the task
        (new $task_to_execute())->execute();
        $this->verify_item_records([
            ['item_id' => $data->courses[1]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 1],
            ['item_id' => $data->courses[1]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 0],
            ['item_id' => $data->courses[1]->id, 'user_id' => $data->users[3]->id, 'criterion_met' => 1],
        ]);

        $pw_achievement_records = $this->verify_pathway_achievements([
            '1-1' => [
                'pathway_id' => $pathway->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[4]->id,
                'related_info' => ['coursecompletion'],
            ],
            '1-2' => [
                'pathway_id' => $pathway->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '1-3' => [
                'pathway_id' => $pathway->get_id(),
                'user_id' => $data->users[3]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[4]->id,
                'related_info' => ['coursecompletion'],
            ],
        ]);

        $this->verify_competency_achievements([
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[4]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['1-1']],
            ],
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => null,
                'proficient' => 0,
                'via' => [],
            ],
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[3]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[4]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['1-3']],
            ],
        ]);
    }

    /**
     * Test aggregation with a single linkedcourses criterion
     * @dataProvider task_to_execute_data_provider
     */
    public function test_aggregation_single_linkedcourses(string $task_to_execute) {
        $data = $this->setup_data();

        $pathways = [];
        for ($i = 1; $i <= 2; $i++) {
            $criterion = $data->criteria_generator->create_linkedcourses(['competency' => $data->competencies[$i]->id]);
            $pathways[$i] = $data->competency_generator->create_criteria_group($data->competencies[$i],
                [$criterion],
                $data->scalevalues[3]->id
            );
        }

        // Link courses to competencies
        linked_courses::set_linked_courses(
            $data->competencies[1]->id,
            [
                ['id' => $data->courses[1]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
                ['id' => $data->courses[2]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
                ['id' => $data->courses[3]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
            ]
        );

        linked_courses::set_linked_courses(
            $data->competencies[2]->id,
            [
                ['id' => $data->courses[3]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
                ['id' => $data->courses[4]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
            ]
        );

        // Assigning users
        $to_assign = [
            ['user_id' => $data->users[1]->id, 'competency_id' => $data->competencies[1]->id],
            ['user_id' => $data->users[2]->id, 'competency_id' => $data->competencies[1]->id],
            ['user_id' => $data->users[1]->id, 'competency_id' => $data->competencies[2]->id],
            ['user_id' => $data->users[3]->id, 'competency_id' => $data->competencies[2]->id],
        ];
        $this->assign_users_to_competencies($to_assign);

        // Mark users' completion of course
        $completed = [
            1 => [1, 3],
            2 => [1, 2],
            3 => [1, 2, 3],
            4 => [4, 5],
        ];
        foreach ($completed as $course_idx => $user_idxs) {
            foreach ($user_idxs as $user_idx) {
                $completion = new completion_completion(['course' => $data->courses[$course_idx]->id,
                    'userid' => $data->users[$user_idx]->id
                ]);
                $completion->mark_complete();
            }
        }

        // Now run the task
        (new $task_to_execute())->execute();

        // Because course3 is linked to 2 different pathways 2 different criteria items are created with the same item_id
        // That results in 2 item_records for each user on the same item_id (note that the criterion_item_ids are different)
        $this->verify_item_records([
            ['item_id' => $data->courses[1]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 1],
            ['item_id' => $data->courses[1]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 0],
            ['item_id' => $data->courses[2]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 1],
            ['item_id' => $data->courses[2]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 1],
            ['item_id' => $data->courses[3]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 1, 'num_occurences' => 2],
            ['item_id' => $data->courses[3]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 1],
            ['item_id' => $data->courses[3]->id, 'user_id' => $data->users[3]->id, 'criterion_met' => 1],
            ['item_id' => $data->courses[4]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 0],
            ['item_id' => $data->courses[4]->id, 'user_id' => $data->users[3]->id, 'criterion_met' => 0],
        ]);

        $pw_achievement_records = $this->verify_pathway_achievements([
            '1-1' => [
                'pathway_id' => $pathways[1]->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => ['linkedcourses'],
            ],
            '1-2' => [
                'pathway_id' => $pathways[1]->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '2-1' => [
                'pathway_id' => $pathways[2]->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '2-2' => [
                'pathway_id' => $pathways[2]->get_id(),
                'user_id' => $data->users[3]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
        ]);

        $this->verify_competency_achievements([
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['1-1']],
            ],
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => null,
                'proficient' => 0,
                'via' => [],
            ],
            [
                'competency_id' => $data->competencies[2]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => null,
                'proficient' => 0,
                'via' => [],
            ],
            [
                'competency_id' => $data->competencies[2]->id,
                'user_id' => $data->users[3]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => null,
                'proficient' => 0,
                'via' => [],
            ],
        ]);
    }

    /**
     * Test aggregation with a single linkedcourses criterion - LEARN ONLY
     *
     * @dataProvider task_to_execute_data_provider
     * @param string $task_to_execute
     */
    public function test_aggregation_single_linkedcourses_learn_only(string $task_to_execute) {
        advanced_feature::disable('competency_assignment');

        // In this case we want to skip events otherwise we'll start iwith the default pathways
        $sink = $this->redirectEvents();

        $data = $this->setup_data();

        $sink->close();

        $pathways = [];
        for ($i = 1; $i <= 2; $i++) {
            $criterion = $data->criteria_generator->create_linkedcourses(['competency' => $data->competencies[$i]->id]);
            $pathways[$i] = $data->competency_generator->create_criteria_group(
                $data->competencies[$i],
                [$criterion],
                $data->scalevalues[3]->id
            );
        }

        // Link courses to competencies
        linked_courses::set_linked_courses(
            $data->competencies[1]->id,
            [
                ['id' => $data->courses[1]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
                ['id' => $data->courses[2]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
            ]
        );

        linked_courses::set_linked_courses(
            $data->competencies[2]->id,
            [
                ['id' => $data->courses[3]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
                ['id' => $data->courses[4]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
            ]
        );

        // Mark users' completion of course
        $completed = [
            1 => [1, 3],
            2 => [1, 2],
            3 => [4],
            4 => [4, 5],
        ];
        foreach ($completed as $course_idx => $user_idxs) {
            foreach ($user_idxs as $user_idx) {
                $completion = new completion_completion(['course' => $data->courses[$course_idx]->id,
                    'userid' => $data->users[$user_idx]->id
                ]);
                $completion->mark_complete();
            }
        }

        $this->waitForSecond();

        // Now run the task
        (new $task_to_execute())->execute();

        // Because course3 is linked to 2 different pathways 2 different criteria items are created with the same item_id
        // That results in 2 item_records for each user on the same item_id (note that the criterion_item_ids are different)
        $this->verify_item_records([
            ['item_id' => $data->courses[1]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 1],
            ['item_id' => $data->courses[1]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 0],
            ['item_id' => $data->courses[1]->id, 'user_id' => $data->users[3]->id, 'criterion_met' => 1],
            ['item_id' => $data->courses[2]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 1],
            ['item_id' => $data->courses[2]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 1],
            ['item_id' => $data->courses[2]->id, 'user_id' => $data->users[3]->id, 'criterion_met' => 0],
            ['item_id' => $data->courses[3]->id, 'user_id' => $data->users[4]->id, 'criterion_met' => 1],
            ['item_id' => $data->courses[3]->id, 'user_id' => $data->users[5]->id, 'criterion_met' => 0],
            ['item_id' => $data->courses[4]->id, 'user_id' => $data->users[4]->id, 'criterion_met' => 1],
            ['item_id' => $data->courses[4]->id, 'user_id' => $data->users[5]->id, 'criterion_met' => 1],
        ]);

        $expected_pathway_achievements = [
            '1-1' => [
                'pathway_id' => $pathways[1]->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => ['linkedcourses'],
            ],
            '1-2' => [
                'pathway_id' => $pathways[1]->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '1-3' => [
                'pathway_id' => $pathways[1]->get_id(),
                'user_id' => $data->users[3]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '2-1' => [
                'pathway_id' => $pathways[2]->get_id(),
                'user_id' => $data->users[4]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => ['linkedcourses'],
            ],
            '2-2' => [
                'pathway_id' => $pathways[2]->get_id(),
                'user_id' => $data->users[5]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
        ];

        // As the all task queues all users related to the parent competency
        // a few more achievement records are created. This is expected as
        // with learn the user is implicitily assigned to the parent and
        // the all task queues more than the actions above
        if ($task_to_execute == 'totara_competency\task\competency_aggregation_all') {
            $expected_pathway_achievements = array_merge(
                $expected_pathway_achievements,
                [
                    '1-4' => [
                        'pathway_id' => $pathways[1]->get_id(),
                        'user_id' => $data->users[4]->id,
                        'status' => pathway_achievement_entity::STATUS_CURRENT,
                        'scale_value_id' => null,
                        'related_info' => [],
                    ],
                    '1-5' => [
                        'pathway_id' => $pathways[1]->get_id(),
                        'user_id' => $data->users[5]->id,
                        'status' => pathway_achievement_entity::STATUS_CURRENT,
                        'scale_value_id' => null,
                        'related_info' => [],
                    ]
                ]
            );
        }

        $pw_achievement_records = $this->verify_pathway_achievements($expected_pathway_achievements);

        $expected_competency_achievements = [
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['1-1']],
            ],
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => null,
                'proficient' => 0,
                'via' => [],
            ],
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[3]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => null,
                'proficient' => 0,
                'via' => [],
            ],
            [
                'competency_id' => $data->competencies[2]->id,
                'user_id' => $data->users[4]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['2-1']],
            ],
            [
                'competency_id' => $data->competencies[2]->id,
                'user_id' => $data->users[5]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => null,
                'proficient' => 0,
                'via' => [],
            ],
        ];

        if ($task_to_execute == 'totara_competency\task\competency_aggregation_all') {
            $expected_competency_achievements = array_merge(
                $expected_competency_achievements,
                [
                    [
                        'competency_id' => $data->competencies[1]->id,
                        'user_id' => $data->users[4]->id,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                        'scale_value_id' => null,
                        'proficient' => 0,
                        'via' => [],
                    ],
                    [
                        'competency_id' => $data->competencies[1]->id,
                        'user_id' => $data->users[5]->id,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                        'scale_value_id' => null,
                        'proficient' => 0,
                        'via' => [],
                    ],
                ]
            );
        }

        $this->verify_competency_achievements($expected_competency_achievements);
    }

    /**
     * Test aggregation with childcompetency criteria.
     * The child competency uses coursecompletion criteria
     *
     * @dataProvider task_to_execute_data_provider
     * @param string $task_to_execute
     */
    public function test_aggregation_coursecompletion_to_childcompetency(string $task_to_execute) {
        $data = $this->setup_data();

        $pathways = [];

        // The child competency ...
        $criterion = $data->criteria_generator->create_coursecompletion(['courseids' => [$data->courses[1]->id]]);
        $pathways['child'] = $data->competency_generator->create_criteria_group($data->competencies[2],
            [$criterion],
            $data->scalevalues[2]->id
        );

        // The parent competency ...
        $criterion = $data->criteria_generator->create_childcompetency(['competency' => $data->competencies[1]->id]);
        $pathways['parent'] = $data->competency_generator->create_criteria_group($data->competencies[1],
            [$criterion],
            $data->scalevalues[4]->id
        );

        // Assign users
        $to_assign = [
            ['user_id' => $data->users[1]->id, 'competency_id' => $data->competencies[2]->id],
            ['user_id' => $data->users[2]->id, 'competency_id' => $data->competencies[2]->id],
            ['user_id' => $data->users[3]->id, 'competency_id' => $data->competencies[2]->id],
            ['user_id' => $data->users[1]->id, 'competency_id' => $data->competencies[1]->id],
            ['user_id' => $data->users[2]->id, 'competency_id' => $data->competencies[1]->id],
        ];
        $this->assign_users_to_competencies($to_assign);

        // Mark course completions
        foreach ([2, 3] as $user_idx) {
            $completion = new completion_completion(['course' => $data->courses[1]->id, 'userid' => $data->users[$user_idx]->id]);
            $completion->mark_complete();
        }

        // Now run the task
        (new $task_to_execute())->execute();

        $this->verify_item_records([
            ['item_id' => $data->courses[1]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 0],
            ['item_id' => $data->courses[1]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 1],
            ['item_id' => $data->courses[1]->id, 'user_id' => $data->users[3]->id, 'criterion_met' => 1],
            ['item_id' => $data->competencies[2]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 0],
            ['item_id' => $data->competencies[2]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 1],
        ]);

        $pw_achievement_records = $this->verify_pathway_achievements([
            'child-1' => [
                'pathway_id' => $pathways['child']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            'child-2' => [
                'pathway_id' => $pathways['child']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[2]->id,
                'related_info' => ['coursecompletion'],
            ],
            'child-3' => [
                'pathway_id' => $pathways['child']->get_id(),
                'user_id' => $data->users[3]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[2]->id,
                'related_info' => ['coursecompletion'],
            ],
            'parent-1' => [
                'pathway_id' => $pathways['parent']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            'parent-2' => [
                'pathway_id' => $pathways['parent']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[4]->id,
                'related_info' => ['childcompetency'],
            ],
        ]);

        $this->verify_competency_achievements([
            [
                'competency_id' => $data->competencies[2]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => null,
                'proficient' => 0,
                'via' => [],
            ],
            [
                'competency_id' => $data->competencies[2]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[2]->id,
                'proficient' => 1,
                'via' => [$pw_achievement_records['child-2']],
            ],
            [
                'competency_id' => $data->competencies[2]->id,
                'user_id' => $data->users[3]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[2]->id,
                'proficient' => 1,
                'via' => [$pw_achievement_records['child-3']],
            ],
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => null,
                'proficient' => 0,
                'via' => [],
            ],
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[4]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['parent-2']],
            ],
        ]);
    }

    /**
     * Test aggregation with childcompetency criteria.
     * The child competency uses coursecompletion criteria
     *
     * @dataProvider task_to_execute_data_provider
     * @param string $task_to_execute
     */
    public function test_aggregation_coursecompletion_to_childcompetency_learn_only(string $task_to_execute) {
        advanced_feature::disable('competency_assignment');

        $sink = $this->redirectEvents();
        $data = $this->setup_data();
        $sink->close();

        $pathways = [];

        // The child competency ...
        $criterion = $data->criteria_generator->create_linkedcourses(['competency' => $data->competencies[2]->id]);
        $pathways['child'] = $data->competency_generator->create_criteria_group(
            $data->competencies[2],
            [$criterion],
            $data->scalevalues[2]->id
        );

        // Link courses to competencies
        linked_courses::set_linked_courses(
            $data->competencies[2]->id,
            [
                ['id' => $data->courses[1]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
            ]
        );

        // The parent competency ...
        $criterion = $data->criteria_generator->create_childcompetency(['competency' => $data->competencies[1]->id]);
        $pathways['parent'] = $data->competency_generator->create_criteria_group($data->competencies[1],
            [$criterion],
            $data->scalevalues[4]->id
        );

        // Mark course completions
        foreach ([2, 3] as $user_idx) {
            $completion = new completion_completion(['course' => $data->courses[1]->id, 'userid' => $data->users[$user_idx]->id]);
            $completion->mark_complete();
        }

        // Now run the task
        (new $task_to_execute())->execute();

        $expected_item_records = [
            ['item_id' => $data->courses[1]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 1],
            ['item_id' => $data->courses[1]->id, 'user_id' => $data->users[3]->id, 'criterion_met' => 1],
        ];

        // The normal queue task requires two runs to get to the same result as the all task
        if ($task_to_execute == 'totara_competency\task\competency_aggregation_all') {
            $expected_item_records = array_merge(
                $expected_item_records,
                [
                    ['item_id' => $data->competencies[2]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 1],
                    ['item_id' => $data->competencies[2]->id, 'user_id' => $data->users[3]->id, 'criterion_met' => 1],
                ]
            );
        }

        $this->verify_item_records($expected_item_records);

        $expected_pathway_achievements = [
            'child-2' => [
                'pathway_id' => $pathways['child']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[2]->id,
                'related_info' => ['linkedcourses'],
            ],
            'child-3' => [
                'pathway_id' => $pathways['child']->get_id(),
                'user_id' => $data->users[3]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[2]->id,
                'related_info' => ['linkedcourses'],
            ],
        ];

        if ($task_to_execute == 'totara_competency\task\competency_aggregation_all') {
            $expected_pathway_achievements = array_merge(
                $expected_pathway_achievements,
                [
                    'parent-2' => [
                        'pathway_id' => $pathways['parent']->get_id(),
                        'user_id' => $data->users[2]->id,
                        'status' => pathway_achievement_entity::STATUS_CURRENT,
                        'scale_value_id' => $data->scalevalues[4]->id,
                        'related_info' => ['childcompetency'],
                    ],
                    'parent-3' => [
                        'pathway_id' => $pathways['parent']->get_id(),
                        'user_id' => $data->users[3]->id,
                        'status' => pathway_achievement_entity::STATUS_CURRENT,
                        'scale_value_id' => $data->scalevalues[4]->id,
                        'related_info' => ['childcompetency'],
                    ],
                ]
            );
        }

        $pw_achievement_records = $this->verify_pathway_achievements($expected_pathway_achievements);

        $expected_competency_achievements = [
            [
                'competency_id' => $data->competencies[2]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[2]->id,
                'proficient' => 1,
                'via' => [$pw_achievement_records['child-2']],
            ],
            [
                'competency_id' => $data->competencies[2]->id,
                'user_id' => $data->users[3]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[2]->id,
                'proficient' => 1,
                'via' => [$pw_achievement_records['child-3']],
            ],
        ];

        if ($task_to_execute == 'totara_competency\task\competency_aggregation_all') {
            $expected_competency_achievements = array_merge(
                $expected_competency_achievements,
                [
                    [
                        'competency_id' => $data->competencies[1]->id,
                        'user_id' => $data->users[2]->id,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                        'scale_value_id' => $data->scalevalues[4]->id,
                        'proficient' => 0,
                        'via' => [$pw_achievement_records['parent-2']],
                    ],
                    [
                        'competency_id' => $data->competencies[1]->id,
                        'user_id' => $data->users[3]->id,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                        'scale_value_id' => $data->scalevalues[4]->id,
                        'proficient' => 0,
                        'via' => [$pw_achievement_records['parent-3']],
                    ],
                ]
            );
        }

        $this->verify_competency_achievements($expected_competency_achievements);

        // If it's not the all task we do need to run the task again
        // to catch make sure the parent gets reaggregated as well
        if ($task_to_execute == 'totara_competency\task\competency_aggregation_queue') {
            $this->waitForSecond();

            (new $task_to_execute())->execute();

            $expected_pathway_achievements = array_merge(
                $expected_pathway_achievements,
                [
                    'parent-2' => [
                        'pathway_id' => $pathways['parent']->get_id(),
                        'user_id' => $data->users[2]->id,
                        'status' => pathway_achievement_entity::STATUS_CURRENT,
                        'scale_value_id' => $data->scalevalues[4]->id,
                        'related_info' => ['childcompetency'],
                    ],
                    'parent-3' => [
                        'pathway_id' => $pathways['parent']->get_id(),
                        'user_id' => $data->users[3]->id,
                        'status' => pathway_achievement_entity::STATUS_CURRENT,
                        'scale_value_id' => $data->scalevalues[4]->id,
                        'related_info' => ['childcompetency'],
                    ],
                ]
            );

            $pw_achievement_records = $this->verify_pathway_achievements($expected_pathway_achievements);

            $expected_competency_achievements = array_merge(
                $expected_competency_achievements,
                [
                    [
                        'competency_id' => $data->competencies[1]->id,
                        'user_id' => $data->users[2]->id,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                        'scale_value_id' => $data->scalevalues[4]->id,
                        'proficient' => 0,
                        'via' => [$pw_achievement_records['parent-2']],
                    ],
                    [
                        'competency_id' => $data->competencies[1]->id,
                        'user_id' => $data->users[3]->id,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                        'scale_value_id' => $data->scalevalues[4]->id,
                        'proficient' => 0,
                        'via' => [$pw_achievement_records['parent-3']],
                    ],
                ]
            );

            $this->verify_competency_achievements($expected_competency_achievements);
        }
    }

    /**
     * Test aggregation with manual pathway.
     * @dataProvider task_to_execute_data_provider
     */
    public function test_aggregation_single_manual(string $task_to_execute) {
        /** @var \totara_competency\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');

        $data = $this->setup_data();

        /** @var manual $pathway */
        $pathway = $data->competency_generator->create_manual($data->competencies[1], [manager::class]);

        // Assign users
        $to_assign = [
            ['user_id' => $data->users[1]->id, 'competency_id' => $data->competencies[1]->id],
            ['user_id' => $data->users[2]->id, 'competency_id' => $data->competencies[1]->id],
            ['user_id' => $data->users[3]->id, 'competency_id' => $data->competencies[1]->id],
        ];
        $this->assign_users_to_competencies($to_assign);

        $ratings = [];
        // Manager gives rating
        $ratings[2] = $generator->create_manual_rating(
            $pathway,
            $data->users[2]->id,
            $data->users['manager']->id,
            manager::class,
            $data->scalevalues[2]->id
        );
        $ratings[3] = $generator->create_manual_rating(
            $pathway,
            $data->users[3]->id,
            $data->users['manager']->id,
            manager::class,
            $data->scalevalues[4]->id
        );

        // Now run the task
        (new $task_to_execute())->execute();

        $this->verify_item_records([]);

        $pw_achievement_records = $this->verify_pathway_achievements([
            '1-1' => [
                'pathway_id' => $pathway->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '1-2' => [
                'pathway_id' => $pathway->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[2]->id,
                'related_info' => ['rating_id' => $ratings[2]->id],
            ],
            '1-3' => [
                'pathway_id' => $pathway->get_id(),
                'user_id' => $data->users[3]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[4]->id,
                'related_info' => ['rating_id' => $ratings[3]->id],
            ],
        ]);

        $this->verify_competency_achievements([
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => null,
                'proficient' => 0,
                'via' => [],
            ],
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[2]->id,
                'proficient' => 1,
                'via' => [$pw_achievement_records['1-2']],
            ],
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[3]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[4]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['1-3']],
            ],
        ]);
    }

    /**
     * Test aggregation with learning_plan pathway.
     *
     * @dataProvider task_to_execute_data_provider
     */
    public function test_aggregation_single_learning_plan(string $task_to_execute) {
        $data = $this->setup_data();

        /** @var learning_plan[] $pathways */
        $pathways = [];
        $pathways[1] = $data->competency_generator->create_learning_plan_pathway($data->competencies[1]);
        $pathways[2] = $data->competency_generator->create_learning_plan_pathway($data->competencies[2]);

        // Assign users
        $to_assign = [
            ['user_id' => $data->users[1]->id, 'competency_id' => $data->competencies[1]->id],
            ['user_id' => $data->users[2]->id, 'competency_id' => $data->competencies[1]->id],
            ['user_id' => $data->users[3]->id, 'competency_id' => $data->competencies[1]->id],
            ['user_id' => $data->users[1]->id, 'competency_id' => $data->competencies[2]->id],
            ['user_id' => $data->users[4]->id, 'competency_id' => $data->competencies[2]->id],
        ];
        $this->assign_users_to_competencies($to_assign);

        // Create learning plans
        $data->competency_generator->create_learning_plan_with_competencies($data->users[1]->id,
            [$data->competencies[1]->id => null, $data->competencies[2]->id => $data->scalevalues[3]->id]
        );
        $data->competency_generator->create_learning_plan_with_competencies($data->users[2]->id,
            [$data->competencies[1]->id => $data->scalevalues[2]->id]
        );
        $data->competency_generator->create_learning_plan_with_competencies($data->users[3]->id,
            [$data->competencies[1]->id => $data->scalevalues[4]->id]
        );
        $data->competency_generator->create_learning_plan_with_competencies($data->users[4]->id,
            [$data->competencies[2]->id => null]
        );

        // Now run the task
        (new $task_to_execute())->execute();

        $this->verify_item_records([]);

        $pw_achievement_records = $this->verify_pathway_achievements([
            '1-1' => [
                'pathway_id' => $pathways[1]->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '1-2' => [
                'pathway_id' => $pathways[1]->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[2]->id,
                'related_info' => [],
            ],
            '1-3' => [
                'pathway_id' => $pathways[1]->get_id(),
                'user_id' => $data->users[3]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[4]->id,
                'related_info' => [],
            ],
            '2-1' => [
                'pathway_id' => $pathways[2]->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => [],
            ],
            '2-4' => [
                'pathway_id' => $pathways[2]->get_id(),
                'user_id' => $data->users[4]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
        ]);

        $this->verify_competency_achievements([
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => null,
                'proficient' => 0,
                'via' => [],
            ],
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[2]->id,
                'proficient' => 1,
                'via' => [$pw_achievement_records['1-2']],
            ],
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[3]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[4]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['1-3']],
            ],
            [
                'competency_id' => $data->competencies[2]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['2-1']],
            ],
            [
                'competency_id' => $data->competencies[2]->id,
                'user_id' => $data->users[4]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => null,
                'proficient' => 0,
                'via' => [],
            ],
        ]);
    }

    /**
     * Test aggregation with learning_plan pathway.
     *
     * @dataProvider task_to_execute_data_provider
     * @param string $task_to_execute
     */
    public function test_aggregation_single_learning_plan_learn_only(string $task_to_execute) {
        advanced_feature::disable('competency_assignment');

        $sink = $this->redirectEvents();
        $data = $this->setup_data();
        $sink->close();

        /** @var learning_plan[] $pathways */
        $pathways = [];
        $pathways[1] = $data->competency_generator->create_learning_plan_pathway($data->competencies[1]);
        $pathways[2] = $data->competency_generator->create_learning_plan_pathway($data->competencies[2]);

        // Create learning plans
        $data->competency_generator->create_learning_plan_with_competencies(
            $data->users[1]->id,
            [$data->competencies[1]->id => null, $data->competencies[2]->id => $data->scalevalues[3]->id]
        );
        $data->competency_generator->create_learning_plan_with_competencies(
            $data->users[2]->id,
            [$data->competencies[1]->id => $data->scalevalues[2]->id]
        );
        $data->competency_generator->create_learning_plan_with_competencies(
            $data->users[3]->id,
            [$data->competencies[1]->id => $data->scalevalues[4]->id]
        );
        $data->competency_generator->create_learning_plan_with_competencies(
            $data->users[4]->id,
            [$data->competencies[2]->id => null]
        );

        // Now run the task
        (new $task_to_execute())->execute();

        $this->verify_item_records([]);

        $expected_pathway_achievements = [
            '1-2' => [
                'pathway_id' => $pathways[1]->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[2]->id,
                'related_info' => [],
            ],
            '1-3' => [
                'pathway_id' => $pathways[1]->get_id(),
                'user_id' => $data->users[3]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[4]->id,
                'related_info' => [],
            ],
            '2-1' => [
                'pathway_id' => $pathways[2]->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => [],
            ],
        ];

        // As the all task queues all users related to the parent competency
        // a few more achievement records are created. This is expected as
        // with learn the user is implicitly assigned to the parent and
        // the all task queues more than the actions above
        if ($task_to_execute == 'totara_competency\task\competency_aggregation_all') {
            $expected_pathway_achievements = array_merge(
                $expected_pathway_achievements,
                [
                    '1-1' => [
                        'pathway_id' => $pathways[1]->get_id(),
                        'user_id' => $data->users[1]->id,
                        'status' => pathway_achievement_entity::STATUS_CURRENT,
                        'scale_value_id' => null,
                        'related_info' => [],
                    ],
                ]
            );
        }

        $pw_achievement_records = $this->verify_pathway_achievements($expected_pathway_achievements);

        $expected_competency_achievements = [
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[2]->id,
                'proficient' => 1,
                'via' => [$pw_achievement_records['1-2']],
            ],
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[3]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[4]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['1-3']],
            ],
            [
                'competency_id' => $data->competencies[2]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['2-1']],
            ],
        ];

        if ($task_to_execute == 'totara_competency\task\competency_aggregation_all') {
            $expected_competency_achievements = array_merge(
                $expected_competency_achievements,
                [
                    [
                        'competency_id' => $data->competencies[1]->id,
                        'user_id' => $data->users[1]->id,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                        'scale_value_id' => null,
                        'proficient' => 0,
                        'via' => [],
                    ],
                ]
            );
        }

        $this->verify_competency_achievements($expected_competency_achievements);
    }

    /**
     * Test aggregation with learning_plan pathway and a course completion where learning plan is given first and then a course is completed
     *
     * @dataProvider task_to_execute_data_provider
     * @param string $task_to_execute
     */
    public function test_aggregation_learning_plan_first_course_completion_second_learn_only(string $task_to_execute) {
        advanced_feature::disable('competency_assignment');

        $sink = $this->redirectEvents();
        $data = $this->setup_data();
        $sink->close();

        // Simulate default legacy pathway, make sure it's the latest achievement
        $configuration = new achievement_configuration($data->competencies[1]);
        $configuration->set_aggregation_type(latest_achieved::aggregation_type());
        $configuration->save_aggregation();

        $configuration = new achievement_configuration($data->competencies[2]);
        $configuration->set_aggregation_type(latest_achieved::aggregation_type());
        $configuration->save_aggregation();

        /** @var learning_plan[] $pathways */
        $pathways = [];
        $pathways[1] = $data->competency_generator->create_learning_plan_pathway($data->competencies[1]);
        $pathways[2] = $data->competency_generator->create_learning_plan_pathway($data->competencies[2]);

        $criterion = $data->criteria_generator->create_linkedcourses(['competency' => $data->competencies[2]->id]);
        $pathways[3] = $data->competency_generator->create_criteria_group(
            $data->competencies[2],
            [$criterion],
            $data->scalevalues[2]->id
        );

        // Create learning plans
        $data->competency_generator->create_learning_plan_with_competencies(
            $data->users[1]->id,
            [$data->competencies[1]->id => null, $data->competencies[2]->id => $data->scalevalues[3]->id]
        );
        $data->competency_generator->create_learning_plan_with_competencies(
            $data->users[2]->id,
            [$data->competencies[1]->id => $data->scalevalues[2]->id]
        );
        $data->competency_generator->create_learning_plan_with_competencies(
            $data->users[3]->id,
            [$data->competencies[1]->id => $data->scalevalues[4]->id]
        );
        $data->competency_generator->create_learning_plan_with_competencies(
            $data->users[4]->id,
            [$data->competencies[2]->id => null]
        );

        $this->waitForSecond();

        // Link courses to competencies
        linked_courses::set_linked_courses(
            $data->competencies[2]->id,
            [
                ['id' => $data->courses[1]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
            ]
        );

        // Mark course completed
        $completion = new completion_completion(['course' => $data->courses[1]->id, 'userid' => $data->users[1]->id]);
        $completion->mark_complete();

        // Now run the task
        (new $task_to_execute())->execute();

        $this->verify_item_records([]);

        $expected_pathway_achievements = [
            '1-2' => [
                'pathway_id' => $pathways[1]->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[2]->id,
                'related_info' => [],
            ],
            '1-3' => [
                'pathway_id' => $pathways[1]->get_id(),
                'user_id' => $data->users[3]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[4]->id,
                'related_info' => [],
            ],
            '2-1' => [
                'pathway_id' => $pathways[2]->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => [],
            ],
            '1-4' => [
                'pathway_id' => $pathways[3]->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[2]->id,
                'related_info' => ["linkedcourses"],
            ]
        ];

        // As the all task queues all users related to the parent competency
        // a few more achievement records are created. This is expected as
        // with learn the user is implicitly assigned to the parent and
        // the all task queues more than the actions above
        if ($task_to_execute == 'totara_competency\task\competency_aggregation_all') {
            $expected_pathway_achievements = array_merge(
                $expected_pathway_achievements,
                [
                    '1-1' => [
                        'pathway_id' => $pathways[1]->get_id(),
                        'user_id' => $data->users[1]->id,
                        'status' => pathway_achievement_entity::STATUS_CURRENT,
                        'scale_value_id' => null,
                        'related_info' => [],
                    ],
                ]
            );
        }

        $pw_achievement_records = $this->verify_pathway_achievements($expected_pathway_achievements);

        $expected_competency_achievements = [
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[2]->id,
                'proficient' => 1,
                'via' => [$pw_achievement_records['1-2']],
            ],
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[3]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[4]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['1-3']],
            ],
            [
                'competency_id' => $data->competencies[2]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[2]->id,
                'proficient' => 1,
                'via' => [$pw_achievement_records['1-4']],
            ],
        ];

        if ($task_to_execute == 'totara_competency\task\competency_aggregation_all') {
            $expected_competency_achievements = array_merge(
                $expected_competency_achievements,
                [
                    [
                        'competency_id' => $data->competencies[1]->id,
                        'user_id' => $data->users[1]->id,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                        'scale_value_id' => null,
                        'proficient' => 0,
                    ],
                ]
            );
        }

        $this->verify_competency_achievements($expected_competency_achievements);
    }

    /**
     * Test aggregation with learning_plan pathway and a course completion where course is completed first and then learning plan is completed
     *
     * @dataProvider task_to_execute_data_provider
     * @param string $task_to_execute
     */
    public function test_aggregation_course_completion_first_learning_plan_second_learn_only(string $task_to_execute) {
        advanced_feature::disable('competency_assignment');

        $sink = $this->redirectEvents();
        $data = $this->setup_data();
        $sink->close();

        // Simulate default legacy pathway, make sure it's the latest achievement
        $configuration = new achievement_configuration($data->competencies[1]);
        $configuration->set_aggregation_type(latest_achieved::aggregation_type());
        $configuration->save_aggregation();

        $configuration = new achievement_configuration($data->competencies[2]);
        $configuration->set_aggregation_type(latest_achieved::aggregation_type());
        $configuration->save_aggregation();

        $pathways = [];
        /** @var learning_plan[] $pathways */
        $pathways[1] = $data->competency_generator->create_learning_plan_pathway($data->competencies[1]);
        $pathways[2] = $data->competency_generator->create_learning_plan_pathway($data->competencies[2]);

        $criterion = $data->criteria_generator->create_linkedcourses(['competency' => $data->competencies[2]->id]);
        $pathways[3] = $data->competency_generator->create_criteria_group(
            $data->competencies[2],
            [$criterion],
            $data->scalevalues[2]->id
        );

        // Link courses to competencies
        linked_courses::set_linked_courses(
            $data->competencies[2]->id,
            [
                ['id' => $data->courses[1]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
            ]
        );

        // Mark course completed
        $completion = new completion_completion(['course' => $data->courses[1]->id, 'userid' => $data->users[1]->id]);
        $completion->mark_complete();

        $this->waitForSecond();

        // Create learning plans
        $data->competency_generator->create_learning_plan_with_competencies(
            $data->users[1]->id,
            [$data->competencies[1]->id => null, $data->competencies[2]->id => $data->scalevalues[3]->id]
        );
        $data->competency_generator->create_learning_plan_with_competencies(
            $data->users[2]->id,
            [$data->competencies[1]->id => $data->scalevalues[2]->id]
        );
        $data->competency_generator->create_learning_plan_with_competencies(
            $data->users[3]->id,
            [$data->competencies[1]->id => $data->scalevalues[4]->id]
        );
        $data->competency_generator->create_learning_plan_with_competencies(
            $data->users[4]->id,
            [$data->competencies[2]->id => null]
        );

        // Now run the task
        (new $task_to_execute())->execute();

        $this->verify_item_records([]);

        $expected_pathway_achievements = [
            '1-2' => [
                'pathway_id' => $pathways[1]->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[2]->id,
                'related_info' => [],
            ],
            '1-3' => [
                'pathway_id' => $pathways[1]->get_id(),
                'user_id' => $data->users[3]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[4]->id,
                'related_info' => [],
            ],
            '2-1' => [
                'pathway_id' => $pathways[2]->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => [],
            ],
            '1-4' => [
                'pathway_id' => $pathways[3]->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[2]->id,
                'related_info' => ["linkedcourses"],
            ]
        ];

        // As the all task queues all users related to the parent competency
        // a few more achievement records are created. This is expected as
        // with learn the user is implicitly assigned to the parent and
        // the all task queues more than the actions above
        if ($task_to_execute == 'totara_competency\task\competency_aggregation_all') {
            $expected_pathway_achievements = array_merge(
                $expected_pathway_achievements,
                [
                    '1-1' => [
                        'pathway_id' => $pathways[1]->get_id(),
                        'user_id' => $data->users[1]->id,
                        'status' => pathway_achievement_entity::STATUS_CURRENT,
                        'scale_value_id' => null,
                        'related_info' => [],
                    ],
                ]
            );
        }

        $pw_achievement_records = $this->verify_pathway_achievements($expected_pathway_achievements);

        $expected_competency_achievements = [
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[2]->id,
                'proficient' => 1,
                'via' => [$pw_achievement_records['1-2']],
            ],
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[3]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[4]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['1-3']],
            ],
            [
                'competency_id' => $data->competencies[2]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['2-1']],
            ],
        ];

        if ($task_to_execute == 'totara_competency\task\competency_aggregation_all') {
            $expected_competency_achievements = array_merge(
                $expected_competency_achievements,
                [
                    [
                        'competency_id' => $data->competencies[1]->id,
                        'user_id' => $data->users[1]->id,
                        'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                        'scale_value_id' => null,
                        'proficient' => 0,
                    ],
                ]
            );
        }

        $this->verify_competency_achievements($expected_competency_achievements);
    }

    /**
     * Test aggregation with an additional assignment added after the user already got an achievement
     *
     * @dataProvider task_to_execute_data_provider
     * @param string $task_to_execute
     */
    public function test_aggregation_adding_additional_assignment(string $task_to_execute) {
        $data = $this->setup_data();

        // Create a criteria_group on competency 1 and 2 with 1 onactivate criterion on the lowest scale
        $pathways = [];
        for ($i = 1; $i <= 2; $i++) {
            $criterion = $data->criteria_generator->create_onactivate(['competency' => $data->competencies[$i]->id]);
            $pathways[$i] = $data->competency_generator->create_criteria_group($data->competencies[$i],
                [$criterion],
                $data->scalevalues[5]->id
            );
        }

        // Now assign some users to competencies with criteria and some to competencies without criteria
        $to_assign = [];
        for ($user_idx = 1; $user_idx <= 3; $user_idx++) {
            $to_assign[] = ['user_id' => $data->users[$user_idx]->id, 'competency_id' => $data->competencies[$user_idx]->id];
            $to_assign[] = ['user_id' => $data->users[$user_idx]->id, 'competency_id' => $data->competencies[$user_idx + 1]->id];
        }
        $this->assign_users_to_competencies($to_assign);

        (new $task_to_execute())->execute();
        $this->verify_item_records([
            ['item_id' => $data->competencies[1]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 1],
            ['item_id' => $data->competencies[2]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 1],
            ['item_id' => $data->competencies[2]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 1],
        ]);

        $pw_achievement_records = $this->verify_pathway_achievements([
            '1-1' => [
                'pathway_id' => $pathways[1]->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['onactivate'],
            ],
            '2-1' => [
                'pathway_id' => $pathways[2]->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['onactivate'],
            ],
            '2-2' => [
                'pathway_id' => $pathways[2]->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['onactivate'],
            ],
        ]);

        $this->verify_competency_achievements([
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['1-1']],
            ],
            [
                'competency_id' => $data->competencies[2]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['2-1']],
            ],
            [
                'competency_id' => $data->competencies[2]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['2-2']],
            ],
        ]);

        // NOW create an additional assignment for a user where he already got an achievement previously.
        // The user should get a second achievement record for that competency with the same values as the existing one

        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        /** @var \totara_competency\testing\assignment_generator $assign_generator */
        $assign_generator = $competency_generator->assignment_generator();

        $assign_generator->create_self_assignment($data->competencies[1]->id, $data->users[1]->id);

        $expand_task = new expand_task($GLOBALS['DB']);
        $expand_task->expand_all();

        (new $task_to_execute())->execute();

        $pw_achievement_records = $this->verify_pathway_achievements([
            '1-1' => [
                'pathway_id' => $pathways[1]->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['onactivate'],
            ],
            '2-1' => [
                'pathway_id' => $pathways[2]->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['onactivate'],
            ],
            '2-2' => [
                'pathway_id' => $pathways[2]->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['onactivate'],
            ],
        ]);

        $this->verify_competency_achievements([
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['1-1']],
            ],
            [
                'competency_id' => $data->competencies[2]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['2-1']],
            ],
            [
                'competency_id' => $data->competencies[2]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['2-2']],
            ],
            // The user should now have a seond achievement row for
            // the new assignment with the same values as the first one
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['1-1']],
            ],
        ]);
    }

    /**
     * Test aggregation when the user is assigned twice to the same competency
     * @dataProvider task_to_execute_data_provider
     */
    public function test_user_assigned_twice_before_criteria_added(string $task_to_execute) {
        global $DB;
        $data = $this->setup_data();

        // First create 2 audiences
        $cohort1 = $data->generator->create_cohort();
        cohort_add_member($cohort1->id, $data->users[1]->id);
        cohort_add_member($cohort1->id, $data->users[2]->id);

        $cohort2 = $data->generator->create_cohort();
        cohort_add_member($cohort2->id, $data->users[1]->id);
        cohort_add_member($cohort2->id, $data->users[3]->id);

        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $data->competency_generator;
        // Now assign both audiences to the competency
        $assignments = [
            1 => $competency_generator->assignment_generator()->create_cohort_assignment($data->competencies[1]->id, $cohort1->id),
            2 => $competency_generator->assignment_generator()->create_cohort_assignment($data->competencies[1]->id, $cohort2->id),
        ];

        $expand_task = new expand_task($DB);
        $expand_task->expand_all();

        // Ensure that although there are assignments, we don't create achievements as there are no criteria

        // Now run the task
        (new $task_to_execute())->execute();
        $this->verify_item_records([]);
        $this->verify_pathway_achievements([]);
        $this->verify_competency_achievements([]);

        // No create a criteria_group with onactivate criterion
        $criterion = $data->criteria_generator->create_onactivate(['competency' => $data->competencies[1]->id]);
        $pathway = $data->competency_generator->create_criteria_group($data->competencies[1],
            [$criterion], $data->scalevalues[5]->id
        );

        // In real life, changes to pathways will be made through the UI which will result in a 'configuration_changed'
        // event being triggered.
        // This doesn't happen in the generator - therefore manually triggering the event here
        configuration_change::add_competency_entry(
            $data->competencies[1]->id,
            configuration_change::CHANGED_CRITERIA,
            time()
        );

        // Run the task
        (new $task_to_execute())->execute();

        $this->verify_item_records([
            ['item_id' => $data->competencies[1]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 1],
            ['item_id' => $data->competencies[1]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 1],
            ['item_id' => $data->competencies[1]->id, 'user_id' => $data->users[3]->id, 'criterion_met' => 1],
        ]);

        $pw_achievement_records = $this->verify_pathway_achievements([
            '1-1' => [
                'pathway_id' => $pathway->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['onactivate'],
            ],
            '1-2' => [
                'pathway_id' => $pathway->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['onactivate'],
            ],
            '1-3' => [
                'pathway_id' => $pathway->get_id(),
                'user_id' => $data->users[3]->id,
                'status' => pathway_achievement_entity::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['onactivate'],
            ],
        ]);

        // Competency achievements are per assignment
        $this->verify_competency_achievements([
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'assignment_id' => $assignments[1]->id,
                'via' => [$pw_achievement_records['1-1']],
            ],
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'assignment_id' => $assignments[2]->id,
                'via' => [$pw_achievement_records['1-1']],
            ],
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'assignment_id' => $assignments[1]->id,
                'via' => [$pw_achievement_records['1-2']],
            ],
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[3]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'assignment_id' => $assignments[2]->id,
                'via' => [$pw_achievement_records['1-3']],
            ],
        ]);
    }

}
