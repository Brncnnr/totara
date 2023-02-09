<?php
/**
 * This file is part of Totara Learn
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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_api
 */

use core_phpunit\testcase;
use totara_api\controllers\documentation;
use core\testing\generator;
use totara_api\views\documentation_view;
use totara_core\advanced_feature;

/**
 * @group totara_api
 */
class totara_api_documentation_testcase extends testcase {

    /** @var generator */
    protected $generator;

    /** @var generator */
    protected $tenant_generator;

    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->generator = self::getDataGenerator();
        $this->tenant_generator = $this->generator->get_plugin_generator('totara_tenant');
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->tenant_generator = null;
        $this->generator = null;
        parent::tearDown();
    }

    /**
     * @return void
     */
    public function test_documentation_view(): void {

        // Skip this test if generated docs are not present.
        if (empty(documentation_view::built_asset_files())) {
            $this->markTestSkipped('Enterprise documentation is not built.');
        }

        $this->setAdminUser();

        ob_start();
        (new documentation())->process();
        $result = ob_get_clean();

        // Confirm that documentation is found.
        $this->assertStringNotContainsString(
            get_string('error_documentation_not_found', 'totara_api'),
            $result
        );

        // Confirm that schema did not change.
        $this->assertStringNotContainsString(
            get_string('error_documentation_schema_changed', 'totara_api'),
            $result
        );

        // No documentation parse error.
        $this->assertStringNotContainsString(
            get_string('error_documentation_parse_error', 'totara_api'),
            $result
        );

        // Confirm that the spectaql shadow container is present.
        $this->assertStringContainsString('<div id="spectaql-shadow-container"></div>', $result);
    }

    /**
     * @return void
     */
    public function test_controller_no_access(): void {
        $this->setUser($this->generator->create_user());

        try {
            (new documentation())->process();
            $this->fail("Expecting access to be denied");
        } catch (moodle_exception $e) {
            $this->assertEquals('Access denied', $e->getMessage());
        }
    }

    /**
     * @return void
     */
    public function test_controller_with_access(): void {
        // Create user.
        $user = $this->generator->create_user();
        $this->setUser($user);

        // Grant user capabilities.
        $this->assign_capabilities($user, context_system::instance());

        // Request page and confirm that the spectaql shadow container is present.
        ob_start();
        (new documentation())->process();
        $result = ob_get_clean();
        $this->assertStringContainsString('<div id="spectaql-shadow-container"></div>', $result);
    }

    /**
     * @return void
     */
    public function test_controller_with_api_disabled(): void {
        advanced_feature::disable('api');
        $this->setAdminUser();

        try {
            (new documentation())->process();
            $this->fail("Expecting API documentation to be disabled and not accessible");
        } catch (moodle_exception $e) {
            $this->assertEquals('Feature api is not available.', $e->getMessage());
        }
    }

    /**
     * @return void
     */
    public function test_controller_multi_tenancy(): void {
        global $PAGE;

        // Create tenant.
        $this->tenant_generator->enable_tenants();
        $tenant1 = $this->tenant_generator->create_tenant();
        $tenant2 = $this->tenant_generator->create_tenant();

        // Create users.
        $user1 = $this->generator->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->generator->create_user(['tenantid' => $tenant2->id]);
        $user3 = $this->generator->create_user();

        // Grant user capabilities to course category context of tenancy.
        $this->assign_capabilities($user1, context_coursecat::instance($tenant1->categoryid));
        $this->assign_capabilities($user2, context_coursecat::instance($tenant2->categoryid));

        // Access tenant1 documentation as tenant1 user.
        $this->setUser($user1);
        $_GET['tenant_id'] = $tenant1->id;
        admin_get_root(true);
        ob_start();
        (new documentation())->process();
        $result = ob_get_clean();
        $this->assertStringContainsString('<div id="spectaql-shadow-container"></div>', $result);

        // Access tenant1 documentation as tenant2 user.
        $this->setUser($user2);
        $_GET['tenant_id'] = $tenant1->id;
        admin_get_root(true);
        try {
            (new documentation())->process();
            $this->fail("Expecting access to be denied");
        } catch (moodle_exception $e) {
            $this->assertEquals('Access denied', $e->getMessage());
        }

        // Access tenant2 documentation as tenant2 user.
        $this->setUser($user2);
        $PAGE = new moodle_page();
        $_GET['tenant_id'] = $tenant2->id;
        admin_get_root(true);
        ob_start();
        (new documentation())->process();
        $result = ob_get_clean();
        $this->assertStringContainsString('<div id="spectaql-shadow-container"></div>', $result);

        // Access tenant documentation as a normal user.
        $this->setUser($user3);
        $_GET['tenant_id'] = $tenant2->id;
        admin_get_root(true);
        try {
            (new documentation())->process();
            $this->fail("Expecting access to be denied");
        } catch (moodle_exception $e) {
            $this->assertEquals('Access denied', $e->getMessage());
        }

        // Grant user access to documentation.
        $this->assign_capabilities($user3, context_system::instance());

        // Access tenant documentation as a normal user with capabilities within system context.
        $this->setUser($user3);
        $_GET['tenant_id'] = $tenant2->id;
        admin_get_root(true);
        ob_start();
        (new documentation())->process();
        $result = ob_get_clean();
        $this->assertStringContainsString('<div id="spectaql-shadow-container"></div>', $result);
    }

    /**
     * @param $user
     * @param context $context
     *
     * @return void
     */
    private function assign_capabilities($user, context $context): void {
        $role = $this->getDataGenerator()->create_role();
        assign_capability('totara/api:viewdocumentation', CAP_ALLOW, $role, $context);
        role_assign($role, $user->id, $context);
    }

}