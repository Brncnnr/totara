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

namespace mod_approval\watcher;

use container_approval\approval;
use core\orm\query\builder;
use core_user\hook\allow_view_profile;
use core_user\hook\allow_view_profile_field;
use core_user\profile\display_setting;
use mod_approval\entity\application\application as application_entity;
use mod_approval\entity\assignment\assignment_approver;
use mod_approval\model\assignment\approver_type\relationship;
use mod_approval\model\assignment\approver_type\user;
use totara_core\advanced_feature;
use totara_core\hook\base;

class core_user {

    /**
     * Handles the allow_view_profile hook call.
     *
     * This method will give access to the users profile based on approval workflow logic.
     *
     * @param allow_view_profile $hook
     */
    public static function handle_allow_view_profile(allow_view_profile $hook) {
        global $CFG;

        // Not our problem.
        if (advanced_feature::is_disabled('approval_workflows')) {
            return;
        }

        if ($hook->has_permission()) {
            // They already have permission.
            return;
        }

        $course = $hook->get_course();
        if ($course) {
            // Nope, we are within course context - need to stay away from it.
            return;
        }

        if (self::has_user_relationship($hook)) {
            $hook->give_permission();
            return;
        }
    }

    /**
     * User access hook to check if one user can view another users profile field in the context of a workflow assignment.
     *
     * @param allow_view_profile_field $hook
     */
    public static function allow_view_profile_field(allow_view_profile_field $hook): void {
        if ($hook->has_permission()) {
            return;
        }

        // Not our problem.
        if (advanced_feature::is_disabled('approval_workflows')) {
            return;
        }

        // Ignore anything other than approval workflow containers.
        $course = $hook->get_course();
        if (!$course || $course->containertype !== approval::get_type()) {
            return;
        }

        // Handle site admins explicitly (performance optimisation).
        if (is_siteadmin()) {
            $hook->give_permission();
            return;
        }

        // Check for any user data which is required specifically for approval workflows (which may
        // or may not have overlap with the user profile card fields below).
        if (in_array($hook->field, ['firstname', 'lastname', 'email', 'url'])
            || in_array($hook->field, display_setting::get_display_fields())
            || in_array($hook->field, display_setting::get_default_display_picture_fields())
        ) {
            if (self::can_view_user($hook)) {
                $hook->give_permission();
                return;
            }
        }

        // If the field is one required to display a user profile card and hasn't already been granted
        // above then check if the viewer is in any situation where they need to be able to select from
        // all (tenant) users.
        if (in_array($hook->field, display_setting::get_display_fields())
            || in_array($hook->field, display_setting::get_display_picture_fields())
        ) {
            if (self::can_select_any_user($hook)) {
                $hook->give_permission();
                return;
            }
        }

        return;
    }

    /**
     * User access hook to check if one user can select any (tenant) user in the context of a workflow or assignment.
     *
     * @param base|allow_view_profile|allow_view_profile_field $hook
     * @return bool
     */
    private static function can_select_any_user(base $hook): bool {
        // Check for can_manage_individual_approvers capability
        if (has_capability('mod/approval:manage_individual_workflow_approvers', $hook->get_course_context(), $hook->viewing_user_id)) {
            return true;
        }
        return false;
    }


    /**
     * User access hook to check if one user can view another users profile data in the context of a workflow assignment.
     *
     * @param base|allow_view_profile|allow_view_profile_field $hook
     * @return bool
     */
    private static function can_view_user(base $hook): bool {
        // If the target user is an applicant or owner of an application in this workflow, let them be viewed.
        // Ideally this would resolve down to the activity level, but user visibility is meant to be determined at course level.
        $applications = application_entity::repository()
            ->as('application')
            ->where_not_null('application.submitted')
            ->join(['approval_workflow_version', 'workflow_version'], 'application.workflow_version_id', '=', 'id')
            ->join(['approval_workflow', 'workflow'], 'workflow_version.workflow_id', '=', 'id')
            ->select('id')
            ->where('workflow.course_id', '=', $hook->get_course()->id)
            ->where('application.user_id', '=', $hook->target_user_id)
            ->or_where('application.owner_id', '=', $hook->target_user_id);
        if ($applications->exists()) {
            return true;
        }

        // If the target user is an individual approver on this workflow, let them be viewed.
        $approvers = assignment_approver::repository()
            ->as('approver')
            ->where('approver.active', '=', 1)
            ->join(['approval', 'assignment'], 'approver.approval_id', '=', 'id')
            ->select('id')
            ->where('assignment.course', '=', $hook->get_course()->id)
            ->where('approver.type', '=', user::TYPE_IDENTIFIER)
            ->where('approver.identifier', '=', $hook->target_user_id);
        if ($approvers->exists()) {
            return true;
        }

        // If the workflow has manager approvers, and the target_user is the manager via job assignment of a user
        // with a submitted application.
        // TODO TL-31334
        return true;

        //return false;
    }

    /**
     * User access hook to check if one user can view another users profile data.
     *
     * @param base|allow_view_profile|allow_view_profile_field $hook
     * @return bool
     */
    private static function has_user_relationship(base $hook): bool {
        // Is the viewing user an approver for any of the target user's assignments?
        $approvers = assignment_approver::repository()
            ->as('approver')
            ->where('approver.active', '=', 1)
            ->join(['approval_application', 'application'], 'approver.approval_id', '=', 'approval_id')
            ->select('id')
            ->where('approver.type', '=', user::TYPE_IDENTIFIER)
            ->where('approver.identifier', '=', $hook->viewing_user_id)
            ->where('application.user_id', '=', $hook->target_user_id);
        if ($approvers->exists()) {
            return true;
        }

        // Is the viewing user an approver manager for any of the target user's assignments?
        $approvers = assignment_approver::repository()
            ->as('approver')
            ->where('approver.active', '=', 1)
            ->join(['approval_application', 'application'], 'approver.approval_id', '=', 'approval_id')
            ->left_join(['job_assignment', 'ja'], 'application.job_assignment_id', '=', 'ja.id')
            ->select('id')
            ->where('application.user_id', '=', $hook->target_user_id)
            ->where('approver.type', '=', relationship::TYPE_IDENTIFIER)
            ->where(function (builder $builder) use ($hook) {
                $builder
                    ->where(function (builder $builder) use ($hook) {
                        $builder->where('ja.managerjaid', $hook->viewing_user_id);
                    })
                    ->or_where(function (builder $builder) use ($hook) {
                        $builder->where('ja.tempmanagerjaid', $hook->viewing_user_id)
                            ->where('ja.tempmanagerexpirydate', '>', time());
                    });
            });
        if ($approvers->exists()) {
            return true;
        }

        return false;
    }
}