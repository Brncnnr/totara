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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

namespace mod_approval\model\workflow\stage_type\state_manager;

use coding_exception;
use mod_approval\exception\model_exception;
use mod_approval\model\application\action\action;
use mod_approval\model\application\application;
use mod_approval\model\application\application_state;
use mod_approval\model\workflow\stage_type\base as stage_type_base;
use mod_approval\model\workflow\workflow_stage;

/**
 * Application state manager.
 * Provides specification of how an application's state is handled in a stage type.
 */
abstract class base {

    /**
     * Stage type for the state manager.
     *
     * @var string|stage_type_base
     */
    protected $stage_type;

    /**
     * @var workflow_stage
     */
    protected $workflow_stage;

    public function __construct(workflow_stage $workflow_stage) {
        if (is_null($this->stage_type)) {
            throw new coding_exception("Stage type for state_manager must be specified");
        }
        if ($workflow_stage->type !== $this->stage_type) {
            throw new coding_exception("Application stage is not of type " . $this->stage_type);
        }

        $this->workflow_stage = $workflow_stage;
    }

    /**
     * Given an application state, get the natural next application state for this stage type.
     *
     * @param application_state $state
     * @return application_state
     */
    abstract public function get_next_state(application_state $state): application_state;

    /**
     * Given an application state, get the natural next application state for this stage type.
     *
     * @param application_state $state
     * @return application_state
     */
    abstract public function get_previous_state(application_state $state): application_state;

    /**
     * Get the initial state for an application in this stage type.
     *
     * @return application_state
     */
    abstract public function get_initial_state(): application_state;

    /**
     * Get state of an application when created in this stage type.
     *
     * @return application_state
     */
    abstract public function get_creation_state(): application_state;

    /**
     * Hook called on application start.
     */
    abstract public function on_application_start(application $application, int $actor_id): void;

    /**
     * Hook called when an application enters a state in this stage type
     *
     * @param application $application
     * @param application_state $old_state
     * @param int|null $actor_id
     * @return void
     */
    abstract public function on_state_entry(application $application, application_state $old_state, ?int $actor_id): void;

    /**
     * Hook called when an application exits a state in this stage type
     *
     * @param application $application
     * @param application_state $new_state
     * @param int|null $actor_id
     * @return void
     */
    abstract public function on_state_exit(application $application, application_state $new_state, ?int $actor_id): void;

    /**
     * Get the new state that an application should transition to, given the current state and an action.
     *
     * This checks for workflow_stage_interaction and workflow_stage_interaction_transition entities that may
     * be used to determine new state, otherwise it falls back to the default transition for an action. Then it
     * resolves the new state via the transition.
     *
     * @param action|string $action
     * @param application $application
     * @return application_state
     */
    public function get_new_state(action $action, application $application): application_state {
        $interaction = $this->workflow_stage->interactions->filter('action_code', $action->get_code())->first();
        if (is_null($interaction)) {
            // No interaction defined for this action, should we throw an exception or just return the current state?
            // FE should not have showed the button.
            return $application->current_state;
        }

        $new_state = $interaction->default_transition->transition->resolve($application->current_state);
        if (is_null($new_state)) {
            // Can't resolve new state
            throw new model_exception('No transition found for ' . $action->get_enum() . ' when application is at this state');
        }
        return $new_state;
    }
}