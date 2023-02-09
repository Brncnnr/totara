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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

use core\entity\user;
use core\orm\query\builder;
use core\pagination\offset_cursor as cursor;
use core_phpunit\testcase;
use mod_approval\data_provider\application\applications_for_others;
use mod_approval\data_provider\application\capability_map\capability_map_controller;
use mod_approval\data_provider\application\role_map\role_map_controller;
use mod_approval\entity\assignment\assignment as assignment_entity;
use mod_approval\model\application\action\approve;
use mod_approval\model\application\action\reject;
use mod_approval\model\application\action\submit;
use mod_approval\model\application\action\withdraw_in_approvals;
use mod_approval\model\application\application;
use mod_approval\model\application\application_state;
use mod_approval\model\application\application_submission;
use mod_approval\model\assignment\approver_type\relationship;
use mod_approval\model\assignment\approver_type\user as user_approver_type;
use mod_approval\model\assignment\assignment as assignment_model;
use mod_approval\model\assignment\assignment_approver as assignment_approver_model;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\form\form_data;
use mod_approval\model\workflow\workflow;
use mod_approval\model\status;
use mod_approval\model\workflow\workflow_stage_approval_level as workflow_stage_approval_level_model;
use mod_approval\model\workflow\workflow_version;
use mod_approval\testing\approval_workflow_test_setup;
use mod_approval\testing\assignment_approver_generator_object;
use mod_approval\testing\assignment_generator_object;
use totara_job\job_assignment;

/**
 * @coversDefaultClass \mod_approval\data_provider\application\applications_for_others
 *
 * @group approval_workflow
 * @group applications_dashboard
 */
class mod_approval_data_provider_applications_for_others_testcase extends testcase {

    use approval_workflow_test_setup;

    private function create_workflow_manager() {
        // Create a user with view_in_dashboard_application_any capability.
        $user = $this->getDataGenerator()->create_user();
        $sys_context = context_system::instance();
        $roleid = self::getDataGenerator()->create_role();
        assign_capability('mod/approval:view_in_dashboard_application_any', CAP_ALLOW, $roleid, $sys_context, true);
        role_assign($roleid, $user->id, $sys_context);
        return $user;
    }

    private function create_user_manager(stdClass $user) {
        // Create a user to be the manager of the given user.
        $boss = $this->getDataGenerator()->create_user();
        job_assignment::create_default($user->id, ['managerjaid' => job_assignment::create_default($boss->id)->id]);
        return $boss;
    }

    /**
     * @covers ::process_fetched_items
     */
    public function test_is_application_model() {
        $this->setAdminUser();
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $wf_manager = $this->create_workflow_manager();
        $boss = $this->create_user_manager($user1);

        // Create an application as user1.
        $this->setUser($user1);
        $user_entity = new user($user1->id);
        $this->create_submitted_application($workflow, $assignment, $user_entity);

        // Create a provider and test it with workflow manager.
        $this->set_user_with_capability_maps($wf_manager);
        $provider = new applications_for_others($wf_manager->id);
        $applications = $provider->fetch()->get();
        $this->assertInstanceOf(application::class, $applications->first());

        // Create a provider and test it with user manager.
        $this->set_user_with_capability_maps($boss);
        $provider = new applications_for_others($boss->id);
        $applications = $provider->fetch()->get();
        $this->assertInstanceOf(application::class, $applications->first());
    }

    /**
     * @covers ::process_fetched_items
     */
    public function test_excludes_draft_applications() {
        $this->setAdminUser();
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity = new user($user1->id);
        $wf_manager = $this->create_workflow_manager();
        $boss = $this->create_user_manager($user1);

        // Create applications as admin for user1. These applications are in DRAFT state and could be seen by owner
        // but no one else.
        $this->setAdminUser();
        role_map_controller::regenerate_all_maps();
        capability_map_controller::regenerate_all_maps(get_admin()->id);
        $this->create_application($workflow, $assignment, $user_entity);
        $this->create_application($workflow, $assignment, $user_entity);

        // Check if admin can see applications created on behalf
        $provider = new applications_for_others(get_admin()->id);
        $applications = $provider->fetch()->get();
        $this->assertCount(2, $applications);

        // And applications created on behalf have a different applicant from the creator/owner
        foreach ($applications as $application) {
            $this->assertNotEquals(get_admin()->id, $application->user_id);
        }

        // Create a provider and test it with workflow manager, who would be able to see non-draft applications.
        $this->set_user_with_capability_maps($wf_manager);
        $provider = new applications_for_others($wf_manager->id);
        $applications = $provider->fetch()->get();
        $this->assertCount(0, $applications);

        // Create a provider and test it with user manager, who would be able to see non-draft applications.
        $this->set_user_with_capability_maps($boss);
        $provider = new applications_for_others($boss->id);
        $applications = $provider->fetch()->get();
        $this->assertCount(0, $applications);
    }

    /**
     * @covers ::build_query
     */
    public function test_excludes_user_as_applicant() {
        $this->setAdminUser();
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity1 = new user($user1->id);
        $wf_manager = $this->create_workflow_manager();
        $wf_manager_entity = new user($wf_manager->id);

        // Create two applications as user1
        $this->setUser($user1);
        for ($i = 0; $i < 2; $i++) {
            $this->create_submitted_application($workflow, $assignment, $user_entity1);
        }
        // Create two applications as manager
        $this->setUser($wf_manager);
        for ($i = 0; $i < 2; $i++) {
            $this->create_submitted_application($workflow, $assignment, $wf_manager_entity);
        }
        // Create a third application as user1
        $this->setUser($user1);
        $this->create_submitted_application($workflow, $assignment, $user_entity1);

        // Create a provider and test that it limits by user id -- wf_manager won't see their own applications.
        $this->set_user_with_capability_maps($wf_manager);
        $provider = new applications_for_others($wf_manager->id);
        $applications = $provider->fetch()->get();
        $this->assertEquals(3, $applications->count());

        // Check that the applications all belong to user1
        foreach ($applications as $application) {
            $this->assertEquals($user1->id, $application->user_id);
        }

        // Delete user1 and check again.
        delete_user($user1);
        $applications = $provider->fetch()->get();
        $this->assertEquals(0, $applications->count());
    }

