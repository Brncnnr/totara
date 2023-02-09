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
namespace container_workspace\webapi\resolver\type;

use core\webapi\execution_context;
use core\webapi\type_resolver;
use container_workspace\interactor\workspace\interactor;

/**
 * Class interactor
 * @package container_workspace\webapi\resolver\type
 */
class workspace_interactor extends type_resolver {
    /**
     * @param string                $field
     * @param interactor            $source
     * @param array                 $args
     * @param execution_context     $ec
     * @return mixed
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        if (!($source instanceof interactor)) {
            throw new \coding_exception("Invalid parameter of source");
        }

        switch ($field) {
            case 'workspace_id':
                return $source->get_workspace()->get_id();

            case 'user':
                $user_id = $source->get_user_id();
                return \core_user::get_user($user_id);

            case 'can_update':
                return $source->can_update();

            case 'can_delete':
                return $source->can_delete();

            case 'can_add_members':
                return $source->can_add_members();

            case 'can_invite':
                return $source->can_invite();

            case 'can_join':
                return $source->can_join();

            case 'own':
                return $source->is_owner();

            case 'workspaces_admin':
                return $source->can_administrate();

            case 'joined':
                return $source->is_joined();

            case 'can_request_to_join':
                return $source->can_request_to_join();

            case 'can_view':
                return $source->can_view_workspace();

            case 'has_requested_to_join':
                return $source->has_requested_to_join();

            case 'can_view_discussions':
                return $source->can_view_discussions();

            case 'can_create_discussions':
                return $source->can_create_discussions();

            case 'can_view_members':
                return $source->can_view_members();

            case 'can_view_library':
                return $source->can_view_library();

            case 'can_view_member_requests':
                return $source->can_accept_member_request() && $source->can_decline_member_request();

            case 'can_share_resources':
                return $source->can_share_resources();

            case 'can_unshare_resources':
                return $source->can_unshare_resources();

            case 'muted':
                return $source->has_turned_off_notification();

            case 'has_seen':
                $last_check_time = $args['last_check_time'] ?? null;
                return $source->has_seen($last_check_time);

            case 'can_transfer_ownership':
                return $source->can_manage();

            case 'can_leave':
                return $source->can_leave_workspace();

            case 'cannot_leave_reason':
                return $source->cannot_leave_reason();

            case 'can_add_audiences':
                return $source->can_add_audiences();

            default:
                debugging("Invalid field '{$field}' that is not in supported yet", DEBUG_DEVELOPER);
                return null;
        }
    }
}