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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_criteria
 */

use criteria_childcompetency\childcompetency;
use pathway_criteria_group\criteria_group;
use totara_competency\hook\competency_configuration_changed;
use totara_criteria\criterion;

/**
 * @group totara_competency
 */
class criteria_childcompetency_testcase extends advanced_testcase {

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

        $tests = [
            [
                'plugin_type' => 'childcompetency',
                'aggregation_method' => childcompetency::AGGREGATE_ALL,
            ],
            [
                'plugin_type' => 'childcompetency',
                'aggregation_method' => childcompetency::AGGREGATE_ANY_N,
                'aggregation_params' => json_encode(['req_items' => 1]),
            ],
            [
                'plugin_type' => 'childcompetency',
                'aggregation_method' => childcompetency::AGGREGATE_ANY_N,
                'aggregation_params' => json_encode(['req_items' => 2]),
            ],
        ];

        foreach ($tests as $tst) {
            // First the criterion
            $tst['criterion_modified'] = time();
            $criterion_id = $DB->insert_record('totara_criteria', $tst, true, false);
            $data->instancerows[$criterion_id] = $DB->get_record('totara_criteria', ['id' => $criterion_id]);

            // Then the metadata
            $tst_metadata = [
                'criterion_id' => $criterion_id,
                'metakey' => criterion::METADATA_COMPETENCY_KEY,
                'metavalue' => 23,
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
     * @param childcompetency $actual
     */
    private function verify_instance($expected, childcompetency $actual) {
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
     * Test returned criterion type
     */
    public function test_criterion_type() {
        $this->assertSame('childcompetency', childcompetency::criterion_type());
    }

    /**
     * Test constructor without attributes
     */
    public function test_constructor_no_attributes() {

        $expected = (object)[
            'id' => 0,
            'plugin_type' => 'childcompetency',
            'aggregation_method' => childcompetency::AGGREGATE_ALL,
            'aggregation_params' => [],
        ];

        $cc = new childcompetency();
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
                'plugin_type' => 'childcompetency',
                'aggregation_method' => $row->aggregation_method,
                'aggregation_params' => json_decode($row->aggregation_params ?? '', true) ?? [],
                'metadata' => ['competency_id' => 23],
            ];

            $cc = childcompetency::fetch($row->id);
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
        $cc = childcompetency::fetch($instancerow->id);
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

            $actual = childcompetency::dump_criterion_configuration($id);
            $this->assertEqualsCanonicalizing($expected, $actual);
        }
    }

    /**
     * Test validate when childcompetency criteria is added later
     */
    public function test_validate_parent_criteria_first() {
        /** @var \totara_criteria\testing\generator $criterion_generator */
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => true]);

        $parent_comp = $competency_generator->create_competency('Parent competency');
        $child_comp = $competency_generator->create_competency('Child competency', null, ['parentid' => $parent_comp->id]);

        // The parent competency first - This should result in parent being invalid
        $criterion = $criteria_generator->create_childcompetency(['competency' => $parent_comp->id]);
        $parent_pw = $competency_generator->create_criteria_group($parent_comp->id,
            [$criterion],
            $parent_comp->scale->default_value
        );

        // Configuration changes are done through the webapi which triggers the competency_configuration_changed hook.
        // Simulating the triggering of this hook here as we are not using the API
        /** @var competency_configuration_changed $hook */
        $hook = new competency_configuration_changed($parent_comp->id);
        $hook->execute();

        $this->assertFalse($parent_pw->is_valid());


        // Now add criteria to the child competency ... parent validity should also be updated as the child is valid
        $criterion = $criteria_generator->create_coursecompletion(['courseids' => [$course->id]]);
        $child_pw = $competency_generator->create_criteria_group($child_comp,
            [$criterion],
            $child_comp->scale->min_proficient_value
        );

        /** @var competency_configuration_changed $hook */
        $hook = new competency_configuration_changed($child_comp->id);
        $hook->execute();

        $this->assertTrue($child_pw->is_valid());

        // Re-initialize parent
        $parent_pw = criteria_group::fetch($parent_pw->get_id());
        $this->assertTrue($parent_pw->is_valid());
    }

}
