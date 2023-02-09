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

use pathway_learning_plan\learning_plan;
use pathway_manual\manual;
use pathway_manual\models\roles\manager;
use pathway_manual\models\roles\self_role;
use totara_competency\entity\competency_achievement;
use totara_competency\entity\pathway_achievement;
use totara_competency\hook\competency_configuration_changed;
use totara_competency\linked_courses;
use totara_criteria\criterion;

global $CFG;
require_once($CFG->dirroot . '/totara/competency/tests/integration_aggregation_base_test.php');

/**
 * This class contains integration tests with all pathways of the same type with multiple runs
 * Integration test file is split because of the sheer size of the file if all integration tests were to be placed in the same file
 *
 * @group totara_competency
 */
class totara_competency_integration_aggregation_single_type_multi_run_testcase extends totara_competency_integration_aggregation_base_testcase {

    /**
     * Test aggregation task with a combination of criteria_groups and multiple runs
     * @dataProvider task_to_execute_data_provider
     */
    public function test_aggregation_criteria_groups_multiple_criteria_multiple_runs(string $task_to_execute) {
        $data = $this->setup_data();

        // Splitting the function for readability
        $this->setup_criteria_groups_multiple_criteria_multiple_runs_data($data);
        $this->criteria_groups_multiple_criteria_multiple_run_1($data, $task_to_execute);
        $this->criteria_groups_multiple_criteria_multiple_run_2($data, $task_to_execute);
        $this->criteria_groups_multiple_criteria_multiple_run_3($data, $task_to_execute);
    }

    /**
     * Test aggregation with manual pathways and multiple runs.
     * @dataProvider task_to_execute_data_provider
     */
    public function test_aggregation_manual_multi_run(string $task_to_execute) {
        $data = $this->setup_data();

        /** @var manual[] $pathways */
        $data->pathways = [];
        $data->pathways['manager'] = $data->competency_generator->create_manual($data->competencies[1], [manager::class]);
        $data->pathways['self'] = $data->competency_generator->create_manual($data->competencies[1], [self_role::class]);
        $data->pathways['manager_self'] = $data->competency_generator->create_manual(
            $data->competencies[1],
            [manager::class, self_role::class]
        );

        /** @var rating[] $ratings */
        $data->ratings = [];

        // Assign users
        $to_assign = [
            ['user_id' => $data->users[1]->id, 'competency_id' => $data->competencies[1]->id],
            ['user_id' => $data->users[2]->id, 'competency_id' => $data->competencies[1]->id],
            ['user_id' => $data->users[3]->id, 'competency_id' => $data->competencies[1]->id],
        ];
        $this->assign_users_to_competencies($to_assign);

        $this->manual_multi_run_1($data, $task_to_execute);
        $this->manual_multi_run_2($data, $task_to_execute);
    }

    /**
     * Test aggregation with learning_plan pathways and multiple runs.
     * @dataProvider task_to_execute_data_provider
     */
    public function test_aggregation_learning_plan_multi_run(string $task_to_execute) {
        $data = $this->setup_data();

        /** @var learning_plan[] $pathways */
        $data->pathways = [];
        $data->pathways[1] = $data->competency_generator->create_learning_plan_pathway($data->competencies[1]);
        $data->pathways[2] = $data->competency_generator->create_learning_plan_pathway($data->competencies[2]);

        // Create learning plans
        $data->learning_plans = [];

        /** @var \totara_plan\testing\generator $plan_generator */
        $data->learning_plans['1-1'] = [
            'dplan' => $data->competency_generator->create_learning_plan_with_competencies($data->users[1]->id,
                [$data->competencies[1]->id => null]
            ),
        ];
        $data->learning_plans['1-2'] = [
            'dplan' => $data->competency_generator->create_learning_plan_with_competencies($data->users[1]->id,
                [$data->competencies[1]->id => null, $data->competencies[2]->id => null]
            ),
        ];
        $data->learning_plans['2-1'] = [
            'dplan' => $data->competency_generator->create_learning_plan_with_competencies($data->users[2]->id,
                [$data->competencies[1]->id => null]
            ),
        ];
        $data->learning_plans['3-1'] = [
            'dplan' => $data->competency_generator->create_learning_plan_with_competencies($data->users[3]->id,
                [$data->competencies[1]->id => null]
            ),
        ];
        $data->learning_plans['4-1'] = [
            'dplan' => $data->competency_generator->create_learning_plan_with_competencies($data->users[4]->id,
                [$data->competencies[2]->id => null]
            ),
        ];

        foreach ($data->learning_plans as $key => $el) {
            $data->learning_plans[$key]['component'] = new dp_competency_component($el['dplan']);
        }

        // Assign users
        $to_assign = [
            ['user_id' => $data->users[1]->id, 'competency_id' => $data->competencies[1]->id],
            ['user_id' => $data->users[2]->id, 'competency_id' => $data->competencies[1]->id],
            ['user_id' => $data->users[3]->id, 'competency_id' => $data->competencies[1]->id],
            ['user_id' => $data->users[1]->id, 'competency_id' => $data->competencies[2]->id],
            ['user_id' => $data->users[4]->id, 'competency_id' => $data->competencies[2]->id],
        ];
        $this->assign_users_to_competencies($to_assign);

        $this->learning_plan_multi_run_1($data, $task_to_execute);
        $this->learning_plan_multi_run_2($data, $task_to_execute);
    }

    /**
     * Test aggregation task with a combination of criteria_groups and multiple runs
     * @dataProvider task_to_execute_data_provider
     */
    public function test_coursecompletion_remove_non_completed(string $task_to_execute) {
        $data = $this->setup_data();

        $data->criteria[1] = $data->criteria_generator->create_coursecompletion([
            'courseids' => [
                $data->courses[1]->id,
            ]
        ]);

        $data->pathway = $data->competency_generator->create_criteria_group($data->competencies[1],
            [$data->criteria[1]], $data->scalevalues[1]->id
        );

        // Assign users 1 and 2 to competencies 3, 4 and 5
        $to_assign = [
            ['user_id' => $data->users[1]->id, 'competency_id' => $data->competencies[1]->id],
        ];
        $this->assign_users_to_competencies($to_assign);

        // Splitting the function for readability
        $this->coursecompletion_remove_non_completed_run_1($data, $task_to_execute);
        $this->coursecompletion_remove_non_completed_run_2($data, $task_to_execute);
        $this->coursecompletion_remove_non_completed_run_3($data, $task_to_execute);
    }


