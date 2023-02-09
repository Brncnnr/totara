<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @package totara_competency
 * @subpackage test
 */

use core\webapi\execution_context;
use pathway_manual\models\roles\manager;
use totara_competency\entity\assignment;
use totara_competency\entity\pathway;
use totara_competency\expand_task;
use totara_competency\webapi\resolver\query\achievement_paths;
use totara_criteria\criterion;

defined('MOODLE_INTERNAL') || die();


/**
 * Tests the query to fetch all available achievement paths (grouped by pathway types and in specific order)
 *
 * @group totara_competency
 */
class totara_competency_webapi_resolver_query_achievement_paths_testcase extends advanced_testcase {

    private function get_execution_context(string $type = 'dev', ?string $operation = null) {
        return execution_context::create($type, $operation);
    }

    public function test_non_existing_assignment() {
        $this->setAdminUser();

        $generator = $this->getDataGenerator();

        $user1 = $generator->create_user();

        $args = [
            'assignment_id' => 999,
            'user_id' => $user1->id
        ];

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Invalid assignment');

        achievement_paths::resolve($args, $this->get_execution_context());
    }

    public function test_non_existing_user() {
        $this->setAdminUser();

        $data = $this->create_data();

        $args = [
            'assignment_id' => $data->assignment1->id,
            'user_id' => 666
        ];

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Invalid user');

        achievement_paths::resolve($args, $this->get_execution_context());
    }

