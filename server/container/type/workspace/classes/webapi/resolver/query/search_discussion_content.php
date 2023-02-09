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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\webapi\resolver\query;

use container_workspace\loader\discussion\loader;
use container_workspace\query\discussion\query;
use core\pagination\offset_cursor;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use core_container\factory;
use container_workspace\workspace;
use container_workspace\interactor\workspace\interactor as workspace_interactor;


/**
 * Query resolver for searching discussion content
 */
class search_discussion_content extends query_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     *
     * @return \stdClass[]
     */
    public static function resolve(array $args, execution_context $ec): array {
        /** @var workspace $workspace */
        $workspace = factory::from_id($args['workspace_id']);

        if (!$workspace->is_typeof(workspace::get_type())) {
            throw new \coding_exception("Cannot fetch discussion content from container that is not a workspace");
        }

        if (!isset($args['search_term'])) {
            throw new \coding_exception("Search term is required");
        }

        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context($workspace->get_context());
        }

        $workspace_interactor = new workspace_interactor($workspace);
        if (!$workspace_interactor->can_view_discussions()) {
            throw new \coding_exception("Cannot search discussion content");
        }

        $query = new query($workspace->get_id());
        $query->set_search_term($args['search_term']);

        if (isset($args['cursor'])) {
            $cursor = offset_cursor::decode($args['cursor']);
            $query->set_cursor($cursor);
        }

        $paginator = loader::search_discussion_content($query);
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