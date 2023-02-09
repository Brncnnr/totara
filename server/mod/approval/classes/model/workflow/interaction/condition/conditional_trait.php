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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\model\workflow\interaction\condition;

use mod_approval\exception\model_exception;

/**
 * Class to provide consistent support for conditional transitions and actions.
 */
trait conditional_trait {

    /**
     * Uses the implementing object's conditional_interface to create an interaction_condition object.
     *
     * @return interaction_condition
     */
    public function get_condition(): interaction_condition {
        return interaction_condition::from_conditional_interface($this);
    }

    /**
     * Get the condition_key value from the entity.
     *
     * @return string
     */
    public function get_condition_key_field(): string {
        return $this->entity->condition_key;
    }

    /**
     * Get the condition_data value from the entity.
     *
     * @return string
     */
    public function get_condition_data_field(): string {
        return $this->entity->condition_data;
    }

    /**
     * Change the implementing object's condition and save the underlying entity.
     *
     * Throws an exception if you try to change entity condition_key from null (unconditional aka default) to something else.
     *
     * @param interaction_condition $condition
     * @return $this
     */
    public function set_condition(interaction_condition $condition): self {
        $this->entity->condition_key = $condition->condition_key_field();
        $this->entity->condition_data = $condition->condition_data_field();
        $this->entity->save();
        return $this;
    }
}