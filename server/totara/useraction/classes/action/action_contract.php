<?php
/**
 * This file is part of Totara Talent Experience Platform
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Cody Finegan <cody.finegan@totara.com>
 * @package totara_useraction
 */

namespace totara_useraction\action;

use core\entity\user;

/**
 * The base contract that each individual action must implement.
 */
interface action_contract {
    /**
     * The resolved language string for this action's label.
     *
     * @return string
     */
    public static function get_name(): string;

    /**
     * Execute the specific action against the specific user.
     * Returns either a true/false depending on success.
     *
     * @param user $user
     * @return action_result
     */
    public function execute(user $user): action_result;
}

