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

use mod_perform\models\activity\section;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group perform
 */
class mod_perform_webapi_resolver_mutation_update_child_element_testcase extends child_element_manager_testcase {
    private const MUTATION = 'mod_perform_update_child_element';

    use webapi_phpunit_helper;

    public function test_update_child_element_success() {
        $test_data = $this->generate_data();
        $new_title = 'Updated title';
        $args = [
            'input' => [
                'element_details' => [
                    'title' => $new_title,
                ],
                'element_id' => $test_data['children']['a']->id,
            ]
        ];
        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);

        /** @var section $section*/
        $element = $result['element'];
        $this->assertEquals($new_title, $element->title);
    }

    public function test_update_child_element_with_wrong_parent_element() {
        $test_data = $this->generate_data();
        $new_title = 'Updated title';
        $args = [
            'input' => [
                'element_details' => [
                    'title' => $new_title,
                ],
                'element_id' => $test_data['parent']->id,
            ]
        ];
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Element is not a child element.");
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }
}
