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
 * @package mod_approval
 */

use core\orm\query\builder;
use core_phpunit\testcase;
use mod_approval\exception\access_denied_exception;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\status;
use mod_approval\testing\approval_workflow_test_setup;
use mod_approval\testing\assignment_generator_object;
use mod_approval\testing\generator as approval_generator;
use mod_approval\testing\workflow_generator_object;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass mod_approval\webapi\resolver\query\manageable_workflows
 * @group approval_workflow
 */
class mod_approval_webapi_query_manageable_workflows_testcase extends testcase {

    use approval_workflow_test_setup;
    use webapi_phpunit_helper;

    private const QUERY = 'mod_approval_manageable_workflows';

    public function test_query_requires_logged_in_user() {
        $this->setGuestUser();
        $this->generate_data();
        $this->expectException('require_login_exception');
        $this->resolve_graphql_query(self::QUERY, ['query_options' => []]);
    }

    public function test_query_as_user() {
        $this->setAdminUser();
        $this->generate_data();

        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        try {
            $this->resolve_graphql_query(self::QUERY, ['query_options' => []]);
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Access denied to manage the workflows', $ex->getMessage());
        }
    }

    public function test_query_without_input_params() {
        $this->setAdminUser();
        $this->generate_data();

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
        $this->assertEquals(40, $result['total']);
        $this->assertCount(20, $result['items']);

        // Filter by Name.
        $args = [
            'query_options' => [
                'filters' => [
                    'name' => "calm work",
                ]
            ]
        ];
        $parsed_query = $this->parsed_graphql_operation(self::QUERY, $args);
        $result = reset($parsed_query);
        $this->assertEquals(10, $result['total']);
        $this->assertCount(10, $result['items']);

        foreach ($result['items'] as $item) {
            $this->assertStringContainsString('Calm', $item['name']);
        }
    }

    /**
     * Tests filtering by draft.
     *
     * @return void
     */
    private function test_filter_by_draft(): void {
        $args = [
            'query_options' => [
                'filters' => [
                    'status' => "DRAFT",
                ]
            ]
        ];
        $parsed_query = $this->parsed_graphql_operation(self::QUERY, $args);
        $result = reset($parsed_query);
        $this->assertEquals(10, $result['total']);
        $this->assertCount(10, $result['items']);

        foreach ($result['items'] as $item) {
            $this->assertEquals('Draft', $item['latest_version']['status_label']);
            $this->assertEquals('Group G members', $item['default_assignment']['assigned_to']['fullname']);
            $this->assertEquals('Audience', $item['default_assignment']['assignment_type_label']);
        }
    }

    /**
     * Tests filtering by active.
     *
     * @return void
     */
    private function test_filter_by_active(): void {
        $args = [
            'query_options' => [
                'filters' => [
                    'status' => "ACTIVE",
                ]
            ]
        ];
        $parsed_query = $this->parsed_graphql_operation(self::QUERY, $args);
        $result = reset($parsed_query);
        $this->assertEquals(20, $result['total']);
        $this->assertCount(20, $result['items']);

        foreach ($result['items'] as $item) {
            $this->assertEquals('Active', $item['latest_version']['status_label']);
            $this->assertEquals('Agency', $item['default_assignment']['assigned_to']['fullname']);
            $this->assertEquals('Organisation', $item['default_assignment']['assignment_type_label']);
        }
    }

    /**
     * Tests filtering by archived.
     *
     * @return void
     */
    private function test_filter_by_archived(): void {
        $args = [
            'query_options' => [
                'filters' => [
                    'status' => "ARCHIVED",
                ]
            ]
        ];
        $parsed_query = $this->parsed_graphql_operation(self::QUERY, $args);
        $result = reset($parsed_query);
        $this->assertEquals(10, $result['total']);
        $this->assertCount(10, $result['items']);

        foreach ($result['items'] as $item) {
            $this->assertEquals('Archived', $item['latest_version']['status_label']);
            $this->assertEquals('Division', $item['default_assignment']['assigned_to']['fullname']);
            $this->assertEquals('Position', $item['default_assignment']['assignment_type_label']);
        }
    }

