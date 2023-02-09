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

use criteria_linkedcourses\linkedcourses;
use totara_competency\linked_courses;
use totara_criteria\criterion;
use totara_criteria\entity\criterion as criterion_entity;

/**
 * @group totara_competency
 */
class criteria_linkedcourses_testcase extends advanced_testcase {

    private function setup_data() {
        global $DB;

        // Insert some dummy data into totara_criterion and totara_criteria_metadata
        // Not using the data generator here as we are testing the functions used by the generator

        $data = new class() {
            /** @var [\stdClass] instancerows */
            public $instancerows = [];
            /** @var [\stdClass] metadatarows */
            public $metadatarows = [];
            /** @var [\stdClass] metadatakeys */
            public $metadatakeys = [];
        };

        $tests = [
            [
                'plugin_type' => 'linkedcourses',
                'aggregation_method' => linkedcourses::AGGREGATE_ALL,
                'metadata' => ['competency_id' => 1],
            ],
            [
                'plugin_type' => 'linkedcourses',
                'aggregation_method' => linkedcourses::AGGREGATE_ANY_N,
                'aggregation_params' => json_encode(['req_items' => 1]),
                'metadata' => ['competency_id' => 1],
            ],
            [
                'plugin_type' => 'linkedcourses',
                'aggregation_method' => linkedcourses::AGGREGATE_ANY_N,
                'aggregation_params' => json_encode(['req_items' => 2]),
                'metadata' => ['competency_id' => 1],
            ],
        ];

        foreach ($tests as $tst) {
            // First the criterion
            $tst['criterion_modified'] = time();

            $criterion_id = $DB->insert_record('totara_criteria', $tst, true, false);
            if (!empty($tst['metadata'])) {
                foreach ($tst['metadata'] as $metakey => $metavalue) {
                    $DB->insert_record('totara_criteria_metadata',
                        [
                            'criterion_id' => $criterion_id,
                            'metakey' => $metakey,
                            'metavalue' => $metavalue,
                        ]
                    );
                }

                $this->verify_saved_metadata($criterion_id, $tst['metadata']);
            }

            $data->metadatarows[$criterion_id] = $DB->get_records('totara_criteria_metadata',
                ['criterion_id' => $criterion_id]
            );

            $data->metadatakeys[$criterion_id] = $DB->get_records_menu('totara_criteria_metadata',
                ['criterion_id' => $criterion_id],
                '',
                'metakey, metavalue'
            );
        }

        $data->instancerows = $DB->get_records('totara_criteria', ['plugin_type' => 'linkedcourses']);

        return $data;
    }

    /**
     * Verify the metadata in the database
     *
     * @param int $criterion_id
     * @param array $expected_pairs
     */
    private function verify_saved_metadata(int $criterion_id, array $expected_pairs) {
        global $DB;

        $actual_pairs = $DB->get_records_menu(
            'totara_criteria_metadata',
            ['criterion_id' => $criterion_id],
            '',
            'metakey, metavalue'
        );
        $this->assertEqualsCanonicalizing($expected_pairs, $actual_pairs);
    }

    /**
     * Verify the instance attributes
     *
     * @param stdClass $expected
     * @param linkedcourses $actual
     */
    private function verify_instance($expected, $actual) {
        $this->assertEquals($expected->id, $actual->get_id());
        $this->assertEquals($expected->plugin_type, $actual->get_plugin_type());
        $this->assertEquals($expected->aggregation_method, $actual->get_aggregation_method());
        $this->assertSame($expected->aggregation_params, $actual->get_aggregation_params());

        $ids = $actual->get_item_ids();
        $this->assertEqualsCanonicalizing($expected->item_ids ?? [], $ids);

        $this->assertEqualsCanonicalizing($expected->metadata ?? [], $actual->get_metadata());
    }

    /**
     * Test returned criterion type
     */
    public function test_criterion_type() {
        $this->assertSame('linkedcourses', linkedcourses::criterion_type());
    }

     /**
     * Test constructor without attributes
     */
    public function test_constructor_no_attributes() {

        $expected = (object)[
            'id' => 0,
            'plugin_type' => 'linkedcourses',
            'aggregation_method' => linkedcourses::AGGREGATE_ALL,
            'aggregation_params' => [],
            'items_type' => '',
            'item_ids' => [],
            'metadata' => [],
        ];

        $cc = new linkedcourses();
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
                'plugin_type' => 'linkedcourses',
                'aggregation_method' => $row->aggregation_method,
                'aggregation_params' => json_decode($row->aggregation_params ?? '', true) ?? [],
                'items_type' => '',
                'item_ids' => [],
                'metadata' => $data->metadatakeys[$row->id],
            ];

