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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_approval
 */

defined('MOODLE_INTERNAL') || die();

use core\orm\query\builder;
use core_phpunit\testcase;
use mod_approval\entity\workflow\workflow_version;
use mod_approval\interactor\workflow_interactor;
use mod_approval\model\status;
use mod_approval\model\workflow\workflow;
use mod_approval\testing\generator;
use totara_tenant\testing\generator as tenant_generator;

/**
 * @group approval_workflow
 */
class mod_approval_workflow_interactor_testcase extends testcase {

    public function test_workflow_interactor_roles_have_correct_defaults() {
        $generator = generator::instance();
        $workflow = $generator->create_simple_request_workflow();
        $workflow_model = workflow::load_by_entity($workflow);

        // Normal user already has the authenticated user role.
        $user = self::getDataGenerator()->create_user();

        $interactor = workflow_interactor::from_workflow($workflow_model, $user->id);

        self::assertFalse($interactor->has_edit_draft_capability());
        self::assertFalse($interactor->has_edit_active_capability());
        self::assertFalse($interactor->has_activate_capability());
        self::assertFalse($interactor->has_archive_capability());
        self::assertFalse($interactor->has_edit_template_capability());
        self::assertFalse($interactor->has_manage_stages_capability());
        self::assertFalse($interactor->has_manage_formview_capability());
        self::assertFalse($interactor->has_add_approval_level_capability());
        self::assertFalse($interactor->has_reorder_approval_level_capability());
        self::assertFalse($interactor->can_manage_workflow_approvers());
        self::assertFalse($interactor->has_manage_individual_approvers_capability());
        self::assertFalse($interactor->has_manage_relationship_approvers_capability());
        self::assertFalse($interactor->has_manage_assignment_overrides_capability());
        self::assertFalse($interactor->has_manage_transitions_capability());
        self::assertFalse($interactor->has_manage_notifications_capability());
        self::assertFalse($interactor->has_assign_roles_capability());

        // Assign staff manager role in the system context (just using system because it is easy and covers all users).
        $staff_manager = self::getDataGenerator()->create_user();
        $staff_manager_role = builder::table('role')->where('shortname', 'staffmanager')->one();
        role_assign($staff_manager_role->id, $staff_manager->id, context_system::instance());

        $interactor = workflow_interactor::from_workflow($workflow_model, $staff_manager->id);

        self::assertFalse($interactor->has_edit_draft_capability());
        self::assertFalse($interactor->has_edit_active_capability());
        self::assertFalse($interactor->has_activate_capability());
        self::assertFalse($interactor->has_archive_capability());
        self::assertFalse($interactor->has_edit_template_capability());
        self::assertFalse($interactor->has_manage_stages_capability());
        self::assertFalse($interactor->has_manage_formview_capability());
        self::assertFalse($interactor->has_add_approval_level_capability());
        self::assertFalse($interactor->has_reorder_approval_level_capability());
        self::assertFalse($interactor->can_manage_workflow_approvers());
        self::assertFalse($interactor->has_manage_individual_approvers_capability());
        self::assertFalse($interactor->has_manage_relationship_approvers_capability());
        self::assertFalse($interactor->has_manage_assignment_overrides_capability());
        self::assertFalse($interactor->has_manage_transitions_capability());
        self::assertFalse($interactor->has_manage_notifications_capability());
        self::assertFalse($interactor->has_assign_roles_capability());

        // Assign approver role in the system context.
        $approver = self::getDataGenerator()->create_user();
        $approver_role = builder::table('role')->where('shortname', 'approvalworkflowapprover')->one();
        role_assign($approver_role->id, $approver->id, context_system::instance());

        $interactor = workflow_interactor::from_workflow($workflow_model, $approver->id);

        self::assertFalse($interactor->has_edit_draft_capability());
        self::assertFalse($interactor->has_edit_active_capability());
        self::assertFalse($interactor->has_activate_capability());
        self::assertFalse($interactor->has_archive_capability());
        self::assertFalse($interactor->has_edit_template_capability());
        self::assertFalse($interactor->has_manage_stages_capability());
        self::assertFalse($interactor->has_manage_formview_capability());
        self::assertFalse($interactor->has_add_approval_level_capability());
        self::assertFalse($interactor->has_reorder_approval_level_capability());
        self::assertFalse($interactor->can_manage_workflow_approvers());
        self::assertFalse($interactor->has_manage_individual_approvers_capability());
        self::assertFalse($interactor->has_manage_relationship_approvers_capability());
        self::assertFalse($interactor->has_manage_assignment_overrides_capability());
        self::assertFalse($interactor->has_manage_transitions_capability());
        self::assertFalse($interactor->has_manage_notifications_capability());
        self::assertFalse($interactor->has_assign_roles_capability());

        // Assign editing trainer role in the system context.
        $workflow_manager = self::getDataGenerator()->create_user();
        $workflow_manager_role = builder::table('role')->where('shortname', 'approvalworkflowmanager')->one();
        role_assign($workflow_manager_role->id, $workflow_manager->id, context_system::instance());

        $interactor = workflow_interactor::from_workflow($workflow_model, $workflow_manager->id);

        self::assertFalse($interactor->has_edit_draft_capability());
        self::assertFalse($interactor->has_edit_active_capability());
        self::assertFalse($interactor->has_activate_capability());
        self::assertFalse($interactor->has_archive_capability());
        self::assertFalse($interactor->has_edit_template_capability());
        self::assertFalse($interactor->has_manage_stages_capability());
        self::assertFalse($interactor->has_manage_formview_capability());
        self::assertFalse($interactor->has_add_approval_level_capability());
        self::assertFalse($interactor->has_reorder_approval_level_capability());
        self::assertFalse($interactor->can_manage_workflow_approvers());
        self::assertFalse($interactor->has_manage_individual_approvers_capability());
        self::assertFalse($interactor->has_manage_relationship_approvers_capability());
        self::assertFalse($interactor->has_manage_assignment_overrides_capability());
        self::assertFalse($interactor->has_manage_transitions_capability());
        self::assertFalse($interactor->has_manage_notifications_capability());
        self::assertTrue($interactor->has_assign_roles_capability());

        // Assign site manager role in the system context.
        $site_manager = self::getDataGenerator()->create_user();
        $site_manager_role = builder::table('role')->where('shortname', 'manager')->one();
        role_assign($site_manager_role->id, $site_manager->id, context_system::instance());

        $interactor = workflow_interactor::from_workflow($workflow_model, $site_manager->id);

        self::assertTrue($interactor->has_edit_draft_capability());
        self::assertFalse($interactor->has_edit_active_capability());
        self::assertTrue($interactor->has_activate_capability());
        self::assertTrue($interactor->has_archive_capability());
        self::assertTrue($interactor->has_edit_template_capability());
        self::assertTrue($interactor->has_manage_stages_capability());
        self::assertTrue($interactor->has_manage_formview_capability());
        self::assertTrue($interactor->has_add_approval_level_capability());
        self::assertTrue($interactor->has_reorder_approval_level_capability());
        self::assertTrue($interactor->can_manage_workflow_approvers());
        self::assertTrue($interactor->has_manage_individual_approvers_capability());
        self::assertTrue($interactor->has_manage_relationship_approvers_capability());
        self::assertTrue($interactor->has_manage_assignment_overrides_capability());
        self::assertTrue($interactor->has_manage_transitions_capability());
        self::assertTrue($interactor->has_manage_notifications_capability());
        self::assertTrue($interactor->has_assign_roles_capability());

        // Assign tenant domain manager role in the system context.
        $tenant_generator = tenant_generator::instance();
        $tenant_generator->enable_tenants();
        $tenant_manager = self::getDataGenerator()->create_user();
        $tenant_manager_role = builder::table('role')->where('shortname', 'tenantdomainmanager')->one();
        role_assign($tenant_manager_role->id, $tenant_manager->id, context_system::instance());

        $interactor = workflow_interactor::from_workflow($workflow_model, $tenant_manager->id);

        self::assertTrue($interactor->has_edit_draft_capability());
        self::assertFalse($interactor->has_edit_active_capability());
        self::assertTrue($interactor->has_activate_capability());
        self::assertTrue($interactor->has_archive_capability());
        self::assertTrue($interactor->has_edit_template_capability());
        self::assertTrue($interactor->has_manage_stages_capability());
        self::assertTrue($interactor->has_manage_formview_capability());
        self::assertTrue($interactor->has_add_approval_level_capability());
        self::assertTrue($interactor->has_reorder_approval_level_capability());
        self::assertTrue($interactor->can_manage_workflow_approvers());
        self::assertTrue($interactor->has_manage_individual_approvers_capability());
        self::assertTrue($interactor->has_manage_relationship_approvers_capability());
        self::assertTrue($interactor->has_manage_assignment_overrides_capability());
        self::assertTrue($interactor->has_manage_transitions_capability());
        self::assertTrue($interactor->has_manage_notifications_capability());
        self::assertTrue($interactor->has_assign_roles_capability());
    }

