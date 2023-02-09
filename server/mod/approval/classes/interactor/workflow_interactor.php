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

use container_approval\approval;
use context_course;
use mod_approval\model\workflow\workflow;

/**
 * A helper class that is constructed with an approval workflow's context and a user's id, which helps to fetch
 * all the available actions that the user can perform in relation to an approval workflow.
 *
 * The main purpose is to expose whether a user can manage an approval workflow.
 *
 * @package mod_approval\interactor
 */
final class workflow_interactor {

    /**
     * The approval workflow.
     *
     * @var workflow
     */
    private $workflow;

    /**
     * The approval workflow context to check against.
     *
     * @var context_course
     */
    private $course_context;

    /**
     * The user id of the user who is interacting with the approval workflow.
     *
     * @var int
     */
    private $user_id;

    /**
     * @param workflow $workflow
     * @param int|null $interactor_user_id
     */
    private function __construct(
        workflow $workflow,
        int $interactor_user_id
    ) {
        $this->workflow = $workflow;
        $this->course_context = $workflow->get_context();
        $this->user_id = $interactor_user_id;
    }

    /**
     * Get workflow interactor from workflow.
     *
     * @param workflow $workflow
     * @param int $user_id
     * @return self
     */
    public static function from_workflow(workflow $workflow, int $user_id): self {
        return new self($workflow, $user_id);
    }

    /**
     * Can the interacting user edit the workflow.
     *
     * @return bool
     */
    public function can_edit(): bool {
        if ($this->workflow->latest_version->is_archived()) {
            return false;
        }

        return ($this->workflow->latest_version->is_draft()
            && $this->has_edit_draft_capability())
        || $this->has_edit_active_capability();
    }

    /**
     * Can the interacting user view applications report on the workflow
     *
     * @return bool
     */
    public function can_view_applications_report(): bool {
        return !$this->workflow->latest_version->is_draft() && has_capability(
            'mod/approval:view_workflow_applications_report',
            $this->course_context,
            $this->user_id
        );
    }

    /**
     * Does the user have capability to edit a workflow, assuming that it is draft (must be checked externally).
     * CW04
     *
     * @return bool
     */
    public function has_edit_draft_capability(): bool {
        return has_capability(
            'mod/approval:edit_draft_workflow',
            $this->course_context,
            $this->user_id
        );
    }

    /**
     * Does the user have capability to edit a workflow, assuming that it is active (must be checked externally).
     * CW05
     *
     * @return bool
     */
    public function has_edit_active_capability(): bool {
        return has_capability(
            'mod/approval:edit_active_workflow',
            $this->course_context,
            $this->user_id
        );
    }

    /**
     * Can the user activate the workflow?
     *
     * @return bool
     */
    public function can_activate(): bool {
        $workflow_version = $this->workflow->latest_version;
        $can_be_activated = $workflow_version->is_draft() || $workflow_version->is_archived();

        return $can_be_activated && $this->has_activate_capability();
    }

    /**
     * Does the user have capability to activate a workflow, assuming that it is draft (must be checked externally).
     * CW06
     *
     * @return bool
     */
    public function has_activate_capability(): bool {
        return has_capability(
            'mod/approval:activate_workflow',
            $this->course_context,
            $this->user_id
        );
    }

    /**
     * Can the user archive the workflow?
     *
     * @return bool
     */
    public function can_archive(): bool {
        return $this->has_archive_capability() && $this->workflow->is_any_active();
    }

    /**
     * Can the user publish the workflow?
     *
     * @return bool
     */
    public function can_publish(): bool {
        return $this->has_activate_capability() && $this->workflow->are_any_draft();
    }

    /**
     * Can the user unarchive the workflow?
     *
     * @return bool
     */
    public function can_unarchive(): bool {
        return $this->has_archive_capability() && !$this->workflow->is_any_active() && $this->workflow->is_any_archived();
    }

    /**
     * Does the user have capability to archive a workflow, assuming that it is active (must be checked externally).
     * CW07
     *
     * @return bool
     */
    public function has_archive_capability(): bool {
        return has_capability(
            'mod/approval:archive_workflow',
            $this->course_context,
            $this->user_id
        );
    }

    /**
     * Does the user have capability to edit a workflow template, assuming that it is a template (must be checked externally).
     * CW09
     *
     * @return bool
     */
    public function has_edit_template_capability(): bool {
        return has_capability(
            'mod/approval:edit_workflow_template',
            $this->course_context,
            $this->user_id
        );
    }

    /**
     * Does the user have capability to manage the workflow's stages.
     * CW10
     *
     * @return bool
     */
    public function has_manage_stages_capability(): bool {
        return has_capability(
            'mod/approval:manage_workflow_stages',
            $this->course_context,
            $this->user_id
        );
    }

    /**
     * Does the user have capability to manage the workflow's form views.
     * CW11
     *
     * @return bool
     */
    public function has_manage_formview_capability(): bool {
        return has_capability(
            'mod/approval:manage_workflow_form_view',
            $this->course_context,
            $this->user_id
        );
    }

