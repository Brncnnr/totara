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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

namespace contentmarketplace_linkedin\core_json\data_format;

use core\json\data_format\data_format;
use core\json\type;

/**
 * Data format for a percentage value.
 */
class progress_percentage extends data_format {

    /**
     * @return string
     */
    public function get_for_type(): string {
        return type::STRING;
    }

    /**
     * Percentage must be an integer within the range of 0 and 100.
     *
     * @param mixed $value
     * @return bool
     */
    public function validate($value): bool {
        // Ensure that the value is a literal integer, or an integer string. Floats are not allowed.
        if (!is_int($value) && !ctype_digit((string) $value)) {
            return false;
        }

        return $value >= 0 && $value <= 100;
    }

}
