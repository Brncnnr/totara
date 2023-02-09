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
use totara_useraction\action\factory;
use totara_useraction\entity\scheduled_rule as entity;
use totara_useraction\entity\scheduled_rule_audience_map;
use totara_useraction\exception\invalid_action_exception;
use totara_useraction\exception\missing_filter_duration_fields;
use totara_useraction\exception\missing_name_field_exception;
use totara_useraction\filter\applies_to;
use totara_useraction\filter\duration;
use totara_useraction\filter\status;
use totara_useraction\fixtures\mock_action;
use totara_useraction\local\testing\mock_actions;
use totara_useraction\model\scheduled_rule as model;
use totara_useraction\webapi\resolver\mutation\delete_scheduled_rule;
use totara_useraction\webapi\resolver\mutation\update_scheduled_rule;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Test the GraphQL mutations for scheduled rules.
 *
 * @group totara_useraction
 */
class totara_useraction_scheduled_rule_mutations_testcase extends testcase {

    use webapi_phpunit_helper, mock_actions;

    const GRAPHQL_CREATE = 'totara_useraction_create_scheduled_rule';
    const GRAPHQL_UPDATE = 'totara_useraction_update_scheduled_rule';
    const GRAPHQL_DELETE = 'totara_useraction_delete_scheduled_rule';

    /**
     * @return array
     */
    public function provide_delete_payloads(): array {
        return [
            'direct' => [false],
            'persistent' => [true],
        ];
    }

    /**
     * @return array
     */
    public function provide_missing_field_data(): array {
        return [
            'direct + name + update' => ['name', true, false],
            'direct + name + create' => ['name', false, false],
            'direct + action + update' => ['action', true, false],
            'direct + action + create' => ['action', false, false],
            'persistent + name + update' => ['name', true, true],
            'persistent + name + create' => ['name', false, true],
            'persistent + action + update' => ['action', true, true],
            'persistent + action + create' => ['action', false, true],
        ];
    }

    /**
     * @return array
     */
    public function provide_mutation_payloads(): array {
        return [
            'direct + minimal' => [
                false,
                [
                    'name' => 'min',
                    'action' => mock_action::class,
                ]
            ],
            'direct + partial' => [
                false,
                [
                    'name' => 'partial',
                    'description' => 'Testing',
                    'status' => true,
                    'action' => mock_action::class,
                ]
            ],
            'direct + disabled' => [
                false,
                [
                    'name' => 'partial',
                    'description' => 'Testing',
                    'status' => false,
                    'action' => mock_action::class,
                ]
            ],
            'persistent + minimal' => [
                true,
                [
                    'name' => 'min',
                    'action' => mock_action::class,
                ]
            ],
            'persistent + partial' => [
                true,
                [
                    'name' => 'partial',
                    'description' => 'Testing',
                    'status' => true,
                    'action' => mock_action::class,
                ]
            ],
            'persistent + disabled' => [
                true,
                [
                    'name' => 'partial',
                    'description' => 'Testing',
                    'status' => false,
                    'action' => mock_action::class,
                ]
            ],
        ];
    }

    /**
     * Assert that calling the create & update mutations will check the fields are not empty.
     *
     * @param string $field The field that we're checking (id number, name or action).
     * @param bool $is_update Update vs Create
     * @param bool $persistent Calling via the persistent query instead
     * @return void
     * @dataProvider provide_missing_field_data
     */
    public function test_missing_field_exceptions(string $field, bool $is_update, bool $persistent): void {
        /** @var \totara_useraction\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_useraction');

        $this->setAdminUser();

        $payload = [];
        if ($is_update) {
            $rule = $generator->create_scheduled_rule();
            $payload['id'] = $rule->id;
        } else {
            $payload['tenant_id'] = null;
        }

        $payload['name'] = 'name';
        $payload = $generator->get_minimal_scheduled_rule_parameters($payload);

        $payload[$field] = ''; // Testing we catch an empty string

        $expected = missing_name_field_exception::class;
        $expected_message = 'The name field must not be empty.';
        if ($field === 'action') {
            $expected = invalid_action_exception::class;
            $expected_message = 'The action field must be a valid action.';
        }

        $mutation = $is_update ? self::GRAPHQL_UPDATE : self::GRAPHQL_CREATE;

        if (!$persistent) {
            $this->expectException($expected);
            $this->mutate_with_input($mutation, $payload);
            return;
        }

        /** @var ExecutionResult $result */
        $result = $this->mutate_with_input($mutation, $payload, true);
        self::assertNotEmpty($result->errors);
        self::assertEmpty($result->data);

        /** @var \GraphQL\Error\Error $error */
        $error = current($result->errors);

        self::assertSame($expected_message, $error->getMessage());
    }