    /**
     * Does the user have capability to add approval levels to a workflow.
     * CW12
     *
     * @return bool
     */
    public function has_add_approval_level_capability(): bool {
        return has_capability(
            'mod/approval:add_workflow_approval_level',
            $this->course_context,
            $this->user_id
        );
    }

    /**
     * Does the user have capability to delete approval levels from a workflow.
     * CW20
     *
     * @return bool
     */
    public function has_delete_approval_level_capability(): bool {
        return has_capability(
            'mod/approval:delete_workflow_approval_level',
            $this->course_context,
            $this->user_id
        );
    }

    /**
     * Does the user have capability to reorder a workflow's approval levels.
     * CW13
     *
     * @return bool
     */
    public function has_reorder_approval_level_capability(): bool {
        return has_capability(
            'mod/approval:reorder_workflow_approval_level',
            $this->course_context,
            $this->user_id
        );
    }

    /**
     * Can manage workflow approvers - just makes sure that you can manage at least one type of workflow approvers.
     * CW14, CW15
     *
     * @return bool
     */
    public function can_manage_workflow_approvers(): bool {
        return has_any_capability(
            [
                'mod/approval:manage_individual_workflow_approvers',
                'mod/approval:manage_relationship_workflow_approvers',
            ],
            $this->course_context,
            $this->user_id
        );
    }

    /**
     * Does the user have capability to manage 'individual' type workflow approvers.
     * Applies to both default and override assignments.
     *
     * CW14
     *
     * @return bool
     */
    public function has_manage_individual_approvers_capability(): bool {
        return has_capability(
            'mod/approval:manage_individual_workflow_approvers',
            $this->course_context,
            $this->user_id
        );
    }

    /**
     * Does the user have capability to manage 'relationship' type workflow approvers.
     * Applies to both default and override assignments.
     *
     * CW15
     *
     * @return bool
     */
    public function has_manage_relationship_approvers_capability(): bool {
        return has_capability(
            'mod/approval:manage_relationship_workflow_approvers',
            $this->course_context,
            $this->user_id
        );
    }

    /**
     * Can the user upload approver overrides for all stages.
     *
     * @return bool
     */
    public function can_upload_approver_overrides(): bool {
        return $this->has_manage_assignment_overrides_capability();
    }

    /**
     * Does the user have capability to manage(create, update or delete) a workflow's assignment overrides(non-default assignments).
     * Managing the approvers within an assignment override requires CW14 & CW15.
     *
     * CW16
     *
     * @return bool
     */
    public function has_manage_assignment_overrides_capability(): bool {
        return has_capability(
            'mod/approval:manage_workflow_assignment_overrides',
            $this->course_context,
            $this->user_id
        );
    }

    /**
     * Does the user have capability to manage a workflow's transitions.
     * CW17
     *
     * @return bool
     */
    public function has_manage_transitions_capability(): bool {
        return has_capability(
            'mod/approval:manage_workflow_transitions',
            $this->course_context,
            $this->user_id
        );
    }

    /**
     * Does the user have capability to manage a workflow's notifications.
     * CW18
     *
     * @return bool
     */
    public function has_manage_notifications_capability(): bool {
        return has_capability(
            'mod/approval:manage_workflow_notifications',
            $this->course_context,
            $this->user_id
        );
    }

    /**
     * Can the user delete the workflow?
     *
     * @return boolean
     */
    public function can_delete(): bool {
        return $this->workflow->are_all_draft() && $this->has_edit_draft_capability();
    }

    /**
     * Determine if the user can clone the workflow.
     *
     * @return boolean
     */
    public function can_clone(): bool {
        $category_interactor = new category_interactor(
            approval::get_default_category_context(),
            $this->user_id
        );

        return $category_interactor->has_clone_workflow_capability();
    }

    /**
     * Does the user have capability to assign roles to the workflow.
     * CW18
     *
     * @return bool
     */
    public function has_assign_roles_capability(): bool {
        return has_capability(
            'moodle/role:assign',
            $this->get_context(),
            $this->get_user_id()
        );
    }

    /**
     * Can the user assign roles, taking into account state, etc.
     *
     * @return bool
     */
    public function can_assign_roles(): bool {
        return $this->has_assign_roles_capability();
    }

    /**
     * @return int
     */
    public function get_user_id(): int {
        return $this->user_id;
    }

    /**
     * @return context_course
     */
    public function get_context(): context_course {
        return $this->course_context;
    }

    /**
     * Get workflow.
     *
     * @return workflow
     */
    public function get_workflow(): workflow {
        return $this->workflow;
    }

    /**
     * Magic attribute getter
     *
     * @param string $field
     * @return mixed|null
     */
    public function __get(string $field) {
        $get_method = 'get_' . $field;

        return method_exists($this, $get_method)
            ? $this->$get_method()
            : null;
    }

    /**
     * Checks if method for magic property exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function __isset(string $name) {
        return method_exists($this, "get_{$name}");
    }
}