    /**
     * Tests filtering by assignment type.
     *
     * @return void
     */
    private function test_filter_by_assignment_type(): void {
        $args = [
            'query_options' => [
                'filters' => [
                    'assignment_type' => "COHORT",
                    'status' => "ACTIVE",
                ]
            ]
        ];
        $parsed_query = $this->parsed_graphql_operation(self::QUERY, $args);
        $result = reset($parsed_query);
        $this->assertEquals(0, $result['total']);
        $this->assertCount(0, $result['items']);

        // Filter by position.
        $args = [
            'query_options' => [
                'filters' => [
                    'assignment_type' => "POSITION",
                ]
            ]
        ];
        $parsed_query = $this->parsed_graphql_operation(self::QUERY, $args);
        $result_2 = reset($parsed_query);
        $this->assertEquals(10, $result_2['total']);
        $this->assertCount(10, $result_2['items']);

        foreach ($result_2['items'] as $item) {
            $this->assertEquals('Archived', $item['latest_version']['status_label']);
            $this->assertEquals('Division', $item['default_assignment']['assigned_to']['fullname']);
            $this->assertEquals('Position', $item['default_assignment']['assignment_type_label']);
        }
    }

    /**
     * Tests filtering by workflow type id.
     *
     * @return void
     */
    private function test_filter_by_workflow_type_id(): void {
        $workflow_type = workflow_type::repository()->one(true);
        $args = [
            'query_options' => [
                'filters' => [
                    'workflow_type_id' => - 1,
                ]
            ]
        ];
        $parsed_query = $this->parsed_graphql_operation(self::QUERY, $args);
        $result = reset($parsed_query);
        $this->assertEquals(0, $result['total']);
        $this->assertCount(0, $result['items']);

        // Filter by position.
        $args = [
            'query_options' => [
                'filters' => [
                    'workflow_type_id' => $workflow_type->id,
                    'status' => "ACTIVE",
                ]
            ]
        ];
        $parsed_query = $this->parsed_graphql_operation(self::QUERY, $args);
        $result = reset($parsed_query);
        $this->assertEquals(20, $result['total']);
        $this->assertCount(20, $result['items']);

        foreach ($result['items'] as $item) {
            $this->assertEquals('Active', $item['latest_version']['status_label']);
            $this->assertEquals('Agency', $item['default_assignment']['assigned_to']['fullname']);
            $this->assertEquals('Organisation', $item['default_assignment']['assignment_type_label']);
        }
    }

    /**
     * Tests filtering by archived and sort by name.
     *
     * @return void
     */
    private function test_filter_by_archived_and_sort_by_name(): void {
        $args = [
            'query_options' => [
                'filters' => [
                    'status' => "ARCHIVED",
                ],
                'sort_by' => 'NAME'
            ]
        ];
        $parsed_query = $this->parsed_graphql_operation(self::QUERY, $args);
        $result = reset($parsed_query);
        $this->assertEquals(10, $result['total']);
        $this->assertCount(10, $result['items']);
        $i = 0;

        foreach ($result['items'] as $item) {
            $this->assertEquals('Archived', $item['latest_version']['status_label']);
            $this->assertEquals("Closed-off Workflow $i", $item['name']);
            $i++;
        }
    }

    /**
     * @param $two_weeks_ago
     *
     * @throws coding_exception
     */
    private function back_date_archived_workflows_to($two_weeks_ago): void {
        $workflow_with_archived_versions = workflow_version::repository()
            ->where('status', status::ARCHIVED)
            ->select('workflow_id')
            ->get()->pluck('workflow_id');
        workflow::repository()->where('id', $workflow_with_archived_versions)->update(['updated' => $two_weeks_ago]);
    }

    /**
     * @param $a_week_ago
     *
     * @throws coding_exception
     */
    private function back_date_draft_workflows_to($a_week_ago): void {
        $workflow_with_draft_versions = workflow_version::repository()
            ->where('status', status::DRAFT)
            ->select('workflow_id')
            ->get()->pluck('workflow_id');
        workflow::repository()->where('id', $workflow_with_draft_versions)->update(['updated' => $a_week_ago]);
    }

    /**
     * Test for sorting by updated.
     *
     * @return void
     */
    private function test_sort_by_updated(): void {
        // Back-date workflows with draft & archived versions.
        $today = time();
        $a_week_ago = $today - (60 * 60 * 24 * 7);
        $two_weeks_ago = $today - (60 * 60 * 24 * 14);
        $this->back_date_draft_workflows_to($a_week_ago);
        $this->back_date_archived_workflows_to($two_weeks_ago);

        // Sort by last modified.
        $args = [
            'query_options' => [
                'pagination' => [
                    'limit' => 30,
                ],
                'sort_by' => 'UPDATED'
            ]
        ];
        $parsed_query = $this->parsed_graphql_operation(self::QUERY, $args);
        $result = reset($parsed_query);
        $this->assertEquals(40, $result['total']);
        $this->assertCount(30, $result['items']);
        $date_formatter = new date_field_formatter(date_format::FORMAT_DATELONG, context_system::instance());
        $active_workflow_count = 20;

        foreach ($result['items'] as $key => $item) {
            $key < $active_workflow_count
                ? $this->assertEquals($date_formatter->format($today), $item['updated'])
                : $this->assertEquals($date_formatter->format($a_week_ago), $item['updated']);
        }

        // Second page has only archived workflows.
        $args = [
            'query_options' => [
                'pagination' => [
                    'limit' => 30,
                    'page' => 2,
                ],
                'sort_by' => 'UPDATED'
            ]
        ];
        $parsed_query = $this->parsed_graphql_operation(self::QUERY, $args);
        $result = reset($parsed_query);
        $this->assertEquals(40, $result['total']);
        $this->assertCount(10, $result['items']);

        foreach ($result['items'] as $item) {
            $this->assertEquals($date_formatter->format($two_weeks_ago), $item['updated']);
        }
    }

