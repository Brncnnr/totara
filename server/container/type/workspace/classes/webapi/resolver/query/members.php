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

use container_workspace\member\member;
use container_workspace\loader\member\loader;
use container_workspace\member\status;
use container_workspace\query\member\query;
use container_workspace\query\member\sort;
use container_workspace\webapi\middleware\require_workspace_members_access;
use core\pagination\offset_cursor;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use core_container\factory;

/**
 * Members query resolver
 */
class members extends query_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     *
     * @return member[]
     */
    public static function resolve(array $args, execution_context $ec): array {
        $workspace_id = $args['workspace_id'];

        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context(factory::from_id($workspace_id)->get_context());
        }

        $query = new query($workspace_id);

        if (isset($args['cursor'])) {
            $cursor = offset_cursor::decode($args['cursor']);
            $query->set_cursor($cursor);
        }

        if (isset($args['status'])) {
            $status_value = status::get_value($args['status']);
            $query->set_member_status($status_value);
        }

        if (isset($args['search_term'])) {
            $query->set_search_term($args['search_term']);
        }

        $sort = sort::get_value($args['sort']);
        $query->set_sort($sort);

        $paginator = loader::get_members($query);
        return $paginator->get_items()->all();
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('container_workspace'),
            new require_workspace_members_access('workspace_id'),
        ];
    }

}