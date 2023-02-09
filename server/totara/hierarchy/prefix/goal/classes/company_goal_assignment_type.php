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

use core\collection;

// Needed to access GOAL_ASSIGNMENT_XYZ defines through graphql class.
global $CFG;
require_once($CFG->dirroot . '/totara/hierarchy/prefix/goal/lib.php');

/**
 * Convenience class to manipulate the company goal assignment types already
 * defined in server/totara/hierarchy/prefix/goal/lib.php.
 */
class company_goal_assignment_type {
    private const ALLOWED = [
        [GOAL_ASSIGNMENT_INDIVIDUAL, 'INDIVIDUAL', 'ind'],
        [GOAL_ASSIGNMENT_AUDIENCE, 'AUDIENCE', 'aud'],
        [GOAL_ASSIGNMENT_POSITION, 'POSITION', 'pos'],
        [GOAL_ASSIGNMENT_ORGANISATION, 'ORGANISATION', 'org']
    ];

    /**
     * Returns all recognized assignment types.
     *
     * @return collection|assignment_type[] assignment types.
     */
    public static function all(): collection {
        return collection::new(self::ALLOWED)
            ->map(function (array $tuple): assignment_type {
                [$value, $name, $lang_key] = $tuple;
                return new assignment_type($value, $name, $lang_key);
            });
    }

    /**
     * Returns the assignment type corresponding to the name passed in.
     *
     * @param string $name assignment type name.
     *
     * @return assignment_type the corresponding assignment type.
     */
    public static function by_name(string $name): assignment_type {
        foreach (self::ALLOWED as $tuple) {
            [$value, $type_name, $lang_key] = $tuple;

            if ($type_name === $name) {
                return new assignment_type($value, $type_name, $lang_key);
            }
        }

        throw new \coding_exception("Unknown company goal assignment type: '$name'");
    }

    /**
     * Returns the assignment type corresponding to the value passed in.
     *
     * @param int $value assignment type value.
     *
     * @return assignment_type the corresponding assignment type.
     */
    public static function by_value(int $value): assignment_type {
        foreach (self::ALLOWED as $tuple) {
            [$type_value, $name, $lang_key] = $tuple;

            if ($type_value === $value) {
                return new assignment_type($type_value, $name, $lang_key);
            }
        }

        throw new \coding_exception("Unknown company goal assignment type value: $value");
    }

    /**
     * Checks whether the incoming value is a valid assignment type.
     *
     * @param int $value value to check.
     */
    final public static function validate(int $value): void {
        self::by_value($value);
    }

    /**
     * Returns the 'individual' type.
     *
     * @return assignment_type the type.
     */
    public static function individual(): assignment_type {
        return self::by_value(GOAL_ASSIGNMENT_INDIVIDUAL);
    }

    /**
     * Returns the 'audience' type.
     *
     * @return assignment_type the type.
     */
    public static function audience(): assignment_type {
        return self::by_value(GOAL_ASSIGNMENT_AUDIENCE);
    }

    /**
     * Returns the 'position' type.
     *
     * @return assignment_type the type.
     */
    public static function position(): assignment_type {
        return self::by_value(GOAL_ASSIGNMENT_POSITION);
    }

    /**
     * Returns the 'organisation' type.
     *
     * @return assignment_type the type.
     */
    public static function organisation(): assignment_type {
        return self::by_value(GOAL_ASSIGNMENT_ORGANISATION);
    }
}