    /**
     * Broadly tests that a user in one tenant cannot see applications in another.
     *
     * This is also covering the integration of capability maps, which have their own mutlitenancy restriction.
     *
     * @covers ::build_query
     */
    public function test_multitenancy_visibility_checks() {
        $generator = $this->getDataGenerator();
        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        $system_user = $generator->create_user();
        $tenant_participant = $generator->create_user();

        $tenant_generator->set_user_participation($tenant_participant->id, [$tenant_one->id, $tenant_two->id]);

        $user1_tenant1 = $generator->create_user(['tenantid' => $tenant_one->id, 'tenantdomainmanager' => $tenant_one->idnumber]);
        $user2_tenant1 = $generator->create_user(['tenantid' => $tenant_one->id]);
        $user1_tenant2 = $generator->create_user(['tenantid' => $tenant_two->id, 'tenantdomainmanager' => $tenant_two->idnumber]);
        $user2_tenant2 = $generator->create_user(['tenantid' => $tenant_two->id]);

        // System
        $this->setAdminUser();
        list($workflow1, , $assignment1) = $this->create_workflow_and_assignment();
        list($workflow2, , $assignment2) = $this->create_workflow_and_assignment();
        $this->assertNotEquals($workflow1->id, $workflow2->id);
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity1 = new user($user1->id);
        $wf_manager = $this->create_workflow_manager();
        $wf_manager_entity = new user($wf_manager->id);
        // Create two applications as user1
        $this->setUser($user_entity1);
        for ($i = 0; $i < 2; $i++) {
            $this->create_submitted_application($workflow1, $assignment1, $user_entity1);
        }

        // Create a workflow in tenant1
        $this->setUser($user1_tenant1);
        $workflow_tenant1 = $this->generator()->create_simple_request_workflow('Testing');
        $assignment_go = new assignment_generator_object(
            $workflow_tenant1->course_id,
            assignment_type\cohort::get_code(),
            $generator->create_cohort()->id
        );
        $assignment_go->is_default = true;
        $assignment_go->status = status::ACTIVE;
        $assignment_tenant1 = $this->generator()->create_assignment($assignment_go);

        // Create two applications as user2_tenant1
        $this->setUser($user2_tenant1);
        for ($i = 0; $i < 2; $i++) {
            $this->create_submitted_application($workflow_tenant1, $assignment_tenant1, new user($user2_tenant1));
        }

        // Create a workflow in tenant2
        $this->setUser($user1_tenant2);
        $workflow_tenant2 = $this->generator()->create_simple_request_workflow('Testing');
        $assignment_go = new assignment_generator_object(
            $workflow_tenant2->course_id,
            assignment_type\cohort::get_code(),
            $generator->create_cohort()->id
        );
        $assignment_go->is_default = true;
        $assignment_go->status = status::ACTIVE;
        $assignment_tenant2 = $this->generator()->create_assignment($assignment_go);

        // Create two applications as user2_tenant2
        $this->setUser($user2_tenant2);
        for ($i = 0; $i < 2; $i++) {
            $this->create_submitted_application($workflow_tenant2, $assignment_tenant2, new user($user2_tenant2));
        }

        // Create a provider and test it with system workflow manager, they should see all applications.
        $this->set_user_with_capability_maps($wf_manager);
        $provider = new applications_for_others($wf_manager->id);
        $applications = $provider->fetch()->get();
        $this->assertCount(6, $applications);

        // Now test with tenant 1 workflow manager, they only see workflow_tenant1 applications
        $this->set_user_with_capability_maps($user1_tenant1);
        $provider = new applications_for_others($user1_tenant1->id);
        $applications = $provider->fetch()->get();
        $this->assertCount(2, $applications);
        foreach ($applications as $application) {
            $this->assertEquals($workflow_tenant1->id, $application->workflow_version->workflow_id);
        }

        // Give tenant2 manager the view_any capability in system context.
        $sys_context = context_system::instance();
        $roleid = self::getDataGenerator()->create_role();
        assign_capability('mod/approval:view_in_dashboard_application_any', CAP_ALLOW, $roleid, $sys_context, true);
        role_assign($roleid, $user1_tenant2->id, $sys_context);
        $this->set_user_with_capability_maps($user1_tenant2);
        $provider = new applications_for_others($user1_tenant2->id);
        $applications = $provider->fetch()->get();

        // Because isolation is not on, they see system applications also.
        $this->assertCount(4, $applications);

        // Turn on tenant isolation and reset the maps.
        set_config('tenantsisolated', 1);

        // System manager sees all 6 applications, still.
        $this->set_user_with_capability_maps($wf_manager);
        $provider = new applications_for_others($wf_manager->id);
        $applications = $provider->fetch()->get();
        $this->assertCount(6, $applications);

        // Tenant 1 manager sees the same 2 workflow_tenant1 applications
        $this->set_user_with_capability_maps($user1_tenant1);
        $provider = new applications_for_others($user1_tenant1->id);
        $applications = $provider->fetch()->get();
        $this->assertCount(2, $applications);
        foreach ($applications as $application) {
            $this->assertEquals($workflow_tenant1->id, $application->workflow_version->workflow_id);
        }

        // Tenant 2 manager only sees 2 applications now.
        $this->set_user_with_capability_maps($user1_tenant2);
        $provider = new applications_for_others($user1_tenant2->id);
        $applications = $provider->fetch()->get();
        $this->assertCount(2, $applications);
        foreach ($applications as $application) {
            $this->assertEquals($workflow_tenant2->id, $application->workflow_version->workflow_id);
        }
    }

    /**
     * Simple test for whether capability integration is working.
     *
     * Setup must create two applications that can be seen when the capability is allowed in system context,
     * and a context where, when the capability is prevented, causes one of the applications to fall out of the
     * results.
     *
     * @param string $capability
     * @param int $prevent_context_id
     */
    private function test_capability_map_integration(string $capability, int $prevent_context_id, user $test_user = null) {
        // Create this workflow manager by hand so we can override the capability later.
        if (is_null($test_user)) {
            $test_user = $this->getDataGenerator()->create_user();
        }

        // Create a provider and test it with no capability.
        $this->set_user_with_capability_maps($test_user);
        $provider = new applications_for_others($test_user->id);
        $applications = $provider->fetch()->get();
        $this->assertEquals(0, $applications->count());

        // Assign capability to user in system context.
        $sys_context = context_system::instance();
        $roleid = self::getDataGenerator()->create_role();
        assign_capability('mod/approval:' . $capability, CAP_ALLOW, $roleid, $sys_context, true);
        role_assign($roleid, $test_user->id, $sys_context);

        $this->set_user_with_capability_maps($test_user);
        $provider = new applications_for_others($test_user->id);
        $applications = $provider->fetch()->get();
        $this->assertEquals(2, $applications->count());

        // Prevent user from using capability in one context.
        assign_capability('mod/approval:' . $capability, CAP_PREVENT, $roleid, $prevent_context_id, true);

        $this->set_user_with_capability_maps($test_user);
        $provider = new applications_for_others($test_user->id);
        $applications = $provider->fetch()->get();
        $this->assertEquals(1, $applications->count());
    }

    /**
     * Covers integration with view_in_dashboard_application_any capability map.
     *
     * @covers ::build_query
     */
    public function test_capability_check_view_in_dashboard_application_any() {
        $this->setAdminUser();
        list($workflow1, , $assignment1) = $this->create_workflow_and_assignment();
        list($workflow2, , $assignment2) = $this->create_workflow_and_assignment();
        $this->assertNotEquals($workflow1->id, $workflow2->id);
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity1 = new user($user1->id);

        // Create two submitted applications as user1
        $this->setUser($user1);
        $this->create_submitted_application($workflow1, $assignment1, $user_entity1);
        $this->create_submitted_application($workflow2, $assignment2, $user_entity1);

        // One will disappear when capability is prevented in assignment1.
        $prevent_context_id = assignment_model::load_by_entity($assignment1)->get_context()->id;

        // Create a provider and test it.
        $this->test_capability_map_integration('view_in_dashboard_application_any', $prevent_context_id);
    }

    /**
     * Covers integration with view_draft_in_dashboard_application_any capability map.
     *
     * @covers ::build_query
     */
    public function test_capability_check_view_draft_in_dashboard_application_any() {
        $this->setAdminUser();
        list($workflow1, , $assignment1) = $this->create_workflow_and_assignment();
        list($workflow2, , $assignment2) = $this->create_workflow_and_assignment();
        $this->assertNotEquals($workflow1->id, $workflow2->id);
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity1 = new user($user1->id);

        // Create two draft applications as user1
        $this->setUser($user1);
        $this->create_application($workflow1, $assignment1, $user_entity1);
        $this->create_application($workflow2, $assignment2, $user_entity1);

        // One will disappear when capability is prevented in assignment1.
        $prevent_context_id = assignment_model::load_by_entity($assignment1)->get_context()->id;

        // Create a provider and test it.
        $this->test_capability_map_integration('view_draft_in_dashboard_application_any', $prevent_context_id);
    }

