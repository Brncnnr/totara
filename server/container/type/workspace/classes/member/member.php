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
namespace container_workspace\member;

use cache;
use container_workspace\exception\enrol_exception;
use container_workspace\interactor\workspace\interactor;
use container_workspace\totara_notification\resolver\user_added;
use container_workspace\totara_notification\workspace_muter;
use container_workspace\tracker\tracker;
use container_workspace\workspace;
use core\entity\cohort;
use core\entity\enrol;
use core\entity\user_enrolment;
use core\orm\query\builder;
use core_container\factory;
use totara_core\visibility_controller;
use stdClass;
use core_user;
use coding_exception;

/**
 * A model class for workspace's member.
 */
final class member {
    /**
     * Need to cache workspace_id that this member is belonging to.
     *
     * @var int|null
     */
    private $workspace_id;

    /**
     * @var user_enrolment
     */
    private $user_enrolment;

    /**
     * @var stdClass|null
     */
    private $user_record;

    /**
     * Workspace audiences associated with member.
     *
     * @var cohort[]
     */
    private $associated_audiences;

    /**
     * member constructor.
     * @param user_enrolment $user_enrolment
     */
    private function __construct(user_enrolment $user_enrolment) {
        if (!$user_enrolment->exists()) {
            throw new \coding_exception("Cannot construct a class of member that is not already exist");
        }

        $this->user_enrolment = $user_enrolment;
        $this->workspace_id = null;
        $this->user_record = null;
    }

    /**
     * Gets the member by it's id
     *
     * @param int $member_id
     * @param int $workspace_id
     * @return member
     */
    public static function from_id(int $member_id, int $workspace_id): member {
        /** @var user_enrolment $user_enrolment */
        $user_enrolment = user_enrolment::repository()->as('ue')
            ->join([enrol::TABLE, 'e'], 'ue.enrolid', 'e.id')
            ->where('ue.id', $member_id)
            ->where('e.courseid', $workspace_id)
            ->one(true);

        $member = new static($user_enrolment);
        $member->workspace_id = $workspace_id;

        return $member;
    }

    /**
     * @param int $user_id
     * @param int $workspace_id
     *
     * @return member
     */
    public static function from_user(int $user_id, int $workspace_id): member {
        /**
         * Get one of the user's enrolments
         * @var user_enrolment $user_enrolment_entity
         */
        $user_enrolment_entity = user_enrolment::repository()->as('ue')
            ->join([enrol::TABLE, 'e'], 'ue.enrolid', 'e.id')
            ->where('ue.userid', $user_id)
            ->where('e.courseid', $workspace_id)
            ->order_by_raw('ue.status, e.status, ue.id')
            ->first_or_fail();

        $member = new static($user_enrolment_entity);
        $member->workspace_id = $workspace_id;

        return $member;
    }

    /**
     * @param \stdClass $record
     *
     * @return member
     */
    public static function from_record(\stdClass $record): member {
        $workspace_id = null;

        if (property_exists($record, 'workspace_id')) {
            $workspace_id = $record->workspace_id;
            unset($record->workspace_id);
        }

        $entity = new user_enrolment($record);

        $member = new static($entity);
        $member->workspace_id = $workspace_id;

        return $member;
    }

    /**
     * If the actor is match with the owner of the workspace, then the actor will be enrolled as workspaceowner
     * role. Otherwise student role will be used for any other user.
     *
     * @param workspace $workspace
     * @param int|null $actor_id If this is null, then $user in session will be used.
     *
     * @return member
     */
    public static function join_workspace(workspace $workspace, ?int $actor_id = null): member {
        global $USER, $CFG;

        if (empty($actor_id)) {
            $actor_id = $USER->id;
        }

        $owner_id = $workspace->get_user_id();
        $is_owner = ($actor_id == $owner_id);

        $interactor = new interactor($workspace, $actor_id);

        if (!$workspace->is_public() && (!$is_owner && !$interactor->can_join())) {
            throw new \coding_exception("Cannot join the non-public workspace");
        }

        if ($CFG->tenantsenabled) {
            if (!$interactor->can_view_workspace_with_tenant_check()) {
                throw new \coding_exception("Cannot join the workspace that is not in the same tenant");
            }
        }

        // Join as a learner/student, unless they're the owner
        $role = $is_owner ? self::get_role_for_owners() : self::get_role_for_members();

        $manager = $workspace->get_enrolment_manager();
        $manager->self_enrol_user($actor_id, $role->id);

        $workspace_id = $workspace->get_id();
        return static::from_user($actor_id, $workspace_id);
    }

