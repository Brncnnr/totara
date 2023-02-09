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
namespace container_workspace\webapi\resolver\query;

use container_workspace\query\member\member_request_query;
use container_workspace\query\member\member_request_status;
use core\pagination\offset_cursor;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use container_workspace\member\member_request;
use core_container\factory;
use container_workspace\workspace;
use container_workspace\loader\member\member_request_loader;
use  container_workspace\interactor\workspace\interactor as workspace_interactor;

/**
 * A query to fetch a list of member requests to join to the workspace
 */
class member_requests extends query_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     *
     * @return member_request[]
     */
    public static function resolve(array $args, execution_context $ec): array {
        $workspace_id = $args['workspace_id'];

        /** @var workspace $workspace */
        $workspace = factory::from_id($workspace_id);

        if (!$workspace->is_typeof(workspace::get_type())) {
            throw new \coding_exception("Cannot fetch the list of member requests for different container");
        }

        $workspace_interactor = new workspace_interactor($workspace);
        if (!$workspace_interactor->can_manage()) {
            throw new \coding_exception("Cannot fetch the workspace member requests");
        }

        $query = new member_request_query($workspace_id);
        if (isset($args['status'])) {
            $status = member_request_status::get_value($args['status']);
            $query->set_member_request_status($status);
        }

        if (isset($args['cursor'])) {
            $cursor = offset_cursor::decode($args['cursor']);
            $query->set_cursor($cursor);
        }

        if (!$ec->has_relevant_context()) {
            $context = $workspace->get_context();
            $ec->set_relevant_context($context);
        }

        $paginator = member_request_loader::get_member_requests($query);
        return $paginator->get_items()->all();
    }

    /**
     * @return array
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('container_workspace')
        ];
    }
}