    /**
     * Covers integration with view_in_dashboard_pending_application_any capability map.
     *
     * @covers ::build_query
     */
    public function test_capability_check_view_in_dashboard_pending_application_any() {
        $this->setAdminUser();
        list($workflow1, , $assignment1) = $this->create_workflow_and_assignment();
        list($workflow2, , $assignment2) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity1 = new user($user1->id);

        $approver = new user($this->getDataGenerator()->create_user()->id);

        $form_data = form_data::from_json('{"agency_code": 25}');

        // For each workflow/assignment pair.
        foreach (['1', '2'] as $wx) {
            // Create stage2 level2 for our approver, on each workflow.
            $workflow_model = workflow::load_by_entity(${'workflow' . $wx});
            $workflow_version = $workflow_model->latest_version;
            $stage1 = $workflow_version->stages->first();
            $stage2 = $workflow_version->get_next_stage($stage1->id);
            $level1 = $stage2->approval_levels->first();
            $level2 = $this->generator()->create_approval_level($stage2->id, 'Level 2', 2);
            $workflow_model->publish($workflow_version);

            // Create a level2 approver on each assignment.
            $assignment_approver_go = new assignment_approver_generator_object(
                ${'assignment' . $wx}->id,
                $level2->id,
                user_approver_type::get_code(),
                $approver->id
            );
            $assignment_approver = $this->generator()->create_assignment_approver($assignment_approver_go);

            // Remove the auto-assigned approvalworkflowapprover role if present.
            $approver_role_id = builder::table('role')->where('shortname', 'approvalworkflowapprover')->value('id');
            $assignment_model = assignment_model::load_by_entity(${'assignment' . $wx});
            role_unassign($approver_role_id, $approver->id, $assignment_model->contextid);

            // Create two applications on each workflow, submitted.
            foreach (['l1', 'l2'] as $lx) {
                $this->setUser($user_entity1);
                $app = $this->create_application(${'workflow' . $wx}, ${'assignment' . $wx}, $user_entity1);
                $submission_entity = $this->generator()->create_application_submission(
                    $app->id,
                    $user_entity1->id,
                    $app->current_state->get_stage_id(),
                    $form_data
                );
                $submission = application_submission::load_by_entity($submission_entity);
                submit::execute($app, $user_entity1->id);
                $app->refresh(true);

                // Create $pending_lx_wx.
                ${'pending_' . $lx . '_w' . $wx} = $app;
                unset($app);
            }
        }

        // Move the '_l2_' applications to Level 2.
        approve::execute($pending_l2_w1, get_admin()->id);
        approve::execute($pending_l2_w2, get_admin()->id);

        // One will disappear when capability is prevented in assignment1.
        $prevent_context_id = assignment_model::load_by_entity($assignment1)->get_context()->id;

        // Create a provider and test it.
        $this->test_capability_map_integration('view_in_dashboard_pending_application_any', $prevent_context_id, $approver);
    }

    /**
     * Covers integration with view_in_dashboard_application_user capability map.
     *
     * @covers ::build_query
     */
    public function test_capability_check_view_in_dashboard_application_user() {
        $this->setAdminUser();
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity1 = new user($user1->id);
        $user2 = $this->getDataGenerator()->create_user();
        $user_entity2 = new user($user2->id);

        // Create applications for each user on the same workflow.
        $this->setUser($user1);
        $this->create_submitted_application($workflow, $assignment, $user_entity1);
        $this->setUser($user2);
        $this->create_submitted_application($workflow, $assignment, $user_entity2);

        // Preventing the capability in user1 context will cause user1's application to drop out.
        $user1_ctx = context_user::instance($user1->id);

        // Create a provider and test it.
        $this->test_capability_map_integration('view_in_dashboard_application_user', $user1_ctx->id);
    }

    /**
     * Covers integration with view_draft_in_dashboard_application_user capability map.
     *
     * @covers ::build_query
     */
    public function test_capability_check_view_draft_in_dashboard_application_user() {
        $this->setAdminUser();
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity1 = new user($user1->id);
        $user2 = $this->getDataGenerator()->create_user();
        $user_entity2 = new user($user2->id);

        // Create applications for each user on the same workflow.
        $this->setUser($user1);
        $this->create_application($workflow, $assignment, $user_entity1);
        $this->setUser($user2);
        $this->create_application($workflow, $assignment, $user_entity2);

        // Preventing the capability in user1 context will cause user1's application to drop out.
        $user1_ctx = context_user::instance($user1->id);

        // Create a provider and test it.
        $this->test_capability_map_integration('view_draft_in_dashboard_application_user', $user1_ctx->id);
    }

    /**
     * Covers integration with view_in_dashboard_pending_application_user capability map.
     *
     * @covers ::build_query
     */
    public function test_capability_check_view_in_dashboard_pending_application_user() {
        $this->setAdminUser();
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity1 = new user($user1->id);
        $user2 = $this->getDataGenerator()->create_user();
        $user_entity2 = new user($user2->id);

        // Create stage2 level2 for our approver.
        $workflow_model = workflow::load_by_entity($workflow);
        $workflow_version = $workflow_model->latest_version;
        $stage1 = $workflow_version->stages->first();
        $stage2 = $workflow_version->get_next_stage($stage1->id);
        $level1 = $stage2->approval_levels->first();
        $level2 = $this->generator()->create_approval_level($stage2->id, 'Level 2', 2);

        // Create a level2 approver.
        $approver = new user($this->getDataGenerator()->create_user()->id);
        $assignment_approver_go = new assignment_approver_generator_object(
            $assignment->id,
            $level2->id,
            user_approver_type::get_code(),
            $approver->id
        );
        $assignment_approver = $this->generator()->create_assignment_approver($assignment_approver_go);

        // Remove the auto-assigned trainer role if present.
        $approver_role_id = builder::table('role')->where('shortname', 'approvalworkflowapprover')->value('id');
        $assignment_model = assignment_model::load_by_entity($assignment);
        role_unassign($approver_role_id, $approver->id, $assignment_model->contextid);

        // Create two applications for each user on the same workflow, submitted.
        $form_data = form_data::from_json('{"agency_code": 25}');
        foreach (['u1' => $user_entity1, 'u2' => $user_entity2] as $ux => $user) {
            foreach (['l1', 'l2'] as $lx) {
                $this->setUser($user);
                $app = $this->create_application($workflow, $assignment, $user);
                $submission_entity = $this->generator()->create_application_submission(
                    $app->id,
                    $user->id,
                    $app->current_state->get_stage_id(),
                    $form_data
                );
                $submission = application_submission::load_by_entity($submission_entity);
                submit::execute($app, $user->id);
                $app->refresh(true);

                // Create $pending_lx_ux.
                ${'pending_' . $lx . '_' . $ux} = $app;
                unset($app);
            }
        }

        // Move the '_l2_' applications to Level 2.
        approve::execute($pending_l2_u1, get_admin()->id);
        approve::execute($pending_l2_u2, get_admin()->id);

        // Preventing the capability in user1 context will cause user1's pending application to drop out.
        $user1_ctx = context_user::instance($user1->id);

        // Create a provider and test it.
        $this->test_capability_map_integration('view_in_dashboard_pending_application_user', $user1_ctx->id, $approver);
    }

    /**
     * @covers ::sort_query_by_newest_first
     * @covers ::sort_query_by_oldest_first
     */
    public function test_sorting_by_newest_oldest_first() {
        global $DB;

        $this->setAdminUser();
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity1 = new user($user1->id);
        $wf_manager = $this->create_workflow_manager();

        // Create three applications as user1
        $this->setUser($user1);
        $apps = [];
        for ($i = 0; $i < 3; $i++) {
            $apps[$i] = $this->create_submitted_application($workflow, $assignment, $user_entity1);
        }

        // Backdate the 2nd one
        $record = new stdClass();
        $record->id = $apps[1]->id;
        $record->created = $apps[1]->created - 3600;
        $DB->update_record('approval_application', $record);

        // Create a data provider for each sorting.
        $this->set_user_with_capability_maps($wf_manager);
        $newest_provider = (new applications_for_others($wf_manager->id))->sort_by('newest_first');
        $oldest_provider = (new applications_for_others($wf_manager->id))->sort_by('oldest_first');

        // Test newest first
        $applications = $newest_provider->fetch()->get();
        $this->assertEquals(3, $applications->count());
        $this->assertEquals($apps[2]->id, $applications->first()->id);
        $this->assertEquals($apps[1]->id, $applications->last()->id);

        // Test oldest first
        $applications = $oldest_provider->fetch()->get();
        $this->assertEquals(3, $applications->count());
        $this->assertEquals($apps[1]->id, $applications->first()->id);
        $this->assertEquals($apps[2]->id, $applications->last()->id);
    }

