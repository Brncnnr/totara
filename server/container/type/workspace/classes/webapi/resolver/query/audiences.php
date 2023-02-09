<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package container_workspace
 */

namespace container_workspace\webapi\resolver\query;

use container_workspace\loader\audience\loader;
use container_workspace\query\audience\query;
use container_workspace\webapi\middleware\require_workspace_access;
use container_workspace\workspace;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use core_container\factory;

/**
 * Query to get audiences synced with a workspace.
 */
class audiences extends query_resolver {

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('container_workspace'),
            new require_workspace_access('input.workspace_id', ['can_add_audiences']),
        ];
    }

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        $input = $args['input'];

        /** @var workspace $workspace */
        $workspace = factory::from_id($input['workspace_id']);
        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context($workspace->get_context());
        }

        $query_params = [
            $workspace,
            $input['name'] ?? null,
            $input['pagination']['page'] ?? 1,
            $input['pagination']['limit'] ?? 20
        ];

        $query = new query(...$query_params);
        $audiences_paginator = loader::get_audiences($query);

        $data = $audiences_paginator->get();
        if (is_null($data['next_cursor'])) {
            $data['next_cursor'] = '';
        }

        return $data;
    }
}