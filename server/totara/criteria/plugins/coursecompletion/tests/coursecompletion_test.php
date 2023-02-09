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

use criteria_coursecompletion\coursecompletion;
use criteria_linkedcourses\linkedcourses;
use totara_criteria\criterion;
use totara_criteria\entity\criterion as criterion_entity;

/**
 * @group totara_competency
 */
class criteria_coursecompletion_testcase extends advanced_testcase {

    private function setup_data() {
        global $DB;

        // Insert some dummy data into totara_criterion and totara_criterion_item
        // Not using the data generator here as we are testing the functions used by the generator

        $data = new class() {
            /** @var [\stdClass] instancerows */
            public $instancerows = [];
            /** @var [\stdClass] itemrows */
            public $itemrows = [];
            /** @var [\stdClass] itemids */
            public $itemids = [];
        };

        $tests = [
            [
                'plugin_type' => 'coursecompletion',
                'aggregation_method' => coursecompletion::AGGREGATE_ALL,
                'item_ids' => [],
            ],
            [
                'plugin_type' => 'coursecompletion',
                'aggregation_method' => coursecompletion::AGGREGATE_ALL,
                'item_ids' => [100, 101, 102],
            ],
            [
                'plugin_type' => 'coursecompletion',
                'aggregation_method' => coursecompletion::AGGREGATE_ANY_N,
                'aggregation_params' => json_encode(['req_items' => 1]),
                'item_ids' => [102, 203, 204]
            ],
            [
                'plugin_type' => 'coursecompletion',
                'aggregation_method' => coursecompletion::AGGREGATE_ANY_N,
                'aggregation_params' => json_encode(['req_items' => 2]),
                'item_ids' => [303, 304, 305]
            ],
        ];

        foreach ($tests as $tst) {
            // First the criterion
            $tst['criterion_modified'] = time();

            $criterion_id = $DB->insert_record("totara_criteria", $tst, true, false);

            if (!empty($tst['item_ids'])) {
                // Add non-existing criterion_items
                foreach ($tst['item_ids'] as $course_id) {
                    $DB->insert_record(
                        'totara_criteria_item',
                        [
                            'criterion_id' => $criterion_id,
                            'item_type' => 'course',
                            'item_id' => $course_id,
                        ]
                    );
                }

                $params = ['criterion_id' => $criterion_id];
                $data->itemrows[$criterion_id] = $DB->get_records('totara_criteria_item', $params);
                $data->itemids[$criterion_id] = $DB->get_records_menu('totara_criteria_item', $params, '', 'id, item_id');

                $this->verify_saved_items($criterion_id, $tst['item_ids']);
            }
        }

        $data->instancerows = $DB->get_records('totara_criteria', ['plugin_type' => 'coursecompletion'], 'id');

        return $data;
    }

    /**
     * Verify the items existing in the database (linked as well as unlinked)
     *
     * @param int $criterion_id
     * @param array $expected_items
     */
    private function verify_saved_items(int $criterion_id, array $expected_items) {
        global $DB;

        $rows = $DB->get_records('totara_criteria_item', ['criterion_id' => $criterion_id]);
        $this->assertEquals(count($expected_items), count($rows));
        foreach ($rows as $row) {
            $this->assertTrue(in_array($row->item_id, $expected_items));
        }
    }

    /**
     * Verify the instance attributes
     *
     * @param stdClass $expected
     * @param linkedcourses $actual
     */
    private function verify_instance($expected, coursecompletion $actual) {
        $this->assertEquals($expected->id ?? 0, $actual->get_id());
        $this->assertEquals($expected->plugin_type ?? 'coursecompletion', $actual->get_plugin_type());
        $this->assertEquals($expected->aggregation_method ?? criterion::AGGREGATE_ALL, $actual->get_aggregation_method());
        $this->assertSame($expected->aggregation_params ?? [], $actual->get_aggregation_params());
        $this->assertSame('course', $actual->get_items_type());

        $ids = $actual->get_item_ids();
        $this->assertEqualsCanonicalizing($expected->item_ids, $ids);

        $this->assertEqualsCanonicalizing((array)$expected->metadata, (array)$actual->get_metadata());
    }

