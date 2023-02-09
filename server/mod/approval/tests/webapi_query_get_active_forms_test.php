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
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 * @package @mod_approval
 */

use core_phpunit\testcase;
use mod_approval\exception\access_denied_exception;
use mod_approval\model\form\form_version;
use mod_approval\model\form\form;
use mod_approval\model\status;
use mod_approval\testing\approval_workflow_test_setup;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass mod_approval\webapi\resolver\query\get_active_forms
 * @group approval_workflow
 */
class mod_approval_webapi_query_get_active_forms_testcase extends testcase {

    use approval_workflow_test_setup;
    use webapi_phpunit_helper;

    private const QUERY = 'mod_approval_get_active_forms';

    public function test_query_requires_logged_in_user() {
        $this->create_workflow_and_assignment();
        form::create('simple', 'test form');

        $this->setGuestUser();
        $this->expectException('require_login_exception');
        $this->resolve_graphql_query(self::QUERY, ['query_options' => []]);
    }

    public function test_query_as_user() {
        $this->create_workflow_and_assignment();
        form::create('simple', 'test form');

        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        try {
            $this->resolve_graphql_query(self::QUERY, ['query_options' => []]);
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Cannot access this workflow', $ex->getMessage());
        }
    }

    public function test_query_without_input_params() {
        $this->create_workflow_and_assignment();
        form::create('simple', 'test form');

        $parsed_query = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assert_webapi_operation_failed($parsed_query);
    }

    public function test_filtering() {
        $this->setAdminUser();
        $this->generate_data();
        $args = [
            'query_options' => []
        ];

        // Test without filters.
        $parsed_query = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($parsed_query);
        $result = reset($parsed_query);

        $this->assertEquals(12, $result['total']);
        $this->assertCount(10, $result['items']);

        // Filter by Name.
        $args = [
            'query_options' => [
                'filters' => [
                    'title' => "protest form",
                ]
            ]
        ];
        $parsed_query = $this->parsed_graphql_operation(self::QUERY, $args);
        $result = reset($parsed_query);
        $this->assertEquals(6, $result['total']);
        $this->assertCount(6, $result['items']);

        foreach ($result['items'] as $item) {
            $this->assertStringContainsString('form', $item['title']);
        }
    }

    /**
     * Tests return just active
     *
     */
    public function test_nonactive() {
        $this->setAdminUser();
        $active_form = form::create('simple', 'Active test form');

        $draft_form = form::create('simple', 'Draft test form');
        $draft_form->get_active_version()->archive();
        $json_schema = file_get_contents(__DIR__ . "/fixtures/form/test_form.json");
        $draft_form_version = form_version::create($draft_form, '20211118', $json_schema, status::DRAFT);

        $args = [
            'query_options' => []
        ];

        $parsed_query = $this->parsed_graphql_operation(self::QUERY, $args);
        $result = reset($parsed_query);

        $this->assertEquals(1, $result['total']);
        $this->assertCount(1, $result['items']);

        foreach ($result['items'] as $item) {
            $this->assertEquals('Active test form', $item['title']);
        }
    }

    /**
     * Test sorting by form title.
     *
     */
    public function test_sort_by_form_title() {
        $this->setAdminUser();
        $this->generate_data();

        $args = [
            'query_options' => [
                'pagination' => [
                    'limit' => 5,
                ],
                'sort_by' => 'title'
            ]
        ];
        $parsed_query = $this->parsed_graphql_operation(self::QUERY, $args);
        $result = reset($parsed_query);
        $this->assertEquals(12, $result['total']);
        $this->assertCount(5, $result['items']);

        $this->assertEquals("A protest form", $result['items'][0]['title']);
    }

    public function test_pagination() {
        $this->setAdminUser();
        $this->generate_data();
        $args = [
            'query_options' => []
        ];

        // Test without pagination parameters, default parameters apply.
        $parsed_query = $this->parsed_graphql_operation(self::QUERY, $args);
        $result = reset($parsed_query);
        $this->assertEquals(12, $result['total']);
        $this->assertCount(10, $result['items']);

        // Specify only limit of items.
        $args = [
            'query_options' => [
                'pagination' => [
                    'limit' => 5
                ]
            ]
        ];
        $parsed_query = $this->parsed_graphql_operation(self::QUERY, $args);
        $result = reset($parsed_query);
        $this->assertEquals(12, $result['total']);
        $this->assertCount(5, $result['items']);

        // Specify only page number.
        $args = [
            'query_options' => [
                'pagination' => [
                    'page' => 2,
                ]
            ]
        ];
        $parsed_query = $this->parsed_graphql_operation(self::QUERY, $args);
        $result = reset($parsed_query);
        $this->assertEquals(12, $result['total']);
        $this->assertCount(2, $result['items']);

        // Specify page number and limit.
        $args = [
            'query_options' => [
                'pagination' => [
                    'limit' => 1,
                    'page' => 2,
                ]
            ]
        ];
        $parsed_query = $this->parsed_graphql_operation(self::QUERY, $args);
        $result = reset($parsed_query);
        $this->assertEquals(12, $result['total']);
        $this->assertCount(1, $result['items']);

        // Specify limit exceeding number of items.
        $args = [
            'query_options' => [
                'pagination' => [
                    'limit' => 500
                ]
            ]
        ];
        $parsed_query = $this->parsed_graphql_operation(self::QUERY, $args);
        $result = reset($parsed_query);
        $this->assertEquals(12, $result['total']);
        $this->assertCount(12, $result['items']);

        // Specify page that doesn't exist.
        $args = [
            'query_options' => [
                'pagination' => [
                    'limit' => 10,
                    'page' => 200,
                ]
            ]
        ];
        $parsed_query = $this->parsed_graphql_operation(self::QUERY, $args);
        $result = reset($parsed_query);
        $this->assertEquals(12, $result['total']);
        $this->assertCount(0, $result['items']);
    }

    private function generate_data() {

        form::create('simple', 'A test form');
        form::create('simple', 'B test form');
        form::create('simple', 'C test form');
        form::create('simple', 'D test form');
        form::create('simple', 'E test form');
        form::create('simple', 'F test form');

        form::create('simple', 'A protest form');
        form::create('simple', 'B protest form');
        form::create('simple', 'C protest form');
        form::create('simple', 'D protest form');
        form::create('simple', 'E protest form');
        form::create('simple', 'F protest form');
    }
}