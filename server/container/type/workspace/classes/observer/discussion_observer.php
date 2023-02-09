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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\observer;

use container_workspace\discussion\discussion;
use container_workspace\task\notify_create_new_discussion_task;
use container_workspace\workspace;
use container_workspace\event\discussion_created;
use container_workspace\event\discussion_updated;
use totara_core\content\content_handler;
use core\task\manager as task_manager;

/**
 * Observer class for discussion events.
 */
final class discussion_observer {
    /**
     * discussion_observer constructor.
     */
    private function __construct() {
        // Preventing this class from construction
    }

    /**
     * @param discussion_created $event
     * @return void
     */
    public static function on_created(discussion_created $event): void {
        static::handle_discussion($event->objectid, $event->userid);

        // Queue adhoc task to notify the members of workspace.
        $task = notify_create_new_discussion_task::from_discussion($event->objectid);
        task_manager::queue_adhoc_task($task);
    }

    /**
     * @param discussion_updated $event
     * @return void
     */
    public static function on_updated(discussion_updated $event): void {
        static::handle_discussion($event->objectid, $event->userid);
    }

    /**
     * Process discussion through content handler
     * @param int       $discussion_id
     * @param int|null  $actor_id
     *
     * @return void
     */
    private static function handle_discussion(int $discussion_id, ?int $actor_id = null): void {
        $discussion = discussion::from_id($discussion_id);
        $workspace = workspace::from_id($discussion->get_workspace_id());

        $handler = content_handler::create();
        $handler->handle_with_params(
            $workspace->get_name(),
            $discussion->get_content(),
            $discussion->get_content_format(),
            $discussion->get_id(),
            'container_workspace',
            discussion::AREA,
            $discussion->get_context()->id,
            $workspace->get_view_url(),
            $actor_id
        );
    }
}