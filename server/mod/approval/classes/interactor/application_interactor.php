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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\interactor;

use context_module;
use context_user;
use mod_approval\model\application\action\reject;
use mod_approval\model\application\application;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\stage_type\finished;
use mod_approval\model\workflow\stage_type\form_submission;

/**
 * A helper class that is constructed with an approval workflow application and a user's id, which helps
 * to fetch all the available actions that the user can perform in relation to an approval workflow application.
 *
 * The main purpose is to expose whether a user can manage an approval workflow application.
 */
class application_interactor {
    /**
     * The application.
     *
     * @var application
     */
    private $application;

    /**
     * The approval workflow assignment context to check against.
     *
     * @var context_module
     */
    private $activity_context;

    /**
     * The user id of the user who the application is about.
     *
     * @var int
     */
    private $applicant_user_id;

    /**
     * The user id of the user who is the owner of the application.
     *
     * @var int
     */
    private $owner_user_id;

    /**
     * The applicant user context to check against.
     *
     * @var context_user
     */
    private $applicant_context;

    /**
     * The user id of the user who is interacting with the approval workflow assignment.
     *
     * @var int
     */
    private $interactor_user_id;

    /**
     * @param application $application
     * @param int $interactor_user_id
     */
    protected function __construct(
        application $application,
        int $interactor_user_id
    ) {
        $this->application = $application;
        $this->activity_context = $application->get_context();
        $this->applicant_user_id = (int) $application->user_id;
        $this->owner_user_id = (int) $application->owner_id;
        $this->applicant_context = context_user::instance($application->user_id);
        $this->interactor_user_id = (int) $interactor_user_id;
    }

    /**
     * Create an instance from an application id.
     *
     * @param int $application_id
     * @param int $interactor_user_id
     * @return application_interactor
     */
    public static function from_application_id(
        int $application_id,
        int $interactor_user_id
    ): application_interactor {
        $application_model = application::load_by_id($application_id);

        return new static($application_model, $interactor_user_id);
    }

    /**
     * Create an instance from an application model instance.
     *
     * @param application $application
     * @param int $interactor_user_id
     * @return self
     */
    public static function from_application_model(application $application, int $interactor_user_id): self {
        return new self($application, $interactor_user_id);
    }

    /**
     * Indicates whether the application is pending for the interactor user.
     *
     * @return bool
     */
    public function is_pending(): bool {
        $current_approval_level = $this->application->current_state->get_approval_level();

        if (empty($current_approval_level)) {
            return false;
        }

        // What a hack: $ignore_caps must be set to true in order to avoid infinite recursion.
        // interactor::is_pending
        // -> application::get_approver_users
        //   -> interactor::can_approve
        //     -> interactor::is_pending
        //       -> application::get_approver_users
        //           ...
        // Also it avoids breaking some phpunit tests that don't strictly set up criteria required by get_approver_users.
        $approvers = $this->application->get_approver_users($current_approval_level, true);
        return isset($approvers[$this->interactor_user_id]);
    }

    /**
     * Returns true if the interactor is the applicant.
     *
     * @return bool
     */
    private function interactor_is_applicant(): bool {
        return $this->applicant_user_id === $this->interactor_user_id;
    }

    /**
     * Returns true if the interactor is the applicant.
     *
     * @return bool
     */
    private function interactor_is_owner(): bool {
        return $this->owner_user_id === $this->interactor_user_id;
    }

    /**
     * Check the _any capability for the given capability fragment.
     * - 'mod/approval:fragment_any' is checked against the assignment context
     *
     * @param string $prefix the prefix prepending to _any
     * @return bool
     */
    private function has_any_capability(string $prefix): bool {
        return has_capability(
            'mod/approval:' . $prefix . '_any',
            $this->activity_context,
            $this->interactor_user_id
        );
    }