    public function test_functions_match_capabilities() {
        $functions_capabilities = [
            'has_edit_draft_capability' => 'edit_draft_workflow',
            'has_edit_active_capability' => 'edit_active_workflow',
            'has_activate_capability' => 'activate_workflow',
            'has_archive_capability' => 'archive_workflow',
            'has_edit_template_capability' => 'edit_workflow_template',
            'has_manage_stages_capability' => 'manage_workflow_stages',
            'has_manage_formview_capability' => 'manage_workflow_form_view',
            'has_add_approval_level_capability' => 'add_workflow_approval_level',
            'has_reorder_approval_level_capability' => 'reorder_workflow_approval_level',
            'has_manage_individual_approvers_capability' => 'manage_individual_workflow_approvers',
            'has_manage_relationship_approvers_capability' => 'manage_relationship_workflow_approvers',
            'has_manage_assignment_overrides_capability' => 'manage_workflow_assignment_overrides',
            'has_manage_transitions_capability' => 'manage_workflow_transitions',
            'has_manage_notifications_capability' => 'manage_workflow_notifications',
        ];

        $generator = generator::instance();
        $workflow = $generator->create_simple_request_workflow();
        $workflow_model = workflow::load_by_entity($workflow);
        $workflow_course_context = context_course::instance($workflow->course_id);
        $user_role = builder::table('role')->where('shortname', 'user')->one();
        $user = self::getDataGenerator()->create_user();

        $interactor = workflow_interactor::from_workflow($workflow_model, $user->id);

        foreach ($functions_capabilities as $function => $capability) {
            // Make sure the role does not contain the cap.
            assign_capability('mod/approval:' . $capability, CAP_PREVENT, $user_role->id, $workflow_course_context->id, true);

            // Test that a user without the cap cannot interact.
            self::assertFalse($interactor->$function());

            // Add capability to the role.
            assign_capability('mod/approval:' . $capability, CAP_ALLOW, $user_role->id, $workflow_course_context->id, true);

            // Test that a user with the cap can interact.
            self::assertTrue($interactor->$function());

            // Remove capability from the role (avoid contamination between test cases).
            assign_capability('mod/approval:' . $capability, CAP_PREVENT, $user_role->id, $workflow_course_context->id, true);
        }
    }

