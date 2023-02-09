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
use core\collection;
use core\entity\user;

use hierarchy_goal\entity\company_goal;
use hierarchy_goal\entity\scale_value;

/**
 * Holds the company goal assignment details for a user.
 */
class company_goal_assignment {
    /**
     * @var int assigned company goal.
     */
    private $id = null;

    /**
     * @var company_goal assigned company goal.
     */
    private $goal = null;

    /**
     * @var user assigned user.
     */
    private $user = null;

    /**
     * @var collection|assignment_type_extended[] assignment types for this user
     *      and company goal.
     */
    private $assignment_types = null;

    /**
     * @var scale_value goal status.
     */
    private $scale_value = null;

    /**
     * Default constructor.
     *
     * @param int $id assignment id.
     * @param company_goal $goal assigned company goal.
     * @param user $user assigned user.
     * @param collection|assignment_type_extended[] $types assignment types.
     * @param scale_value $scale_value goal status.
     */
    public function __construct(
        int $id,
        company_goal $goal,
        user $user,
        array $types,
        ?scale_value $scale_value = null
    ) {
        $this->id = $id;
        $this->goal = $goal;
        $this->user = $user;
        $this->assignment_types = collection::new($types);
        $this->scale_value = $scale_value;
    }

    /**
     * Returns the assignment id.
     *
     * @return int the id.
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Returns the assigned company goal.
     *
     * @return company_goal the company goal.
     */
    public function get_goal(): company_goal {
        return $this->goal;
    }

    /**
     * Returns the assigned user.
     *
     * @return user the user.
     */
    public function get_user(): user {
        return $this->user;
    }

    /**
     * Returns the goal status.
     *
     * @return scale_value the status value.
     */
    public function get_scale_value(): ?scale_value {
        return $this->scale_value;
    }

    /**
     * Returns the assigned user id.
     *
     * @return user the user id.
     */
    public function get_user_id(): int {
        return $this->user->id;
    }

    /**
     * Returns the assignment types.
     *
     * @return user the types.
     */
    public function get_assignment_types(): collection {
        return $this->assignment_types;
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
