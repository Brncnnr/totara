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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_api
 */

use core\entity\user;
use core_container\container_category_helper;
use core_phpunit\testcase;
use totara_api\controllers\client\edit_settings;
use totara_api\model\client;
use totara_core\advanced_feature;
use totara_core\feature_not_available_exception;

/**
 * @group totara_api
 */
class totara_api_edit_settings_controller_testcase extends testcase {
    /** @var  \core\testing\generator $data_generator */
    protected $generator;

    /** @var \totara_tenant\testing\generator $tenant_generator */
    protected $tenant_generator;

    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setup();

        $this->generator = self::getDataGenerator();
        $this->tenant_generator = $this->generator->get_plugin_generator('totara_tenant');
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->generator = null;
        $this->tenant_generator = null;

        parent::tearDown();
    }

    /**
     * @return void
     */
    public function test_api_disabled(): void {
        advanced_feature::disable('api');
        $this->setAdminUser();
        $user = $this->generator->create_user();

        $client = client::create('123', $user->id);
        $_GET['client_id'] = $client->id;
        $controller = new edit_settings();
        admin_get_root(true); // Load admin tree.

        self::expectException(feature_not_available_exception::class);
        self::expectExceptionMessage('Feature api is not available');
        $controller->process();
    }

    /**
     * @return void
     */
    public function test_edit_settings_context(): void {
        $this->tenant_generator->enable_tenants();
        advanced_feature::enable('api');
        $this->setAdminUser();
        $user = $this->generator->create_user();

        $tenant1 = $this->tenant_generator->create_tenant();
        $this->tenant_generator->create_tenant();

        $client = client::create('1212', $user->id);
        $_GET['client_id'] = $client->id;
        $controller = new edit_settings();
        admin_get_root(true);
        ob_start();
        $controller->process();
        ob_get_clean();

        self::assertSame(context_system::instance()->id, $controller->get_context()->id);

        $user_two = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);

        $client2 = client::create('a22', $user_two->id,'', $tenant1->id);
        $_GET['client_id'] = $client2->id;
        $controller = new edit_settings();
        admin_get_root(true);
        ob_start();
        $controller->process();
        ob_get_clean();

        self::assertSame(context_coursecat::instance($tenant1->categoryid)->id, $controller->get_context()->id);
    }

    /**
     * @return void
     */
    public function test_edit_settings_system_page_access_success(): void {
        advanced_feature::enable('api');

        $sys_context = context_system::instance();
        $roleid = $this->generator->create_role();
        assign_capability('totara/api:manageclients', CAP_ALLOW, $roleid, $sys_context);
        assign_capability('totara/api:managesettings', CAP_ALLOW, $roleid, $sys_context);

        $system_role = $this->generator->create_user();
        role_assign($roleid, $system_role->id, $sys_context);

        $this->setUser($system_role);
        $client = client::create('123132', $system_role->id);
        $_GET['client_id'] = $client->id;
        $controller = new edit_settings();
        admin_get_root(true);
        ob_start();
        $controller->process();
        $output = ob_get_clean();
        self::assertStringContainsString(get_string('clients', 'totara_api'), $output);
    }

    /**
     * @return void
     */
    public function test_edit_settings_system_page_access_denied_wrong_context(): void {
        advanced_feature::enable('api');

        $sys_context = context_system::instance();
        $roleid = $this->generator->create_role();
        assign_capability('totara/api:managesettings', CAP_ALLOW, $roleid, $sys_context);

        $category_role = $this->generator->create_user();
        $cat_context = context_coursecat::instance(container_category_helper::get_default_category_id('container_course'));
        role_assign($roleid, $category_role->id, $cat_context);

        $client = client::create('1211', $category_role->id);
        $_GET['client_id'] = $client->id;

        $this->setUser($category_role);
        $controller = new edit_settings();
        admin_get_root(true);
        self::expectException(moodle_exception::class);
        self::expectExceptionMessage('Access denied');
        $controller->process();
    }

    /**
     * @return void
     */
    public function test_edit_settings_system_page_access_denied_no_role(): void {
        advanced_feature::enable('api');

        $no_role = $this->generator->create_user();

        $this->setUser($no_role);

        $client = client::create('123231', $no_role->id);
        $_GET['client_id'] = $client->id;

        $controller = new edit_settings();
        admin_get_root(true);
        self::expectException(moodle_exception::class);
        self::expectExceptionMessage('Access denied');
        $controller->process();
    }

    /**
     * @return void
     */
    public function test_edit_settings_tenant_page_success(): void {
        advanced_feature::enable('api');
        $this->tenant_generator->enable_tenants();

        $tenant_one = $this->tenant_generator->create_tenant();

        $_GET['client_id'] = 1;
        $user_one = $this->generator->create_user();

        $this->tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $user_one->tenantid = $tenant_one->id;

        $sys_context = context_system::instance();
        $roleid = $this->generator->create_role();
        assign_capability('totara/api:manageclients', CAP_ALLOW, $roleid, $sys_context);
        assign_capability('totara/api:managesettings', CAP_ALLOW, $roleid, $sys_context);

        $cat_context = context_coursecat::instance($tenant_one->categoryid);
        $this->generator->role_assign($roleid, $user_one->id, $cat_context->id);

        $this->setUser($user_one);

        $client = client::create('123123', $user_one->id,'', $tenant_one->id);
        $_GET['client_id'] = $client->id;

        $controller = new edit_settings();
        admin_get_root(true);
        ob_start();
        $controller->process();
        $output = ob_get_clean();

        self::assertStringContainsString(get_string('clients', 'totara_api'), $output);
    }

    /**
     * @return void
     */
    public function test_edit_settings_tenant_page_failure_wrong_tenant(): void {
        advanced_feature::enable('api');
        $this->tenant_generator->enable_tenants();

        $tenant_one = $this->tenant_generator->create_tenant();
        $tenant_two = $this->tenant_generator->create_tenant();

        $user_one = $this->generator->create_user();

        $this->tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $user_one->tenantid = $tenant_one->id;

        // Create role with this capability only
        $sys_context = context_system::instance();
        $roleid = $this->generator->create_role();
        assign_capability('totara/api:managesettings', CAP_ALLOW, $roleid, $sys_context);

        $cat_context = context_coursecat::instance($tenant_two->categoryid);
        $this->generator->role_assign($roleid, $user_one->id, $cat_context->id);

        $this->setUser($user_one);

        $user_two = $this->getDataGenerator()->create_user(['tenantid' => $tenant_two->id]);


        $client = client::create('1212', $user_two->id,'', $tenant_two->id);
        $_GET['client_id'] = $client->id;

        $controller = new edit_settings();
        admin_get_root(true); // Load admin tree for this user.
        self::expectException(moodle_exception::class);
        self::expectExceptionMessage('Access denied');
        $controller->process();
    }

    /**
     * @return void
     */
    public function test_edit_settings_tenant_page_failure_no_role(): void {
        advanced_feature::enable('api');
        $this->tenant_generator->enable_tenants();

        $tenant_one = $this->tenant_generator->create_tenant();

        // Set param for URL
        $_GET['tenant_id'] = $tenant_one->id;

        $user_one = $this->generator->create_user();

        $this->tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        // User is in the right tenant, but no role given.
        $user_one->tenantid = $tenant_one->id;

        $this->setUser($user_one);

        $user_two = $this->getDataGenerator()->create_user(['tenantid' => $tenant_one->id]);

        $client = client::create('123', $user_two->id,'', $tenant_one->id);
        $_GET['client_id'] = $client->id;

        $controller = new edit_settings();
        admin_get_root(true); // Load admin tree for this user.
        self::expectException(moodle_exception::class);
        self::expectExceptionMessage('Access denied');
        $controller->process();
    }
}