    /**
     * Check the _user capability for the given capability fragment.
     * - 'mod/approval:fragment_user' is checked against the applicant's user context
     *
     * @param string $prefix the prefix prepending to _user
     * @return bool
     */
    private function has_user_capability(string $prefix): bool {
        return has_capability(
            'mod/approval:' . $prefix . '_user',
            $this->applicant_context,
            $this->interactor_user_id
        );
    }

    /**
     * Check the _applicant capability for the given capability fragment.
     * - 'mod/approval:fragment_applicant' is checked against the assignment context when the interactor is the applicant
     *
     * @param string $prefix the prefix prepending to _applicant
     * @return bool
     */
    private function has_applicant_capability(string $prefix): bool {
        return $this->interactor_is_applicant() && has_capability(
            'mod/approval:' . $prefix . '_applicant',
            $this->activity_context,
            $this->interactor_user_id
        );
    }

    /**
     * Check the _owner capability for the given capability fragment.
     * - 'mod/approval:fragment_owner' is checked against the assignment context when the interactor is the owner
     *
     * @param string $prefix the prefix prepending to _applicant
     * @return bool
     */
    private function has_owner_capability(string $prefix): bool {
        return $this->interactor_is_owner() && has_capability(
            'mod/approval:' . $prefix . '_owner',
            $this->activity_context,
            $this->interactor_user_id
        );
    }

    /**
     * Check _any, _user and _applicant capabilities for the given capability fragment.
     * - fragment_any is checked against the assignment context
     * - fragment_user is checked against the applicant's user context
     * - fragment_applicant is checked against the assignment context when the interactor is the applicant
     *
     * @param string $prefix the prefix prepending to _any, _user and _applicant
     * @return bool
     */
    private function has_capability(string $prefix): bool {
        return $this->has_any_capability($prefix) ||
            $this->has_user_capability($prefix) ||
            $this->has_applicant_capability($prefix) ||
            $this->has_owner_capability($prefix);
    }

    /**
     * Has a capability allowing the user to view a draft application in the dashboard.
     * CX00a
     *
     * The owner always has the capability to view their draft applications in the dashboard.
     *
     * @return bool
     */
    public function has_view_draft_in_dashboard_capability(): bool {
        return $this->has_any_capability('view_draft_in_dashboard_application') ||
            $this->has_user_capability('view_draft_in_dashboard_application') ||
            $this->has_applicant_capability('view_draft_in_dashboard_application');
    }

    /**
     * Has a capability allowing the user to view a draft application.
     * CX01
     *
     * @return bool
     */
    public function has_view_draft_capability(): bool {
        return $this->has_capability('view_draft_application');
    }

    /**
     * Has a capability allowing the user to edit a draft application.
     * CX02
     *
     * @return bool
     */
    public function has_edit_draft_capability(): bool {
        return $this->has_capability('edit_draft_application');
    }

    /**
     * Has a capability allowing the user to delete a draft application.
     * CX03
     *
     * @return bool
     */
    public function has_delete_draft_capability(): bool {
        return $this->has_capability('delete_draft_application');
    }

    /**
     * Has a capability allowing the user to view a non-draft application in the dashboard.
     * CX00b
     *
     * The owner always has the capability to view their non-draft applications in the dashboard.
     *
     * @return bool
     */
    public function has_view_in_dashboard_capability(): bool {
        return $this->has_any_capability('view_in_dashboard_application') ||
            $this->has_user_capability('view_in_dashboard_application') ||
            $this->has_applicant_capability('view_in_dashboard_application');
    }

    /**
     * Has a capability allowing the user to view only a pending application in the dashboard.
     * CX00c
     *
     * @return bool
     */
    public function has_view_in_dashboard_pending_capability(): bool {
        return $this->has_any_capability('view_in_dashboard_pending_application') ||
            $this->has_user_capability('view_in_dashboard_pending_application');
    }

    /**
     * Has a capability allowing the user to view an application when NOT draft.
     * CX04
     *
     * @return bool
     */
    public function has_view_capability(): bool {
        return $this->has_capability('view_application');
    }

