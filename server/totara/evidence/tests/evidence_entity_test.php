<?php
/**
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 * @category test
 */

use totara_evidence\entity;
use totara_evidence\entity\evidence_field_data;
use totara_evidence\entity\evidence_type as evidence_type_entity;
use totara_evidence\models\evidence_type as evidence_type_model;

global $CFG;
require_once($CFG->dirroot . '/totara/evidence/tests/evidence_testcase.php');

/**
 * @group totara_evidence
 */
class totara_evidence_entity_testcase extends totara_evidence_testcase {

    /**
     * Test that an evidence item's field data attribute has the custom field data associated with it
     */
    public function test_entity_item_attribute_data(): void {
        $type = $this->generator()->create_evidence_type_entity(['name' => 0]);
        $item = $this->generator()->create_evidence_item_entity(['type' => 0]);

        $this->assertEmpty($item->data);

        $fields_num = 3;

        $expected = [];

        for ($i = 0; $i < $fields_num; $i++) {
            $field = $this->generator()->create_evidence_field([
                'typeid'    => $type->id,
                'sortorder' => $i,
                'datatype'  => 'text',
                'fullname'  => $i,
                'shortname' => $i,
            ]);
            $data = new evidence_field_data([
                'fieldid'    => $field->id,
                'evidenceid' => $item->id,
                'data'       => "Data $i"
            ]);
            $expected[] = $data->save()->to_array();
        }

        $item->load_relation('data');
        $this->assertEquals($expected, $item->data->to_array());

        $item->delete();
        $item->load_relation('data');
        $this->assertDebuggingCalled('Entity does not exist.');
        $this->assertEmpty($item->data);
        $this->assertDebuggingCalled('Entity does not exist.');
    }

    /**
     * Test that a type's items attribute returns its child evidence items
     */
    public function test_entity_type_attribute_items(): void {
        $type = $this->generator()->create_evidence_type_entity(['name' => 0]);

        $this->assertEmpty($type->items);

        for ($i = 0; $i < 3; $i++) {
            $this->generator()->create_evidence_item_entity(['type' => 0]);
        }

        $items = entity\evidence_item::repository()
            ->where('typeid', $type->id)
            ->order_by('id')
            ->get()
            ->all();
        $type->load_relation('items');
        $this->assertEqualsCanonicalizing($items, $type->items->all());

        // Cannot delete type as long as there are items
        $this->expectException(dml_write_exception::class);
        $type->delete();
    }

    /**
     * Test that a type's fields attribute has the custom fields associated with it and has the correct order
     */
    public function test_entity_type_attribute_fields(): void {
        $type = $this->generator()->create_evidence_type_entity(['fields' => 0]);

        $this->assertEmpty($type->fields);

        $this->generator()->create_evidence_field(['typeid' => $type->id, 'fullname' => 'Field One', 'sortorder' => 2]);
        $this->generator()->create_evidence_field(['typeid' => $type->id, 'fullname' => 'Field Two', 'sortorder' => 0]);
        $this->generator()->create_evidence_field(['typeid' => $type->id, 'fullname' => 'Field Three', 'sortorder' => 1]);

        $type->load_relation('fields');
        $this->assertNotEquals($this->field_repository()->order_by('id')->get()->all(), $type->fields->all());
        $this->assertEquals($this->field_repository()->order_by('sortorder')->get()->all(), $type->fields->all());

        $type->delete();
        $type->load_relation('fields');
        $this->assertDebuggingCalled('Entity does not exist.');
        $this->assertEmpty($type->fields);
        $this->assertDebuggingCalled('Entity does not exist.');
    }

    /**
     * Test that field's type params attribute returns its parent type
     */
    public function test_entity_field_attribute_type(): void {
        $this->generator()->create_evidence_type_entity(['fields' => 1]);
        $this->assertEquals($this->types()->all()[0]->to_array(), $this->fields()->all()[0]->type->to_array());
    }

