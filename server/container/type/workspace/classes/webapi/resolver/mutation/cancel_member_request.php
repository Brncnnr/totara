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
namespace container_workspace\webapi\resolver\mutation;

use container_workspace\member\member_request;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use container_workspace\entity\workspace_member_request;
use container_workspace\interactor\workspace\interactor as workspace_interactor;

/**
 * Mutation to cancel the member request.
 */
class cancel_member_request extends mutation_resolver {

    /**
     * @param array $args
     * @param execution_context $ec
     *
     * @return member_request
     */
    public static function resolve(array $args, execution_context $ec): member_request{
        global $USER, $CFG;

        $workspace_id = $args['workspace_id'];
        $repository = workspace_member_request::repository();
        $pending_entity = $repository->get_current_pending_request($workspace_id, $USER->id);

        if (null === $pending_entity) {
            throw new \coding_exception(
                "No pending request found for user '{$USER->id}' against workspace '{$workspace_id}'"
            );
        }

        // Sometimes the member request has happened before the tenant even kicked in. Hence we will
        // have to check if the user actor is still able to see the workspace or not.
        if ($CFG->tenantsenabled) {
            $interactor = workspace_interactor::from_workspace_id($pending_entity->course_id, $USER->id);

            if (!$interactor->can_view_workspace()) {
                // User is not able to view the workspace anymore. Throw exception
                throw new \coding_exception("User actor is not able to see the workspace");
            }
        }

        $member_request = member_request::from_entity($pending_entity);
        $member_request->cancel($USER->id);

        return $member_request;
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