    /**
     * @covers ::sort_query_by_submitted
     */
    public function test_sorting_by_submitted() {
        global $DB;

        $this->setAdminUser();
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity1 = new user($user1->id);
        $wf_manager = $this->create_workflow_manager();

        // Create some applications as user1, some draft some submitted to test COALESCE
        $this->setUser($user1);
        $apps = [];
        $apps[0] = $this->create_application($workflow, $assignment, $user_entity1);
        $apps[1] = $this->create_application($workflow, $assignment, $user_entity1);
        $apps[2] = $this->create_application($workflow, $assignment, $user_entity1);

        $submission0 = application_submission::create_or_update($apps[0], $user_entity1->id, form_data::create_empty());
        $submission0->publish($user_entity1->id);
        submit::execute($apps[0], $user_entity1->id);
        $submission1 = application_submission::create_or_update($apps[1], $user_entity1->id, form_data::create_empty());
        $submission1->publish($user_entity1->id);
        submit::execute($apps[1], $user_entity1->id);
        $submission2 = application_submission::create_or_update($apps[2], $user_entity1->id, form_data::create_empty());
        $submission2->publish($user_entity1->id);
        submit::execute($apps[2], $user_entity1->id);

        // Backdate the 1st application to one hour ago.
        $record0 = new stdClass();
        $record0->id = $apps[0]->id;
        $record0->created = $apps[0]->created - 3600;
        $record0->updated = $apps[0]->updated - 3600;
        $DB->update_record('approval_application', $record0);

        // Backdate the 2nd application to created yesterday, submitted 2 hours ago.
        $record1 = new stdClass();
        $record1->id = $apps[1]->id;
        $record1->created = $apps[1]->created - 86400;
        $record1->updated = $apps[1]->updated - 7200;
        $record1->submitted = $apps[1]->submitted - 7200;
        $DB->update_record('approval_application', $record1);

        // Order should be apps[2], apps[0], apps[1]
        $expected_keys = [
            $apps[2]->id,
            $apps[0]->id,
            $apps[1]->id,
        ];

        // Create a data provider for sorting
        $this->set_user_with_capability_maps($wf_manager);
        $provider = (new applications_for_others($wf_manager->id))->sort_by('submitted');
        $applications = $provider->fetch()->get();
        $this->assertEquals(3, $applications->count());
        $this->assertEquals($expected_keys, $applications->keys());
    }

    /**
     * @covers ::sort_query_by_workflow_type_name
     */
    public function test_sorting_by_workflow_type_name() {
        $this->setAdminUser();
        list($workflow1, , $assignment1) = $this->create_workflow_and_assignment('Test');
        list($workflow2, , $assignment2) = $this->create_workflow_and_assignment('Apple');
        list($workflow3, , $assignment3) = $this->create_workflow_and_assignment('Zebra');
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity1 = new user($user1->id);
        $wf_manager = $this->create_workflow_manager();

        // Create some applications as user1
        $this->setUser($user1);
        $apps = [];
        $apps[0] = $this->create_submitted_application($workflow1, $assignment1, $user_entity1);
        $apps[1] = $this->create_submitted_application($workflow2, $assignment2, $user_entity1);
        $apps[2] = $this->create_submitted_application($workflow3, $assignment3, $user_entity1);
        $apps[3] = $this->create_submitted_application($workflow2, $assignment2, $user_entity1);
        $apps[4] = $this->create_submitted_application($workflow1, $assignment1, $user_entity1);

        // Order should be apps[3], apps[1], apps[4], apps[0], apps[2]
        $expected_keys = [
            $apps[3]->id,
            $apps[1]->id,
            $apps[4]->id,
            $apps[0]->id,
            $apps[2]->id,
        ];

        // Create a data provider for sorting.
        $this->set_user_with_capability_maps($wf_manager);
        $provider = (new applications_for_others($wf_manager->id))->sort_by('workflow_type_name');
        $applications = $provider->fetch()->get();
        $this->assertEquals(5, $applications->count());
        $this->assertEqualsCanonicalizing($expected_keys, $applications->keys());
    }

    public function test_sorting_by_applicant_name() {
        $this->setAdminUser();
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user_a = $this->getDataGenerator()->create_user(['firstname' => 'Alice', 'lastname' => 'Zebra']);
        $user_entity_a = new user($user_a->id);
        $user_z = $this->getDataGenerator()->create_user(['firstname' => 'Zephyr', 'lastname' => 'Goat']);
        $user_entity_z = new user($user_z->id);
        $user_b = $this->getDataGenerator()->create_user(['firstname' => 'Alicia', 'lastname' => 'Buffalo']);
        $user_entity_b = new user($user_b->id);
        $wf_manager = $this->create_workflow_manager();

        // Create application for each user
        $apps = [];
        $apps['a'] = $this->create_submitted_application($workflow, $assignment, $user_entity_a);
        $apps['z'] = $this->create_submitted_application($workflow, $assignment, $user_entity_z);
        $apps['b'] = $this->create_submitted_application($workflow, $assignment, $user_entity_b);
        $apps['z2'] = $this->create_submitted_application($workflow, $assignment, $user_entity_z);

        // Order should be apps[a], apps[b], apps[z], apps[z2]
        $expected_keys = [
            $apps['a']->id,
            $apps['b']->id,
            $apps['z']->id,
            $apps['z2']->id,
        ];

        // Test sorting.
        $this->set_user_with_capability_maps($wf_manager);
        $provider = (new applications_for_others($wf_manager->id))->sort_by('applicant_name');
        $applications = $provider->fetch()->get();
        $this->assertEquals(4, $applications->count());
        $this->assertEqualsCanonicalizing($expected_keys, $applications->keys());
    }

    /**
     * @return array title or id_number
     */
    public function data_title_or_id_number(): array {
        return [['title'], ['id_number']];
    }

    /**
     * @covers ::sort_query_by_title
     * @covers ::sort_query_by_id_number
     * @dataProvider data_title_or_id_number
     */
    public function test_sort_query_by_title_or_id_number(string $field) {
        $this->setAdminUser();
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user = new user($this->getDataGenerator()->create_user());
        $this->setUser($user);
        $app1 = $this->create_submitted_application($workflow, $assignment, $user);
        $app2 = $this->create_submitted_application($workflow, $assignment, $user);
        $app3 = $this->create_submitted_application($workflow, $assignment, $user);
        builder::table('approval_application')->where('id', $app1->id)->update([$field => 'P']);
        builder::table('approval_application')->where('id', $app2->id)->update([$field => 'p']);
        builder::table('approval_application')->where('id', $app3->id)->update([$field => 'A']);
        $wf_manager = $this->create_workflow_manager();
        $this->set_user_with_capability_maps($wf_manager);
        $provider = (new applications_for_others($wf_manager->id))->sort_by($field);
        $applications = $provider->fetch()->get();
        $expected = [$app3->id, $app2->id, $app1->id];
        $this->assertEquals($expected, $applications->keys());
    }

    /**
     * @covers ::filter_query_by_application_id
     * @covers \mod_approval\data_provider\application\filter\application_id::apply
     */
    public function test_filter_by_application_id() {
        $this->setAdminUser();
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity1 = new user($user1->id);
        $wf_manager = $this->create_workflow_manager();

        // Create three applications as user1
        $this->setUser($user1);
        $apps = [];
        for ($i = 0; $i < 5; $i++) {
            $apps[$i] = $this->create_submitted_application($workflow, $assignment, $user_entity1);
        }

        // Test with one ID.
        $this->set_user_with_capability_maps($wf_manager);
        $provider = new applications_for_others($wf_manager->id);
        $provider->add_filters(['application_id' => $apps[1]->id]);
        $applications = $provider->fetch()->get();
        $this->assertEquals(1, $applications->count());
        $this->assertEquals($apps[1]->id, $applications->first()->id);

        // Test with two IDs.
        $provider = new applications_for_others($wf_manager->id);
        $provider->add_filters(['application_id' => [$apps[1]->id, $apps[4]->id]]);
        $applications = $provider->fetch()->get();
        $this->assertEquals(2, $applications->count());
        $this->assertEquals($apps[1]->id, $applications->first()->id);
        $this->assertEquals($apps[4]->id, $applications->last()->id);
    }