    /**
     * Adds given members in bulk and returns the ids of the members created.
     *
     * This is optimised for bulk users so that the visibility recalculation is done only once and not per member.
     *
     * @param workspace $workspace
     * @param int[] $user_ids
     * @param bool $trigger_notification
     * @param int|null $actor_id
     * @return member[] returns the member ids just added
     * @deprecated since Totara 16.0 Bulk add audience members has been deprecated.
     */
    public static function added_to_workspace_in_bulk(workspace $workspace, array $user_ids,
                                                      bool $trigger_notification = true, ?int $actor_id = null): array {
        debugging("member::added_to_workspace_in_bulk has been deprecated.", DEBUG_DEVELOPER);
        global $USER;
        if (empty($actor_id)) {
            $actor_id = $USER->id;
        }

        if (empty($user_ids)) {
            throw new coding_exception('No user ids given');
        }

        if (!$trigger_notification) {
            foreach ($user_ids as $user_id) {
                workspace_muter::mute(user_added::class, $workspace->get_id(), $user_id);
            }
        }

        $members = builder::get_db()->transaction(
            function () use ($workspace, $user_ids, $actor_id): array {
                $role = self::get_role_for_members();

                $owner_id = $workspace->get_user_id();

                foreach ($user_ids as $user_id) {
                    // The owner cannot be added by this, he should always be a member already
                    if ($user_id == $owner_id) {
                        throw enrol_exception::on_manual_enrol();
                    }
                }

                return self::do_add_to_workspace_bulk($workspace, $user_ids, $role->id, $actor_id);
            }
        );

        return $members;
    }

    /**
     * Target user is being added to the workspace by the actor.
     *
     * @param workspace $workspace
     * @param int       $user_id
     * @param bool      $trigger_notification
     * @param int|null  $actor_id
     *
     * @return member
     */
    public static function added_to_workspace(workspace $workspace, int $user_id,
                                              bool $trigger_notification = true, ?int $actor_id = null): member {
        global $USER;
        if (empty($actor_id)) {
            $actor_id = $USER->id;
        }

        $owner_id = $workspace->get_user_id();
        if ($user_id == $owner_id) {
            throw enrol_exception::on_manual_enrol();
        }

        $role = self::get_role_for_members();

        if (!$trigger_notification) {
            workspace_muter::mute(user_added::class, $workspace->get_id(), $user_id);
        }

        return self::do_add_to_workspace($workspace, $user_id, $role->id, $actor_id);
    }

    /**
     * Get role for member being added.
     *
     * @return stdClass
     */
    public static function get_role_for_members(): stdClass {
        $cache = cache::make('container_workspace', 'workspace');
        $role = $cache->get('member_role');
        if (!$role) {
            $roles = get_archetype_roles('student');
            if (empty($roles)) {
                throw new \coding_exception("No roles for archetype 'student'");
            }
            $role = reset($roles);
            $cache->set('member_role', $role);
        }

        return $role;
    }

    /**
     * Get role for workspace owners.
     *
     * @return stdClass
     */
    public static function get_role_for_owners(): stdClass {
        $cache = cache::make('container_workspace', 'workspace');
        $role = $cache->get('owner_role');
        if (!$role) {
            $roles = get_archetype_roles('workspaceowner');
            if (empty($roles)) {
                throw new \coding_exception("No roles for archetype 'workspaceowner'");
            }
            $role = reset($roles);
            $cache->set('owner_role', $role);
        }

        return $role;
    }