    public function test_can_manage_workflow_approvers(): void {
        $generator = generator::instance();
        $workflow = $generator->create_simple_request_workflow();
        $workflow_course_context = context_course::instance($workflow->course_id);
        $user_role = builder::table('role')->where('shortname', 'user')->one();
        $user = self::getDataGenerator()->create_user();

        $interactor = workflow_interactor::from_workflow(workflow::load_by_entity($workflow), $user->id);

        // Test that a user without the caps cannot interact.
        self::assertFalse($interactor->can_manage_workflow_approvers());

        // Test with the individual cap.
        assign_capability(
            'mod/approval:manage_individual_workflow_approvers',
            CAP_ALLOW,
            $user_role->id,
            $workflow_course_context->id,
            true
        );
        self::assertTrue($interactor->can_manage_workflow_approvers());
        assign_capability(
            'mod/approval:manage_individual_workflow_approvers',
            CAP_PREVENT,
            $user_role->id,
            $workflow_course_context->id,
            true
        );

        // Test with the individual cap.
        assign_capability(
            'mod/approval:manage_relationship_workflow_approvers',
            CAP_ALLOW,
            $user_role->id,
            $workflow_course_context->id,
            true
        );
        self::assertTrue($interactor->can_manage_workflow_approvers());
        assign_capability(
            'mod/approval:manage_relationship_workflow_approvers',
            CAP_PREVENT,
            $user_role->id,
            $workflow_course_context->id,
            true
        );
    }

