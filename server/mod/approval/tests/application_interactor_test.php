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

use core\entity\user;
use core\orm\query\builder;
use core_phpunit\testcase;
use mod_approval\entity\application\application as application_entity;
use mod_approval\model\application\action\reject;
use mod_approval\model\application\action\submit;
use mod_approval\model\application\application as application_model;
use mod_approval\model\application\application_state;
use mod_approval\model\application\application_submission;
use mod_approval\model\assignment\approver_type\relationship as relationship_approver_type;
use mod_approval\model\assignment\approver_type\user as individual_approver_type;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\form\form_data;
use mod_approval\model\status as status;
use mod_approval\model\workflow\stage_feature\formviews;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\stage_type\finished;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_formview;
use mod_approval\model\workflow\workflow_version;
use mod_approval\testing\application_generator_object;
use mod_approval\testing\approval_workflow_test_setup;
use mod_approval\testing\assignment_approver_generator_object;
use mod_approval\testing\assignment_generator_object;
use mod_approval\testing\generator;
use mod_approval\testing\workflow_generator_object;
use totara_core\entity\relationship;
use totara_job\job_assignment;

/**
 * @group approval_workflow
 * @coversDefaultClass \mod_approval\interactor\application_interactor
 */
class mod_approval_application_interactor_testcase extends testcase {

    use approval_workflow_test_setup;

    private function get_application(int $approver_id = null, bool $include_formview = true): application_model {
        $generator = generator::instance();

        // Create a form and version.
        $form_version = $generator->create_form_and_version();
        $form = $form_version->form;

        // Create a workflow and version.
        $workflow_type = $generator->create_workflow_type('test');
        $workflow_go = new workflow_generator_object($workflow_type->id, $form->id, $form_version->id, status::DRAFT);
        $workflow_version = $generator->create_workflow_and_version($workflow_go);
        $workflow = $workflow_version->workflow;

        $workflow_stage1 = $generator->create_workflow_stage($workflow_version->id, 'Test Stage', form_submission::get_enum());
        $workflow_stage2 = $generator->create_workflow_stage($workflow_version->id, 'Approvals stage', approvals::get_enum());

        $approval_level_1 = $workflow_stage2->approval_levels()->first();
        $approval_level_2 = $generator->create_approval_level($workflow_stage2->id, 'level2', 2);

        $workflow_stage3 = $generator->create_workflow_stage($workflow_version->id, 'End', finished::get_enum());

        // Workflow stage formviews created by default.
        // When false we need to delete them.
        if (!$include_formview) {
            $stages = $workflow_version->stages;
            foreach ($stages as $stage) {
                $stage = workflow_stage::load_by_entity($stage);
                $default_formviews = $stage->formviews->all();
                /** @var workflow_stage_formview $formview */
                foreach ($default_formviews as $formview) {
                    $stage->configure_formview([['field_key' => $formview->field_key, 'visibility' => formviews::HIDDEN]]);
                }
            }
        }

        // Create an assignment.
        $framework = $this->generate_org_hierarchy();

        $assignment_go = new assignment_generator_object(
            $workflow->course_id,
            assignment_type\organisation::get_code(),
            $framework->agency->id
        );
        $assignment_go->is_default = true;
        $assignment = $generator->create_assignment($assignment_go);

        // Create an assignment_approver generator object
        if ($approver_id) {
            $approver_go1 = new assignment_approver_generator_object(
                $assignment->id,
                $approval_level_1->id,
                individual_approver_type::TYPE_IDENTIFIER,
                $approver_id
            );
            $approver_go2 = new assignment_approver_generator_object(
                $assignment->id,
                $approval_level_2->id,
                individual_approver_type::TYPE_IDENTIFIER,
                $approver_id
            );
        } else {
            $manager_relationship = relationship::repository()->where('idnumber', '=', 'manager')->one();
            $approver_go1 = new assignment_approver_generator_object(
                $assignment->id,
                $approval_level_1->id,
                relationship_approver_type::TYPE_IDENTIFIER,
                $manager_relationship->id
            );
            $approver_go2 = new assignment_approver_generator_object(
                $assignment->id,
                $approval_level_2->id,
                relationship_approver_type::TYPE_IDENTIFIER,
                $manager_relationship->id
            );
        }
        $generator->create_assignment_approver($approver_go1);
        $generator->create_assignment_approver($approver_go2);

        $workflow_version->status = status::ACTIVE;
        $workflow_version->save();

        // Create an application.
        $application_go = new application_generator_object(
            $workflow_version->id,
            $form_version->id,
            $assignment->id
        );
        $application_go->user_id = self::getDataGenerator()->create_user()->id;
        $application_go->creator_id = self::getDataGenerator()->create_user()->id;
        $application_entity = $generator->create_application($application_go);
        return application_model::load_by_entity($application_entity);
    }