    /**
     * Has a capability allowing the user to view a pending application when NOT draft.
     * CX05
     *
     * @return bool
     */
    public function has_view_pending_capability(): bool {
        return $this->has_user_capability('view_pending_application') ||
            $this->has_any_capability('view_pending_application');
    }

    /**
     * Has a capability allowing the user to edit an unsubmitted application.
     * CX06
     *
     * @return bool
     */
    public function has_edit_unsubmitted_capability(): bool {
        return $this->has_capability('edit_unsubmitted_application');
    }

    /**
     * Has a capability allowing the user to edit a submitted, in-approvals application.
     * CX07
     *
     * @return bool
     */
    public function has_edit_in_approvals_capability(): bool {
        return $this->has_capability('edit_in_approvals_application');
    }

    /**
     * Has a capability allowing the user to edit a submitted, in-approvals pending application.
     * CX08
     *
     * @return bool
     */
    public function has_edit_in_approvals_pending_capability(): bool {
        return $this->has_user_capability('edit_in_approvals_pending_application') ||
            $this->has_any_capability('edit_in_approvals_pending_application');
    }

    /**
     * Has a capability allowing the user to edit a submitted, in-approvals application.
     * CX09
     *
     * @return bool
     */
    public function has_edit_first_approval_level_capability(): bool {
        return $this->has_capability('edit_first_approval_level_application');
    }

    /**
     * Has a capability allowing the user to edit a submitted, in-approvals pending application.
     * CX10
     *
     * @return bool
     */
    public function has_edit_first_approval_level_pending_capability(): bool {
        return $this->has_user_capability('edit_first_approval_level_pending_application') ||
            $this->has_any_capability('edit_first_approval_level_pending_application');
    }

    /**
     * Has a capability allowing the user to edit an application without resetting approvals.
     * CX11
     *
     * Note that this capability is a modifier for the standard edit capabilities. This capability alone
     * does not allow a user to edit applications.
     *
     * @return bool
     */
    public function has_edit_without_invalidating_approvals_capability(): bool {
        return $this->has_capability('edit_without_invalidating_approvals');
    }

    /**
     * Has a capability allowing the user to edit the full form of an application.
     * CX11b
     *
     * @return bool
     */
    public function has_edit_full_application(): bool {
        return $this->has_capability('edit_full_application');
    }

    /**
     * Has a capability allowing the user to approve applications.
     * CX12
     *
     * Users cannot approve applicantions where they are the applicant, unless they explicitly have the
     * approve_application_applicant capability.
     *
     * User do NOT need to be approvers in order to approve.
     *
     * @return bool
     */
    public function has_approve_capability(): bool {
        if ($this->interactor_is_applicant()) {
            return $this->has_applicant_capability('approve_application');
        } else {
            return $this->has_any_capability('approve_application') ||
                $this->has_user_capability('approve_application') ||
                $this->has_owner_capability('approve_application');
        }
    }

    /**
     * Has a capability allowing the user to approve pending application belonging to the applicant.
     * CX13
     *
     * Users cannot approve pending applications where they are the applicant, unless they explicitly have the
     * approve_pending_application_applicant capability.
     *
     * Users DO need to be approvers to approve with the pending capability, because they can only be
     * 'pending' if they are an approver (checked in can_approve).
     *
     * @return bool
     */
    public function has_approve_pending_capability(): bool {
        if ($this->interactor_is_applicant()) {
            return $this->has_applicant_capability('approve_pending_application');
        } else {
            return $this->has_any_capability('approve_pending_application') ||
                $this->has_user_capability('approve_pending_application') ||
                $this->has_owner_capability('approve_pending_application');
        }
    }

    /**
     * Has a capability allowing the user to attach files in an application.
     * CX14
     *
     * Note that this capability is a modifier for the standard edit capabilities. This capability alone
     * does not allow a user to attach files to applications.
     *
     * @return bool
     */
    public function has_attach_file_capability(): bool {
        return $this->has_capability('attach_file_to_application');
    }

