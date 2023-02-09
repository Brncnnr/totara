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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\state;

use core\orm\query\builder;
use ReflectionClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Trait for models that are state aware.
 */
trait state_aware {

    /**
     * Get the code of the model's currently stored state.
     *
     * @param string $status_type
     *
     * @return int
     */
    abstract public function get_current_state_code(string $status_type): int;

    /**
     * Make the model update its currently stored state.
     *
     * @param state $state
     * @return void
     */
    abstract protected function update_state_code(state $state): void;

    /**
     * Get the current state.
     *
     * @param string $state_type
     * @return state
     * @throws \coding_exception
     */
    public function get_state(string $state_type): state {
        $state_class = state_helper::from_code(
            $this->get_current_state_code($state_type),
            $this->get_object_type(),
            $state_type
        );
        return new $state_class($this);
    }

    /**
     * Get the object type.
     * By convention that is the object's short class name.
     *
     * @return string
     */
    protected function get_object_type(): string {
        return (new ReflectionClass($this))->getShortName();
    }

    /**
     * Switch state.
     * This method must be used for any state changes.
     *
     * @param string $target_state class name
     * @return self
     */
    public function switch_state(string $target_state): self {
        $old_state = $this->get_state(call_user_func([$target_state, 'get_type']));
        $new_state = $old_state->transition_to($target_state);

        builder::get_db()->transaction(function () use ($new_state) {
            $this->update_state_code($new_state);
            if ($new_state instanceof state_event) {
                $new_state->get_event()->trigger();
            }
            $new_state->on_enter();
        });

        return $this;
    }

}