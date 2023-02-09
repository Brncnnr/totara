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

namespace mod_approval\model\workflow\interaction\transition;

use mod_approval\model\application\application_state;
use mod_approval\model\json_trait;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_interaction_transition;

/**
 * Transition to-state resolver base class, provides an api for determining what state an application
 * should be switched to.
 */
abstract class transition_base {

    /**
     * Get ENUM identifying the transition.
     *
     * @return string
     */
    public static function get_enum(): string {
        return strtoupper((new \ReflectionClass(static::class))->getShortName());
    }

    /**
     * Instantiate the to-stage resolver used by a particular transition instance.
     *
     * @param workflow_stage_interaction_transition $transition
     * @return static
     */
    public static function from_transition(workflow_stage_interaction_transition $transition): self {
        $classname_or_id = $transition->get_transition_field();
        // Here is the place where the transition field determines which transition class constructor to call.
        if (!is_numeric($classname_or_id)) {
            $classname = '\mod_approval\model\workflow\interaction\transition\\' . strtolower($classname_or_id);
            return new $classname();
        } else {
            // transition field was ID of a particular stage
            return new stage((int)$classname_or_id);
        }
    }

    /**
     * Resolves a post-transition application_state from a current state
     *
     * @param application_state $current_state The state we're coming from.
     * @return application_state|null The state we're going to.
     */
    abstract public function resolve(application_state $current_state): ?application_state;

    /**
     * Options available for the resolver.
     *
     * @param workflow_stage $stage
     * @return transition_option[]|array
     */
    abstract public function get_options(workflow_stage $stage): array;

    /**
     * Sortorder option.
     * @return int
     */
    abstract public static function get_sort_order(): int;

    /**
     * Encodes the implementation classname as a simple string for entity storage.
     *
     * @return string
     */
    public function transition_field(): string {
        return $this->get_enum();
    }
}