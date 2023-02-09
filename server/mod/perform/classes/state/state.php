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
 * Base class for all states.
 *
 * This is responsible for definition of all state transitions and other operations that depend on an object's state.
 */
abstract class state {
    /**
     * @var state_aware
     */
    protected $object = null;

    /**
     * @var state
     */
    protected $previous_state = null;

    /**
     * Get possible transitions with conditions from current state.
     * @return transition[]
     */
    abstract public function get_transitions(): array;

    /**
     * Code of status as it is stored in DB.
     * Status codes must be unique within one object type.
     *
     * @return int
     */
    abstract public static function get_code(): int;

    /**
     * Get internal state name.
     *
     * @return string
     */
    abstract public static function get_name(): string;

    /**
     * Get internal state type.
     *
     * @return string
     */
    abstract public static function get_type(): string;

    /**
     * Get translated state name.
     *
     * @return string
     */
    abstract public static function get_display_name(): string;

    /**
     * state constructor.
     *
     * @param object $object Object that this state belongs to.
     */
    public function __construct(object $object) {
        $this->object = $object;
    }

    /**
     * Get the object that this state belongs to.
     *
     * @return object
     */
    final public function get_object(): object {
        return $this->object;
    }

    /**
     * Check whether it's possible to switch to the given state class.
     *
     * @param string $target_state_class
     * @return bool
     */
    final public function can_switch(string $target_state_class): bool {
        try {
            $this->transition_to($target_state_class);
            return true;
        } catch (invalid_state_switch_exception $e) {
            return false;
        }
    }

    /**
     * Check if transition to the given target state is possible and return a target state object if it is.
     * This doesn't affect the object's state.
     *
     * @param string $target_state_class target state.
     * @return state new state
     * @throws invalid_state_switch_exception If not possible to switch
     */
    final public function transition_to(string $target_state_class): state {
        $transition = $this->get_transition_to($target_state_class);
        if ($transition && $transition->is_possible()) {
            return $transition->get_to()->set_previous_state($this);
        }
        throw new invalid_state_switch_exception(get_class($this), $target_state_class);
    }

    /**
     * Returns the given transition if the state defined it. If the state does not contain
     * the transition it returns null
     *
     * @param string $state_class
     * @return transition|null
     */
    final public function get_transition_to(string $state_class): ?transition {
        foreach ($this->get_transitions() as $transition) {
            if ($transition->get_to() instanceof $state_class) {
                return $transition;
            }
        }
        return null;
    }

    /**
     * This is called when the object has switched to the current state.
     * Can be used if triggering an event seems unnecessary.
     *
     * @return void
     */
    public function on_enter(): void {
        // Override if required.
    }

    /**
     * Indicates the triggering state that caused the system to move into this
     * state.
     *
     * @param state|null $state $state the triggering state.
     *
     * @return state this updated object.
     */
    public function set_previous_state(?state $state): state {
        $this->previous_state = $state;
        return $this;
    }
}
