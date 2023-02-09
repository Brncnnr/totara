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
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use container_workspace\member\member;
use core\entity\user_enrolment;

class container_workspace_multi_tenancy_join_workspace_as_tenant_user_testcase extends advanced_testcase {
    /**
     * The main actor of this test.
     * @var stdClass|null
     */
    private $tenant_user;

    /**
     * @var stdClass|null
     */
    private $tenant_participant;

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

        $tenant = $tenant_generator->create_tenant();

        $this->tenant_user = $generator->create_user([
            'firstname' => uniqid('tenant_user_'),
            'lastname' => uniqid('tenant_user_'),
            'tenantid' => $tenant->id
        ]);

        $this->system_user = $generator->create_user([
            'firstname' => uniqid('system_user_'),
            'lastname' => uniqid('system_user_')
        ]);

        $this->tenant_participant = $generator->create_user([
            'firstname' => uniqid('tenant_participant_'),
            'lastname' => uniqid('tenant_participant_')
        ]);

        $tenant_generator->set_user_participation($this->tenant_participant->id, [$tenant->id]);
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->tenant_user = null;
        $this->tenant_participant = null;
        $this->system_user = null;
    }

    /**
     * @return \container_workspace\testing\generator
     */
    private function get_workspace_generator(): \container_workspace\testing\generator {
        $generator = self::getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        return $workspace_generator;
    }

    /**
     * @return void
     */
    public function test_join_system_user_workspace_with_isolation(): void {
        set_config('tenantsisolated', 1);
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->system_user);
        $workspace = $workspace_generator->create_workspace();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot join the workspace that is not in the same tenant");

        member::join_workspace($workspace, $this->tenant_user->id);
    }

    /**
     * @return void
     */
    public function test_join_system_user_workspace_without_isolation(): void {
        global $DB;
        $workspace_generator = $this->get_workspace_generator();
        $this->setUser($this->system_user);

        $workspace = $workspace_generator->create_workspace();
        $member = member::join_workspace($workspace, $this->tenant_user->id);

        self::assertNotEmpty($member->get_id());
        self::assertEquals($this->tenant_user->id, $member->get_user_id());

        self::assertTrue($DB->record_exists(user_enrolment::TABLE, ['id' => $member->get_id()]));
    }

    /**
     * @return void
     */
    public function test_join_tenant_participant_workspace_with_isolation(): void {
        set_config('tenantsisolated', 1);
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_participant);
        $workspace = $workspace_generator->create_workspace();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot join the workspace that is not in the same tenant");

        member::join_workspace($workspace, $this->tenant_user->id);
    }

    /**
     * @return void
     */
    public function test_join_tenant_participant_workspace_without_isolation(): void {
        global $DB;

        $workspace_generator = $this->get_workspace_generator();
        $this->setUser($this->tenant_participant);

        $workspace = $workspace_generator->create_workspace();
        $member = member::join_workspace($workspace, $this->tenant_user->id);

        self::assertEquals($this->tenant_user->id, $member->get_user_id());
        self::assertTrue($DB->record_exists(user_enrolment::TABLE, ['id' => $member->get_id()]));
    }
}