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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package container_workspace
 */

namespace container_workspace\webapi\resolver\query;

use container_workspace\interactor\workspace\interactor as workspace_interactor;
use container_workspace\totara_engage\share\recipient\library;
use container_workspace\workspace;
use core\pagination\offset_cursor;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use core_container\factory;
use totara_engage\card\card_loader;
use totara_engage\query\query;

class shared_cards extends query_resolver {
    /**
     * @param array             $args
     * @param execution_context $ec
     *
     * @return array
     */

    public static function resolve(array $args, execution_context $ec): array {
        /** @var workspace $workspace */
        $workspace = factory::from_id($args['workspace_id']);

        if (!$workspace->is_typeof(workspace::get_type())) {
            throw new \coding_exception("Cannot fetch discussions from container that is not a workspace");
        }

        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context($workspace->get_context());
        }

        $interactor = new workspace_interactor($workspace);
        if (!$interactor->can_view_library()) {
            throw new \moodle_exception('access_denied', 'container_workspace');
        }

        $query = new query();
        $query->set_filters($args['filter']);
        $query->set_component('container_workspace');
        $query->set_area($args['area']);

        if (!empty($args['cursor'])) {
            $cursor = offset_cursor::decode($args['cursor']);
            $query->set_cursor($cursor);
        }

        $recipient = new library($args['workspace_id']);
        $loader = new card_loader($query);
        $paginator = $loader->fetch_shared($recipient);

        return [
            'cursor' => $paginator,
            'cards' => $paginator->get_items()->all()
        ];
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