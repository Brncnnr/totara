<?php
/*
 *  This file is part of Totara TXP
 *
 *  Copyright (C) 2022 onwards Totara Learning Solutions LTD
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @package totara_api
 *  @author Simon Coggins <simon.coggins@totaralearning.com>
 *
 */

use core_container\container_category_helper;
use core_phpunit\testcase;
use totara_core\advanced_feature;
use totara_core\feature_not_available_exception;

/**
 * @group totara_api
 */
class manage_clients_controller_testcase extends testcase {

    /** @var  \core\testing\generator $data_generator */
    protected $generator;

    /** @var \totara_tenant\testing\generator $tenant_generator */
    protected $tenant_generator;

    protected function setUp(): void {
        parent::setup();

        $this->generator = self::getDataGenerator();
        $this->tenant_generator = $this->generator->get_plugin_generator('totara_tenant');
    }

    protected function tearDown(): void {
        $this->generator = null;
        $this->tenant_generator = null;

        parent::tearDown();
    }

    public function test_api_disabled(): void {
        advanced_feature::disable('api');
        $this->setAdminUser();
        admin_get_root(true); // Load admin tree.

        $controller = new totara_api\controllers\client\manage_clients();

        self::expectException(feature_not_available_exception::class);
        self::expectExceptionMessage('Feature api is not available');
        $controller->process();
    }

    public function test_manage_clients_context(): void {
        $this->tenant_generator->enable_tenants();
        advanced_feature::enable('api');
        $this->setAdminUser();
        admin_get_root(true); // Fix random errors depending on test order.

        $tenant1 = $this->tenant_generator->create_tenant();
        $this->tenant_generator->create_tenant();

        // No tenant_id set
        $controller = new totara_api\controllers\client\manage_clients();

        ob_start();
        $controller->process();
        ob_get_clean();

        // Controller should be using system context.
        self::assertSame(context_system::instance()->id, $controller->get_context()->id);

        // Re-run with tenant_id param set.
        $_GET['tenant_id'] = $tenant1->id;
        $controller = new totara_api\controllers\client\manage_clients();
        ob_start();
        $controller->process();
        ob_get_clean();

        self::assertSame(context_coursecat::instance($tenant1->categoryid)->id, $controller->get_context()->id);

    }

    public function test_manage_clients_system_page_access_success(): void {
        advanced_feature::enable('api');
        admin_get_root(true); // Fix random errors depending on test order.

        // Create role with this capability only
        $sys_context = context_system::instance();
        $roleid = $this->generator->create_role();
        assign_capability('totara/api:manageclients', CAP_ALLOW, $roleid, $sys_context);

        // Create user with role in system context:
        $system_role = $this->generator->create_user();
        role_assign($roleid, $system_role->id, $sys_context);

        // User with system role can see the page.
        $this->setUser($system_role);
        $controller = new totara_api\controllers\client\manage_clients();
        ob_start();
        $controller->process();
        $output = ob_get_clean();
        self::assertStringContainsString(get_string('clients', 'totara_api'), $output);
    }

    public function test_manage_clients_system_page_access_denied_wrong_context(): void {
        advanced_feature::enable('api');
        admin_get_root(true); // Fix random errors depending on test order.

        // Create role with this capability only
        $sys_context = context_system::instance();
        $roleid = $this->generator->create_role();
        assign_capability('totara/api:manageclients', CAP_ALLOW, $roleid, $sys_context);

        // Create user with role in lower context:
        $category_role = $this->generator->create_user();
        $cat_context = context_coursecat::instance(container_category_helper::get_default_category_id('container_course'));
        role_assign($roleid, $category_role->id, $cat_context);

        // User with role in other context cannot.
        $this->setUser($category_role);
        $controller = new totara_api\controllers\client\manage_clients();
        self::expectException(moodle_exception::class);
        self::expectExceptionMessage('Access denied');
        $controller->process();
    }

    public function test_manage_clients_system_page_access_denied_no_role(): void {
        advanced_feature::enable('api');
        admin_get_root(true); // Fix random errors depending on test order.

        // Create user with no role:
        $no_role = $this->generator->create_user();

        // User with no role cannot access page.
        $this->setUser($no_role);
        $controller = new totara_api\controllers\client\manage_clients();
        self::expectException(moodle_exception::class);
        self::expectExceptionMessage('Access denied');
        $controller->process();
    }

    public function test_manage_clients_tenant_page_success(): void {
        advanced_feature::enable('api');
        $this->tenant_generator->enable_tenants();

        $tenant_one = $this->tenant_generator->create_tenant();
        $tenant_two = $this->tenant_generator->create_tenant();

        // Set param for URL
        $_GET['tenant_id'] = $tenant_one->id;

        $user_one = $this->generator->create_user();

        $this->tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $user_one->tenantid = $tenant_one->id;

        // Create role with this capability only
        $sys_context = context_system::instance();
        $roleid = $this->generator->create_role();
        assign_capability('totara/api:manageclients', CAP_ALLOW, $roleid, $sys_context);

        $cat_context = context_coursecat::instance($tenant_one->categoryid);
        $this->generator->role_assign($roleid, $user_one->id, $cat_context->id);

        $this->setUser($user_one);
        admin_get_root(true); // Load admin tree for this user.

        $controller = new totara_api\controllers\client\manage_clients();
        ob_start();
        $controller->process();
        $output = ob_get_clean();

        self::assertStringContainsString(get_string('clients', 'totara_api'), $output);
    }

    public function test_manage_clients_tenant_page_failure_wrong_tenant(): void {
        advanced_feature::enable('api');
        $this->tenant_generator->enable_tenants();

        $tenant_one = $this->tenant_generator->create_tenant();
        $tenant_two = $this->tenant_generator->create_tenant();

        // Set param for URL to tenant 2 (the wrong one for this user).
        $_GET['tenant_id'] = $tenant_two->id;

        $user_one = $this->generator->create_user();

        $this->tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $user_one->tenantid = $tenant_one->id;

        // Create role with this capability only
        $sys_context = context_system::instance();
        $roleid = $this->generator->create_role();
        assign_capability('totara/api:manageclients', CAP_ALLOW, $roleid, $sys_context);

        $cat_context = context_coursecat::instance($tenant_two->categoryid);
        $this->generator->role_assign($roleid, $user_one->id, $cat_context->id);

        $this->setUser($user_one);
        admin_get_root(true); // Load admin tree for this user.

        $controller = new totara_api\controllers\client\manage_clients();
        self::expectException(moodle_exception::class);
        self::expectExceptionMessage('Access denied');
        $controller->process();
    }

    public function test_manage_clients_tenant_page_failure_no_role(): void {
        advanced_feature::enable('api');
        $this->tenant_generator->enable_tenants();

        $tenant_one = $this->tenant_generator->create_tenant();
        $tenant_two = $this->tenant_generator->create_tenant();

        // Set param for URL
        $_GET['tenant_id'] = $tenant_one->id;

        $user_one = $this->generator->create_user();

        $this->tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        // User is in the right tenant, but no role given.
        $user_one->tenantid = $tenant_one->id;

        $this->setUser($user_one);
        admin_get_root(true); // Load admin tree for this user.

        $controller = new totara_api\controllers\client\manage_clients();
        self::expectException(moodle_exception::class);
        self::expectExceptionMessage('Access denied');
        $controller->process();
    }
}