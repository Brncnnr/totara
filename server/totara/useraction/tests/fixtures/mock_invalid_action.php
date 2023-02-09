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

use stdClass;
use totara_useraction\action\action_result;

/**
 * A mock action used by unit tests.
 */
class mock_invalid_action {
    /**
     * @return string
     */
    public static function get_name(): string {
        return 'mock invalid action';
    }

    /**
     * @param stdClass $user
     * @return action_result
     */
    public function execute(stdClass $user): action_result {
        // We don't do anything
        return action_result::success();
    }
}