    public function test_can_edit() {
        $generator = generator::instance();
        $user = self::getDataGenerator()->create_user();

        // Active workflow
        $active_workflow = $generator->create_simple_request_workflow();
        $active_workflow_model = workflow::load_by_entity($active_workflow);

        // Draft workflow
        $draft_workflow = $generator->create_simple_request_workflow();
        $draft_workflow_model = workflow::load_by_entity($draft_workflow);
        workflow_version::repository()->where('id', $draft_workflow_model->latest_version->id)->update([
            'status' => status::DRAFT
        ]);
        $draft_workflow_model->refresh(true);

        // Archived workflow
        $archived_workflow = $generator->create_simple_request_workflow();
        $archived_workflow_model = workflow::load_by_entity($archived_workflow);
        workflow_version::repository()->where('id', $archived_workflow_model->latest_version->id)->update([
            'status' => status::ARCHIVED
        ]);
        $archived_workflow_model->refresh(true);

        // Test can_edit without capability.
        // active workflow
        $workflow_interactor = workflow_interactor::from_workflow($active_workflow_model, $user->id);
        $this->assertFalse($workflow_interactor->can_edit());

        // draft workflow
        $workflow_interactor = workflow_interactor::from_workflow($draft_workflow_model, $user->id);
        $this->assertFalse($workflow_interactor->can_edit());

        // archived workflow
        $workflow_interactor = workflow_interactor::from_workflow($archived_workflow_model, $user->id);
        $this->assertFalse($workflow_interactor->can_edit());

        // Enable can_archive capability.
        $user_role = builder::table('role')->where('shortname', 'user')->one();

        // Test can_edit with edit_draft_workflow capability only.
        assign_capability(
            'mod/approval:edit_draft_workflow',
            CAP_ALLOW,
            $user_role->id,
            true
        );

        // active workflow
        $workflow_interactor = workflow_interactor::from_workflow($active_workflow_model, $user->id);
        $this->assertFalse($workflow_interactor->can_edit());

        // draft workflow
        $workflow_interactor = workflow_interactor::from_workflow($draft_workflow_model, $user->id);
        $this->assertTrue($workflow_interactor->can_edit());

        // archived workflow
        $workflow_interactor = workflow_interactor::from_workflow($archived_workflow_model, $user->id);
        $this->assertFalse($workflow_interactor->can_edit());

        // Test can_edit with edit_active_workflow capability only.
        assign_capability(
            'mod/approval:edit_draft_workflow',
            CAP_PREVENT,
            $user_role->id,
            true
        );
        assign_capability(
            'mod/approval:edit_active_workflow',
            CAP_ALLOW,
            $user_role->id,
            true
        );

        // active workflow
        $workflow_interactor = workflow_interactor::from_workflow($active_workflow_model, $user->id);
        $this->assertTrue($workflow_interactor->can_edit());

        // draft workflow
        $workflow_interactor = workflow_interactor::from_workflow($draft_workflow_model, $user->id);
        $this->assertTrue($workflow_interactor->can_edit());

        // archived workflow
        $workflow_interactor = workflow_interactor::from_workflow($archived_workflow_model, $user->id);
        $this->assertFalse($workflow_interactor->can_edit());
    }

