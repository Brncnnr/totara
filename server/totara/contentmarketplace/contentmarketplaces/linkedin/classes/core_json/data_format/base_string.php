<?php
/**
 * This file is part of Totara Core
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package contentmarketplace_linkedin
 */
namespace contentmarketplace_linkedin\core_json\data_format;

use core\json\data_format\data_format;
use core\json\type;

abstract class base_string extends data_format {
    /**
     * @return string
     */
    public function get_for_type(): string {
        return type::STRING;
    }

    /**
     * @param string $value
     * @return bool
     */
    public function validate($value): bool {
        if (!is_string($value)) {
            return false;
        }

        return static::do_validate($value);
    }

    /**
     * @param string $value
     * @return bool
     */
    abstract protected function do_validate(string $value): bool;
}