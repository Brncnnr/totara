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
namespace container_workspace\interactor\member;

use container_workspace\workspace;
use core_container\factory;
use container_workspace\member\member;
use container_workspace\interactor\workspace\interactor as workspace_interactor;

/**
 * A helper class that is constructed with {user_enrolment}'s id and the user's id, which helps to fetch
 * all the available actions that a user can interact with a membership of other.
 */
final class interactor {
    /**
     * @var member
     */
    private $member;

    /**
     * The actor's id.
     * @var int
     */
    private $user_id;

    /**
     * interactor constructor.
     * @param member    $member
     * @param int|null  $user_id
     */
    public function __construct(member $member, ?int $user_id = null) {
        global $USER;

        if (null === $user_id || 0 === $user_id) {
            $user_id = $USER->id;
        }

        $this->member = $member;
        $this->user_id = $user_id;
    }

    /**
     * Remove will suspend the enrolment of the member.
     * This differs from the delete behaviour which actually removes records in the database.
     *
     * @return bool
     */
    public function can_remove(): bool {
        $workspace_id = $this->member->get_workspace_id();

        /** @var workspace $workspace */
        $workspace = factory::from_id($workspace_id);
        $owner_id = $workspace->get_user_id();

        // Owners can not remove themselves from the workspace.
        if ($owner_id == $this->user_id) {
            $member_user_id = $this->member->get_user_id();
            if ($this->user_id == $member_user_id) {
                return false;
            }
        }

        // Users cannot be directly removed from the workspace if they were added via a group
        if ($this->member->get_added_via_group()) {
            return false;
        }

        $context = $workspace->get_context();
        return has_capability('container/workspace:removemember', $context, $this->user_id);
    }

    /**
     * To delete a member record, an actor must have the ability to delete a workspace.
     *
     * @return bool
     */
    public function can_delete(): bool {
        $member_user_id = $this->member->get_user_id();
        if ($member_user_id == $this->user_id) {
            // Same user origin - hence we allow them to delete their own record.
            return true;
        }

        $workspace = $this->member->get_workspace();
        $workspace_interactor = new workspace_interactor($workspace, $this->user_id);

        return $workspace_interactor->can_delete();
    }

    /**
     * @return int
     */
    public function get_user_id(): int {
        return $this->user_id;
    }

    /**
     * @return member
     */
    public function get_member(): member {
        return $this->member;
    }
}