    public function test_workflow_interactor_roles_have_correct_defaults() {
        $application = $this->get_application();

        // Some other random user in relation to the applicant.
        $random_user = self::getDataGenerator()->create_user();
        $interactor = $application->get_interactor($random_user->id);
        self::assertFalse($interactor->has_view_draft_in_dashboard_capability());
        self::assertFalse($interactor->has_view_draft_capability());
        self::assertFalse($interactor->has_edit_draft_capability());
        self::assertFalse($interactor->has_delete_draft_capability());
        self::assertFalse($interactor->has_view_in_dashboard_capability());
        self::assertFalse($interactor->has_view_capability());
        self::assertFalse($interactor->has_view_pending_capability());
        self::assertFalse($interactor->has_edit_unsubmitted_capability());
        self::assertFalse($interactor->has_edit_in_approvals_capability());
        self::assertFalse($interactor->has_edit_in_approvals_pending_capability());
        self::assertFalse($interactor->has_edit_first_approval_level_capability());
        self::assertFalse($interactor->has_edit_first_approval_level_pending_capability());
        self::assertFalse($interactor->has_edit_without_invalidating_approvals_capability());
        self::assertFalse($interactor->has_approve_capability());
        self::assertFalse($interactor->has_approve_pending_capability());
        self::assertFalse($interactor->has_attach_file_capability());
        self::assertFalse($interactor->has_view_comment_capability());
        self::assertFalse($interactor->has_post_comment_capability());
        self::assertFalse($interactor->has_post_comment_pending_capability());
        self::assertFalse($interactor->has_withdraw_unsubmitted_capability());
        self::assertFalse($interactor->has_withdraw_in_approvals_capability());
        self::assertFalse($interactor->has_backdate_capability());

        // Owner in relation to their own application.
        $interactor = $application->get_interactor($application->owner_id);
        // There is no owner view_in_dashboard cap, because they have built-in viewing rights.
        self::assertFalse($interactor->has_view_draft_in_dashboard_capability());
        self::assertTrue($interactor->has_view_draft_capability());
        self::assertTrue($interactor->has_edit_draft_capability());
        self::assertTrue($interactor->has_delete_draft_capability());
        self::assertFalse($interactor->has_view_in_dashboard_capability());
        self::assertTrue($interactor->has_view_capability());
        self::assertFalse($interactor->has_view_pending_capability());
        self::assertFalse($interactor->has_edit_unsubmitted_capability());
        self::assertFalse($interactor->has_edit_in_approvals_capability());
        self::assertFalse($interactor->has_edit_in_approvals_pending_capability());
        self::assertFalse($interactor->has_edit_first_approval_level_capability());
        self::assertFalse($interactor->has_edit_first_approval_level_pending_capability());
        self::assertFalse($interactor->has_edit_without_invalidating_approvals_capability());
        self::assertFalse($interactor->has_approve_capability());
        self::assertFalse($interactor->has_approve_pending_capability());
        self::assertTrue($interactor->has_attach_file_capability());
        self::assertTrue($interactor->has_view_comment_capability());
        self::assertTrue($interactor->has_post_comment_capability());
        self::assertFalse($interactor->has_post_comment_pending_capability());
        self::assertFalse($interactor->has_withdraw_unsubmitted_capability());
        self::assertFalse($interactor->has_withdraw_in_approvals_capability());
        self::assertFalse($interactor->has_backdate_capability());

        // Applicant in relation to their own application.
        $interactor = $application->get_interactor($application->user_id);
        self::assertFalse($interactor->has_view_draft_in_dashboard_capability());
        self::assertFalse($interactor->has_view_draft_capability());
        self::assertFalse($interactor->has_edit_draft_capability());
        self::assertFalse($interactor->has_delete_draft_capability());
        self::assertTrue($interactor->has_view_in_dashboard_capability());
        self::assertTrue($interactor->has_view_capability());
        self::assertFalse($interactor->has_view_pending_capability());
        self::assertTrue($interactor->has_edit_unsubmitted_capability());
        self::assertFalse($interactor->has_edit_in_approvals_capability());
        self::assertFalse($interactor->has_edit_in_approvals_pending_capability());
        self::assertFalse($interactor->has_edit_first_approval_level_capability());
        self::assertFalse($interactor->has_edit_first_approval_level_pending_capability());
        self::assertFalse($interactor->has_edit_without_invalidating_approvals_capability());
        self::assertFalse($interactor->has_approve_capability());
        self::assertFalse($interactor->has_approve_pending_capability());
        self::assertTrue($interactor->has_attach_file_capability());
        self::assertTrue($interactor->has_view_comment_capability());
        self::assertTrue($interactor->has_post_comment_capability());
        self::assertFalse($interactor->has_post_comment_pending_capability());
        self::assertTrue($interactor->has_withdraw_unsubmitted_capability());
        self::assertTrue($interactor->has_withdraw_in_approvals_capability());
        self::assertFalse($interactor->has_backdate_capability());

        // A manager in relation to one of their staff.
        $manager_user = self::getDataGenerator()->create_user();
        $manager_job = job_assignment::create_default($manager_user->id);
        job_assignment::create_default($application->user_id, ['managerjaid' => $manager_job->id]);
        $interactor = $application->get_interactor($manager_user->id);
        self::assertFalse($interactor->has_view_draft_in_dashboard_capability());
        self::assertFalse($interactor->has_view_draft_capability());
        self::assertFalse($interactor->has_edit_draft_capability());
        self::assertFalse($interactor->has_delete_draft_capability());
        self::assertTrue($interactor->has_view_in_dashboard_capability());
        self::assertTrue($interactor->has_view_capability());
        self::assertFalse($interactor->has_view_pending_capability());
        self::assertFalse($interactor->has_edit_unsubmitted_capability());
        self::assertFalse($interactor->has_edit_in_approvals_capability());
        self::assertTrue($interactor->has_edit_in_approvals_pending_capability());
        self::assertFalse($interactor->has_edit_first_approval_level_capability());
        self::assertFalse($interactor->has_edit_first_approval_level_pending_capability());
        self::assertFalse($interactor->has_edit_without_invalidating_approvals_capability());
        self::assertFalse($interactor->has_approve_capability());
        self::assertTrue($interactor->has_approve_pending_capability());
        self::assertTrue($interactor->has_attach_file_capability());
        self::assertTrue($interactor->has_view_comment_capability());
        self::assertTrue($interactor->has_post_comment_capability());
        self::assertFalse($interactor->has_post_comment_pending_capability());
        self::assertFalse($interactor->has_withdraw_unsubmitted_capability());
        self::assertFalse($interactor->has_withdraw_in_approvals_capability());
        self::assertFalse($interactor->has_backdate_capability());

        // An approver in the assignment activity context.
        $approver_user = self::getDataGenerator()->create_user();
        $approver_role = builder::table('role')->where('shortname', 'approvalworkflowapprover')->one();
        role_assign($approver_role->id, $approver_user->id, $application->get_assignment()->get_context());
        $interactor = $application->get_interactor($approver_user->id);
        self::assertFalse($interactor->has_view_draft_in_dashboard_capability());
        self::assertFalse($interactor->has_view_draft_capability());
        self::assertFalse($interactor->has_edit_draft_capability());
        self::assertFalse($interactor->has_delete_draft_capability());
        self::assertTrue($interactor->has_view_in_dashboard_capability());
        self::assertTrue($interactor->has_view_capability());
        self::assertFalse($interactor->has_view_pending_capability());
        self::assertFalse($interactor->has_edit_unsubmitted_capability());
        self::assertFalse($interactor->has_edit_in_approvals_capability());
        self::assertTrue($interactor->has_edit_in_approvals_pending_capability());
        self::assertFalse($interactor->has_edit_first_approval_level_capability());
        self::assertFalse($interactor->has_edit_first_approval_level_pending_capability());
        self::assertFalse($interactor->has_edit_without_invalidating_approvals_capability());
        self::assertFalse($interactor->has_approve_capability());
        self::assertTrue($interactor->has_approve_pending_capability());
        self::assertTrue($interactor->has_attach_file_capability());
        self::assertTrue($interactor->has_view_comment_capability());
        self::assertTrue($interactor->has_post_comment_capability());
        self::assertFalse($interactor->has_post_comment_pending_capability());
        self::assertFalse($interactor->has_withdraw_unsubmitted_capability());
        self::assertFalse($interactor->has_withdraw_in_approvals_capability());
        self::assertFalse($interactor->has_backdate_capability());

        // An editing trainer in the assignment activity context.
        $workflow_manager_user = self::getDataGenerator()->create_user();
        $workflow_manager_role = builder::table('role')->where('shortname', 'approvalworkflowmanager')->one();
        role_assign($workflow_manager_role->id, $workflow_manager_user->id, $application->get_assignment()->get_context());
        $interactor = $application->get_interactor($workflow_manager_user->id);
        self::assertFalse($interactor->has_view_draft_in_dashboard_capability());
        self::assertFalse($interactor->has_view_draft_capability());
        self::assertFalse($interactor->has_edit_draft_capability());
        self::assertFalse($interactor->has_delete_draft_capability());
        self::assertTrue($interactor->has_view_in_dashboard_capability());
        self::assertTrue($interactor->has_view_capability());
        self::assertFalse($interactor->has_view_pending_capability());
        self::assertTrue($interactor->has_edit_unsubmitted_capability());
        self::assertTrue($interactor->has_edit_in_approvals_capability());
        self::assertFalse($interactor->has_edit_in_approvals_pending_capability());
        self::assertFalse($interactor->has_edit_first_approval_level_capability());
        self::assertFalse($interactor->has_edit_first_approval_level_pending_capability());
        self::assertTrue($interactor->has_edit_without_invalidating_approvals_capability());
        self::assertTrue($interactor->has_approve_capability());
        self::assertFalse($interactor->has_approve_pending_capability());
        self::assertTrue($interactor->has_attach_file_capability());
        self::assertTrue($interactor->has_view_comment_capability());
        self::assertTrue($interactor->has_post_comment_capability());
        self::assertFalse($interactor->has_post_comment_pending_capability());
        self::assertTrue($interactor->has_withdraw_unsubmitted_capability());
        self::assertTrue($interactor->has_withdraw_in_approvals_capability());
        self::assertTrue($interactor->has_backdate_capability());
        self::assertTrue($interactor->has_edit_full_application());

        // A site manager in the assignment activity context.
        $site_manager_user = self::getDataGenerator()->create_user();
        $site_manager_role = builder::table('role')->where('shortname', 'manager')->one();
        role_assign($site_manager_role->id, $site_manager_user->id, $application->get_assignment()->get_context());
        $interactor = $application->get_interactor($site_manager_user->id);
        self::assertFalse($interactor->has_view_draft_in_dashboard_capability());
        self::assertFalse($interactor->has_view_draft_capability());
        self::assertFalse($interactor->has_edit_draft_capability());
        self::assertFalse($interactor->has_delete_draft_capability());
        self::assertTrue($interactor->has_view_in_dashboard_capability());
        self::assertTrue($interactor->has_view_capability());
        self::assertFalse($interactor->has_view_pending_capability());
        self::assertTrue($interactor->has_edit_unsubmitted_capability());
        self::assertTrue($interactor->has_edit_in_approvals_capability());
        self::assertFalse($interactor->has_edit_in_approvals_pending_capability());
        self::assertFalse($interactor->has_edit_first_approval_level_capability());
        self::assertFalse($interactor->has_edit_first_approval_level_pending_capability());
        self::assertTrue($interactor->has_edit_without_invalidating_approvals_capability());
        self::assertTrue($interactor->has_approve_capability());
        self::assertFalse($interactor->has_approve_pending_capability());
        self::assertTrue($interactor->has_attach_file_capability());
        self::assertTrue($interactor->has_view_comment_capability());
        self::assertTrue($interactor->has_post_comment_capability());
        self::assertFalse($interactor->has_post_comment_pending_capability());
        self::assertTrue($interactor->has_withdraw_unsubmitted_capability());
        self::assertTrue($interactor->has_withdraw_in_approvals_capability());
        self::assertTrue($interactor->has_backdate_capability());
        self::assertTrue($interactor->has_edit_full_application());
        // Tenant domain manager?
    }