    public function test_load_for_assignment_with_no_pathways_present() {
        $this->setAdminUser();

        $data = $this->create_data();

        $args = [
            'assignment_id' => $data->assignment1->id,
            'user_id' => $data->assignment1->user_group_id
        ];

        $result = achievement_paths::resolve($args, $this->get_execution_context());
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_invalid_capability() {
        $this->setAdminUser();

        $data = $this->create_data();

        $generator = $this->getDataGenerator();

        $user1 = $generator->create_user();

        $this->setUser($user1);

        $args = [
            'assignment_id' => $data->assignment1->id,
            'user_id' => $data->assignment1->user_group_id
        ];

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Invalid assignment');

        achievement_paths::resolve($args, $this->get_execution_context());
    }

    public function test_load_for_assignment_with_pathways_present() {
        $this->setAdminUser();

        $data = $this->create_data();

        $args = [
            'assignment_id' => $data->assignment1->id,
            'user_id' => $data->assignment1->user_group_id
        ];

        /** @var \totara_competency\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');

        /** @var \totara_criteria\testing\generator $criteria_generator */
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $generator->create_manual($data->comp1, [manager::class], 1);

        $course1 = $this->getDataGenerator()->create_course([
            'shortname' => "Course 1",
            'fullname' => "Course 1",
            'enablecompletion' => true,
        ]);

        $cc1 = $criteria_generator->create_coursecompletion([
            'aggregation' => [
                'method' => criterion::AGGREGATE_ANY_N,
                'req_items' => 1,
            ],
            'courseids' => [$course1->id],
        ]);

        $scale_value = $data->comp1->scale->sorted_values_high_to_low->first();
        $generator->create_criteria_group($data->comp1, [$cc1], $scale_value, 2);

        $course2 = $this->getDataGenerator()->create_course([
            'shortname' => "Course 2",
            'fullname' => "Course 2",
            'enablecompletion' => true,
        ]);

        $cc2 = $criteria_generator->create_coursecompletion([
            'aggregation' => [
                'method' => criterion::AGGREGATE_ANY_N,
                'req_items' => 1,
            ],
            'courseids' => [$course2->id],
        ]);

        $generator->create_criteria_group($data->comp1, [$cc2], $scale_value, 3);
        $generator->create_learning_plan_pathway($data->comp1, 4);

        $result = achievement_paths::resolve($args, $this->get_execution_context());
        $this->assertEquals(
            [
                [
                    'class' => 'SINGLEVALUE',
                    'type' => null,
                    'name' => get_string('achievement_path_group_label_single', 'totara_competency'),
                ],
                [
                    'class' => 'MULTIVALUE',
                    'type' => 'manual',
                    'name' => get_string('achievement_path_group_label', 'pathway_manual'),
                ],
                [
                    'class' => 'MULTIVALUE',
                    'type' => 'learning_plan',
                    'name' => get_string('achievement_path_group_label', 'pathway_learning_plan'),
                ],
            ],
            $result
        );
    }

    public function test_load_for_assignment_with_archived_pathway_present() {
        $this->setAdminUser();

        $data = $this->create_data();

        $args = [
            'assignment_id' => $data->assignment1->id,
            'user_id' => $data->assignment1->user_group_id
        ];

        $pathway1 = new pathway();
        $pathway1->competency_id = $data->assignment1->competency_id;
        $pathway1->sortorder = 1;
        $pathway1->path_type = 'manual';
        $pathway1->path_instance_id = 0;
        $pathway1->status = \totara_competency\pathway::PATHWAY_STATUS_ARCHIVED;
        $pathway1->save();

        $pathway2 = new pathway();
        $pathway2->competency_id = $data->assignment1->competency_id;
        $pathway2->sortorder = 2;
        $pathway2->path_type = 'criteria_group';
        $pathway2->path_instance_id = 0;
        $pathway2->status = \totara_competency\pathway::PATHWAY_STATUS_ACTIVE;
        $pathway2->save();

        $result = achievement_paths::resolve($args, $this->get_execution_context());
        $this->assertEquals(
            [
                [
                    'class' => 'SINGLEVALUE',
                    'type' => null,
                    'name' => get_string('achievement_path_group_label_single', 'totara_competency'),
                ]
            ],
            $result
        );
    }

    public function test_load_for_assignment_with_non_standard_pathway_present() {
        global $CFG;
        require_once $CFG->dirroot.'/totara/competency/tests/fixtures/fake_multivalue_type.php';
        require_once $CFG->dirroot.'/totara/competency/tests/fixtures/fake_singlevalue_type.php';

        $this->setAdminUser();

        $data = $this->create_data();

        $args = [
            'assignment_id' => $data->assignment1->id,
            'user_id' => $data->assignment1->user_group_id
        ];

        $pathway1 = new pathway();
        $pathway1->competency_id = $data->assignment1->competency_id;
        $pathway1->sortorder = 1;
        $pathway1->path_type = 'fake_multivalue_type';
        $pathway1->path_instance_id = 0;
        $pathway1->status = \totara_competency\pathway::PATHWAY_STATUS_ACTIVE;
        $pathway1->save();

        $pathway2 = new pathway();
        $pathway2->competency_id = $data->assignment1->competency_id;
        $pathway2->sortorder = 2;
        $pathway2->path_type = 'criteria_group';
        $pathway2->path_instance_id = 0;
        $pathway2->status = \totara_competency\pathway::PATHWAY_STATUS_ACTIVE;
        $pathway2->save();

        $pathway3 = new pathway();
        $pathway3->competency_id = $data->assignment1->competency_id;
        $pathway3->sortorder = 3;
        $pathway3->path_type = 'learning_plan';
        $pathway3->path_instance_id = 0;
        $pathway3->status = \totara_competency\pathway::PATHWAY_STATUS_ACTIVE;
        $pathway3->save();

        $pathway4 = new pathway();
        $pathway4->competency_id = $data->assignment1->competency_id;
        $pathway4->sortorder = 4;
        $pathway4->path_type = 'fake_singlevalue_type';
        $pathway4->path_instance_id = 0;
        $pathway4->status = \totara_competency\pathway::PATHWAY_STATUS_ACTIVE;
        $pathway4->save();


        $result = achievement_paths::resolve($args, $this->get_execution_context());
        $this->assertEquals(
            [
                [
                    'class' => 'SINGLEVALUE',
                    'type' => null,
                    'name' => get_string('achievement_path_group_label_single', 'totara_competency'),
                ],
                [
                    'class' => 'MULTIVALUE',
                    'type' => 'learning_plan',
                    'name' => get_string('achievement_path_group_label', 'pathway_learning_plan'),
                ],
                [
                    'class' => 'MULTIVALUE',
                    'type' => 'fake_multivalue_type',
                    'name' => 'fake multi value label',
                ]
            ],
            $result
        );
    }

    protected function create_data() {
        $assign_generator = $this->generator()->assignment_generator();

        $data = new class() {
            public $fw1;
            public $comp1;
            public $assignment1;
        };
        $data->fw1 = $this->generator()->hierarchy_generator()->create_comp_frame([]);

        $data->comp1 = $this->generator()->create_competency(null, $data->fw1->id, [
            'shortname' => 'c-chef',
            'fullname' => 'Chef proficiency',
            'description' => 'Bossing around',
            'idnumber' => 'cook-chef-c',
        ]);

        $data->assignment1 = $assign_generator->create_user_assignment(
            $data->comp1->id,
            null,
            ['status' => assignment::STATUS_ACTIVE, 'type' => assignment::TYPE_ADMIN]
        );

        (new expand_task($GLOBALS['DB']))->expand_all();

        return $data;
    }

    /**
     * Get hierarchy specific generator
     *
     * @return \totara_competency\testing\generator
     */
    protected function generator() {
        return \totara_competency\testing\generator::instance();
    }
}
