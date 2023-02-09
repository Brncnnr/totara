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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\webapi\resolver\mutation;

use coding_exception;
use core\entity\user;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\mutation_resolver;
use mod_approval\exception\access_denied_exception;
use mod_approval\interactor\workflow_interactor;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_approval_level;
use mod_approval\webapi\middleware\require_workflow;

/**
 * Class workflow_reorder_levels mutation
 */
class workflow_reorder_levels extends mutation_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        /** @var workflow $workflow */
        $workflow = $args['workflow'];
        /** @var workflow_stage $workflow_stage */
        $workflow_stage = $args['workflow_stage'];
        $input = $args['input'];

        if (!isset($input['workflow_stage_approval_level_ids'])) {
            // Should not be here.
            throw new coding_exception('workflow_stage_approval_level_ids are missing');
        }

        $workflow_stage_approval_levels = array_map(function ($id) {
            return workflow_stage_approval_level::load_by_id($id);
        }, $input['workflow_stage_approval_level_ids']);

        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context($workflow->get_context());
        }
        $user = user::logged_in();
        $interactor = workflow_interactor::from_workflow($workflow, $user->id);

        if (!$interactor->has_reorder_approval_level_capability()) {
            throw access_denied_exception::workflow("Can not reorder approval levels");
        }
        $workflow_stage->reorder_approval_levels($workflow_stage_approval_levels);

        return [
            'stage' => $workflow_stage->refresh(true),
        ];
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('approval_workflows'),
            new require_authenticated_user(),
            require_workflow::by_input_workflow_stage_id()
        ];
    }
}