    public function test_has_functions_match_capabilities() {
        $functions_capabilities = [
            'has_view_draft_in_dashboard_capability' =>
                ['view_draft_in_dashboard_application', ['applicant', 'user', 'any']],
            'has_view_draft_capability' =>
                ['view_draft_application', ['other', 'applicant', 'user', 'any']],
            'has_edit_draft_capability' =>
                ['edit_draft_application', ['other', 'applicant', 'user', 'any']],
            'has_delete_draft_capability' =>
                ['delete_draft_application', ['other', 'applicant', 'user', 'any']],
            'has_view_in_dashboard_capability' =>
                ['view_in_dashboard_application', ['applicant', 'user', 'any']],
            'has_view_capability' =>
                ['view_application', ['other', 'applicant', 'user', 'any']],
            'has_view_pending_capability' =>
                ['view_pending_application', ['user', 'any']],
            'has_edit_unsubmitted_capability' =>
                ['edit_unsubmitted_application', ['other', 'applicant', 'user', 'any']],
            'has_edit_in_approvals_capability' =>
                ['edit_in_approvals_application', ['other', 'applicant', 'user', 'any']],
            'has_edit_in_approvals_pending_capability' =>
                ['edit_in_approvals_pending_application', ['user', 'any']],
            'has_edit_first_approval_level_capability' =>
                ['edit_first_approval_level_application', ['other', 'applicant', 'user', 'any']],
            'has_edit_first_approval_level_pending_capability' =>
                ['edit_first_approval_level_pending_application', ['user', 'any']],
            'has_edit_without_invalidating_approvals_capability' =>
                ['edit_without_invalidating_approvals', ['other', 'applicant', 'user', 'any']],
            'has_approve_capability' =>
                ['approve_application', ['other', 'applicant', 'user', 'any']],
            'has_approve_pending_capability' =>
                ['approve_pending_application', ['user', 'any']],
            'has_attach_file_capability' =>
                ['attach_file_to_application', ['other', 'applicant', 'user', 'any']],
            'has_view_comment_capability' =>
                ['view_comment_on_application', ['other', 'applicant', 'user', 'any']],
            'has_post_comment_capability' =>
                ['post_comment_on_application', ['other', 'applicant', 'user', 'any']],
            'has_post_comment_pending_capability' =>
                ['post_comment_on_pending_application', ['user', 'any']],
            'has_withdraw_unsubmitted_capability' =>
                ['withdraw_unsubmitted_application', ['other', 'applicant', 'user', 'any']],
            'has_withdraw_in_approvals_capability' =>
                ['withdraw_in_approvals_application', ['other', 'applicant', 'user', 'any']],
            'has_backdate_capability' =>
                ['backdate_application', ['other', 'applicant', 'user', 'any']],
            'has_edit_full_application' =>
                ['edit_full_application', ['other', 'applicant', 'user', 'any']],
        ];

        $application = $this->get_application();
        $assignment_context = $application->get_assignment()->get_context();
        $user_role = builder::table('role')->where('shortname', 'user')->one();
        $interactor_user = self::getDataGenerator()->create_user();
        $applicant_user_context = context_user::instance($application->user_id);

        $applicant_interactor = $application->get_interactor($application->user_id);
        $interactor_interactor = $application->get_interactor($interactor_user->id);

        foreach ($functions_capabilities as $function => $test_case) {
            [$capability, $cap_types] = $test_case;

            // Turn off all default capabilities.
            if (in_array('owner', $cap_types)) {
                assign_capability("mod/approval:{$capability}_owner", CAP_PREVENT, $user_role->id, $assignment_context, true);
            }
            if (in_array('applicant', $cap_types)) {
                assign_capability("mod/approval:{$capability}_applicant", CAP_PREVENT, $user_role->id, $assignment_context, true);
            }
            if (in_array('user', $cap_types)) {
                assign_capability("mod/approval:{$capability}_user", CAP_PREVENT, $user_role->id, $applicant_user_context, true);
            }
            if (in_array('any', $cap_types)) {
                assign_capability("mod/approval:{$capability}_any", CAP_PREVENT, $user_role->id, $assignment_context, true);
            }

            // Test the caps.

            if (in_array('owner', $cap_types)) {
                // Test that a user with the 'owner' capability can interact.
                self::assertFalse($applicant_interactor->$function());
                assign_capability("mod/approval:{$capability}_owner", CAP_ALLOW, $user_role->id, $assignment_context, true);
                self::assertTrue($applicant_interactor->$function(), $capability);
                assign_capability("mod/approval:{$capability}_owner", CAP_PREVENT, $user_role->id, $assignment_context, true);
            }

            if (in_array('applicant', $cap_types)) {
                // Test that a user with the 'applicant' capability can interact.
                self::assertFalse($applicant_interactor->$function());
                assign_capability("mod/approval:{$capability}_applicant", CAP_ALLOW, $user_role->id, $assignment_context, true);
                self::assertTrue($applicant_interactor->$function(), $capability);
                assign_capability("mod/approval:{$capability}_applicant", CAP_PREVENT, $user_role->id, $assignment_context, true);
            }

            if (in_array('user', $cap_types)) {
                // Test 'user' capability in the applicant user context.
                self::assertFalse($interactor_interactor->$function());
                assign_capability("mod/approval:{$capability}_user", CAP_ALLOW, $user_role->id, $applicant_user_context, true);
                self::assertTrue($interactor_interactor->$function());
                assign_capability("mod/approval:{$capability}_user", CAP_PREVENT, $user_role->id, $applicant_user_context, true);
            }

            if (in_array('any', $cap_types)) {
                // Test 'any' capability in the assignment's activity context.
                self::assertFalse($interactor_interactor->$function());
                assign_capability("mod/approval:{$capability}_any", CAP_ALLOW, $user_role->id, $assignment_context, true);
                self::assertTrue($interactor_interactor->$function());
                assign_capability("mod/approval:{$capability}_any", CAP_PREVENT, $user_role->id, $assignment_context, true);
            }
        }
    }

    public function test_is_pending(): void {
        $manager_user = self::getDataGenerator()->create_user();
        $manager_job_assignment = job_assignment::create_default($manager_user->id);

        $nonpending_application = $this->get_application();
        $pending_application = $this->get_application();
        $pending_state = $pending_application->current_stage->state_manager->get_next_state($pending_application->current_state);
        $pending_application->set_current_state($pending_state);

        job_assignment::create_default($nonpending_application->user_id, ['managerjaid' => $manager_job_assignment->id]);
        job_assignment::create_default($pending_application->user_id, ['managerjaid' => $manager_job_assignment->id]);

        $manager_interactor = $nonpending_application->get_interactor($manager_user->id);
        self::assertFalse($manager_interactor->is_pending());

        $manager_interactor = $pending_application->get_interactor($manager_user->id);
        self::assertTrue($manager_interactor->is_pending());
    }

    public function test_can_view_in_dashboard_with_draft(): void {
        // Start with a draft application.
        $application = $this->get_application();
        $application->set_current_state(new application_state($application->current_state->get_stage_id(), true));

        // Make sure the owner can view it.
        $owner_interactor = $application->get_interactor($application->owner_id);
        self::assertTrue($owner_interactor->can_view_in_dashboard());

        // The applicant cannot see it.
        $applicant_interactor = $application->get_interactor($application->user_id);
        self::assertFalse($applicant_interactor->can_view_in_dashboard());

        // Granting the applicant capability lets them see it.
        $user_role = builder::table('role')->where('shortname', 'user')->one();
        assign_capability(
            "mod/approval:view_draft_in_dashboard_application_applicant",
            CAP_ALLOW,
            $user_role->id,
            $application->get_context(),
            true
        );
        $applicant_interactor = $application->get_interactor($application->user_id);
        self::assertTrue($applicant_interactor->can_view_in_dashboard());
    }

    public function test_can_view_in_dashboard_with_non_draft_owner_applicant(): void {
        // Start with a non-draft application.
        $application = $this->get_application();
        $application->set_current_state(new application_state($application->current_state->get_stage_id()));

        // Make sure the owner can view it.
        $owner_interactor = $application->get_interactor($application->owner_id);
        self::assertTrue($owner_interactor->can_view_in_dashboard());

        // The applicant can see it.
        $applicant_interactor = $application->get_interactor($application->user_id);
        self::assertTrue($applicant_interactor->can_view_in_dashboard());

        // Removing the applicant capability hides it.
        $user_role = builder::table('role')->where('shortname', 'user')->one();
        assign_capability(
            "mod/approval:view_in_dashboard_application_applicant",
            CAP_PREVENT,
            $user_role->id,
            $application->get_context(),
            true
        );
        $applicant_interactor = $application->get_interactor($application->user_id);
        self::assertFalse($applicant_interactor->can_view_in_dashboard());
    }

