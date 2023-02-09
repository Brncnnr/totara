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

/**
 * Convenience class to manipulate goal assignment types already defined in
 * server/totara/hierarchy/prefix/goal/lib.php.
 */
class assignment_type {
    /**
     * @var int assignment name.
     */
    private $value = null;

    /**
     * @var string assignment type name.
     */
    private $name = null;

    /**
     * @var string assignment type display name.
     */
    private $label = null;

    /**
     * Default constructor.
     *
     * @param int $value type value.
     * @param string $name "official" type name.
     * @param string $label_key key to lookup lang string for this type.
     */
    public function __construct(int $value, string $name, string $label_key) {
        $this->name = $name;
        $this->value = $value;
        $this->label = get_string(
            "goal_assignment_type_$label_key", 'totara_hierarchy'
        );
    }

    /**
     * Returns the assignment name.
     *
     * @return string the name.
     */
    final public function get_name(): string {
        return $this->name;
    }

    /**
     * Returns the display label.
     *
     * @return string the label.
     */
    final public function get_label(): string {
        return $this->label;
    }

    /**
     * Returns the type value.
     *
     * @return int the value.
     */
    final public function get_value(): int {
        return $this->value;
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
