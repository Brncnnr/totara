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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_api
 */

use core\entity\tenant;
use core_phpunit\testcase;
use totara_api\model\client;
use totara_api\testing\generator;
use totara_api\model\helpers\client_capability_helper;

/**
 * @group totara_api
 */
class totara_api_client_capability_helper_testcase extends testcase {

    /** @var \core\testing\generator */
    protected $generator;

    /** @var \totara_tenant\testing\generator */
    protected $tenant_generator;

    /**
     * @return generator
     */
    protected function generator(): generator {
        return generator::instance();
    }

    protected function setUp(): void {
        parent::setUp();

        $this->generator = self::getDataGenerator();
        $this->tenant_generator = $this->generator->get_plugin_generator('totara_tenant');
    }

    protected function tearDown(): void {
        $this->tenant_generator = null;
        $this->generator = null;

        parent::tearDown();
    }

    /**
     * @return void
     */
    public function test_client_capability_helper_for_client(): void {
        self::setAdminUser();
        $user = self::getDataGenerator()->create_user();

        $client = client::create('test', $user->id);

        $client_helper = client_capability_helper::for_client($client);
        self::assertTrue($client_helper->can_manage(true));

        self::setUser($user);
        $client_helper = client_capability_helper::for_client($client);
        self::expectException(required_capability_exception::class);
        self::expectExceptionMessage('Sorry, but you do not currently have permissions to do that (Manage API clients)');
        $client_helper->can_manage(true);
    }

    /**
     * @return void
     */
    public function test_client_capability_helper_for_tenant(): void {
        $generator = self::getDataGenerator();
        /** @var \totara_tenant\testing\generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();
        $tenant = $tenant_generator->create_tenant();

        $generator->create_user();
        $user1 = $generator->create_user(['tenantid' => $tenant->id, 'tenantdomainmanager' => $tenant->idnumber]);

        self::setUser($user1);
        $tenant_entity = new tenant($tenant->id);

        $client_helper = client_capability_helper::for_tenant($tenant_entity);
        self::assertTrue($client_helper->can_manage(true));

        $user2 = $generator->create_user();
        self::setUser($user2);

        $client_helper = client_capability_helper::for_tenant($tenant_entity);
        self::expectException(required_capability_exception::class);
        self::expectExceptionMessage('Sorry, but you do not currently have permissions to do that (Manage API clients)');
        $client_helper->can_manage(true);
    }
}