    /**
     * In these tests, we are checking that the correct states are being taken into account, as well as the
     * correct capability checks are being made.
     */
    public function test_can_view_in_dashboard_with_non_draft_manager_approver(): void {
        $user_role = builder::table('role')->where('shortname', 'user')->one();
        $manager_role = builder::table('role')->where('shortname', 'staffmanager')->one();
        $approver_role = builder::table('role')->where('shortname', 'approvalworkflowapprover')->one();

        $non_manager_user = self::getDataGenerator()->create_user();
        $nonpending_application = $this->get_application($non_manager_user->id);
        $nonpending_application->set_current_state(new application_state($nonpending_application->current_state->get_stage_id()));
        $nonpending_interactor = $nonpending_application->get_interactor($non_manager_user->id);
        $np_a_context = $nonpending_application->get_assignment()->get_context();

        $manager_user = self::getDataGenerator()->create_user();
        $manager_job_assignment = job_assignment::create_default($manager_user->id);
        $pending_application = $this->get_application();
        $pending_state = $pending_application->current_stage->state_manager->get_next_state($pending_application->current_state);
        $pending_application->set_current_state($pending_state);
        $pending_interactor = $pending_application->get_interactor($manager_user->id);
        $p_a_context = $pending_application->get_assignment()->get_context();
        job_assignment::create_default($pending_application->user_id, ['managerjaid' => $manager_job_assignment->id]);

        // Remove the default capabilities.
        $p_u_context = context_user::instance($pending_application->user_id);
        assign_capability("mod/approval:view_in_dashboard_application_any", CAP_PREVENT, $approver_role->id, $np_a_context, true);
        assign_capability("mod/approval:view_in_dashboard_application_user", CAP_PREVENT, $manager_role->id, $p_u_context, true);

        // Check setup was correct.
        self::assertFalse($nonpending_interactor->is_pending());
        self::assertTrue($pending_interactor->is_pending());

        // User with no cap cannot view.
        self::assertFalse($nonpending_interactor->can_view_in_dashboard());
        self::assertFalse($pending_interactor->can_view_in_dashboard());

        // User with a view draft cap can view draft.
        assign_capability("mod/approval:view_draft_in_dashboard_application_any", CAP_ALLOW, $user_role->id, $np_a_context, true);
        assign_capability("mod/approval:view_draft_in_dashboard_application_any", CAP_ALLOW, $user_role->id, $p_a_context, true);
        self::assertFalse($nonpending_interactor->can_view_in_dashboard());
        self::assertFalse($pending_interactor->can_view_in_dashboard());
        assign_capability("mod/approval:view_draft_in_dashboard_application_any", CAP_PREVENT, $user_role->id, $np_a_context, true);
        assign_capability("mod/approval:view_draft_in_dashboard_application_any", CAP_PREVENT, $user_role->id, $p_a_context, true);

        // User with a view published cap can view published.
        assign_capability("mod/approval:view_in_dashboard_application_any", CAP_ALLOW, $user_role->id, $np_a_context, true);
        assign_capability("mod/approval:view_in_dashboard_application_any", CAP_ALLOW, $user_role->id, $p_a_context, true);
        self::assertTrue($nonpending_interactor->can_view_in_dashboard());
        self::assertTrue($pending_interactor->can_view_in_dashboard());
        assign_capability("mod/approval:view_in_dashboard_application_any", CAP_PREVENT, $user_role->id, $np_a_context, true);
        assign_capability("mod/approval:view_in_dashboard_application_any", CAP_PREVENT, $user_role->id, $p_a_context, true);

        // User with the pending cap can only view the pending application.
        assign_capability("mod/approval:view_in_dashboard_pending_application_any", CAP_ALLOW, $user_role->id, $np_a_context, true);
        assign_capability("mod/approval:view_in_dashboard_pending_application_any", CAP_ALLOW, $user_role->id, $p_a_context, true);
        self::assertFalse($nonpending_interactor->can_view_in_dashboard());
        self::assertTrue($pending_interactor->can_view_in_dashboard());
    }

    /**
     * In these tests, we are checking that the correct states are being taken into account, as well as the
     * correct capability checks are being made.
     */
    public function test_can_view_with_draft(): void {
        $draft_application = $this->get_application();
        $draft_application->set_current_state(new application_state($draft_application->current_state->get_stage_id(), true));

        // Remove cap that allows owner (creator) to view non-draft application they own.
        $user_role = builder::table('role')->where('shortname', 'user')->one();
        $d_a_context = $draft_application->get_context();
        assign_capability("mod/approval:view_application_owner", CAP_PREVENT, $user_role->id, $d_a_context, true);

        // Owner can view draft.
        $owner_interactor = $draft_application->get_interactor($draft_application->owner_id);
        self::assertTrue($owner_interactor->can_view());

        // Creator cannot view when not draft (shows that there were no special caps required for creator to view draft).
        $draft_application->set_current_state(new application_state($draft_application->current_state->get_stage_id()));
        $creator_interactor = $draft_application->get_interactor($draft_application->creator_id);
        self::assertFalse($creator_interactor->can_view());

        // Non-creator (applicant) can view non-draft.
        $other_interactor = $draft_application->get_interactor($draft_application->user_id);
        self::assertTrue($other_interactor->can_view());

        // Non-creator cannot view draft, despite having some view capability.
        $draft_application->set_current_state(new application_state($draft_application->current_state->get_stage_id(), true));
        $other_interactor = $draft_application->get_interactor($draft_application->user_id);
        self::assertFalse($other_interactor->can_view());
    }

    /**
     * In these tests, we are checking that the correct states are being taken into account, as well as the
     * correct capability checks are being made.
     */
    public function test_can_view_with_non_draft(): void {
        $user_role = builder::table('role')->where('shortname', 'user')->one();
        $manager_role = builder::table('role')->where('shortname', 'staffmanager')->one();
        $approver_role = builder::table('role')->where('shortname', 'approvalworkflowapprover')->one();

        $non_manager_user = self::getDataGenerator()->create_user();
        $nonpending_application = $this->get_application($non_manager_user->id);
        $nonpending_application->set_current_state(new application_state($nonpending_application->current_state->get_stage_id()));
        $nonpending_interactor = $nonpending_application->get_interactor($non_manager_user->id);
        $np_a_context = $nonpending_application->get_assignment()->get_context();

        $manager_user = self::getDataGenerator()->create_user();
        $manager_job_assignment = job_assignment::create_default($manager_user->id);
        $pending_application = $this->get_application();
        $pending_state = $pending_application->current_stage->state_manager->get_next_state($pending_application->current_state);
        $pending_application->set_current_state($pending_state);
        $pending_interactor = $pending_application->get_interactor($manager_user->id);
        $p_a_context = $pending_application->get_assignment()->get_context();
        job_assignment::create_default($pending_application->user_id, ['managerjaid' => $manager_job_assignment->id]);

        // Remove the default capabilities.
        $p_u_context = context_user::instance($pending_application->user_id);
        assign_capability("mod/approval:view_application_any", CAP_PREVENT, $approver_role->id, $np_a_context, true);
        assign_capability("mod/approval:view_application_user", CAP_PREVENT, $manager_role->id, $p_u_context, true);

        // Check setup was correct.
        self::assertFalse($nonpending_interactor->is_pending());
        self::assertTrue($pending_interactor->is_pending());

        // User with no cap cannot view.
        self::assertFalse($nonpending_interactor->can_view());
        self::assertFalse($pending_interactor->can_view());

        // User with a view draft cap can view draft.
        assign_capability("mod/approval:view_draft_application_any", CAP_ALLOW, $user_role->id, $np_a_context, true);
        assign_capability("mod/approval:view_draft_application_any", CAP_ALLOW, $user_role->id, $p_a_context, true);
        self::assertFalse($nonpending_interactor->can_view());
        self::assertFalse($pending_interactor->can_view());
        assign_capability("mod/approval:view_draft_application_any", CAP_PREVENT, $user_role->id, $np_a_context, true);
        assign_capability("mod/approval:view_draft_application_any", CAP_PREVENT, $user_role->id, $p_a_context, true);

        // User with a view published cap can view published.
        assign_capability("mod/approval:view_application_any", CAP_ALLOW, $user_role->id, $np_a_context, true);
        assign_capability("mod/approval:view_application_any", CAP_ALLOW, $user_role->id, $p_a_context, true);
        self::assertTrue($nonpending_interactor->can_view());
        self::assertTrue($pending_interactor->can_view());
        assign_capability("mod/approval:view_application_any", CAP_PREVENT, $user_role->id, $np_a_context, true);
        assign_capability("mod/approval:view_application_any", CAP_PREVENT, $user_role->id, $p_a_context, true);

        // User with the pending cap can only view the pending application.
        assign_capability("mod/approval:view_pending_application_any", CAP_ALLOW, $user_role->id, $np_a_context, true);
        assign_capability("mod/approval:view_pending_application_any", CAP_ALLOW, $user_role->id, $p_a_context, true);
        self::assertFalse($nonpending_interactor->can_view());
        self::assertTrue($pending_interactor->can_view());
    }

