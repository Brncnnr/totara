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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\observer;

use container_workspace\local\workspace_helper;
use container_workspace\task\add_content_task;
use container_workspace\totara_engage\share\recipient\library;
use core_container\factory;
use totara_engage\event\share_created;
use core\task\manager;
use totara_engage\share\helper;
use container_workspace\workspace;

/**
 * Observer class for share event
 */
final class share_observer {
    /**
     * share_observer constructor.
     * Preventing this class from construction
     */
    private function __construct() {
    }

    /**
     * @param share_created $event
     */
    public static function content_added(share_created $event): void {
        $others = $event->other;

        // When item does not shared to workspace, we do not need send notification.
        if ($others['area'] !== library::AREA && $others['recipient_component'] !== workspace::get_type()) {
            return;
        }

        $sharer_id = $event->userid;
        $item_id = $event->objectid;

        $workspace_id =  $others['recipient_id'];

        /** @var workspace $workspace */
        $workspace = factory::from_id($workspace_id);

        if (!$workspace->is_typeof(workspace::get_type())) {
            throw new \coding_exception("Cannot find workspace by id '{$workspace_id}'");
        }

        // Create a task to send notification to the user within workspace.
        self::create_notify_shared_task($others, $sharer_id, $item_id);

        // Bump the timestamp of workspace - so that user can see its updated.
        workspace_helper::update_workspace_timestamp($workspace, $sharer_id);
    }

    /**
     * @param array $data
     * @param int   $user_id
     * @param int   $item_id
     *
     * @return void
     */
    private static function create_notify_shared_task(array $data, int $user_id, int $item_id): void {
        $task = new add_content_task();
        $task->set_component('totara_engage');
        $task->set_custom_data([
            'component' => helper::get_provider_type($data['component']),
            'workspace_id' => $data['recipient_id'],
            'sharer_id' => $user_id,
            'item_name' => helper::get_resource_name($data['component'], $item_id)
        ]);

        manager::queue_adhoc_task($task);
    }
}