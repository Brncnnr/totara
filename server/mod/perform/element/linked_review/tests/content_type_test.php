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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package performelement_linked_review
 */

use mod_perform\constants;
use performelement_linked_review\content_type_factory;
use performelement_linked_review\testing\generator as linked_review_generator;
use totara_competency\performelement_linked_review\competency_assignment;
use totara_core\advanced_feature;
use totara_core\relationship\relationship;

/**
 * @group perform
 * @group perform_element
 */
class performelement_linked_review_content_type_testcase extends advanced_testcase {

    public function test_identifiers_are_unique(): void {
        $content_types = content_type_factory::get_all();

        $identifier_occurrence_count = [];
        foreach ($content_types as $content_type) {
            if (!isset($identifier_occurrence_count[$content_type::get_identifier()])) {
                $identifier_occurrence_count[$content_type::get_identifier()] = 0;
            }
            $identifier_occurrence_count[$content_type::get_identifier()]++;
        }

        foreach ($identifier_occurrence_count as $identifier => $count) {
            $this->assertEquals(
                1, $count,
                "There are multiple review content types using the same identifier ('$identifier') when there should only be one!"
            );
        }
    }

    public function test_factory_get_from_identifier(): void {
        if (!class_exists('\totara_competency\performelement_linked_review\competency_assignment')) {
            $this->markTestSkipped('Test requires totara_competency');
        }

        $content_type = content_type_factory::get_class_name_from_identifier('totara_competency');
        $this->assertStringContainsString('totara_competency', $content_type);
        $this->assertEquals('totara_competency', $content_type::get_identifier());

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Couldn't locate a review content type with the identifier 'Not A Content Type!'");
        content_type_factory::get_class_name_from_identifier('Not A Content Type!');
    }

    public function test_factory_get_all_enabled(): void {
        if (!class_exists('\totara_competency\performelement_linked_review\competency_assignment')) {
            $this->markTestSkipped('Test requires totara_competency');
        }

        $all_content_types = content_type_factory::get_all();
        $enabled_content_types = content_type_factory::get_all_enabled();
        $competency_content_type = competency_assignment::class;

        $this->assertEquals($all_content_types->to_array(), $enabled_content_types->to_array());
        $this->assertContainsEquals($competency_content_type, $enabled_content_types->to_array());

        advanced_feature::disable('competency_assignment');

        $enabled_content_types = content_type_factory::get_all_enabled();
        $this->assertNotContainsEquals($competency_content_type, $enabled_content_types->to_array());
    }

    public function test_saving_element_successful(): void {
        if (!class_exists('\totara_competency\performelement_linked_review\competency_assignment')) {
            $this->markTestSkipped('Test requires totara_competency');
        }

        $subject_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_SUBJECT);
        $manager_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_MANAGER);

        $element1_input_data = [
            'content_type' => 'totara_competency',
            'content_type_settings' => [
                'enable_rating' => false,
                'rating_relationship' => null,
            ],
            'selection_relationships' => [$subject_relationship->id],
        ];
        $element1 = linked_review_generator::instance()->create_linked_review_element($element1_input_data);
        $element1_output_data = json_decode($element1->data, true);
        unset($element1_output_data['components']);
        unset($element1_output_data['compatible_child_element_plugins']);
        $this->assertEquals([
            'content_type' => 'totara_competency',
            'content_type_settings' => [
                'enable_rating' => false,
                'rating_relationship' => null,
            ],
            'selection_relationships' => [$subject_relationship->id],
            'selection_relationships_display' => [
                [
                    'id' => $subject_relationship->id,
                    'name' => $subject_relationship->name
                ],
            ],
            'content_type_display' => 'Competencies',
            'content_type_settings_display' => [
                [
                    'title' => get_string('enable_performance_rating', 'totara_competency'),
                    'value' => get_string('no'),
                ],
            ]
        ], $element1_output_data);

        $element2_input_data = [
            'content_type' => 'totara_competency',
            'content_type_settings' => [
                'enable_rating' => true,
                'rating_relationship' => $manager_relationship->id,
            ],
            'selection_relationships' => [$manager_relationship->id],
        ];
        $element2 = linked_review_generator::instance()->create_linked_review_element($element2_input_data);
        $element2_output_data = json_decode($element2->data, true);
        unset($element2_output_data['components']);
        unset($element2_output_data['compatible_child_element_plugins']);
        $this->assertEquals([
            'content_type' => 'totara_competency',
            'content_type_settings' => [
                'enable_rating' => true,
                'rating_relationship' => $manager_relationship->id,
                'rating_relationship_name' => $manager_relationship->get_name(),
            ],
            'selection_relationships' => [$manager_relationship->id],
            'selection_relationships_display' => [
                [
                    'id' => $manager_relationship->id,
                    'name' => $manager_relationship->name
                ],
            ],
            'content_type_display' => 'Competencies',
            'content_type_settings_display' => [
                [
                    'title' => get_string('enable_performance_rating', 'totara_competency'),
                    'value' => get_string('yes'),
                ],
                [
                    'title' => get_string('enable_performance_rating_participant', 'totara_competency'),
                    'value' => $manager_relationship->name,
                ],
            ]
        ], $element2_output_data);
    }

    public function invalid_element_data_provider(): array {
        return [
            [
                'data' => [],
                'expected_error_message' => 'No additional data was specified when saving the element with ID',
            ],
            [
                'data' => ['invalid_key' => null],
                'expected_error_message' => 'The saved data must contain and only contain these keys:',
            ],
            [
                'data' => [
                    'content_type' => 'not a plugin',
                    'content_type_settings' => [],
                    'selection_relationships' => [],
                ],
                'expected_error_message' => "Couldn't locate a review content type with the identifier 'not a plugin'",
            ],
            [
                'data' => [
                    'content_type' => 'totara_competency',
                    'content_type_settings' => [
                        'invalid' => 'setting',
                    ],
                    'selection_relationships' => [],
                ],
                'expected_error_message' => 'Invalid setting(s) keys were saved:',
            ],
            [
                'data' => [
                    'content_type' => 'totara_competency',
                    'content_type_settings' => [
                        'enable_rating' => false,
                    ],
                    'selection_relationships' => [],
                ],
                'expected_error_message' => 'No selection relationship IDs were specified',
            ],
            [
                'data' => [
                    'content_type' => 'totara_competency',
                    'content_type_settings' => [
                        'enable_rating' => false,
                    ],
                    'selection_relationships' => ['-1'],
                ],
                'expected_error_message' => 'Invalid selection relationship ID specified:',
            ],
        ];
    }

    /**
     * @dataProvider invalid_element_data_provider
     * @param array $data
     * @param string $expected_message
     */
    public function test_element_saving_validation(array $data, string $expected_message): void {
        if (!class_exists('\totara_competency\performelement_linked_review\competency_assignment')) {
            $this->markTestSkipped('Test requires totara_competency');
        }

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage($expected_message);
        linked_review_generator::instance()->create_linked_review_element($data);
    }

}
