<?php
/**
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage\resource\input;

use totara_engage\access\access;

/**
 * Validating for access value.
 */
final class access_validator implements input_validator {
    /**
     * @param int $value
     * @return bool
     */
    public function is_valid($value): bool {
        if (!is_number($value)) {
            debugging("Expecting the parameter \$value to be an integer", DEBUG_DEVELOPER);
            return false;
        }

        return access::is_valid($value);
    }
}