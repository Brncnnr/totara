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
 * @author Angela Kuznetsova <angela.kuznetsova@totara.com>
 * @package core_user
 */
defined('MOODLE_INTERNAL') || die();

use core\entity\user;
use core\record\tenant;
use core_phpunit\testcase;
use core_user\external\user_interactor;

/**
 * @group core_user
 */
class user_interactor_testcase extends testcase {
    /**
     * @var user|null;
     */
    private $tenant_one_user;

    /**
     * @var user|null;
     */
    private $tenant_two_user;

    /**
     * @var user|null;
     */
    private $system_user;

    /**
     * @var user|null;
     */
    private $system_api_user;

    /**
     * @var user|null;
     */
    private $tenant_one_api_user;

    /**
     * @var tenant|null;
     */
    private $tenant_one;

    /**
     * @var tenant|null;
     */
    private $tenant_two;

    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = self::getDataGenerator();
        $tenant_generator = $this->get_tenant_generator();

        $tenant_one = $tenant_generator->create_tenant();
        $this->tenant_one = $tenant_one;
        $tenant_two = $tenant_generator->create_tenant();
        $this->tenant_two = $tenant_two;

        $tenant_user1 = $generator->create_user([
            'firstname' => uniqid('tenant_one_user_'),
            'lastname' => uniqid('tenant_one_user_')
        ]);

        $this->tenant_one_user = new user($tenant_user1->id);
        $this->get_tenant_generator()->migrate_user_to_tenant($tenant_user1->id, $tenant_one->id);

        $tenant_user2 = $generator->create_user([
            'firstname' => uniqid('tenant_two_user_'),
            'lastname' => uniqid('tenant_two_user_')
        ]);

        $this->tenant_two_user = new user($tenant_user2->id);
        $this->get_tenant_generator()->migrate_user_to_tenant($tenant_user2->id, $tenant_two->id);

        $tenant_api = $generator->create_user([
            'firstname' => uniqid('tenant_one_api_'),
            'lastname' => uniqid('tenant_one_api_')
        ]);

        $this->tenant_one_api_user = new user($tenant_api->id);
        $this->get_tenant_generator()->migrate_user_to_tenant($tenant_api->id, $tenant_one->id);

        $api = $generator->create_user([
            'firstname' => uniqid('api_user_'),
            'lastname' => uniqid('api_user_')
        ]);

        $this->system_api_user = new user($api->id);

        $system = $generator->create_user([
            'firstname' => uniqid('system_user_'),
            'lastname' => uniqid('system_user_')
        ]);

