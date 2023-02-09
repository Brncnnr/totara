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
 * @author  Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

use contentmarketplace_linkedin\constants;
use contentmarketplace_linkedin\webapi\resolver\query\catalog_import_learning_objects_filter_options;
use core_phpunit\testcase;
use totara_contentmarketplace\plugininfo\contentmarketplace;
use totara_contentmarketplace\testing\helper;
use totara_core\tui\tree\tree_node;
use totara_webapi\phpunit\webapi_phpunit_helper;
use contentmarketplace_linkedin\testing\generator;

/**
 * @covers \contentmarketplace_linkedin\webapi\resolver\query\catalog_import_learning_objects_filter_options
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_webapi_resolver_query_catalog_import_learning_objects_filter_options_testcase extends testcase {

    use webapi_phpunit_helper;

    private const QUERY = 'contentmarketplace_linkedin_catalog_import_learning_objects_filter_options';

    private const NODE_TYPE = 'totara_core_tui_tree_node';

    private const CONTENT_TYPE = 'contentmarketplace_linkedin_catalog_import_filter_select_option';

    protected function setUp(): void {
        parent::setUp();
        self::setAdminUser();
        $plugin = contentmarketplace::plugin('linkedin');
        $plugin->enable();
    }

    public function test_resolve_asset_types(): void {
        $result = $this->resolve_graphql_query(self::QUERY);
        $assert_types_node = reset($result['asset_type']);

        $expected_data = [
            'id' => 'asset_types',
            'label' => 'Type',
            'content' => [
                [
                    'id' => 'COURSE',
                    'label' => 'Courses',
                ],
                [
                    'id' => 'VIDEO',
                    'label' => 'Videos',
                ]
            ],
        ];

        $this->assertEquals($expected_data, $this->resolve_node($assert_types_node));
    }

    public function test_resolve_time_to_complete(): void {
        $result = $this->resolve_graphql_query(self::QUERY);
        $time_to_complete_node = reset($result['time_to_complete']);

        $expected_data = [
            'id' => 'time_to_complete',
            'label' => 'Time to Complete',
            'content' => [
                [
                    'id' => '{"max":600}',
                    'label' => '< 10 mins',
                ],
                [
                    'id' => '{"min":600,"max":1800}',
                    'label' => '10 - 30 mins',
                ],
                [
                    'id' => '{"min":1800,"max":3600}',
                    'label' => '30 - 60 mins',
                ],
                [
                    'id' => '{"min":3600,"max":7200}',
                    'label' => '1 - 2 hours',
                ],
                [
                    'id' => '{"min":7200,"max":10800}',
                    'label' => '2 - 3 hours',
                ],
                [
                    'id' => '{"min":10800}',
                    'label' => '3+ hours',
                ],
            ],
        ];

        $this->assertEquals($expected_data, $this->resolve_node($time_to_complete_node));
    }

    public function test_resolve_in_catalog(): void {
        $result = $this->resolve_graphql_query(self::QUERY);
        $in_catalog_node = reset($result['in_catalog']);

        $expected_data = [
            'id' => 'in_catalog',
            'label' => 'Added to your catalogue',
            'content' => [
                [
                    'id' => 'yes',
                    'label' => 'Added to your catalogue',
                ],
                [
                    'id' => 'no',
                    'label' => 'Not added to your catalogue',
                ]
            ],
        ];

        self::assertEquals($expected_data, $this->resolve_node($in_catalog_node));
    }

    public function test_plugin_disabled(): void {
        $plugin = contentmarketplace::plugin('linkedin');

        $plugin->enable();
        $this->resolve_graphql_query(self::QUERY);

        $plugin->disable();
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('LinkedIn Learning content marketplace disabled');
        $this->resolve_graphql_query(self::QUERY);
    }

    public function test_no_permission(): void {
        $role_id = helper::get_authenticated_user_role();
        $context_id = helper::get_default_course_category_context()->id;
        $user = self::getDataGenerator()->create_user();
        self::setUser($user);

        assign_capability('totara/contentmarketplace:add', CAP_ALLOW, $role_id, $context_id);
        $this->resolve_graphql_query(self::QUERY);

        unassign_capability('totara/contentmarketplace:add', $role_id, $context_id);
        $this->expectException(required_capability_exception::class);
        $this->resolve_graphql_query(self::QUERY);
    }

    /**
     * @return void
     */
    public function test_resolve_subjects(): void {
        $generator = generator::instance();
        $classification_1 = $generator->create_classification(null, ['type' => constants::CLASSIFICATION_TYPE_LIBRARY]);
        $classification_2 = $generator->create_classification(null, ['name' => 'Badmin']);

        $generator->create_classification_relationship($classification_1->id, $classification_2->id);
        self::setAdminUser();

        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(catalog_import_learning_objects_filter_options::class)
        );

        $assert_types_node = reset($result['subjects']);
        $resolved_nodes = $this->resolve_node($assert_types_node);

        $expected = [
            'id' => 'subjects',
            'label' => get_string('catalog_filter_subjects', 'contentmarketplace_linkedin'),
            'children' => [
                [
                    'id' => $classification_1->id,
                    'label' => $classification_1->name,
                    'content' => [
                        [
                            'id' => $classification_2->id,
                            'label' => $classification_2->name
                        ]
                    ]
                ]
            ]
        ];
        self::assertEquals($expected, $resolved_nodes);
    }

    /**
     * @return void
     */
    public function test_resolve_subjects_with_locale_language(): void {
        $generator = generator::instance();
        $classification_1 = $generator->create_classification(
            null,
            [
                'type' => constants::CLASSIFICATION_TYPE_LIBRARY,
                'locale_language' => 'de',
                'locale_country' => 'DE'
            ]
        );

        $classification_2 = $generator->create_classification(
            null,
            [
                'type' => constants::CLASSIFICATION_TYPE_SUBJECT,
                'locale_language' => 'de',
                'locale_country' => 'DE'
            ]
        );

        $generator->create_classification_relationship($classification_1->id, $classification_2->id);
        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(catalog_import_learning_objects_filter_options::class),
            [
                'input' => [
                    'language' => 'ja'
                ]
            ]
        );

        $node = reset($result['subjects']);
        self::assertEquals(
            [
                'id' => 'subjects',
                'label' => get_string('catalog_filter_subjects', 'contentmarketplace_linkedin', 'ja')
            ],
            $this->resolve_node($node)
        );
    }

    /**
     * Recursively resolves the tree using the GraphQL type fields.
     * This will return basically the same result as a query via Apollo would.
     *
     * @param tree_node $node
     * @return array
     */
    private function resolve_node(tree_node $node): array {
        $result = [
            'id' => $this->resolve_graphql_type(self::NODE_TYPE, 'id', $node),
            'label' => $this->resolve_graphql_type(self::NODE_TYPE, 'label', $node),
        ];

        $children = $this->resolve_graphql_type(self::NODE_TYPE, 'children', $node);
        foreach ($children as $child) {
            $result['children'][] = $this->resolve_node($child);
        }

        $content = $this->resolve_graphql_type(self::NODE_TYPE, 'content', $node);
        if (isset($content)) {
            foreach ($content as $item) {
                $result['content'][] = [
                    'id' => $this->resolve_graphql_type(self::CONTENT_TYPE, 'id', $item),
                    'label' => $this->resolve_graphql_type(self::CONTENT_TYPE, 'label', $item),
                ];
            }
        }

        return $result;
    }

}
