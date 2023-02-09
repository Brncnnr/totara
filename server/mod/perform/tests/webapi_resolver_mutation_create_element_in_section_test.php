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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

require_once(__DIR__ . '/section_element_manager_testcase.php');

use mod_perform\models\activity\section;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group perform
 */
class mod_perform_webapi_resolver_mutation_create_element_in_section_testcase extends section_element_manager_testcase {
    private const MUTATION = 'mod_perform_create_element_in_section';

    use webapi_phpunit_helper;

    public function test_create_element_in_section_success() {
        $test_data = $this->generate_data();
        $new_child_title = 'Child 047';
        $args = [
            'input' => [
                'element' => [
                    'plugin_name' => 'short_text',
                    'element_details' => [
                        'title' => $new_child_title,
                    ],
                ],
                'after_section_element_id' => $test_data['section_elements']['b']->id,
                'section_id' => $test_data['section']->id
            ]
        ];
        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        /** @var section $section*/
        $section = $result['section'];
        $section_elements = $section->get_section_elements()->all();
        $this->assertCount(4, $section_elements);
        $this->assertEquals($new_child_title, $section_elements[2]->element->title);
        $this->assertEquals(3, $section_elements[2]->sort_order);
    }

    public function test_missing_title(): void {
        $test_data = $this->generate_data();
        $args = [
            'input' => [
                'element' => [
                    'plugin_name' => 'short_text',
                    'element_details' => [
                        'identifier' => 'test identifier',
                    ],
                ],
                'after_section_element_id' => $test_data['section_elements']['b']->id,
                'section_id' => $test_data['section']->id
            ]
        ];

        $this->expectException(invalid_parameter_exception::class);
        $this->expectExceptionMessage('title must be provided');
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    public function test_create_element_with_invalid_data() {
        $test_data = $this->generate_data();
        $args = [
            'input' => [
                'element' => [
                    'plugin_name' => 'short_text',
                    'element_details' => [
                        'title' => 'test title',
                        'identifier' => 'test identifier',
                        'data'  => '{ bar: "baz", }'
                    ],
                ],
                'after_section_element_id' => $test_data['section_elements']['b']->id,
                'section_id' => $test_data['section']->id
            ]
        ];
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Invalid element data format, expected a json string");
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }
}