    /**
     * Test sorting by workflow name.
     *
     * @return void
     */
    private function test_sort_by_workflow_name(): void {
        $args = [
            'query_options' => [
                'pagination' => [
                    'limit' => 10,
                ],
                'sort_by' => 'NAME'
            ]
        ];
        $parsed_query = $this->parsed_graphql_operation(self::QUERY, $args);
        $result = reset($parsed_query);
        $this->assertEquals(40, $result['total']);
        $this->assertCount(10, $result['items']);
        $i = 0;

        foreach ($result['items'] as $item) {
            $this->assertEquals("Calm Workflow $i", $item['name']);
            $i++;
            $this->assertStringContainsString('Draft', $item['latest_version']['status_label']);
        }
    }

    /**
     * Test sorting by id_number.
     */
    private function test_sort_by_id_number(): void {
        $args = [
            'query_options' => [
                'pagination' => [
                    'limit' => 10,
                ],
                'sort_by' => 'ID_NUMBER'
            ]
        ];
        $parsed_query = $this->parsed_graphql_operation(self::QUERY, $args);
        $result = reset($parsed_query);
        $this->assertEquals(40, $result['total']);
        $this->assertCount(10, $result['items']);
        $i = 0;

        foreach ($result['items'] as $item) {
            $this->assertEquals("ARCHIVED-FLOW-$i", $item['id_number']);
            $i++;
        }
    }

    /**
     * Test sorting by status.
     *
     * @return void
     */
    private function test_sort_by_status(): array {
        $args = [
            'query_options' => [
                'pagination' => [
                    'limit' => 30,
                ],
                'sort_by' => 'STATUS'
            ]
        ];
        $parsed_query = $this->parsed_graphql_operation(self::QUERY, $args);
        $result = reset($parsed_query);
        $this->assertEquals(40, $result['total']);
        $this->assertCount(30, $result['items']);

        $draft_workflow_count = 10;
        $i = 0;

        foreach ($result['items'] as $item) {
            // Test drafts come first.
            $i < $draft_workflow_count
                ? $this->assertEquals('Draft', $item['latest_version']['status_label'])
                : $this->assertEquals('Active', $item['latest_version']['status_label']);

            $i++;
        }

        // Next page would have only archived workflows.
        $args = [
            'query_options' => [
                'pagination' => [
                    'limit' => 30,
                    'page' => 2,
                ],
                'sort_by' => 'STATUS'
            ]
        ];
        $parsed_query = $this->parsed_graphql_operation(self::QUERY, $args);
        $result = reset($parsed_query);
        $this->assertEquals(40, $result['total']);
        $this->assertCount(10, $result['items']);

        foreach ($result['items'] as $item) {
            $this->assertEquals('Archived', $item['latest_version']['status_label']);
        }
        return $parsed_query;
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
        $this->assertEquals(40, $result['total']);
        $this->assertCount(20, $result['items']);

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
        $this->assertEquals(40, $result['total']);
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
        $this->assertEquals(40, $result['total']);
        $this->assertCount(20, $result['items']);

        // Specify page number and limit.
        $args = [
            'query_options' => [
                'pagination' => [
                    'limit' => 10,
                    'page' => 2,
                ]
            ]
        ];
        $parsed_query = $this->parsed_graphql_operation(self::QUERY, $args);
        $result = reset($parsed_query);
        $this->assertEquals(40, $result['total']);
        $this->assertCount(10, $result['items']);

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
        $this->assertEquals(40, $result['total']);
        $this->assertCount(40, $result['items']);

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
        $this->assertEquals(40, $result['total']);
        $this->assertCount(0, $result['items']);
    }

