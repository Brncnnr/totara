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

use pathway_manual\models\roles\manager;
use totara_competency\entity\competency_achievement;
use totara_competency\entity\pathway_achievement;
use totara_competency\hook\competency_configuration_changed;

global $CFG;
require_once($CFG->dirroot . '/totara/competency/tests/integration_aggregation_base_test.php');

/**
 * This class contains integration tests with all pathways of the same type with multiple runs
 * Integration test file is split because of the sheer size of the file if all integration tests were to be placed in the same file
 *
 * @group totara_competency
 */
class totara_competency_integration_multi_type_testcase extends totara_competency_integration_aggregation_base_testcase {

    /**
     * Test aggregation task with a typical legacy aggregation using latest_achieved aggregation
     * @dataProvider task_to_execute_data_provider
     */
    public function test_latest_achieved_legacy(string $task_to_execute) {
        $data = $this->setup_data();

        $this->setup_latest_achieved_legacy_data($data);

        $this->latest_achieved_legacy_run_1($data, $task_to_execute);
        $this->latest_achieved_legacy_run_2($data, $task_to_execute);
    }

    /**
     * Test aggregation task with a combination of criteria_groups with othercompetency and multiple runs
     * @dataProvider task_to_execute_data_provider
     */
    public function test_othercompetency_manual(string $task_to_execute) {
        $this->markTestIncomplete();
        $data = $this->setup_data();

        $this->setup_othercompetency_manual_data($data);

        $this->othercompetency_manual_run_1($data, $task_to_execute);
        $this->othercompetency_manual_run_2($data, $task_to_execute);
        // $this->othercompetency_manual_run_3($data, $task_to_execute);
    }

    /**
     * Setup data and return created criteria and pathways for latest_achieved_legacy test
     *
     * @param stdClass &$data
     */
    private function setup_latest_achieved_legacy_data(&$data) {
        // Create learning plans
        $data->learning_plans = [];

        /** @var \totara_plan\testing\generator $plan_generator */
        $data->learning_plans['1-1'] = [
            'dplan' => $data->competency_generator->create_learning_plan_with_competencies($data->users[1]->id,
                [$data->competencies[1]->id => null]
            ),
        ];

        foreach ($data->learning_plans as $key => $el) {
            $data->learning_plans[$key]['component'] = new dp_competency_component($el['dplan']);
        }

        $data->pathways['lp'] = $data->competency_generator->create_learning_plan_pathway($data->competencies[1], 1);

        $data->criteria['coursecompletion'] = $data->criteria_generator->create_coursecompletion([
            'courseids' => [
                $data->courses[1]->id,
            ]
        ]);
        $data->pathways['course'] = $data->competency_generator->create_criteria_group($data->competencies[1],
            [$data->criteria['coursecompletion']], $data->scalevalues[3]->id, 2);

        $data->criteria['child'] = $data->criteria_generator->create_childcompetency([
            'competency' => $data->competencies[1]->id
        ]);
        $data->pathways['child'] = $data->competency_generator->create_criteria_group($data->competencies[1],
            [$data->criteria['child']], $data->scalevalues[3]->id, 2);

        $to_assign = [
            ['user_id' => $data->users[1]->id, 'competency_id' => $data->competencies[1]->id],
        ];
        $this->assign_users_to_competencies($to_assign);
    }