    public function test_can_activate() {
        $generator = generator::instance();
        $user = self::getDataGenerator()->create_user();

        // Active workflow
        $active_workflow = $generator->create_simple_request_workflow();
        $active_workflow_model = workflow::load_by_entity($active_workflow);

        // Draft workflow
        $draft_workflow = $generator->create_simple_request_workflow();
        $draft_workflow_model = workflow::load_by_entity($draft_workflow);
        workflow_version::repository()->where('id', $draft_workflow_model->latest_version->id)->update([
            'status' => status::DRAFT
        ]);
        $draft_workflow_model->refresh(true);

        // Archived workflow
        $archived_workflow = $generator->create_simple_request_workflow();
        $archived_workflow_model = workflow::load_by_entity($archived_workflow);
        workflow_version::repository()->where('id', $archived_workflow_model->latest_version->id)->update([
            'status' => status::ARCHIVED
        ]);
        $archived_workflow_model->refresh(true);

        // Test can_activate without capability.
        // active workflow
        $workflow_interactor = workflow_interactor::from_workflow($active_workflow_model, $user->id);
        $this->assertFalse($workflow_interactor->can_activate());

        // draft workflow
        $workflow_interactor = workflow_interactor::from_workflow($draft_workflow_model, $user->id);
        $this->assertFalse($workflow_interactor->can_activate());

        // archived workflow
        $workflow_interactor = workflow_interactor::from_workflow($archived_workflow_model, $user->id);
        $this->assertFalse($workflow_interactor->can_activate());

        // Enable can_archive capability.
        $user_role = builder::table('role')->where('shortname', 'user')->one();
        assign_capability(
            'mod/approval:activate_workflow',
            CAP_ALLOW,
            $user_role->id,
            true
        );

        // Test can_activate with capability.
        // active workflow
        $workflow_interactor = workflow_interactor::from_workflow($active_workflow_model, $user->id);
        $this->assertFalse($workflow_interactor->can_activate());

        // draft workflow
        $workflow_interactor = workflow_interactor::from_workflow($draft_workflow_model, $user->id);
        $this->assertTrue($workflow_interactor->can_activate());

        // archived workflow
        $workflow_interactor = workflow_interactor::from_workflow($archived_workflow_model, $user->id);
        $this->assertTrue($workflow_interactor->can_activate());
    }

    /**
     * Create a workflow for testing.
     *
     * @param integer $status
     * @param integer ...$more_statuses
     * @return workflow
     */
    private function create_simple_workflow_and_versions(int $status, int ...$more_statuses): workflow {
        $generator = generator::instance();
        $workflow = $generator->create_simple_request_workflow();
        /** @var workflow_version $version */
        $version = workflow_version::repository()->where('workflow_id', $workflow->id)->one(true);
        $version->status = $status;
        $version->save();
        // add more versions if needed
        $form_version_id = $version->form_version_id;
        foreach ($more_statuses as $more_status) {
            $version = new workflow_version();
            $version->workflow_id = $workflow->id;
            $version->form_version_id = $form_version_id;
            $version->status = $more_status;
            $version->save();
        }
        return workflow::load_by_id($workflow->id);
    }

    /**
     * @covers mod_approval\interactor\workflow_interactor::can_archive
     */
    public function test_can_archive() {
        $user = self::getDataGenerator()->create_user();

        // Active workflow
        $active_workflow_model = $this->create_simple_workflow_and_versions(status::ACTIVE);

        // Draft workflow
        $draft_workflow_model = $this->create_simple_workflow_and_versions(status::DRAFT);

        // Archived workflow
        $archived_workflow_model = $this->create_simple_workflow_and_versions(status::ARCHIVED);

        // Draft active workflow
        $draft_active_workflow_model = $this->create_simple_workflow_and_versions(status::ACTIVE, status::DRAFT);

        // Test can_archive without capability.
        // active workflow
        $workflow_interactor = workflow_interactor::from_workflow($active_workflow_model, $user->id);
        $this->assertFalse($workflow_interactor->can_archive());

        // draft workflow
        $workflow_interactor = workflow_interactor::from_workflow($draft_workflow_model, $user->id);
        $this->assertFalse($workflow_interactor->can_archive());

        // archived workflow
        $workflow_interactor = workflow_interactor::from_workflow($archived_workflow_model, $user->id);
        $this->assertFalse($workflow_interactor->can_archive());

        // draft active workflow
        $workflow_interactor = workflow_interactor::from_workflow($draft_active_workflow_model, $user->id);
        $this->assertFalse($workflow_interactor->can_archive());

        // Enable can_archive capability.
        $user_role = builder::table('role')->where('shortname', 'user')->one();
        assign_capability(
            'mod/approval:archive_workflow',
            CAP_ALLOW,
            $user_role->id,
            true
        );

        // Test can_archive with capability.
        // active workflow
        $workflow_interactor = workflow_interactor::from_workflow($active_workflow_model, $user->id);
        $this->assertTrue($workflow_interactor->can_archive());

        // draft workflow
        $workflow_interactor = workflow_interactor::from_workflow($draft_workflow_model, $user->id);
        $this->assertFalse($workflow_interactor->can_archive());

        // archived workflow
        $workflow_interactor = workflow_interactor::from_workflow($archived_workflow_model, $user->id);
        $this->assertFalse($workflow_interactor->can_archive());

        // draft active workflow
        $workflow_interactor = workflow_interactor::from_workflow($draft_active_workflow_model, $user->id);
        $this->assertTrue($workflow_interactor->can_archive());
    }