    /**
     * In these tests, we are checking that the correct states are being taken into account, as well as the
     * correct capability checks are being made.
     */
    public function test_can_edit_with_draft(): void {
        $draft_application = $this->get_application();
        $draft_application->set_current_state(new application_state($draft_application->current_state->get_stage_id(), true));

        // Creator can edit draft.
        $creator_interactor = $draft_application->get_interactor($draft_application->creator_id);
        self::assertTrue($creator_interactor->can_edit());

        // Creator cannot edit when not draft (shows that there were no special caps required for creator to edit draft).
        $draft_application->set_current_state(new application_state($draft_application->current_state->get_stage_id()));
        $creator_interactor = $draft_application->get_interactor($draft_application->creator_id);
        self::assertFalse($creator_interactor->can_edit());

        // Removing the draft capability removes access.
        $user_role = builder::table('role')->where('shortname', 'user')->one();
        assign_capability(
            "mod/approval:edit_draft_application_owner",
            CAP_PREVENT,
            $user_role->id,
            $draft_application->get_context(),
            true
        );
        self::assertFalse($creator_interactor->can_edit());
        assign_capability(
            "mod/approval:edit_draft_application_owner",
            CAP_ALLOW,
            $user_role->id,
            $draft_application->get_context(),
            true
        );

        // Non-creator cannot edit draft, despite having some edit capability.
        $draft_application->set_current_state(new application_state($draft_application->current_state->get_stage_id(), true));
        $other_interactor = $draft_application->get_interactor($draft_application->user_id);
        self::assertFalse($other_interactor->can_edit());
    }

    /**
     * In these tests, we are checking that the correct states are being taken into account, as well as the
     * correct capability checks are being made.
     */
    public function test_can_edit(): void {
        $role = builder::table('role')->where('shortname', 'user')->one();

        $random_user = self::getDataGenerator()->create_user();
        $nonpending_application = $this->get_application();
        $nonpending_application->set_current_state(new application_state($nonpending_application->current_state->get_stage_id()));
        $nonpending_interactor = $nonpending_application->get_interactor($random_user->id);
        $np_a_context = $nonpending_application->get_assignment()->get_context();

        $manager_user = self::getDataGenerator()->create_user();
        $pending_application = $this->get_application();
        $pending_application->set_current_state(new application_state($pending_application->current_state->get_stage_id()));
        $pending_interactor = $pending_application->get_interactor($manager_user->id);
        $p_a_context = $pending_application->get_assignment()->get_context();
        $manager_job_assignment = job_assignment::create_default($manager_user->id);
        job_assignment::create_default($pending_application->user_id, ['managerjaid' => $manager_job_assignment->id]);

        // Remove manager default caps.
        $manager_role = builder::table('role')->where('shortname', 'staffmanager')->one();
        $approver_role = builder::table('role')->where('shortname', 'approvalworkflowapprover')->one();
        assign_capability(
            "mod/approval:edit_in_approvals_pending_application_any",
            CAP_PREVENT,
            $approver_role->id,
            $np_a_context,
            true
        );
        assign_capability(
            "mod/approval:edit_in_approvals_pending_application_user",
            CAP_PREVENT,
            $manager_role->id,
            context_user::instance($nonpending_application->user_id),
            true
        );
        assign_capability(
            "mod/approval:edit_in_approvals_pending_application_user",
            CAP_PREVENT,
            $manager_role->id,
            context_user::instance($pending_application->user_id),
            true
        );

        // Verify pending state (not pending until in-approvals).
        self::assertFalse($nonpending_interactor->is_pending());
        self::assertFalse($pending_interactor->is_pending());

        // Application starts out unsubmitted.
        self::assertTrue($nonpending_application->current_state->is_stage_type(form_submission::get_code()));
        self::assertEmpty($nonpending_application->current_state->get_approval_level());
        self::assertTrue($pending_application->current_state->is_stage_type(form_submission::get_code()));
        self::assertEmpty($pending_application->current_state->get_approval_level());

        // User with no cap cannot edit.
        self::assertFalse($nonpending_interactor->can_edit());
        self::assertFalse($pending_interactor->can_edit());

        // User with an unsubmitted cap can edit unsubmitted application.
        assign_capability("mod/approval:edit_unsubmitted_application_any", CAP_ALLOW, $role->id, $np_a_context, true);
        assign_capability("mod/approval:edit_unsubmitted_application_any", CAP_ALLOW, $role->id, $p_a_context, true);
        self::assertTrue($nonpending_interactor->can_edit());
        self::assertTrue($pending_interactor->can_edit());
        assign_capability("mod/approval:edit_unsubmitted_application_any", CAP_PREVENT, $role->id, $np_a_context, true);
        assign_capability("mod/approval:edit_unsubmitted_application_any", CAP_PREVENT, $role->id, $p_a_context, true);

        // User with a submitted cap cannot edit unsubmitted application.
        assign_capability("mod/approval:edit_first_approval_level_application_any", CAP_ALLOW, $role->id, $np_a_context, true);
        assign_capability("mod/approval:edit_first_approval_level_application_any", CAP_ALLOW, $role->id, $p_a_context, true);
        self::assertFalse($nonpending_interactor->can_edit());
        self::assertFalse($pending_interactor->can_edit());
        assign_capability("mod/approval:edit_first_approval_level_application_any", CAP_PREVENT, $role->id, $np_a_context, true);
        assign_capability("mod/approval:edit_first_approval_level_application_any", CAP_PREVENT, $role->id, $p_a_context, true);

        // User with an in-approvals cap cannot edit unsubmitted application.
        assign_capability("mod/approval:edit_in_approvals_application_any", CAP_ALLOW, $role->id, $np_a_context, true);
        assign_capability("mod/approval:edit_in_approvals_application_any", CAP_ALLOW, $role->id, $p_a_context, true);
        self::assertFalse($nonpending_interactor->can_edit());
        self::assertFalse($pending_interactor->can_edit());
        assign_capability("mod/approval:edit_in_approvals_application_any", CAP_PREVENT, $role->id, $np_a_context, true);
        assign_capability("mod/approval:edit_in_approvals_application_any", CAP_PREVENT, $role->id, $p_a_context, true);

        // User with the edit pending cap cannot edit unsubmitted application.
        assign_capability("mod/approval:edit_in_approvals_pending_application_any", CAP_ALLOW, $role->id, $np_a_context, true);
        assign_capability("mod/approval:edit_in_approvals_pending_application_any", CAP_ALLOW, $role->id, $p_a_context, true);
        self::assertFalse($nonpending_interactor->can_edit());
        self::assertFalse($pending_interactor->can_edit());
        assign_capability("mod/approval:edit_in_approvals_pending_application_any", CAP_PREVENT, $role->id, $np_a_context, true);
        assign_capability("mod/approval:edit_in_approvals_pending_application_any", CAP_PREVENT, $role->id, $p_a_context, true);

        // Set the applications to in approvals on the first approval level.
        $pending_state = $nonpending_application->current_stage->state_manager
            ->get_next_state($nonpending_application->current_state);
        $nonpending_application->set_current_state($pending_state);
        $nonpending_interactor = $nonpending_application->get_interactor($random_user->id);
        $pending_state = $pending_application->current_stage->state_manager->get_next_state($pending_application->current_state);
        $pending_application->set_current_state($pending_state);
        $pending_interactor = $pending_application->get_interactor($manager_user->id);

        // Verify pending state (not pending until in-approvals).
        self::assertFalse($nonpending_interactor->is_pending());
        self::assertTrue($pending_interactor->is_pending());

        self::assertTrue($nonpending_application->current_state->is_stage_type(approvals::get_code()));
        self::assertTrue($nonpending_application->current_state->get_approval_level()->is_first());
        self::assertTrue($pending_application->current_state->is_stage_type(approvals::get_code()));
        self::assertTrue($pending_application->current_state->get_approval_level()->is_first());

        // User with an unsubmitted cap cannot edit submitted application.
        assign_capability("mod/approval:edit_unsubmitted_application_any", CAP_ALLOW, $role->id, $np_a_context, true);
        assign_capability("mod/approval:edit_unsubmitted_application_any", CAP_ALLOW, $role->id, $p_a_context, true);
        self::assertFalse($nonpending_interactor->can_edit());
        self::assertFalse($pending_interactor->can_edit());
        assign_capability("mod/approval:edit_unsubmitted_application_any", CAP_PREVENT, $role->id, $np_a_context, true);
        assign_capability("mod/approval:edit_unsubmitted_application_any", CAP_PREVENT, $role->id, $p_a_context, true);

        // User with a submitted cap can edit submitted application.
        assign_capability("mod/approval:edit_first_approval_level_application_any", CAP_ALLOW, $role->id, $np_a_context, true);
        assign_capability("mod/approval:edit_first_approval_level_application_any", CAP_ALLOW, $role->id, $p_a_context, true);
        self::assertTrue($nonpending_interactor->can_edit());
        self::assertTrue($pending_interactor->can_edit());
        assign_capability("mod/approval:edit_first_approval_level_application_any", CAP_PREVENT, $role->id, $np_a_context, true);
        assign_capability("mod/approval:edit_first_approval_level_application_any", CAP_PREVENT, $role->id, $p_a_context, true);

        // User with an in-approvals cap can edit submitted application.
        assign_capability("mod/approval:edit_in_approvals_application_any", CAP_ALLOW, $role->id, $np_a_context, true);
        assign_capability("mod/approval:edit_in_approvals_application_any", CAP_ALLOW, $role->id, $p_a_context, true);
        self::assertTrue($nonpending_interactor->can_edit());
        self::assertTrue($pending_interactor->can_edit());
        assign_capability("mod/approval:edit_in_approvals_application_any", CAP_PREVENT, $role->id, $np_a_context, true);
        assign_capability("mod/approval:edit_in_approvals_application_any", CAP_PREVENT, $role->id, $p_a_context, true);

        // User with the edit pending cap can edit the submitted pending application.
        assign_capability("mod/approval:edit_in_approvals_pending_application_any", CAP_ALLOW, $role->id, $np_a_context, true);
        assign_capability("mod/approval:edit_in_approvals_pending_application_any", CAP_ALLOW, $role->id, $p_a_context, true);
        self::assertFalse($nonpending_interactor->can_edit());
        self::assertTrue($pending_interactor->can_edit());
        assign_capability("mod/approval:edit_in_approvals_pending_application_any", CAP_PREVENT, $role->id, $np_a_context, true);
        assign_capability("mod/approval:edit_in_approvals_pending_application_any", CAP_PREVENT, $role->id, $p_a_context, true);

        // Set the applications to in approvals on the next approval level.
        $pending_state = $nonpending_application->current_stage->state_manager
            ->get_next_state($nonpending_application->current_state);
        $nonpending_application->set_current_state($pending_state);
        $nonpending_interactor = $nonpending_application->get_interactor($random_user->id);

        $pending_state = $pending_application->current_stage->state_manager->get_next_state($pending_application->current_state);
        $pending_application->set_current_state($pending_state);
        $pending_interactor = $pending_application->get_interactor($manager_user->id);

        self::assertTrue($nonpending_application->current_state->is_stage_type(approvals::get_code()));
        self::assertFalse($nonpending_application->current_state->get_approval_level()->is_first());
        self::assertTrue($pending_application->current_state->is_stage_type(approvals::get_code()));
        self::assertFalse($pending_application->current_state->get_approval_level()->is_first());

        // User with an unsubmitted cap cannot edit approved application.
        assign_capability("mod/approval:edit_unsubmitted_application_any", CAP_ALLOW, $role->id, $np_a_context, true);
        assign_capability("mod/approval:edit_unsubmitted_application_any", CAP_ALLOW, $role->id, $p_a_context, true);
        self::assertFalse($nonpending_interactor->can_edit());
        self::assertFalse($pending_interactor->can_edit());
        assign_capability("mod/approval:edit_unsubmitted_application_any", CAP_PREVENT, $role->id, $np_a_context, true);
        assign_capability("mod/approval:edit_unsubmitted_application_any", CAP_PREVENT, $role->id, $p_a_context, true);

        // User with a submitted cap cannot edit approved application.
        assign_capability("mod/approval:edit_first_approval_level_application_any", CAP_ALLOW, $role->id, $np_a_context, true);
        assign_capability("mod/approval:edit_first_approval_level_application_any", CAP_ALLOW, $role->id, $p_a_context, true);
        self::assertFalse($nonpending_interactor->can_edit());
        self::assertFalse($pending_interactor->can_edit());
        assign_capability("mod/approval:edit_first_approval_level_application_any", CAP_PREVENT, $role->id, $np_a_context, true);
        assign_capability("mod/approval:edit_first_approval_level_application_any", CAP_PREVENT, $role->id, $p_a_context, true);

        // User with an in-approvals cap can edit approved application.
        assign_capability("mod/approval:edit_in_approvals_application_any", CAP_ALLOW, $role->id, $np_a_context, true);
        assign_capability("mod/approval:edit_in_approvals_application_any", CAP_ALLOW, $role->id, $p_a_context, true);
        self::assertTrue($nonpending_interactor->can_edit());
        self::assertTrue($pending_interactor->can_edit());
        assign_capability("mod/approval:edit_in_approvals_application_any", CAP_PREVENT, $role->id, $np_a_context, true);
        assign_capability("mod/approval:edit_in_approvals_application_any", CAP_PREVENT, $role->id, $p_a_context, true);

        // User with the edit pending cap can edit the approved pending application.
        assign_capability("mod/approval:edit_in_approvals_pending_application_any", CAP_ALLOW, $role->id, $np_a_context, true);
        assign_capability("mod/approval:edit_in_approvals_pending_application_any", CAP_ALLOW, $role->id, $p_a_context, true);
        self::assertFalse($nonpending_interactor->can_edit());
        self::assertTrue($pending_interactor->can_edit());
        assign_capability("mod/approval:edit_in_approvals_pending_application_any", CAP_PREVENT, $role->id, $np_a_context, true);
        assign_capability("mod/approval:edit_in_approvals_pending_application_any", CAP_PREVENT, $role->id, $p_a_context, true);
    }