    /**
     * @param $data
     * @param string $task_to_execute
     */
    private function latest_achieved_legacy_run_1($data, string $task_to_execute) {
        /*
            lp comp1: - user1: value 5
        */

        $data->learning_plans['1-1']['component']->set_value($data->competencies[1]->id,
            $data->users[1]->id,
            $data->scalevalues[5]->id,
            (object)['manual' => true]
        );

        $this->waitForSecond();

        // Now run the task
        (new $task_to_execute())->execute();

        $this->verify_item_records([
            ['item_id' => $data->courses[1]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 0, 'num_occurrences' => 1],
        ]);

        $pw_achievement_records = $this->verify_pathway_achievements([
            'run1-lp-1' => [
                'pathway_id' => $data->pathways['lp']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
            ],
            'run1-course-1' => [
                'pathway_id' => $data->pathways['course']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
            ],
        ]);

        $this->verify_competency_achievements([
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [
                    $pw_achievement_records['run1-lp-1'],
                ],
            ],
        ]);
    }

    /**
     * @param $data
     * @param string $task_to_execute
     */
    private function latest_achieved_legacy_run_2($data, string $task_to_execute) {
        /*
            user1 completes course1
        */

        $completion = new completion_completion(['course' => $data->courses[1]->id, 'userid' => $data->users[1]->id]);
        $completion->mark_complete();

        $this->waitForSecond();

        // Now run the task
        (new $task_to_execute())->execute();

        $this->verify_item_records([
            ['item_id' => $data->courses[1]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 1, 'num_occurrences' => 1],
        ]);

        $pw_achievement_records = $this->verify_pathway_achievements([
            'run1-lp-1' => [
                'pathway_id' => $data->pathways['lp']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
            ],
            'run1-course-1' => [
                'pathway_id' => $data->pathways['course']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_ARCHIVED,
                'scale_value_id' => null,
            ],
            'run2-course-1' => [
                'pathway_id' => $data->pathways['course']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[3]->id,
            ],
        ]);

        $this->verify_competency_achievements([
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::SUPERSEDED,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [
                    $pw_achievement_records['run1-lp-1'],
                ],
            ],
            [
                'competency_id' => $data->competencies[1]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'proficient' => 0,
                'via' => [
                    $pw_achievement_records['run2-course-1'],
                ],
            ],
        ]);
    }



    /**
     * Setup data and return created criteria and pathways for othercompetency test
     *
     * @param stdClass &$data
     */
    private function setup_othercompetency_manual_data(&$data) {
        $data->pathways['comp2-man'] = $data->competency_generator->create_manual($data->competencies[2], [manager::class]);
        $data->pathways['comp3-man'] = $data->competency_generator->create_manual($data->competencies[3], [manager::class]);

        $data->criteria['coursecompletion'] = $data->criteria_generator->create_coursecompletion([
            'courseids' => [
                $data->courses[1]->id,
            ]
        ]);
        $data->pathways['comp3-courses'] = $data->competency_generator->create_criteria_group($data->competencies[3],
            [$data->criteria['coursecompletion']], $data->scalevalues[2]->id);

        $data->criteria['other-23'] = $data->criteria_generator->create_othercompetency([
            'competencyids' => [
                $data->competencies[2]->id,
                $data->competencies[3]->id,
            ]
        ]);

        $data->criteria['other-2'] = $data->criteria_generator->create_othercompetency([
            'competencyids' => [
                $data->competencies[2]->id,
            ]
        ]);

        $data->pathways['comp1-other1'] = $data->competency_generator->create_criteria_group($data->competencies[1],
            [$data->criteria['other-23']], $data->scalevalues[1]->id
        );
        $data->pathways['comp1-other3'] = $data->competency_generator->create_criteria_group($data->competencies[1],
            [$data->criteria['other-2']], $data->scalevalues[3]->id
        );

        $to_assign = [
            ['user_id' => $data->users[1]->id, 'competency_id' => $data->competencies[1]->id],
            ['user_id' => $data->users[2]->id, 'competency_id' => $data->competencies[1]->id],
            ['user_id' => $data->users[1]->id, 'competency_id' => $data->competencies[2]->id],
            ['user_id' => $data->users[2]->id, 'competency_id' => $data->competencies[2]->id],
            ['user_id' => $data->users[1]->id, 'competency_id' => $data->competencies[3]->id],
            ['user_id' => $data->users[2]->id, 'competency_id' => $data->competencies[3]->id],
        ];
        $this->assign_users_to_competencies($to_assign);
    }

    /**
     * @param $data
     * @param string $task_to_execute
     */
    private function othercompetency_manual_run_1($data, string $task_to_execute) {
        /** @var \totara_competency\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');

        /*
            comp2:
                - user1: value 1
                - user2: value 3
            comp3:
                - user1: value 3
                - user2: value 4
            course1:
                - user2
        */

        /** @var rating[] $ratings */
        $data->ratings = [];

        $data->ratings['run1-comp2-1'] = $generator->create_manual_rating(
            $data->pathways['comp2-man'],
            $data->users[1]->id,
            $data->users['manager']->id,
            manager::class,
            $data->scalevalues[1]->id
        );
        $data->ratings['run1-comp2-2'] = $generator->create_manual_rating(
            $data->pathways['comp2-man'],
            $data->users[2]->id,
            $data->users['manager']->id,
            manager::class,
            $data->scalevalues[3]->id
        );
        $data->ratings['run1-comp3-1'] = $generator->create_manual_rating(
            $data->pathways['comp3-man'],
            $data->users[1]->id,
            $data->users['manager']->id,
            manager::class,
            $data->scalevalues[3]->id
        );
        $data->ratings['run1-comp3-2'] = $generator->create_manual_rating(
            $data->pathways['comp3-man'],
            $data->users[2]->id,
            $data->users['manager']->id,
            manager::class,
            $data->scalevalues[4]->id
        );

        $completion = new completion_completion(['course' => $data->courses[1]->id, 'userid' => $data->users[2]->id]);
        $completion->mark_complete();

        $this->waitForSecond();

        // Now run the task
        (new $task_to_execute())->execute();

        $this->verify_item_records([
            ['item_id' => $data->competencies[2]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 1, 'num_occurrences' => 2],
            ['item_id' => $data->competencies[2]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 0, 'num_occurrences' => 2],
            ['item_id' => $data->competencies[3]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 0, 'num_occurrences' => 1],
            ['item_id' => $data->competencies[3]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 0, 'num_occurrences' => 1],
            ['item_id' => $data->courses[1]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 0, 'num_occurrences' => 1],
            ['item_id' => $data->courses[1]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 1, 'num_occurrences' => 1],
        ]);

        $pw_achievement_records = $this->verify_pathway_achievements([
            'run1-comp2-1' => [
                'pathway_id' => $data->pathways['comp2-man']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[1]->id,
                'related_info' => ['rating_id' => $data->ratings['run1-comp2-1']->id],
            ],
            'run1-comp2-2' => [
                'pathway_id' => $data->pathways['comp2-man']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => ['rating_id' => $data->ratings['run1-comp2-2']->id],
            ],
            'run1-comp3-m1' => [
                'pathway_id' => $data->pathways['comp3-man']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => ['rating_id' => $data->ratings['run1-comp3-1']->id],
            ],
            'run1-comp3-m2' => [
                'pathway_id' => $data->pathways['comp3-man']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[4]->id,
                'related_info' => ['rating_id' => $data->ratings['run1-comp3-2']->id],
            ],
            'run1-comp3-c1' => [
                'pathway_id' => $data->pathways['comp3-courses']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            'run1-comp3-c2' => [
                'pathway_id' => $data->pathways['comp3-courses']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[2]->id,
                'related_info' => ['coursecompletion'],
            ],
            'run1-other1-1' => [
                'pathway_id' => $data->pathways['comp1-other1']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            'run1-other1-2' => [
                'pathway_id' => $data->pathways['comp1-other1']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            'run1-other3-1' => [
                'pathway_id' => $data->pathways['comp1-other3']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => ['othercompetency'],
            ],
            'run1-other3-2' => [
                'pathway_id' => $data->pathways['comp1-other3']->get_id(),
                'user_id' => $data->users[2]->id,
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
                    $pw_achievement_records['run1-other3-1'],
                ],
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
                'scale_value_id' => $data->scalevalues[1]->id,
                'proficient' => 1,
                'via' => [
                    $pw_achievement_records['run1-comp2-1'],
                ],
            ],
            [
                'competency_id' => $data->competencies[2]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'proficient' => 0,
                'via' => [
                    $pw_achievement_records['run1-comp2-2'],
                ],
            ],
            [
                'competency_id' => $data->competencies[3]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'proficient' => 0,
                'via' => [
                    $pw_achievement_records['run1-comp3-m1'],
                ],
            ],
            [
                'competency_id' => $data->competencies[3]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[2]->id,
                'proficient' => 1,
                'via' => [
                    $pw_achievement_records['run1-comp3-c2'],
                ],
            ],
        ]);
    }

    /**
     * @param $data
     * @param string $task_to_execute
     */
    private function othercompetency_manual_run_2($data, string $task_to_execute) {
        /*
            add course2 to comp3's coursecompletion criteria
        */

        $data->criteria['coursecompletion']->add_items([$data->courses[2]->id]);
        $data->criteria['coursecompletion']->save();

        // Criteria configuration is done through the webapi which triggers the competency_configuration_changed hook
        // Simulating this here to ensure childcompetency watchers are triggered
        /** @var competency_configuration_changed $hook */
        $hook = new competency_configuration_changed($data->competencies[3]->id);
        $hook->execute();

        $this->waitForSecond();

        // Now run the task
        (new $task_to_execute())->execute();

        $this->verify_item_records([
            ['item_id' => $data->competencies[2]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 1, 'num_occurrences' => 3],
            ['item_id' => $data->competencies[2]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 0, 'num_occurrences' => 3],
            ['item_id' => $data->competencies[3]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 0, 'num_occurrences' => 2],
            ['item_id' => $data->competencies[3]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 0, 'num_occurrences' => 2],
            ['item_id' => $data->courses[1]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 0, 'num_occurrences' => 1],
            ['item_id' => $data->courses[1]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 1, 'num_occurrences' => 1],
            ['item_id' => $data->courses[2]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 0, 'num_occurrences' => 1],
            ['item_id' => $data->courses[2]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 0, 'num_occurrences' => 1],
        ]);

        $pw_achievement_records = $this->verify_pathway_achievements([
            'run1-comp2-1' => [
                'pathway_id' => $data->pathways['comp2-man']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[1]->id,
                'related_info' => ['rating_id' => $data->ratings['run1-comp2-1']->id],
            ],
            'run1-comp2-2' => [
                'pathway_id' => $data->pathways['comp2-man']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => ['rating_id' => $data->ratings['run1-comp2-2']->id],
            ],
            'run1-comp3-m1' => [
                'pathway_id' => $data->pathways['comp3-man']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => ['rating_id' => $data->ratings['run1-comp3-1']->id],
            ],
            'run1-comp3-m2' => [
                'pathway_id' => $data->pathways['comp3-man']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[4]->id,
                'related_info' => ['rating_id' => $data->ratings['run1-comp3-2']->id],
            ],
            'run1-comp3-c1' => [
                'pathway_id' => $data->pathways['comp3-courses']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            'run1-comp3-c2' => [
                'pathway_id' => $data->pathways['comp3-courses']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_ARCHIVED,
                'scale_value_id' => $data->scalevalues[2]->id,
                'related_info' => ['coursecompletion'],
            ],
            'run1-comp3-c2' => [
                'pathway_id' => $data->pathways['comp3-courses']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            'run1-other1-1' => [
                'pathway_id' => $data->pathways['comp1-other1']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            'run1-other1-2' => [
                'pathway_id' => $data->pathways['comp1-other1']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            'run1-other3-1' => [
                'pathway_id' => $data->pathways['comp1-other3']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => ['othercompetency'],
            ],
            'run1-other3-2' => [
                'pathway_id' => $data->pathways['comp1-other3']->get_id(),
                'user_id' => $data->users[2]->id,
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
                    $pw_achievement_records['run1-other3-1'],
                ],
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
                'scale_value_id' => $data->scalevalues[1]->id,
                'proficient' => 1,
                'via' => [
                    $pw_achievement_records['run1-comp2-1'],
                ],
            ],
            [
                'competency_id' => $data->competencies[2]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'proficient' => 0,
                'via' => [
                    $pw_achievement_records['run1-comp2-2'],
                ],
            ],
            [
                'competency_id' => $data->competencies[3]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'proficient' => 0,
                'via' => [
                    $pw_achievement_records['run1-comp3-m1'],
                ],
            ],
            [
                'competency_id' => $data->competencies[3]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::SUPERSEDED,
                'scale_value_id' => $data->scalevalues[2]->id,
                'proficient' => 1,
                'via' => [
                    $pw_achievement_records['run1-comp3-c2'],
                ],
            ],
            [
                'competency_id' => $data->competencies[3]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[4]->id,
                'proficient' => 0,
                'via' => [
                    $pw_achievement_records['run1-comp3-m2'],
                ],
            ],
        ]);
    }

    /**
     * @param $data
     * @param string $task_to_execute
     */
    private function othercompetency_manual_run_3($data, string $task_to_execute) {
        /*
            unassign user 1 from comp2
        */

        $to_unassign = [
            ['user_id' => $data->users[1]->id, 'competency_id' => $data->competencies[2]->id],
        ];
        $this->unassign_users_from_competencies($to_unassign);

        $this->waitForSecond();

        // Now run the task
        (new $task_to_execute())->execute();

        $this->verify_item_records([
            ['item_id' => $data->competencies[2]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 0, 'num_occurrences' => 3],
            ['item_id' => $data->competencies[2]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 0, 'num_occurrences' => 3],
            ['item_id' => $data->competencies[3]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 0, 'num_occurrences' => 2],
            ['item_id' => $data->competencies[3]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 0, 'num_occurrences' => 2],
            ['item_id' => $data->competencies[4]->id, 'user_id' => $data->users[1]->id, 'criterion_met' => 0, 'num_occurrences' => 1],
            ['item_id' => $data->competencies[4]->id, 'user_id' => $data->users[2]->id, 'criterion_met' => 1, 'num_occurrences' => 1],
        ]);

        $pw_achievement_records = $this->verify_pathway_achievements([
            'run1-comp2-1' => [
                'pathway_id' => $data->pathways['comp2-man']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_ARCHIVED,
                'scale_value_id' => $data->scalevalues[1]->id,
                'related_info' => ['rating_id' => $data->ratings['run1-comp2-1']->id],
            ],
            'run1-comp2-2' => [
                'pathway_id' => $data->pathways['comp2-man']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => ['rating_id' => $data->ratings['run1-comp2-2']->id],
            ],
            'run1-comp3-1' => [
                'pathway_id' => $data->pathways['comp3-man']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => ['rating_id' => $data->ratings['run1-comp3-1']->id],
            ],
            'run1-comp3-2' => [
                'pathway_id' => $data->pathways['comp3-man']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['rating_id' => $data->ratings['run1-comp3-2']->id],
            ],
            'run1-comp4-1' => [
                'pathway_id' => $data->pathways['comp4-man']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'related_info' => ['rating_id' => $data->ratings['run1-comp4-1']->id],
            ],
            'run1-comp4-2' => [
                'pathway_id' => $data->pathways['comp4-man']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => $data->scalevalues[1]->id,
                'related_info' => ['rating_id' => $data->ratings['run1-comp4-2']->id],
            ],
            'run1-other1-1' => [
                'pathway_id' => $data->pathways['comp1-other1']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            'run1-other1-2' => [
                'pathway_id' => $data->pathways['comp1-other1']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            'run1-other3-1' => [
                'pathway_id' => $data->pathways['comp1-other3']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            'run1-other3-2' => [
                'pathway_id' => $data->pathways['comp1-other3']->get_id(),
                'user_id' => $data->users[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            'run1-other5-1' => [
                'pathway_id' => $data->pathways['comp1-other5']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_ARCHIVED,
                'scale_value_id' => $data->scalevalues[5]->id,
                'related_info' => ['othercompetency'],
            ],
            'run2-other5-1' => [
                'pathway_id' => $data->pathways['comp1-other5']->get_id(),
                'user_id' => $data->users[1]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'scale_value_id' => null,
                'related_info' => [],
            ],
            'run1-other5-2' => [
                'pathway_id' => $data->pathways['comp1-other5']->get_id(),
                'user_id' => $data->users[2]->id,
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
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [
                    $pw_achievement_records['run1-other5-1'],
                ],
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
                'scale_value_id' => null,
                'proficient' => 0,
                'via' => [],
            ],
            [
                'competency_id' => $data->competencies[2]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ARCHIVED_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[1]->id,
                'proficient' => 1,
                'via' => [
                    $pw_achievement_records['run1-comp2-1'],
                ],
            ],
            [
                'competency_id' => $data->competencies[2]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'proficient' => 0,
                'via' => [
                    $pw_achievement_records['run1-comp2-2'],
                ],
            ],
            [
                'competency_id' => $data->competencies[3]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'proficient' => 0,
                'via' => [
                    $pw_achievement_records['run1-comp3-1'],
                ],
            ],
            [
                'competency_id' => $data->competencies[3]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[5]->id,
                'proficient' => 0,
                'via' => [
                    $pw_achievement_records['run1-comp3-2'],
                ],
            ],
            [
                'competency_id' => $data->competencies[4]->id,
                'user_id' => $data->users[1]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[3]->id,
                'proficient' => 0,
                'via' => [
                    $pw_achievement_records['run1-comp4-1'],
                ],
            ],
            [
                'competency_id' => $data->competencies[4]->id,
                'user_id' => $data->users[2]->id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
                'scale_value_id' => $data->scalevalues[1]->id,
                'proficient' => 1,
                'via' => [
                    $pw_achievement_records['run1-comp4-2'],
                ],
            ],
        ]);
    }

}
