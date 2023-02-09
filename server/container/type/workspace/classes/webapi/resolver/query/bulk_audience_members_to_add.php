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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\webapi\resolver\query;

use container_workspace\interactor\workspace\interactor as workspace_interactor;
use container_workspace\loader\member\audience_loader;
use container_workspace\workspace;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use core_container\factory;

/**
 * A query to fetch the number of members which would be added by the given audiences
 * @deprecated since Totara 16.0 This query has been deprecated alongside add_bulk_audience_members mutation
 */
class bulk_audience_members_to_add extends query_resolver {

    /**
     * @inheritDoc
     * @deprecated since Totara 16.0
     */
    public static function resolve(array $args, execution_context $ec): array {
        debugging("This query has been deprecated alongside add_bulk_audience_members mutation", DEBUG_DEVELOPER);
        $input = $args['input'];

        $workspace_id = $input['workspace_id'];
        $audience_ids = $input['audience_ids'] ?? [];

        /** @var workspace $workspace */
        $workspace = factory::from_id($workspace_id);
        if (!$workspace->is_typeof(workspace::get_type())) {
            throw new \moodle_exception('invalid_workspace', 'container_workspace');
        }

        $interactor = new workspace_interactor($workspace);
        if (!($interactor->can_manage() || $interactor->is_owner())
            || !$interactor->can_add_audiences()
        ) {
            throw new \moodle_exception('invalid_workspace', 'container_workspace');
        }

        if (empty($audience_ids)) {
            throw new \moodle_exception('invalid_workspace', 'container_workspace');
        }

        if (!$ec->has_relevant_context()) {
            $context = $workspace->get_context();
            $ec->set_relevant_context($context);
        }

        // Get all users from given audiences who are not yet enrolled in the workspace
        $members_to_add = audience_loader::get_bulk_members_to_add_count($workspace, $audience_ids);

        return ['members_to_add' => $members_to_add];
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