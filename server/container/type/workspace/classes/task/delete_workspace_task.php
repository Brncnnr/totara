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
namespace container_workspace\task;

use container_workspace\local\workspace_helper;
use container_workspace\workspace;
use core\task\adhoc_task;
use core_container\factory;

class delete_workspace_task extends adhoc_task {
    /**
     * Create an adhoc tasks from workspace's id and the user who triggered the deletion.
     * So that the user is set to the cron's user actor.
     *
     * @param int $workspace_id
     * @param int $actor_id
     *
     * @return delete_workspace_task
     */
    public static function from_workspace_id(int $workspace_id, int $actor_id): delete_workspace_task {
        $task = new delete_workspace_task();

        $task->set_userid($actor_id);
        $task->set_custom_data(['workspace_id' => $workspace_id]);

        return $task;
    }

    /**
     * @return void
     */
    public function execute(): void {
        $data = $this->get_custom_data();
        $user_id = $this->get_userid();

        if (empty($data) || !property_exists($data, 'workspace_id') || empty($user_id)) {
            throw new \coding_exception("Cannot execute the deletion task due to missing workspace's id or user's id");
        }

        /** @var workspace $workspace */
        $workspace = factory::from_id($data->workspace_id);
        if (!$workspace->is_typeof(workspace::get_type())) {
            throw new \coding_exception("Invalid workspace's id");
        }

        if (!$workspace->is_to_be_deleted()) {
            throw new \coding_exception("The workspace was not set to be deleted");
        }

        workspace_helper::delete_workspace($workspace, $user_id);
    }
}