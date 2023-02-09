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

use core_phpunit\testcase;
use totara_competency\entity\assignment;
use totara_competency\expand_task;
use totara_core\advanced_feature;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\scale;
use totara_hierarchy\entity\scale_value;
use totara_job\job_assignment;

/**
 * This is an integration test with multiple users assigned to multiple competencies
 * It verifies over the competency / criteria boundaries to ensure the correct data is
 * created on all levels
 *
 * Test descriptions are defined in https://docs.google.com/spreadsheets/d/1rjnFZtI-ZJZCE8AmJjmiXtmU9S1_uIld_swteRyIKgA/edit#gid=0
 *
 * @group totara_competency
 */
abstract class totara_competency_integration_aggregation_base_testcase extends testcase {

    private $num_users = 10;
    private $num_courses = 10;

    protected $assignments;

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        global $CFG;
        require_once($CFG->dirroot . '/completion/completion_completion.php');
    }

    protected function setUp(): void {
        parent::setUp();
        advanced_feature::enable('competency_assignment');
    }

    protected function tearDown(): void {
        parent::tearDown();
        $this->assignments = null;
    }

    protected function setup_data() {
        $this->setAdminUser();

        $data = new class() {
            /** @var scale */
            public $scale;
            /** @var scale_value[] */
            public $scalevalues;
            /** @var competency[] */
            public $competencies = [];
            public $users = [];
            public $courses = [];
            /** @var assignment[]  */
            public $assignments = [];

            /** @var testing_data_generator $generator */
            public $generator;
            /** @var totara_hierarchy_generator $hierarchy_generator */
            public $hierarchy_generator;
            /** @var totara_competency_generator $competency_generator */
            public $competency_generator;
            /** @var totara_criteria_generator $criteria_generator */
            public $criteria_generator;

        };

        $data->generator = $this->getDataGenerator();
        $data->hierarchy_generator = $data->generator->get_plugin_generator('totara_hierarchy');
        $data->competency_generator = $data->generator->get_plugin_generator('totara_competency');
        $data->criteria_generator = $data->generator->get_plugin_generator('totara_criteria');

        $data->scale = $data->hierarchy_generator->create_scale(
            'comp',
            ['name' => 'Test scale', 'description' => 'Test scale'],
            [
                5 => ['name' => 'No clue', 'proficient' => 0, 'sortorder' => 5, 'default' => 1],
                4 => ['name' => 'Learning', 'proficient' => 0, 'sortorder' => 4, 'default' => 0],
                3 => ['name' => 'Getting there', 'proficient' => 0, 'sortorder' => 3, 'default' => 0],
                2 => ['name' => 'Almost there', 'proficient' => 1, 'sortorder' => 2, 'default' => 0],
                1 => ['name' => 'Arrived', 'proficient' => 1, 'sortorder' => 1, 'default' => 0],
            ]
        );

        $framework = $data->hierarchy_generator->create_comp_frame(['scale' => $data->scale->id]);

        $data->scale = new scale($data->scale->id);
        $data->scalevalues = $data->scale
            ->sorted_values_high_to_low
            ->key_by('sortorder')
            ->all(true);

        $competencies_and_parents = [
            1 => 0,
            2 => 1,
            3 => 0,
            4 => 3,
            5 => 3
        ];

        foreach ($competencies_and_parents as $idx => $parent_idx) {
            $comp_data = [
                'frameworkid' => $framework->id,
                'parentid' => empty($parent_idx) ? 0 : $data->competencies[$parent_idx]->id,
            ];
            $comp = $data->hierarchy_generator->create_comp($comp_data);
            $data->competencies[$idx] = new competency($comp);
        }

        // Users with job assignments
        $data->users['manager'] = $data->generator->create_user(['username' => 'manager']);
        $data->users['appraiser'] = $data->generator->create_user(['username' => 'appraiser']);

        // Job assignments
        $managerja = job_assignment::create_default($data->users['manager']->id, [
            'fullname' => 'Manager job',
            'idnumber' => 'MANAGERJOB',
        ]);

        for ($i = 1; $i <= $this->num_users; $i++) {
            $data->users[$i] = $data->generator->create_user(['username' => "user{$i}"]);

            // All users get manager as manager and appraiser as appraiser
            job_assignment::create_default($data->users[$i]->id, [
                'managerjaid' => $managerja->id,
                'fullname' => 'Managed by manager',
                'idnumber' => "User{$i}managed",
            ]);

            job_assignment::create_default($data->users[$i]->id, [
                'appraiserid' => $data->users['appraiser']->id,
                'fullname' => 'Appraised by appraiser',
                'idnumber' => "User{$i}appraised",
            ]);
        }


        // Create courses and enroll all users in all courses
        for ($i = 1; $i <= $this->num_courses; $i++) {
            $record = [
                'shortname' => "Course $i",
                'fullname' => "Course $i",
                'enablecompletion' => true,
            ];

            $data->courses[$i] = $data->generator->create_course($record);
            foreach ($data->users as $user) {
                $data->generator->enrol_user($user->id, $data->courses[$i]->id);
            }
        }

        return $data;
    }

    protected function assign_users_to_competencies(array $to_assign): array {
        global $DB;

        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $assign_generator = $competency_generator->assignment_generator();

        $assignment_ids = [];
        foreach ($to_assign as $user_comp) {
            $key = implode('_', [$user_comp['competency_id'], $user_comp['user_id']]);
            $this->assignments[$key] = $assign_generator->create_user_assignment($user_comp['competency_id'], $user_comp['user_id']);
            $assignment_ids[] = $this->assignments[$key]->id;
        }

        $expand_task = new expand_task($DB);
        $expand_task->expand_all();

        return $assignment_ids;
    }

    protected function unassign_users_from_competencies(array $to_unassign) {
        foreach ($to_unassign as $user_comp) {
            $key = implode('_', [$user_comp['competency_id'], $user_comp['user_id']]);
            if (!isset($this->assignments[$key])) {
                throw new \coding_exception('Unknown competency/user assignment combination');
            }

            $this->assignments[$key]->force_delete();
            unset($this->assignments[$key]);
        }
    }

    /**
     * Data provider for all tests. Define which task to execute
     */
    public function task_to_execute_data_provider() {
        return [
            ['totara_competency\task\competency_aggregation_all'],
            ['totara_competency\task\competency_aggregation_queue'],
        ];
    }

    /**
     * @param array $expected_rows
     */
    protected function verify_item_records(array $expected_rows) {
        global $DB;

        $sql =
            "SELECT tcir.*, tci.item_id
               FROM {totara_criteria_item_record} tcir
               JOIN {totara_criteria_item} tci
                 ON tci.id = tcir.criterion_item_id";
        $actual_rows = $DB->get_records_sql($sql, []);

        foreach ($actual_rows as $actual_row) {
            $expected_row = reset($expected_rows);
            while ($expected_row !== false) {
                if ((int)$actual_row->item_id == $expected_row['item_id'] && (int)$actual_row->user_id == $expected_row['user_id']) {
                    $this->assertEquals($expected_row['criterion_met'], $actual_row->criterion_met);

                    $key = key($expected_rows);
                    if (isset($expected_row['num_occurrences']) && $expected_row['num_occurrences'] > 1) {
                        $expected_rows[$key]['num_occurrences'] -= 1;
                    } else {
                        unset($expected_rows[$key]);
                    }

                    break;
                }

                $expected_row = next($expected_rows);
            }
        }

        $this->assertSame(0, count($expected_rows));
    }

    /**
     * @param $expected_rows
     * @return array Array of pathway achievement ids. Key matches the matching expected row's key
     */
    protected function verify_pathway_achievements($expected_rows): array {
        global $DB;

        $actual_ids = [];

        $actual_rows = $DB->get_records('totara_competency_pathway_achievement');
        $this->assertSame(count($expected_rows), count($actual_rows));

        foreach ($actual_rows as $actual_row) {
            foreach ($expected_rows as $key => $expected_row) {
                if ((int)$actual_row->pathway_id == $expected_row['pathway_id']
                    && (int)$actual_row->user_id == $expected_row['user_id']
                    && (int)$actual_row->status == $expected_row['status']
                    && (int)$actual_row->scale_value_id == $expected_row['scale_value_id']
                    && (!isset($expected_row['related_info']) ||
                        $actual_row->related_info == json_encode($expected_row['related_info']))) {
                    $actual_ids[$key] = $actual_row->id;
                    unset($expected_rows[$key]);
                    break;
                }
            }
        }

        $this->assertSame(0, count($expected_rows));

        return $actual_ids;
    }

    /**
     * @param $expected_rows
     */
    protected function verify_competency_achievements($expected_rows) {
        global $DB;

        $actual_rows = $DB->get_records('totara_competency_achievement', []);
        $this->assertSame(count($expected_rows), count($actual_rows));

        foreach ($actual_rows as $actual_row) {
            foreach ($expected_rows as $key => $expected_row) {
                if ((int)$actual_row->competency_id == $expected_row['competency_id']
                    && (int)$actual_row->user_id == $expected_row['user_id']
                    && (int)$actual_row->status == $expected_row['status']
                    && (int)$actual_row->scale_value_id == $expected_row['scale_value_id']
                    && (int)$actual_row->proficient == $expected_row['proficient']
                    && (!isset($expected_row['assignment_id']) || $actual_row->assignment_id == $expected_row['assignment_id'])) {
                    if (!isset($expected_row['via'])
                        || $this->competency_achievement_via_matches($actual_row->id, $expected_row['via'])) {
                        unset($expected_rows[$key]);
                        break;
                    }
                }
            }
        }

        $this->assertEmpty($expected_rows);
    }

    /**
     * @param int $comp_achievement_id
     * @param array $expected_via_pathway_ids
     * @return bool
     */
    private function competency_achievement_via_matches(int $comp_achievement_id, array $expected_via_pathway_ids): bool {
        global $DB;

        $via_records = $DB->get_records('totara_competency_achievement_via', ['comp_achievement_id' => $comp_achievement_id]);
        if (count($via_records) != count($expected_via_pathway_ids)) {
            return false;
        }

        foreach ($via_records as $record) {
            foreach ($expected_via_pathway_ids as $key => $pathway_achievement_id) {
                if ($record->pathway_achievement_id == $pathway_achievement_id) {
                    unset($expected_via_pathway_ids[$key]);
                    break;
                }
            }
        }

        return count($expected_via_pathway_ids) == 0;
    }
}