    /**
     * Assert that a user can create a scheduled rule at system & tenant levels.
     *
     * @param bool $persistent
     * @param array $payload
     * @return void
     * @throws coding_exception
     * @dataProvider provide_mutation_payloads
     */
    public function test_mutation_create(bool $persistent, array $payload): void {
        /** @var \totara_useraction\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_useraction');

        $user = $this->getDataGenerator()->create_user();
        $this->grant_user_capability($user);
        $this->setUser($user);

        $payload['tenant_id'] = null;
        $payload = $generator->get_minimal_scheduled_rule_parameters($payload);

        $result = $this->mutate_with_input(self::GRAPHQL_CREATE, $payload, $persistent);
        $this->assert_validate_create_update_mutation($result, $payload, $persistent);

        // Now create another in a tenant
        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $this->getDataGenerator()->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();
        $tenant = $tenant_generator->create_tenant();

        $payload['tenant_id'] = $tenant->id;

        $user2 = $this->getDataGenerator()->create_user();
        $this->grant_user_capability($user2, $tenant->id);
        $this->setUser($user2);

        $result = $this->mutate_with_input(self::GRAPHQL_CREATE, $payload, $persistent);
        $this->assert_validate_create_update_mutation($result, $payload, $persistent);
    }

    /**
     * Assert that a rule cannot be created without the capability.
     *
     * @return void
     */
    public function test_mutation_create_no_capability(): void {
        /** @var \totara_useraction\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_useraction');

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Test the persistent method
        $result = $this->mutate_with_input(
            self::GRAPHQL_CREATE,
            $generator->get_minimal_scheduled_rule_parameters(),
            true
        );
        $this->assert_capability_error($result);

        $this->expectException(required_capability_exception::class);
        $this->mutate_with_input(
            self::GRAPHQL_CREATE,
            $generator->get_minimal_scheduled_rule_parameters(),
        );
    }

    /**
     * Assert that a rule can be deleted via GraphQL, tests both the direct and persistent mutations.
     *
     * @param bool $persistent
     * @return void
     * @dataProvider provide_delete_payloads
     */
    public function test_mutation_delete(bool $persistent): void {
        global $DB;

        /** @var \totara_useraction\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_useraction');
        $this->setAdminUser();

        $rule = $generator->create_scheduled_rule();

        /**
         * Helper method to run the delete & assert the record no longer exists.
         *
         * @param int $rule_id
         * @return void
         */
        $assert_delete = function (int $rule_id) use ($DB, $persistent): void {
            $result = $this->mutate(self::GRAPHQL_DELETE, ['id' => $rule_id], $persistent);
            if ($persistent) {
                /** @var ExecutionResult $result */
                self::assertArrayHasKey('result', $result->data);
                self::assertTrue($result->data['result']);
                self::assertEmpty($result->errors);
            } else {
                self::assertTrue($result);
            }
            self::assertFalse($DB->record_exists(entity::TABLE, ['id' => $rule_id]));
        };

        // Confirm admin can delete
        $assert_delete($rule->id);

        // Now create a regular user, confirm they cannot delete a rule
        $rule2 = $generator->create_scheduled_rule();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $caught = false;
        if ($persistent) {
            $result = $this->mutate(self::GRAPHQL_DELETE, ['id' => $rule2->id], true);
            $this->assert_capability_error($result);
        } else {
            // We test the validation in a try/catch as we want to continue the test afterwards
            try {
                $this->mutate(self::GRAPHQL_DELETE, ['id' => $rule2->id]);
            } catch (Exception $ex) {
                self::assertInstanceOf(required_capability_exception::class, $ex);
                $caught = true;
            } finally {
                self::assertTrue($caught);
            }
        }

        self::assertTrue($DB->record_exists(entity::TABLE, ['id' => $rule2->id]));

        // Grant the capability
        $this->grant_user_capability($user);

        // Confirm we can still delete
        $assert_delete($rule2->id);
    }

    /**
     * Asserts that the delete mutation does include a check the middleware provides the model.
     *
     * @return void
     */
    public function test_mutation_delete_checks_for_model_in_payload(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            'Coding error detected, it must be fixed by a programmer: ' .
            'Resolution failure with the scheduled rule delete mutation.'
        );

        $ec = $this->create_webapi_context('testing');
        delete_scheduled_rule::resolve([], $ec);
    }

    /**
     * Assert that mutations correctly update the specified fields, tenant and non-tenant.
     *
     * @param bool $persistent
     * @param array $payload
     * @return void
     * @dataProvider provide_mutation_payloads
     */
    public function test_mutation_update(bool $persistent, array $payload): void {
        /** @var \totara_useraction\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_useraction');

        $rule = $generator->create_scheduled_rule();

        $payload['id'] = $rule->id;

        $user = $this->getDataGenerator()->create_user();
        $this->grant_user_capability($user);
        $this->setUser($user);

        $result = $this->mutate_with_input(self::GRAPHQL_UPDATE, $payload, $persistent);
        $payload['tenant_id'] = null;
        $this->assert_validate_create_update_mutation($result, $payload, $persistent);

        // Now create another in a tenant
        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $this->getDataGenerator()->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();
        $tenant = $tenant_generator->create_tenant();

        $rule = $generator->create_scheduled_rule(['tenant_id' => $tenant->id]);
        $payload['id'] = $rule->id;
        unset($payload['tenant_id']);

        $user2 = $this->getDataGenerator()->create_user();
        $this->grant_user_capability($user2, $tenant->id);
        $this->setUser($user2);

        $result = $this->mutate_with_input(self::GRAPHQL_UPDATE, $payload, $persistent);
        $payload['tenant_id'] = $tenant->id;
        $this->assert_validate_create_update_mutation($result, $payload, $persistent);
    }

    /**
     * Asserts that the update mutation does include a check the middleware provides the model.
     *
     * @return void
     */
    public function test_mutation_update_checks_for_model_in_payload(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            'Coding error detected, it must be fixed by a programmer: ' .
            'Resolution failure with the scheduled rule update mutation.'
        );

        $ec = $this->create_webapi_context('testing');
        update_scheduled_rule::resolve([], $ec);
    }

    /**
     * Assert that duration fields are caught if missing.
     *
     * @return void
     */
    public function test_mutation_update_missing_duration_fields(): void {
        /** @var \totara_useraction\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_useraction');

        $rule = $generator->create_scheduled_rule();
        $this->setAdminUser();

        $payload = [
            'id' => $rule->get_id(),
            'filter_duration' => [
                'value' => 10,
            ]
        ];

        $this->expectException(missing_filter_duration_fields::class);
        $this->mutate_with_input(self::GRAPHQL_UPDATE, $payload, false);
    }

    /**
     * Assert that a rule cannot be updated without the capability.
     *
     * @return void
     */
    public function test_mutation_update_no_capability(): void {
        /** @var \totara_useraction\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_useraction');

        $rule = $generator->create_scheduled_rule();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $payload = [
            'id' => $rule->id,
            'name' => 'Rule Name',
            'action' => mock_action::class,
        ];

        // Test the persistent method
        $result = $this->mutate_with_input(self::GRAPHQL_UPDATE, $payload, true);
        $this->assert_capability_error($result);

        $this->expectException(required_capability_exception::class);
        $this->mutate_with_input(self::GRAPHQL_UPDATE, $payload);
    }

    /**
     * Tests the individual fields can be updated successfully.
     *
     * @param bool $persistent
     * @return void
     * @dataProvider provide_delete_payloads
     */
    public function test_update_field_check(bool $persistent): void {
        $this->setAdminUser();

        /** @var \totara_useraction\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_useraction');
        $rule = $generator->create_scheduled_rule();

        // Test we can update the other fields
        $payload = [
            'id' => $rule->id,
            'name' => 'My Name',
            'description' => 'Some text',
            'action' => mock_action::class,
            'status' => false,
            'filter_user_status' => status::ENUM_DELETED,
            'filter_duration' => [
                'source' => duration::ENUM_SUSPENDED,
                'unit' => duration::ENUM_UNIT_YEARS,
                'value' => 10
            ],
            'filter_applies_to' => [
                'audiences' => null,
            ],
        ];
        $result = $this->mutate_with_input(self::GRAPHQL_UPDATE, $payload, $persistent);
        $this->assert_validate_create_update_mutation($result, $payload, $persistent);

        // Load the rule and confirm the updates occurred
        /** @var entity $rule_entity */
        $rule_entity = entity::repository()->where('id', $rule->id)->one();
        self::assertEquals('My Name', $rule_entity->name);
        self::assertEquals('Some text', $rule_entity->description);
        self::assertFalse($rule_entity->status);
        self::assertEquals(mock_action::class, $rule_entity->action);
        self::assertEquals(status::STATUS_DELETED, $rule_entity->filter_status);
        self::assertEquals(duration::SOURCE_SUSPENDED, $rule_entity->filter_duration_source);
        self::assertEquals(duration::UNIT_YEARS, $rule_entity->filter_duration_unit);
        self::assertEquals(duration::unit_to_seconds(10, duration::UNIT_YEARS), $rule_entity->filter_duration_value);
        self::assertTrue($rule_entity->filter_all_users);

        // Try applies to with actual audiences again
        /** @var \totara_cohort\testing\generator $audience_generator */
        $audience_generator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');
        $audience_a = $audience_generator->create_cohort();
        $audience_b = $audience_generator->create_cohort();

        $payload = [
            'id' => $rule->id,
            'filter_applies_to' => [
                'audiences' => [
                    $audience_a->id,
                    $audience_b->id,
                ],
            ],
        ];

        // Check there are no mapped audiences
        $count = scheduled_rule_audience_map::repository()->where('scheduled_rule_id', $rule->id)->count();
        self::assertEquals(0, $count);

        $result = $this->mutate_with_input(self::GRAPHQL_UPDATE, $payload, $persistent);
        $this->assert_validate_create_update_mutation($result, $payload, $persistent);

        // Check we have mapped audiences
        $count = scheduled_rule_audience_map::repository()->where('scheduled_rule_id', $rule->id)->count();
        self::assertEquals(2, $count);

        // Try removing an audience
        $payload = [
            'id' => $rule->id,
            'filter_applies_to' => [
                'audiences' => [
                    $audience_a->id,
                ],
            ],
        ];

        $result = $this->mutate_with_input(self::GRAPHQL_UPDATE, $payload, $persistent);
        $this->assert_validate_create_update_mutation($result, $payload, $persistent);

        // Check we have mapped audiences
        $count = scheduled_rule_audience_map::repository()->where('scheduled_rule_id', $rule->id)->count();
        self::assertEquals(1, $count);
    }

    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->inject_mock_actions();
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        parent::tearDown();
        $this->remove_mock_actions();
    }

    /**
     * Parse the result for a capability error
     *
     * @param ExecutionResult $result
     * @param string|null $key If provided, the wrapper key results are kept in.
     * @return void
     */
    private function assert_capability_error(ExecutionResult $result, string $key = null): void {
        $this->assert_error(
            'Sorry, but you do not currently have permissions to do that (Manage User Actions)',
            $result,
            $key
        );
    }

    /**
     * Assert an error was seen in the response.
     *
     * @param string $error_message
     * @param ExecutionResult $result
     * @param string|null $key
     * @return void
     */
    private function assert_error(string $error_message, ExecutionResult $result, string $key = null): void {
        self::assertNotEmpty($result->errors);

        if ($key) {
            self::assertArrayHasKey($key, $result->data);
            self::assertEmpty($result->data[$key]);
        } else {
            self::assertEmpty($result->data);
        }

        /** @var \GraphQL\Error\Error $error */
        $error = current($result->errors);
        self::assertSame($error_message, $error->getMessage());
    }

    /**
     * Small helper to validate the helper has what we expect with both methods.
     *
     * @param mixed $result
     * @param array $payload
     * @param bool $persistent
     * @return void
     */
    private function assert_validate_create_update_mutation($result, array $payload, bool $persistent) {
        if ($persistent) {
            self::assertEmpty($result->errors);
            self::assertArrayHasKey('rule', $result->data);
            self::assertIsArray($result->data['rule']);
            self::assertArrayHasKey('id', $result->data['rule']);
            self::assertIsNumeric($result->data['rule']['id']);
        } else {
            $this->assertInstanceOf(model::class, $result);

            foreach ($payload as $key => $value) {
                if ($key === 'action') {
                    self::assertTrue(factory::is_valid($value));
                    continue;
                }
                if ($key === 'filter_user_status') {
                    self::assertInstanceOf(status::class, $result->$key);
                    continue;
                }
                if ($key === 'filter_duration') {
                    self::assertInstanceOf(duration::class, $result->$key);
                    continue;
                }
                if ($key === 'filter_applies_to') {
                    self::assertInstanceOf(applies_to::class, $result->$key);
                    continue;
                }

                self::assertEquals($value, $result->$key, 'key is ' . $key);
            }
        }
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
     * Shortcut around the mutation method.
     *
     * @param string $operation
     * @param array $input
     * @param bool $persistent
     * @return mixed|null
     */
    private function mutate(string $operation, array $input, bool $persistent = false) {
        if ($persistent) {
            return $this->execute_graphql_operation($operation, $input);
        }
        return $this->resolve_graphql_mutation($operation, $input);
    }

    /**
     * Shortcut around the mutation method.
     *
     * @param string $operation
     * @param array $input
     * @param bool $persistent
     * @return mixed|null
     */
    private function mutate_with_input(string $operation, array $input, bool $persistent = false) {
        return $this->mutate($operation, compact('input'), $persistent);
    }
}