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
namespace core\json\abstraction;

/**
 * An adapter of validation result which retrieved from json validator library.
 */
interface validation_result {
    /**
     * @return bool
     */
    public function is_valid(): bool;

    /**
     * Would prefer to use non localised string, as this
     * message should be used for developers to understand
     * what was wrong.
     *
     * @return string
     */
    public function get_error_message(): string;
}