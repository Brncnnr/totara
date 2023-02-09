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
use criteria_onactivate\onactivate;
use totara_criteria\criterion;

/**
 * @group totara_competency
 */
class criteria_onactivate_testcase extends advanced_testcase {

    private function setup_data() {
        global $DB;

        // Insert some dummy data into totara_criterion
        // Not using the data generator here as we are testing the functions used by the generator

        $data = new class() {
            /** @var [\stdClass] instancerows */
            public $instancerows = [];
            /** @var [\stdClass] metadatarows */
            public $metadatarows = [];
        };

        for ($i = 0; $i < 3; $i++) {
            $record = [
                'plugin_type' => 'onactivate',
                'aggregation_method' => criterion::AGGREGATE_ALL,
                'criterion_modified' => time()
            ];

            $criterion_id = $DB->insert_record('totara_criteria', $record, true, false);
            $data->instancerows[$criterion_id] = $DB->get_record('totara_criteria', ['id' => $criterion_id]);

            // Then the metadata
            $tst_metadata = [
                'criterion_id' => $criterion_id,
                'metakey' => criterion::METADATA_COMPETENCY_KEY,
                'metavalue' => 45,
            ];
            $DB->insert_record('totara_criteria_metadata', $tst_metadata);

            $data->metadatarows[$criterion_id] = $DB->get_records('totara_criteria_metadata', ['criterion_id' => $criterion_id]);
        }

        return $data;
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

        $this->assertEqualsCanonicalizing($expected->metadata, $actual->get_metadata());
    }

    /**
     * Test returned criterion type
     */
    public function test_criterion_type() {
        $this->assertSame('onactivate', onactivate::criterion_type());
    }

     /**
     * Test constructor without attributes
     */
    public function test_constructor_no_attributes() {

        $expected = (object)[
            'id' => 0,
            'plugin_type' => 'onactivate',
            'aggregation_method' => criterion::AGGREGATE_ALL,
            'aggregation_params' => [],
            'items_type' => '',
            'item_ids' => [],
            'metadata' => [],
        ];

        $cc = new onactivate();
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
                'plugin_type' => 'onactivate',
                'aggregation_method' => criterion::AGGREGATE_ALL,
                'aggregation_params' => [],
                'items_type' => '',
                'item_ids' => [],
                'metadata' => ['competency_id' => 45],
            ];

            $cc = onactivate::fetch($row->id);
            $this->verify_instance($expected, $cc);
        }
    }


    /**
     * Test delete
     */
    public function test_delete() {
        global $DB;

        $data = $this->setup_data();

        // Starting condition
        $instancerow = array_shift($data->instancerows);
        $cc = onactivate::fetch($instancerow->id);
        $id = $cc->get_id();

        $cc->delete();

        $this->assertEquals(0, $cc->get_id());

        $rows = $DB->get_records('totara_criteria');
        $this->assertSame(2, count($rows));

        $row = $DB->get_record('totara_criteria', ['id' => $id]);
        $this->assertFalse($row);
        $rows = $DB->get_records('totara_criteria_item', ['criterion_id' => $id]);
        $this->assertSame(0, count($rows));
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
            $expected->metadata = $data->metadatarows[$row->id];

            $actual = onactivate::dump_criterion_configuration($id);
            $this->assertEqualsCanonicalizing($expected, $actual);
        }
    }

}
