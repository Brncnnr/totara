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

namespace totara_useraction\filter;

/**
 * Factory for dealing with filters.
 */
class factory {
    /**
     * @param string $class
     * @param mixed $values
     * @param bool $from_input
     * @return filter_contract
     */
    public static function create(string $class, $values, bool $from_input = false): filter_contract {
        $method = $from_input ? 'create_from_input' : 'create_from_stored';
        $filter = call_user_func([$class, $method], $values);

        if (!$filter instanceof filter_contract) {
            throw new \coding_exception("Cannot create a filter that is not a valid filter_contract. Saw '$class'");
        }

        return $filter;
    }
}
