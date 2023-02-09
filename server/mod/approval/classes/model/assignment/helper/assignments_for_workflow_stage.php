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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\model\assignment\helper;

use core\collection;
use core\orm\query\builder;
use mod_approval\entity\assignment\assignment_approver as assignment_approver_entity;
use mod_approval\entity\workflow\workflow_stage_approval_level as workflow_stage_approval_level_entity;
use mod_approval\model\assignment\assignment;
use mod_approval\model\assignment\assignment_approval_level;
use mod_approval\model\assignment\assignment_approver;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\webapi\schema_object\workflow_assignment_approvals;

/**
 * Class assignments_for_workflow_stage
 *
 * @package mod_approval\model\assignment\helper
 */
class assignments_for_workflow_stage {

    /**
     * Get workflow assigment/s by workflow_stage_id
     *
     * @param collection $assignments
     * @param int $workflow_stage_id
     * @return workflow_assignment_approvals[]
     */
    public static function get(collection $assignments, int $workflow_stage_id): array {
        $assignment_ids = $assignments->map(function (assignment $assignment) {
            return $assignment->id;
        });

        // Find all approval levels for this workflow stage.
        $workflow_stage = workflow_stage::load_by_id($workflow_stage_id);
        $approval_levels = $workflow_stage->get_approval_levels();

        // To improve performance, we will load all approvers for all applicable assignments and levels,
        // then group them and set them in the assignment approval level objects.
        $approvers = builder::table(assignment_approver_entity::TABLE, 'approver')
            ->join([workflow_stage_approval_level_entity::TABLE, 'level'], 'approver.workflow_stage_approval_level_id', '=', 'id')
            ->where_in('approval_id', $assignment_ids->all())
            ->where('level.workflow_stage_id', $workflow_stage_id)
            ->where('active', '=', 1)
            ->where_null('ancestor_id')
            ->get()
            ->map_to(assignment_approver_entity::class)
            ->map_to(assignment_approver::class);

        $grouped_approvers = [];
        /** @var assignment_approver_entity $approver */
        foreach ($approvers as $approver) {
            $grouped_approvers[$approver->approval_id][$approver->workflow_stage_approval_level_id][] = $approver;
        }
        // Construct each override assignment.
        $result = [];
        foreach ($assignments as $assignment) {
            // Each workflow_assignment needs a list of ALL approval levels.
            $assignment_approval_levels = [];
            foreach ($approval_levels as $approval_level) {
                $assignment_approval_level = new assignment_approval_level(
                    $assignment,
                    $approval_level
                );
                // Set the pre-calculated set of approvers in the assignment approval level.
                $approvers = $grouped_approvers[$assignment->id][$approval_level->id] ?? [];
                $assignment_approval_level->set_approvers_cache($approvers);
                $assignment_approval_levels[] = $assignment_approval_level;
            }
            $result[] = new workflow_assignment_approvals($assignment, $assignment_approval_levels);
        }
        return $result;
    }
}