    /**
     * @covers ::filter_query_by_workflow_type_name
     * @covers \mod_approval\data_provider\application\filter\workflow_type_id::apply
     */
    public function test_filter_by_workflow_type_name() {
        $this->setAdminUser();
        list($workflow_a, , $assignment_a) = $this->create_workflow_and_assignment('Apples');
        list($workflow_b, , $assignment_b) = $this->create_workflow_and_assignment('Bananas');
        list($workflow_c, , $assignment_c) = $this->create_workflow_and_assignment('Apples');
        $apples = $workflow_a->workflow_type;
        $bananas = $workflow_b->workflow_type;
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity1 = new user($user1->id);
        $wf_manager = $this->create_workflow_manager();

        // As user 1, create two Apples applications, and one Bananas
        $this->setUser($user1);
        $apps = [];
        $apps[0] = $this->create_submitted_application($workflow_a, $assignment_a, $user_entity1);
        $apps[1] = $this->create_submitted_application($workflow_b, $assignment_b, $user_entity1);
        $apps[2] = $this->create_submitted_application($workflow_c, $assignment_c, $user_entity1);

        $this->set_user_with_capability_maps($wf_manager);
        $provider = new applications_for_others($wf_manager->id);
        $provider->add_filters(['workflow_type_name' => $apples->name]);
        $applications = $provider->fetch()->get();
        $this->assertEquals(2, $applications->count());
        $expected_keys = [$apps[0]->id, $apps[2]->id];
        $this->assertEqualsCanonicalizing($expected_keys, $applications->keys());

        $provider = new applications_for_others($wf_manager->id);
        $provider->add_filters(['workflow_type_name' => $bananas->name]);
        $applications = $provider->fetch()->get();
        $this->assertEquals(1, $applications->count());
        $expected_keys = [$apps[1]->id];
        $this->assertEqualsCanonicalizing($expected_keys, $applications->keys());

        // Test no match
        $this->create_workflow_and_assignment('Oranges');
        $provider = new applications_for_others($wf_manager->id);
        $provider->add_filters(['workflow_type_name' => 'Oranges']);
        $applications = $provider->fetch()->get();
        $this->assertEquals(0, $applications->count());

        // Test with invalid value
        $provider = new applications_for_others($wf_manager->id);
        $provider->add_filters(['workflow_type_name' => 'Feijoas']);
        self::expectException(invalid_parameter_exception::class);
        self::expectExceptionMessage('No workflow_type by that name');
        $provider->fetch()->get();
    }

    /**
     * @covers ::filter_query_by_overall_progress
     * @covers \mod_approval\data_provider\application\filter\overall_progress::apply
     */
    public function test_filter_by_overall_progress() {
        $this->setAdminUser();
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity1 = new user($user1->id);
        $wf_manager = $this->create_workflow_manager();

        // Create 5 applications as user1
        $this->setUser($user1);
        $apps = [];
        for ($i = 0; $i < 5; $i++) {
            $apps[$i] = $this->create_application($workflow, $assignment, $user_entity1);
            $submission = application_submission::create_or_update($apps[$i], $user1->id, form_data::create_empty());
            $submission->publish($user1->id);
            submit::execute($apps[$i], $user1->id);
        }
        // Create a draft.
        $apps[] = $this->create_application($workflow, $assignment, $user_entity1);

        // Test with the DRAFT filter -- you should NEVER see draft applications for another user.
        $this->set_user_with_capability_maps($wf_manager);
        $provider = new applications_for_others($wf_manager->id);
        $provider->add_filters(['overall_progress' => ["DRAFT"]]);
        $applications = $provider->fetch()->get();
        $this->assertEquals(0, $applications->count());

        // Test with the IN_PROGRESS filter.
        $provider = new applications_for_others($wf_manager->id);
        $provider->add_filters(['overall_progress' => ["IN_PROGRESS"]]);
        $applications = $provider->fetch()->get();
        $this->assertEquals(5, $applications->count());
        $this->assertEquals($apps[1]->current_state, $applications->first()->current_state);

        // Withdraw one application.
        withdraw_in_approvals::execute($apps[1], $user1->id);

        // Mark another as completed.
        $final_stage = $apps[2]->get_next_stage();
        $apps[2]->set_current_state(new application_state($final_stage->id));

        // Mark another as rejected.
        reject::execute($apps[3], $user1->id);

        // Test with the IN_PROGRESS filter again.
        $provider = new applications_for_others($wf_manager->id);
        $provider->add_filters(['overall_progress' => ["IN_PROGRESS"]]);
        $applications = $provider->fetch()->get();
        $this->assertEquals(2, $applications->count());

        // Test with the REJECTED filter.
        $provider = new applications_for_others($wf_manager->id);
        $provider->add_filters(['overall_progress' => ["REJECTED"]]);
        $applications = $provider->fetch()->get();
        $this->assertEquals(1, $applications->count());

        // Test with the WITHDRAWN filter.
        $provider = new applications_for_others($wf_manager->id);
        $provider->add_filters(['overall_progress' => ["WITHDRAWN"]]);
        $applications = $provider->fetch()->get();
        $this->assertEquals(1, $applications->count());

        // Test with the invalid filter
        $provider = new applications_for_others($wf_manager->id);
        $provider->add_filters(['overall_progress' => ["APPROVED"]]);
        self::expectException(invalid_parameter_exception::class);
        self::expectExceptionMessage('invalid value(s): APPROVED');
        $provider->fetch()->get();
    }

    /**
     * @covers ::filter_query_by_your_progress
     * @covers \mod_approval\data_provider\application\filter\your_progress::apply
     */
    public function test_filter_by_your_progress() {
        $this->setAdminUser();
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $workflow_version = workflow_version::load_latest_by_workflow_id($workflow->id);
        $stage1 = $workflow_version->stages->first();
        $stage2 = $workflow_version->get_next_stage($stage1->id);
        $level1 = $stage2->approval_levels->first();
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity1 = new user($user1->id);
        $approver = new user($this->create_workflow_manager()->id);
        $approver_go = new assignment_approver_generator_object(
            $assignment->id,
            $level1->id,
            user_approver_type::TYPE_IDENTIFIER,
            $approver->id
        );
        $this->generator()->create_assignment_approver($approver_go);

        $approver2 = new user($this->create_workflow_manager()->id);
        $approver_go->identifier = $approver2->id;
        $this->generator()->create_assignment_approver($approver_go);


        // Create 5 applications as user1
        $this->setUser($user1);
        $form_data = form_data::from_json('{"agency_code": 25}');
        $apps = [];
        for ($i = 0; $i < 5; $i++) {
            $apps[$i] = $this->create_submitted_application($workflow, $assignment, $user_entity1);
            application_submission::create_or_update($apps[$i], $user_entity1->id, $form_data);
        }

        // Add some actions.
        $this->set_user_with_capability_maps($approver);
        approve::execute($apps[0], $approver->id);
        reject::execute($apps[1], $approver->id);
        approve::execute($apps[3], $approver->id);

        // Test approved for approver1.
        $provider = new applications_for_others($approver->id);
        $provider->add_filters(['your_progress' => "APPROVED"]);
        $expected_keys = [$apps[0]->id, $apps[3]->id];
        $applications = $provider->fetch()->get();
        $this->assertCount(2, $applications);
        $this->assertEquals($expected_keys, $applications->keys());
        $this->assertEquals('APPROVED', $applications->first()->your_progress);
        // And for approver 2:
        $this->set_user_with_capability_maps($approver2);
        $provider = new applications_for_others($approver2->id);
        $provider->add_filters(['your_progress' => "APPROVED"]);
        $applications = $provider->fetch()->get();
        $this->assertCount(0, $applications);

        // Test rejected for approver1.
        $this->setUser($approver);
        $provider = new applications_for_others($approver->id);
        $provider->add_filters(['your_progress' => "REJECTED"]);
        $expected_keys = [$apps[1]->id];
        $applications = $provider->fetch()->get();
        $this->assertCount(1, $applications);
        $this->assertEquals($expected_keys, $applications->keys());
        $this->assertEquals('REJECTED', $applications->first()->your_progress);
        // And for approver 2.
        $this->setUser($approver2);
        $provider = new applications_for_others($approver2->id);
        $provider->add_filters(['your_progress' => "REJECTED"]);
        $applications = $provider->fetch()->get();
        $this->assertCount(0, $applications);

        // Test pending for approver1
        $this->setUser($approver);
        $provider = new applications_for_others($approver->id);
        $provider->add_filters(['your_progress' => "PENDING"]);
        $expected_keys = [$apps[2]->id, $apps[4]->id];
        $applications = $provider->fetch()->get();
        $this->assertCount(2, $applications);
        $this->assertEquals($expected_keys, $applications->keys());
        $this->assertEquals('PENDING', $applications->first()->your_progress);
        // And for approver2.
        $this->setUser($approver2);
        $provider = new applications_for_others($approver2->id);
        $provider->add_filters(['your_progress' => "PENDING"]);
        $expected_keys = [$apps[2]->id, $apps[4]->id];
        $applications = $provider->fetch()->get();
        $this->assertCount(2, $applications);
        $this->assertEquals($expected_keys, $applications->keys());
        $this->assertEquals('PENDING', $applications->first()->your_progress);

        // Test n/a for approver1
        $this->setUser($approver);
        $provider = new applications_for_others($approver->id);
        $provider->add_filters(['your_progress' => "NA"]);
        $applications = $provider->fetch()->get();
        $this->assertCount(0, $applications);
        // And for approver2
        $this->setUser($approver2);
        $provider = new applications_for_others($approver2->id);
        $provider->add_filters(['your_progress' => "NA"]);
        $expected_keys = [$apps[0]->id, $apps[1]->id, $apps[3]->id];
        $applications = $provider->fetch()->get();
        $this->assertCount(3, $applications);
        $this->assertEquals($expected_keys, $applications->keys());
        $this->assertEquals('NA', $applications->first()->your_progress);
    }