        $this->system_user = new user($system->id);
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->tenant_one_user = null;
        $this->tenant_two_user = null;
        $this->tenant_one_api_user = null;
        $this->system_api_user = null;
        $this->tenant_one = null;
        $this->tenant_two = null;
        $this->system_user = null;
        parent::tearDown();
    }

    /**
     * @return \totara_tenant\testing\generator
     */
    private function get_tenant_generator(): \totara_tenant\testing\generator {
        $generator = self::getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        return $tenant_generator;
    }

    /**
     * The actor is a system_user.
     *
     * @return void
     */
    public function test_interactor_system_user(): void {

        $this->setUser($this->system_api_user);
        $roles = get_archetype_roles('apiuser');
        $role = reset($roles);
        role_assign($role->id, $this->system_api_user->id, context_system::instance());

        $interactor = new user_interactor($this->system_api_user->id);

        self::assertTrue($interactor->can_create_system_user());
        self::assertTrue($interactor->can_create_tenant_user($this->tenant_one->id));
        self::assertTrue($interactor->can_create_tenant_user($this->tenant_two->id));
        self::assertTrue($interactor->has_system_user_create_capability());
        self::assertTrue($interactor->has_tenant_user_create_capability($this->tenant_one->id));
        self::assertTrue($interactor->has_tenant_user_create_capability($this->tenant_two->id));
        self::assertTrue($interactor->can_view());
        self::assertTrue($interactor->has_user_viewalldetails_capability_in_tenant_context($this->tenant_one->id));
        self::assertTrue($interactor->has_user_viewalldetails_capability_in_tenant_context($this->tenant_two->id));
        self::assertFalse($interactor->has_user_viewparticipants_capability_in_tenant_context($this->tenant_one->id));
        self::assertFalse($interactor->has_user_viewparticipants_capability_in_tenant_context($this->tenant_two->id));
        self::assertTrue($interactor->has_user_viewalldetails_capability_in_system_context());

        assign_capability('totara/tenant:manageparticipants', CAP_ALLOW, $role->id, context_system::instance());
        role_assign($role->id, $this->system_api_user->id, context_system::instance());

        $interactor = new user_interactor($this->system_api_user->id, $this->system_user->id);
        self::assertTrue($interactor->can_update_user());
        self::assertTrue($interactor->has_user_update_capability());
        self::assertTrue($interactor->has_user_editprofile_capability());
        self::assertTrue($interactor->has_user_managelogin_capability());
        self::assertTrue($interactor->has_tenancy_update_capability());
        self::assertTrue($interactor->can_delete_user());
        self::assertTrue($interactor->has_user_delete_capability());

        $interactor = new user_interactor($this->system_api_user->id, $this->tenant_two_user->id);
        self::assertTrue($interactor->can_update_user());
        self::assertTrue($interactor->has_user_update_capability());
        self::assertTrue($interactor->has_user_editprofile_capability());
        self::assertTrue($interactor->has_user_managelogin_capability());
        self::assertTrue($interactor->has_tenancy_update_capability());
        self::assertTrue($interactor->can_delete_user());
        self::assertTrue($interactor->has_user_delete_capability());
    }

    /**
     * The actor is a tenant_user.
     *
     * @return void
     */
    public function test_interactor_tenant_user(): void {

        $this->setUser($this->tenant_one_api_user->id);
        $roles = get_archetype_roles('apiuser');
        $role = reset($roles);
        role_assign($role->id, $this->tenant_one_api_user->id, context_tenant::instance($this->tenant_one->id));

        $interactor = new user_interactor($this->tenant_one_api_user->id);

        self::assertFalse($interactor->can_create_system_user());
        self::assertTrue($interactor->can_create_tenant_user($this->tenant_one->id));
        self::assertFalse($interactor->can_create_tenant_user($this->tenant_two->id));
        self::assertFalse($interactor->has_system_user_create_capability());
        self::assertTrue($interactor->has_tenant_user_create_capability($this->tenant_one->id));
        self::assertFalse($interactor->has_tenant_user_create_capability($this->tenant_two->id));
        self::assertTrue($interactor->can_view());
        self::assertTrue($interactor->has_user_viewalldetails_capability_in_tenant_context($this->tenant_one->id));
        self::assertFalse($interactor->has_user_viewalldetails_capability_in_tenant_context($this->tenant_two->id));
        self::assertFalse($interactor->has_user_viewparticipants_capability_in_tenant_context($this->tenant_one->id));
        self::assertFalse($interactor->has_user_viewparticipants_capability_in_tenant_context($this->tenant_two->id));
        self::assertFalse($interactor->has_user_viewalldetails_capability_in_system_context());

        // Tenant API User can update only user from own tenancy
        $interactor = new user_interactor($this->tenant_one_api_user->id, $this->tenant_one_user->id);
        self::assertTrue($interactor->can_update_user());
        self::assertFalse($interactor->has_user_update_capability());
        self::assertTrue($interactor->has_user_editprofile_capability());
        self::assertTrue($interactor->has_user_managelogin_capability());
        self::assertFalse($interactor->has_tenancy_update_capability());
        self::assertTrue($interactor->can_delete_user());
        self::assertTrue($interactor->has_user_delete_capability());

        $interactor = new user_interactor($this->tenant_one_api_user->id, $this->system_user->id);
        self::assertFalse($interactor->can_update_user());
        self::assertFalse($interactor->has_user_update_capability());
        self::assertFalse($interactor->has_user_editprofile_capability());
        self::assertFalse($interactor->has_user_managelogin_capability());
        self::assertFalse($interactor->has_tenancy_update_capability());
        self::assertFalse($interactor->can_delete_user());
        self::assertFalse($interactor->has_user_delete_capability());

        $interactor = new user_interactor($this->tenant_one_api_user->id, $this->tenant_two_user->id);
        self::assertFalse($interactor->can_update_user());
        self::assertFalse($interactor->has_user_editprofile_capability());
        self::assertFalse($interactor->has_user_managelogin_capability());
        self::assertFalse($interactor->has_tenancy_update_capability());
        self::assertFalse($interactor->can_delete_user());
        self::assertFalse($interactor->has_user_delete_capability());
    }

    /**
     * The actor is a tenant_user with system capabilities.
     *
     * @return void
     */
    public function test_interactor_tenant_user_with_system_caps(): void {

        $this->setUser($this->tenant_one_api_user->id);
        $roles = get_archetype_roles('apiuser');
        $role = reset($roles);
        assign_capability('totara/tenant:manageparticipants', CAP_ALLOW, $role->id, context_system::instance());
        assign_capability('moodle/user:update', CAP_ALLOW, $role->id, context_system::instance());
        role_assign($role->id, $this->tenant_one_api_user->id, context_system::instance());

        $interactor = new user_interactor($this->tenant_one_api_user->id);

        self::assertTrue($interactor->can_create_system_user());
        self::assertTrue($interactor->can_create_tenant_user($this->tenant_one->id));
        self::assertFalse($interactor->can_create_tenant_user($this->tenant_two->id));
        self::assertTrue($interactor->has_system_user_create_capability());
        self::assertTrue($interactor->has_tenant_user_create_capability($this->tenant_one->id));
        self::assertFalse($interactor->has_tenant_user_create_capability($this->tenant_two->id));
        self::assertTrue($interactor->can_view());
        self::assertTrue($interactor->has_user_viewalldetails_capability_in_tenant_context($this->tenant_one->id));
        self::assertFalse($interactor->has_user_viewalldetails_capability_in_tenant_context($this->tenant_two->id));
        self::assertFalse($interactor->has_user_viewparticipants_capability_in_tenant_context($this->tenant_one->id));
        self::assertFalse($interactor->has_user_viewparticipants_capability_in_tenant_context($this->tenant_two->id));
        self::assertTrue($interactor->has_user_viewalldetails_capability_in_system_context());

        // Tenant API User can update all users, but cannot delete users from different tenancy
        $interactor = new user_interactor($this->tenant_one_api_user->id, $this->tenant_one_user->id);
        self::assertTrue($interactor->can_update_user());
        self::assertTrue($interactor->has_user_update_capability());
        self::assertTrue($interactor->has_user_editprofile_capability());
        self::assertTrue($interactor->has_user_managelogin_capability());
        self::assertTrue($interactor->has_tenancy_update_capability());
        self::assertTrue($interactor->can_delete_user());
        self::assertTrue($interactor->has_user_delete_capability());

        $interactor = new user_interactor($this->tenant_one_api_user->id, $this->system_user->id);
        self::assertTrue($interactor->can_update_user());
        self::assertTrue($interactor->has_user_update_capability());
        self::assertTrue($interactor->has_user_editprofile_capability());
        self::assertTrue($interactor->has_user_managelogin_capability());
        self::assertTrue($interactor->has_tenancy_update_capability());
        self::assertTrue($interactor->can_delete_user());
        self::assertTrue($interactor->has_user_delete_capability());

        $interactor = new user_interactor($this->tenant_one_api_user->id, $this->tenant_two_user->id);
        self::assertFalse($interactor->can_update_user());
        self::assertFalse($interactor->has_user_editprofile_capability());
        self::assertFalse($interactor->has_user_managelogin_capability());
        self::assertTrue($interactor->has_tenancy_update_capability());
        self::assertFalse($interactor->can_delete_user());
        self::assertFalse($interactor->has_user_delete_capability());
    }

    /**
     * The actor is a tenant_user with system capabilities with tenantisolation is on
     *
     * @return void
     */
    public function test_interactor_tenant_user_with_system_caps_isolation_on(): void {
        set_config('tenantsisolated', 1);
        $this->setUser($this->tenant_one_api_user->id);
        $roles = get_archetype_roles('apiuser');
        $role = reset($roles);
        assign_capability('totara/tenant:manageparticipants', CAP_ALLOW, $role->id, context_system::instance());
        assign_capability('moodle/user:update', CAP_ALLOW, $role->id, context_system::instance());
        role_assign($role->id, $this->tenant_one_api_user->id, context_system::instance());

        $interactor = new user_interactor($this->tenant_one_api_user->id);

        self::assertFalse($interactor->can_create_system_user());
        self::assertTrue($interactor->can_create_tenant_user($this->tenant_one->id));
        self::assertFalse($interactor->can_create_tenant_user($this->tenant_two->id));
        self::assertFalse($interactor->has_system_user_create_capability());
        self::assertTrue($interactor->has_tenant_user_create_capability($this->tenant_one->id));
        self::assertFalse($interactor->has_tenant_user_create_capability($this->tenant_two->id));
        self::assertTrue($interactor->can_view());
        self::assertTrue($interactor->has_user_viewalldetails_capability_in_tenant_context($this->tenant_one->id));
        self::assertFalse($interactor->has_user_viewalldetails_capability_in_tenant_context($this->tenant_two->id));
        self::assertFalse($interactor->has_user_viewparticipants_capability_in_tenant_context($this->tenant_one->id));
        self::assertFalse($interactor->has_user_viewparticipants_capability_in_tenant_context($this->tenant_two->id));
        self::assertFalse($interactor->has_user_viewalldetails_capability_in_system_context());

        // Tenant API User can update own users if tenantisolation is on
        $interactor = new user_interactor($this->tenant_one_api_user->id, $this->tenant_one_user->id);
        self::assertTrue($interactor->can_update_user());
        self::assertFalse($interactor->has_user_update_capability());
        self::assertTrue($interactor->has_user_editprofile_capability());
        self::assertTrue($interactor->has_user_managelogin_capability());
        // Cannot change tenancy
        self::assertFalse($interactor->has_tenancy_update_capability());
        self::assertTrue($interactor->can_delete_user());
        self::assertTrue($interactor->has_user_delete_capability());

        $interactor = new user_interactor($this->tenant_one_api_user->id, $this->system_user->id);
        self::assertFalse($interactor->can_update_user());
        self::assertFalse($interactor->has_user_update_capability());
        self::assertFalse($interactor->has_user_editprofile_capability());
        self::assertFalse($interactor->has_user_managelogin_capability());
        self::assertFalse($interactor->has_tenancy_update_capability());
        self::assertFalse($interactor->can_delete_user());
        self::assertFalse($interactor->has_user_delete_capability());

        $interactor = new user_interactor($this->tenant_one_api_user->id, $this->tenant_two_user->id);
        self::assertFalse($interactor->can_update_user());
        self::assertFalse($interactor->has_user_editprofile_capability());
        self::assertFalse($interactor->has_user_managelogin_capability());
        self::assertFalse($interactor->has_tenancy_update_capability());
        self::assertFalse($interactor->can_delete_user());
        self::assertFalse($interactor->has_user_delete_capability());
    }

    /**
     * can_create_system_user() test
     *
     * @return void
     */
    public function test_can_create_system_user(): void {
        $interactor = new user_interactor($this->system_user->id);
        self::assertFalse($interactor->can_create_system_user());
        self::assertFalse($interactor->has_system_user_create_capability());

        $role_id = self::getDataGenerator()->create_role();
        assign_capability('moodle/user:create', CAP_ALLOW, $role_id, context_system::instance()->id);
        role_assign($role_id, $this->system_user->id, context_system::instance());
        self::assertTrue($interactor->can_create_system_user());
        self::assertTrue($interactor->has_system_user_create_capability());

        $interactor = new user_interactor($this->tenant_one_user->id);
        self::assertFalse($interactor->can_create_system_user());
        self::assertFalse($interactor->has_system_user_create_capability());

        role_assign($role_id, $this->tenant_one_user->id, context_system::instance());
        self::assertTrue($interactor->can_create_system_user());
        self::assertTrue($interactor->has_system_user_create_capability());
    }

    /**
     * can_create_tenant_user() test
     *
     * @return void
     */
    public function test_can_create_tenant_user(): void {
        $interactor = new user_interactor($this->system_user->id);
        self::assertFalse($interactor->can_create_tenant_user($this->tenant_one->id));
        self::assertFalse($interactor->has_system_user_create_capability());
        self::assertFalse($interactor->has_tenant_user_create_capability($this->tenant_one->id));

        $role_id = self::getDataGenerator()->create_role();
        assign_capability('moodle/user:create', CAP_ALLOW, $role_id, context_system::instance()->id);
        role_assign($role_id, $this->system_user->id, context_system::instance());
        self::assertTrue($interactor->can_create_tenant_user($this->tenant_one->id));
        self::assertTrue($interactor->has_system_user_create_capability());
        self::assertFalse($interactor->has_tenant_user_create_capability($this->tenant_one->id));

        $interactor = new user_interactor($this->tenant_one_user->id);
        self::assertFalse($interactor->can_create_tenant_user($this->tenant_one->id));
        self::assertFalse($interactor->has_system_user_create_capability());
        self::assertFalse($interactor->has_tenant_user_create_capability($this->tenant_one->id));

        $role_id = self::getDataGenerator()->create_role();
        assign_capability('totara/tenant:usercreate', CAP_ALLOW, $role_id, context_tenant::instance($this->tenant_one->id));
        role_assign($role_id, $this->tenant_one_user->id, context_tenant::instance($this->tenant_one->id));
        self::assertTrue($interactor->can_create_tenant_user($this->tenant_one->id));
        self::assertFalse($interactor->has_system_user_create_capability());
        self::assertTrue($interactor->has_tenant_user_create_capability($this->tenant_one->id));
    }

    /**
     * can_update_user() test
     *
     * @return void
     */
    public function test_can_update_user(): void {
        $system_user_2 = self::getDataGenerator()->create_user();
        $interactor = new user_interactor($this->system_user->id, $system_user_2->id);
        self::assertFalse($interactor->can_update_user());
        self::assertFalse($interactor->has_user_update_capability());
        self::assertFalse($interactor->has_user_editprofile_capability());

        $role_id = self::getDataGenerator()->create_role();
        assign_capability('moodle/user:update', CAP_ALLOW, $role_id, context_system::instance()->id);
        assign_capability('moodle/user:editprofile', CAP_ALLOW, $role_id, context_user::instance($system_user_2->id)->id);
        role_assign($role_id, $this->system_user->id, context_system::instance());
        self::assertTrue($interactor->can_update_user());
        self::assertTrue($interactor->has_user_update_capability());
        self::assertTrue($interactor->has_user_editprofile_capability());

        $interactor = new user_interactor($this->tenant_one_user->id, $system_user_2->id);
        self::assertFalse($interactor->can_update_user());
        self::assertFalse($interactor->has_user_update_capability());
        self::assertFalse($interactor->has_user_editprofile_capability());

        $role_id = self::getDataGenerator()->create_role();
        assign_capability('moodle/user:update', CAP_ALLOW, $role_id, context_system::instance()->id);
        assign_capability('moodle/user:editprofile', CAP_ALLOW, $role_id, context_user::instance($system_user_2->id)->id);
        role_assign($role_id, $this->tenant_one_user->id, context_system::instance());
        self::assertTrue($interactor->can_update_user());
        self::assertTrue($interactor->has_user_update_capability());
        self::assertTrue($interactor->has_user_editprofile_capability());
    }

    /**
     * can_delete_user() test
     *
     * @return void
     */
    public function test_can_delete_user(): void {
        $system_user_2 = self::getDataGenerator()->create_user();
        $interactor = new user_interactor($this->system_user->id, $system_user_2->id);
        self::assertFalse($interactor->can_delete_user());
        self::assertFalse($interactor->has_user_delete_capability());

        $role_id = self::getDataGenerator()->create_role();
        assign_capability('moodle/user:delete', CAP_ALLOW, $role_id, context_user::instance($system_user_2->id)->id);
        role_assign($role_id, $this->system_user->id, context_user::instance($system_user_2->id)->id);
        self::assertTrue($interactor->can_delete_user());
        self::assertTrue($interactor->has_user_delete_capability());

        $interactor = new user_interactor($this->tenant_one_user->id, $system_user_2->id);
        self::assertFalse($interactor->can_delete_user());
        self::assertFalse($interactor->has_user_delete_capability());

        $role_id = self::getDataGenerator()->create_role();
        assign_capability('moodle/user:delete', CAP_ALLOW, $role_id, context_user::instance($system_user_2->id)->id);
        role_assign($role_id, $this->tenant_one_user->id, context_user::instance($system_user_2->id)->id);
        self::assertTrue($interactor->can_delete_user());
        self::assertTrue($interactor->has_user_delete_capability());
    }

    /**
     * can_view() test
     *
     * @return void
     */
    public function test_can_view(): void {
        $interactor = new user_interactor($this->system_user->id);
        self::assertFalse($interactor->can_view());
        self::assertFalse($interactor->has_user_viewalldetails_capability_in_tenant_context($this->tenant_one->id));
        self::assertFalse($interactor->has_user_viewalldetails_capability_in_tenant_context($this->tenant_two->id));
        self::assertFalse($interactor->has_user_viewparticipants_capability_in_tenant_context($this->tenant_one->id));
        self::assertFalse($interactor->has_user_viewparticipants_capability_in_tenant_context($this->tenant_two->id));
        self::assertFalse($interactor->has_user_viewalldetails_capability_in_system_context());

        $role_id = self::getDataGenerator()->create_role();
        assign_capability('moodle/user:viewalldetails', CAP_ALLOW, $role_id, context_system::instance());
        role_assign($role_id, $this->system_user->id, context_system::instance());
        self::assertTrue($interactor->can_view());
        self::assertTrue($interactor->has_user_viewalldetails_capability_in_tenant_context($this->tenant_one->id));
        self::assertTrue($interactor->has_user_viewalldetails_capability_in_tenant_context($this->tenant_two->id));
        self::assertFalse($interactor->has_user_viewparticipants_capability_in_tenant_context($this->tenant_one->id));
        self::assertFalse($interactor->has_user_viewparticipants_capability_in_tenant_context($this->tenant_two->id));
        self::assertTrue($interactor->has_user_viewalldetails_capability_in_system_context());

        $interactor = new user_interactor($this->tenant_one_user->id);
        self::assertFalse($interactor->can_view());
        self::assertFalse($interactor->has_user_viewalldetails_capability_in_tenant_context($this->tenant_one->id));
        self::assertFalse($interactor->has_user_viewalldetails_capability_in_tenant_context($this->tenant_two->id));
        self::assertFalse($interactor->has_user_viewparticipants_capability_in_tenant_context($this->tenant_one->id));
        self::assertFalse($interactor->has_user_viewparticipants_capability_in_tenant_context($this->tenant_two->id));
        self::assertFalse($interactor->has_user_viewalldetails_capability_in_system_context());

        $role_id = self::getDataGenerator()->create_role();
        assign_capability('totara/tenant:viewparticipants', CAP_ALLOW, $role_id, context_coursecat::instance($this->tenant_one->categoryid));
        role_assign($role_id, $this->tenant_one_user->id, context_coursecat::instance($this->tenant_one->categoryid));
        self::assertTrue($interactor->can_view());
        self::assertFalse($interactor->has_user_viewalldetails_capability_in_tenant_context($this->tenant_one->id));
        self::assertFalse($interactor->has_user_viewalldetails_capability_in_tenant_context($this->tenant_two->id));
        self::assertTrue($interactor->has_user_viewparticipants_capability_in_tenant_context($this->tenant_one->id));
        self::assertFalse($interactor->has_user_viewparticipants_capability_in_tenant_context($this->tenant_two->id));
        self::assertFalse($interactor->has_user_viewalldetails_capability_in_system_context());
    }

    /**
     * has_tenant_user_create_capability() test
     *
     * @return void
     */
    public function test_has_tenant_user_create_capability(): void {
        $interactor = new user_interactor($this->system_user->id);
        self::assertFalse($interactor->has_tenant_user_create_capability($this->tenant_one->id));

        $role_id = self::getDataGenerator()->create_role();
        assign_capability('totara/tenant:usercreate', CAP_ALLOW, $role_id, context_tenant::instance($this->tenant_one->id));
        role_assign($role_id, $this->system_user->id, context_tenant::instance($this->tenant_one->id));
        self::assertTrue($interactor->has_tenant_user_create_capability($this->tenant_one->id));

        $interactor = new user_interactor($this->tenant_one_user->id);
        self::assertFalse($interactor->has_tenant_user_create_capability($this->tenant_one->id));

        role_assign($role_id, $this->tenant_one_user->id, context_tenant::instance($this->tenant_one->id));
        self::assertTrue($interactor->has_tenant_user_create_capability($this->tenant_one->id));
    }

    /**
     * has_system_user_create_capability() test
     *
     * @return void
     */
    public function has_system_user_create_capability(): void {
        $interactor = new user_interactor($this->system_user->id);
        self::assertFalse($interactor->has_system_user_create_capability());

        $role_id = self::getDataGenerator()->create_role();
        assign_capability('moodle/user:create', CAP_ALLOW, $role_id, context_system::instance()->id);
        role_assign($role_id, $this->system_user->id, context_system::instance());
        self::assertTrue($interactor->has_system_user_create_capability());

        $interactor = new user_interactor($this->tenant_one_user->id);
        self::assertFalse($interactor->has_system_user_create_capability());

        role_assign($role_id, $this->tenant_one_user->id, context_system::instance());
        self::assertTrue($interactor->has_system_user_create_capability());
    }

    /**
     * has_user_update_capability() test
     *
     * @return void
     */
    public function test_has_user_update_capability(): void {
        $system_user_2 = self::getDataGenerator()->create_user();
        $interactor = new user_interactor($this->system_user->id, $system_user_2->id);
        self::assertFalse($interactor->has_user_update_capability());

        $role_id = self::getDataGenerator()->create_role();
        assign_capability('moodle/user:update', CAP_ALLOW, $role_id, context_system::instance()->id);
        role_assign($role_id, $this->system_user->id, context_system::instance());
        self::assertTrue($interactor->has_user_update_capability());

        $interactor = new user_interactor($this->tenant_one_user->id, $system_user_2->id);
        self::assertFalse($interactor->has_user_update_capability());

        role_assign($role_id, $this->tenant_one_user->id, context_system::instance());
        self::assertTrue($interactor->has_user_update_capability());
    }

    /**
     * has_user_editprofile_capability() test
     *
     * @return void
     */
    public function test_has_user_editprofile_capability(): void {
        $system_user_2 = self::getDataGenerator()->create_user();
        $interactor = new user_interactor($this->system_user->id, $system_user_2->id);
        self::assertFalse($interactor->has_user_editprofile_capability());

        $role_id = self::getDataGenerator()->create_role();
        assign_capability('moodle/user:editprofile', CAP_ALLOW, $role_id, context_user::instance($system_user_2->id)->id);
        role_assign($role_id, $this->system_user->id, context_user::instance($system_user_2->id)->id);
        self::assertTrue($interactor->has_user_editprofile_capability());

        $interactor = new user_interactor($this->tenant_one_user->id, $system_user_2->id);
        self::assertFalse($interactor->has_user_editprofile_capability());

        role_assign($role_id, $this->tenant_one_user->id, context_user::instance($system_user_2->id)->id);
        self::assertTrue($interactor->has_user_editprofile_capability());
    }

    /**
     * has_user_managelogin_capability() test
     *
     * @return void
     */
    public function test_has_user_managelogin_capability(): void {
        $system_user_2 = self::getDataGenerator()->create_user();
        $interactor = new user_interactor($this->system_user->id, $system_user_2->id);
        self::assertFalse($interactor->has_user_managelogin_capability());

        $role_id = self::getDataGenerator()->create_role();
        assign_capability('moodle/user:managelogin', CAP_ALLOW, $role_id, context_user::instance($system_user_2->id)->id);
        role_assign($role_id, $this->system_user->id, context_user::instance($system_user_2->id)->id);
        self::assertTrue($interactor->has_user_managelogin_capability());

        $interactor = new user_interactor($this->tenant_one_user->id, $system_user_2->id);
        self::assertFalse($interactor->has_user_managelogin_capability());

        role_assign($role_id, $this->tenant_one_user->id, context_user::instance($system_user_2->id)->id);
        self::assertTrue($interactor->has_user_managelogin_capability());
    }

    /**
     * has_tenancy_update_capability() test
     *
     * @return void
     */
    public function test_has_tenancy_update_capability(): void {
        $interactor = new user_interactor($this->system_user->id);
        self::assertFalse($interactor->has_tenancy_update_capability());

        $role_id = self::getDataGenerator()->create_role();
        assign_capability('totara/tenant:manageparticipants', CAP_ALLOW, $role_id, context_system::instance());
        role_assign($role_id, $this->system_user->id, context_system::instance());
        self::assertTrue($interactor->has_tenancy_update_capability());

        $interactor = new user_interactor($this->tenant_one_user->id);
        self::assertFalse($interactor->has_tenancy_update_capability());

        role_assign($role_id, $this->tenant_one_user->id, context_system::instance());
        self::assertTrue($interactor->has_tenancy_update_capability());
    }

    /**
     * has_user_delete_capability() test
     *
     * @return void
     */
    public function test_has_user_delete_capability(): void {
        $system_user_2 = self::getDataGenerator()->create_user();
        $interactor = new user_interactor($this->system_user->id, $system_user_2->id);
        self::assertFalse($interactor->has_user_delete_capability());

        $role_id = self::getDataGenerator()->create_role();
        assign_capability('moodle/user:delete', CAP_ALLOW, $role_id, context_user::instance($system_user_2->id)->id);
        role_assign($role_id, $this->system_user->id, context_user::instance($system_user_2->id)->id);
        self::assertTrue($interactor->has_user_delete_capability());

        $interactor = new user_interactor($this->tenant_one_user->id, $system_user_2->id);
        self::assertFalse($interactor->has_user_delete_capability());

        role_assign($role_id, $this->tenant_one_user->id, context_user::instance($system_user_2->id)->id);
        self::assertTrue($interactor->has_user_delete_capability());
    }

    /**
     * has_user_viewalldetails_capability_in_tenant_context() test
     *
     * @return void
     */
    public function test_has_user_viewalldetails_capability_in_tenant_context(): void {
        $interactor = new user_interactor($this->system_user->id);
        self::assertFalse($interactor->has_user_viewalldetails_capability_in_tenant_context($this->tenant_one->id));
        self::assertFalse($interactor->has_user_viewalldetails_capability_in_tenant_context($this->tenant_two->id));

        $role_id = self::getDataGenerator()->create_role();
        assign_capability('moodle/user:viewalldetails', CAP_ALLOW, $role_id, context_tenant::instance($this->tenant_one->id));
        role_assign($role_id, $this->system_user->id, context_tenant::instance($this->tenant_one->id));
        self::assertTrue($interactor->has_user_viewalldetails_capability_in_tenant_context($this->tenant_one->id));
        self::assertFalse($interactor->has_user_viewalldetails_capability_in_tenant_context($this->tenant_two->id));

        $interactor = new user_interactor($this->tenant_one_user->id);
        self::assertFalse($interactor->has_user_viewalldetails_capability_in_tenant_context($this->tenant_one->id));
        self::assertFalse($interactor->has_user_viewalldetails_capability_in_tenant_context($this->tenant_two->id));

        role_assign($role_id, $this->tenant_one_user->id, context_tenant::instance($this->tenant_one->id));
        self::assertTrue($interactor->has_user_viewalldetails_capability_in_tenant_context($this->tenant_one->id));
        self::assertFalse($interactor->has_user_viewalldetails_capability_in_tenant_context($this->tenant_two->id));
    }

    /**
     * has_user_viewparticipants_capability_in_tenant_context() test
     *
     * @return void
     */
    public function test_has_user_viewparticipants_capability_in_tenant_context(): void {
        $interactor = new user_interactor($this->system_user->id);
        self::assertFalse($interactor->has_user_viewparticipants_capability_in_tenant_context($this->tenant_one->id));
        self::assertFalse($interactor->has_user_viewparticipants_capability_in_tenant_context($this->tenant_two->id));

        $role_id = self::getDataGenerator()->create_role();
        assign_capability('totara/tenant:viewparticipants', CAP_ALLOW, $role_id, context_coursecat::instance($this->tenant_one->categoryid));
        role_assign($role_id, $this->system_user->id, context_coursecat::instance($this->tenant_one->categoryid));
        self::assertTrue($interactor->has_user_viewparticipants_capability_in_tenant_context($this->tenant_one->id));
        self::assertFalse($interactor->has_user_viewparticipants_capability_in_tenant_context($this->tenant_two->id));

        $interactor = new user_interactor($this->tenant_one_user->id);
        self::assertFalse($interactor->has_user_viewparticipants_capability_in_tenant_context($this->tenant_one->id));
        self::assertFalse($interactor->has_user_viewparticipants_capability_in_tenant_context($this->tenant_two->id));

        role_assign($role_id, $this->tenant_one_user->id, context_coursecat::instance($this->tenant_one->categoryid));
        self::assertTrue($interactor->has_user_viewparticipants_capability_in_tenant_context($this->tenant_one->id));
        self::assertFalse($interactor->has_user_viewparticipants_capability_in_tenant_context($this->tenant_two->id));
    }

    /**
     * has_user_viewalldetails_capability_in_system_context() test
     *
     * @return void
     */
    public function has_user_viewalldetails_capability_in_system_context(): void {
        $interactor = new user_interactor($this->system_user->id);
        self::assertFalse($interactor->has_user_viewalldetails_capability_in_system_context());

        $role_id = self::getDataGenerator()->create_role();
        assign_capability('moodle/user:viewalldetails', CAP_ALLOW, $role_id, context_system::instance());
        role_assign($role_id, $this->system_user->id, context_tenant::instance($this->tenant_one->id));
        self::assertTrue($interactor->has_user_viewalldetails_capability_in_system_context());

        $interactor = new user_interactor($this->tenant_one_user->id);
        self::assertFalse($interactor->has_user_viewalldetails_capability_in_system_context());

        role_assign($role_id, $this->tenant_one_user->id, context_system::instance());
        self::assertFalse($interactor->has_user_viewalldetails_capability_in_system_context());
    }
}
