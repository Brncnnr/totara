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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_criteria
 */

use criteria_childcompetency\childcompetency;
use criteria_coursecompletion\coursecompletion;
use criteria_linkedcourses\linkedcourses;
use criteria_onactivate\onactivate;
use criteria_othercompetency\othercompetency;
use totara_competency\linked_courses;
use totara_criteria\criterion;

/**
 * @group totara_competency
 */
class totara_criteria_generator_testcase extends advanced_testcase {

    private function setup_data() {
        $data = new class() {
            public $courses;
        };

        $prefix = 'Course ';
        for ($i = 1; $i <= 5; $i++) {
            $record = [
                'shortname' => $prefix . $i,
                'fullname' => $prefix . $i,
            ];

            $data->courses[$i] = $this->getDataGenerator()->create_course($record);
        }

        return $data;
    }

    /**
     * Test coursecompletion generator with single course
     */
    public function test_coursecompletion_generator_all_single_course() {
        $data = $this->setup_data();

        /** @var \totara_criteria\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $record = ['courseids' => [$data->courses[1]->id]];
        $cc = $generator->create_coursecompletion($record);

        $this->validate_coursecompletion($cc, $record);
    }

    /**
     * Test coursecompletion generator with multiple courses - ALL aggregation
     */
    public function test_coursecompletion_generator_all_multi_courses() {
        $data = $this->setup_data();

        /** @var \totara_criteria\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $record = [
            'aggregation' => ['method' => criterion::AGGREGATE_ALL],
            'courseids' => [$data->courses[1]->id, $data->courses[2]->id, $data->courses[3]->id],
        ];

        $cc = $generator->create_coursecompletion($record);

        $this->validate_coursecompletion($cc, $record);
    }

    /**
     * Test coursecompletion generator with multiple courses - ANY aggregation
     */
    public function test_coursecompletion_generator_any_multi_courses() {
        $data = $this->setup_data();

        /** @var \totara_criteria\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $record = [
            'aggregation' => [
                'method' => criterion::AGGREGATE_ANY_N,
                'req_items' => 2,
            ],
            'courseids' => [$data->courses[2]->id, $data->courses[3]->id, $data->courses[4]->id, $data->courses[5]->id],
        ];

        $cc = $generator->create_coursecompletion($record);

        $this->validate_coursecompletion($cc, $record);
    }

    /**
     * Test linkedcourses generator defaults
     */
    public function test_linkedcourses_generator_defaults() {
        /** @var \totara_criteria\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $lc = $generator->create_linkedcourses(['competency' => 1]);

        $record = [
            'aggregation' => [
                'method' => criterion::AGGREGATE_ALL,
            ],
            'competency' => 1,
        ];

        $this->validate_linkedcourses($lc, $record);
    }

    /**
     * Test linkedcourses generator requiring all mandatory linked courses
     */
    public function test_linkedcourses_generator_all_mandatory() {
        /** @var \totara_criteria\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $record = [
            'aggregation' => [
                'method' => criterion::AGGREGATE_ALL,
                'req_items' => 1,
            ],
            'competency' => 1,
        ];

        $lc = $generator->create_linkedcourses($record);
        $this->validate_linkedcourses($lc, $record);
    }

    /**
     * Test linkedcourses generator requiring some linked courses
     */
    public function test_linkedcourses_generator_some() {
        /** @var \totara_criteria\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $record = [
            'aggregation' => [
                'method' => criterion::AGGREGATE_ANY_N,
                'req_items' => 2,
            ],
            'competency' => 1,
        ];

        $lc = $generator->create_linkedcourses($record);

        $this->validate_linkedcourses($lc, $record);
    }


    /**
     * Test linkedcourses generator with courses linked to the competency
     */
    public function test_linkedcourses_generator_with_courses() {
        global $CFG;
        require_once($CFG->dirroot . '/totara/plan/lib.php');

        /** @var \totara_competency\testing\generator $comp_generator */
        $comp_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $comp = $comp_generator->create_competency();

        linked_courses::set_linked_courses($comp->id, [
            ['id' => 1001, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
            ['id' => 1002, 'linktype' => linked_courses::LINKTYPE_OPTIONAL],
            ['id' => 1003, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
        ]);

        /** @var \totara_criteria\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $record = [
            'aggregation' => [
                'method' => criterion::AGGREGATE_ANY_N,
                'req_items' => 2,
            ],
            'competency' => $comp->id,
        ];

        $lc = $generator->create_linkedcourses($record);

        $record['items'] = [
            ['item_type' => 'course', 'item_id' => 1001],
            ['item_type' => 'course', 'item_id' => 1002],
            ['item_type' => 'course', 'item_id' => 1003],
        ];

        $this->validate_linkedcourses($lc, $record);
    }


    /**
     * Test onactivate generator
     */
    public function test_onactivate_generator() {
        /** @var \totara_criteria\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');
        $oa = $generator->create_onactivate(['competency' => 1]);
        $this->validate_onactivate($oa);
    }


    /**
     * Test childcompetency generator defaults
     */
    public function test_childcompetency_generator_defaults() {
        /** @var \totara_criteria\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $cc = $generator->create_childcompetency(['competency' => 1]);

        $record = [
            'aggregation' => [
                'method' => criterion::AGGREGATE_ALL,
            ],
            'competency' => 1,
        ];

        $this->validate_childcompetency($cc, $record);
    }

    /**
     * Test childcompetency generator requiring all child competencies
     */
    public function test_childcompetency_generator_all_mandatory() {
        /** @var \totara_criteria\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $record = [
            'aggregation' => [
                'method' => criterion::AGGREGATE_ALL,
                'req_items' => 1,
            ],
            'competency' => 12,
        ];

        $cc = $generator->create_childcompetency($record);

        $this->validate_childcompetency($cc, $record);
    }

    /**
     * Test childcompetency generator requiring some child competencies
     */
    public function test_childcompetency_generator_some() {
        /** @var \totara_criteria\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $record = [
            'aggregation' => [
                'method' => criterion::AGGREGATE_ANY_N,
                'req_items' => 2,
            ],
            'competency' => 123,
        ];

        $cc = $generator->create_childcompetency($record);

        $this->validate_childcompetency($cc, $record);
    }

    /**
     * Test childcompetency generator with existing child competencies
     */
    public function test_childcompetency_generator_with_children() {
        /** @var \totara_competency\testing\generator $comp_generator */
        $comp_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');

        $parent_comp = $comp_generator->create_competency();
        $child_comp = [];
        foreach ([1, 2] as $child) {
            $child_comp[$child] = $comp_generator->create_competency(null, null, ['parentid' => $parent_comp->id]);
        }

        /** @var \totara_criteria\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $record = [
            'aggregation' => [
                'method' => criterion::AGGREGATE_ANY_N,
                'req_items' => 2,
            ],
            'competency' => $parent_comp->id,
        ];

        $cc = $generator->create_childcompetency($record);

        $record['items'] = [
            ['item_type' => 'competency', 'item_id' => $child_comp[1]->id],
            ['item_type' => 'competency', 'item_id' => $child_comp[2]->id],
        ];

        $this->validate_childcompetency($cc, $record);
    }

    /**
     * Test othercompetency generator with single competency
     */
    public function test_othercompetency_generator_all_single_competency() {
        /** @var \totara_competency\testing\generator $comp_generator */
        $comp_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $comp1 = $comp_generator->create_competency();

        /** @var \totara_criteria\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $record = ['competencyids' => [$comp1->id]];
        $oc = $generator->create_othercompetency($record);

        $this->validate_othercompetency($oc, $record);
    }

    /**
     * Test othercompetency generator with multiple competencies - ALL aggregation
     */
    public function test_othercompetency_generator_all_multi_competencies() {
        /** @var \totara_competency\testing\generator $comp_generator */
        $comp_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $comp1 = $comp_generator->create_competency();
        $comp2 = $comp_generator->create_competency();
        $comp3 = $comp_generator->create_competency();

        /** @var \totara_criteria\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $record = [
            'aggregation' => ['method' => criterion::AGGREGATE_ALL],
            'competencyids' => [$comp1->id, $comp2->id, $comp3->id],
        ];

        $oc = $generator->create_othercompetency($record);

        $this->validate_othercompetency($oc, $record);
    }

    /**
     * Test othercompetency generator with multiple courses - ANY aggregation
     */
    public function test_othercompetency_generator_any_multi_courses() {
        /** @var \totara_competency\testing\generator $comp_generator */
        $comp_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $comp1 = $comp_generator->create_competency();
        $comp2 = $comp_generator->create_competency();
        $comp3 = $comp_generator->create_competency();

        /** @var \totara_criteria\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $record = [
            'aggregation' => [
                'method' => criterion::AGGREGATE_ANY_N,
                'req_items' => 2,
            ],
            'competencyids' => [$comp1->id, $comp2->id, $comp3->id],
        ];

        $oc = $generator->create_othercompetency($record);

        $this->validate_othercompetency($oc, $record);
    }


     /**
     * Validate the generated coursecompletion against the source record
     *
     * @param  coursecompletion $cc Generated coursecompletion
     * @param  array Srecord Source record
     */
    private function validate_coursecompletion(coursecompletion $cc, array $record) {
        global $DB;

        $rows = $DB->get_records('totara_criteria');
        $this->assertEquals(1, count($rows));
        $row = reset($rows);
        $this->assertEquals($cc->get_id(), $row->id);

        // Aggregation
        if (isset($record['aggregation'])) {
            $this->assertEquals($record['aggregation']['method'] ?? criterion::AGGREGATE_ALL, $cc->get_aggregation_method());

            if (isset($record['aggregation']['req_items'])) {
                $this->assertEquals(['req_items' => $record['aggregation']['req_items']], $cc->get_aggregation_params());
            }
        }

        $rows = $DB->get_records('totara_criteria_item');
        $this->assertEquals(count($record['courseids']), count($rows));

        foreach ($rows as $row) {
            $this->assertEquals('course', $row->item_type);
            $this->assertTrue(in_array($row->item_id, $record['courseids']));
            $this->assertEquals($cc->get_id(), $row->criterion_id);
        }
    }

    /**
     * Validate the generated linkedcourses against the source record
     *
     * @param  linkedcourses $lc Generated linkedcourses
     * @param  array Srecord Source record
     */
    private function validate_linkedcourses(linkedcourses $lc, array $record) {
        global $DB;

        $rows = $DB->get_records('totara_criteria');
        $this->assertEquals(1, count($rows));
        $row = reset($rows);
        $this->assertEquals($lc->get_id(), $row->id);

        // Aggregation
        if (isset($record['aggregation'])) {
            $this->assertEquals($record['aggregation']['method'] ?? criterion::AGGREGATE_ALL, $lc->get_aggregation_method());

            if (isset($record['aggregation']['req_items'])) {
                $this->assertEquals(['req_items' => $record['aggregation']['req_items']], $lc->get_aggregation_params());
            }
        }

        // Competency
        $rows = $DB->get_records('totara_criteria_metadata');
        $this->assertEquals(1, count($rows));

        $metadata = $lc->get_metadata();
        $this->assertEquals($record['competency'], $metadata[criterion::METADATA_COMPETENCY_KEY]);

        if (isset($record['items'])) {
            $this->validate_items($record['items']);
        }
    }

    /**
     * Validate the generated onactivate criterion
     *
     * @param  onactivate $oa Generated criterion
     */
    private function validate_onactivate(onactivate $oa) {
        global $DB;

        $row = $DB->get_record('totara_criteria', []);
        $this->assertEquals($oa->get_id(), $row->id);

        $row = $DB->get_record('totara_criteria_item', []);
        $this->assertEquals('onactivate', $row->item_type);
        $this->assertEquals($oa->get_competency_id(), $row->item_id);
    }

    /**
     * Validate the generated childcompetency against the source record
     *
     * @param  childcompetency $cc Generated childcompetency
     * @param  array Srecord Source record
     */
    private function validate_childcompetency(childcompetency $cc, array $record) {
        global $DB;

        $row = $DB->get_record('totara_criteria', []);
        $this->assertEquals($cc->get_id(), $row->id);

        // Aggregation
        if (isset($record['aggregation'])) {
            $this->assertEquals($record['aggregation']['method'] ?? criterion::AGGREGATE_ALL, $cc->get_aggregation_method());

            if (isset($record['aggregation']['req_items'])) {
                $this->assertEquals(['req_items' => $record['aggregation']['req_items']], $cc->get_aggregation_params());
            }
        }

        $metadata = $cc->get_metadata();
        $this->assertEquals($record['competency'], $metadata[criterion::METADATA_COMPETENCY_KEY]);

        // Items
        if (isset($record['items'])) {
            $this->validate_items($record['items']);
        }
    }

     /**
     * Validate the generated othercompetency against the source record
     *
     * @param  othercompetency $oc Generated othercompetency
     * @param  array Srecord Source record
     */
    private function validate_othercompetency(othercompetency $oc, array $record) {
        global $DB;

        $rows = $DB->get_records('totara_criteria');
        $this->assertEquals(1, count($rows));
        $row = reset($rows);
        $this->assertEquals($oc->get_id(), $row->id);

        // Aggregation
        if (isset($record['aggregation'])) {
            $this->assertEquals($record['aggregation']['method'] ?? criterion::AGGREGATE_ALL, $oc->get_aggregation_method());

            if (isset($record['aggregation']['req_items'])) {
                $this->assertEquals(['req_items' => $record['aggregation']['req_items']], $oc->get_aggregation_params());
            }
        }

        $rows = $DB->get_records('totara_criteria_item');
        $this->assertEquals(count($record['competencyids']), count($rows));

        foreach ($rows as $row) {
            $this->assertEquals('competency', $row->item_type);
            $this->assertTrue(in_array($row->item_id, $record['competencyids']));
            $this->assertEquals($oc->get_id(), $row->criterion_id);
        }
    }


    /**
     * Validate that the expected records were created in totara_criteria_item
     *
     * @param array $expected_items
     */
    private function validate_items(array $expected_items) {
        global $DB;

        $item_rows = $DB->get_records('totara_criteria_item');
        $this->assertEquals(count($expected_items), count($item_rows));
        foreach ($item_rows as $row) {
            foreach ($expected_items as $key => $expected) {
                if ($row->item_type == $expected['item_type'] && $row->item_id == $expected['item_id']) {
                    unset($expected_items[$key]);
                    break;
                }
            }
        }
        $this->assertEmpty($expected_items);
    }
}
