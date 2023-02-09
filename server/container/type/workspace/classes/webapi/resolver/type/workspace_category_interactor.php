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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package container_workspace
 */

namespace container_workspace\webapi\resolver\type;

use container_workspace\interactor\workspace\category_interactor;
use core\webapi\execution_context;
use core\webapi\type_resolver;

/**
 * Class interactor
 *
 * @package container_workspace\webapi\resolver\type
 */
class workspace_category_interactor extends type_resolver {
    /**
     * @param string $field
     * @param category_interactor $source
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        if (!($source instanceof category_interactor)) {
            throw new \coding_exception("Invalid parameter of source");
        }

        switch ($field) {
            case 'user':
                $user_id = $source->get_user_id();
                return \core_user::get_user($user_id);

            case 'can_create_public':
                return $source->can_create_public();

            case 'can_create_private':
                return $source->can_create_private();

            case 'can_create_hidden':
                return $source->can_create_hidden();

            case 'can_create':
                return $source->can_create_public() || $source->can_create_private() || $source->can_create_hidden();

            case 'context_id':
                $context = $source->get_context();
                return $context->id;

            default:
                debugging("Invalid field '{$field}' that is not in supported yet", DEBUG_DEVELOPER);
                return null;
        }
    }
}