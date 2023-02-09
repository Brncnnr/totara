<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

use core_phpunit\testcase;
use mod_approval\form_schema\form_schema;
use mod_approval\form_schema\form_schema_field;
use mod_approval\form_schema\form_schema_section;
use mod_approval\model\form\form_data;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage_formview;
use mod_approval\testing\approval_workflow_test_setup;
use mod_approval\testing\formview_generator_object;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\form_schema\form_schema
 */
class mod_approval_form_schema_testcase extends testcase {

    use approval_workflow_test_setup;

    private $form_schema;

    public function setUp(): void {
        parent::setUp();
        global $CFG;
        $json_data = file_get_contents($CFG->dirroot . '/mod/approval/tests/fixtures/form/test_form.json');
        $this->form_schema = form_schema::from_json($json_data);
    }

    public function tearDown(): void {
        $this->form_schema = null;
        parent::tearDown();
    }

    /**
     * @covers ::get_version
     */
    public function test_get_version(): void {
        $this->assertEquals('2021030200', $this->form_schema->get_version());
    }

    /**
     * @covers ::get_fields
     */
    public function test_get_fields(): void {
        $expected_fields = [
            'agency_code',
            'request_status',
            'detailed_description',
            'applicant_name',
            'training_vendor',
        ];
        $actual_fields = $this->form_schema->get_fields();
        $this->assertEquals($expected_fields, array_keys($actual_fields));
        /** @var form_schema_field $agency_code */
        $agency_code = $actual_fields['agency_code'];
        $this->assertInstanceOf(form_schema_field::class, $agency_code);
        $this->assertEquals('A', $agency_code->line);
        $this->assertEquals('Agency code', $agency_code->label);
        $this->assertEquals('text', $agency_code->type);
        $this->assertEquals('top/agency_code', $agency_code->get_index());
        $this->assertNull($agency_code->instruction);
        $this->assertNull($agency_code->default);
        $this->assertFalse($agency_code->required);
        $this->assertFalse($agency_code->disabled);
    }

    /**
     * @covers ::get_fields_of_type
     */
    public function test_get_fields_of_type(): void {
        $text_fields = $this->form_schema->get_fields_of_type('text');
        foreach ($text_fields as $text_field) {
            $this->assertEquals('text', $text_field->type);
        }
    }

    /**
     * @covers ::get_sections
     */
    public function test_get_sections(): void {
        $expected_sections = [
            'A' => ['line' => 'Section A', 'label' => 'Basic Information'],
            'B' => ['line' => 'Section B', 'label' => 'Course Information'],
        ];
        $actual_sections = $this->form_schema->get_sections();
        $this->assertEquals(array_keys($expected_sections), array_keys($actual_sections));
        /** @var form_schema_section $section_a */
        $section_a = $actual_sections['A'];
        $this->assertInstanceOf(form_schema_section::class, $section_a);
        $this->assertEquals('A', $section_a->get_key());
        $this->assertEquals('Section A', $section_a->line);
        $this->assertEquals('Basic Information', $section_a->label);
    }

    /**
     * @covers ::get_top_level_fields
     */
    public function test_get_top_level_fields(): void {
        $expected_fields = [
            'agency_code',
            'request_status',
            'detailed_description',
        ];
        $this->assertEquals($expected_fields, array_keys($this->form_schema->get_top_level_fields()));
    }

    /**
     * @covers ::get_section_fields
     */
    public function test_get_section_fields(): void {
        $expected_fields_A = ['applicant_name',];
        $expected_fields_B = ['training_vendor'];
        $this->assertEquals($expected_fields_A, array_keys($this->form_schema->get_section_fields('A')));
        $this->assertEquals($expected_fields_B, array_keys($this->form_schema->get_section_fields('B')));
    }

    /**
     * @covers ::has_field
     */
    public function test_has_field(): void {
        $this->assertTrue($this->form_schema->has_field('applicant_name'));
        $this->assertFalse($this->form_schema->has_field('unknown_key'));
    }