    /**
     * @covers ::filter_query_by_your_progress
     * @covers \mod_approval\data_provider\application\filter\your_progress::apply
     */
    public function test_filter_by_your_progress_for_manager() {
        $this->setAdminUser();
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $workflow_version = workflow_version::load_latest_by_workflow_id($workflow->id);
        $stage1 = $workflow_version->stages->first();
        $stage2 = $workflow_version->get_next_stage($stage1->id);
        $level1 = $stage2->approval_levels->first();
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity1 = new user($user1->id);
        $manager_entity = \totara_core\entity\relationship::repository()->where('idnumber', '=', 'manager')->one(true);
        $approver_go = new assignment_approver_generator_object(
            $assignment->id,
            $level1->id,
            relationship::TYPE_IDENTIFIER,
            $manager_entity->id
        );
        $this->generator()->create_assignment_approver($approver_go);

        $manager1 = new user($this->getDataGenerator()->create_user());
        $manager2 = new user($this->getDataGenerator()->create_user());
        $tempmanager = new user($this->getDataGenerator()->create_user());
        job_assignment::create_default($user1->id, [
            'managerjaid' => job_assignment::create_default($manager1->id)->id,
        ]);
        $manager2_ja = job_assignment::create_default($user1->id, [
            'managerjaid' => job_assignment::create_default($manager2->id)->id,
            'tempmanagerjaid' => job_assignment::create_default($tempmanager->id)->id,
            'tempmanagerexpirydate' => time() + DAYSECS,
        ]);

        // Create 5 applications as user1, on job assignment 2
        $this->setUser($user1);
        $form_data = form_data::from_json('{"agency_code": 25}');
        $apps = [];
        for ($i = 0; $i < 5; $i++) {
            $apps[$i] = $this->create_submitted_application($workflow, $assignment, $user_entity1, $manager2_ja->id);
            application_submission::create_or_update($apps[$i], $user_entity1->id, $form_data);
        }

        // Add some actions.
        $this->set_user_with_capability_maps($manager2);
        approve::execute($apps[0], $manager2->id);
        reject::execute($apps[1], $manager2->id);
        approve::execute($apps[3], $manager2->id);

        // Test approved for manager2
        $provider = new applications_for_others($manager2->id);
        $provider->add_filters(['your_progress' => "APPROVED"]);
        $expected_keys = [$apps[0]->id, $apps[3]->id];
        $applications = $provider->fetch()->get();
        $this->assertCount(2, $applications);
        $this->assertEquals($expected_keys, $applications->keys());
        $this->assertEquals('APPROVED', $applications->first()->your_progress);
        // And for manager1
        $this->set_user_with_capability_maps($manager1);
        $provider = new applications_for_others($manager1->id);
        $provider->add_filters(['your_progress' => "APPROVED"]);
        $applications = $provider->fetch()->get();
        $this->assertCount(0, $applications);
        // And for tempmanager
        $this->set_user_with_capability_maps($tempmanager);
        $provider = new applications_for_others($tempmanager->id);
        $provider->add_filters(['your_progress' => "APPROVED"]);
        $applications = $provider->fetch()->get();
        $this->assertCount(0, $applications);

        // Test rejected for manager2.
        $this->setUser($manager2);
        $provider = new applications_for_others($manager2->id);
        $provider->add_filters(['your_progress' => "REJECTED"]);
        $expected_keys = [$apps[1]->id];
        $applications = $provider->fetch()->get();
        $this->assertCount(1, $applications);
        $this->assertEquals($expected_keys, $applications->keys());
        $this->assertEquals('REJECTED', $applications->first()->your_progress);
        // And for manager1
        $this->setUser($manager1);
        $provider = new applications_for_others($manager1->id);
        $provider->add_filters(['your_progress' => "REJECTED"]);
        $applications = $provider->fetch()->get();
        $this->assertCount(0, $applications);
        // And for tempmanager
        $this->setUser($tempmanager);
        $provider = new applications_for_others($tempmanager->id);
        $provider->add_filters(['your_progress' => "REJECTED"]);
        $applications = $provider->fetch()->get();
        $this->assertCount(0, $applications);

        // Test pending for manager2
        $this->setUser($manager2);
        $provider = new applications_for_others($manager2->id);
        $provider->add_filters(['your_progress' => "PENDING"]);
        $expected_keys = [$apps[2]->id, $apps[4]->id];
        $applications = $provider->fetch()->get();
        $this->assertCount(2, $applications);
        $this->assertEquals($expected_keys, $applications->keys());
        $this->assertEquals('PENDING', $applications->first()->your_progress);
        // And for manager1 (who should not see anything pending because wrong job assignment)
        $this->setUser($manager1);
        $provider = new applications_for_others($manager1->id);
        $provider->add_filters(['your_progress' => "PENDING"]);
        $applications = $provider->fetch()->get();
        $this->assertCount(0, $applications);
        // And for tempmanager.
        $this->setUser($tempmanager);
        $provider = new applications_for_others($tempmanager->id);
        $provider->add_filters(['your_progress' => "PENDING"]);
        $expected_keys = [$apps[2]->id, $apps[4]->id];
        $applications = $provider->fetch()->get();
        $this->assertCount(2, $applications);
        $this->assertEquals($expected_keys, $applications->keys());
        $this->assertEquals('PENDING', $applications->first()->your_progress);

        // Test n/a for manager2
        $this->setUser($manager2);
        $provider = new applications_for_others($manager2->id);
        $provider->add_filters(['your_progress' => "NA"]);
        $applications = $provider->fetch()->get();
        $this->assertCount(0, $applications);
        // And for manager1
        $this->setUser($manager1);
        $provider = new applications_for_others($manager1->id);
        $provider->add_filters(['your_progress' => "NA"]);
        $expected_keys = [$apps[0]->id, $apps[1]->id, $apps[2]->id, $apps[3]->id, $apps[4]->id];
        $applications = $provider->fetch()->get();
        $this->assertCount(5, $applications);
        $this->assertEquals($expected_keys, $applications->keys());
        $this->assertEquals('NA', $applications->first()->your_progress);
        // And for tempmanager
        $this->setUser($tempmanager);
        $provider = new applications_for_others($tempmanager->id);
        $provider->add_filters(['your_progress' => "NA"]);
        $applications = $provider->fetch()->get();
        $this->assertCount(3, $applications);

        // Check the applications your_progress field to make sure the values are correct.
        $applications->map(function ($application) {
            $this->assertEquals('NA', $application->your_progress);
        });
    }

