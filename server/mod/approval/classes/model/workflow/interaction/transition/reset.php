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
use mod_approval\model\workflow\workflow_stage;

/**
 * Transition implementation that resolves to the initial state of the current stage in a workflow.
 */
class reset extends transition_base {

    /**
     * Returns the current stage.
     *
     * @param application_state $current_state
     * @return application_state|null
     */
    public function resolve(application_state $current_state): ?application_state {
        return $current_state->get_stage()->state_manager->get_initial_state();
    }

    /**
     * @inheritDoc
     */
    public static function get_sort_order(): int {
        return 30;
    }

    /**
     * @inheritDoc
     */
    public function get_options(workflow_stage $stage): array {
        $interactions = $stage->get_interactions();
        $default_transition = [];

        foreach ($interactions as $interaction) {
            $default_transition[] = $interaction->get_default_transition()->get_transition_field();
        }

        if (in_array("RESET", $default_transition)) {
            $transition_option = new transition_option(get_string('reset', 'mod_approval'), self::get_enum(), null);
        } else {
            return [];
        }

        return [$transition_option];
    }
}