    /**
     * Setup data and return created criteria and pathways
     *
     * @param stdClass &$data
     */
    private function setup_criteria_groups_multiple_criteria_multiple_runs_data(&$data) {
        /** @var [criterion] $criteria */
        $criteria = [];
        /** @var [pathway] $pathways */
        $pathways = [];

        // Competency3's criteria and pathways
        $criteria['3-1-linkedcourses'] = $data->criteria_generator->create_linkedcourses(['competency' => $data->competencies[3]->id]);
        $criteria['3-2-1-coursecompletion'] = $data->criteria_generator->create_coursecompletion([
            'courseids' => [
                $data->courses[1]->id,
                $data->courses[2]->id,
            ]
        ]);
        $criteria['3-2-2-coursecompletion'] = $data->criteria_generator->create_coursecompletion([
            'courseids' => [
                $data->courses[3]->id,
                $data->courses[4]->id,
                $data->courses[5]->id,
            ]
        ]);
        $criteria['3-3-coursecompletion-any'] = $data->criteria_generator->create_coursecompletion([
            'aggregation' => [
                'method' => criterion::AGGREGATE_ANY_N,
                'req_items' => 3,
            ],
            'courseids' => [
                $data->courses[1]->id,
                $data->courses[2]->id,
                $data->courses[3]->id,
                $data->courses[4]->id,
                $data->courses[5]->id,
            ]
        ]);
        $criteria['3-4-childcompetency'] = $data->criteria_generator->create_childcompetency(['competency' => $data->competencies[3]->id]);
        $criteria['3-5-onactivate'] = $data->criteria_generator->create_onactivate(['competency' => $data->competencies[3]->id]);

        $pathways['3-1'] = $data->competency_generator->create_criteria_group($data->competencies[3],
            [$criteria['3-1-linkedcourses']], $data->scalevalues[1]->id
        );
        $pathways['3-2-1'] = $data->competency_generator->create_criteria_group($data->competencies[3],
            [$criteria['3-2-1-coursecompletion']], $data->scalevalues[2]->id
        );
        $pathways['3-2-2'] = $data->competency_generator->create_criteria_group($data->competencies[3],
            [$criteria['3-2-2-coursecompletion']], $data->scalevalues[2]->id
        );
        $pathways['3-3'] = $data->competency_generator->create_criteria_group($data->competencies[3],
            [$criteria['3-3-coursecompletion-any']], $data->scalevalues[3]->id
        );
        $pathways['3-4'] = $data->competency_generator->create_criteria_group($data->competencies[3],
            [$criteria['3-4-childcompetency']], $data->scalevalues[4]->id
        );
        $pathways['3-5'] = $data->competency_generator->create_criteria_group($data->competencies[3],
            [$criteria['3-5-onactivate']], $data->scalevalues[5]->id
        );

        // Link courses 1-5 to competency3
        linked_courses::set_linked_courses(
            $data->competencies[3]->id,
            [
                ['id' => $data->courses[1]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
                ['id' => $data->courses[2]->id, 'linktype' => linked_courses::LINKTYPE_OPTIONAL],
                ['id' => $data->courses[3]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
                ['id' => $data->courses[4]->id, 'linktype' => linked_courses::LINKTYPE_OPTIONAL],
                ['id' => $data->courses[5]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
            ]
        );


        // Competency4's pathways and criteria
        $criteria['4-2-linkedcourses'] = $data->criteria_generator->create_linkedcourses(['competency' => $data->competencies[4]->id]);
        $criteria['4-4-coursecompletion-any-2'] = $data->criteria_generator->create_coursecompletion([
            'aggregation' => [
                'method' => criterion::AGGREGATE_ANY_N,
                'req_items' => 2,
            ],
            'courseids' => [
                $data->courses[4]->id,
                $data->courses[6]->id,
                $data->courses[8]->id,
            ]
        ]);
        $criteria['4-5-coursecompletion-any-1'] = $data->criteria_generator->create_coursecompletion([
            'aggregation' => [
                'method' => criterion::AGGREGATE_ANY_N,
                'req_items' => 1,
            ],
            'courseids' => [
                $data->courses[4]->id,
                $data->courses[6]->id,
                $data->courses[8]->id,
            ]
        ]);

        $pathways['4-2'] = $data->competency_generator->create_criteria_group($data->competencies[4],
            [$criteria['4-2-linkedcourses']], $data->scalevalues[2]->id
        );
        $pathways['4-4'] = $data->competency_generator->create_criteria_group($data->competencies[4],
            [$criteria['4-4-coursecompletion-any-2']], $data->scalevalues[4]->id
        );
        $pathways['4-5'] = $data->competency_generator->create_criteria_group($data->competencies[4],
            [$criteria['4-5-coursecompletion-any-1']], $data->scalevalues[5]->id
        );

        // Link courses 4, 6 and 8 to competency4
        linked_courses::set_linked_courses(
            $data->competencies[4]->id,
            [
                ['id' => $data->courses[4]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
                ['id' => $data->courses[6]->id, 'linktype' => linked_courses::LINKTYPE_OPTIONAL],
                ['id' => $data->courses[8]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
            ]
        );


        // Competency5's pathways and criteria
        $criteria['5-1-coursecompletion-all'] = $data->criteria_generator->create_coursecompletion([
            'courseids' => [
                $data->courses[5]->id,
                $data->courses[8]->id,
            ]
        ]);
        $criteria['5-5-onactivate'] = $data->criteria_generator->create_onactivate(['competency' => $data->competencies[5]->id]);

        $pathways['5-1'] = $data->competency_generator->create_criteria_group($data->competencies[5],
            [$criteria['5-1-coursecompletion-all']],
            $data->scalevalues[1]->id
        );
        $pathways['5-5'] = $data->competency_generator->create_criteria_group($data->competencies[5],
            [$criteria['5-5-onactivate']],
            $data->scalevalues[5]->id
        );

        // Assign users 1 and 2 to competencies 3, 4 and 5
        $to_assign = [
            ['user_id' => $data->users[1]->id, 'competency_id' => $data->competencies[3]->id],
            ['user_id' => $data->users[2]->id, 'competency_id' => $data->competencies[3]->id],
            ['user_id' => $data->users[1]->id, 'competency_id' => $data->competencies[4]->id],
            ['user_id' => $data->users[2]->id, 'competency_id' => $data->competencies[4]->id],
            ['user_id' => $data->users[1]->id, 'competency_id' => $data->competencies[5]->id],
            ['user_id' => $data->users[2]->id, 'competency_id' => $data->competencies[5]->id],
        ];
        $this->assign_users_to_competencies($to_assign);

        $data->criteria = $criteria;
        $data->pathways = $pathways;

        // Criteria configuration is done through the webapi which triggers the competency_configuration_changed hook
        // Simulating this here to ensure watchers are triggered
        /** @var competency_configuration_changed $hook */
        $hook = new competency_configuration_changed($data->competencies[3]->id);
        $hook->execute();
        $hook = new competency_configuration_changed($data->competencies[4]->id);
        $hook->execute();
        $hook = new competency_configuration_changed($data->competencies[5]->id);
        $hook->execute();
    }

    /**
     * Execute first criteria_groups_multiple_criteria_multiple run
     *
     * @param stdClass $data
     * @param string $task_to_execute
     */
    private function criteria_groups_multiple_criteria_multiple_run_1($data, string $task_to_execute) {
        // user1 completes course3
        // aggregation_all

        $completion = new completion_completion(['course' => $data->courses[1]->id, 'userid' => $data->users[1]->id]);
        $completion->mark_complete();

        $this->waitForSecond();

        (new $task_to_execute())->execute();

        $this->verify_item_records([
            ['item_id' => $data->courses[1]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 1, 'num_occurrences' => 3],
            ['item_id' => $data->courses[1]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 0, 'num_occurrences' => 3],
            ['item_id' => $data->courses[2]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 0, 'num_occurrences' => 3],
            ['item_id' => $data->courses[2]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 0, 'num_occurrences' => 3],
            ['item_id' => $data->courses[3]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 0, 'num_occurrences' => 3],
            ['item_id' => $data->courses[3]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 0, 'num_occurrences' => 3],
            ['item_id' => $data->courses[4]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 0, 'num_occurrences' => 6],
            ['item_id' => $data->courses[4]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 0, 'num_occurrences' => 6],
            ['item_id' => $data->courses[5]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 0, 'num_occurrences' => 4],
            ['item_id' => $data->courses[5]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 0, 'num_occurrences' => 4],
            ['item_id' => $data->courses[6]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 0, 'num_occurrences' => 3],
            ['item_id' => $data->courses[6]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 0, 'num_occurrences' => 3],
            ['item_id' => $data->courses[8]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 0, 'num_occurrences' => 4],
            ['item_id' => $data->courses[8]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 0, 'num_occurrences' => 4],
        ]);

        $pw_achievement_records = $this->verify_pathway_achievements([
            '3-1-1' => [
                'pathway_id' => $data->pathways['3-1']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '3-2-1-1' => [
                'pathway_id' => $data->pathways['3-2-1']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '3-2-2-1' => [
                'pathway_id' => $data->pathways['3-2-2']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '3-3-1' => [
                'pathway_id' => $data->pathways['3-3']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '3-4-1' => [
                'pathway_id' => $data->pathways['3-4']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '3-5-1' => [
                'pathway_id' => $data->pathways['3-5']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['onactivate'],
            ],

            '3-1-2' => [
                'pathway_id' => $data->pathways['3-1']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '3-2-1-2' => [
                'pathway_id' => $data->pathways['3-2-1']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '3-2-2-2' => [
                'pathway_id' => $data->pathways['3-2-2']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '3-3-2' => [
                'pathway_id' => $data->pathways['3-3']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '3-4-2' => [
                'pathway_id' => $data->pathways['3-4']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '3-5-2' => [
                'pathway_id' => $data->pathways['3-5']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['onactivate'],
            ],

            '4-2-1' => [
                'pathway_id' => $data->pathways['4-2']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '4-4-1' => [
                'pathway_id' => $data->pathways['4-4']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '4-5-1' => [
                'pathway_id' => $data->pathways['4-5']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],

            '4-2-2' => [
                'pathway_id' => $data->pathways['4-2']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '4-4-2' => [
                'pathway_id' => $data->pathways['4-4']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '4-5-2' => [
                'pathway_id' => $data->pathways['4-5']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],

            '5-1-1' => [
                'pathway_id' => $data->pathways['5-1']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '5-5-1' => [
                'pathway_id' => $data->pathways['5-5']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['onactivate'],
            ],

            '5-1-2' => [
                'pathway_id' => $data->pathways['5-1']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '5-5-2' => [
                'pathway_id' => $data->pathways['5-5']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['onactivate'],
            ],
        ]);

        $this->verify_competency_achievements([
            [
                'competency_id' => $data->competencies[3]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['3-5-1']],
            ],
            [
                'competency_id' => $data->competencies[4]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => null,
                'proficient' => 0,
                'via' => [],
            ],
            [
                'competency_id' => $data->competencies[5]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['5-5-1']],
            ],

            [
                'competency_id' => $data->competencies[3]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['3-5-2']],
            ],
            [
                'competency_id' => $data->competencies[4]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => null,
                'proficient' => 0,
                'via' => [],
            ],
            [
                'competency_id' => $data->competencies[5]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['5-5-2']],
            ],
        ]);
    }

    /**
     * Execute second criteria_groups_multiple_criteria_multiple run
     *
     * @param stdClass $data
     * @param string $task_to_execute
     */
    private function criteria_groups_multiple_criteria_multiple_run_2($data, string $task_to_execute) {
        // user1 completes courses 5, 8
        // user2 completes courses 3, 4

        $completion = new completion_completion(['course' => $data->courses[5]->id, 'userid' => $data->users[1]->id]);
        $completion->mark_complete();
        $completion = new completion_completion(['course' => $data->courses[8]->id, 'userid' => $data->users[1]->id]);
        $completion->mark_complete();
        $completion = new completion_completion(['course' => $data->courses[3]->id, 'userid' => $data->users[2]->id]);
        $completion->mark_complete();
        $completion = new completion_completion(['course' => $data->courses[4]->id, 'userid' => $data->users[2]->id]);
        $completion->mark_complete();

        $this->waitForSecond();

        (new $task_to_execute())->execute();

        $this->verify_item_records([
            ['item_id' => $data->courses[1]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 1, 'num_occurrences' => 3],
            ['item_id' => $data->courses[1]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 0, 'num_occurrences' => 3],
            ['item_id' => $data->courses[2]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 0, 'num_occurrences' => 3],
            ['item_id' => $data->courses[2]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 0, 'num_occurrences' => 3],
            ['item_id' => $data->courses[3]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 0, 'num_occurrences' => 3],
            ['item_id' => $data->courses[3]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 1, 'num_occurrences' => 3],
            ['item_id' => $data->courses[4]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 0, 'num_occurrences' => 6],
            ['item_id' => $data->courses[4]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 1, 'num_occurrences' => 6],
            ['item_id' => $data->courses[5]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 1, 'num_occurrences' => 4],
            ['item_id' => $data->courses[5]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 0, 'num_occurrences' => 4],
            ['item_id' => $data->courses[6]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 0, 'num_occurrences' => 3],
            ['item_id' => $data->courses[6]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 0, 'num_occurrences' => 3],
            ['item_id' => $data->courses[8]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 1, 'num_occurrences' => 4],
            ['item_id' => $data->courses[8]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 0, 'num_occurrences' => 4],
        ]);

        $pw_achievement_records = $this->verify_pathway_achievements([
            '3-1-1' => [
                'pathway_id' => $data->pathways['3-1']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '3-2-1-1' => [
                'pathway_id' => $data->pathways['3-2-1']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '3-2-2-1' => [
                'pathway_id' => $data->pathways['3-2-2']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '3-3-1' => [
                'pathway_id' => $data->pathways['3-3']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '3-4-1' => [
                'pathway_id' => $data->pathways['3-4']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '3-5-1' => [
                'pathway_id' => $data->pathways['3-5']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['onactivate'],
            ],

            '3-1-2' => [
                'pathway_id' => $data->pathways['3-1']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '3-2-1-2' => [
                'pathway_id' => $data->pathways['3-2-1']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '3-2-2-2' => [
                'pathway_id' => $data->pathways['3-2-2']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '3-3-2' => [
                'pathway_id' => $data->pathways['3-3']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '3-4-2' => [
                'pathway_id' => $data->pathways['3-4']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '3-5-2' => [
                'pathway_id' => $data->pathways['3-5']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['onactivate'],
            ],

            '4-2-1' => [
                'pathway_id' => $data->pathways['4-2']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '4-4-1' => [
                'pathway_id' => $data->pathways['4-4']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '4-5-1-a' => [
                'pathway_id' => $data->pathways['4-5']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_ARCHIVED,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '4-5-1' => [
                'pathway_id' => $data->pathways['4-5']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['coursecompletion'],
            ],

            '4-2-2' => [
                'pathway_id' => $data->pathways['4-2']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '4-4-2' => [
                'pathway_id' => $data->pathways['4-4']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '4-5-2-a' => [
                'pathway_id' => $data->pathways['4-5']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_ARCHIVED,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '4-5-2' => [
                'pathway_id' => $data->pathways['4-5']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['coursecompletion'],
            ],

            '5-1-1' => [
                'pathway_id' => $data->pathways['5-1']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[1]->id,
                'related_info' => ['coursecompletion'],
            ],
            '5-1-1-a' => [
                'pathway_id' => $data->pathways['5-1']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_ARCHIVED,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '5-5-1' => [
                'pathway_id' => $data->pathways['5-5']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['onactivate'],
            ],

            '5-1-2' => [
                'pathway_id' => $data->pathways['5-1']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '5-5-2' => [
                'pathway_id' => $data->pathways['5-5']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['onactivate'],
            ],
        ]);

        $this->verify_competency_achievements([
            [
                'competency_id' => $data->competencies[3]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['3-5-1']],
            ],
            [
                'competency_id' => $data->competencies[4]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['4-5-1']],
            ],
            [
                'competency_id' => $data->competencies[4]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::SUPERSEDED,
                'scale_value_id' => null,
                'proficient' => 0,
                'via' => [],
            ],
            [
                'competency_id' => $data->competencies[5]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[1]->id,
                'proficient' => 1,
                'via' => [$pw_achievement_records['5-1-1']],
            ],
            [
                'competency_id' => $data->competencies[5]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::SUPERSEDED,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['5-5-1']],
            ],

            [
                'competency_id' => $data->competencies[3]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['3-5-2']],
            ],
            [
                'competency_id' => $data->competencies[4]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['4-5-2']],
            ],
            [
                'competency_id' => $data->competencies[4]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::SUPERSEDED,
                'scale_value_id' => null,
                'proficient' => 0,
                'via' => [],
            ],
            [
                'competency_id' => $data->competencies[5]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['5-5-2']],
            ],
        ]);
    }

    /**
     * Execute third criteria_groups_multiple_criteria_multiple run
     *
     * @param stdClass $data
     * @param string $task_to_execute
     */
    private function criteria_groups_multiple_criteria_multiple_run_3($data, string $task_to_execute) {
        // user1 completes courses 4, 6
        // user2 completes courses 5

        $completion = new completion_completion(['course' => $data->courses[4]->id, 'userid' => $data->users[1]->id]);
        $completion->mark_complete();
        $completion = new completion_completion(['course' => $data->courses[6]->id, 'userid' => $data->users[1]->id]);
        $completion->mark_complete();
        $completion = new completion_completion(['course' => $data->courses[5]->id, 'userid' => $data->users[2]->id]);
        $completion->mark_complete();

        $this->waitForSecond();

        (new $task_to_execute())->execute();

        $this->verify_item_records([
            ['item_id' => $data->courses[1]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 1, 'num_occurrences' => 3],
            ['item_id' => $data->courses[1]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 0, 'num_occurrences' => 3],
            ['item_id' => $data->courses[2]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 0, 'num_occurrences' => 3],
            ['item_id' => $data->courses[2]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 0, 'num_occurrences' => 3],
            ['item_id' => $data->courses[3]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 0, 'num_occurrences' => 3],
            ['item_id' => $data->courses[3]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 1, 'num_occurrences' => 3],
            ['item_id' => $data->courses[4]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 1, 'num_occurrences' => 6],
            ['item_id' => $data->courses[4]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 1, 'num_occurrences' => 6],
            ['item_id' => $data->courses[5]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 1, 'num_occurrences' => 4],
            ['item_id' => $data->courses[5]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 1, 'num_occurrences' => 4],
            ['item_id' => $data->courses[6]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 1, 'num_occurrences' => 3],
            ['item_id' => $data->courses[6]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 0, 'num_occurrences' => 3],
            ['item_id' => $data->courses[8]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 1, 'num_occurrences' => 4],
            ['item_id' => $data->courses[8]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 0, 'num_occurrences' => 4],
        ]);

        $pw_achievement_records = $this->verify_pathway_achievements([
            '3-1-1' => [
                'pathway_id' => $data->pathways['3-1']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '3-2-1-1' => [
                'pathway_id' => $data->pathways['3-2-1']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '3-2-2-1' => [
                'pathway_id' => $data->pathways['3-2-2']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '3-3-1' => [
                'pathway_id' => $data->pathways['3-3']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => ['coursecompletion'],
            ],
            '3-3-1-a' => [
                'pathway_id' => $data->pathways['3-3']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_ARCHIVED,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '3-4-1' => [
                'pathway_id' => $data->pathways['3-4']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[4]->id,
                'related_info' => ['childcompetency'],
            ],
            '3-4-1-a' => [
                'pathway_id' => $data->pathways['3-4']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_ARCHIVED,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '3-5-1' => [
                'pathway_id' => $data->pathways['3-5']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['onactivate'],
            ],

            '3-1-2' => [
                'pathway_id' => $data->pathways['3-1']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '3-2-1-2' => [
                'pathway_id' => $data->pathways['3-2-1']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '3-2-2-2' => [
                'pathway_id' => $data->pathways['3-2-2']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[2]->id,
                'related_info' => ['coursecompletion'],
            ],
            '3-2-2-2-a' => [
                'pathway_id' => $data->pathways['3-2-2']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_ARCHIVED,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '3-3-2' => [
                'pathway_id' => $data->pathways['3-3']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => ['coursecompletion'],
            ],
            '3-3-2-a' => [
                'pathway_id' => $data->pathways['3-3']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_ARCHIVED,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '3-4-2' => [
                'pathway_id' => $data->pathways['3-4']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '3-5-2' => [
                'pathway_id' => $data->pathways['3-5']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['onactivate'],
            ],

            '4-2-1' => [
                'pathway_id' => $data->pathways['4-2']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[2]->id,
                'related_info' => ['linkedcourses'],
            ],
            '4-2-1-a' => [
                'pathway_id' => $data->pathways['4-2']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_ARCHIVED,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '4-4-1' => [
                'pathway_id' => $data->pathways['4-4']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[4]->id,
                'related_info' => ['coursecompletion'],
            ],
            '4-4-1-a' => [
                'pathway_id' => $data->pathways['4-4']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_ARCHIVED,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '4-5-1' => [
                'pathway_id' => $data->pathways['4-5']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['coursecompletion'],
            ],
            '4-5-1-a' => [
                'pathway_id' => $data->pathways['4-5']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_ARCHIVED,
                'scale_value_id' => null,
                'related_info' => [],
            ],

            '4-2-2' => [
                'pathway_id' => $data->pathways['4-2']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '4-4-2' => [
                'pathway_id' => $data->pathways['4-4']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '4-5-2' => [
                'pathway_id' => $data->pathways['4-5']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['coursecompletion'],
            ],
            '4-5-2-a' => [
                'pathway_id' => $data->pathways['4-5']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_ARCHIVED,
                'scale_value_id' => null,
                'related_info' => [],
            ],

            '5-1-1' => [
                'pathway_id' => $data->pathways['5-1']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[1]->id,
                'related_info' => ['coursecompletion'],
            ],
            '5-1-1-a' => [
                'pathway_id' => $data->pathways['5-1']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_ARCHIVED,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '5-5-1' => [
                'pathway_id' => $data->pathways['5-5']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['onactivate'],
            ],

            '5-1-2' => [
                'pathway_id' => $data->pathways['5-1']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '5-5-2' => [
                'pathway_id' => $data->pathways['5-5']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['onactivate'],
            ],
        ]);

        $this->verify_competency_achievements([
            [
                'competency_id' => $data->competencies[3]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['3-3-1']],
            ],
            [
                'competency_id' => $data->competencies[3]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::SUPERSEDED,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['3-5-1']],
            ],
            [
                'competency_id' => $data->competencies[4]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[2]->id,
                'proficient' => 1,
                'via' => [$pw_achievement_records['4-2-1']],
            ],
            [
                'competency_id' => $data->competencies[4]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::SUPERSEDED,
                'scale_value_id' => null,
                'proficient' => 0,
                'via' => [],
            ],
            [
                'competency_id' => $data->competencies[4]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::SUPERSEDED,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['4-5-1']],
            ],
            [
                'competency_id' => $data->competencies[5]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[1]->id,
                'proficient' => 1,
                'via' => [$pw_achievement_records['5-1-1']],
            ],
            [
                'competency_id' => $data->competencies[5]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::SUPERSEDED,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['5-5-1']],
            ],

            [
                'competency_id' => $data->competencies[3]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[2]->id,
                'proficient' => 1,
                'via' => [$pw_achievement_records['3-2-2-2']],
            ],
            [
                'competency_id' => $data->competencies[3]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::SUPERSEDED,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['3-5-2']],
            ],
            [
                'competency_id' => $data->competencies[4]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['4-5-2']],
            ],
            [
                'competency_id' => $data->competencies[4]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::SUPERSEDED,
                'scale_value_id' => null,
                'proficient' => 0,
                'via' => [],
            ],
            [
                'competency_id' => $data->competencies[5]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [$pw_achievement_records['5-5-2']],
            ],
        ]);
    }

    /**
     * @param $data
     * @param string $task_to_execute
     */
    private function manual_multi_run_1($data, string $task_to_execute) {
        /** @var \totara_competency\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');

        // manager rates user1 = 4, user2 = 3
        // user1 rates himself = 3
        // user2 rates himself = 3

        // Ratings - order is important for manager_self values
        $data->ratings['run1-manager-1'] = $generator->create_manual_rating(
            $data->pathways['manager'],
            $data->users[1]->id,
            $data->users['manager']->id,
            manager::class,
            $data->scalevalues[4]->id
        );
        $data->ratings['run1-manager-2'] = $generator->create_manual_rating(
            $data->pathways['manager'],
            $data->users[2]->id,
            $data->users['manager']->id,
            manager::class,
            $data->scalevalues[3]->id
        );
        $data->ratings['run1-self-1'] = $generator->create_manual_rating(
            $data->pathways['self'],
            $data->users[1]->id,
            $data->users[1]->id,
            self_role::class,
            $data->scalevalues[3]->id
        );
        $data->ratings['run1-self-2'] = $generator->create_manual_rating(
            $data->pathways['self'],
            $data->users[2]->id,
            $data->users[2]->id,
            self_role::class,
            $data->scalevalues[3]->id
        );
        $this->waitForSecond();

        // Now run the task
        (new $task_to_execute())->execute();

        $this->verify_item_records([]);

        $pw_achievement_records = $this->verify_pathway_achievements([
            'run1-manager-1' => [
                'pathway_id' => $data->pathways['manager']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[4]->id,
                'related_info' => ['rating_id' => $data->ratings['run1-manager-1']->id],
            ],
            'run1-manager-2' => [
                'pathway_id' => $data->pathways['manager']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => ['rating_id' => $data->ratings['run1-manager-2']->id],
            ],
            'run1-manager-3' => [
                'pathway_id' => $data->pathways['manager']->get_id(),
                'user_id' => $data->users[3]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],

            'run1-self-1' => [
                'pathway_id' => $data->pathways['self']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => ['rating_id' => $data->ratings['run1-self-1']->id],
            ],
            'run1-self-2' => [
                'pathway_id' => $data->pathways['self']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => ['rating_id' => $data->ratings['run1-self-2']->id],
            ],
            'run1-self-3' => [
                'pathway_id' => $data->pathways['self']->get_id(),
                'user_id' => $data->users[3]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],


            'run1-manager_self-1' => [
                'pathway_id' => $data->pathways['manager_self']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => ['rating_id' => $data->ratings['run1-self-1']->id],
            ],
            'run1-manager_self-2' => [
                'pathway_id' => $data->pathways['manager_self']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => ['rating_id' => $data->ratings['run1-self-2']->id],
            ],
            'run1-manager_self-3' => [
                'pathway_id' => $data->pathways['manager_self']->get_id(),
                'user_id' => $data->users[3]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
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
                'via' => [
                    $pw_achievement_records['run1-self-1'],
                    $pw_achievement_records['run1-manager_self-1']
                ],
            ],
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'proficient' => 0,
                'via' => [
                    $pw_achievement_records['run1-manager-2'],
                    $pw_achievement_records['run1-self-2'],
                    $pw_achievement_records['run1-manager_self-2'],
                ],
            ],
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[3]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => null,
                'proficient' => 0,
                'via' => [],
            ],
        ]);
    }

    /**
     * @param $data
     * @param string $task_to_execute
     */
    private function manual_multi_run_2($data, string $task_to_execute) {
        /** @var \totara_competency\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');

        // user1 rates himself = 2
        // manager rates user1 = 5

        // Ratings - order is important for manager_self values
        $data->ratings['run2-self-1'] = $generator->create_manual_rating(
            $data->pathways['self'],
            $data->users[1]->id,
            $data->users[1]->id,
            self_role::class,
            $data->scalevalues[2]->id
        );
        $data->ratings['run2-manager-1'] = $generator->create_manual_rating(
            $data->pathways['manager'],
            $data->users[1]->id,
            $data->users['manager']->id,
            manager::class,
            $data->scalevalues[5]->id
        );
        $this->waitForSecond();

        // Now run the task
        (new $task_to_execute())->execute();

        $this->verify_item_records([]);

        $pw_achievement_records = $this->verify_pathway_achievements([
            'run2-manager-1' => [
                'pathway_id' => $data->pathways['manager']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['rating_id' => $data->ratings['run2-manager-1']->id],
            ],
            'run1-manager-1' => [
                'pathway_id' => $data->pathways['manager']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_ARCHIVED,
                'scale_value_id' => $data->scalevalues[4]->id,
                'related_info' => ['rating_id' => $data->ratings['run1-manager-1']->id],
            ],
            'run1-manager-2' => [
                'pathway_id' => $data->pathways['manager']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => ['rating_id' => $data->ratings['run1-manager-2']->id],
            ],
            'run1-manager-3' => [
                'pathway_id' => $data->pathways['manager']->get_id(),
                'user_id' => $data->users[3]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],

            'run2-self-1' => [
                'pathway_id' => $data->pathways['self']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[2]->id,
                'related_info' => ['rating_id' => $data->ratings['run2-self-1']->id],
            ],
            'run1-self-1' => [
                'pathway_id' => $data->pathways['self']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_ARCHIVED,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => ['rating_id' => $data->ratings['run1-self-1']->id],
            ],
            'run1-self-2' => [
                'pathway_id' => $data->pathways['self']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => ['rating_id' => $data->ratings['run1-self-2']->id],
            ],
            'run1-self-3' => [
                'pathway_id' => $data->pathways['self']->get_id(),
                'user_id' => $data->users[3]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],


            'run2-manager_self-1' => [
                'pathway_id' => $data->pathways['manager_self']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['rating_id' => $data->ratings['run2-manager-1']->id],
            ],
            'run1-manager_self-1' => [
                'pathway_id' => $data->pathways['manager_self']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_ARCHIVED,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => ['rating_id' => $data->ratings['run1-self-1']->id],
            ],
            'run1-manager_self-2' => [
                'pathway_id' => $data->pathways['manager_self']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => ['rating_id' => $data->ratings['run1-self-2']->id],
            ],
            'run1-manager_self-3' => [
                'pathway_id' => $data->pathways['manager_self']->get_id(),
                'user_id' => $data->users[3]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
        ]);

        $this->verify_competency_achievements([
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[2]->id,
                'proficient' => 1,
                'via' => [
                    $pw_achievement_records['run2-self-1'],
                ],
            ],
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::SUPERSEDED,
                'scale_value_id' => $data->scalevalues[3]->id,
                'proficient' => 0,
                'via' => [
                    $pw_achievement_records['run1-self-1'],
                    $pw_achievement_records['run1-manager_self-1'],
                ],
            ],
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'proficient' => 0,
                'via' => [
                    $pw_achievement_records['run1-manager-2'],
                    $pw_achievement_records['run1-self-2'],
                    $pw_achievement_records['run1-manager_self-2'],
                ],
            ],
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[3]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => null,
                'proficient' => 0,
                'via' => [],
            ],
        ]);
    }

    /**
     * @param $data
     * @param string $task_to_execute
     */
    private function learning_plan_multi_run_1($data, string $task_to_execute) {
        /*
            user1 plan1:
                - comp1 value 4
            user1 plan2:
                - comp1 value 3
                - comp2 value 5
            user2 plan1:
                - comp1 value 3
            user3 plan1:
                - comp1 value 3
            user4 plan 1:
                - comp2 value 5
        */

        $data->learning_plans['1-1']['component']->set_value($data->competencies[1]->id,
            $data->users[1]->id,
            $data->scalevalues[4]->id,
            (object)['manual' => true]
        );
        $data->learning_plans['1-2']['component']->set_value($data->competencies[1]->id,
            $data->users[1]->id,
            $data->scalevalues[3]->id,
            (object)['manual' => true]
        );
        $data->learning_plans['1-2']['component']->set_value($data->competencies[2]->id,
            $data->users[1]->id,
            $data->scalevalues[5]->id,
            (object)['manual' => true]
        );
        $data->learning_plans['2-1']['component']->set_value($data->competencies[1]->id,
            $data->users[2]->id,
            $data->scalevalues[3]->id,
            (object)['manual' => true]
        );
        $data->learning_plans['3-1']['component']->set_value($data->competencies[1]->id,
            $data->users[3]->id,
            $data->scalevalues[3]->id,
            (object)['manual' => true]
        );
        $data->learning_plans['4-1']['component']->set_value($data->competencies[2]->id,
            $data->users[4]->id,
            $data->scalevalues[5]->id,
            (object)['manual' => true]
        );

        $this->waitForSecond();

        // Now run the task
        (new $task_to_execute())->execute();

        $this->verify_item_records([]);

        $pw_achievement_records = $this->verify_pathway_achievements([
            'run1-1-1' => [
                'pathway_id' => $data->pathways['1']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => [],
            ],
            'run1-1-2' => [
                'pathway_id' => $data->pathways[1]->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => [],
            ],
            'run1-1-3' => [
                'pathway_id' => $data->pathways[1]->get_id(),
                'user_id' => $data->users[3]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => [],
            ],

            'run1-2-1' => [
                'pathway_id' => $data->pathways[2]->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => [],
            ],
            'run1-2-4' => [
                'pathway_id' => $data->pathways[2]->get_id(),
                'user_id' => $data->users[4]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
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
                'via' => [
                    $pw_achievement_records['run1-1-1'],
                ],
            ],
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'proficient' => 0,
                'via' => [
                    $pw_achievement_records['run1-1-2'],
                ],
            ],
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[3]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'proficient' => 0,
                'via' => [
                    $pw_achievement_records['run1-1-3'],
                ],
            ],
            [
                'competency_id' => $data->competencies[2]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [
                    $pw_achievement_records['run1-2-1'],
                ],
            ],
            [
                'competency_id' => $data->competencies[2]->id,
                'user_id' => $data->users[4]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [
                    $pw_achievement_records['run1-2-4'],
                ],
            ],
        ]);
    }

    /**
     * @param $data
     * @param string $task_to_execute
     */
    private function learning_plan_multi_run_2($data, string $task_to_execute) {
        /*
            user1 plan1:
                - comp1 remove value
            user3 plan1:
                - comp1 value 1
        */

        $data->learning_plans['1-1']['component']->set_value($data->competencies[1]->id,
            $data->users[1]->id,
            null,
            (object)['manual' => true]
        );
        $data->learning_plans['3-1']['component']->set_value($data->competencies[1]->id,
            $data->users[3]->id,
            $data->scalevalues[1]->id,
            (object)['manual' => true]
        );

        $this->waitForSecond();

        // Now run the task
        (new $task_to_execute())->execute();

        $this->verify_item_records([]);

        $pw_achievement_records = $this->verify_pathway_achievements([
            'run2-1-1' => [
                'pathway_id' => $data->pathways['1']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            'run1-1-1' => [
                'pathway_id' => $data->pathways['1']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_ARCHIVED,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => [],
            ],
            'run1-1-2' => [
                'pathway_id' => $data->pathways[1]->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => [],
            ],
            'run2-1-3' => [
                'pathway_id' => $data->pathways[1]->get_id(),
                'user_id' => $data->users[3]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[1]->id,
                'related_info' => [],
            ],
            'run1-1-3' => [
                'pathway_id' => $data->pathways[1]->get_id(),
                'user_id' => $data->users[3]->id,
                'status' => pathway_achievement::STATUS_ARCHIVED,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => [],
            ],

            'run1-2-1' => [
                'pathway_id' => $data->pathways[2]->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => [],
            ],
            'run1-2-4' => [
                'pathway_id' => $data->pathways[2]->get_id(),
                'user_id' => $data->users[4]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
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
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::SUPERSEDED,
                'scale_value_id' => $data->scalevalues[3]->id,
                'proficient' => 0,
                'via' => [
                    $pw_achievement_records['run1-1-1'],
                ],
            ],
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'proficient' => 0,
                'via' => [
                    $pw_achievement_records['run1-1-2'],
                ],
            ],
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[3]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[1]->id,
                'proficient' => 1,
                'via' => [
                    $pw_achievement_records['run2-1-3'],
                ],
            ],
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[3]->id,
                'status' => competency_achievement::SUPERSEDED,
                'scale_value_id' => $data->scalevalues[3]->id,
                'proficient' => 0,
                'via' => [
                    $pw_achievement_records['run1-1-3'],
                ],
            ],
            [
                'competency_id' => $data->competencies[2]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [
                    $pw_achievement_records['run1-2-1'],
                ],
            ],
            [
                'competency_id' => $data->competencies[2]->id,
                'user_id' => $data->users[4]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [
                    $pw_achievement_records['run1-2-4'],
                ],
            ],
        ]);
    }


    /**
     * Execute first coursecompletion_remove_non_completed run
     *
     * @param stdClass $data
     * @param string $task_to_execute
     */
    private function coursecompletion_remove_non_completed_run_1($data, string $task_to_execute) {
        // user1 completes course1
        // aggregation_all

        $completion = new completion_completion(['course' => $data->courses[1]->id, 'userid' => $data->users[1]->id]);
        $completion->mark_complete();

        $this->waitForSecond();

        (new $task_to_execute())->execute();

        $this->verify_item_records([
            ['item_id' => $data->courses[1]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 1, 'num_occurrences' => 1],
        ]);

        $pw_achievement_records = $this->verify_pathway_achievements([
            '1-1' => [
                'pathway_id' => $data->pathway->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[1]->id,
                'related_info' => ['coursecompletion'],
            ],
        ]);

        $this->verify_competency_achievements([
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[1]->id,
                'proficient' => 1,
                'via' => [$pw_achievement_records['1-1']],
            ],
        ]);
    }

    /**
     * Execute second coursecompletion_remove_non_completed run
     *
     * @param stdClass $data
     * @param string $task_to_execute
     */
    private function coursecompletion_remove_non_completed_run_2($data, string $task_to_execute) {
        // Add course2 to the coursecompletion criterion

        // We need to ensure that the last modified and last evaluated times are differennt
        $this->waitForSecond();

        /** @var criterion $criterion */
        $data->criteria[1]->add_items([$data->courses[2]->id]);
        $data->criteria[1]->save();

        // Criteria configuration is done through the webapi which triggers the competency_configuration_changed hook
        // Simulating this here to ensure watchers are triggered
        /** @var competency_configuration_changed $hook */
        $hook = new competency_configuration_changed($data->competencies[1]->id);
        $hook->execute();

        $this->waitForSecond();

        (new $task_to_execute())->execute();

        $this->verify_item_records([
            ['item_id' => $data->courses[1]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 1, 'num_occurrences' => 1],
            ['item_id' => $data->courses[2]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 0, 'num_occurrences' => 1],
        ]);

        $pw_achievement_records = $this->verify_pathway_achievements([
            '1-1' => [
                'pathway_id' => $data->pathway->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_ARCHIVED,
                'scale_value_id' => $data->scalevalues[1]->id,
                'related_info' => ['coursecompletion'],
            ],
            '2-1' => [
                'pathway_id' => $data->pathway->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
        ]);

        $this->verify_competency_achievements([
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::SUPERSEDED,
                'scale_value_id' => $data->scalevalues[1]->id,
                'proficient' => 1,
                'via' => [$pw_achievement_records['1-1']],
            ],
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => null,
                'proficient' => 0,
                'via' => [],
            ],
        ]);
    }

    /**
     * Execute third coursecompletion_remove_non_completed run
     *
     * @param stdClass $data
     * @param string $task_to_execute
     */
    private function coursecompletion_remove_non_completed_run_3($data, string $task_to_execute) {
        // Remove course2 to the coursecompletion criterion

        // We need to ensure that the last modified and last evaluated times are differennt
        $this->waitForSecond();

        /** @var criterion $criterion */
        $data->criteria[1]->remove_items([$data->courses[2]->id]);
        $data->criteria[1]->save();

        // Criteria configuration is done through the webapi which triggers the competency_configuration_changed hook
        // Simulating this here to ensure watchers are triggered
        /** @var competency_configuration_changed $hook */
        $hook = new competency_configuration_changed($data->competencies[1]->id);
        $hook->execute();

        $this->waitForSecond();

        (new $task_to_execute())->execute();

        $this->verify_item_records([
            ['item_id' => $data->courses[1]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 1, 'num_occurrences' => 1],
        ]);

        $pw_achievement_records = $this->verify_pathway_achievements([
            '1-1' => [
                'pathway_id' => $data->pathway->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_ARCHIVED,
                'scale_value_id' => $data->scalevalues[1]->id,
                'related_info' => ['coursecompletion'],
            ],
            '2-1' => [
                'pathway_id' => $data->pathway->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_ARCHIVED,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            '3-1' => [
                'pathway_id' => $data->pathway->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[1]->id,
                'related_info' => ['coursecompletion'],
            ],
        ]);

        $this->verify_competency_achievements([
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::SUPERSEDED,
                'scale_value_id' => $data->scalevalues[1]->id,
                'proficient' => 1,
                'via' => [$pw_achievement_records['1-1']],
            ],
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::SUPERSEDED,
                'scale_value_id' => null,
                'proficient' => 0,
                'via' => [],
            ],
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[1]->id,
                'proficient' => 1,
                'via' => [$pw_achievement_records['3-1']],
            ],
        ]);
    }

}
