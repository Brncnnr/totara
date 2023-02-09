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

use core\entity\user as user_entity;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\mutation_resolver;
use mod_approval\entity\assignment\assignment_approver as assignment_approver_entity;
use mod_approval\exception\access_denied_exception;
use mod_approval\exception\model_exception;
use mod_approval\interactor\workflow_interactor;
use mod_approval\model\assignment\approver_type\relationship;
use mod_approval\model\assignment\approver_type\user;
use mod_approval\model\assignment\assignment;
use mod_approval\model\assignment\assignment_approver as assignment_approver_model;
use mod_approval\model\assignment\assignment_approver_type;
use mod_approval\model\workflow\workflow_stage_approval_level;
use mod_approval\webapi\middleware\require_assignment;

/**
 * Update the list of approvers for an assignment level.
 *
 * This does a complete replacement of all the approvers at the given level.
 */
class assignment_set_level_approvers extends mutation_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        $input = $args['input'];

        $interactor_user = user_entity::logged_in();

        /** @var assignment $assignment */
        $assignment = $args['assignment'];

        $workflow_interactor = workflow_interactor::from_workflow(
            $assignment->get_workflow(),
            $interactor_user->id
        );

        list(
            $can_manage_individual_approvers,
            $can_manage_relationship_approvers
        ) = self::can_manage_approvers($workflow_interactor);

        $approval_level = workflow_stage_approval_level::load_by_id($input['approval_level_id']);
        $approvers = $input['approvers'];

        // Pre-check & translate the approver types.
        $approvers = array_map(function ($approver) {
            return [
                'identifier' => $approver['identifier'],
                'assignment_approver_type' => assignment_approver_type::enum_to_code($approver['assignment_approver_type']),
            ];
        }, $approvers);

        list($has_individual_approvers, $has_relationship_approvers) = self::get_approver_changes($approvers, $approval_level, $assignment);

        if ($has_individual_approvers && !$can_manage_individual_approvers) {
            throw access_denied_exception::workflow('User cannot update individual approvers for assignment');
        }
        if ($has_relationship_approvers && !$can_manage_relationship_approvers) {
            throw access_denied_exception::workflow('User cannot update relationship approvers for assignment');
        }

        // Make the change.
        $assignment->set_approvers_for_level($approval_level, $approvers);

        return ['success' => true];
    }

    /**
     * Can the assignment approvers be managed.
     * Returns what approver types(relationship/individual) can be managed.
     *
     * @param workflow_interactor $workflow_interactor
     * @return array of [can_manage_individual_approvers, can_manage_relationship_approvers]
     */
    private static function can_manage_approvers(workflow_interactor $workflow_interactor): array {
        $can_manage_individual_approvers = $workflow_interactor->has_manage_individual_approvers_capability();
        $can_manage_relationship_approvers = $workflow_interactor->has_manage_relationship_approvers_capability();

        $can_manage_approvers = $can_manage_individual_approvers || $can_manage_relationship_approvers;

        // Check that the user has some capability.
        if (!$can_manage_approvers) {
            throw access_denied_exception::workflow('User cannot update the assignment approvers');
        }

        return array($can_manage_individual_approvers, $can_manage_relationship_approvers);
    }

    /**
     * Get the approver changes that need to be done.
     *
     * @param array $approvers
     * @param workflow_stage_approval_level $approval_level
     * @param assignment $assignment
     * @return array of [has_individual_approvers, has_relationship_approvers]
     */
    private static function get_approver_changes(array $approvers, workflow_stage_approval_level $approval_level, assignment $assignment): array {
        $has_individual_approvers = false;
        $has_relationship_approvers = false;

        // Check what type of approvers have been provided.
        foreach ($approvers as $approver) {
            if ($approver['assignment_approver_type'] === user::TYPE_IDENTIFIER) {
                $has_individual_approvers = true;
            } else if ($approver['assignment_approver_type'] === relationship::TYPE_IDENTIFIER) {
                $has_relationship_approvers = true;
            } else {
                throw new model_exception('Unknown approver type provided');
            }
        }

        // Check what type of approvers are already stored (the user needs access to update them).
        $existing_approvers = assignment_approver_entity::repository()
            ->where('approval_id', $assignment->id)
            ->where('workflow_stage_approval_level_id', $approval_level->id)
            ->where('active', '=', true)
            ->get()->map_to(assignment_approver_model::class);

        /** @var assignment_approver_model $existing_approver */
        foreach ($existing_approvers as $existing_approver) {
            if ($existing_approver->type == user::TYPE_IDENTIFIER) {
                $has_individual_approvers = true;
            } else if ($existing_approver->type === relationship::TYPE_IDENTIFIER) {
                $has_relationship_approvers = true;
            } else {
                throw new model_exception('Unknown approver type found in database');
            }
        }
        return array($has_individual_approvers, $has_relationship_approvers);
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('approval_workflows'),
            new require_authenticated_user(),
            require_assignment::by_input_assignment_id(),
        ];
    }
}