    /**
     * Test returned criterion type
     */
    public function test_criterion_type() {
        $this->assertSame('coursecompletion', coursecompletion::criterion_type());
    }


     /**
     * Test constructor without attributes
     */
    public function test_constructor_no_attributes() {

        $expected = (object)[
            'id' => 0,
            'plugin_type' => 'coursecompletion',
            'aggregation_method' => coursecompletion::AGGREGATE_ALL,
            'aggregation_params' => [],
            'item_ids' => [],
            'metadata' => [],
        ];

        $cc = new coursecompletion();
        $this->verify_instance($expected, $cc);
    }

     /**
     * Test constructor with id
     */
    public function test_fetch() {
        $data = $this->setup_data();

        foreach ($data->instancerows as $row) {
            $expected = (object)[
                'id' => $row->id,
                'plugin_type' => 'coursecompletion',
                'aggregation_method' => $row->aggregation_method,
                'aggregation_params' => json_decode($row->aggregation_params ?? '', true) ?? [],
                'item_ids' => $data->itemids[$row->id] ?? [],
                'metadata' => [],
            ];

            $cc = coursecompletion::fetch($row->id);
            $this->verify_instance($expected, $cc);
        }
    }

    /**
     * Test add and remove items with saving
     */
    public function test_add_remove_items() {
        global $DB;

        $data = $this->setup_data();

        // Ensure we start without archived rows
        $rows = $DB->get_records('totara_criteria');
        $this->assertSame(4, count($rows));

        $instancerow = end($data->instancerows);
        $expected = (object)[
            'id' => $instancerow->id,
            'plugin_type' => 'coursecompletion',
            'aggregation_method' => $instancerow->aggregation_method,
            'aggregation_params' => json_decode($instancerow->aggregation_params ?? '', true) ?? [],
            'item_ids' => $data->itemids[$instancerow->id],
            'metadata' => [],
        ];

        $cc = coursecompletion::fetch($instancerow->id);
        $this->verify_instance($expected, $cc);

        // Save without any changes - nothing should change
        $cc->save();
        $this->verify_instance($expected, $cc);

        // Now add some new items
        $cc->add_items([987, 876]);
        // Not yet saved
        $this->verify_saved_items($cc->get_id(), $expected->item_ids);

        $expected->item_ids = [303, 304, 305, 987, 876];
        $this->verify_instance($expected, $cc);

        // Test saving with changes
        $cc->save();
        $this->assertEquals($instancerow->id, $cc->get_id());
        $this->verify_saved_items($cc->get_id(), $expected->item_ids);
        $rows = $DB->get_records('totara_criteria');
        $this->assertSame(4, count($rows));

        // Now remove some items
        $cc->remove_items([304, 876]);
        $this->verify_saved_items($cc->get_id(), $expected->item_ids);

        $expected->item_ids = [303, 305, 987];
        $this->verify_instance($expected, $cc);

        // Test saving with removal only
        $cc->save();
        $this->verify_instance($expected, $cc);
        $this->verify_saved_items($cc->get_id(), $expected->item_ids);
        $rows = $DB->get_records('totara_criteria');
        $this->assertSame(4, count($rows));

        // Add some and remove others and then save
        $cc->add_items([555, 666]);
        $cc->remove_items([303]);

        $expected->item_ids = [305, 987, 555, 666];
        $cc->save();
        $this->verify_instance($expected, $cc);
        $this->verify_saved_items($cc->get_id(), $expected->item_ids);

        $rows = $DB->get_records('totara_criteria');
        $this->assertSame(4, count($rows));
    }

