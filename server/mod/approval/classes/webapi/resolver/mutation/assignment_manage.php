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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\webapi\resolver\mutation;

use core\collection;
use core\entity\user as user_entity;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\mutation_resolver;
use mod_approval\model\assignment\assignment;
use mod_approval\model\assignment\assignment_type\provider as assignment_type_provider;
use mod_approval\model\assignment\helper\assignments_for_workflow_stage;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\webapi\middleware\require_workflow;
use mod_approval\exception\access_denied_exception;

/**
 * assignment_manage class to create an assignment
 */
class assignment_manage extends mutation_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        /** @var workflow $workflow */
        $workflow = $args['workflow'];
        if (!$workflow->get_interactor(user_entity::logged_in()->id)->has_manage_assignment_overrides_capability()) {
            throw access_denied_exception::application('Cannot create assignment for the given workflow');
        }

        /** @var workflow_stage $workflow_stage*/
        $workflow_stage = $args['workflow_stage'];
        $input = $args['input'];

        $assignment_type = assignment_type_provider::get_by_enum($input['type']);
        $assignment = assignment::create(
            $workflow->course_id,
            $assignment_type::get_code(), // assignment_type::Organization|Position|Cohort
            $input['identifier'], // Organization|Position|Cohort->id
            false
        );
        $collection = new collection([$assignment]);

        $items = assignments_for_workflow_stage::get($collection, $workflow_stage->id);

        return $items[0];
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