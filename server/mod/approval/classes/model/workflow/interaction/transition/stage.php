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
 * Transition implementation that always resolves to a particular stage.
 *
 * This transition is unusual in that it does not store its enum in the entity transition field, but rather the
 * database ID of the particular stage.
 */
class stage extends transition_base {

    /**
     * The stage this implementation always resolves to.
     *
     * @var workflow_stage
     */
    protected $workflow_stage;

    /**
     * Constructor uses an extra parameter to load the workflow_stage by ID.
     *
     * @param int $workflow_stage_id
     */
    public function __construct(int $workflow_stage_id) {
        $this->workflow_stage = workflow_stage::load_by_id($workflow_stage_id);
    }

    /**
     * @inheritDoc
     */
    public static function get_sort_order(): int {
        return 40;
    }

    /**
     * This implementation always resolves to a particular state rather than computing the to-stage from the current state.
     *
     * @param application_state $current_state
     * @return application_state
     */
    public function resolve(application_state $current_state): application_state {
        // Ignore the current state, and return the workflow stage.
        return $this->workflow_stage->state_manager->get_initial_state();
    }

    /**
     * This implementation stores the database ID of the particular stage, rather than its classname.
     *
     * @return string
     */
    public function transition_field(): string {
        return (string) $this->workflow_stage->id;
    }

    /**
     * Return all stages in workflow_version except itself
     *
     * @param workflow_stage $stage
     * @return array
     */
    public function get_options(workflow_stage $stage): array {
        $stages = $stage->workflow_version->stages->filter(
            function ($s) use ($stage) {
                /** @var workflow_stage $s */
                return $s->id !== $stage->id;
            }
        );

        $options = $stages->map(function ($stage) {
            /** @var workflow_stage $stage */
            return new transition_option(
                get_string('stage_number_name', 'mod_approval', [
                    'ordinal_number' => $stage->ordinal_number,
                    'name' => $stage->name,
                ]),
                $stage->id
            );
        });
        return $options->all();
    }
}