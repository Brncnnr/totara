<?php
/**
 * This file is part of Totara Talent Experience Platform
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Cody Finegan <cody.finegan@totara.com>
 * @package totara_useraction
 */

use core_phpunit\testcase;
use GraphQL\Executor\ExecutionResult;
use totara_useraction\filter\duration;
use totara_useraction\filter\status;
use totara_useraction\model\scheduled_rule as model;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Test the GraphQL queries for scheduled rules.
 *
 * @group totara_useraction
 */
class totara_useraction_scheduled_rule_queries_testcase extends testcase {

    use webapi_phpunit_helper;

    const GRAPHQL_SINGLE = 'totara_useraction_scheduled_rule';
    const GRAPHQL_MULTIPLE = 'totara_useraction_scheduled_rules';
    const GRAPHQL_PERSISTENT_SINGLE = 'totara_useraction_scheduled_rule_for_editing';
    const GRAPHQL_PERSISTENT_MULTIPLE = 'totara_useraction_scheduled_rules';

    /**
     * Data provider for the queries test.
     *
     * @return array
     */
    public function queries_data_provider(): array {
        return [
            'admin + system' => ['admin', false, false,],
            'admin + tenant' => ['admin', true, false,],
            'user + system + cap' => ['user', false, false],
            'user + tenant + cap' => ['user', true, false],
            'user + system + no cap' => ['user', false, true],
            'user + tenant + no cap' => ['user', true, true],
        ];
    }

    /**
     * Assert filters are returned in the correct structure.
     *
     * @return void
     */
    public function test_filters(): void {
        /** @var \totara_useraction\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_useraction');

        /** @var \totara_cohort\testing\generator $audience_generator */
        $audience_generator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');
        $audience = $audience_generator->create_cohort();

        $this->setAdminUser();

        $rule = $generator->create_scheduled_rule([
            'filter_status' => status::ENUM_SUSPENDED,
            'filter_duration' => [
                'source' => duration::SOURCE_SUSPENDED,
                'unit' => duration::UNIT_MONTHS,
                'value' => 3
            ],
            'filter_applies_to' => [$audience->id],
        ]);

        $result = $this->execute_graphql_operation(self::GRAPHQL_PERSISTENT_SINGLE, ['id' => $rule->id]);
        $filters = $result->data['rule']['filters'];

        $this->assertEquals(status::ENUM_SUSPENDED, $filters['user_status']);
        $this->assertEqualsCanonicalizing(
            ['source' => duration::ENUM_SUSPENDED, 'unit' => duration::ENUM_UNIT_MONTHS, 'value' => 3],
            $filters['duration']
        );
        $this->assertEqualsCanonicalizing(
            [['id' => $audience->id, 'name' => $audience->name]],
            $filters['applies_to']['audiences']
        );
    }

    /**
     * Test multiple results from the GraphQL queries.
     *
     * @param string $user
     * @param bool $in_tenant
     * @param bool $expect_exception
     * @return void
     * @dataProvider queries_data_provider
     */
    public function test_multiple_queries(string $user, bool $in_tenant, bool $expect_exception): void {
        /** @var \totara_useraction\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_useraction');

        $rules = [];
        for ($i = 0; $i < 20; $i++) {
            $rules[] = $generator->create_scheduled_rule();
        }

        $tenant_generator = $this->getDataGenerator()->get_plugin_generator('totara_tenant');
        /** @var totara_tenant\testing\generator $tenant_generator */

        $tenant_generator->enable_tenants();
        $tenant = $tenant_generator->create_tenant();

        $tenant_rules = [];
        for ($i = 0; $i < 20; $i++) {
            $tenant_rules[] = $generator->create_scheduled_rule(['tenant_id' => $tenant->id]);
        }

        $tenant_id = $in_tenant ? $tenant->id : null;

        if ($user === 'admin') {
            $this->setAdminUser();
        } else {
            $user = $this->getDataGenerator()->create_user();
            if (!$expect_exception) {
                $this->grant_user_capability($user, $tenant_id);
            }
            $this->setUser($user);
        }

        $input = $in_tenant ? ['tenant_id' => $tenant_id] : [];

        if ($expect_exception) {
            $this->expectException(required_capability_exception::class);
            $this->resolve_graphql_query(self::GRAPHQL_MULTIPLE, compact('input'));
            return;
        }

        $results = $this->resolve_graphql_query(self::GRAPHQL_MULTIPLE, compact('input'));
        self::assertIsArray($results);
        self::assertArrayHasKey('items', $results);
        self::assertCount(20, $results['items']);

        // Pluck all the ids out
        $system_ids = $this->pluck('id', $rules);
        $tenant_ids = $this->pluck('id', $tenant_rules);
        $result_ids = $this->pluck('id', $results['items']);

        asort($system_ids);
        asort($tenant_ids);
        asort($result_ids);

        if ($in_tenant) {
            // Make sure we do not see system-level results inside the tenant
            self::assertNotEqualsCanonicalizing($system_ids, $result_ids);
            self::assertEqualsCanonicalizing($tenant_ids, $result_ids);
        } else {
            // Make sure we do not see tenant-level results
            self::assertNotEqualsCanonicalizing($tenant_ids, $result_ids);
            self::assertEqualsCanonicalizing($system_ids, $result_ids);
        }
    }

