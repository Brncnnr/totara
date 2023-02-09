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

use core\webapi\resolver\payload;
use core_phpunit\testcase;
use totara_useraction\model\scheduled_rule;
use totara_useraction\webapi\middleware\require_capability;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Test the require_capability middleware.
 *
 * @group totara_useraction
 */
class totara_useraction_middleware_require_capability_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * Assert that an access error is triggered when an invalid ID is provided.
     *
     * @return void
     */
    public function test_from_id_callback_with_invalid_rule(): void {
        $middleware = require_capability::from_id('id');
        $callback = $this->get_callback($middleware);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Not found');
        $callback($this->make_payload(['id' => '-1']));
    }

    /**
     * Assert that an access error is triggered when a tenant crosses tenant boundaries.
     *
     * @return void
     */
    public function test_from_id_callback_with_invalid_tenant_id(): void {
        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $this->getDataGenerator()->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        /** @var \totara_useraction\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_useraction');
        $tenant_a = $tenant_generator->create_tenant();
        $rule = $generator->create_scheduled_rule(['tenant_id' => $tenant_a->id]);

        $tenant_b = $tenant_generator->create_tenant();
        $user_b = $this->getDataGenerator()->create_user();
        $tenant_generator->migrate_user_to_tenant($user_b->id, $tenant_b->id);
        $user_b->tenantid = $tenant_b->id;

        // Tenant B member accessing Tenant A rule
        $this->setUser($user_b);
        $null_closure = function (): void {
            // This function does not get called, so fail it if it does.
            $this->fail('Tenant ID check failed.');
        };

        $middleware = require_capability::from_tenant_id('tenant_id');
        $payload = $this->make_payload(['tenant_id' => $rule->get_tenant()->id]);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Not found');
        $middleware->handle($payload, $null_closure);
    }

    /**
     * Assert an coding error is triggered when no ID is provided.
     *
     * @return void
     */
    public function test_from_id_callback_with_null(): void {
        $middleware = require_capability::from_id('id');
        $callback = $this->get_callback($middleware);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('No ID was provided');
        $callback($this->make_payload([]));
    }

    /**
     * Assert the expected callback ID is shown.
     *
     * @return void
     */
    public function test_from_id_callback_with_valid_id(): void {
        $this->setAdminUser();

        $middleware = require_capability::from_id('id');
        $callback = $this->get_callback($middleware);

        /** @var \totara_useraction\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_useraction');
        $rule = $generator->create_scheduled_rule();

        // Check for no tenant id on the rule
        $result = $callback($this->make_payload(['id' => $rule->get_id()]));
        self::assertNull($result);

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $this->getDataGenerator()->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();
        $tenant = $tenant_generator->create_tenant();
        $rule = $generator->create_scheduled_rule(['tenant_id' => $tenant->id]);

        // Check for the tenant ID on the rule
        $payload = $this->make_payload(['id' => $rule->get_id()]);
        $result = $callback($payload);
        self::assertEquals($tenant->id, $result);

        // Now confirm the payload is populated with the model
        $model = $payload->get_variable('scheduled_rule_model');
        self::assertInstanceOf(scheduled_rule::class, $model);
        self::assertEquals($rule->get_id(), $model->get_id());
    }

    /**
     * @param require_capability $middleware
     * @return callable|null
     */
    private function get_callback(require_capability $middleware): ?callable {
        $property = new ReflectionProperty(require_capability::class, 'tenant_id_loader');
        $property->setAccessible(true);
        return $property->getValue($middleware);
    }

    /**
     * @param $args
     * @return payload
     */
    private function make_payload($args): payload {
        $context = $this->create_webapi_context('testing');
        return payload::create($args, $context);
    }
}