    /**
     * @covers mod_approval\interactor\workflow_interactor::can_unarchive
     */
    public function test_can_unarchive() {
        $user = self::getDataGenerator()->create_user();

        // Active workflow
        $active_workflow_model = $this->create_simple_workflow_and_versions(status::ACTIVE);

        // Draft workflow
        $draft_workflow_model = $this->create_simple_workflow_and_versions(status::DRAFT);

        // Archived workflow
        $archived_workflow_model = $this->create_simple_workflow_and_versions(status::ARCHIVED);

        // Draft archived workflow
        $draft_archived_workflow_model = $this->create_simple_workflow_and_versions(status::ARCHIVED, status::DRAFT);

        // Archived active workflow
        $archived_active_workflow_model = $this->create_simple_workflow_and_versions(status::ACTIVE, status::ARCHIVED);

        // Test can_unarchive without capability.
        $workflow_interactor = workflow_interactor::from_workflow($active_workflow_model, $user->id);
        $this->assertFalse($workflow_interactor->can_unarchive());

        $workflow_interactor = workflow_interactor::from_workflow($draft_workflow_model, $user->id);
        $this->assertFalse($workflow_interactor->can_unarchive());

        $workflow_interactor = workflow_interactor::from_workflow($archived_workflow_model, $user->id);
        $this->assertFalse($workflow_interactor->can_unarchive());

        $workflow_interactor = workflow_interactor::from_workflow($draft_archived_workflow_model, $user->id);
        $this->assertFalse($workflow_interactor->can_unarchive());

        $workflow_interactor = workflow_interactor::from_workflow($archived_active_workflow_model, $user->id);
        $this->assertFalse($workflow_interactor->can_unarchive());

        // Enable can_unarchive capability.
        $user_role = builder::table('role')->where('shortname', 'user')->one();
        assign_capability(
            'mod/approval:archive_workflow',
            CAP_ALLOW,
            $user_role->id,
            true
        );

        // Test can_unarchive with capability.
        $workflow_interactor = workflow_interactor::from_workflow($active_workflow_model, $user->id);
        $this->assertFalse($workflow_interactor->can_unarchive());

        $workflow_interactor = workflow_interactor::from_workflow($draft_workflow_model, $user->id);
        $this->assertFalse($workflow_interactor->can_unarchive());

        $workflow_interactor = workflow_interactor::from_workflow($archived_workflow_model, $user->id);
        $this->assertTrue($workflow_interactor->can_unarchive());

        $workflow_interactor = workflow_interactor::from_workflow($draft_archived_workflow_model, $user->id);
        $this->assertTrue($workflow_interactor->can_unarchive());

        $workflow_interactor = workflow_interactor::from_workflow($archived_active_workflow_model, $user->id);
        $this->assertFalse($workflow_interactor->can_unarchive());
    }

    /**
     * @covers mod_approval\interactor\workflow_interactor::can_delete
     */
    public function test_can_delete() {
        $generator = generator::instance();
        $user = self::getDataGenerator()->create_user();

        // Active workflow
        $active_workflow = $generator->create_simple_request_workflow();
        $active_workflow_model = workflow::load_by_id($active_workflow->id);

        // Draft workflow
        $draft_workflow = $generator->create_simple_request_workflow();
        $draft_workflow_model = workflow::load_by_id($draft_workflow->id);
        workflow_version::repository()->where('id', $draft_workflow_model->latest_version->id)->update([
            'status' => status::DRAFT
        ]);
        $draft_workflow_model->refresh(true);

        // Archived workflow
        $archived_workflow = $generator->create_simple_request_workflow();
        $archived_workflow_model = workflow::load_by_id($archived_workflow->id);
        workflow_version::repository()->where('id', $archived_workflow_model->latest_version->id)->update([
            'status' => status::ARCHIVED
        ]);
        $archived_workflow_model->refresh(true);

        // Test can_delete without capability.
        // active workflow
        $workflow_interactor = workflow_interactor::from_workflow($active_workflow_model, $user->id);
        $this->assertFalse($workflow_interactor->can_delete());

        // draft workflow
        $workflow_interactor = workflow_interactor::from_workflow($draft_workflow_model, $user->id);
        $this->assertFalse($workflow_interactor->can_delete());

        // archived workflow
        $workflow_interactor = workflow_interactor::from_workflow($archived_workflow_model, $user->id);
        $this->assertFalse($workflow_interactor->can_delete());

        // Enable can_delete capability.
        $site_manager_role = builder::table('role')->where('shortname', 'manager')->one();
        role_assign($site_manager_role->id, $user->id, context_system::instance());

        // Test can_delete with capability.
        // active workflow
        $workflow_interactor = workflow_interactor::from_workflow($active_workflow_model, $user->id);
        $this->assertFalse($workflow_interactor->can_delete());

        // draft workflow
        $workflow_interactor = workflow_interactor::from_workflow($draft_workflow_model, $user->id);
        $this->assertTrue($workflow_interactor->can_delete());

        // archived workflow
        $workflow_interactor = workflow_interactor::from_workflow($archived_workflow_model, $user->id);
        $this->assertFalse($workflow_interactor->can_delete());
    }

