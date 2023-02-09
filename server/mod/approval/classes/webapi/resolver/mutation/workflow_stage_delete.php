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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 */

namespace mod_approval\webapi\resolver\mutation;

use core\entity\user;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\mutation_resolver;
use mod_approval\exception\access_denied_exception;
use mod_approval\exception\malicious_form_data_exception;
use mod_approval\interactor\workflow_interactor;
use mod_approval\model\workflow\stage_type\finished;
use mod_approval\model\workflow\workflow_stage;

/**
 * workflow_stage_delete mutation
 */
class workflow_stage_delete extends mutation_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        $input = $args['input'];
        $workflow_stage = workflow_stage::load_by_id($input['workflow_stage_id']);
        $workflow_version = $workflow_stage->workflow_version;
        $workflow = $workflow_version->workflow;

        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context($workflow->get_context());
        }

        $user = user::logged_in();
        $workflow_interactor = workflow_interactor::from_workflow($workflow, $user->id);
        if (!$workflow_interactor->has_manage_stages_capability()) {
            throw access_denied_exception::workflow("Can not manage stages");
        }

        // The model allows any stage to be deleted, but the front-end and mutation enforce restrictions.
        if ($workflow_stage->id == $workflow_version->get_stages()->first()->id) {
            throw new malicious_form_data_exception('Cannot remove the first stage');
        }

        if ($workflow_stage->type::get_enum() == finished::get_enum()) {
            $finished_count = 0;
            foreach ($workflow_version->get_stages() as $some_stage) {
                if ($some_stage->type::get_enum() == finished::get_enum()) {
                    $finished_count++;
                }
            }
            if ($finished_count == 1) {
                throw new malicious_form_data_exception('Cannot remove the last finished stage');
            }
        }

        $workflow_version->delete_stage($workflow_stage);

        return [
            'workflow' => $workflow->refresh(true),
        ];
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('approval_workflows'),
            new require_authenticated_user(),
        ];
    }
}