<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\models\activity\section;
use mod_perform\webapi\resolver\mutation\update_section_elements;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\mutation\update_section_elements
 *
 * Tests the mutation to add, update and delete section elements
 */
class mod_perform_webapi_resolver_mutation_update_section_elements_testcase extends advanced_testcase {
    private const MUTATION = 'mod_perform_update_section_elements';

    use webapi_phpunit_helper;

    public function test_create_new_section_elements(): void {
        global $DB;

        self::setAdminUser();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();

        $activity = $perform_generator->create_activity_in_container();
        $section = $perform_generator->create_section($activity);

        $args = [
            'input' => [
                'section_id' => $section->id,
                'create_new' => [
                    [
                        'plugin_name' => 'short_text',
                        'title' => 'Test title 1',
                        'identifier' => 'Test identifier 1',
                        'data' => '{"aaa":"aaa"}',
                        'is_required' => true,
                        'sort_order' => 2,
                    ],
                    [
                        'plugin_name' => 'short_text',
                        'title' => 'Test title 2',
                        'identifier' => 'Test identifier 2',
                        'data' => '{"bbb":"bbb"}',
                        'is_required' => true,
                        'sort_order' => 1,
                    ],
                ],
            ]
        ];

        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $this->assertDebuggingCalled(
            [
                'This mutation has been deprecated. Use the individual section element mutations.',
                'Method has been deprecated. Use section_element_manager::remove_section_elements',
                'Method has been deprecated. Use section_element_manager::add_element_after',
                'Method has been deprecated. Use section_element_manager::add_element_after',
                'Method has been deprecated. Use section_element_manager::move_section_elements',
            ],
            DEBUG_DEVELOPER
        );

        // Check that the changes were made.
        $section_element_records = $DB->get_records('perform_section_element', ['section_id' => $section->id]);
        $this->assertCount(2, $section_element_records);

        $section_elements = [];
        foreach ($section_element_records as $section_element_record) {
            $section_elements[$section_element_record->sort_order] = $section_element_record;
        }

        $element1 = $DB->get_record('perform_element', ['id' => $section_elements[1]->element_id]);
        $this->assertEquals('Test title 2', $element1->title);
        $identifier1 = $DB->get_record('perform_element_identifier', ['id' => $element1->identifier_id]);
        $this->assertEquals('Test identifier 2', $identifier1->identifier);
        $this->assertEquals('{"bbb":"bbb"}', $element1->data);
        $this->assertEquals(1, $element1->is_required);

        $element2 = $DB->get_record('perform_element', ['id' => $section_elements[2]->element_id]);
        $this->assertEquals('Test title 1', $element2->title);
        $identifier2 = $DB->get_record('perform_element_identifier', ['id' => $element2->identifier_id]);
        $this->assertEquals('Test identifier 1', $identifier2->identifier);
        $this->assertEquals('{"aaa":"aaa"}', $element2->data);
        $this->assertEquals(1, $element2->is_required);

        $this->assertEquals($section->id, $section_elements[1]->section_id);
        $this->assertEquals($element1->id, $section_elements[1]->element_id);
        $this->assertEquals(1, $section_elements[1]->sort_order);

        $this->assertEquals($section->id, $section_elements[2]->section_id);
        $this->assertEquals($element2->id, $section_elements[2]->element_id);
        $this->assertEquals(2, $section_elements[2]->sort_order);

        // Check that the result is correct (good enough).
        /** @var section $result_section */
        $result_section = $result['section'];
        $this->assertEquals($section->id, $result_section->id);
        $this->assertCount(2, $result_section->get_section_elements());
    }

    public function test_create_linked_section_elements(): void {
        global $DB;

        self::setAdminUser();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();

        $activity = $perform_generator->create_activity_in_container();
        $section = $perform_generator->create_section($activity);
        $element1 = $perform_generator->create_element([
            'title' => 'Test title 1',
            'data' => '{"aaa":"aaa"}',
            'identifier' => 'Test identifier 1',
        ]);
        $element2 = $perform_generator->create_element([
            'title' => 'Test title 2',
            'data' => '{"bbb":"bbb"}',
            'identifier' => 'Test identifier 2',
        ]);

        $args = [
            'input' => [
                'section_id' => $section->id,
                'create_link' => [
                    [
                        'element_id' => $element1->id,
                        'sort_order' => 2,
                    ],
                    [
                        'element_id' => $element2->id,
                        'sort_order' => 1,
                    ],
                ],
            ]
        ];

        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);

