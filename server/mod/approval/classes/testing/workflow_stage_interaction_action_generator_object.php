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

namespace mod_approval\testing;

/**
 * Class workflow_stage_interaction_action_generator_object
 *
 * Provides a structured interface for passing properties to the workflow generator.
 *
 * @package mod_approval\testing
 */
final class workflow_stage_interaction_action_generator_object {
    public $workflow_stage_interaction_id;
    public $condition_key;
    public $condition_data;
    public $effect;
    public $effect_data;

    /**
     * Workflow_stage_interaction_action_generator_object constructor, captures required properties.
     *
     * @param int $workflow_stage_interaction_id
     * @param string $effect
     * @param string|null $effect_data
     */
    public function __construct(int $workflow_stage_interaction_id, string $effect, string $effect_data = null) {
        $this->workflow_stage_interaction_id = $workflow_stage_interaction_id;
        $this->effect = $effect;
        $this->effect_data = $effect_data;
    }
}