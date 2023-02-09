<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\state;

defined('MOODLE_INTERNAL') || die();

/**
 * This class is used to define options for state transitions.
 */
final class transition {
    /**
     * @var state
     */
    private $to = null;

    /**
     * All conditions must be met to perform the state transition.
     * @var string[] conditions
     */
    private $conditions = [];

    /**
     * Transition must be created using transition::to() factory function
     * @param state $state
     */
    private function __construct(state $state) {
        $this->to = $state;
    }

    /**
     * Create new transition to given state.
     *
     * @param state $state
     * @return transition
     */
    public static function to(state $state): transition {
        return new transition($state);
    }

    /**
     * Add conditions that are required to be met for state transition.
     *
     * @param array $conditions condition classes
     * @return transition
     */
    public function with_conditions(array $conditions): transition {
        $this->conditions = array_merge($this->conditions, $conditions);
        return $this;
    }

    /**
     * Check if all conditions are passing
     * @return bool
     */
    public function is_possible(): bool {
        foreach ($this->conditions as $condition_class) {
            /** @var condition $condition */
            $condition = new $condition_class($this->to->get_object());
            if (!$condition->pass()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get target state
     * @return state
     */
    public function get_to(): state {
        return $this->to;
    }
}
