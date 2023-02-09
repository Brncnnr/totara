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

use stdClass;

/**
 * An adapter for json schema validator library to integrate with
 * our codebase.
 */
interface validator {
    /**
     * Given the json data, this functions will try to check if the json data
     * is in the right structure given by the json schema.
     *
     * @param stdClass|array $json_data
     * @param stdClass       $structure
     *
     * @return validation_result
     */
    public function in_structure($json_data, stdClass $structure): validation_result;
}