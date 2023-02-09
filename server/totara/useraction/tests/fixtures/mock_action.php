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

namespace totara_useraction\fixtures;

use core\entity\user;
use totara_useraction\action\action_contract;
use totara_useraction\action\action_result;

/**
 * A mock action used by unit tests.
 */
class mock_action implements action_contract {
    /**
     * @return string
     */
    public static function get_name(): string {
        return 'mock action';
    }

    /**
     * @param user $user
     * @return bool
     */
    public function execute(user $user): action_result {
        // We don't do anything
        return action_result::success();
    }
}