     /**
     * Test delete
     */
    public function test_delete() {
        global $DB;

        $data = $this->setup_data();

        // Starting condition
        $instancerow = array_shift($data->instancerows);
        $cc = coursecompletion::fetch($instancerow->id);
        $id = $cc->get_id();

        $cc->delete();

        $this->assertEquals(0, $cc->get_id());

        $rows = $DB->get_records('totara_criteria');
        $this->assertSame(3, count($rows));

        $row = $DB->get_record('totara_criteria', ['id' => $id]);
        $this->assertFalse($row);
        $rows = $DB->get_records('totara_criteria_item', ['criterion_id' => $id]);
        $this->assertSame(0, count($rows));

        // Add some item_records to ensure they are also deleted
        $instancerow = array_shift($data->instancerows);
        $cc = coursecompletion::fetch($instancerow->id);
        $id = $cc->get_id();
        $item_id = array_keys($data->itemids[$id])[0];

        $record = ['user_id' => 1,
            'criterion_item_id' => $item_id,
            'criterion_met' => 0,
            'timeevaluated' => time(),
        ];
        $DB->insert_record("totara_criteria_item_record", $record);
        $rows = $DB->get_records('totara_criteria_item_record', ['criterion_item_id' => $item_id]);
        $this->assertSame(1, count($rows));

        // Now delete
        $cc->delete();

        $this->assertEquals(0, $cc->get_id());

        $rows = $DB->get_records('totara_criteria');
        $this->assertSame(2, count($rows));

        $row = $DB->get_record('totara_criteria', ['id' => $id]);
        $this->assertFalse($row);
        $rows = $DB->get_records('totara_criteria_item', ['criterion_id' => $id]);
        $this->assertSame(0, count($rows));
        $rows = $DB->get_records('totara_criteria_item_record', ['criterion_item_id' => $item_id]);
        $this->assertSame(0, count($rows));
    }

    /**
     * Test aggregate
     */
    public function test_aggregate() {

        $numcourses = 3;

        // Some data
        for ($j = 1; $j <= $numcourses; $j++) {
            ${"course_$j"} = $this->getDataGenerator()->create_course();
        }

        for ($i = 0; $i <= $numcourses; $i++) {
            ${"user_$i"} = $this->getDataGenerator()->create_user();
        }

        $cc_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        // Now for the tests

        // Single course to be completed
        $record = [
            'aggregation' => ['method' => criterion::AGGREGATE_ALL],
            'courseids' => [$course_2->id],
        ];

        $cc = $cc_generator->create_coursecompletion($record);
        $this->simulate_course_completion($cc, [$user_2, $user_3], [$course_2]);

        for ($i = 0; $i <= $numcourses; $i++) {
            $this->assertSame($i >= 2, $cc->aggregate(${"user_$i"}->id));
        }


        // Multiple courses to be completed
        $record = [
            'aggregation' => ['method' => criterion::AGGREGATE_ALL],
            'courseids' => [$course_2->id, $course_3->id],
        ];

        $cc = $cc_generator->create_coursecompletion($record);
        $this->simulate_course_completion($cc, [$user_0, $user_1], [$course_1]);
        $this->simulate_course_completion($cc, [$user_2, $user_3], [$course_2]);
        $this->simulate_course_completion($cc, [$user_1, $user_3], [$course_3]);

        // Only user_3 completed both
        for ($i = 0; $i <= $numcourses; $i++) {
            $this->assertSame($i == 3, $cc->aggregate(${"user_$i"}->id));
        }

        // Any of 2 courses to be completed
        $record = [
            'aggregation' => ['method' => criterion::AGGREGATE_ANY_N, 'req_items' => 1],
            'courseids' => [$course_2->id, $course_3->id],
        ];

        $cc = $cc_generator->create_coursecompletion($record);
        $this->simulate_course_completion($cc, [$user_0, $user_1], [$course_1]);
        $this->simulate_course_completion($cc, [$user_2, $user_3], [$course_2]);
        $this->simulate_course_completion($cc, [$user_1, $user_3], [$course_3]);

        // users 1, 2 and 3 completed at least 1
        for ($i = 0; $i <= $numcourses; $i++) {
            $this->assertSame($i > 0, $cc->aggregate(${"user_$i"}->id));
        }
    }


    /**
     * Test dump_criterion_configuration
     */
    public function test_dump_criterion_configuration() {

        $data = $this->setup_data();

        foreach ($data->instancerows as $id => $row) {
            $expected = $row;
            $expected->items = $data->itemrows[$id] ?? [];
            $expected->metadata = [];

            $actual = coursecompletion::dump_criterion_configuration($id);
            $this->assertEqualsCanonicalizing($expected, $actual);
        }
    }

