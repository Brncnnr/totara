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
 * @package mod_approval
 */

namespace mod_approval\webapi\resolver\mutation;

use core\entity\user;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\mutation_resolver;
use mod_approval\exception\access_denied_exception;
use mod_approval\interactor\workflow_interactor;
use mod_approval\model\assignment\assignment_type\provider as assignment_type_provider;
use mod_approval\model\workflow\helper\cloner as workflow_clone_helper;
use mod_approval\model\workflow\workflow;
use mod_approval\webapi\middleware\require_workflow;

/**
 * workflow_clone mutation.
 */
final class workflow_clone extends mutation_resolver {
    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec): array {
        /** @var workflow $existing_workflow */
        $existing_workflow = $args['workflow'];

        // TODO: TL-32221 Remove this and make name required in workflow.graphqls.
        if (empty($args['input']['name'])) {
            $name = $existing_workflow->name . ' - ' . date('Y-m-d');
        } else {
            $name = $args['input']['name'];
        }

        if (empty($args['input']['default_assignment'])) {
            $default_assignment = [
                'id' => $existing_workflow->default_assignment->assignment_identifier,
                'type' => $existing_workflow->default_assignment->assignment_type,
            ];
        } else {
            $default_assignment = [
                'id' => $args['input']['default_assignment']['id'],
                'type' => assignment_type_provider::get_by_enum($args['input']['default_assignment']['type'])::get_code(),
            ];
        }

        $user = user::logged_in();

        $interactor = workflow_interactor::from_workflow($existing_workflow, $user->id);
        if (!$interactor->can_clone()) {
            throw access_denied_exception::workflow('Cannot clone workflow');
        }

        $new_workflow = workflow_clone_helper::clone(
            $existing_workflow,
            $name,
            $default_assignment['type'],
            $default_assignment['id']
        );

        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context($new_workflow->get_context());
        }
        return ['workflow' => $new_workflow];
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('approval_workflows'),
            new require_authenticated_user(),
            require_workflow::by_input_workflow_id()
        ];
    }
}
