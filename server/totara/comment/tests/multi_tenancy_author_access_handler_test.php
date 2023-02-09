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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_comment
 */
defined('MOODLE_INTERNAL') || die();

use totara_comment\access\author_access_handler;

class totara_comment_multi_tenancy_author_access_handler_testcase extends advanced_testcase {
    /**
     * @var stdClass|null
     */
    private $tenant_one_user_one;

    /**
     * @var stdClass|null
     */
    private $tenant_one_user_two;

    /**
     * @var stdClass|null
     */
    private $tenant_two_user_one;

    /**
     * @var stdClass|null
     */
    private $tenant_one_participant;

    /**
     * @var stdClass|null
     */
    private $system_user;

    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = self::getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        $this->tenant_one_user_one = $generator->create_user([
            'firstname' => 'tenant_one_user_one',
            'lastname' => 'tenant_one_user_one',
            'tenantid' => $tenant_one->id
        ]);

        $this->tenant_one_user_two = $generator->create_user([
            'firstname' => 'tenant_one_user_two',
            'lastname' => 'tenant_one_user_two',
            'tenantid' => $tenant_one->id
        ]);

        $this->tenant_two_user_one = $generator->create_user([
            'firstname' => 'tenant_two_user_one',
            'lastname' => 'tenant_two_user_one',
            'tenantid' => $tenant_two->id
        ]);

        $this->system_user = $generator->create_user([
            'firstname' => 'system_user',
            'lastname' => 'system_user'
        ]);

        $this->tenant_one_participant = $generator->create_user([
            'firstname' => 'tenant_one_participant',
            'lastname' => 'tenant_one_participant'
        ]);