        $this->assertDebuggingCalled(
            [
                'This mutation has been deprecated. Use the individual section element mutations.',
                'Method has been deprecated. Use section_element_manager::remove_section_elements',
                'Method has been deprecated. Use section_element_manager::add_element_after',
                'Method has been deprecated. Use section_element_manager::add_element_after',
                'Method has been deprecated. Use section_element_manager::move_section_elements',
            ],
            DEBUG_DEVELOPER
        );

        // Check that the changes were made.
        $section_element_records = $DB->get_records('perform_section_element', ['section_id' => $section->id]);
        $this->assertCount(2, $section_element_records);

        $section_elements = [];
        foreach ($section_element_records as $section_element_record) {
            $section_elements[$section_element_record->sort_order] = $section_element_record;
        }

        $element1 = $DB->get_record('perform_element', ['id' => $section_elements[1]->element_id]);
        $this->assertEquals('Test title 2', $element1->title);
        $identifier1 = $DB->get_record('perform_element_identifier', ['id' => $element1->identifier_id]);
        $this->assertEquals('Test identifier 2', $identifier1->identifier);
        $this->assertEquals('{"bbb":"bbb"}', $element1->data);

        $element2 = $DB->get_record('perform_element', ['id' => $section_elements[2]->element_id]);
        $this->assertEquals('Test title 1', $element2->title);
        $identifier2 = $DB->get_record('perform_element_identifier', ['id' => $element2->identifier_id]);
        $this->assertEquals('Test identifier 1', $identifier2->identifier);
        $this->assertEquals('{"aaa":"aaa"}', $element2->data);

        $this->assertEquals($section->id, $section_elements[1]->section_id);
        $this->assertEquals($element1->id, $section_elements[1]->element_id);
        $this->assertEquals(1, $section_elements[1]->sort_order);

        $this->assertEquals($section->id, $section_elements[2]->section_id);
        $this->assertEquals($element2->id, $section_elements[2]->element_id);
        $this->assertEquals(2, $section_elements[2]->sort_order);