    /**
     * In this test, we are checking that the application is not editable as workflow stage formview does not exist.
     */
    public function test_can_edit_without_formview_records(): void {
        $role = builder::table('role')->where('shortname', 'user')->one();
        // Attention: test_can_edit() is testing when formview records do exists.
        $manager_user = self::getDataGenerator()->create_user();
        $manager_job_assignment = job_assignment::create_default($manager_user->id);

        $context = context_system::instance();
        assign_capability("mod/approval:edit_draft_application_any", CAP_ALLOW, $role->id, $context, true);

        // Without a formview, the application cannot be edited.
        $application = $this->get_application($manager_user->id, false); // Don't create a formview.
        $application_interactor = $application->get_interactor($manager_user->id);
        job_assignment::create_default($application->user_id, ['managerjaid' => $manager_job_assignment->id]);
        self::assertFalse($application_interactor->can_edit());

        // Granting the edit_full_application capability allows editing, regardless of the formview
        assign_capability("mod/approval:edit_full_application_any", CAP_ALLOW, $role->id, $context, true);
        self::assertTrue($application_interactor->can_edit());
        assign_capability("mod/approval:edit_full_application_any", CAP_PREVENT, $role->id, $context, true);

        // With the formview, the application can be edited.
        $application = $this->get_application($manager_user->id); // Create a formview.
        $application_interactor = $application->get_interactor($manager_user->id);
        job_assignment::create_default($application->user_id, ['managerjaid' => $manager_job_assignment->id]);
        self::assertTrue($application_interactor->can_edit());
    }

    /**
     * In these tests, we are checking that the correct states are being taken into account, as well as the
     * correct capability checks are being made.
     * @covers ::can_attach_file
     */
    public function test_can_attach_file(): void {
        $user_role = builder::table('role')->where('shortname', 'user')->one();
        $interactor_user = self::getDataGenerator()->create_user();
        self::setUser($interactor_user);
        $application = $this->get_application();
        $interactor = $application->get_interactor($interactor_user->id);
        $application_context = $application->get_context();

        // Regular Application.
        // User without cap cannot attach file.
        $this->assertFalse($interactor->can_attach_file());

        // User with cap can attach file.
        assign_capability("mod/approval:attach_file_to_application_any", CAP_ALLOW, $user_role->id, $application_context, true);
        $this->assertTrue($interactor->can_attach_file());

        // Submit the application so it goes into pending.
        $response = json_encode([
            'agency_code' => '007',
        ]);
        $interactor_user_entity = new user($interactor_user);
        $submission = application_submission::create_or_update(
            $application,
            $interactor_user_entity->id,
            form_data::from_json($response)
        );
        $submission->publish($interactor_user_entity->id);
        submit::execute($application, $interactor_user_entity->id);

        // User with 'any' cap can still attach file.
        $this->assertTrue($interactor->can_attach_file());

        // Remove the 'any' cap.
        assign_capability("mod/approval:attach_file_to_application_any", CAP_PREVENT, $user_role->id, $application_context, true);
    }

    /**
     * In these tests, we are checking that the correct states are being taken into account, as well as the
     * correct capability checks are being made.
     */
    public function test_can_delete(): void {
        $user_role = builder::table('role')->where('shortname', 'user')->one();
        $application = $this->get_application();

        // Verify it is draft.
        self::assertTrue($application->current_state->is_draft());

        // Owner can delete by default.
        $owner_interactor = $application->get_interactor($application->owner_id);
        self::assertTrue($owner_interactor->can_delete());

        // Without the cap they cannot delete.
        assign_capability(
            "mod/approval:delete_draft_application_owner",
            CAP_PREVENT,
            $user_role->id,
            context_system::instance(),
            true
        );
        self::assertFalse($owner_interactor->can_delete());
        assign_capability(
            "mod/approval:delete_draft_application_owner",
            CAP_ALLOW,
            $user_role->id,
            context_system::instance(),
            true
        );

        // With the cap, they cannot delete when not draft.
        $application->set_current_state(new application_state($application->current_state->get_stage_id()));
        $owner_interactor = $application->get_interactor($application->owner_id);
        self::assertFalse($owner_interactor->can_delete());
    }

