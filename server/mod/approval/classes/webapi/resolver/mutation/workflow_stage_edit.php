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
 */

namespace mod_approval\webapi\resolver\mutation;

use core\entity\user;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\mutation_resolver;
use mod_approval\exception\access_denied_exception;
use mod_approval\interactor\workflow_interactor;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\webapi\middleware\require_workflow;
use moodle_exception;

/**
 * Updates an existing workflow stage.
 *
 * workflow_stage_edit mutation
 */
class workflow_stage_edit extends mutation_resolver {
    /**
     * @var string Length is from table schema.
     */
    private const MAX_LENGTH = 255;

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        // Get the input data.
        $input = $args['input'];
        /* @var workflow_stage $workflow_stage */
        $workflow_stage = $args['workflow_stage'];
        $workflow = $args['workflow'];

        // Make sure name isn't ''.
        $name = trim($input['name']);
        if (empty($name)) {
            throw new \invalid_parameter_exception("Stage name must not be empty");
        }

        if (\core_text::strlen($name) > self::MAX_LENGTH) {
            throw new moodle_exception('Length of name can not exceed 255');
        }

        // Check the user has the capability to edit the stage.
        $interactor_user = user::logged_in();
        $interactor = workflow_interactor::from_workflow($workflow, $interactor_user->id);
        if (!$interactor->has_manage_stages_capability()) {
            throw access_denied_exception::workflow("Can not manage stages");
        }

        // Return the updated stage.
        return [
            'stage' => $workflow_stage->set_name($name),
        ];
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('approval_workflows'),
            new require_authenticated_user(),
            require_workflow::by_input_workflow_stage_id(true),
        ];
    }
}