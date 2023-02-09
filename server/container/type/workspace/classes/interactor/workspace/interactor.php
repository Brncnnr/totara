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
namespace container_workspace\interactor\workspace;

use container_workspace\entity\workspace_member_request;
use container_workspace\loader\member\loader;
use container_workspace\member\member;
use container_workspace\notification\workspace_notification;
use container_workspace\tracker\tracker;
use container_workspace\workspace;
use context_user;
use core\orm\query\exceptions\record_not_found_exception;
use core_container\factory;
use totara_engage\engage_core;

/**
 * A helper class that is constructed with workspace's id and the user's id, which helps to fetch
 * all the available actions that a user can interact with a workspace.
 *
 * Note that this will also include ability to fetch the state of this user against the workspace.
 */
final class interactor {
    /**
     * The workspace's id that we are going to check against.
     *
     * @var workspace
     */
    private $workspace;

    /**
     * The user's id that act as an actor interact with the workspace.
     *
     * @var int
     */
    private $user_id;

    /**
     * Enum representing can not leave workspace because user is not a member.
     */
    public const NOT_A_MEMBER = 'NOT_A_MEMBER';

    /**
     * Enum representing can not leave workspace because user is the owner.
     */
    public const IS_OWNER = 'IS_OWNER';

    /**
     * Enum representing can not leave workspace because user belongs to an audience added to the workspace.
     */
    public const AUDIENCE_MEMBERSHIP = 'AUDIENCE_MEMBERSHIP';

    /**
     * actor constructor.
     *
     * @param workspace $workspace
     * @param int|null $user_id     If null is set for this field, then user in session will be used.
     */
    public function __construct(workspace $workspace, ?int $user_id = null) {
        global $USER;

        if (null === $user_id || 0 === $user_id) {
            $user_id = $USER->id;
        }

        $this->workspace = $workspace;
        $this->user_id = $user_id;
    }

    /**
     * @param int $workspace_id
     * @param int|null $user_id
     *
     * @return interactor
     */
    public static function from_workspace_id(int $workspace_id, ?int $user_id = null): interactor {
        /** @var workspace $workspace */
        $workspace = factory::from_id($workspace_id);

        if (!$workspace->is_typeof(workspace::get_type())) {
            throw new \coding_exception(
                "Cannot instantiate an object of interactor from a container that is not a workspace: {$workspace_id}"
            );
        }

        return new static($workspace, $user_id);
    }

    /**
     * User has the manage capability.
     *
     * @return bool
     */
    public function can_manage(): bool {
        if ($this->workspace->is_to_be_deleted()) {
            // No one can manage this workspace anymore.
            return false;
        }

        if ($this->can_administrate()) {
            // If you can administrate, you can manage.
            return true;
        }

        $context = $this->workspace->get_context();
        return has_capability('container/workspace:manage', $context, $this->user_id);
    }

    /**
     * Primary owner is the owner held against the workspace
     *
     * @return bool
     * @deprecated since Totara 16. Use {@see is_owner} instead.
     */
    public function is_primary_owner(): bool {
        debugging('\container_workspace\interactor\workspace\interactor::is_primary_owner() has been deprecated, please use \container_workspace\interactor\workspace\interactor::is_owner() instead', DEBUG_DEVELOPER);
        return $this->workspace->get_user_id() == $this->user_id;
    }

    /**
     * You are an owner if you have the owner role within the context of this workspace.
     *
     * @return bool
     */
    public function is_owner(): bool {
        $context = $this->workspace->get_context();
        $roles = get_user_roles($context, $this->user_id);
        $owner_role = member::get_role_for_owners();

        // Check to see if they've already got the role
        foreach ($roles as $role) {
            if ($role->roleid === $owner_role->id) {
                return true;
            }
        }
        return false;
    }

    /**
     * Like manage, but across the whole category/site. Super admin privilege.
     * Note that this does not check the workplace status intentionally.
     *
     * @return bool
     */
    public function can_administrate(): bool {
        $context = \context_coursecat::instance($this->workspace->category);
        return has_capability('container/workspace:administrate', $context, $this->user_id);
    }

