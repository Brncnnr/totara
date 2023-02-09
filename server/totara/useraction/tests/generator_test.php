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
use totara_useraction\action\delete_user;
use totara_useraction\fixtures\mock_action;
use totara_useraction\local\testing\mock_actions;
use totara_useraction\model\scheduled_rule as model;

/**
 * Tests the generator class.
 *
 * @group totara_useraction
 */
class totara_useraction_generator_testcase extends testcase {
    use mock_actions;

    /**
     * @return void
     */
    public function test_create_history_entry(): void {
        $generator = \totara_useraction\testing\generator::instance();

        $this->setAdminUser();

        $instance = $generator->create_history_entry([]);

        self::assertNotEmpty($instance->created);
        self::assertNotEmpty($instance->success);
        self::assertNotEmpty($instance->action);
        self::assertNotEmpty($instance->scheduled_rule_id);
        self::assertNotEmpty($instance->user_id);

        // Make a rule and confirm it gets assigned
        $rule = $generator->create_scheduled_rule();
        $instance = $generator->create_history_entry(['rule_id' => $rule->id]);

        self::assertNotEmpty($instance->created);
        self::assertNotEmpty($instance->success);
        self::assertNotEmpty($instance->action);
        self::assertEquals($rule->id, $instance->scheduled_rule_id);
        self::assertNotEmpty($instance->user_id);
    }

    /**
     * @return void
     */
    public function test_create_history_entry_from_params(): void {
        $generator = \totara_useraction\testing\generator::instance();

        $this->setAdminUser();

        $instance = $generator->create_history_entry_from_params();
        self::assertNotEmpty($instance->created);
        self::assertNotEmpty($instance->success);
        self::assertNotEmpty($instance->action);
        self::assertNotEmpty($instance->scheduled_rule_id);
        self::assertNotEmpty($instance->user_id);

        // Make a rule and confirm it gets assigned
        $rule = $generator->create_scheduled_rule(['name' => 'ABC']);
        $instance = $generator->create_history_entry_from_params(['rule' => 'ABC']);

        self::assertNotEmpty($instance->created);
        self::assertNotEmpty($instance->success);
        self::assertNotEmpty($instance->action);
        self::assertEquals($rule->id, $instance->scheduled_rule_id);
        self::assertNotEmpty($instance->user_id);
    }

    /**
     * Assert a scheduled_rule can be created.
     *
     * @return void
     */
    public function test_create_scheduled_rule(): void {
        $generator = \totara_useraction\testing\generator::instance();

        $instance = $generator->create_scheduled_rule();

        self::assertInstanceOf(model::class, $instance);
        self::assertIsNumeric($instance->id);
        self::assertNotEmpty($instance->name);
        self::assertSame('', $instance->description);
        self::assertNull($instance->tenant_id);
        self::assertEquals(0, $instance->status);
        self::assertNotEmpty($instance->created);
        self::assertNotEmpty($instance->updated);
        self::assertInstanceOf(delete_user::class, $instance->action);

        $instance = $generator->create_scheduled_rule([
            'name' => 'Name',
            'description' => 'This is a description',
            'tenant_id' => null,
            'status' => 1,
            'action' => mock_action::class,
        ]);

        self::assertInstanceOf(model::class, $instance);
        self::assertIsNumeric($instance->id);
        self::assertSame('Name', $instance->name);
        self::assertSame('This is a description', $instance->description);
        self::assertNull($instance->tenant_id);
        self::assertEquals(1, $instance->status);
        self::assertNotEmpty($instance->created);
        self::assertNotEmpty($instance->updated);
        self::assertInstanceOf(mock_action::class, $instance->action);

        // Multi-tenancy
        /** @var \totara_tenant\testing\generator $multitenancy */
        $multitenancy = $this->getDataGenerator()->get_plugin_generator('totara_tenant');
        $multitenancy->enable_tenants();

        $tenant = $multitenancy->create_tenant();

        $instance = $generator->create_scheduled_rule([
            'tenant_id' => $tenant->id,
        ]);
        self::assertInstanceOf(model::class, $instance);
        self::assertEquals($tenant->id, $instance->tenant_id);
    }

    /**
     * @return void
     */
    public function test_create_scheduled_rule_from_params(): void {
        $generator = \totara_useraction\testing\generator::instance();

        // Create an instance with minimal properties
        $instance = $generator->create_scheduled_rule_from_params(['name' => 'Test Name']);

        self::assertInstanceOf(model::class, $instance);
        self::assertIsNumeric($instance->id);
        self::assertSame('Test Name', $instance->name);
        self::assertSame('', $instance->description);
        self::assertNull($instance->tenant_id);
        self::assertEquals(0, $instance->status);
        self::assertNotEmpty($instance->created);
        self::assertNotEmpty($instance->updated);
        self::assertInstanceOf(delete_user::class, $instance->action);

        // Create an instance passing in the behat-friendly values
        $params = [
            'name' => 'A Second test',
            'description' => 'More',
            'status' => false,
            'user_status' => 'SUSPENDED',
            'data_source' => 'DATE_SUSPENDED',
            'duration_unit' => 'YEAR',
            'duration_value' => 32,
            'applies_to' => 'ALL_USERS',
        ];

        $instance = $generator->create_scheduled_rule_from_params($params);
        self::assertInstanceOf(model::class, $instance);
        self::assertIsNumeric($instance->id);
        self::assertSame('A Second test', $instance->name);
        self::assertSame('More', $instance->description);
        self::assertSame(0, $instance->filter_user_status->get_status());
        $duration = [
            'source' => 'DATE_SUSPENDED',
            'unit' => 'YEAR',
            'value' => 32,
        ];
        self::assertEqualsCanonicalizing($duration, $instance->filter_duration->to_graphql());
        self::assertTrue($instance->filter_applies_to->is_all_users());
        self::assertSame(0, $instance->filter_applies_to->get_audiences()->count());
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
}
