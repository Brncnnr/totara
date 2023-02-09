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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package pathway_criteria_group
 */

use core\entity\course as course_entity;
use core_phpunit\testcase;
use criteria_coursecompletion\coursecompletion;
use pathway_criteria_group\aggregation_helper;
use pathway_criteria_group\criteria_group;
use totara_competency\expand_task;
use totara_competency\linked_courses;
use totara_core\advanced_feature;
use totara_hierarchy\entity\scale_value;

/**
 * @group totara_competency
 */
class pathway_criteria_group_aggregation_helper_testcase extends testcase {

    /**
     * @param bool $assignments_enabled
     * @return object instance of anonymous class
     */
    private function setup_data(bool $assignments_enabled) {
        if ($assignments_enabled) {
            advanced_feature::enable('competency_assignment');
        } else {
            advanced_feature::disable('competency_assignment');
        }

        global $DB;

        // If assignments are not enabled some of the data needs to be set up differently,
        // see comments further down below
        $assignments_enabled = advanced_feature::is_enabled('competency_assignment');

        $data = new class {
            public $competency_data = [];
            public $courses = [];
            public $users = [];

        };

        // Redirect the events during setup
        $sink = $this->redirectEvents();

        for ($i = 1; $i <= 6; $i++) {
            $data->users[$i] = $this->getDataGenerator()->create_user();
            $data->courses[$i] = $this->getDataGenerator()->create_course(['enablecompletion' => true]);
        }

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        $scale = $hierarchygenerator->create_scale(
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
        $rows = $DB->get_records('comp_scale_values', ['scaleid' => $scale->id], 'sortorder');
        foreach ($rows as $row) {
            $scalevalues[$row->sortorder] = new scale_value($row->id);
        }

        $framework = $hierarchygenerator->create_comp_frame(['scale' => $scale->id]);

        $competency_generator = \totara_competency\testing\generator::instance();
        $assignment_generator = $competency_generator->assignment_generator();

        $to_create = [
            'Comp A' => [
                'user_keys' => [1, 2],
                'course_keys' => [1],
            ],
            'Comp B' => [
                'user_keys' => [1, 3],
                'course_keys' => [2, 3],
            ],
            'Comp C' => [
                'user_keys' => [3],
                'course_keys' => [4],
            ],
            'Comp D' => [
                'user_keys' => [1, 2, 3],
                'course_keys' => [],
            ],
        ];

        foreach ($to_create as $competency_name => $competency_values) {
            $competency = $competency_generator->create_competency($competency_name, $framework->id);
            $criteria_ids = [];
            $linked_courses = [];

            // Each coursecompletion criterion is created in its own pathway
            foreach ($competency_values['course_keys'] as $course_idx) {
                $criterion = new coursecompletion();
                $criterion->set_aggregation_method(coursecompletion::AGGREGATE_ALL);
                $criterion->add_items([$data->courses[$course_idx]->id]);

                $pathway = new criteria_group();
                $pathway->set_competency($competency);
                $pathway->set_scale_value($scalevalues[3]);
                $pathway->add_criterion($criterion);
                $pathway->save();

                $criteria_ids[$course_idx] = $criterion->get_id();

                $linked_courses[] = [
                    'id' => $data->courses[$course_idx]->id,
                    'linktype' => linked_courses::LINKTYPE_MANDATORY
                ];
            }

            // We do need the courses linked for learn-only aggregation to pick it up
            // as in learn-only there are no assignments just completions of courses linked to a competency
            if (!$assignments_enabled) {
                linked_courses::set_linked_courses($competency->id, $linked_courses);
            }

            foreach ($competency_values['user_keys'] as $user_idx) {
                // Only create assignments if assignments feature is turned on
                // Otherwise create completion records for the courses
                if ($assignments_enabled) {
                    $assignment_generator->create_user_assignment($competency->id, $data->users[$user_idx]->id);
                } else {
                    foreach ($competency_values['course_keys'] as $course_idx) {
                        $completion = new completion_completion([
                            'course' => $data->courses[$course_idx]->id,
                            'userid' => $data->users[$user_idx]->id
                        ]);
                        $completion->mark_complete();
                    }
                }
            }

            $data->competency_data[$competency_name] = [
                'competency_id' => $competency->id,
                'criteria_ids' => $criteria_ids,
            ];
        }

        if ($assignments_enabled) {
            $expand_task = new expand_task($DB);
            $expand_task->expand_all();
        }
        $sink->close();

        return $data;
    }

    public function assignments_enabled_dataprovider(): array {
        return [
            [ true ],
            [ false ]
        ];
    }

    /**
     * Test no criteria_ids provided
     *
     * @dataProvider assignments_enabled_dataprovider
     * @param bool $assignments_enabled
     */
    public function test_mark_from_criteria_empty_list(bool $assignments_enabled) {
        global $DB;

        $data = $this->setup_data($assignments_enabled);
        $this->assertSame(0, $DB->count_records('totara_competency_aggregation_queue', []));

        // No criteria_ids
        aggregation_helper::mark_for_reaggregate_from_criteria([], $data->users[3]->id);
        $this->verify_queue([]);
    }

    /**
     * Test single competency, single criterion
     *
     * @dataProvider assignments_enabled_dataprovider
     */
    public function test_mark_from_criteria_single_competency_single_criterion_assigned_user(bool $assignments_enabled) {
        $data = $this->setup_data($assignments_enabled);

        $criteria_ids = $data->competency_data['Comp A']['criteria_ids'];
        aggregation_helper::mark_for_reaggregate_from_criteria([$data->users[1]->id => $criteria_ids]);

        $this->verify_queue([
            [
                'user_id' => $data->users[1]->id,
                'competency_id' => $data->competency_data['Comp A']['competency_id'],
            ]
        ]);
    }

    /**
     * Test single criterion, user not assigned
     *
     * @dataProvider assignments_enabled_dataprovider
     * @param bool $assignments_enabled
     */
    public function test_mark_from_criteria_single_criterion_not_assigned_user(bool $assignments_enabled) {
        $data = $this->setup_data($assignments_enabled);

        $criteria_ids = $data->competency_data['Comp A']['criteria_ids'];
        aggregation_helper::mark_for_reaggregate_from_criteria([$data->users[3]->id => $criteria_ids]);

        $this->verify_queue([]);
    }

    /**
     * Test single competency, multiple criteria, user assigned
     *
     * @dataProvider assignments_enabled_dataprovider
     * @param bool $assignments_enabled
     */
    public function test_mark_from_criteria_single_competencies_multiple_criteria_assigned_user(bool $assignments_enabled) {
        $data = $this->setup_data($assignments_enabled);

        $criteria_ids = $data->competency_data['Comp B']['criteria_ids'];
        aggregation_helper::mark_for_reaggregate_from_criteria([$data->users[1]->id => $criteria_ids]);

        // Expecting ad-hoc task with 1 competency id
        $this->verify_queue([
            [
                'user_id' => $data->users[1]->id,
                'competency_id' => $data->competency_data['Comp B']['competency_id'],
            ]
        ]);
    }

    /**
     * Test single competency, multiple criteria, user not assigned
     *
     * @dataProvider assignments_enabled_dataprovider
     * @param bool $assignments_enabled
     */
    public function test_mark_from_criteria_single_competencies_multiple_criteria_not_assigned_user(bool $assignments_enabled) {
        $data = $this->setup_data($assignments_enabled);

        $criteria_ids = $data->competency_data['Comp B']['criteria_ids'];
        aggregation_helper::mark_for_reaggregate_from_criteria([$data->users[2]->id => $criteria_ids]);

        $this->verify_queue([]);
    }

    /**
     * Test multiple competencies, multiple criteria, user assigned in some
     *
     * @dataProvider assignments_enabled_dataprovider
     * @param bool $assignments_enabled
     */
    public function test_mark_from_criteria_multiple_competencies_multiple_criteria(bool $assignments_enabled) {
        $data = $this->setup_data($assignments_enabled);

        $criteria_ids = array_merge(
            $data->competency_data['Comp A']['criteria_ids'],
            $data->competency_data['Comp B']['criteria_ids'],
            $data->competency_data['Comp C']['criteria_ids']
        );
        aggregation_helper::mark_for_reaggregate_from_criteria([$data->users[3]->id => $criteria_ids]);

        $this->verify_queue([
            [
                'user_id' => $data->users[3]->id,
                'competency_id' => $data->competency_data['Comp B']['competency_id'],
            ],
            [
                'user_id' => $data->users[3]->id,
                'competency_id' => $data->competency_data['Comp C']['competency_id'],
            ],
        ]);
    }

    /**
     * Test multiple competencies, multiple criteria, all assigned users
     *
     * @dataProvider assignments_enabled_dataprovider
     * @param bool $assignments_enabled
     */
    public function test_mark_from_criteria_multiple_competencies_multiple_criteria_all_users(bool $assignments_enabled) {
        $data = $this->setup_data($assignments_enabled);

        $user_criteria_ids = [
            $data->users[1]->id => array_merge(
                $data->competency_data['Comp A']['criteria_ids'],
                $data->competency_data['Comp B']['criteria_ids']
            ),
            $data->users[2]->id => $data->competency_data['Comp A']['criteria_ids'],
            $data->users[3]->id => array_merge(
                $data->competency_data['Comp B']['criteria_ids'],
                $data->competency_data['Comp C']['criteria_ids']
            ),
        ];

        aggregation_helper::mark_for_reaggregate_from_criteria($user_criteria_ids);

        $this->verify_queue([
            [
                'user_id' => $data->users[1]->id,
                'competency_id' => $data->competency_data['Comp A']['competency_id'],
            ],
            [
                'user_id' => $data->users[1]->id,
                'competency_id' => $data->competency_data['Comp B']['competency_id'],
            ],
            [
                'user_id' => $data->users[2]->id,
                'competency_id' => $data->competency_data['Comp A']['competency_id'],
            ],
            [
                'user_id' => $data->users[3]->id,
                'competency_id' => $data->competency_data['Comp B']['competency_id'],
            ],
            [
                'user_id' => $data->users[3]->id,
                'competency_id' => $data->competency_data['Comp C']['competency_id'],
            ],
        ]);
    }

     /**
     * Test changes in criteria validity
     *
     * @dataProvider assignments_enabled_dataprovider
     * @param bool $assignments_enabled
     */
    public function test_validate_and_mark_from_criteria(bool $assignments_enabled) {
        $data = $this->setup_data($assignments_enabled);

        $hook_sink = $this->redirectHooks();

        // If all stays valid, nothing should be queued
        $criteria_ids = [
            $data->competency_data['Comp A']['criteria_ids'][1],
            $data->competency_data['Comp C']['criteria_ids'][4],
        ];

        aggregation_helper::validate_and_mark_from_criteria($criteria_ids);
        $this->verify_queue([]);
        $this->assertSame(0, $hook_sink->count());

        // Now invalidate a criterion
        $course = new course_entity($data->courses[1]->id);
        $course->delete();

        $hook_sink->clear();

        aggregation_helper::validate_and_mark_from_criteria($criteria_ids);
        $this->verify_queue([
            [
                'user_id' => $data->users[1]->id,
                'competency_id' => $data->competency_data['Comp A']['competency_id'],
            ],
            [
                'user_id' => $data->users[2]->id,
                'competency_id' => $data->competency_data['Comp A']['competency_id'],
            ],
        ]);

        $hooks = $hook_sink->get_hooks();
        $this->assertCount(1, $hooks);
        $hook = reset($hooks);
        $this->assertTrue($hook instanceof \totara_competency\hook\competency_validity_changed);
        $expected = [$data->competency_data['Comp A']['competency_id']];
        $actual = $hook->get_competency_ids();
        $this->assertEquals($expected, $actual);
        $hook_sink->close();
    }

    private function verify_queue(array $expected_rows) {
        global $DB;

        $rows = $DB->get_records('totara_competency_aggregation_queue');
        $this->assertSame(count($expected_rows), count($rows));

        // Just checking user_id and competency_id
        foreach ($rows as $row) {
            foreach ($expected_rows as $key => $expected) {
                if ($row->competency_id == $expected['competency_id'] && $row->user_id == $expected['user_id']) {
                    unset($expected_rows[$key]);
                    break 1;
                }
            }
        }

        $this->assertSame(0, count($expected_rows));
    }

}
