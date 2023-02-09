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

use core_phpunit\testcase;
use core\webapi\execution_context;
use totara_competency\entity\assignment;
use totara_competency\entity\pathway_achievement;
use totara_competency\expand_task;
use totara_competency\plugin_types;
use totara_competency\task\competency_aggregation_all;
use totara_competency\webapi\resolver\query\scale_achievements;
use totara_criteria\criterion;
use totara_hierarchy\entity\scale_value;
use totara_webapi\graphql;

defined('MOODLE_INTERNAL') || die();


/**
 * Tests the query to fetch all scales and their items for single value pathways
 *
 * @group totara_competency
 */
class totara_competency_webapi_resolver_query_scale_achievements_testcase extends testcase {

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

        scale_achievements::resolve($args, $this->get_execution_context());
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

        scale_achievements::resolve($args, $this->get_execution_context());
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

        scale_achievements::resolve($args, $this->get_execution_context());
    }

    public function test_load_for_assignment_with_no_pathways_present() {
        $this->setAdminUser();

        $data = $this->create_data();

        $args = [
            'assignment_id' => $data->assignment1->id,
            'user_id' => $data->assignment1->user_group_id
        ];
        $result = scale_achievements::resolve($args, $this->get_execution_context());
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_load_for_assignment_with_pathways_present() {
        $this->activate_additional_pathways();

        $this->setAdminUser();

        $data = $this->create_data();

        $args = [
            'assignment_id' => $data->assignment1->id,
            'user_id' => $data->assignment1->user_group_id
        ];

        $this->create_achievement_paths_data($data);

        $results = scale_achievements::resolve($args, $this->get_execution_context());
        $this->assertCount(2, $results);
        foreach ($results as $result) {
            $this->assertInstanceOf(scale_value::class, $result['scale_value']);
            $this->assertIsArray($result['items']);
            $this->assertCount(1, $result['items']);
            $this->assertContainsOnlyInstancesOf(pathway_achievement::class, $result['items']);
        }
    }

    public function test_call_service() {
        $this->activate_additional_pathways();

        $this->setAdminUser();

        $data = $this->create_data();

        $args = [
            'assignment_id' => $data->assignment1->id,
            'user_id' => $data->assignment1->user_group_id
        ];

        $pathway_data = $this->create_achievement_paths_data($data);

        $this->complete_course($args['user_id'], $pathway_data->course2->id);
        $this->aggregate();

        // Create an additional group which has not been aggregated yet to make sure the service does not trip over that

        $course3 = $this->getDataGenerator()->create_course([
            'shortname' => "Course 3",
            'fullname' => "Course 3",
            'enablecompletion' => true,
        ]);

        $cc3 = $this->criteria_generator()->create_coursecompletion([
            'aggregation' => [
                'method' => criterion::AGGREGATE_ANY_N,
                'req_items' => 1,
            ],
            'courseids' => [$course3->id],
        ]);

        $cg3 = $this->competency_generator()->create_criteria_group($data->comp1, [$cc3], $pathway_data->scale_value3, 3);

        $execution_context = $this->get_execution_context(graphql::TYPE_AJAX, 'totara_competency_scale_achievements');
        $result = graphql::execute_operation($execution_context, $args);
        $result = $result->toArray();

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('totara_competency_scale_achievements', $result['data']);
        $data = $result['data']['totara_competency_scale_achievements'];
        $this->assertCount(3, $data);
        $first_result = $data[0];
        $this->assertArrayHasKey('scale_value', $first_result);
        $this->assertEquals([
            'name' => 'Complete',
            'numericscore' => null,
            'proficient' => true
        ], $first_result['scale_value']);

        $this->assertArrayHasKey('items', $first_result);
        $this->assertCount(1, $first_result['items']);
        $item = $first_result['items'][0];
        $this->assertEquals(false, $item['achieved']);
        $this->assertNull($item['date_achieved']);
        $this->assertArrayHasKey('pathway', $item);
        $this->assertEquals($pathway_data->cg1->get_path_instance_id(), $item['pathway']['instance_id']);
        $this->assertEquals('criteria_group', $item['pathway']['pathway_type']);

        $second_result = $data[1];
        $this->assertArrayHasKey('scale_value', $second_result);
        $this->assertEquals([
            'name' => 'Progress',
            'numericscore' => null,
            'proficient' => false
        ], $second_result['scale_value']);

        $this->assertArrayHasKey('items', $second_result);
        $this->assertCount(1, $second_result['items']);
        $item = $second_result['items'][0];
        $this->assertEquals(true, $item['achieved']);
        $this->assertGreaterThan(0, $item['date_achieved']);
        $this->assertArrayHasKey('pathway', $item);
        $this->assertEquals($pathway_data->cg2->get_path_instance_id(), $item['pathway']['instance_id']);
        $this->assertEquals('criteria_group', $item['pathway']['pathway_type']);

        $third_result = $data[2];
        $this->assertArrayHasKey('scale_value', $third_result);
        $this->assertEquals([
            'name' => 'Assigned',
            'numericscore' => null,
            'proficient' => false
        ], $third_result['scale_value']);

        $this->assertArrayHasKey('items', $third_result);
        $this->assertCount(1, $third_result['items']);
        $item = $third_result['items'][0];
        $this->assertEquals(false, $item['achieved']);
        $this->assertNull($item['date_achieved']);
        $this->assertArrayHasKey('pathway', $item);
        $this->assertEquals($cg3->get_path_instance_id(), $item['pathway']['instance_id']);
        $this->assertEquals('criteria_group', $item['pathway']['pathway_type']);
    }

    protected function create_data() {
        $assign_generator = $this->competency_generator()->assignment_generator();

        $data = new class() {
            public $scale;
            public $fw1;
            public $comp1;
            public $assignment1;
        };

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator =  $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        $data->scale = $hierarchy_generator->create_scale('comp');

        $data->fw1 = $hierarchy_generator->create_comp_frame(['scale' => $data->scale->id]);

        $data->comp1 = $this->competency_generator()->create_competency(null, $data->fw1->id, [
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

    private function create_achievement_paths_data($data) {
        $generator = $this->getDataGenerator();
        $competency_generator = $this->competency_generator();
        $criteria_generator = $this->criteria_generator();

        $pathway_data = new class {
            public $cg1, $cg2;
            public $course1, $course2;
            public $scale_value1, $scale_value2, $scale_value3;
        };

        /** @var scale_value[]|\core\orm\collection $scale_values */
        $scale_values = $data->comp1->scale->sorted_values_high_to_low;
        $pathway_data->scale_value1 = $scale_values->shift();
        $pathway_data->scale_value2 = $scale_values->shift();
        $pathway_data->scale_value3 = $scale_values->shift();

        $pathway_data->course1 = $generator->create_course([
            'shortname' => "Course 1",
            'fullname' => "Course 1",
            'enablecompletion' => true,
        ]);

        $cc1 = $criteria_generator->create_coursecompletion([
            'aggregation' => [
                'method' => criterion::AGGREGATE_ANY_N,
                'req_items' => 1,
            ],
            'courseids' => [$pathway_data->course1->id],
        ]);

        $pathway_data->cg1 = $competency_generator->create_criteria_group($data->comp1, [$cc1], $pathway_data->scale_value1, 1);

        $pathway_data->course2 = $generator->create_course([
            'shortname' => "Course 2",
            'fullname' => "Course 2",
            'enablecompletion' => true,
        ]);

        $cc2 = $criteria_generator->create_coursecompletion([
            'aggregation' => [
                'method' => criterion::AGGREGATE_ANY_N,
                'req_items' => 1,
            ],
            'courseids' => [$pathway_data->course2->id],
        ]);

        $pathway_data->cg2 = $competency_generator->create_criteria_group($data->comp1, [$cc2], $pathway_data->scale_value2, 2);

        return $pathway_data;
    }

    /**
     * Complete the given course for the given user
     *
     * @param int $user_id
     * @param int $course_id
     */
    protected function complete_course(int $user_id, int $course_id) {
        global $CFG;
        require_once($CFG->dirroot . '/completion/completion_completion.php');

        $completion = new completion_completion(['userid' => $user_id, 'course' => $course_id]);
        $completion->mark_complete();
    }

    /**
     * Aggregate all competencies
     */
    protected function aggregate() {
        (new competency_aggregation_all())->execute();
    }

    /**
     * Get hierarchy specific generator
     *
     * @return \totara_competency\testing\generator
     */
    protected function competency_generator() {
        return \totara_competency\testing\generator::instance();
    }

    /**
     * Get criteria specific generator
     *
     * @return \totara_criteria\testing\generator
     */
    protected function criteria_generator() {
        return \totara_criteria\testing\generator::instance();
    }

    private function activate_additional_pathways() {
        global $CFG;
        require_once $CFG->dirroot.'/totara/competency/tests/fixtures/fake_multivalue_type.php';
        require_once $CFG->dirroot.'/totara/competency/tests/fixtures/fake_singlevalue_type.php';

        plugin_types::enable_plugin('fake_multivalue_type', 'pathway', 'totara_competency');
        plugin_types::enable_plugin('fake_singlevalue_type', 'pathway', 'totara_competency');
    }

}
