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

namespace container_workspace\webapi\resolver\mutation;

use container_workspace\event\audience_added;
use container_workspace\interactor\workspace\interactor as workspace_interactor;
use container_workspace\task\bulk_add_workspace_members_adhoc_task;
use container_workspace\workspace;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use core_container\factory;

/**
 * @deprecated since Totara 16.0 Bulk add audience members has been deprecated.
 */
class add_bulk_audience_members extends mutation_resolver {

    /**
     * @inheritDoc
     * @deprecated since Totara 16.0
     */
    public static function resolve(array $args, execution_context $ec) {
        debugging("This mutation has been deprecated use add_audiences", DEBUG_DEVELOPER);
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

        bulk_add_workspace_members_adhoc_task::enqueue($workspace_id, $audience_ids);

        return ['workspace' => $workspace];
    }

    /**
     * @return array
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('container_workspace'),
        ];
    }
}