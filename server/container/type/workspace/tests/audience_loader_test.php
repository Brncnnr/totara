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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package container_workspace
 */

defined('MOODLE_INTERNAL') || die();

use container_workspace\workspace;
use container_workspace\loader\member\audience_loader;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group container_workspace
 */
class container_workspace_audience_loader_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        global $CFG;
        require_once($CFG->dirroot.'/cohort/lib.php');
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

    public function test_get_users_to_add(): void {
        $generator = $this->getDataGenerator();

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();
        $user4 = $generator->create_user();
        $user5 = $generator->create_user();
        $user6 = $generator->create_user();
        $user7 = $generator->create_user();

        $cohort1 = $generator->create_cohort();
        $cohort2 = $generator->create_cohort();
        // Have an empty cohort
        $cohort3 = $generator->create_cohort();

        cohort_add_member($cohort1->id, $user2->id);
        cohort_add_member($cohort1->id, $user3->id);

        cohort_add_member($cohort2->id, $user4->id);
        cohort_add_member($cohort2->id, $user5->id);

        $this->setUser($user1);

        $workspace_generator = $this->get_workspace_generator();
        $workspace1 = $workspace_generator->create_workspace();
        $workspace2 = $workspace_generator->create_workspace();

        $workspace_generator->add_member($workspace2, $user2->id);
        $workspace_generator->add_member($workspace2, $user3->id);

        $workspace_generator->add_member($workspace1, $user4->id);
        $workspace_generator->add_member($workspace1, $user6->id);

        $cohort_ids = [$cohort1->id, $cohort2->id];
        $expected = [$user2->id, $user3->id, $user5->id];
        $this->assert_cohort_members_to_add_to_workspace($workspace1, $cohort_ids, $expected);

        $workspace_generator->add_member($workspace1, $user2->id);
        $expected = [$user3->id, $user5->id];
        $this->assert_cohort_members_to_add_to_workspace($workspace1, $cohort_ids, $expected);

        $workspace_generator->add_member($workspace1, $user3->id);
        $expected = [$user5->id];
        $this->assert_cohort_members_to_add_to_workspace($workspace1, $cohort_ids, $expected);

        $workspace_generator->add_member($workspace1, $user5->id);
        $expected = [];
        $this->assert_cohort_members_to_add_to_workspace($workspace1, $cohort_ids, $expected);

        // Now try with an empty cohort
        $cohort_ids = [$cohort3->id];
        $expected = [];
        $this->assert_cohort_members_to_add_to_workspace($workspace1, $cohort_ids, $expected);

        cohort_add_member($cohort3->id, $user4->id);
        cohort_add_member($cohort3->id, $user6->id);
        $this->assert_cohort_members_to_add_to_workspace($workspace1, $cohort_ids, $expected);

        // Now add one more and try again

        cohort_add_member($cohort3->id, $user7->id);
        $expected = [$user7->id];
        $this->assert_cohort_members_to_add_to_workspace($workspace1, $cohort_ids, $expected);
        $this->assertDebuggingCalledCount(14);
    }

    public function test_get_users_to_add_with_multi_tenancy(): void {
        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->enable_tenants();

        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();

        // Tenant 1 users
        $tenant1_user1 = $generator->create_user(['tenantid' => $tenant1->id]);
        $tenant1_user2 = $generator->create_user(['tenantid' => $tenant1->id]);
        $tenant1_user3 = $generator->create_user(['tenantid' => $tenant1->id]);
        // Tenant 2 users
        $tenant2_user1 = $generator->create_user(['tenantid' => $tenant2->id]);
        $tenant2_user2 = $generator->create_user(['tenantid' => $tenant2->id]);
        $tenant2_user3 = $generator->create_user(['tenantid' => $tenant2->id]);
        // A system user
        $system_user1 = $generator->create_user();

        $tenant1_cat_context = context_coursecat::instance($tenant1->categoryid);
        $tenant2_cat_context = context_coursecat::instance($tenant1->categoryid);

        // Tenant audiences
        $tenant1_cohort = $generator->create_cohort(['contextid' => $tenant1_cat_context->id]);
        $tenant2_cohort = $generator->create_cohort(['contextid' => $tenant2_cat_context->id]);
        // System audience
        $mixed_cohort = $generator->create_cohort();

        cohort_add_member($tenant1_cohort->id, $tenant1_user1->id);
        cohort_add_member($tenant1_cohort->id, $tenant1_user2->id);
        cohort_add_member($tenant1_cohort->id, $tenant1_user3->id);

        cohort_add_member($tenant2_cohort->id, $tenant2_user1->id);
        cohort_add_member($tenant2_cohort->id, $tenant2_user2->id);
        cohort_add_member($tenant2_cohort->id, $tenant2_user3->id);

        // Create a mixed cohort
        cohort_add_member($mixed_cohort->id, $tenant1_user2->id);
        cohort_add_member($mixed_cohort->id, $tenant1_user3->id);
        cohort_add_member($mixed_cohort->id, $system_user1->id);

        $this->setUser($tenant1_user1);

        $workspace_generator = $this->get_workspace_generator();
        $tenant1_workspace = $workspace_generator->create_workspace();

        $this->setUser($tenant2_user1);

        $workspace_generator = $this->get_workspace_generator();
        $tenant2_workspace = $workspace_generator->create_workspace();

        $this->setAdminUser();

        $workspace_generator = $this->get_workspace_generator();
        $system_workspace = $workspace_generator->create_workspace();

        $cohort_ids = [$tenant1_cohort->id];
        $expected = [$tenant1_user2->id, $tenant1_user3->id];
        $this->assert_cohort_members_to_add_to_workspace($tenant1_workspace, $cohort_ids, $expected);

        $cohort_ids = [$tenant1_cohort->id];
        $expected = [];
        $this->assert_cohort_members_to_add_to_workspace($tenant2_workspace, $cohort_ids, $expected);

        $cohort_ids = [$tenant2_cohort->id];
        $expected = [];
        $this->assert_cohort_members_to_add_to_workspace($tenant1_workspace, $cohort_ids, $expected);

        $cohort_ids = [$tenant2_cohort->id];
        $expected = [$tenant2_user2->id, $tenant2_user3->id];
        $this->assert_cohort_members_to_add_to_workspace($tenant2_workspace, $cohort_ids, $expected);

        $cohort_ids = [$mixed_cohort->id];
        $expected = [$tenant1_user2->id, $tenant1_user3->id];
        $this->assert_cohort_members_to_add_to_workspace($tenant1_workspace, $cohort_ids, $expected);

        $cohort_ids = [$mixed_cohort->id];
        $expected = [];
        $this->assert_cohort_members_to_add_to_workspace($tenant2_workspace, $cohort_ids, $expected);

        $cohort_ids = [$mixed_cohort->id];
        $expected = [$tenant1_user2->id, $tenant1_user3->id, $system_user1->id];
        $this->assert_cohort_members_to_add_to_workspace($system_workspace, $cohort_ids, $expected);

        set_config('tenantsisolated', 1);

        $cohort_ids = [$mixed_cohort->id];
        $expected = [$system_user1->id];
        $this->assert_cohort_members_to_add_to_workspace($system_workspace, $cohort_ids, $expected);
        $this->assertDebuggingCalledCount(16);
    }

    /**
     * Verifies the cohort members that can added as members to a workspace.
     *
     * @param workspace $workspace the workspace to check.
     * @param int[] $cohort_ids
     * @param int[] $potential_members cohort members that can be added to the
     *        workspace.
     */
    private function assert_cohort_members_to_add_to_workspace(
        workspace $workspace,
        array $cohort_ids,
        array $potential_members
    ): void {
        $count = audience_loader::get_bulk_members_to_add_count($workspace, $cohort_ids);
        $this->assertCount($count, $potential_members);

        $to_add_members = audience_loader::get_bulk_members_to_add($workspace, $cohort_ids);
        $this->assertEqualsCanonicalizing($to_add_members, $potential_members);
    }
}