    /**
     * @return bool
     */
    public function is_joined(): bool {
        // Owner is considered to automatically be joined
        if ($this->is_owner()) {
            // Save us another cycle of fetching.
            return true;
        }

        $workspace_id = $this->workspace->get_id();
        $member = loader::get_for_user($this->user_id, $workspace_id);
        if (null !== $member && $member->is_active()) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function can_update(): bool {
        if ($this->workspace->is_to_be_deleted()) {
            // Workspace has been deleted - hence no one is able to update the workspace.
            return false;
        }

        $context = $this->workspace->get_context();
        return has_capability('container/workspace:update', $context, $this->user_id);
    }

    /**
     * A helper check function to check whether user actor is able to delete the workspace or not.
     * Note that this function will not check for the deleted status of workspace, as we don't want to add more
     * or modified any contents to/of the workspace, but only removing from it.
     *
     * @return bool
     */
    public function can_delete(): bool {
        $context = $this->workspace->get_context();
        return has_capability('container/workspace:delete', $context, $this->user_id);
    }

    /**
     * @return bool
     */
    public function can_add_members(): bool {
        if (!$this->can_view_workspace()) {
            return false;
        }

        $context = $this->workspace->get_context();
        return has_capability('container/workspace:addmember', $context, $this->user_id);
    }

    /**
     * @return bool
     */
    public function can_invite(): bool {
        if (!$this->can_view_workspace()) {
            return false;
        }

        $context = $this->workspace->get_context();
        return has_capability('container/workspace:invite', $context, $this->user_id);
    }

    /**
     * A capability and logic check whether the actor is able to join the workspace or not.
     * It depends on different workspace type that perform different check.
     *
     * @return bool
     */
    public function can_join(): bool {
        if ($this->is_joined()) {
            // Nope, members cannot join again
            return false;
        }

        if (!$this->can_view_workspace()) {
            // Cannot view the workspace means that user should not be able to join the workspace.
            return false;
        }

        $context = $this->workspace->get_context();

        if ($this->workspace->is_public()) {
            // Anyone with the capability can join the workspace
            return has_capability('container/workspace:joinpublic', $context, $this->user_id);
        }

        // Admin can join anyworkspace.
        return $this->can_administrate();
    }

    /**
     * Ability to check whether the current user's actor had been able to request to join the workspace or not.
     *
     * @return bool
     */
    public function can_request_to_join(): bool {
        if ($this->is_joined()) {
            return false;
        }

        if ($this->workspace->is_public()) {
            return false;
        }

        if (!$this->can_view_workspace()) {
            // Unable to see the workspace meaning that user should not be able to request.
            return false;
        }

        // Hidden workspaces cannot be seen to get a request
        if ($this->workspace->is_hidden()) {
            return false;
        }

        // Anyone with the capability can join the workspace
        $context = $this->workspace->get_context();
        return has_capability('container/workspace:joinprivate', $context, $this->user_id);
    }

    /**
     * @return workspace
     */
    public function get_workspace(): workspace {
        return $this->workspace;
    }

    /**
     * Returning the user's id who is interacting with the workspace so far.
     *
     * @return int
     */
    public function get_user_id(): int {
        return $this->user_id;
    }

    /**
     * @return bool
     */
    public function can_accept_member_request(): bool {
        if ($this->workspace->is_public()) {
            return false;
        }

        // Anyone who can add members can accept them
        $context = $this->workspace->get_context();
        return has_capability('container/workspace:addmember', $context, $this->user_id);
    }

    /**
     * @return bool
     */
    public function can_decline_member_request(): bool {
        if ($this->workspace->is_public()) {
            return false;
        } else if ($this->workspace->is_to_be_deleted()) {
            // Workspace is deleted - hence no-one can make any changes to the workspace.
            return false;
        }

        if (is_siteadmin($this->user_id)) {
            return true;
        }

        // Anyone who can remove members can decline them
        return $this->can_remove_members();
    }

    /**
     * @return bool
     */
    public function can_remove_members(): bool {
        $context = $this->workspace->get_context();
        return has_capability('container/workspace:removemember', $context, $this->user_id);
    }

    /**
     * @return bool
     */
    public function can_view_workspace(): bool {
        if ($this->workspace->is_to_be_deleted()) {
            // Workspace has been flagged to be deleted - hence all the user should not be able
            // to see this workspace at all.
            return false;
        }

        if ($this->can_administrate()) {
            return true;
        }

        $context = context_user::instance($this->user_id);
        if (!has_capability('container/workspace:workspacesview', $context, $this->user_id)) {
            return false;
        }

        // Check for tenancy first before checking whether you had been joined
        // the workspace or not.
        if (!$this->can_view_workspace_with_tenant_check()) {
            return false;
        }

        if ($this->is_joined()) {
            return true;
        }

        if ($this->workspace->is_public() || $this->workspace->is_private()) {
            return true;
        }

        if ($this->workspace->is_hidden()) {
            $context = $this->workspace->get_context();
            return has_capability('moodle/course:viewhiddencourses', $context, $this->user_id);
        }

        throw new \coding_exception("Unsupported visibility type");
    }

    /**
     * A function to check whether the workspace is available for user when multi tenancy is on.
     *
     * @return bool
     */
    public function can_view_workspace_with_tenant_check(): bool {
        global $CFG;
        if ($this->workspace->is_to_be_deleted()) {
            // Workspace has been flagged to be deleted - hence all the user should not be able
            // to see this workspace at all.
            return false;
        }

        if (!$CFG->tenantsenabled || $this->can_administrate()) {
            // Multi tenancy is not enabled - so we skip the rest.
            return true;
        }

        $context = $this->workspace->get_context();

        // Context check first of all, handles any mismatched tenancy checks.
        // You may be a member, but it's possible shifting tenancies has blocked your access
        if ($context->is_user_access_prevented($this->user_id)) {
            return false;
        }

        // Workspace extended logic rule. The rule is simple, we prevent any access from
        // system user to tenant workspace. But we do not prevent any access
        // from tenant user to system workspace.
        if (!empty($context->tenantid)) {
            // Target context is within tenant. Hence check for whether the user actor is
            // able to access to this tenant, despite of isolation mode is on/off.
            return engage_core::is_user_part_of_tenant($context->tenantid, $this->user_id);
        }

        // Yes. You are allow to see the workspace.
        return true;
    }

    /**
     * This is only a function to check the state of user against the workspace.
     * Note that we do not check for workspace's deleted here, because
     * workspace's deleted will not affected the state check at all.
     *
     * @return bool
     */
    public function has_requested_to_join(): bool {
        $repository = workspace_member_request::repository();

        $workspace_id = $this->workspace->get_id();
        $entity = $repository->get_current_pending_request(
            $workspace_id,
            $this->user_id
        );

        return null !== $entity;
    }

    /**
     * @return bool
     */
    public function can_view_discussions(): bool {
        if (!$this->can_view_workspace()) {
            // Checking whether the user actor is able to view the workspace or not.
            return false;
        }

        // Note that is_joined && is_public had already been used in can view workspace.
        // However it does not mean that when u can view workspace u can view the discussion,
        // it is just another layer check to say that if u cannot see a workspace, you can not go here.
        // If we remove is_joined and is_public in this check then user can be a normal member without
        // any capability to manage the workspace then user cannot see the discussion.
        // Hence these checks have to stay here.
        if ($this->can_manage() || $this->is_joined() || $this->workspace->is_public()) {
            return true;
        }

        // Otherwise you'll need the discussion manage capability (rare)
        $context = $this->workspace->get_context();
        return has_capability('container/workspace:discussionmanage', $context, $this->user_id);
    }

    /**
     * @return bool
     */
    public function can_create_discussions(): bool {
        if ($this->workspace->is_to_be_deleted()) {
            // Workspace has already been deleted - no user should be able to create any new discussion.
            return false;
        }

        // You can create if you can see them + have the create capability.
        if (!$this->can_view_discussions()) {
            return false;
        }

        $context = $this->workspace->get_context();
        return has_capability('container/workspace:discussioncreate', $context, $this->user_id);
    }

    /**
     * @return bool
     */
    public function can_view_library(): bool {
        if (!$this->can_view_workspace()) {
            // Checking whether the user actor is able to view the workspace or not.
            return false;
        }

        if ($this->can_manage()) {
            return true;
        }

        $context = \context_user::instance($this->user_id);
        $view_library = has_capability('totara/engage:viewlibrary', $context, $this->user_id);

        return $view_library && ($this->workspace->is_public() || $this->is_joined());
    }

    /**
     * @return bool
     */
    public function can_view_members(): bool {
        if (!$this->can_view_workspace()) {
            // Safety checks for multi-tenancy, plus whether the user actor is
            // able to view the workspace or not.
            return false;
        }

        if ($this->can_manage()) {
            return true;
        }

        return $this->workspace->is_public() || $this->is_joined();
    }

    /**
     * @return bool
     */
    public function can_share_resources(): bool {
        if (!$this->can_view_workspace()) {
            // Either workspace has been deleted, or user has been moved to different tenant.
            // Hence this user cannot see the workspace, and it means that the user cannot
            // share the resources to library.
            return false;
        }

        $context = $this->workspace->get_context();
        // When user have none of them, return false.
        if (!has_any_capability(['engage/article:share', 'engage/survey:share', 'totara/playlist:share'], $context, $this->user_id)) {
            return false;
        }

        return has_capability('container/workspace:libraryadd', $context, $this->user_id);
    }

    /**
     * A helper check, to check whether user actor is able to unshare the resources.
     * Note that this was not checking whether the workspace has been deleted or not, this
     * is because we do not want to add anything more to the workspace, but only remove from it.
     *
     * This function will be less likely called somewhere else from the user interfaces, as the
     * workspace record will be hidden when it is deleted.
     *
     * @return bool
     */
    public function can_unshare_resources(): bool {
        $context = $this->workspace->get_context();
        return has_capability('container/workspace:libraryremove', $context, $this->user_id);
    }

    /**
     * @return bool
     */
    public function has_turned_off_notification(): bool {
        $workspace_id = $this->workspace->get_id();
        return workspace_notification::is_off($workspace_id, $this->user_id);
    }

    /**
     * A function to check whether the workspace had been updated ever since the last time user visited it.
     * The parameter $last_check_time is in place to help not refetching from the table "ttr_user_lastaccess".
     * If it is not provided - then we are  going to fetch from table "ttr_user_lastaccess"
     *
     * @param int|null $last_check_time
     * @return bool
     */
    public function has_seen(?int $last_check_time = null): bool {
        if (null === $last_check_time) {
            $workspace_id = $this->workspace->get_id();

            $tracker = new tracker($this->user_id);
            $last_check_time = $tracker->get_last_time_visit_workspace($workspace_id);

            if (null === $last_check_time) {
                // User had not access to this workspace yet.
                return false;
            }
        }

        // If the last check time is not greater than the workspace time stamp then it means that user
        // had not yet visit this very workspace.
        $workspace_timestamp = $this->workspace->get_timestamp();
        return $last_check_time >= $workspace_timestamp;
    }

    /**
     * @return bool
     */
    public function can_leave_workspace(): bool {
        if ($this->is_owner()) {
            // As long as this user is not an owner of the specific workspace
            // then user is able to leave the workspace.
            return false;
        }

        return $this->is_joined();
    }

    /**
     * Reason a user can not leave a workspace.
     *
     * @return string|null
     */
    public function cannot_leave_reason(): ?string {
        try {
            $member = member::from_user($this->user_id, $this->workspace->id);
        } catch (record_not_found_exception $exception) {
            return self::NOT_A_MEMBER;
        }

        switch (true) {
            case $this->is_owner():
                return self::IS_OWNER;
            case count($member->get_audiences()) > 0:
                return self::AUDIENCE_MEMBERSHIP;
            default:
                // Regular members can leave
                return null;
        }
    }

    /**
     * Returns true if the current users has the capabilities
     * to add audiences to this workspace or any above it
     */
    public function can_add_audiences(): bool {
        return ($this->can_manage() || $this->is_owner()) && has_capability('moodle/cohort:view', $this->workspace->get_context());
    }

    /**
     * Referesh workspace cache.
     *
     * @return void
     */
    public function reload_workspace(): void {
        $this->workspace->reload();
    }

}