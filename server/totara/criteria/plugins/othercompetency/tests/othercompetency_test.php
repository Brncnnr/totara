<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @package criteria_othercompetency
 */

use criteria_othercompetency\othercompetency;
use pathway_manual\models\roles\manager;
use totara_competency\hook\competency_configuration_changed;

/**
 * @group totara_competency
 */
class criteria_othercompetency_testcase extends advanced_testcase {

    private function setup_data() {
        global $DB;

        // Insert some dummy data into totara_criterion
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
                'plugin_type' => 'othercompetency',
                'aggregation_method' => othercompetency::AGGREGATE_ALL,
                'item_ids' => [],
            ],
            [
                'plugin_type' => 'othercompetency',
                'aggregation_method' => othercompetency::AGGREGATE_ALL,
                'item_ids' => [100, 101, 102],
            ],
            [
                'plugin_type' => 'othercompetency',
                'aggregation_method' => othercompetency::AGGREGATE_ANY_N,
                'aggregation_params' => json_encode(['req_items' => 1]),
                'item_ids' => [102, 203, 204]
            ],
            [
                'plugin_type' => 'othercompetency',
                'aggregation_method' => othercompetency::AGGREGATE_ANY_N,
                'aggregation_params' => json_encode(['req_items' => 2]),
                'item_ids' => [303, 304, 305]
            ],
        ];

        foreach ($tests as $tst) {
            // First the criterion
            $tst['criterion_modified'] = time();
            $criterion_id = $DB->insert_record('totara_criteria', $tst, true, false);
            $data->instancerows[$criterion_id] = $DB->get_record('totara_criteria', ['id' => $criterion_id]);

            if (!empty($tst['item_ids'])) {
                // Add non-existing criterion_items
                foreach ($tst['item_ids'] as $competency_id) {
                    $DB->insert_record(
                        'totara_criteria_item',
                        [
                            'criterion_id' => $criterion_id,
                            'item_type' => 'competency',
                            'item_id' => $competency_id,
                        ]
                    );
                }

                $params = ['criterion_id' => $criterion_id];
                $data->itemrows[$criterion_id] = $DB->get_records('totara_criteria_item', $params);
                $data->itemids[$criterion_id] = $DB->get_records_menu('totara_criteria_item', $params, '', 'id, item_id');

                $this->verify_saved_items($criterion_id, $tst['item_ids']);
            }
        }

        $data->instancerows = $DB->get_records('totara_criteria', ['plugin_type' => 'othercompetency'], 'id');

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
     * @param othercompetency $actual
     */
    private function verify_instance($expected, othercompetency $actual) {
        $this->assertEquals($expected->id, $actual->get_id());
        $this->assertEquals($expected->plugin_type, $actual->get_plugin_type());
        $this->assertEquals($expected->aggregation_method, $actual->get_aggregation_method());
        $this->assertSame($expected->aggregation_params, $actual->get_aggregation_params());
        $this->assertSame('competency', $actual->get_items_type());

        $ids = $actual->get_item_ids();
        $this->assertEqualsCanonicalizing($expected->item_ids ?? [], $ids);

        $this->assertEqualsCanonicalizing($expected->metadata ?? [], $actual->get_metadata());
    }

    /**
     * Test constructor without attributes
     */
    public function test_constructor_no_attributes() {

        $expected = (object)[
            'id' => 0,
            'plugin_type' => 'othercompetency',
            'aggregation_method' => othercompetency::AGGREGATE_ALL,
            'aggregation_params' => [],
            'item_ids' => [],
            'metadata' => [],
        ];

        $cc = new othercompetency();
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
                'plugin_type' => 'othercompetency',
                'aggregation_method' => $row->aggregation_method,
                'aggregation_params' => json_decode($row->aggregation_params ?? '', true) ?? [],
                'item_ids' => $data->itemids[$row->id] ?? [],
                'metadata' => [],
            ];

            $cc = othercompetency::fetch($row->id);
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
        $cc = othercompetency::fetch($instancerow->id);
        $id = $cc->get_id();

        $cc->delete();

        $this->assertEquals(0, $cc->get_id());

        $rows = $DB->get_records('totara_criteria');
        $this->assertSame(3, count($rows));

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
            $expected->items = $data->itemrows[$row->id] ?? [];
            $expected->metadata = [];

            $actual = othercompetency::dump_criterion_configuration($id);
            $this->assertEqualsCanonicalizing($expected, $actual);
        }
    }

    /**
     * Test validate when othercompetency criteria is added later
     */
    public function test_validate_othercompetency_criteria_added_later() {
        /** @var \totara_criteria\testing\generator $criterion_generator */
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');

        // Create the criterion without any competencies
        $criterion = $criteria_generator->create_othercompetency(['competencyids' => []]);
        $this->assertFalse($criterion->is_valid());

        // Create a competency with a manual pathway and add it as an item to the criterion
        $comp_a = $competency_generator->create_competency('compA');
        $manual_pw = $competency_generator->create_manual($comp_a, [manager::class]);
        $criterion->add_items([$comp_a->id]);
        $criterion->save();
        $this->assertTrue($criterion->is_valid());

        // Now remove the manual pathway from the competency - this should result in invalid criterion again
        $manual_pw->delete();

        // Configuration changes are done through the webapi which triggers the competency_configuration_changed hook.
        // Simulating the triggering of this hook here as we are not using the API
        /** @var competency_configuration_changed $hook */
        $hook = new competency_configuration_changed($comp_a->id);
        $hook->execute();

        // Refetch the criterion
        $criterion = othercompetency::fetch($criterion->get_id());
        $this->assertFalse($criterion->is_valid());
    }

}
