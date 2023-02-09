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

use container_workspace\loader\workspace\loader;
use container_workspace\query\workspace\access;
use container_workspace\query\workspace\query;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use container_workspace\workspace;

/**
 * Query resolver for query container_workspace_workspaces.
 */
class workspaces extends query_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     * @return workspace[]
     */
    public static function resolve(array $args, execution_context $ec): array {
        global $USER;
        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context(\context_coursecat::instance(workspace::get_default_category_id()));
        }

        // At this point, target user and actor can be the same until the parameters tell us differently.
        $actor_id = $USER->id;
        $target_user_id = $USER->id;

        if (isset($args['user_id'])) {
            $target_user_id = $args['user_id'];
        }

        if ($actor_id != $target_user_id) {
            if (isset($args['access']) && !access::is_public(access::get_value($args['access']))) {
                debugging(
                    "You are not allowed to fetch target user's non public workspaces, access is reset",
                    DEBUG_DEVELOPER
                );
            }

            // Reset access to PUBLIC as we are only fetching the public.
            $args['access'] = access::get_code(access::PUBLIC);
        }

        $query = query::from_parameters($args, $target_user_id);
        $query->set_actor_id($actor_id);

        $paginator = loader::get_workspaces($query);

        return $paginator->get_items()->all();
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