    /**
     * Add users in bulk to the workspace
     * @param workspace $workspace
     * @param int[] $user_ids
     * @param int $role_id
     * @param int $actor_id
     * @return member[]
     */
    private static function do_add_to_workspace_bulk(
        workspace $workspace,
        array $user_ids,
        int $role_id,
        int $actor_id
    ): array {
        global $CFG;
        // Make sure everything is valid before enrolling users
        foreach ($user_ids as $user_id) {
            if ($CFG->tenantsenabled) {
                // Only checking this if multi-tenancy is enabled.
                $target_workspace_interactor = new interactor($workspace, $user_id);
                if (!$target_workspace_interactor->can_view_workspace_with_tenant_check()) {
                    // Check if the newly going-to-be-added user is able to see the workspace or not.
                    throw new \coding_exception("Target user is not able to see the workspace");
                }
            }
        }

        $manager = $workspace->get_enrolment_manager();
        $manager->manual_enrol_user_bulk($user_ids, $role_id, $actor_id);

        $members = [];
        foreach ($user_ids as $user_id) {
            $members[] = self::from_user($user_id, $workspace->id);
        }

        return $members;
    }

    /**
     * @param workspace $workspace
     * @param int $user_id
     * @param int $role_id
     * @param int $actor_id
     *
     * @return member
     */
    private static function do_add_to_workspace(workspace $workspace, int $user_id,
                                                    int $role_id, int $actor_id): member {
        global $CFG;
        if ($CFG->tenantsenabled) {
            // Only checking this if multi-tenancy is enabled.
            $target_workspace_interactor = new interactor($workspace, $user_id);
            if (!$target_workspace_interactor->can_view_workspace_with_tenant_check()) {
                // Check if the newly going-to-be-added user is able to see the workspace or not.
                throw new \coding_exception("Target user is not able to see the workspace");
            }
        }

        $manager = $workspace->get_enrolment_manager();
        $manager->manual_enrol_user($user_id, $role_id, $actor_id);

        $workspace_id = $workspace->get_id();
        return static::from_user($user_id, $workspace_id);
    }

    /**
     * @return int
     */
    public function get_workspace_id(): int {
        global $DB;

        if (null === $this->workspace_id || 0 === $this->workspace_id) {
            $enrol_id = $this->user_enrolment->enrolid;
            $this->workspace_id = $DB->get_field('enrol', 'courseid', ['id' => $enrol_id], MUST_EXIST);
        }

        return $this->workspace_id;
    }

    /**
     * @return workspace
     */
    public function get_workspace(): workspace {
        $workspace_id = $this->get_workspace_id();

        /** @var workspace $workspace */
        $workspace = factory::from_id($workspace_id);
        return $workspace;
    }

    /**
     * @return bool
     */
    public function is_suspended(): bool {
        return $this->user_enrolment->is_suspended();
    }

    /**
     * @return bool
     */
    public function is_active(): bool {
        return $this->user_enrolment->is_active();
    }

    /**
     * @param int|null $actor_id
     * @return bool
     */
    public function leave(?int $actor_id = null): bool {
        global $USER;

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER->id;
        }

        $user_id = $this->user_enrolment->userid;
        if ($user_id != $actor_id) {
            throw new \coding_exception("Actor trying to leave and the user's enrolment is not sync");
        }

        if ($this->is_suspended()) {
            // Enrolment is already suspended.
            return true;
        }

        $workspace_id = $this->get_workspace_id();

        /** @var workspace $workspace */
        $workspace = factory::from_id($workspace_id);
        $manager = $workspace->get_enrolment_manager();

        $manager->suspend_enrol($this->user_enrolment);
        $this->user_enrolment->refresh();

        // Remove the tracker for user's id.
        $tracker = new tracker($user_id);
        $tracker->clear($workspace_id);