    public function test_filter_by_your_progress_with_override_assignments() {
        $this->setAdminUser();
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment('Override Testing', true, false);
        $workflow_model = workflow::load_by_entity($workflow);
        $workflow_version = $workflow_model->latest_version;
        $stage1 = $workflow_version->stages->first();
        $stage2 = $workflow_version->get_next_stage($stage1->id);
        $level1 = $stage2->approval_levels->first();
        $level2 = $stage2->add_approval_level('Level 2');
        $workflow_model->publish($workflow_version);

        // Set level 1 approver to manager. Use models so that approver aliases get filled in for overrides.
        $manager_entity = \totara_core\entity\relationship::repository()->where('idnumber', '=', 'manager')->one(true);
        assignment_approver_model::create(
            assignment_model::load_by_id($assignment->id),
            workflow_stage_approval_level_model::load_by_id($level1->id),
            relationship::TYPE_IDENTIFIER,
            $manager_entity->id
        );

        // Set level 2 approver to individual for default assignment (agency)
        $agency_approver_user = $this->getDataGenerator()->create_user();
        $agency_approver_entity = new user($agency_approver_user->id);
        assignment_approver_model::create(
            assignment_model::load_by_id($assignment->id),
            workflow_stage_approval_level_model::load_by_id($level2->id),
            user_approver_type::TYPE_IDENTIFIER,
            $agency_approver_entity->id
        );

        // Set level 2 approver to individual for program_a assignment override.
        $program_a_approver_user = $this->getDataGenerator()->create_user();
        $program_a_approver_entity = new user($program_a_approver_user->id);
        /** @var assignment_entity $program_a_assignment */
        $program_a_assignment = assignment_entity::repository()
            ->where('assignment_type', '=', assignment_type\organisation::get_code())
            ->where('assignment_identifier', '=', $framework->agency->subagency_a->program_a->id)
            ->one();
        assignment_approver_model::create(
            assignment_model::load_by_entity($program_a_assignment),
            workflow_stage_approval_level_model::load_by_id($level2->id),
            user_approver_type::TYPE_IDENTIFIER,
            $program_a_approver_entity->id
        );

        // Create an agency applicant.
        $agency_applicant_user = $this->getDataGenerator()->create_user();
        $agency_applicant_entity = new user($agency_applicant_user->id);

        // Create a program_a applicant.
        $program_a_applicant_user = $this->getDataGenerator()->create_user();
        $program_a_applicant_entity = new user($agency_applicant_user->id);

        // Create a manager and tempmanager for agency applicant.
        $manager1 = new user($this->getDataGenerator()->create_user());
        $tempmanager = new user($this->getDataGenerator()->create_user());
        $manager1_ja = job_assignment::create_default($agency_applicant_user->id, [
            'managerjaid' => job_assignment::create_default($manager1->id)->id,
            'tempmanagerjaid' => job_assignment::create_default($tempmanager->id)->id,
            'tempmanagerexpirydate' => time() + DAYSECS
        ]);

        // Create just a manager (same one) for program_a applicant.
        $manager2_ja = job_assignment::create_default($program_a_applicant_user->id, [
            'managerjaid' => job_assignment::create_default($manager1->id)->id
        ]);

        // Create 5 applications as each user
        $form_data = form_data::from_json('{"agency_code": 25}');
        $this->setUser($agency_applicant_user);
        $agency_apps = [];
        for ($i = 0; $i < 7; $i++) {
            $agency_apps[$i] = $this->create_application(
                $workflow,
                $assignment,
                $agency_applicant_entity,
                $manager1_ja->id
            );
            $submission = application_submission::create_or_update(
                $agency_apps[$i],
                $agency_applicant_entity->id,
                $form_data
            );
            $submission->publish($agency_applicant_entity->id);
            submit::execute($agency_apps[$i], $agency_applicant_entity->id);
        }
        $this->setUser($program_a_applicant_user);
        $program_a_apps = [];
        for ($i = 0; $i < 7; $i++) {
            $program_a_apps[$i] = $this->create_application(
                $workflow,
                $program_a_assignment,
                $program_a_applicant_entity,
                $manager2_ja->id
            );
            $submission = application_submission::create_or_update(
                $program_a_apps[$i],
                $program_a_applicant_entity->id,
                $form_data
            );
            $submission->publish($program_a_applicant_entity->id);
            submit::execute($program_a_apps[$i], $program_a_applicant_entity->id);
        }

        // Set up actions.
        approve::execute($agency_apps[1], $manager1->id);
        approve::execute($agency_apps[2], $manager1->id);
        approve::execute($agency_apps[2], $agency_approver_entity->id);
        approve::execute($agency_apps[3], $manager1->id);
        reject::execute($agency_apps[5], $manager1->id);
        withdraw_in_approvals::execute($agency_apps[6], $agency_applicant_entity->id);

        approve::execute($program_a_apps[1], $manager1->id);
        approve::execute($program_a_apps[2], $manager1->id);
        approve::execute($program_a_apps[2], $program_a_approver_entity->id);
        approve::execute($program_a_apps[3], $manager1->id);
        reject::execute($program_a_apps[5], $manager1->id);
        withdraw_in_approvals::execute($program_a_apps[6], $program_a_applicant_entity->id);

        /**
         * | app | manager  | approver |
         * | 0   | pending  | NA       |
         * | 1   | approved | pending  |
         * | 2   | approved | approved |
         * | 3   | approved | pending  |
         * | 4   | pending  | NA       |
         * | 5   | rejected | NA       |
         * | 6   | NA       | NA       |
         */

        // Test approved for manager (includes both assignments)
        $this->set_user_with_capability_maps($manager1);
        $provider = new applications_for_others($manager1->id);
        $provider->add_filters(['your_progress' => "APPROVED"]);
        $expected_keys = [
            $agency_apps[1]->id,
            $agency_apps[2]->id,
            $agency_apps[3]->id,
            $program_a_apps[1]->id,
            $program_a_apps[2]->id,
            $program_a_apps[3]->id
        ];
        $applications = $provider->fetch()->get();
        $this->assertEquals($expected_keys, $applications->keys());
        $this->assertEquals('APPROVED', $applications->first()->your_progress);

        // Test approved for agency approver
        $this->set_user_with_capability_maps($agency_approver_user);
        $provider = new applications_for_others($agency_approver_user->id);
        $provider->add_filters(['your_progress' => "APPROVED"]);
        $expected_keys = [$agency_apps[2]->id];
        $applications = $provider->fetch()->get();
        $this->assertEquals($expected_keys, $applications->keys());
        $this->assertEquals('APPROVED', $applications->first()->your_progress);

        // Test approved for program_a approver
        $this->set_user_with_capability_maps($program_a_approver_user);
        $provider = new applications_for_others($program_a_approver_user->id);
        $provider->add_filters(['your_progress' => "APPROVED"]);
        $expected_keys = [$program_a_apps[2]->id];
        $applications = $provider->fetch()->get();
        $this->assertEquals($expected_keys, $applications->keys());
        $this->assertEquals('APPROVED', $applications->first()->your_progress);

        // Test rejected for manager (includes both assignments)
        $this->setUser($manager1);
        $provider = new applications_for_others($manager1->id);
        $provider->add_filters(['your_progress' => "REJECTED"]);
        $expected_keys = [$agency_apps[5]->id, $program_a_apps[5]->id];
        $applications = $provider->fetch()->get();
        $this->assertEquals($expected_keys, $applications->keys());
        $this->assertEquals('REJECTED', $applications->first()->your_progress);

        // Test rejected for agency approver
        $this->setUser($agency_approver_user);
        $provider = new applications_for_others($agency_approver_user->id);
        $provider->add_filters(['your_progress' => "REJECTED"]);
        $expected_keys = [];
        $applications = $provider->fetch()->get();
        $this->assertEquals($expected_keys, $applications->keys());

        // Test rejected for program_a approver
        $this->setUser($program_a_approver_user);
        $provider = new applications_for_others($program_a_approver_user->id);
        $provider->add_filters(['your_progress' => "REJECTED"]);
        $expected_keys = [];
        $applications = $provider->fetch()->get();
        $this->assertEquals($expected_keys, $applications->keys());

        // Test pending for manager (includes both assignments)
        $this->setUser($manager1);
        $provider = new applications_for_others($manager1->id);
        $provider->add_filters(['your_progress' => "PENDING"]);
        $expected_keys = [$agency_apps[0]->id, $agency_apps[4]->id, $program_a_apps[0]->id, $program_a_apps[4]->id];
        $applications = $provider->fetch()->get();
        $this->assertEquals($expected_keys, $applications->keys());
        $this->assertEquals('PENDING', $applications->first()->your_progress);

        // Test pending for agency approver
        $this->setUser($agency_approver_user);
        $provider = new applications_for_others($agency_approver_user->id);
        $provider->add_filters(['your_progress' => "PENDING"]);
        $expected_keys = [$agency_apps[1]->id, $agency_apps[3]->id];
        $applications = $provider->fetch()->get();
        $this->assertEquals($expected_keys, $applications->keys());
        $this->assertEquals('PENDING', $applications->first()->your_progress);

        // Test pending for program_a approver
        $this->setUser($program_a_approver_user);
        $provider = new applications_for_others($program_a_approver_user->id);
        $provider->add_filters(['your_progress' => "PENDING"]);
        $expected_keys = [$program_a_apps[1]->id, $program_a_apps[3]->id];
        $applications = $provider->fetch()->get();
        $this->assertEquals($expected_keys, $applications->keys());
        $this->assertEquals('PENDING', $applications->first()->your_progress);

        // Test NA for manager (includes both assignments)
        $this->setUser($manager1);
        $provider = new applications_for_others($manager1->id);
        $provider->add_filters(['your_progress' => "NA"]);
        $expected_keys = [$agency_apps[6]->id, $program_a_apps[6]->id];
        $applications = $provider->fetch()->get();
        $this->assertEquals($expected_keys, $applications->keys());
        $this->assertEquals('NA', $applications->first()->your_progress);

        // Test NA for agency approver
        $this->setUser($agency_approver_user);
        $provider = new applications_for_others($agency_approver_user->id);
        $provider->add_filters(['your_progress' => "NA"]);
        $expected_keys = [$agency_apps[0]->id, $agency_apps[4]->id, $agency_apps[5]->id, $agency_apps[6]->id];
        $applications = $provider->fetch()->get();
        $this->assertEquals($expected_keys, $applications->keys());
        $this->assertEquals('NA', $applications->first()->your_progress);

        // Test NA for program_a approver
        $this->setUser($program_a_approver_user);
        $provider = new applications_for_others($program_a_approver_user->id);
        $provider->add_filters(['your_progress' => "NA"]);
        $expected_keys = [$program_a_apps[0]->id, $program_a_apps[4]->id, $program_a_apps[5]->id, $program_a_apps[6]->id];
        $applications = $provider->fetch()->get();
        $this->assertEquals($expected_keys, $applications->keys());
        $this->assertEquals('NA', $applications->first()->your_progress);
    }