    /**
     * Test validate
     */
    public function test_validate() {
        global $CFG;

        // Completion only enabled for every second course
        $courses = [];
        for ($i = 1; $i <= 5; $i++) {
            $courses[$i] = $this->getDataGenerator()->create_course(['enablecompletion' => $i % 2]);
        }

        $CFG->enablecompletion = true;

        $criterion = new coursecompletion();
        // Initial value
        $this->assertFalse($criterion->is_valid());

        // No items yet
        $criterion->validate();
        $this->assertFalse($criterion->is_valid());

        // Add valid course
        $criterion->set_item_ids([$courses[1]->id]);
        // Not yet validated
        $this->assertFalse($criterion->is_valid());
        // Validated
        $criterion->validate();
        $this->assertTrue($criterion->is_valid());

        // Add invalid course
        $criterion->add_items([$courses[2]->id]);
        // Not yet validated
        $this->assertTrue($criterion->is_valid());
        // Validated
        $criterion->validate();
        $this->assertFalse($criterion->is_valid());

        // All valid courses
        $criterion->set_item_ids([$courses[1]->id, $courses[3]->id]);
        // Not yet validated
        $this->assertFalse($criterion->is_valid());
        // Validated
        $criterion->validate();
        $this->assertTrue($criterion->is_valid());

        // Changed aggregation
        $criterion->set_aggregation_method(criterion::AGGREGATE_ANY_N);
        $criterion->set_aggregation_params(['req_items' => 3]);
        // Not yet validated
        $this->assertTrue($criterion->is_valid());
        // Validated
        $criterion->validate();
        $this->assertFalse($criterion->is_valid());
    }

    /**
     * Test save with item validation
     */
    public function test_save_with_item_validation() {
        global $CFG;

        /** @var \totara_criteria\testing\generator $criteria_generator */
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        // Completion only enabled for every second course
        $courses = [];
        for ($i = 1; $i <= 5; $i++) {
            $courses[$i] = $this->getDataGenerator()->create_course(['enablecompletion' => $i % 2]);
        }

        $CFG->enablecompletion = true;

        // Coursecompletion with valid courses
        $criterion = $criteria_generator->create_coursecompletion([
            'aggregation' => ['method' => criterion::AGGREGATE_ANY_N, 'req_items' => 2],
            'courseids' => [$courses[1]->id, $courses[3]->id],
        ]);
        $this->assertTrue($criterion->is_valid());
        $on_disk = new criterion_entity($criterion->get_id());
        $this->assertEquals(1, $on_disk->valid);

        // Coursecompletion with invalid courses
        $criterion = $criteria_generator->create_coursecompletion([
            'aggregation' => ['method' => criterion::AGGREGATE_ANY_N, 'req_items' => 2],
            'courseids' => [$courses[1]->id, $courses[2]->id],
        ]);
        $this->assertFalse($criterion->is_valid());
        $on_disk = new criterion_entity($criterion->get_id());
        $this->assertEquals(0, $on_disk->valid);

        // Coursecompletion with valid non-existent course
        $criterion = $criteria_generator->create_coursecompletion([
            'aggregation' => ['method' => criterion::AGGREGATE_ANY_N, 'req_items' => 2],
            'courseids' => [$courses[1]->id, $courses[3]->id, 12345],
        ]);
        $this->assertFalse($criterion->is_valid());
        $on_disk = new criterion_entity($criterion->get_id());
        $this->assertEquals(0, $on_disk->valid);
    }


    /**
     * Simulate course completion events by manually inserting data into totara_criterion_item_record
     *
     * @param coursecompletion $cc  Coursecompletion criteria
     * @param array $users User that 'completed' the courses
     * @param array $courses Courses 'completed'
     */
    private function simulate_course_completion(coursecompletion $cc, array $users, array $courses) {
        global $DB;

        foreach ($courses as $course) {
            if ($item = $DB->get_record('totara_criteria_item', ['criterion_id' => $cc->get_id(), 'item_id' => $course->id])) {
                foreach ($users as $user) {
                    $record = [
                        'user_id' => $user->id,
                        'criterion_item_id' => $item->id,
                        'criterion_met' => 1,
                        'timeevaluated' => time(),
                    ];
                    $DB->insert_record('totara_criteria_item_record', $record);
                }
            }
        }
    }

}