            $cc = linkedcourses::fetch($row->id);
            $this->verify_instance($expected, $cc);
        }
    }

    /**
     * Test add and remove metadata with saving
     */
    public function test_add_remove_metadata() {
        global $DB;

        $data = $this->setup_data();

        // Ensure we start without archived rows
        $rows = $DB->get_records('totara_criteria');
        $this->assertSame(3, count($rows));

        $instancerow = end($data->instancerows);
        $expected = (object)[
            'id' => $instancerow->id,
            'plugin_type' => 'linkedcourses',
            'aggregation_method' => $instancerow->aggregation_method,
            'aggregation_params' => json_decode($instancerow->aggregation_params, true) ?? [],
            'items_type' => '',
            'item_ids' => [],
            'metadata' => $data->metadatakeys[$instancerow->id],
        ];

        $cc = linkedcourses::fetch($instancerow->id);
        $this->verify_instance($expected, $cc);

        // Save without any changes - nothing should change
        $cc->save();
        $this->verify_instance($expected, $cc);

        // Now add some new metadata
        $cc->add_metadata([['metakey' => 'newkey', 'metavalue' => 'newvalue']]);
        // Not yet saved
        $this->verify_saved_metadata($cc->get_id(), $expected->metadata);

        $expected->metadata['newkey'] = 'newvalue';
        $this->verify_instance($expected, $cc);

        // Test saving with changes
        $cc->save();
        $this->assertEquals($instancerow->id, $cc->get_id());
        $this->verify_saved_metadata($cc->get_id(), $expected->metadata);
        $rows = $DB->get_records('totara_criteria');
        $this->assertSame(3, count($rows));

        // Now remove some metadata
        unset($expected->metadata['newkey']);
        $cc->remove_metadata(['newkey']);
        $cc->save();
        $this->verify_saved_metadata($cc->get_id(), $expected->metadata);

        // Test saving with removal only
        $cc->save();
        $this->verify_instance($expected, $cc);
        $this->verify_saved_metadata($cc->get_id(), $expected->metadata);
        $rows = $DB->get_records('totara_criteria');
        $this->assertSame(3, count($rows));

        // Add some and remove others and then save
        $cc->add_metadata([
            ['metakey' => 'many1', 'metavalue' => 'first of many'],
            ['metakey' => 'many2', 'metavalue' => 'second of many']
        ]);
        $cc->remove_metadata(['newkey']);

        $expected->metadata['many1'] = 'first of many';
        $expected->metadata['many2'] = 'second of many';
        $cc->save();
        $this->verify_instance($expected, $cc);
        $this->verify_saved_metadata($cc->get_id(), $expected->metadata);

        $rows = $DB->get_records('totara_criteria');
        $this->assertSame(3, count($rows));
    }

     /**
     * Test delete
     */
    public function test_delete() {
        global $DB;

        $data = $this->setup_data();

        // Starting condition
        $instancerow = array_shift($data->instancerows);
        $cc = linkedcourses::fetch($instancerow->id);
        $id = $cc->get_id();

        $cc->delete();

        $this->assertEquals(0, $cc->get_id());

        $rows = $DB->get_records('totara_criteria');
        $this->assertSame(2, count($rows));

        $row = $DB->get_record('totara_criteria', ['id' => $id]);
        $this->assertFalse($row);
        $rows = $DB->get_records('totara_criteria_metadata', ['criterion_id' => $id]);
        $this->assertSame(0, count($rows));
    }


    /**
     * Test dump_criterion_configuration
     */
    public function test_dump_criterion_configuration() {

        $data = $this->setup_data();

        foreach ($data->instancerows as $id => $row) {
            $expected = $row;
            $expected->items = [];
            $expected->metadata = $data->metadatarows[$id];

            $actual = linkedcourses::dump_criterion_configuration($id);
            $this->assertEqualsCanonicalizing($expected, $actual);
        }
    }


    /**
     * Test save with validation of items
     */
    public function test_save_with_item_validation() {
        global $CFG;

        /** @var \totara_criteria\testing\generator $criteria_generator */
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');
        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');

        // Completion only enabled for every second course
        $courses = [];
        for ($i = 1; $i <= 5; $i++) {
            $courses[$i] = $this->getDataGenerator()->create_course(['enablecompletion' => $i % 2]);
        }

        $competency = $competency_generator->create_competency();

        $CFG->enablecompletion = true;

        // All valid
        linked_courses::set_linked_courses($competency->id, [
            ['id' => $courses[1]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
            ['id' => $courses[3]->id, 'linktype' => linked_courses::LINKTYPE_OPTIONAL],
            ['id' => $courses[5]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
        ]);

        $criterion = $criteria_generator->create_linkedcourses([
            'aggregation' => ['method' => criterion::AGGREGATE_ANY_N, 'req_items' => 2],
            'competency' => $competency->id,
        ]);
        $this->assertTrue($criterion->is_valid());
        $on_disk = new criterion_entity($criterion->get_id());
        $this->assertEquals(1, $on_disk->valid);

        // Some invalid
        linked_courses::set_linked_courses($competency->id, [
            ['id' => 12345, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
            ['id' => $courses[3]->id, 'linktype' => linked_courses::LINKTYPE_OPTIONAL],
        ]);
        $on_disk = new criterion_entity($criterion->get_id());
        $this->assertEquals(0, $on_disk->valid);
        $criterion = linkedcourses::fetch($criterion->get_id());
        $this->assertFalse($criterion->is_valid());
    }

}