    /**
     * Test that field data's various attributes return their expected value
     */
    public function test_entity_field_data_attributes(): void {
        $this->generator()->create_evidence_type_entity(['fields' => 1, 'name' => 'Type_One']);
        $item = $this->generator()->create_evidence_item_entity(['type' => 'Type_One']);

        $field = $this->fields()->all()[0];

        $field_data = (new entity\evidence_field_data([
            'fieldid'    => $field->id,
            'evidenceid' => $item->id,
            'data'       => 'Data'
        ]))->save();

        $this->assertEquals($this->items()->all()[0]->to_array(),  $field_data->item->to_array());
        $this->assertEquals($field->to_array(), $field_data->field->to_array());

        $children_count = 3;

        $expected = [];

        for ($i = 0; $i < $children_count; $i++) {
            $child = new entity\evidence_field_data_param([
                'dataid' => $field_data->id,
                'value'  => "Param Data $i"
            ]);
            $expected[] = $child->save()->to_array();
        }

        $actual = $field_data->params->to_array();

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test that field data's params attribute returns its params
     */
    public function test_entity_field_data_attribute_params(): void {
        $this->generator()->create_evidence_type_entity(['fields' => 1, 'name' => 'Type_One']);
        $item = $this->generator()->create_evidence_item_entity(['type' => 'Type_One']);

        $field = $this->fields()->all()[0];

        $parent_data = [
            'fieldid'    => $field->id,
            'evidenceid' => $item->id,
            'data'       => 'Data'
        ];

        $parent = new entity\evidence_field_data($parent_data);
        $parent->save();

        $children_count = 3;

        $expected = [];

        for ($i = 0; $i < $children_count; $i++) {
            $child = new entity\evidence_field_data_param([
                'dataid' => $parent->id,
                'value'  => "Param Data $i"
            ]);
            $expected[] = $child->save()->to_array();
        }

        $actual = $parent->params->to_array();

        $this->assertEquals($expected, $actual);

        $parent->delete();
        $this->assertEmpty($parent->params);
    }

    /**
     * Test that the filter_by_standard_location() and filter_by_rol_location() filters in evidence_item_repository return as expected
     */
    public function test_evidence_item_repository_location_filters(): void {
        $standard_type = $this->generator()->create_evidence_type_entity([
            'location' => evidence_type_model::LOCATION_EVIDENCE_BANK
        ]);
        $standard_items = [];
        for ($i = 0; $i < 3; $i++) {
            $standard_items[] = $this->generator()
                ->create_evidence_item_entity(['typeid' => $standard_type->id, 'imported' => 0])
                ->to_array();
        }

        $system_type = $this->generator()->create_evidence_type_entity([
            'location' => evidence_type_model::LOCATION_RECORD_OF_LEARNING
        ]);
        $system_items = [];
        for ($i = 0; $i < 3; $i++) {
            $system_items[] = $this->generator()
                ->create_evidence_item_entity(['typeid' => $system_type->id, 'imported' => 1])
                ->to_array();
        }

        $this->assertEquals($standard_items, entity\evidence_item::repository()
            ->filter_by_standard_location()->order_by('id')->get()->to_array()
        );
        $this->assertEquals($system_items, entity\evidence_item::repository()
            ->filter_by_rol_location()->order_by('id')->get()->to_array()
        );
    }

    /**
     * Test that the filter_by_standard_location() and filter_by_system_location() filters in evidence_type_repository return as expected
     */
    public function test_evidence_type_repository_location_filters(): void {
        $standard_types = [];
        for ($i = 0; $i < 3; $i++) {
            $standard_types[] = $this->generator()->create_evidence_type_entity([
                'location' => evidence_type_model::LOCATION_EVIDENCE_BANK,
            ])->to_array();
        }

        $import_types = [];
        for ($i = 0; $i < 3; $i++) {
            $import_types[] = $this->generator()->create_evidence_type_entity([
                'location' => evidence_type_model::LOCATION_RECORD_OF_LEARNING,
            ])->to_array();
        }

        $non_system_types = array_merge($standard_types, $import_types);
        $system_types = evidence_type_entity::repository()
            ->where_in('idnumber', evidence_type_model::DEFAULT_SYSTEM_TYPES)
            ->get()
            ->to_array();

        $this->assertEquals($non_system_types, evidence_type_entity::repository()
            ->filter_by_standard_location()->order_by('id')->get()->to_array()
        );
        $this->assertEquals($system_types, evidence_type_entity::repository()
            ->filter_by_system_location()->order_by('id')->get()->to_array()
        );
    }

    /**
     * Test that the filter_by_active() and filter_by_hidden() filters in evidence_type_repository return as expected
     */
    public function test_evidence_type_repository_status_filters(): void {
        $active_types = [];
        for ($i = 0; $i < 3; $i++) {
            $active_types[] = $this->generator()->create_evidence_type_entity([
                'status' => evidence_type_model::STATUS_ACTIVE,
            ])->to_array();
        }

        $hidden_types = [];
        for ($i = 0; $i < 3; $i++) {
            $hidden_types[] = $this->generator()->create_evidence_type_entity([
                'status' => evidence_type_model::STATUS_HIDDEN,
            ])->to_array();
        }

        $this->assertEquals($active_types, $this->type_repository()
            ->filter_by_active()->order_by('id')->get()->to_array()
        );
        $this->assertEquals($hidden_types, $this->type_repository()
            ->filter_by_hidden()->order_by('id')->get()->to_array()
        );
    }

}
