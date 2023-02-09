<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
namespace container_workspace\webapi\resolver\mutation;

use container_workspace\exception\workspace_exception;
use core\task\manager as task_manager;
use container_workspace\interactor\workspace\interactor as workspace_interactor;
use container_workspace\task\delete_workspace_task;
use container_workspace\workspace;
use core\notification;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use core_container\factory;

/**
 * Resolver for deleting a workspace via graphql.
 */
class delete extends mutation_resolver {

    /**
     * Mutation resolver.
     *
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(array $args, execution_context $ec): bool {
        global $USER;

        /** @var workspace $workspace */
        $workspace = factory::from_id($args['workspace_id']);

        if (!$workspace->is_typeof(workspace::get_type())) {
            throw new \coding_exception("Cannot delete different container from workspace's API");
        }

        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context($workspace->get_context());
        }

        $interactor = new workspace_interactor($workspace, $USER->id);
        if (!$interactor->can_delete()) {
            throw workspace_exception::on_delete();
        }

        $task = delete_workspace_task::from_workspace_id($workspace->get_id(), $USER->id);
        task_manager::queue_adhoc_task($task, true);

        $workspace->mark_to_be_deleted(true);

        // Add deleted workspace to notification.
        notification::success(get_string('notification_deleted', 'container_workspace', $workspace->fullname));
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('container_workspace'),
        ];
    }

}