    public function test_can_withdraw_when_draft(): void {
        $application = $this->get_application();

        // Site admin cannot withdraw when draft, therefore no one can.
        self::assertTrue($application->current_state->is_draft());
        $interactor = $application->get_interactor(2); // 2 = site admin's id.
        self::assertFalse($interactor->can_withdraw());
    }

    public function test_can_withdraw_when_finished(): void {
        $application = $this->get_application();

        // Site admin cannot withdraw when finished, therefore no one can.
        $approval_stage = $application->get_next_stage();
        $final_stage = $application->get_workflow_version()->get_next_stage($approval_stage->id);
        $application->set_current_state(new application_state($final_stage->id));
        self::assertTrue($application->current_state->is_stage_type(finished::get_code()));
        $interactor = $application->get_interactor(2); // 2 = site admin's id.
        self::assertFalse($interactor->can_withdraw());
    }

    public function test_can_withdraw_when_in_approvals(): void {
        $application = $this->get_application();

        $pending_state = $application->current_stage->state_manager->get_next_state($application->current_state);
        $application->set_current_state($pending_state);
        // Applicants can withdraw when in-approvals by default.
        $interactor = $application->get_interactor($application->user_id);
        self::assertTrue($interactor->can_withdraw());

        // Removing the capability results in losing access.
        $user_role = builder::table('role')->where('shortname', 'user')->one();
        assign_capability(
            "mod/approval:withdraw_in_approvals_application_applicant",
            CAP_PREVENT,
            $user_role->id,
            context_system::instance(),
            true
        );
        self::assertFalse($interactor->can_withdraw());
    }

    public function test_can_withdraw_when_rejected(): void {
        $application = $this->get_application();
        $admin_id = 2;
        self::setAdminUser();
        // Applicants can withdraw when rejected by default.
        $submission = application_submission::create_or_update($application, $admin_id, form_data::create_empty());
        $submission->publish($admin_id);
        submit::execute($application, $admin_id);
        $application->refresh();
        reject::execute($application, $admin_id);
        self::assertEquals(reject::get_code(), $application->last_action->code);
        $interactor = $application->get_interactor($application->user_id);
        self::assertTrue($interactor->can_withdraw());

        // Removing the capability results in losing access.
        $user_role = builder::table('role')->where('shortname', 'user')->one();
        assign_capability(
            "mod/approval:withdraw_unsubmitted_application_applicant",
            CAP_PREVENT,
            $user_role->id,
            context_system::instance(),
            true
        );
        self::assertFalse($interactor->can_withdraw());
    }

    /**
     * In these tests, we are checking that the correct states are being taken into account, as well as the
     * correct capability checks are being made.
     *
     * The ability to clone depends on the ability to see an application and to be able to create a new one.
     */
    public function test_can_clone(): void {
        $role = builder::table('role')->where('shortname', 'user')->one();

        $application = $this->get_application();
        $application->set_current_state(new application_state($application->current_state->get_stage_id()));
        $applicant = $application->get_user();
        $owner = $application->get_owner();
        self::assertNotEquals($applicant->id, $owner->id);

        $application_interactor = $application->get_interactor($applicant->id);
        $assignment_interactor = $application->get_assignment()->get_interactor($applicant->id, $applicant->id);
        $system_context = context_system::instance();

        // User can clone application where they are the applicant because they can see it and they can
        // create applications (default caps).
        self::assertTrue($application_interactor->can_view());
        self::assertTrue($application_interactor->can_edit());
        self::assertTrue($assignment_interactor->can_create_application());
        self::assertTrue($application_interactor->can_clone());

        // If they can't see it but they can still edit it then they can still clone it.
        assign_capability("mod/approval:view_application_applicant", CAP_PREVENT, $role->id, $system_context, true);
        self::assertFalse($application_interactor->can_view());
        self::assertTrue($application_interactor->can_edit());
        self::assertTrue($assignment_interactor->can_create_application());
        self::assertTrue($application_interactor->can_clone());
        assign_capability("mod/approval:view_application_applicant", CAP_ALLOW, $role->id, $system_context, true);

        // If they can't edit it but they can still see it then they can still clone it.
        assign_capability("mod/approval:edit_unsubmitted_application_applicant", CAP_PREVENT, $role->id, $system_context, true);
        self::assertTrue($application_interactor->can_view());
        self::assertFalse($application_interactor->can_edit());
        self::assertTrue($assignment_interactor->can_create_application());
        self::assertTrue($application_interactor->can_clone());
        assign_capability("mod/approval:edit_unsubmitted_application_applicant", CAP_ALLOW, $role->id, $system_context, true);

        // If they can't see or edit it then they can't clone it.
        assign_capability("mod/approval:view_application_applicant", CAP_PREVENT, $role->id, $system_context, true);
        assign_capability("mod/approval:edit_unsubmitted_application_applicant", CAP_PREVENT, $role->id, $system_context, true);
        self::assertFalse($application_interactor->can_view());
        self::assertFalse($application_interactor->can_edit());
        self::assertTrue($assignment_interactor->can_create_application());
        self::assertFalse($application_interactor->can_clone());
        assign_capability("mod/approval:view_application_applicant", CAP_ALLOW, $role->id, $system_context, true);
        assign_capability("mod/approval:edit_unsubmitted_application_applicant", CAP_ALLOW, $role->id, $system_context, true);

        // If they can't create an application then they can't clone it.
        assign_capability("mod/approval:create_application_applicant", CAP_PREVENT, $role->id, $system_context, true);
        self::assertTrue($application_interactor->can_view());
        self::assertTrue($application_interactor->can_edit());
        self::assertFalse($assignment_interactor->can_create_application());
        self::assertFalse($application_interactor->can_clone());
        assign_capability("mod/approval:create_application_applicant", CAP_ALLOW, $role->id, $system_context, true);
    }

