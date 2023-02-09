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
use totara_core\advanced_feature;

/**
 * @group perform
 */
class mod_perform_webapi_resolver_mutation_update_element_in_section_testcase extends section_element_manager_testcase {
    private const MUTATION = 'mod_perform_update_element_in_section';

    use webapi_phpunit_helper;

    public function test_update_element_in_section_success() {
        $test_data = $this->generate_data();
        $new_title = 'Updated title';
        $args = [
            'input' => [
                'element_details' => [
                    'title' => $new_title,
                ],
                'section_element_id' => $test_data['section_elements']['a']->id,
            ]
        ];
        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);

        /** @var section $section*/
        $section = $result['section'];
        $section_elements = $section->get_section_elements()->all();
        $this->assertCount(3, $section_elements);
        $this->assertEquals($new_title, $section_elements[0]->element->title);
    }

    public function test_update_element_with_invalid_data(): void {
        $test_data = $this->generate_data();
        $new_title = 'Updated title';
        $args = [
            'input' => [
                'element_details' => [
                    'title' => $new_title,
                    'data'  => '{ bar: "baz", }'
                ],
                'section_element_id' => $test_data['section_elements']['a']->id,
            ]
        ];
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Invalid element data format, expected a json string");
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    public function test_update_element_with_invalid_title(): void {
        $test_data = $this->generate_data();
        $args = [
            'input' => [
                'element_details' => [
                    'title' => '',
                ],
                'section_element_id' => $test_data['section_elements']['a']->id,
            ]
        ];

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Respondable elements must include a title');
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    public function test_failed_ajax_query(): void {
        $test_data = $this->generate_data();
        $new_title = 'Updated title';
        $args = [
            'input' => [
                'element_details' => [
                    'title' => $new_title,
                ],
                'section_element_id' => $test_data['section_elements']['a']->id,
            ]
        ];
        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
    }
}