    /**
     * Assert the multiple queries handles pagination, cursor & regular.
     *
     * @return void
     */
    public function test_multiple_queries_pagination(): void {
        /** @var \totara_useraction\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_useraction');

        for ($i = 1; $i <= 20; $i++) {
            $rules[$i] = $generator->create_scheduled_rule(['name' => 'R' . $i]);
        }

        $this->setAdminUser();

        $input = ['tenant_id' => null, 'pagination' => ['limit' => 2, 'page' => 1]];
        $results = $this->resolve_graphql_query(self::GRAPHQL_MULTIPLE, compact('input'));

        self::assertArrayHasKey('items', $results);
        self::assertCount(2, $results['items']);

        $this->assert_rules_match($rules[20], $results['items'][0]);
        $this->assert_rules_match($rules[19], $results['items'][1]);

        // Page 2
        $input = ['tenant_id' => null, 'pagination' => ['limit' => 2, 'page' => 2]];
        $results = $this->resolve_graphql_query(self::GRAPHQL_MULTIPLE, compact('input'));
        self::assertArrayHasKey('items', $results);
        self::assertCount(2, $results['items']);

        $this->assert_rules_match($rules[18], $results['items'][0]);
        $this->assert_rules_match($rules[17], $results['items'][1]);

        // Now try with the cursor
        $input = ['tenant_id' => null, 'pagination' => ['cursor' => $results['next_cursor']]];
        $results = $this->resolve_graphql_query(self::GRAPHQL_MULTIPLE, compact('input'));
        self::assertArrayHasKey('items', $results);
        self::assertCount(2, $results['items']);

        $this->assert_rules_match($rules[16], $results['items'][0]);
        $this->assert_rules_match($rules[15], $results['items'][1]);
    }

    /**
     * Test the totara_useraction_scheduled_rule_for_editing persistent query.
     *
     * @return void
     */
    public function test_persistent_scheduled_rule_for_editing() {
        /** @var \totara_useraction\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_useraction');

        $this->setAdminUser();

        // Generate a few
        $rule_a = $generator->create_scheduled_rule();
        $rule_b = $generator->create_scheduled_rule();
        $rule_c = $generator->create_scheduled_rule();

        $assert_result_matches_model = function (model $expected, $actual): void {
            self::assertIsArray($actual);

            $keys = ['__typename', 'id', 'name', 'description', 'status'];
            foreach ($keys as $key) {
                self::assertArrayHasKey($key, $actual);
                if ($key === '__typename') {
                    self::assertSame('totara_useraction_scheduled_rule', $actual[$key]);
                    continue;
                }

                self::assertEquals($expected->$key, $actual[$key]);
            }
        };

        foreach ([$rule_a, $rule_b, $rule_c] as $rule) {
            $result = $this->execute_graphql_operation(self::GRAPHQL_PERSISTENT_SINGLE, ['id' => $rule->id]);
            self::assertEmpty($result->errors);
            self::assertArrayHasKey('rule', $result->data);
            $assert_result_matches_model($rule, $result->data['rule']);
        }

        // Now try loading as with the capability
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->grant_user_capability($user);

        $result = $this->execute_graphql_operation(self::GRAPHQL_PERSISTENT_SINGLE, ['id' => $rule_a->id]);
        self::assertEmpty($result->errors);
        self::assertArrayHasKey('rule', $result->data);
        $assert_result_matches_model($rule_a, $result->data['rule']);

        // Now try without the capability
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $result = $this->execute_graphql_operation(self::GRAPHQL_PERSISTENT_SINGLE, ['id' => $rule_a->id]);
        $this->assert_capability_error($result);
    }

    /**
     * Test multiple results from the GraphQL queries.
     *
     * @param string $user
     * @param bool $in_tenant
     * @param bool $expect_exception
     * @return void
     * @dataProvider queries_data_provider
     */
    public function test_persistent_scheduled_rules(string $user, bool $in_tenant, bool $expect_exception): void {
        /** @var \totara_useraction\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_useraction');

        $rules = [];
        for ($i = 0; $i < 20; $i++) {
            $rules[] = $generator->create_scheduled_rule();
        }

        $tenant_generator = $this->getDataGenerator()->get_plugin_generator('totara_tenant');
        /** @var totara_tenant\testing\generator $tenant_generator */

        $tenant_generator->enable_tenants();
        $tenant = $tenant_generator->create_tenant();

        $tenant_rules = [];
        for ($i = 0; $i < 20; $i++) {
            $tenant_rules[] = $generator->create_scheduled_rule(['tenant_id' => $tenant->id]);
        }

        $tenant_id = $in_tenant ? $tenant->id : null;

        if ($user === 'admin') {
            $this->setAdminUser();
        } else {
            $user = $this->getDataGenerator()->create_user();
            if (!$expect_exception) {
                $this->grant_user_capability($user, $tenant_id);
            }
            $this->setUser($user);
        }

        $input = $in_tenant ? ['tenant_id' => $tenant_id] : [];

        if ($expect_exception) {
            $result = $this->execute_graphql_operation(self::GRAPHQL_PERSISTENT_MULTIPLE, compact('input'));
            $this->assert_capability_error($result, 'rules');
            return;
        }

        $result = $this->execute_graphql_operation(self::GRAPHQL_MULTIPLE, compact('input'));
        self::assertEmpty($result->errors);
        self::assertArrayHasKey('rules', $result->data);
        self::assertArrayHasKey('items', $result->data['rules']);

        $items = $result->data['rules']['items'];
        self::assertCount(20, $items);

        // Pluck all the ids out
        $system_ids = $this->pluck('id', $rules);
        $tenant_ids = $this->pluck('id', $tenant_rules);
        $result_ids = $this->pluck('id', $items);

        asort($system_ids);
        asort($tenant_ids);
        asort($result_ids);

        if ($in_tenant) {
            // Make sure we do not see system-level results inside the tenant
            self::assertNotEqualsCanonicalizing($system_ids, $result_ids);
            self::assertEqualsCanonicalizing($tenant_ids, $result_ids);
        } else {
            // Make sure we do not see tenant-level results
            self::assertNotEqualsCanonicalizing($tenant_ids, $result_ids);
            self::assertEqualsCanonicalizing($system_ids, $result_ids);
        }
    }

    /**
     * Test loading a single scheduled rule from GraphQL.
     *
     * @return void
     */
    public function test_single_query(): void {
        /** @var \totara_useraction\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_useraction');

        $this->setAdminUser();

        // Generate a few
        $rule_a = $generator->create_scheduled_rule();
        $rule_b = $generator->create_scheduled_rule();
        $rule_c = $generator->create_scheduled_rule();

        foreach ([$rule_a, $rule_b, $rule_c] as $rule) {
            $result = $this->resolve_graphql_query(self::GRAPHQL_SINGLE, ['id' => $rule->id]);
            self::assertInstanceOf(model::class, $result);
            $this->assert_rules_match($rule, $result);
        }

        // Now try loading as with the capability
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->grant_user_capability($user);

        $result = $this->resolve_graphql_query(self::GRAPHQL_SINGLE, ['id' => $rule_a->id]);
        self::assertInstanceOf(model::class, $result);
        $this->assert_rules_match($rule_a, $result);

        // Now try without the capability
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->expectException(required_capability_exception::class);
        $this->resolve_graphql_query(self::GRAPHQL_SINGLE, ['id' => $rule_a->id]);
    }

    /**
     * Parse the result for a capability error
     *
     * @param ExecutionResult $result
     * @param string|null $key If provided, the wrapper key results are kept in.
     * @return void
     */
    private function assert_capability_error(ExecutionResult $result, string $key = null): void {
        self::assertNotEmpty($result->errors);

        if ($key) {
            self::assertArrayHasKey($key, $result->data);
            self::assertEmpty($result->data[$key]);
        } else {
            self::assertEmpty($result->data);
        }

        /** @var \GraphQL\Error\Error $error */
        $error = current($result->errors);
        self::assertSame(
            'Sorry, but you do not currently have permissions to do that (Manage User Actions)',
            $error->getMessage()
        );
    }

    /**
     * Compares the rule object array values for a match.
     *
     * @param model $a
     * @param model $b
     * @return void
     */
    private function assert_rules_match(model $a, model $b) {
        $a = $a->to_array();
        $b = $b->to_array();
        ksort($a);
        ksort($b);
        self::assertEqualsCanonicalizing($a, $b);
    }

    /**
     * Grant the provided user the manage capability.
     *
     * @param stdClass $user
     * @param int|null $tenant_id
     * @return void
     */
    private function grant_user_capability(stdClass $user, ?int $tenant_id = null) {
        $role_id = $this->getDataGenerator()->create_role();

        $context = $tenant_id ? context_tenant::instance($tenant_id) : context_system::instance();
        role_assign($role_id, $user->id, $context->id);
        assign_capability('totara/useraction:manage_actions', CAP_ALLOW, $role_id, $context->id);
    }

    /**
     * Helper to pluck a single result out of the collection.
     *
     * @param string $column
     * @param array $collection
     * @return array
     */
    private function pluck(string $column, array $collection): array {
        return array_map(function ($result) use ($column) {
            return is_array($result) ? $result[$column] : $result->$column;
        }, $collection);
    }
}