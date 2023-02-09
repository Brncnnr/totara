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

require_once(__DIR__ . '/child_element_manager_testcase.php');

use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group perform
 */
class mod_perform_webapi_resolver_mutation_create_child_element_testcase extends child_element_manager_testcase {
    private const MUTATION = 'mod_perform_create_child_element';

    use webapi_phpunit_helper;

    public function test_create_child_element_success() {
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
                'after_sibling_element_id' => null,
                'parent' => $test_data['parent']->id,
            ]
        ];
        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $parent = $result['parent_element'];
        $children = $parent->get_children()->all();
        $this->assertCount(4, $children);
        $this->assertEquals($new_child_title, $children[0]->title);
        $this->assertEquals($test_data['children']['a']->title, $children[1]->title);
    }
}