        // Check that the result is correct (good enough).
        /** @var section $result_section */
        $result_section = $result['section'];
        $this->assertEquals($section->id, $result_section->id);
        $this->assertCount(2, $result_section->get_section_elements());
    }

    public function test_update_elements(): void {
        global $DB;

        self::setAdminUser();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();

        $activity = $perform_generator->create_activity_in_container();
        $section = $perform_generator->create_section($activity);
        $element1 = $perform_generator->create_element([
            'title' => 'Test title 1',
            'data' => '{"aaa":"aaa"}',
            'identifier' => 111,
        ]);
        $element2 = $perform_generator->create_element([
            'title' => 'Test title 2',
            'data' => '{"bbb":"bbb"}',
            'identifier' => 222,
        ]);

        $args = [
            'input' => [
                'section_id' => $section->id,
                'create_link' => [
                    [
                        'element_id' => $element1->id,
                        'sort_order' => 2,
                    ],
                    [
                        'element_id' => $element2->id,
                        'sort_order' => 1,
                    ],
                ],
            ]
        ];
        // Section sort order 1 => element 2 (title 2), section sort order 2 => element 1 (title 1).

        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);

        $this->assertDebuggingCalled(
            [
                'This mutation has been deprecated. Use the individual section element mutations.',
                'Method has been deprecated. Use section_element_manager::remove_section_elements',
                'Method has been deprecated. Use section_element_manager::add_element_after',
                'Method has been deprecated. Use section_element_manager::add_element_after',
                'Method has been deprecated. Use section_element_manager::move_section_elements',
            ],
            DEBUG_DEVELOPER
        );

        $args = [
            'input' => [
                'section_id' => $section->id,
                'update' => [
                    [
                        'element_id' => $element1->id,
                        'title' => 'Test title 3',
                        'identifier' => 'Test identifier 3',
                        'data' => '{"ccc":"ccc"}',
                    ],
                    [
                        'element_id' => $element2->id,
                        'title' => 'Test title 4',
                        'identifier' => 'Test identifier 4',
                        'data' => '{"ddd":"ddd"}',
                    ],
                ],
            ]
        ];
        // Section sort order 1 => element 2 (title 4), section sort order 2 => element 1 (title 3).

        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);

        $this->assertDebuggingCalled(
            [
                'This mutation has been deprecated. Use the individual section element mutations.',
                'Method has been deprecated. Use section_element_manager::remove_section_elements',
                'Method has been deprecated. Use section_element_manager::move_section_elements',
            ],
            DEBUG_DEVELOPER
        );

        // Check that the changes were made.
        $section_element_records = $DB->get_records('perform_section_element', ['section_id' => $section->id]);
        $this->assertCount(2, $section_element_records);

        $section_elements = [];
        foreach ($section_element_records as $section_element_record) {
            $section_elements[$section_element_record->sort_order] = $section_element_record;
        }

        $element1 = $DB->get_record('perform_element', ['id' => $section_elements[1]->element_id]);
        $this->assertEquals('Test title 4', $element1->title);
        $identifier1 = $DB->get_record('perform_element_identifier', ['id' => $element1->identifier_id]);
        $this->assertEquals('Test identifier 4', $identifier1->identifier);
        $this->assertEquals('{"ddd":"ddd"}', $element1->data);

        $element2 = $DB->get_record('perform_element', ['id' => $section_elements[2]->element_id]);
        $this->assertEquals('Test title 3', $element2->title);
        $identifier2 = $DB->get_record('perform_element_identifier', ['id' => $element2->identifier_id]);
        $this->assertEquals('Test identifier 3', $identifier2->identifier);
        $this->assertEquals('{"ccc":"ccc"}', $element2->data);

        $this->assertEquals($section->id, $section_elements[1]->section_id);
        $this->assertEquals($element1->id, $section_elements[1]->element_id);
        $this->assertEquals(1, $section_elements[1]->sort_order);

        $this->assertEquals($section->id, $section_elements[2]->section_id);
        $this->assertEquals($element2->id, $section_elements[2]->element_id);
        $this->assertEquals(2, $section_elements[2]->sort_order);

        // Check that the result is correct (good enough).
        /** @var section $result_section */
        $result_section = $result['section'];
        $this->assertEquals($section->id, $result_section->id);
        $this->assertCount(2, $result_section->get_section_elements());
    }

    public function test_delete_section_elements(): void {
        global $DB;

        self::setAdminUser();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();

        $activity = $perform_generator->create_activity_in_container();
        $section = $perform_generator->create_section($activity);

        $args = [
            'input' => [
                'section_id' => $section->id,
                'create_new' => [
                    [
                        'plugin_name' => 'short_text',
                        'title' => 'Test title 1',
                        'identifier' => 'Test identifier 1',
                        'data' => '{"aaa":"aaa"}',
                        'sort_order' => 3,
                    ],
                    [
                        'plugin_name' => 'short_text',
                        'title' => 'Test title 2',
                        'identifier' => 'Test identifier 2',
                        'data' => '{"bbb":"bbb"}',
                        'sort_order' => 1,
                    ],
                    [
                        'plugin_name' => 'short_text',
                        'title' => 'Test title 3',
                        'identifier' => 'Test identifier 3',
                        'data' => '{"ccc":"ccc"}',
                        'sort_order' => 4,
                    ],
                    [
                        'plugin_name' => 'short_text',
                        'title' => 'Test title 4',
                        'identifier' => 'Test identifier 4',
                        'data' => '{"ddd":"ddd"}',
                        'sort_order' => 2,
                    ],
                ],
            ]
        ];

        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);

        $this->assertDebuggingCalled(
            [
                'This mutation has been deprecated. Use the individual section element mutations.',
                'Method has been deprecated. Use section_element_manager::remove_section_elements',
                'Method has been deprecated. Use section_element_manager::add_element_after',
                'Method has been deprecated. Use section_element_manager::add_element_after',
                'Method has been deprecated. Use section_element_manager::add_element_after',
                'Method has been deprecated. Use section_element_manager::add_element_after',
                'Method has been deprecated. Use section_element_manager::move_section_elements',
            ],
            DEBUG_DEVELOPER
        );

        $section_elements = $section->get_section_elements()->all(true);

        $args = [
            'input' => [
                'section_id' => $section->id,
                'delete' => [
                    [
                        'section_element_id' => $section_elements[3]->id,
                    ],
                    [
                        'section_element_id' => $section_elements[1]->id,
                    ],
                ],
                [
                    'section_element_id' => $section_elements[2]->id,
                    'sort_order' => 2,
                ],
                [
                    'section_element_id' => $section_elements[4]->id,
                    'sort_order' => 1,
                ],
            ]
        ];

        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);

        $this->assertDebuggingCalled(
            [
                'This mutation has been deprecated. Use the individual section element mutations.',
                'Method has been deprecated. Use section_element_manager::remove_section_elements',
                'Method has been deprecated. Use section_element_manager::move_section_elements',
            ],
            DEBUG_DEVELOPER
        );

        // Check that the changes were made.
        $section_element_records = $DB->get_records('perform_section_element', ['section_id' => $section->id]);
        $this->assertCount(2, $section_element_records);

        $section_elements = [];
        foreach ($section_element_records as $section_element_record) {
            $section_elements[$section_element_record->sort_order] = $section_element_record;
        }

        $element1 = $DB->get_record('perform_element', ['id' => $section_elements[1]->element_id]);
        $this->assertEquals('Test title 4', $element1->title);
        $identifier1 = $DB->get_record('perform_element_identifier', ['id' => $element1->identifier_id]);
        $this->assertEquals('Test identifier 4', $identifier1->identifier);
        $this->assertEquals('{"ddd":"ddd"}', $element1->data);

        $element2 = $DB->get_record('perform_element', ['id' => $section_elements[2]->element_id]);
        $this->assertEquals('Test title 3', $element2->title);
        $identifier2 = $DB->get_record('perform_element_identifier', ['id' => $element2->identifier_id]);
        $this->assertEquals('Test identifier 3', $identifier2->identifier);
        $this->assertEquals('{"ccc":"ccc"}', $element2->data);

        $this->assertEquals($section->id, $section_elements[1]->section_id);
        $this->assertEquals($element1->id, $section_elements[1]->element_id);
        $this->assertEquals(1, $section_elements[1]->sort_order);

        $this->assertEquals($section->id, $section_elements[2]->section_id);
        $this->assertEquals($element2->id, $section_elements[2]->element_id);
        $this->assertEquals(2, $section_elements[2]->sort_order);

        // Check that the result is correct (good enough).
        /** @var section $result_section */
        $result_section = $result['section'];
        $this->assertEquals($section->id, $result_section->id);
        $this->assertCount(2, $result_section->get_section_elements());
    }

    public function test_move_section_elements(): void {
        global $DB;

        self::setAdminUser();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();

        $activity = $perform_generator->create_activity_in_container();
        $section = $perform_generator->create_section($activity);

        $args = [
            'input' => [
                'section_id' => $section->id,
                'create_new' => [
                    [
                        'plugin_name' => 'short_text',
                        'title' => 'Test title 1',
                        'identifier' => 'Test identifier 1',
                        'data' => '{"aaa":"aaa"}',
                        'sort_order' => 2,
                    ],
                    [
                        'plugin_name' => 'short_text',
                        'title' => 'Test title 2',
                        'identifier' => 'Test identifier 2',
                        'data' => '{"bbb":"bbb"}',
                        'sort_order' => 1,
                    ],
                ],
            ]
        ];

        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);

        $this->assertDebuggingCalled(
            [
                'This mutation has been deprecated. Use the individual section element mutations.',
                'Method has been deprecated. Use section_element_manager::remove_section_elements',
                'Method has been deprecated. Use section_element_manager::add_element_after',
                'Method has been deprecated. Use section_element_manager::add_element_after',
                'Method has been deprecated. Use section_element_manager::move_section_elements',
            ],
            DEBUG_DEVELOPER
        );

        $section_elements = $section->get_section_elements()->all(true);

        $args = [
            'input' => [
                'section_id' => $section->id,
                'create_new' => [
                    [
                        'plugin_name' => 'short_text',
                        'title' => 'Test title 3',
                        'identifier' => 'Test identifier 3',
                        'data' => '{"ccc":"ccc"}',
                        'sort_order' => 2,
                    ],
                    [
                        'plugin_name' => 'short_text',
                        'title' => 'Test title 4',
                        'identifier' => 'Test identifier 4',
                        'data' => '{"ddd":"ddd"}',
                        'sort_order' => 4,
                    ],
                ],
                'move' => [
                    [
                        'section_element_id' => $section_elements[1]->id,
                        'sort_order' => 3,
                    ],
                    [
                        'section_element_id' => $section_elements[2]->id,
                        'sort_order' => 1,
                    ],
                ],
            ]
        ];

        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);

        $this->assertDebuggingCalled(
            [
                'This mutation has been deprecated. Use the individual section element mutations.',
                'Method has been deprecated. Use section_element_manager::remove_section_elements',
                'Method has been deprecated. Use section_element_manager::add_element_after',
                'Method has been deprecated. Use section_element_manager::add_element_after',
                'Method has been deprecated. Use section_element_manager::move_section_elements',
            ],
            DEBUG_DEVELOPER
        );

        // Check that the changes were made.
        $section_element_records = $DB->get_records('perform_section_element', ['section_id' => $section->id]);
        $this->assertCount(4, $section_element_records);

        $section_elements = [];
        foreach ($section_element_records as $section_element_record) {
            $section_elements[$section_element_record->sort_order] = $section_element_record;
        }

        $element1 = $DB->get_record('perform_element', ['id' => $section_elements[1]->element_id]);
        $this->assertEquals('Test title 1', $element1->title);
        $identifier1 = $DB->get_record('perform_element_identifier', ['id' => $element1->identifier_id]);
        $this->assertEquals('Test identifier 1', $identifier1->identifier);
        $this->assertEquals('{"aaa":"aaa"}', $element1->data);

        $element2 = $DB->get_record('perform_element', ['id' => $section_elements[2]->element_id]);
        $this->assertEquals('Test title 3', $element2->title);
        $identifier2 = $DB->get_record('perform_element_identifier', ['id' => $element2->identifier_id]);
        $this->assertEquals('Test identifier 3', $identifier2->identifier);
        $this->assertEquals('{"ccc":"ccc"}', $element2->data);

        $element3 = $DB->get_record('perform_element', ['id' => $section_elements[3]->element_id]);
        $this->assertEquals('Test title 2', $element3->title);
        $identifier3 = $DB->get_record('perform_element_identifier', ['id' => $element3->identifier_id]);
        $this->assertEquals('Test identifier 2', $identifier3->identifier);
        $this->assertEquals('{"bbb":"bbb"}', $element3->data);

        $element4 = $DB->get_record('perform_element', ['id' => $section_elements[4]->element_id]);
        $this->assertEquals('Test title 4', $element4->title);
        $identifier4 = $DB->get_record('perform_element_identifier', ['id' => $element4->identifier_id]);
        $this->assertEquals('Test identifier 4', $identifier4->identifier);
        $this->assertEquals('{"ddd":"ddd"}', $element4->data);

        $this->assertEquals($section->id, $section_elements[1]->section_id);
        $this->assertEquals($element1->id, $section_elements[1]->element_id);
        $this->assertEquals(1, $section_elements[1]->sort_order);

        $this->assertEquals($section->id, $section_elements[2]->section_id);
        $this->assertEquals($element2->id, $section_elements[2]->element_id);
        $this->assertEquals(2, $section_elements[2]->sort_order);

        $this->assertEquals($section->id, $section_elements[3]->section_id);
        $this->assertEquals($element3->id, $section_elements[3]->element_id);
        $this->assertEquals(3, $section_elements[3]->sort_order);

        $this->assertEquals($section->id, $section_elements[4]->section_id);
        $this->assertEquals($element4->id, $section_elements[4]->element_id);
        $this->assertEquals(4, $section_elements[4]->sort_order);

        // Check that the result is correct (good enough).
        /** @var section $result_section */
        $result_section = $result['section'];
        $this->assertEquals($section->id, $result_section->id);
        $this->assertCount(4, $result_section->get_section_elements());
    }

    /**
     * Test the mutation through the GraphQL stack.
     */
    public function test_combination(): void {
        global $DB;

        self::setAdminUser();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = self::getDataGenerator()->get_plugin_generator('mod_perform');

        $activity = $perform_generator->create_activity_in_container();
        $section = $perform_generator->create_section($activity);

        $args = [
            'input' => [
                'section_id' => $section->id,
                'create_new' => [
                    [
                        'plugin_name' => 'short_text',
                        'title' => 'Test title 1',
                        'identifier' => 'Test identifier 1',
                        'data' => '{"aaa":"aaa"}',
                        'is_required' => true,
                        'sort_order' => 1,
                    ],
                    [
                        'plugin_name' => 'short_text',
                        'title' => 'Test title 2',
                        'identifier' => 'Test identifier 2',
                        'data' => '{"bbb":"bbb"}',
                        'is_required' => true,
                        'sort_order' => 2,
                    ],
                    [
                        'plugin_name' => 'short_text',
                        'title' => 'Test title 3',
                        'identifier' => 'Test identifier 3',
                        'data' => '{"ccc":"ccc"}',
                        'is_required' => true,
                        'sort_order' => 3,
                    ],
                ],
            ]
        ];

        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $this->assertDebuggingCalled(
            [
                'This mutation has been deprecated. Use the individual section element mutations.',
                'Method has been deprecated. Use section_element_manager::remove_section_elements',
                'Method has been deprecated. Use section_element_manager::add_element_after',
                'Method has been deprecated. Use section_element_manager::add_element_after',
                'Method has been deprecated. Use section_element_manager::add_element_after',
                'Method has been deprecated. Use section_element_manager::move_section_elements',
            ],
            DEBUG_DEVELOPER
        );

        $section_elements = $section->get_section_elements()->all(true);

        $element4 = $perform_generator->create_element([
            'title' => 'Test title 5',
            'data' => '{"eee":"eee"}',
            'is_required' => true,
            'identifier' => 555,
        ]);

        $args = [
            'input' => [
                'section_id' => $section->id,
                'create_new' => [
                    [
                        'plugin_name' => 'short_text',
                        'title' => 'Test title 4',
                        'identifier' => 'Test ID',
                        'data' => '{"ddd":"ddd"}',
                        'is_required' => true,
                        'sort_order' => 1,
                    ],
                ],
                'create_link' => [
                    [
                        'element_id' => $element4->id,
                        'sort_order' => 2,
                    ],
                ],
                'update' => [
                    [
                        'element_id' => $element4->id,
                        'title' => 'Test title 6',
                        'identifier' => 'Test ID2',
                        'is_required' => true,
                        'data' => '{"fff":"fff"}',
                    ],
                ],
                'delete' => [
                    [
                        'section_element_id' => $section_elements[1]->id,
                    ],
                ],
                'move' => [
                    [
                        'section_element_id' => $section_elements[2]->id,
                        'sort_order' => 4,
                    ],
                    [
                        'section_element_id' => $section_elements[3]->id,
                        'sort_order' => 3,
                    ],
                ],
            ]
        ];

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assertDebuggingCalled(
            [
                'This mutation has been deprecated. Use the individual section element mutations.',
                'Method has been deprecated. Use section_element_manager::remove_section_elements',
                'Method has been deprecated. Use section_element_manager::add_element_after',
                'Method has been deprecated. Use section_element_manager::add_element_after',
                'Method has been deprecated. Use section_element_manager::move_section_elements',
            ],
            DEBUG_DEVELOPER
        );
        $this->assert_webapi_operation_successful($result);

        $result_data = $this->get_webapi_operation_data($result);
        $this->assertNotEmpty($result_data, "no result");

        // Check that the changes were made.
        $section_element_records = $DB->get_records('perform_section_element', ['section_id' => $section->id]);
        $this->assertCount(4, $section_element_records);

        $section_elements = [];
        foreach ($section_element_records as $section_element_record) {
            $section_elements[$section_element_record->sort_order] = $section_element_record;
        }

        $element1 = $DB->get_record('perform_element', ['id' => $section_elements[1]->element_id]);
        $this->assertEquals('Test title 4', $element1->title);
        $identifier1 = $DB->get_record('perform_element_identifier', ['id' => $element1->identifier_id]);
        $this->assertEquals('Test ID', $identifier1->identifier);
        $this->assertEquals('{"ddd":"ddd"}', $element1->data);

        $element2 = $DB->get_record('perform_element', ['id' => $section_elements[2]->element_id]);
        $this->assertEquals('Test title 6', $element2->title);
        $identifier2 = $DB->get_record('perform_element_identifier', ['id' => $element2->identifier_id]);
        $this->assertEquals('Test ID2', $identifier2->identifier);
        $this->assertEquals('{"fff":"fff"}', $element2->data);

        $element3 = $DB->get_record('perform_element', ['id' => $section_elements[3]->element_id]);
        $this->assertEquals('Test title 3', $element3->title);
        $this->assertEquals('{"ccc":"ccc"}', $element3->data);

        $element4 = $DB->get_record('perform_element', ['id' => $section_elements[4]->element_id]);
        $this->assertEquals('Test title 2', $element4->title);
        $this->assertEquals('{"bbb":"bbb"}', $element4->data);

        $this->assertEquals($section->id, $section_elements[1]->section_id);
        $this->assertEquals($element1->id, $section_elements[1]->element_id);
        $this->assertEquals(1, $section_elements[1]->sort_order);

        $this->assertEquals($section->id, $section_elements[2]->section_id);
        $this->assertEquals($element2->id, $section_elements[2]->element_id);
        $this->assertEquals(2, $section_elements[2]->sort_order);

        $this->assertEquals($section->id, $section_elements[3]->section_id);
        $this->assertEquals($element3->id, $section_elements[3]->element_id);
        $this->assertEquals(3, $section_elements[3]->sort_order);

        $this->assertEquals($section->id, $section_elements[4]->section_id);
        $this->assertEquals($element4->id, $section_elements[4]->element_id);
        $this->assertEquals(4, $section_elements[4]->sort_order);

        // Check that the result is correct (good enough).
        $this->assertEquals($section->id, $result_data['section']['id']);
        $this->assertCount(4, $result_data['section']['section_elements']);
    }

    public function validation_create_new_data_provider() {
        return [
            ['multi_choice_single', 'Test-ID', 'multi_choice_single', 'Test-ID', true],
            ['multi_choice_single', '', 'short_text', '', true],
        ];
    }

    /**
     * @dataProvider validation_create_new_data_provider
     * @param string $plugin1
     * @param string $id1
     * @param string $plugin2
     * @param string $id2
     * @param bool $passes_validation
     */
    public function test_validation_create_new(string $plugin1, string $id1, string $plugin2, string $id2, bool $passes_validation) {
        self::setAdminUser();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = self::getDataGenerator()->get_plugin_generator('mod_perform');

        $activity = $perform_generator->create_activity_in_container();
        $section = $perform_generator->create_section($activity);
        $perform_generator->create_element([
            'plugin_name' => $plugin1,
            'title' => 'Test title',
            'identifier' => $id1,
            'data' => '{"aaa":"aaa"}',
        ]);

        $args = [
            'input' => [
                'section_id' => $section->id,
                'create_new' => [
                    [
                        'plugin_name' => $plugin2,
                        'title' => 'Test title',
                        'identifier' => $id2,
                        'data' => '{"aaa":"aaa"}',
                        'is_required' => true,
                        'sort_order' => 1,
                    ],
                ],
            ]
        ];

        if (!$passes_validation) {
            $this->expectException(coding_exception::class);
            $this->expectExceptionMessage('Cannot save identifier');
        }
        $context = $this->create_webapi_context(self::MUTATION);
        $result = update_section_elements::resolve($args, $context);

        $this->assertDebuggingCalled(
            [
                'This mutation has been deprecated. Use the individual section element mutations.',
                'Method has been deprecated. Use section_element_manager::remove_section_elements',
                'Method has been deprecated. Use section_element_manager::add_element_after',
                'Method has been deprecated. Use section_element_manager::move_section_elements',
            ],
            DEBUG_DEVELOPER
        );
        if ($passes_validation) {
            /** @var section $result_section */
            $result_section = $result['section'];
            $this->assertEquals($section->id, $result_section->id);
        }
    }

    public function test_validation_update() {
        self::setAdminUser();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = self::getDataGenerator()->get_plugin_generator('mod_perform');

        $activity = $perform_generator->create_activity_in_container();
        $section = $perform_generator->create_section($activity);
        $perform_generator->create_element([
            'plugin_name' => 'multi_choice_single',
            'title' => 'Test title',
            'identifier' => 'Test-ID multi_choice',
            'data' => '{"aaa":"aaa"}',
        ]);
        $element_to_update = $perform_generator->create_element([
            'plugin_name' => 'short_text',
            'title' => 'Test title',
            'identifier' => 'Test-ID short_text',
            'data' => '{"aaa":"aaa"}',
        ]);

        $args = [
            'input' => [
                'section_id' => $section->id,
                'create_link' => [
                    [
                        'element_id' => $element_to_update->id,
                        'sort_order' => 1,
                    ],
                ],
            ]
        ];
        $context = $this->create_webapi_context(self::MUTATION);
        update_section_elements::resolve($args, $context);
        $this->assertDebuggingCalled(
            [
                'This mutation has been deprecated. Use the individual section element mutations.',
                'Method has been deprecated. Use section_element_manager::remove_section_elements',
                'Method has been deprecated. Use section_element_manager::add_element_after',
                'Method has been deprecated. Use section_element_manager::move_section_elements',
            ],
            DEBUG_DEVELOPER
        );
    }
}
