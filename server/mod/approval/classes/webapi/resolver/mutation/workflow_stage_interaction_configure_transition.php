<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 */

namespace mod_approval\webapi\resolver\mutation;

use core\entity\user;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\mutation_resolver;
use invalid_parameter_exception;
use mod_approval\exception\access_denied_exception;
use mod_approval\exception\model_exception;
use mod_approval\interactor\workflow_interactor;
use mod_approval\model\status;
use mod_approval\model\workflow\interaction\transition\provider;
use mod_approval\model\workflow\workflow_stage_interaction;
use mod_approval\model\workflow\workflow_stage_interaction_transition;
use mod_approval\webapi\middleware\require_workflow;

/**
 * Configure transition on a workflow_stage_interaction
 *
 * workflow_stage_interaction_configure_transition mutation
 */
class workflow_stage_interaction_configure_transition extends mutation_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        $input = $args['input'];
        /** @var workflow_stage_interaction $workflow_stage_interaction */
        $workflow_stage_interaction = $args['workflow_stage_interaction'];
        $workflow = $args['workflow'];

        if ($workflow_stage_interaction->workflow_stage->workflow_version->status !== status::DRAFT) {
            throw new model_exception("Can only edit interaction in workflow with a draft workflow version");
        }
        if (!$workflow_stage_interaction->workflow_stage->active) {
            throw new model_exception("Can not edit interaction in inactive workflow stage");
        }

        // Check capability. Note that execution context is set to workflow by middleware.
        $user = user::logged_in();
        $interactor = workflow_interactor::from_workflow($workflow, $user->id);
        if (!$interactor->has_manage_transitions_capability()) {
            throw access_denied_exception::workflow("Can not update transition");
        }

        // Instantiate desired transition from input.
        $transition = provider::get_transition_by_field($input['transition'], $workflow_stage_interaction->workflow_stage);

        // Load existing? Or create new?
        if (!empty($input['workflow_stage_interaction_transition_id'])) {
            $workflow_stage_interaction_transition = workflow_stage_interaction_transition::load_by_id($input['workflow_stage_interaction_transition_id']);
            if ($workflow_stage_interaction_transition->workflow_stage_interaction_id != $workflow_stage_interaction->id) {
                throw new invalid_parameter_exception('Transition does not belong to interaction');
            }
            // Set the transition.
            $workflow_stage_interaction_transition->set_transition($transition);
        } else {
            throw new model_exception('Creating new conditional transitions is not supported');
        }

        return [
            'transition' => $workflow_stage_interaction_transition
        ];
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('approval_workflows'),
            new require_authenticated_user(),
            require_workflow::by_input_workflow_stage_interaction_id(true),
        ];
    }
}