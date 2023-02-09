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
use container_workspace\interactor\discussion\interactor;

/**
 * Resolver for discussion interactor type
 */
class discussion_interactor extends type_resolver {
    /**
     * @param string            $field
     * @param interactor        $source
     * @param array             $args
     * @param execution_context $ec
     * @return mixed|null
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        if (!($source instanceof interactor)) {
            throw new \coding_exception("Expecting the parameter to be type of " . interactor::class);
        }

        switch ($field) {
            case 'discussion_id':
                return $source->get_discussion_id();

            case 'workspace_id':
                return $source->get_workspace_id();

            case 'can_update':
                return $source->can_update();

            case 'can_delete':
                return $source->can_delete();

            case 'can_comment':
                // At this point, every member can always comment to the discussion post.
                return $source->can_comment();

            case 'user':
                $user_id = $source->get_user_id();
                return \core_user::get_user($user_id);

            case 'reacted':
                return $source->reacted();

            case 'can_react':
                return $source->can_react();

            case 'can_pin':
                return $source->can_pin();

            case 'can_report':
                return $source->can_report();

            case 'removed':
                return $source->is_removed();

            default:
                debugging("The field '{$field}' is not supported yet", DEBUG_DEVELOPER);
                return null;
        }
    }
}