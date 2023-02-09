<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package core
 */

namespace core\webapi\resolver\query;

use core\webapi\execution_context;
use core_user\access_controller;

class user_own_profile extends \core\webapi\query_resolver {
    public static function resolve(array $args, execution_context $ec) {
        global $USER;

        // Note: the access controller checks for everything, including require login.
        $controller = access_controller::for($USER, null);
        if (!$controller->can_view_profile()) {
            throw new \coding_exception('Current user can not access their profile.');
        }
        return $USER;
    }
}
