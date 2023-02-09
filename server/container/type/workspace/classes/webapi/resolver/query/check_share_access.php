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

use container_workspace\exception\workspace_exception;
use container_workspace\interactor\workspace\interactor as workspace_interactor;
use core\webapi\execution_context;
use core\webapi\query_resolver;
use core\webapi\middleware\require_login;
use core\webapi\middleware\require_advanced_feature;
use core_container\factory;
use container_workspace\workspace;
use totara_engage\access\accessible;
use totara_engage\share\provider as share_provider;

/**
 * For resolving the query to check whether we have to change the access settings or not.
 */
class check_share_access extends query_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     * @return array
     */
    public static function resolve(array $args, execution_context $ec): array {
        global $USER;

        $workspace_id = $args['workspace']['instanceid'];

        /** @var workspace $workspace */
        $workspace = factory::from_id($workspace_id);

        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context($workspace->get_context());
        }

        if (!$workspace->is_typeof(workspace::get_type())) {
            throw new \coding_exception("Cannot find workspace by id '{$workspace_id}'");
        }

        $workspace_interactor = new workspace_interactor($workspace, $USER->id);
        if (!$workspace_interactor->can_view_library()) {
            throw workspace_exception::on_view();
        }

        $warning = false;
        $warning_message = "";

        // Note that at the moment, in workspace we are only supporting the library area.
        $items = $args['items'];
        foreach ($items as $item) {
            $provider = share_provider::create($item['component']);
            $instance = $provider->get_item_instance($item['itemid']);

            if ($instance instanceof accessible) {
                if ($workspace->is_public() && !$instance->is_public()) {
                    // Not match public
                    $warning = true;
                    $warning_message = get_string('warning_change_to_public', 'container_workspace');
                    break;
                } else if (!$workspace->is_public() && !$instance->is_public()) {
                    // Workspace is not a public and same with the instance - hence we will have a warning.
                    $warning = true;
                    $warning_message = get_string('warning_change_to_restricted', 'container_workspace');
                    break;
                }
            }
        }

        return [
            'warning' => $warning,
            'message' => $warning_message
        ];
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