    public function test_can_upload_approver_overrides() {
        $generator = generator::instance();
        $user = self::getDataGenerator()->create_user();

        // Active workflow
        $active_workflow = $generator->create_simple_request_workflow();
        $active_workflow_model = workflow::load_by_entity($active_workflow);

        // Draft workflow
        $draft_workflow = $generator->create_simple_request_workflow();
        $draft_workflow_model = workflow::load_by_entity($draft_workflow);
        workflow_version::repository()->where('id', $draft_workflow_model->latest_version->id)->update([
            'status' => status::DRAFT
        ]);
        $draft_workflow_model->refresh(true);

        // Archived workflow
        $archived_workflow = $generator->create_simple_request_workflow();
        $archived_workflow_model = workflow::load_by_entity($archived_workflow);
        workflow_version::repository()->where('id', $archived_workflow_model->latest_version->id)->update([
            'status' => status::ARCHIVED
        ]);
        $archived_workflow_model->refresh(true);

        // Test can_upload_approver_overrides without capability.
        // active workflow
        $workflow_interactor = workflow_interactor::from_workflow($active_workflow_model, $user->id);
        $this->assertFalse($workflow_interactor->can_upload_approver_overrides());

        // draft workflow
        $workflow_interactor = workflow_interactor::from_workflow($draft_workflow_model, $user->id);
        $this->assertFalse($workflow_interactor->can_upload_approver_overrides());

        // archived workflow
        $workflow_interactor = workflow_interactor::from_workflow($archived_workflow_model, $user->id);
        $this->assertFalse($workflow_interactor->can_upload_approver_overrides());

        // Enable can_archive capability.
        $user_role = builder::table('role')->where('shortname', 'user')->one();
        assign_capability(
            'mod/approval:manage_workflow_assignment_overrides',
            CAP_ALLOW,
            $user_role->id,
            true
        );

        // Test can_upload_approver_overrides with capability.
        // active workflow
        $workflow_interactor = workflow_interactor::from_workflow($active_workflow_model, $user->id);
        $this->assertTrue($workflow_interactor->can_upload_approver_overrides());

        // draft workflow
        $workflow_interactor = workflow_interactor::from_workflow($draft_workflow_model, $user->id);
        $this->assertTrue($workflow_interactor->can_upload_approver_overrides());

        // archived workflow
        $workflow_interactor = workflow_interactor::from_workflow($archived_workflow_model, $user->id);
        $this->assertTrue($workflow_interactor->can_upload_approver_overrides());
    }

    /**
     * @covers mod_approval\interactor\workflow_interactor::has_assign_roles_capability
     */
    public function test_has_assign_roles_capability() {
        $generator = generator::instance();
        $user = self::getDataGenerator()->create_user();

        // Workflow
        $entity = $generator->create_simple_request_workflow();
        $workflow_model = workflow::load_by_entity($entity);
        $this->assertFalse($workflow_model->get_interactor($user->id)->has_assign_roles_capability());

        $user_role = builder::table('role')->where('shortname', 'user')->one();
        assign_capability(
            'moodle/role:assign',
            CAP_ALLOW,
            $user_role->id,
            true
        );

        $this->assertTrue($workflow_model->get_interactor($user->id)->has_assign_roles_capability());
    }
}