    /**
     * @covers ::get_field
     */
    public function test_get_field(): void {
        // Simple field.
        $expected_field = ['line' => '1', 'label' => 'Applicant\'s Name', 'type' => 'fullname'];
        $field = $this->form_schema->get_field('applicant_name');
        $this->assertInstanceOf(form_schema_field::class, $field);
        $this->assertEquals('0/applicant_name', $field->get_index());
        $this->assertEquals('applicant_name', $field->get_field_key());
        $this->assertEquals('0', $field->get_section_index());
        foreach ($expected_field as $key => $value) {
            $this->assertEquals($value, $field->{$key});
        }

        // Complex field (has choices).
        $expected_field = ["line" => "B", "label" => "Request Status", "type" => "select_one"];
        $field = $this->form_schema->get_field('request_status');
        $this->assertEquals('top/request_status', $field->get_index());
        $this->assertEquals('request_status', $field->get_field_key());
        $this->assertEquals('top', $field->get_section_index());
        foreach ($expected_field as $key => $value) {
            $this->assertEquals($value, $field->{$key});
        }
        $output = $field->to_stdClass();
        $this->assertEquals('request_status', $output->key);
        $this->assertEquals('B', $output->line);
        $this->assertEquals('Request Status', $output->label);
        $this->assertNull($output->instruction);
        $this->assertNull($output->help);
        $this->assertEquals('select_one', $output->type);
        $this->assertObjectNotHasAttribute('format', $output->attrs);
        $this->assertFalse($output->required);
        $this->assertFalse($output->disabled);
        $this->assertNull($output->default);
        $this->assertIsArray($output->attrs->choices);
        $this->assertCount(3, $output->attrs->choices);
    }

    /**
     * @covers ::get_field_section
     */
    public function test_get_field_section(): void {
        $expected_section_a = ["line" => "Section A", "label" => "Basic Information"];
        $section = $this->form_schema->get_field_section('applicant_name');
        $this->assertInstanceOf(form_schema_section::class, $section);
        $this->assertEquals('A', $section->get_key());
        foreach ($expected_section_a as $key => $value) {
            $this->assertEquals($value, $section->{$key});
        }
    }

    /**
     * @covers ::to_json
     */
    public function test_to_json(): void {
        $expected =
            '{"title":"Test Form","shortname":"test","revision":"1.0","version":"2021030200","language":"en-US","fields":[{"key":"agency_code","line":"A","label":"Agency code","required":false,"type":"text"},{"key":"request_status","line":"B","label":"Request Status","type":"select_one","required":false,"default":null,"attrs":{"choices":[{"key":null,"label":"Select one"},{"key":"Yes","label":"Yes, of course"},{"key":"No","label":"No thank you"}]}},{"key":"detailed_description","line":"C","label":"A detailed description","required":false,"type":"editor"}],"sections":[{"key":"A","line":"Section A","label":"Basic Information","fields":[{"key":"applicant_name","line":"1","label":"Applicant\'s Name","instruction":"Last, First, Middle Initial","required":false,"type":"fullname","attrs":{"format":"last,first,middle-initial"}}]},{"key":"B","line":"Section B","label":"Course Information","fields":[{"key":"training_vendor","line":"1","label":"Name and Mailing Address of Training Vendor","instruction":"No., Street, City, Sata, ZIP Code","required":false,"type":"address"}]}]}';
        $this->assertEquals($expected, $this->form_schema->to_json());
    }

    /**
     * @covers ::apply_formviews
     */
    public function test_apply_formviews(): void {
        $this->setAdminUser();
        list($workflow_entity, $framework, $assignment) = $this->create_workflow_and_assignment();
        $workflow = workflow::load_by_entity($workflow_entity);

        /** @var \mod_approval\model\workflow\workflow_stage $stage1 */
        $stage1 = $workflow->latest_version->stages->first();
        $new_schema = $this->form_schema->apply_formviews($stage1->formviews);

        // create_workflow_and_assignment creates a default formviews at stage 1
        $this->assertCount(5, $new_schema->get_fields());
        $field = $new_schema->get_field('agency_code');
        $this->assertInstanceOf(form_schema_field::class, $field);
        $this->assertEquals('A', $field->line);
        $this->assertEquals('Agency code', $field->label);
        $this->assertEquals('text', $field->type);
        $this->assertEquals('top/agency_code', $field->get_index());
        $this->assertNull($field->instruction);
        $this->assertNull($field->default);
        $this->assertFalse($field->required);
        $this->assertFalse($field->disabled);

        $schema = json_decode($new_schema->to_json());
        $this->assertCount(2, $schema->sections);
        $this->assertEquals('A', $schema->sections[0]->key);
        $this->assertEquals('B', $schema->sections[1]->key);
    }

    /**
     * @covers ::set_field_default
     * @covers ::set_field_disabled
     */
    public function test_set_field_properties(): void {
        $key = 'applicant_name';
        $field = $this->form_schema->get_field($key);
        $this->assertNull($field->default);
        $this->assertFalse($field->disabled);

        $this->form_schema->set_field_default($key, 'Olivia S. Parsons');
        $this->form_schema->set_field_disabled($key, true);
        $field = $this->form_schema->get_field($key);
        $this->assertEquals('Olivia S. Parsons', $field->default);
        $this->assertTrue($field->disabled);
    }
}
