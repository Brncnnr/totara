<?php
/**
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package core
 */

defined('MOODLE_INTERNAL') || die();

use core\entity\tenant;
use core\theme\settings as theme_settings;
use totara_webapi\phpunit\webapi_phpunit_helper;

class core_webapi_mutation_update_theme_settings_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    private const MUTATION = 'core_update_theme_settings';
    private const THEME = 'ventura';

    public function test_no_permission_for_system_level(): void {
        [, $params] = self::create_test_data();

        $this->assert_error(
            $params,
            'Sorry, but you do not currently have permissions to do that (Manage theme settings)'
        );
    }

    public function test_no_permission_for_tenant_level(): void {
        [, $params] = self::create_test_data();
        [$tenant1, ] = self::create_tenants();

        $params['tenant_id'] = $tenant1->id;

        $this->assert_error(
            $params,
            'Sorry, but you do not currently have permissions to do that (Manage theme settings)'
        );
    }

    public function test_with_system_permissions(): void {
        [$user, $params] = self::create_test_data();
        self::assign_appearance_role($user);

        $this->assert_success($params);
    }

    public function test_tenant_id_with_multi_tenancy_off(): void {
        [$user, $params] = self::create_test_data();
        self::assign_appearance_role($user);

        // Get a valid tenant id.
        [$tenant1, ] = self::create_tenants();
        $params['tenant_id'] = $tenant1->id;

        // Disable tenants.
        $generator = self::getDataGenerator();
        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->disable_tenants();

        $this->assert_error($params, 'Can only set tenant_id when multi-tenancy is enabled.');
    }

    public function test_invalid_tenant_id(): void {
        [$user, $params] = self::create_test_data();
        self::assign_appearance_role($user);
        self::create_tenants();

        $invalid_tenant_id = 9999;
        while (tenant::repository()->find($invalid_tenant_id)) {
            $invalid_tenant_id ++;
        }
        $params['tenant_id'] = $invalid_tenant_id;

        $this->assert_error($params, 'Invalid tenant_id');
    }

    public function test_cross_tenant(): void {
        [$user, $params] = self::create_test_data();
        [$tenant1, $tenant2] = self::create_tenants();
        self::assign_appearance_role($user, $tenant1->id);

        $params['tenant_id'] = $tenant2->id;
        $this->assert_error(
            $params,
            'Sorry, but you do not currently have permissions to do that (Manage theme settings)'
        );
    }

    public function test_tenant_branding_not_enabled(): void {
        [$tenant1, ] = self::create_tenants();
        [$user, $params] = self::create_test_data($tenant1);
        self::assign_appearance_role($user, $tenant1->id);

        $params['tenant_id'] = $tenant1->id;
        $this->assert_error(
            $params,
            'Tenant branding is not enabled for this tenant.'
        );
    }

    public function test_same_tenant_branding_enabled(): void {
        [$tenant1, ] = self::create_tenants();
        [$user, $params] = self::create_test_data($tenant1);
        self::assign_appearance_role($user, $tenant1->id);
        self::enable_tenant_branding($tenant1->id);

        $params['tenant_id'] = $tenant1->id;
        $this->assert_success($params);
    }

    public function test_tenants_with_system_permission(): void {
        [$user, $params] = self::create_test_data();
        [$tenant1, $tenant2] = self::create_tenants();
        self::assign_appearance_role($user);
        self::enable_tenant_branding($tenant1->id);
        self::enable_tenant_branding($tenant2->id);

        $params['tenant_id'] = $tenant1->id;
        $this->assert_success($params);

        $params['tenant_id'] = $tenant2->id;
        $this->assert_success($params);
    }

    public function test_system_with_tenant_permission(): void {
        [$user, $params] = self::create_test_data();
        [$tenant1, ] = self::create_tenants();
        self::assign_appearance_role($user, $tenant1->id);

        $this->assert_error(
            $params,
            'Sorry, but you do not currently have permissions to do that (Manage theme settings)'
        );
    }

    public function test_mutation_through_full_graphql_stack(): void {
        [$user, $params] = self::create_test_data();
        self::assign_appearance_role($user);
        $result = $this->execute_graphql_operation(self::MUTATION, $params);
        self::assertEmpty($result->errors);
        self::assertNotEmpty($result->data);
    }

    /**
     * @param array $params
     * @param string $expected_error_message
     */
    private function assert_error(array $params, string $expected_error_message): void {
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $params);
            self::fail('Expected an exception with message: ' . $expected_error_message);
        } catch (Exception $e) {
            self::assertStringContainsString(
                $expected_error_message,
                $e->getMessage()
            );
        }
    }

    /**
     * @param array $params
     */
    private function assert_success(array $params): void {
        $result = $this->resolve_graphql_mutation(self::MUTATION, $params);
        self::assertNotEmpty($result['categories']);
        self::assertNotEmpty($result['flavours']);
        self::assertNotEmpty($result['files']);
        // We don't check result data any further here. That's already tested in core_theme_settings_testcase.
    }

    /**
     * @return array
     */
    private static function create_tenants(): array {
        $generator = self::getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();

        return [$tenant1, $tenant2];
    }

    /**
     * Enable tenant branding for given tenant_id.
     *
     * @param int $tenant_id
     */
    private static function enable_tenant_branding(int $tenant_id): void {
        $theme_config = \theme_config::load(self::THEME);
        $theme_settings = new theme_settings($theme_config, $tenant_id);
        $theme_settings->update_categories([
            [
                'name' => 'tenant',
                'properties' => [
                    [
                        'name' => 'formtenant_field_tenant',
                        'type' => 'boolean',
                        'value' => 'true'
                    ]
                ]
            ]
        ]);
    }

    /**
     * @param null $tenant
     * @return array
     */
    private static function create_test_data($tenant = null): array {
        $generator = self::getDataGenerator();
        if (!$tenant) {
            $user = $generator->create_user();
        } else {
            $user = $generator->create_user(
                ['tenantid' => $tenant->id, 'tenantdomainmanager' => $tenant->idnumber]
            );
        }
        self::setUser($user);

        $params = [
            'theme' => self::THEME,
            'categories' => [
                [
                    'name' => 'colours',
                    'properties' => [
                        [
                            'name' => 'test_name',
                            'type' => 'value',
                            'value' => 'yellow_test'
                        ]
                    ]
                ]
            ],
            'files' => []
        ];

        return [$user, $params];
    }

    /**
     * Assign totara/core:appearance capability in system or tenant context to given user.
     *
     * @param stdClass $user
     */
    private static function assign_appearance_role(stdClass $user, int $tenant_id = null): void {
        $context = $tenant_id ? context_tenant::instance($tenant_id) : context_system::instance();
        $appearance_role = self::getDataGenerator()->create_role();
        assign_capability('totara/core:appearance', CAP_ALLOW, $appearance_role, $context);
        assign_capability('totara/tui:themesettings', CAP_ALLOW, $appearance_role, $context);
        self::getDataGenerator()->role_assign($appearance_role, $user->id);
    }
}