    /**
     * In these tests, we are checking that the correct states are being taken into account, as well as the
     * correct capability checks are being made.
     */
    public function test_can_approve(): void {
        $user_role = builder::table('role')->where('shortname', 'user')->one();

        $random_user = self::getDataGenerator()->create_user();
        $nonpending_application = $this->get_application();
        $nonpending_application->set_current_state(new application_state($nonpending_application->current_state->get_stage_id()));
        $nonpending_interactor = $nonpending_application->get_interactor($random_user->id);
        $np_a_context = $nonpending_application->get_assignment()->get_context();

        $manager_user = self::getDataGenerator()->create_user();
        $pending_application = $this->get_application();
        $pending_application->set_current_state(new application_state($pending_application->current_state->get_stage_id()));
        $pending_interactor = $pending_application->get_interactor($manager_user->id);
        $p_a_context = $pending_application->get_assignment()->get_context();
        $manager_job_assignment = job_assignment::create_default($manager_user->id);
        job_assignment::create_default($pending_application->user_id, ['managerjaid' => $manager_job_assignment->id]);

        // Remove the default capabilities.
        $manager_role = builder::table('role')->where('shortname', 'staffmanager')->one();
        $np_u_context = context_user::instance($nonpending_application->user_id);
        $p_u_context = context_user::instance($pending_application->user_id);
        assign_capability("mod/approval:approve_pending_application_user", CAP_PREVENT, $manager_role->id, $np_u_context, true);
        assign_capability("mod/approval:approve_pending_application_user", CAP_PREVENT, $manager_role->id, $p_u_context, true);

        // Verify pending state.
        self::assertFalse($nonpending_interactor->is_pending());
        self::assertFalse($pending_interactor->is_pending());

        // Verify it is not in approvals.
        self::assertTrue($nonpending_application->current_state->is_stage_type(form_submission::get_code()));
        self::assertTrue($pending_application->current_state->is_stage_type(form_submission::get_code()));

        // User with no cap cannot approve when not in approvals.
        self::assertFalse($nonpending_interactor->can_approve());
        self::assertFalse($pending_interactor->can_approve());

        // User with the cap cannot approve when not submitted.
        assign_capability("mod/approval:approve_application_any", CAP_ALLOW, $user_role->id, $np_a_context, true);
        assign_capability("mod/approval:approve_application_any", CAP_ALLOW, $user_role->id, $p_a_context, true);
        self::assertFalse($nonpending_interactor->can_approve());
        self::assertFalse($pending_interactor->can_approve());

        // Set the applications to submitted.
        $pending_state = $nonpending_application->current_stage->state_manager
            ->get_next_state($nonpending_application->current_state);
        $nonpending_application->set_current_state($pending_state);
        $nonpending_interactor = $nonpending_application->get_interactor($random_user->id);
        $pending_state = $pending_application->current_stage->state_manager->get_next_state($pending_application->current_state);
        $pending_application->set_current_state($pending_state);
        $pending_interactor = $pending_application->get_interactor($manager_user->id);
        self::assertTrue($nonpending_application->current_state->is_stage_type(approvals::get_code()));
        self::assertTrue($pending_application->current_state->is_stage_type(approvals::get_code()));

        // User with the cap can approve when submitted regardless of pending state.
        self::assertTrue($nonpending_interactor->can_approve());
        self::assertTrue($pending_interactor->can_approve());
        assign_capability("mod/approval:approve_application_any", CAP_PREVENT, $user_role->id, $np_a_context, true);
        assign_capability("mod/approval:approve_application_any", CAP_PREVENT, $user_role->id, $p_a_context, true);

        // User with the pending cap cannot approve only when pending.
        assign_capability("mod/approval:approve_pending_application_any", CAP_ALLOW, $user_role->id, $np_a_context, true);
        assign_capability("mod/approval:approve_pending_application_any", CAP_ALLOW, $user_role->id, $p_a_context, true);
        self::assertFalse($nonpending_interactor->can_approve());
        self::assertTrue($pending_interactor->can_approve());
        assign_capability("mod/approval:approve_pending_application_any", CAP_PREVENT, $user_role->id, $np_a_context, true);
        assign_capability("mod/approval:approve_pending_application_any", CAP_PREVENT, $user_role->id, $p_a_context, true);

        // User with no cap cannot approve when submitted.
        self::assertFalse($nonpending_interactor->can_approve());
        self::assertFalse($pending_interactor->can_approve());

        // An applicant can only approve if they have the _applicant capability - the _user or _any capabilities are insufficient.
        // Only testing has_approve_capability and has_approve_pending_capability functions, which are used inside can_approve.
        $nonpending_applicant = $nonpending_application->get_user();
        $nonpending_applicant_interactor = $nonpending_application->get_interactor($nonpending_applicant->id);
        $nonpending_other_interactor = $nonpending_application->get_interactor($random_user->id);
        $pending_applicant = $pending_application->get_user();
        $pending_applicant_interactor = $pending_application->get_interactor($pending_applicant->id);
        $pending_other_interactor = $pending_application->get_interactor($manager_user->id);

        // No one has any capability to start with.
        self::assertFalse($nonpending_applicant_interactor->has_approve_capability());
        self::assertFalse($pending_other_interactor->has_approve_pending_capability());
        self::assertFalse($pending_applicant_interactor->has_approve_pending_capability());
        self::assertFalse($nonpending_other_interactor->has_approve_capability());

        // The _any capability is sufficient for the manager but not the applicant.
        assign_capability("mod/approval:approve_application_any", CAP_ALLOW, $user_role->id, $np_a_context, true);
        assign_capability("mod/approval:approve_pending_application_any", CAP_ALLOW, $user_role->id, $p_a_context, true);
        self::assertFalse($nonpending_applicant_interactor->has_approve_capability());
        self::assertFalse($pending_applicant_interactor->has_approve_pending_capability());
        self::assertTrue($nonpending_other_interactor->has_approve_capability());
        self::assertTrue($pending_other_interactor->has_approve_pending_capability());
        assign_capability("mod/approval:approve_application_any", CAP_PREVENT, $user_role->id, $np_a_context, true);
        assign_capability("mod/approval:approve_pending_application_any", CAP_PREVENT, $user_role->id, $p_a_context, true);

        // The _user capability is sufficient for the manager but not the applicant.
        assign_capability("mod/approval:approve_application_user", CAP_ALLOW, $user_role->id, $np_u_context, true);
        assign_capability("mod/approval:approve_pending_application_user", CAP_ALLOW, $user_role->id, $p_u_context, true);
        self::assertFalse($nonpending_applicant_interactor->has_approve_capability());
        self::assertFalse($pending_applicant_interactor->has_approve_pending_capability());
        self::assertTrue($nonpending_other_interactor->has_approve_capability());
        self::assertTrue($pending_other_interactor->has_approve_pending_capability());
        assign_capability("mod/approval:approve_application_user", CAP_PREVENT, $user_role->id, $np_u_context, true);
        assign_capability("mod/approval:approve_pending_application_user", CAP_PREVENT, $user_role->id, $p_u_context, true);

        // The _applicant capability is sufficient for the applicant and it ONLY works for the applicant.
        assign_capability("mod/approval:approve_application_applicant", CAP_ALLOW, $user_role->id, $np_a_context, true);
        assign_capability("mod/approval:approve_pending_application_applicant", CAP_ALLOW, $user_role->id, $p_a_context, true);
        self::assertTrue($nonpending_applicant_interactor->has_approve_capability());
        self::assertTrue($pending_applicant_interactor->has_approve_pending_capability());
        self::assertFalse($nonpending_other_interactor->has_approve_capability());
        self::assertFalse($pending_other_interactor->has_approve_pending_capability());
        assign_capability("mod/approval:approve_application_applicant", CAP_PREVENT, $user_role->id, $np_a_context, true);
        assign_capability("mod/approval:approve_pending_application_applicant", CAP_PREVENT, $user_role->id, $p_a_context, true);
    }

    public function test_can_view_comment_when_draft() {
        $application = $this->get_application();

        // Site admin cannot view comments when draft, therefore no one can.
        self::assertTrue($application->current_state->is_draft());
        $interactor = $application->get_interactor(2); // 2 = site admin's id.
        self::assertFalse($interactor->can_view_comments());
    }

    public function test_can_view_comment_requires_capability() {
        $application = $this->get_application();

        // Owners have view capability by default.
        $application->set_current_state(new application_state($application->current_state->get_stage_id()));
        $interactor = $application->get_interactor($application->owner_id);
        self::assertTrue($interactor->can_view_comments());

        // Without the capability, they cannot view.
        $user_role = builder::table('role')->where('shortname', 'user')->one();
        assign_capability(
            "mod/approval:view_comment_on_application_owner",
            CAP_PREVENT,
            $user_role->id,
            $application->get_context(),
            true
        );
        self::assertFalse($interactor->can_view_comments());
    }

    public function test_can_post_comment_when_draft() {
        $application = $this->get_application();

        // Site admin cannot view comments when draft, therefore no one can.
        self::assertTrue($application->current_state->is_draft());
        $interactor = $application->get_interactor(2); // 2 = site admin's id.
        self::assertFalse($interactor->can_post_comment());
    }

    public function test_can_post_comment_requires_capability() {
        $application = $this->get_application();

        // Owners have post capability by default.
        $application->set_current_state(new application_state($application->current_state->get_stage_id()));
        $interactor = $application->get_interactor($application->owner_id);
        self::assertTrue($interactor->can_post_comment());

        // Without the capability, they cannot post.
        $user_role = builder::table('role')->where('shortname', 'user')->one();
        assign_capability(
            "mod/approval:post_comment_on_application_owner",
            CAP_PREVENT,
            $user_role->id,
            $application->get_context(),
            true
        );
        self::assertFalse($interactor->can_post_comment());
    }

    public function test_can_post_comment_when_pending() {
        // Create an application with manager relationship approvers.
        $application = $this->get_application();

        // Create manager.
        $manager_user = self::getDataGenerator()->create_user();
        $manager_job_assignment = job_assignment::create_default($manager_user->id);
        job_assignment::create_default($application->user_id, ['managerjaid' => $manager_job_assignment->id]);

        // Bump it into in approvals state.
        $pending_state = $application->current_stage->state_manager->get_next_state($application->current_state);
        $application->set_current_state($pending_state);
        $interactor = $application->get_interactor($manager_user->id);
        self::assertTrue($interactor->is_pending());

        // Approver has default capabilities to post comments regardless of pending state.
        self::assertTrue($interactor->can_post_comment());

        // We remove the non-pending capability and show that they cannot post.
        $manager_role = builder::table('role')->where('shortname', 'staffmanager')->one();
        assign_capability(
            "mod/approval:post_comment_on_application_user",
            CAP_PREVENT,
            $manager_role->id,
            context_user::instance($application->user_id),
            true
        );
        self::assertFalse($interactor->can_post_comment());

        // When granted the pending capability, they can post.
        assign_capability(
            "mod/approval:post_comment_on_pending_application_user",
            CAP_ALLOW,
            $manager_role->id,
            context_user::instance($application->user_id),
            true
        );
        self::assertTrue($interactor->can_post_comment());

        // When not pending, the pending capability does not grant access.
        $application->set_current_state(new application_state($application->workflow_version->stages->first()->id));
        builder::table(application_entity::TABLE)->where('id', $application->id)->update(['current_approval_level_id' => null]);
        $application = application_model::load_by_id($application->id);
        $interactor = $application->get_interactor($manager_user->id);
        self::assertFalse($interactor->can_post_comment());
    }
}
