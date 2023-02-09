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
class mod_perform_webapi_resolver_mutation_reorder_section_element_testcase extends section_element_manager_testcase {
    private const MUTATION = 'mod_perform_reorder_section_element';

    use webapi_phpunit_helper;

    public function test_reorder_section_element_success() {
        $test_data = $this->generate_data();
        $args = [
            'input' => [
                'section_element_id' => $test_data['section_elements']['a']->id,
                'move_to_after_section_element_id' => $test_data['section_elements']['c']->id,
            ]
        ];
        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);

        /** @var section $section*/
        $section = $result['section'];
        $section_elements = $section->get_section_elements()->all();
        $this->assertCount(3, $section_elements);
        $this->assertEquals(1, $section_elements[0]->sort_order);
        $this->assertEquals($test_data['section_elements']['b']->id, $section_elements[0]->id);
        $this->assertEquals(2, $section_elements[1]->sort_order);
        $this->assertEquals($test_data['section_elements']['c']->id, $section_elements[1]->id);

        $this->assertEquals(3, $section_elements[2]->sort_order);
        $this->assertEquals($test_data['section_elements']['a']->id, $section_elements[2]->id);
    }
}
