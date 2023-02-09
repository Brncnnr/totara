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
 * @package totara_engage
 */
defined('MOODLE_INTERNAL') || die();

use totara_engage\engage_core;

class totara_engage_core_tenancy_check_testcase extends advanced_testcase {
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
    private $tenant_one_participant;

    /**
     * @var stdClass|null
     */
    private $tenant_two_user;

    /**
     * @var stdClass|null
     */
    private $system_user;

    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        $this->tenant_one_user_one = $generator->create_user([
            'firstname' => uniqid('tenant_one_user_one_'),
            'lastname' => uniqid('tenant_one_user_one'),
            'tenantid' => $tenant_one->id
        ]);

        $this->tenant_one_user_two = $generator->create_user([
            'firstname' => uniqid('tenant_one_user_two_'),
            'lastname' => uniqid('tenant_one_user_two_'),
            'tenantid' => $tenant_one->id
        ]);

        $this->system_user = $generator->create_user([
            'firstname' => uniqid('system_user_'),
            'lastname' => uniqid('system_user_')
        ]);

        $this->tenant_two_user = $generator->create_user([
            'firstname' => uniqid('tenant_two_user_'),
            'lastname' => uniqid('tenant_two_user_'),
            'tenantid' => $tenant_two->id
        ]);

        $this->tenant_one_participant = $generator->create_user([
            'firstname' => uniqid('tenant_one_participant_'),
            'lastname' => uniqid('tenant_one_participant_')
        ]);

        $tenant_generator->set_user_participation($this->tenant_one_participant->id, [$tenant_one->id]);
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->tenant_one_user_one = null;
        $this->tenant_one_user_two = null;
        $this->tenant_one_participant = null;
        $this->tenant_two_user = null;
        $this->system_user = null;
    }

    /**
     * @return void
     */
    public function test_tenant_one_member_cannot_interact_with_tenant_two_member(): void {
        self::assertFalse(
            engage_core::can_interact_with_user_in_tenancy_check(
                $this->tenant_one_user_one->id,
                $this->tenant_two_user->id
            )
        );

        set_config('tenantsisolated', 1);

        self::assertFalse(
            engage_core::can_interact_with_user_in_tenancy_check(
                $this->tenant_one_user_one->id,
                $this->tenant_two_user->id
            )
        );
    }

    /**
     * @return void
     */
    public function test_tenant_one_member_can_interact_with_system_user(): void {
        self::assertTrue(
            engage_core::can_interact_with_user_in_tenancy_check(
                $this->tenant_one_user_one->id,
                $this->system_user->id
            )
        );

        set_config('tenantsisolated', 1);

        self::assertFalse(
            engage_core::can_interact_with_user_in_tenancy_check(
                $this->tenant_one_user_one->id,
                $this->system_user->id
            )
        );
    }

    /**
     * @return void
     */
    public function test_tenant_one_member_can_interact_with_tenant_one_member(): void {
        self::assertTrue(
            engage_core::can_interact_with_user_in_tenancy_check(
                $this->tenant_one_user_one->id,
                $this->tenant_one_user_two->id
            )
        );

        set_config('tenantsisolated', 1);

        self::assertTrue(
            engage_core::can_interact_with_user_in_tenancy_check(
                $this->tenant_one_user_one->id,
                $this->tenant_one_user_two->id
            )
        );
    }

    /**
     * @return void
     */
    public function test_tenant_one_member_can_interact_with_participant_user(): void {
        self::assertTrue(
            engage_core::can_interact_with_user_in_tenancy_check(
                $this->tenant_one_user_one->id,
                $this->tenant_one_participant->id
            )
        );

        set_config('tenantsisolated', 1);

        self::assertTrue(
            engage_core::can_interact_with_user_in_tenancy_check(
                $this->tenant_one_user_one->id,
                $this->tenant_one_participant->id
            )
        );
    }


    /**
     * @return void
     */
    public function test_tenant_one_member_see_participant_user(): void {
        self::assertTrue(
            engage_core::can_interact_with_user_in_tenancy_check(
                $this->tenant_one_user_one->id,
                $this->tenant_one_participant->id
            )
        );

        set_config('tenantsisolated', 1);

        self::assertTrue(
            engage_core::can_interact_with_user_in_tenancy_check(
                $this->tenant_one_user_one->id,
                $this->tenant_one_participant->id
            )
        );
    }

    /**
     * @return void
     */
    public function test_tenant_participant_can_interact_with_tenant_member(): void {
        self::assertTrue(
            engage_core::can_interact_with_user_in_tenancy_check(
                $this->tenant_one_participant->id,
                $this->tenant_one_user_two->id
            )
        );

        set_config('tenantsisolated', 1);

        self::assertTrue(
            engage_core::can_interact_with_user_in_tenancy_check(
                $this->tenant_one_participant->id,
                $this->tenant_one_user_two->id
            )
        );
    }

    /**
     * @return void
     */
    public function test_tenant_participant_can_interact_with_system_user(): void {
        self::assertTrue(
            engage_core::can_interact_with_user_in_tenancy_check(
                $this->tenant_one_participant->id,
                $this->system_user->id
            )
        );

        set_config('tenantsisolated', 1);

        self::assertTrue(
            engage_core::can_interact_with_user_in_tenancy_check(
                $this->tenant_one_participant->id,
                $this->system_user->id
            )
        );
    }

    /**
     * @return void
     */
    public function test_system_user_can_interact_with_tenant_member(): void {
        self::assertTrue(
            engage_core::can_interact_with_user_in_tenancy_check(
                $this->system_user->id,
                $this->tenant_two_user->id
            )
        );

        set_config('tenantsisolated', 1);

        self::assertFalse(
            engage_core::can_interact_with_user_in_tenancy_check(
                $this->system_user->id,
                $this->tenant_two_user->id
            )
        );
    }
}