    /**
     * Has a capability allowing the user to view comments on an application.
     * CX15
     *
     * @return bool
     */
    public function has_view_comment_capability(): bool {
        return $this->has_capability('view_comment_on_application');
    }

    /**
     * Has a capability allowing the user to post comment on an application.
     * CX16
     *
     * @return bool
     */
    public function has_post_comment_capability(): bool {
        return $this->has_capability('post_comment_on_application');
    }

    /**
     * Has a capability allowing the user to post comment on a pending application.
     * CX17
     *
     * @return bool
     */
    public function has_post_comment_pending_capability(): bool {
        return $this->has_user_capability('post_comment_on_pending_application') ||
            $this->has_any_capability('post_comment_on_pending_application');
    }

    /**
     * Has a capability allowing the user to withdraw unsubmitted applications.
     * CX18
     *
     * @return bool
     */
    public function has_withdraw_unsubmitted_capability(): bool {
        return $this->has_capability('withdraw_unsubmitted_application');
    }

    /**
     * Has a capability allowing the user to withdraw in-approvals applications.
     * CX19
     *
     * @return bool
     */
    public function has_withdraw_in_approvals_capability(): bool {
        return $this->has_capability('withdraw_in_approvals_application');
    }

    /**
     * Has a capability allowing the user to backdate dates in applications.
     * CX20
     *
     * @return bool
     */
    public function has_backdate_capability(): bool {
        return $this->has_capability('backdate_application');
    }

    /**
     * Determine if a user can view the application in the dashboard, taking into account the state of the application, etc.
     *
     * The owner always has the ability to view their applications in the dashboard.
     *
     * @return bool
     */
    public function can_view_in_dashboard(): bool {
        if ($this->interactor_is_owner()) {
            return true;
        }

        // CX00a
        if ($this->application->current_state->is_draft()) {
            return $this->has_view_draft_in_dashboard_capability();
        }

        // CX00b
        if ($this->has_view_in_dashboard_capability()) {
            return true;
        }

        // CX00c
        return $this->has_view_in_dashboard_pending_capability() && $this->is_pending();
    }

    /**
     * Determine if the user can view the application, taking into account the state of the application, etc.
     *
     * @return boolean
     */
    public function can_view(): bool {
        if ($this->application->current_state->is_draft()) {
            // CX01
            return $this->has_view_draft_capability();
        }

        // CX04
        if ($this->has_view_capability()) {
            return true;
        }

        // CX05
        return $this->has_view_pending_capability() && $this->is_pending();
    }

