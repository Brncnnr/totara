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
 * @package core
 */
namespace core\json\data_format;


use core\json\type;

/**
 * Base param type that relates to string value.
 */
abstract class base_string_param extends data_format {
    /**
     * Only returns the constant of param type PARAM_* that relates to
     * the string value only.
     *
     * @return string
     */
    abstract protected function get_param_type(): string;

    /**
     * @return string
     */
    public function get_for_type(): string {
        return type::STRING;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function validate($value): bool {
        if (!is_string($value)) {
            // Prevent array, or object to validate by the clean param.
            return false;
        }

        $param_type = $this->get_param_type();
        $cleaned = clean_param($value, $param_type);

        return $cleaned === $value;
    }
}