    private function generate_data() {
        $generator = approval_generator::instance();
        $workflow_type = $generator->create_workflow_type('Testing');

        // Create a form and version.
        $form_version = $generator->create_form_and_version('simple', 'Simple Request Form');
        $form = $form_version->form;

        // Create organization hierarchy.
        $framework = $this->generate_org_hierarchy();
        $number_of_active_workflows = 20;

        for ($i = 1; $i <= $number_of_active_workflows; $i++) {
            // Create a workflow and version.
            $workflow_go = new workflow_generator_object($workflow_type->id, $form->id, $form_version->id);
            $workflow_go->name = "Crafty Workflow $i";
            $workflow_go->id_number = "WATER-FLOW-$i" . \core\uuid::generate();
            $workflow_version_entity = $generator->create_workflow_and_version($workflow_go);
            $assignment_go = new assignment_generator_object(
                $workflow_version_entity->workflow->course_id,
                assignment_type\organisation::get_code(),
                $framework->agency->id
            );
            $assignment_go->is_default = true;
            $generator->create_assignment($assignment_go);
        }

        // Draft workflows.
        $number_of_draft_workflows = 9;
        $cohort = $this->getDataGenerator()->create_cohort(['name' => 'Group G members']);

        for ($i = 0; $i <= $number_of_draft_workflows; $i++) {
            // Create a workflow and version.
            $workflow_go = new workflow_generator_object($workflow_type->id, $form->id, $form_version->id);
            $workflow_go->name = "Calm Workflow $i";
            $workflow_go->id_number = "DRAFT-FLOW-$i" . \core\uuid::generate();
            $workflow_version_entity = $generator->create_workflow_and_version($workflow_go);
            $workflow_version_entity->status = status::DRAFT;
            $workflow_version_entity->update();
            $assignment_go = new assignment_generator_object(
                $workflow_version_entity->workflow->course_id,
                assignment_type\cohort::get_code(),
                $cohort->id
            );
            $assignment_go->is_default = true;
            $generator->create_assignment($assignment_go);
        }

        // Archived workflows.
        $number_of_archived_workflows = 9;
        $position_framework = $this->generate_pos_hierarchy();

        for ($i = 0; $i <= $number_of_archived_workflows; $i++) {
            // Create a workflow and version.
            $workflow_go = new workflow_generator_object($workflow_type->id, $form->id, $form_version->id);
            $workflow_go->name = "Closed-off Workflow $i";
            $workflow_go->id_number = "ARCHIVED-FLOW-$i" .  \core\uuid::generate();
            $workflow_version_entity = $generator->create_workflow_and_version($workflow_go);
            $workflow_version_entity->status = status::ARCHIVED;
            $workflow_version_entity->update();
            $assignment_go = new assignment_generator_object(
                $workflow_version_entity->workflow->course_id,
                assignment_type\position::get_code(),
                $position_framework->division->id
            );
            $assignment_go->is_default = true;
            $generator->create_assignment($assignment_go);
        }
    }

    public function test_view_workflows_by_tenant_domain_manager(): void {
        // Login as site manager to create workflows
        self::setAdminUser();
        $this->generate_data();

        $generator = self::getDataGenerator();
        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->enable_tenants();
        // Create tenants.
        $tenant_one = $tenant_generator->create_tenant();
        $user = $generator->create_user(['tenantid' => $tenant_one->id]);
        $tenantdomainmanager = builder::get_db()->get_record('role', ['archetype' => 'tenantdomainmanager']);

        $context_tenant_category = context_coursecat::instance($tenant_one->categoryid);
        role_assign($tenantdomainmanager->id, $user->id, $context_tenant_category->id);

        // Login as tenant manager to see workflows
        self::setUser($user);
        $this->generate_data();

        $parsed_query = $this->parsed_graphql_operation(self::QUERY,
            [
                'query_options' => [
                    'pagination' => [
                        'limit' => 100
                    ],
                    'filters' => [
                        'status' => "DRAFT",
                    ]
                ],

            ]
        );

        // 20 draft workflows created by generate_data().
        $result = reset($parsed_query);
        self::assertCount(20, $result['items']);

        // Enable tenant isolation.
        set_config('tenantsisolated', 1);

        $parsed_query = $this->parsed_graphql_operation(self::QUERY,
            [
                'query_options' => [
                    'pagination' => [
                        'limit' => 100
                    ],
                    'filters' => [
                        'status' => "ARCHIVED",
                    ]
                ],

            ]
        );

        // 10 archived workflows created by tenant domain manager.
        $result = reset($parsed_query);
        self::assertCount(10, $result['items']);

        foreach ($result['items'] as $item) {
           self::assertEquals('Archived', $item['latest_version']['status_label']);
        }
    }
}