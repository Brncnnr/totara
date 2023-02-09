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
 * @package totara_core
 */

use core_phpunit\testcase;
use totara_core\tui\tree\tree_node;
use totara_core\webapi\resolver\type\tui_tree_node;
use totara_webapi\phpunit\webapi_phpunit_helper;

class totara_core_webapi_resolver_type_tui_tree_node_test extends testcase {

    use webapi_phpunit_helper;

    public function test_resolve_tree(): void {
        $tree = (new tree_node('1', 'Root Node'))->add_children(
            (new tree_node('2', 'Child Node 1'))->add_children(
                (new tree_node('2-1', 'Child Node 1, Grandchild Node 1'))
                    ->set_content('Content 1'),
                (new tree_node('2-2', 'Child Node 1, Grandchild Node 2'))
                    ->set_content('Content 2'),
            ),
            (new tree_node('3', 'Child Node 2'))->add_children(
                (new tree_node('3-1', 'Child Node 2, Grandchild Node 1'))
                    ->set_content('Content 3'),
                (new tree_node('3-2', 'Child Node 2, Grandchild Node 2'))
                    ->set_content('Content 4'),
            ),
        );

        $expected_result = [
            'id' => '1',
            'label' => 'Root Node',
            'content' => null,
            'children' => [
                [
                    'id' => '2',
                    'label' => 'Child Node 1',
                    'content' => null,
                    'children' => [
                        [
                            'id' => '2-1',
                            'label' => 'Child Node 1, Grandchild Node 1',
                            'content' => 'Content 1',
                            'children' => [],
                        ],
                        [
                            'id' => '2-2',
                            'label' => 'Child Node 1, Grandchild Node 2',
                            'content' => 'Content 2',
                            'children' => [],
                        ],
                    ],
                ],
                [
                    'id' => '3',
                    'label' => 'Child Node 2',
                    'content' => null,
                    'children' => [
                        [
                            'id' => '3-1',
                            'label' => 'Child Node 2, Grandchild Node 1',
                            'content' => 'Content 3',
                            'children' => [],
                        ],
                        [
                            'id' => '3-2',
                            'label' => 'Child Node 2, Grandchild Node 2',
                            'content' => 'Content 4',
                            'children' => [],
                        ],
                    ],
                ],
            ],
        ];

        $resolved_tree = $this->resolve_node($tree);
        $this->assertEquals($expected_result, $resolved_tree);
    }

    /**
     * Ensure that the label has plain formatting applied.
     * Note that for now, the label is always formatted as plain due to how it is displayed in the front end.
     */
    public function test_label_is_formatted_correctly(): void {
        $xss_string = "Leaf <script>alert('XSS!')</script>Label";
        $leaf = new tree_node('1', $xss_string);

        $resolved_label_field = $this->resolve_graphql_type($this->get_graphql_name(tui_tree_node::class), 'label', $leaf);
        $this->assertNotEquals($xss_string, $resolved_label_field);
        $this->assertEquals("Leaf alert('XSS!')Label", $resolved_label_field);
    }

    /**
     * Recursively resolves the tree using the GraphQL type fields.
     * This will return basically the same result as a query via Apollo would.
     *
     * @param tree_node $tree_node
     * @return array
     */
    private function resolve_node(tree_node $tree_node): array {
        $type_name = $this->get_graphql_name(tui_tree_node::class);
        $result = [
            'id' => $this->resolve_graphql_type($type_name, 'id', $tree_node),
            'label' => $this->resolve_graphql_type($type_name, 'label', $tree_node),
            'content' => $this->resolve_graphql_type($type_name, 'content', $tree_node),
        ];

        $children = $this->resolve_graphql_type($type_name, 'children', $tree_node);
        $result['children'] = [];
        foreach ($children as $child) {
            $result['children'][] = $this->resolve_node($child);
        }

        return $result;
    }

}