    /**
     * @covers ::filter_query_by_applicant_name
     * @covers \mod_approval\data_provider\application\filter\applicant_name::apply
     */
    public function test_filter_by_applicant_name() {
        $this->setAdminUser();
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user_a = $this->getDataGenerator()->create_user(['firstname' => 'Alice', 'lastname' => 'Zebra']);
        $user_entity_a = new user($user_a->id);
        $user_z = $this->getDataGenerator()->create_user(['firstname' => 'Zephyr', 'lastname' => 'Goat']);
        $user_entity_z = new user($user_z->id);
        $user_b = $this->getDataGenerator()->create_user(['firstname' => 'Alicia', 'lastname' => 'Buffalo']);
        $user_entity_b = new user($user_b->id);
        $wf_manager = $this->create_workflow_manager();

        // Create application for each user
        $this->create_submitted_application($workflow, $assignment, $user_entity_a);
        $this->create_submitted_application($workflow, $assignment, $user_entity_z);
        $this->create_submitted_application($workflow, $assignment, $user_entity_b);

        // Test with the filter
        $this->set_user_with_capability_maps($wf_manager);
        $provider = new applications_for_others($wf_manager->id);
        $provider->add_filters(['applicant_name' => "alic"]);
        $applications = $provider->fetch()->get();
        $this->assertEquals(2, $applications->count());

        $provider = new applications_for_others($wf_manager->id);
        $provider->add_filters(['applicant_name' => "alice"]);
        $applications = $provider->fetch()->get();
        $this->assertEquals(1, $applications->count());
        $this->assertEquals($user_a->id, $applications->first()->user_id);

        // Test no match
        $provider = new applications_for_others($wf_manager->id);
        $provider->add_filters(['applicant_name' => "Gnu"]);
        $applications = $provider->fetch()->get();
        $this->assertEquals(0, $applications->count());

        // Test with empty string.
        $provider = new applications_for_others($wf_manager->id);
        $provider->add_filters(['applicant_name' => ""]);
        self::expectException(coding_exception::class);
        self::expectExceptionMessage('applicant fullname filter must have a string for value');
        $provider->fetch()->get();
    }

    /**
     * @covers ::get_paginator
     */
    public function test_paged_results() {
        $this->setAdminUser();
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity1 = new user($user1->id);
        $wf_manager = $this->create_workflow_manager();

        // Create 12 applications as user1
        $this->setUser($user1);
        $apps = [];
        for ($i = 0; $i < 12; $i++) {
            $apps[$i] = $this->create_submitted_application($workflow, $assignment, $user_entity1);
        }

        // Set a page size of 5
        $item_counts = [5, 5, 2];
        $expected_ids = [
            0 => [$apps[0]->id, $apps[1]->id, $apps[2]->id, $apps[3]->id, $apps[4]->id],
            1 => [$apps[5]->id, $apps[6]->id, $apps[7]->id, $apps[8]->id, $apps[9]->id],
            2 => [$apps[10]->id, $apps[11]->id]
        ];
        $this->set_user_with_capability_maps($wf_manager);
        $cursor = cursor::create()->set_limit(5);
        for ($i = 0; $i < 3; $i++) {
            $paginator = (new applications_for_others($wf_manager->id))
                ->get_paginator($cursor);

            $items = $paginator->get_items();
            $this->assertCount($item_counts[$i], $items);
            $actual_ids = $items->pluck('id');
            // Order should be the same
            $this->assertSame($expected_ids[$i], $actual_ids);

            $cursor = $paginator->get_next_cursor();
        }

        // Set a page size of 4
        $item_counts = [4, 4, 4];
        $expected_ids = [
            0 => [$apps[0]->id, $apps[1]->id, $apps[2]->id, $apps[3]->id],
            1 => [$apps[4]->id, $apps[5]->id, $apps[6]->id, $apps[7]->id],
            2 => [$apps[8]->id, $apps[9]->id, $apps[10]->id, $apps[11]->id]
        ];
        $cursor = cursor::create()->set_limit(4);
        for ($i = 0; $i < 3; $i++) {
            $paginator = (new applications_for_others($wf_manager->id))
                ->get_paginator($cursor);

            $items = $paginator->get_items();
            $this->assertCount($item_counts[$i], $items);
            $actual_ids = $items->pluck('id');
            // Order should be the same
            $this->assertSame($expected_ids[$i], $actual_ids);

            $cursor = $paginator->get_next_cursor();
        }

        $this->assertNull($cursor);
    }
}