    /**
     * Determine if the user can edit the application, taking into account the state of the application, etc.
     *
     * @return boolean
     */
    public function can_edit(): bool {
        $current_state = $this->application->current_state;
        $current_stage = $current_state->get_stage();

        $formviews = $current_stage->get_formviews();
        if (!$formviews->valid() && !$this->has_edit_full_application()) {
            return false;
        }


        if ($current_state->is_draft()) {
            // CX02
            return $this->has_edit_draft_capability();
        }

        if ($current_stage->get_type()::get_code() == finished::get_code()) {
            return false;
        }

        if ($current_stage->get_type()::get_code() == form_submission::get_code()) {
            // CX06
            return $this->has_edit_unsubmitted_capability();
        }

        // Else in-approvals.

        // CX07
        if ($this->has_edit_in_approvals_capability()) {
            return true;
        }

        // CX08
        if ($this->is_pending() && $this->has_edit_in_approvals_pending_capability()) {
            return true;
        }

        // Check for the first approval level.
        $current_approval_level = $current_state->get_approval_level();
        if ($current_approval_level && $current_approval_level->ordinal_number == 1) {
            // CX09
            if ($this->has_edit_first_approval_level_capability()) {
                return true;
            }

            // CX10
            if ($this->is_pending() && $this->has_edit_first_approval_level_pending_capability()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the user can edit an application without invalidating existing approvals,
     * taking into account the state of the application, etc.
     *
     * @return boolean
     */
    public function can_edit_without_invalidating(): bool {
        // CX11
        return $this->has_edit_without_invalidating_approvals_capability();
    }

    /**
     * Determine if the user can delete the application, taking into account the state of the application, etc.
     *
     * @return boolean
     */
    public function can_delete(): bool {
        // CX03
        return $this->application->current_state->is_draft() && $this->has_delete_draft_capability();
    }

    /**
     * Determine if the user can mark approval (approved/rejected) in the application, taking into account
     * the state of the application, etc.
     *
     * @return boolean
     */
    public function can_approve(): bool {
        if (!$this->application->current_state->is_stage_type(approvals::get_code())) {
            return false;
        }

        // CX12
        if ($this->has_approve_capability()) {
            return true;
        }

        // CX13
        return $this->is_pending() && $this->has_approve_pending_capability();
    }

    /**
     * Determine if the user can attach files in the application, taking into account
     * the state of the application, etc.
     *
     * @return boolean
     */
    public function can_attach_file(): bool {
        // CX14
        return $this->has_attach_file_capability();
    }

    /**
     * Determine if the user can view comments in the application, taking into account
     * the state of the application, etc.
     *
     * @return boolean
     */
    public function can_view_comments(): bool {
        if ($this->application->current_state->is_draft()) {
            return false;
        }

        // CX15
        return $this->has_view_comment_capability();
    }

    /**
     * Determine if the user can post comments on the application, taking into account
     * the state of the application, etc.
     *
     * @return boolean
     */
    public function can_post_comment(): bool {
        if ($this->application->current_state->is_draft() ||
            $this->application->current_state->is_stage_type(finished::get_code())) {
            return false;
        }

        // CX16
        if ($this->has_post_comment_capability()) {
            return true;
        }

        // CX17
        return $this->is_pending() && $this->has_post_comment_pending_capability();
    }

    /**
     * Determine if the user can withdraw the application, taking into account the state of the application, etc.
     *
     * @return boolean
     */
    public function can_withdraw(): bool {
        if ($this->application->current_state->is_draft() ||
            $this->application->current_state->is_stage_type(finished::get_code())) {
            return false;
        }

        if ($this->application->current_state->is_stage_type(form_submission::get_code())) {
            if (!$this->application->last_action
                || $this->application->last_action->code != reject::get_code()
            ) {
                // TODO This check should be removed when we implement proper withdrawing from unsubmitted states.
                return false;
            }
            // CX18
            return $this->has_withdraw_unsubmitted_capability();
        }

        // Else in-approvals.
        // CX19
        return $this->has_withdraw_in_approvals_capability();
    }

    /**
     * Determine if the user can clone the application, taking into account the state of the application, etc.
     *
     * @return boolean
     */
    public function can_clone(): bool {
        $can_view_or_edit = $this->can_view() || $this->can_edit();
        if (!$can_view_or_edit) {
            return false;
        }

        if (!$this->application->get_workflow_version()->workflow->get_latest_version()->is_active()) {
            return false;
        }

        $assignment_interactor = new assignment_interactor(
            $this->application->get_assignment()->get_context(),
            $this->applicant_user_id,
            $this->interactor_user_id
        );

        return $assignment_interactor->can_create_application();
    }

    /**
     * Determine if the user can delete comment in the application, taking into account
     * the state of the application, etc.
     *
     * @return boolean
     */
    public function can_delete_comment(): bool {
        if ($this->application->current_state->is_draft() ||
            $this->application->current_state->is_stage_type(finished::get_code())) {
            return false;
        }

        // Let the user delete their comment while the application is in-progress.
        return true;
    }

    /**
     * @return application
     */
    public function get_application(): application {
        return $this->application;
    }

    /**
     * @return int
     */
    public function get_interactor_user_id(): int {
        return $this->interactor_user_id;
    }
}
