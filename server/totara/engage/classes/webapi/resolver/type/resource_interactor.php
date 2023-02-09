<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @package totara_engage
 */

namespace totara_engage\webapi\resolver\type;

use core\webapi\execution_context;
use core\webapi\type_resolver;
use totara_engage\interactor\interactor;

class resource_interactor extends type_resolver {

    /**
     * @param string                $field
     * @param interactor            $source
     * @param array                 $args
     * @param execution_context     $ec
     *
     * @return mixed
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        if (!($source instanceof interactor)) {
            throw new \coding_exception('Invalid source parameter');
        }

        switch ($field) {
            case 'user_id':
                return $source->get_actor_id();

            case 'can_bookmark':
                return $source->can_bookmark();

            case 'can_comment':
                return $source->can_comment();

            case 'can_react':
                return $source->can_react();

            case 'can_share':
                return $source->can_share();

            default:
                debugging("Field '{$field}' is not supported");
                return null;
        }
    }

}