        return $this->is_suspended();
    }

    /**
     * Remove a user from a workspace.
     * Note: There are no capability checks performed here.
     *
     * @param int|null $actor_id
     * @return bool
     */
    public function removed_from_workspace(?int $actor_id = null): bool {
        global $USER;

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER->id;
        }

        $user_id = $this->user_enrolment->userid;
        if ($actor_id == $user_id) {
            // As the same actor with this enrolment should not be able to remove himself.
            throw new \coding_exception(
                "The actor's id and the user enrolment is the same. Should use leave method instead"
            );
        }

        if ($this->is_suspended()) {
            return true;
        }

        $workspace_id = $this->get_workspace_id();

        /** @var workspace $workspace */
        $workspace = factory::from_id($workspace_id);
        $interactor = interactor::from_workspace_id($workspace_id, $actor_id);
        // To remove another user you must either manage the workspace or can remove members
        if (!$interactor->can_manage() && !$interactor->can_remove_members()) {
            throw new \coding_exception("No capability to remove the member");
        }

        $manager = $workspace->get_enrolment_manager();
        $manager->suspend_enrol($this->user_enrolment);

        $this->user_enrolment->refresh();
        return $this->is_suspended();
    }

    /**
     * The function only changes the role assignment to user, from owner role to a learner role.
     * It does not remove the user's enrolment.
     *
     * @param int|null $actor_id
     * @return void
     */
    public function demote_from_owner(?int $actor_id = null): void {
        global $USER;

        if (empty($actor_id)) {
            $actor_id = $USER->id;
        }

        $workspace_id = $this->get_workspace_id();
        $actor_workspace_interactor = interactor::from_workspace_id($workspace_id, $actor_id);

        if (!$actor_workspace_interactor->can_manage()) {
            throw new \coding_exception("No capability to demote an owner");
        }

        $context = \context_course::instance($workspace_id);

        // Workspace's owner role.
        $owner_roles = get_archetype_roles('workspaceowner');
        if (empty($owner_roles)) {
            throw new \coding_exception("There are no workspace's owner roles");
        }

        $current_roles = get_user_roles($context, $this->user_enrolment->userid);
        foreach ($current_roles as $current_role) {
            if (!isset($owner_roles[$current_role->roleid])) {
                continue;
            }

            // Unassign the user's role for workspace owner.
            role_unassign(
                $current_role->roleid,
                $this->user_enrolment->userid,
                $context->id,
                'container_workspace'
            );
        }

        // Then assign the workspace member role.
        $learner_role = self::get_role_for_members();
        role_assign(
            $learner_role->id,
            $this->user_enrolment->userid,
            $context->id,
            'container_workspace'
        );
    }

    /**
     * @param int|null $actor_id
     * @return void
     */
    public function promote_to_owner(?int $actor_id = null): void {
        global $USER;
        if (empty($actor_id)) {
            $actor_id = $USER->id;
        }

        $workspace_id = $this->get_workspace_id();
        $actor_workspace_interactor = interactor::from_workspace_id($workspace_id, $actor_id);

        if (!$actor_workspace_interactor->can_manage()) {
            throw new \coding_exception("No capability to promote a member");
        }

        $context = \context_course::instance($workspace_id);

        // Workspace learner role - we need to remove them first.
        $learner_roles = get_archetype_roles('student');
        if (empty($learner_roles)) {
            throw new \coding_exception("There are no learner roles found");
        }

        $current_roles = get_user_roles($context, $this->user_enrolment->userid);
        foreach ($current_roles as $current_role) {
            if (isset($learner_roles[$current_role->roleid])) {
                continue;
            }

            // Unassign the user's role for workspace member.
            role_unassign(
                $current_role->roleid,
                $this->user_enrolment->userid,
                $context->id,
                'container_workspace'
            );
        }

        $owner_role = self::get_role_for_owners();
        // Check if the user already had this role.
        $has_role = user_has_role_assignment(
            $this->user_enrolment->userid,
            $owner_role->id,
            $context->id
        );

        if (!$has_role) {
            // Only assign owner role if user does not have it yet.
            role_assign(
                $owner_role->id,
                $this->user_enrolment->userid,
                $context->id,
                'container_workspace'
            );
        }
    }

    /**
     * @return int
     */
    public function get_time_modified(): int {
        return $this->user_enrolment->timemodified;
    }

    /**
     * @return int
     */
    public function get_time_created(): int {
        return $this->user_enrolment->timecreated;
    }

    /**
     * @return stdClass
     */
    public function get_user_record(): stdClass {
        if (null === $this->user_record) {
            $this->user_record = core_user::get_user($this->user_enrolment->userid, '*', MUST_EXIST);
        }

        return $this->user_record;
    }

    public function set_user_record(stdClass $user_record): void {
        if (!property_exists($user_record, 'id')) {
            throw new coding_exception("The user's record does not have 'id' property");
        }

        if ($user_record->id != $this->user_enrolment->userid) {
            throw new coding_exception("User record is different from the user's enrolment");
        }

        $this->user_record = $user_record;
    }

    /**
     * Get audiences
     *
     * @return array
     */
    public function get_audiences(): array {
        if (is_null($this->associated_audiences)) {
            $this->associated_audiences = cohort::repository()->as('c')
                ->join([enrol::TABLE, 'e'], 'c.id', 'e.customint1')
                ->join([user_enrolment::TABLE, 'ue'], 'ue.enrolid', 'e.id')
                ->where('e.enrol', 'cohort')
                ->where('e.status', ENROL_INSTANCE_ENABLED)
                ->where('e.courseid', $this->get_workspace_id())
                ->where('ue.status', ENROL_USER_ACTIVE)
                ->where('ue.userid', $this->get_user_id())
                ->get()
                ->all();
        }

        return $this->associated_audiences;
    }

    /**
     * Set audiences
     *
     * @param array $audiences
     * @return void
     */
    public function set_audiences(array $audiences): void {
        foreach ($audiences as $audience) {
            if (!$audience instanceof cohort) {
                throw new coding_exception("Invalid cohort");
            }
        }

        $this->associated_audiences = $audiences;
    }

    /**
     * Get whether this member was added via a group (e.g. audiences).
     *
     * @return bool
     */
    public function get_added_via_group(): bool {
        return !empty($this->get_audiences());
    }

    /**
     * @return int
     */
    public function get_user_id(): int {
        return $this->user_enrolment->userid;
    }

    /**
     * @param int|null $actor_id
     * @return void
     */
    public function delete(?int $actor_id = null): void {
        global $USER;

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER->id;
        }

        $workspace_id = $this->get_workspace_id();


        if ($actor_id != $this->user_enrolment->userid) {
            // Not the same user with the enrolment. Time to check whether it is an owner or not

            /** @var workspace $workspace */
            $workspace = factory::from_id($workspace_id);
            $owner_id = $workspace->get_user_id();

            if ($owner_id != $actor_id && !is_siteadmin($actor_id)) {
                throw new \coding_exception("User cannot delete the user enrolment of someone else");
            }
        }

        $this->user_enrolment->delete();
    }

    /**
     * @return int
     */
    public function get_status(): int {
        return $this->user_enrolment->status;
    }

    /**
     * @return int
     * @deprecated Since Totara 13.2
     */
    public function get_member_id(): int {
        debugging(
            "Function 'get_member_id' had been deprecated, please use 'get_id' instead",
            DEBUG_DEVELOPER
        );

        return $this->get_id();
    }

    /**
     * Returning the member id
     * @return int
     */
    public function get_id(): int {
        return $this->user_enrolment->id;
    }

    /**
     * Returning the member user id.
     * @return int
     */
    public function get_member_user_id(): int {
        return $this->user_enrolment->userid;
    }

    /**
     * @return void
     */
    public function reload(): void {
        $this->user_enrolment->refresh();
    }
}