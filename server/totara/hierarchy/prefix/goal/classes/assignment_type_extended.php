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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package hierarchy_goal
 */

namespace hierarchy_goal;

use coding_exception;
use goal;

/**
 * Extended goal assignment type.
 */
class assignment_type_extended {
    /**
     * @var assignment_type assignment type.
     */
    private $type = null;

    /**
     * @var string assignment description.
     */
    private $description = null;

    /**
     * Creates an extended company goal assignment type.
     *
     * @param assignment_type $type the company goal assignment type.
     * @param $source the source from which a type description is generated.
     */
    public static function create_company_goal_assignment_type(
        assignment_type $type,
        $source
    ): assignment_type_extended {
        company_goal_assignment_type::validate($type->get_value());
        $desc = goal::get_assignment_string(goal::SCOPE_COMPANY, $source);

        return new assignment_type_extended($type, $desc);
    }

    /**
     * Creates a personal goal assignment type.
     *
     * @param assignment_type $type the personal assignment type.
     * @param $source the source from which a type description is generated.
     */
    public static function create_personal_goal_assignment_type(
        assignment_type $type,
        $source
    ): assignment_type_extended {
        personal_goal_assignment_type::validate($type->get_value());
        $desc = goal::get_assignment_string(goal::SCOPE_PERSONAL, $source);

        return new assignment_type_extended($type, $desc);
    }

    /**
     * Default constructor.
     *
     * @param assignment_type $type assignment type..
     * @param string $description assignment type description.
     */
    public function __construct(assignment_type $type, string $description) {
        $this->type = $type;
        $this->description = $description;
    }

    /**
     * Returns the assignment type.
     *
     * @return assignment_type the type.
     */
    final public function get_type(): assignment_type {
        return $this->type;
    }

    /**
     * Returns the assignment type description.
     *
     * @return string assignment type description.
     */
    final public function get_description(): string {
        return $this->description;
    }

    /**
     * Magic attribute getter.
     *
     * @param string $field attribute name to look up.
     *
     * @return mixed the attribute value.
     */
    public function __isset(string $field) {
        $getter = "get_$field";
        return method_exists($this, $getter) && $this->{$getter}();
    }

    /**
     * Magic attribute getter.
     *
     * @param string $field attribute name to look up.
     *
     * @return mixed the attribute value.
     */
    public function __get(string $field) {
        $getter = "get_$field";
        if (method_exists($this, $getter)) {
            return $this->{$getter}();
        }

        throw new coding_exception('Unknown getter method for '.$field);
    }
}