        $tenant_generator->set_user_participation(
            $this->tenant_one_participant->id,
            [$tenant_one->id]
        );
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->tenant_one_user_one = null;
        $this->tenant_one_user_two = null;
        $this->tenant_two_user_one = null;
        $this->tenant_one_participant = null;
        $this->system_user = null;
    }

    /**
     * @return void
     */
    public function test_check_of_tenant_participant_without_isolation_mode(): void {
        $handler = new author_access_handler($this->tenant_one_participant->id);

        self::assertTrue($handler->can_see_user($this->tenant_one_user_one->id));
        self::assertTrue($handler->can_see_user($this->tenant_one_user_two->id));
        self::assertTrue($handler->can_see_user($this->system_user->id));

        self::assertTrue($handler->can_see_user($this->tenant_two_user_one->id));

        // This is to make sure that the cache is working.
        self::assertTrue($handler->can_see_user($this->system_user->id));
    }

    /**
     * @return void
     */
    public function test_check_of_tenant_participant_with_isolation_mode(): void {
        set_config('tenantsisolated', 1);
        $handler = new author_access_handler($this->tenant_one_participant->id);

        self::assertTrue($handler->can_see_user($this->tenant_one_participant->id));
        self::assertTrue($handler->can_see_user($this->tenant_one_user_one->id));
        self::assertTrue($handler->can_see_user($this->tenant_one_user_two->id));
        self::assertTrue($handler->can_see_user($this->system_user->id));

        self::assertFalse($handler->can_see_user($this->tenant_two_user_one->id));

        // This is to make sure that the cache is working.
        self::assertTrue($handler->can_see_user($this->system_user->id));
    }

    /**
     * @return void
     */
    public function test_check_of_tenant_member_with_isolation(): void {
        set_config('tenantsisolated', 1);
        $handler = new author_access_handler($this->tenant_one_user_one->id);

        self::assertTrue($handler->can_see_user($this->tenant_one_user_two->id));
        self::assertTrue($handler->can_see_user($this->tenant_one_user_one->id));

        self::assertFalse($handler->can_see_user($this->tenant_one_participant->id));
        self::assertFalse($handler->can_see_user($this->system_user->id));
        self::assertFalse($handler->can_see_user($this->tenant_two_user_one->id));

        // This is to make sure that the cache is working.
        self::assertFalse($handler->can_see_user($this->system_user->id));
    }

    /**
     * @return void
     */
    public function test_check_of_tenant_member_without_isolation(): void {
        $handler = new author_access_handler($this->tenant_one_user_one->id);

        self::assertTrue($handler->can_see_user($this->tenant_one_user_two->id));
        self::assertTrue($handler->can_see_user($this->tenant_one_user_one->id));

        self::assertTrue($handler->can_see_user($this->tenant_one_participant->id));
        self::assertTrue($handler->can_see_user($this->system_user->id));
        self::assertFalse($handler->can_see_user($this->tenant_two_user_one->id));

        // This is to make sure that the cache is working.
        self::assertTrue($handler->can_see_user($this->system_user->id));
    }

    /**
     * @return void
     */
    public function test_check_of_system_user_with_isolation(): void {
        set_config('tenantsisolated', 1);
        $handler = new author_access_handler($this->system_user->id);

        self::assertTrue($handler->can_see_user($this->tenant_one_participant->id));
        self::assertFalse($handler->can_see_user($this->tenant_one_user_one->id));
        self::assertFalse($handler->can_see_user($this->tenant_one_user_two->id));
        self::assertFalse($handler->can_see_user($this->tenant_two_user_one->id));
    }

    /**
     * @return void
     */
    public function test_check_of_system_user_without_isolation(): void {
        $handler = new author_access_handler($this->system_user->id);

        self::assertTrue($handler->can_see_user($this->tenant_one_participant->id));
        self::assertTrue($handler->can_see_user($this->tenant_one_user_one->id));
        self::assertTrue($handler->can_see_user($this->tenant_one_user_two->id));
        self::assertTrue($handler->can_see_user($this->tenant_two_user_one->id));
    }

    /**
     * @return void
     */
    public function test_check_on_different_cache_instance(): void {
        $handler_one = new author_access_handler($this->tenant_one_user_one->id);
        self::assertTrue($handler_one->can_see_user($this->tenant_one_participant->id));
        self::assertTrue($handler_one->can_see_user($this->tenant_one_user_two->id));
        self::assertTrue($handler_one->can_see_user($this->system_user->id));
        self::assertFalse($handler_one->can_see_user($this->tenant_two_user_one->id));

        $handler_two = new author_access_handler($this->tenant_two_user_one->id);
        self::assertTrue($handler_two->can_see_user($this->tenant_one_participant->id));
        self::assertFalse($handler_two->can_see_user($this->tenant_one_user_one->id));
        self::assertFalse($handler_two->can_see_user($this->tenant_one_user_two->id));
        self::assertTrue($handler_two->can_see_user($this->system_user->id));
        self::assertTrue($handler_two->can_see_user($this->tenant_two_user_one->id));
    }

    /**
     * @return void
     */
    public function test_check_of_admin_user_with_isolation(): void {
        set_config('tenantsisolated', 1);
        $admin = get_admin();
        $handler = new author_access_handler($admin->id);

        self::assertTrue($handler->can_see_user($this->tenant_one_participant->id));
        self::assertTrue($handler->can_see_user($this->tenant_one_user_two->id));
        self::assertTrue($handler->can_see_user($this->tenant_one_user_one->id));
        self::assertTrue($handler->can_see_user($this->system_user->id));
        self::assertTrue($handler->can_see_user($this->tenant_two_user_one->id));
    }

    /**
     * @return void
     */
    public function test_check_of_admin_user_without_isolation(): void {
        $admin = get_admin();
        $handler = new author_access_handler($admin->id);

        self::assertTrue($handler->can_see_user($this->tenant_one_participant->id));
        self::assertTrue($handler->can_see_user($this->tenant_one_user_two->id));
        self::assertTrue($handler->can_see_user($this->tenant_one_user_one->id));
        self::assertTrue($handler->can_see_user($this->system_user->id));
        self::assertTrue($handler->can_see_user($this->tenant_two_user_one->id));
    }
}