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

use container_workspace\exception\enrol_exception;
use container_workspace\loader\member\loader;
use container_workspace\loader\member\audience_loader;
use container_workspace\query\member\query;
use container_workspace\workspace;
use container_workspace\interactor\workspace\interactor as workspace_interactor;
use container_workspace\interactor\member\interactor as member_interactor;
use core\collection;
use core\orm\query\builder;
use core\pagination\offset_cursor;

/**
 * This class is to mix up between the user interacting with the model and the database layer.
 * Where we can safely run the capability check and execute the database modification API.
 */
class member_handler {
    /**
     * The user's id who is acting.
     * @var int
     */
    private $actor_id;

    /**
     * Note that we do not fallback to any GLOBAL $USER here because we want to enforce developer
     * to pass down the correct user's id.
     *
     * handler constructor.
     * @param int $actor_id
     */
    public function __construct(int $actor_id) {
        $this->actor_id = $actor_id;
    }

    /**
     * Bulk delete all member records of the workspace.
     * This function should normally be used when the workspace is about to be deleted.
     * Note that the function is not responsible for checking whether the workspace is deleted or not.
     *
     * @param workspace $workspace
     * @param int       $batch_limit    How many records do we want to delete per one batch. As the deletion is
     *                                  being done with batch deleting.
     *
     * @return void
     */
    public function delete_members_of_workspace(workspace $workspace, int $batch_limit = 100): void {
        $workspace_interactor = new workspace_interactor($workspace, $this->actor_id);

        // If the user does not have ability to delete the workspace then user should not have ability
        // bulk delete the member records.
        // The workspace delete mutation resolver will have already checked that a user had the required capability prior
        // to queuing the workspace delete task, and set workspace.is_to_be_deleted to true.
        if (!$workspace->is_to_be_deleted()) {
            throw new \coding_exception("The workspace has not been marked for deletion, please set to_be_deleted to true.");
        }

        $transaction = builder::get_db()->start_delegated_transaction();

        // We are going to use this cursor thru out the whole process.
        // It is because the loader is moving forward, hence for every single batch deleted,
        // we will miss one page as the next cursor would be invalid after the deletion happened.
        $cursor = new offset_cursor([
            'limit' => $batch_limit,
            'page' => 1
        ]);

        $query = new query($workspace->get_id());
        $query->set_cursor($cursor);

        $cursor_paginator = loader::get_members($query);
        $members = $cursor_paginator->get_items()->all();

        while (!empty($members)) {
            /** @var member $member */
            foreach ($members as $member) {
                $this->delete_member($member);
            }

            $cursor_paginator = loader::get_members($query);
            $members = $cursor_paginator->get_items()->all();
        }

        $transaction->allow_commit();
    }

    /**
     * @param member $member
     *
     * @return void
     */
    public function delete_member(member $member): void {
        $member_interactor = new member_interactor($member, $this->actor_id);
        // The workspace delete mutation resolver will have already checked that a user had the required capability prior
        // to queuing the workspace delete task, and set workspace.is_to_be_deleted to true.
        if (!$member_interactor->get_member()->get_workspace()->is_to_be_deleted()) {
            throw new \coding_exception("The workspace has not been marked for deletion, please set to_be_deleted to true.");
        }

        $member->delete($this->actor_id);
    }

    /**
     * Assigns the members of cohorts to a specified workspace.
     *
     * @param workspace $workspace
     * @param collection $cohort_ids
     * @param bool $trigger_notification
     * @return member[] returns the member ids just being added
     * @deprecated since Totara 16.0 Bulk add audience members has been deprecated.
     */
    public function add_workspace_members_from_cohorts(
        workspace $workspace,
        collection $cohort_ids,
        bool $trigger_notification = true
    ): array {
        debugging("member_handler::add_workspace_members_from_cohorts has been deprecated", DEBUG_DEVELOPER);
        $interactor = new workspace_interactor($workspace, $this->actor_id);
        if (!($interactor->can_manage()
            || $interactor->is_owner())
            || !$interactor->can_add_audiences()
        ) {
            throw enrol_exception::on_cohort_enrol_permission();
        }

        $new_user_ids = audience_loader::get_bulk_members_to_add($workspace, $cohort_ids->all());
        if (empty($new_user_ids)) {
            return [];
        }

        return member::added_to_workspace_in_bulk($workspace, $new_user_ids, $trigger_notification, $this->actor_id);
    }
}