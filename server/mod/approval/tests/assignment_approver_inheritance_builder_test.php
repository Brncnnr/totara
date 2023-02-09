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

use core_phpunit\testcase;
use mod_approval\model\assignment\approver_type\user as approver_type_user;
use mod_approval\model\assignment\assignment;
use mod_approval\model\assignment\assignment_approval_level;
use mod_approval\model\assignment\assignment_approver;
use mod_approval\model\assignment\assignment_type\position;
use mod_approval\model\assignment\helper\assignment_approver_inheritance_builder;
use mod_approval\model\status;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_approval_level;
use mod_approval\testing\approval_workflow_test_setup;
use mod_approval\testing\assignment_generator_object;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\assignment\helper\assignment_approver_inheritance_builder
 */
class mod_approval_assignment_approver_inheritance_builder_testcase extends testcase {

    use approval_workflow_test_setup;

    /**
     * Creates matching workflows with one stage and two approval levels, default assignment, and a set of position overrides.
     *
     * For workflow_1, the override assignments created are active.
     * For workflow_2, the override assignments created are drafts.
     *
     * @return array [$assignments_1, $assignments_2, $workflow_1, $workflow_2, $framework]
     */
    private function create_workflows(): array {
        // Create two identical workflows using the same framework.
        $this->setAdminUser();

        $framework = $this->generate_pos_hierarchy();
        $overrides = [
            'position_a' => $framework->division->position_a,
            'position_a_a' => $framework->division->position_a->grade_a,
            'position_a_a_a' => $framework->division->position_a->grade_a->region_a,
            'position_a_a_b' => $framework->division->position_a->grade_a->region_b,
            'position_a_b' => $framework->division->position_a->grade_b,
            'position_a_b_a' => $framework->division->position_a->grade_b->region_a,
            'position_a_b_b' => $framework->division->position_a->grade_b->region_b,
        ];
        $assignments_1 = [];
        $assignments_2 = [];

        // Create two identical workflows with 1 stage and 2 approval levels
        $workflow_entity_1 = $this->generator()->create_simple_request_workflow('Testing', 'Test', false);
        // create default assignment.
        $assignment_go = new assignment_generator_object(
            $workflow_entity_1->course_id,
            position::get_code(),
            $framework->division->id
        );
        $assignment_go->is_default = true;
        $assignment_go->status = status::ACTIVE;
        $default_assignment_entity_1 = $this->generator()->create_assignment($assignment_go);
        $assignments_1['division'] = assignment::load_by_entity($default_assignment_entity_1);
        $workflow_1 = workflow::load_by_entity($workflow_entity_1);
        /** @var workflow_stage $stage_1_1 */
        $stage_1_1 = $workflow_1->latest_version->stages->first();
        $stage_2_1 = $workflow_1->latest_version->get_next_stage($stage_1_1->id);
        $level_1_1 = $stage_2_1->approval_levels->first();
        $level_2_1 = $stage_2_1->feature_manager->approval_levels->add('Level 2');

        $workflow_entity_2 = $this->generator()->create_simple_request_workflow('Testing', 'Test', false);
        $assignment_go->course = $workflow_entity_2->course_id;
        $default_assignment_entity_2 = $this->generator()->create_assignment($assignment_go);
        $assignments_2['division'] = assignment::load_by_entity($default_assignment_entity_2);
        $workflow_2 = workflow::load_by_entity($workflow_entity_2);
        $stage_1_2 = $workflow_2->latest_version->stages->first();
        $stage_2_2 = $workflow_2->latest_version->get_next_stage($stage_1_2->id);
        $level_1_2 = $stage_2_2->approval_levels->first();
        $level_2_2 = $stage_2_2->feature_manager->approval_levels->add('Level 2');

        // For each override, create an activated assignment on workflow_1, and a non-activated assignment on workflow_2.
        foreach ($overrides as $key => $position) {
            $assignment_1 = assignment::create(
                $workflow_1->container,
                position::get_code(),
                $position->id,
                false,
                '1_'.$key
            );
            // Activate assignment 1 up front.
            $assignment_1->activate();
            $assignments_1[$key] = $assignment_1;

            $assignment_2 = assignment::create(
                $workflow_2->container,
                position::get_code(),
                $position->id,
                false,
                '2_'.$key
            );
            // Do not activate assignment 2 yet.
            $assignments_2[$key] = $assignment_2;
        }

        $workflow_1->refresh(true);
        $workflow_2->refresh(true);
        return [$assignments_1, $assignments_2, $workflow_1, $workflow_2, $framework];
    }

    /**
     * Adds approvers to both levels of the provided assignments, and activates $workflow_2's assignments.
     *
     * The same approver is added to the same level in each workflow. For example, Level 1 of assignment_1
     * will have the same approver as Level 1 of assignment_2.
     *
     * @param array $approver_overrides
     * @param array $assignments_1
     * @param array $assignments_2
     * @param workflow $workflow_1 Workflow matching assignments_1
     * @param workflow $workflow_2 Workflow matching assignments_2
     */
    private function add_approver_overrides_and_activate(array $approver_overrides, array $assignments_1, array $assignments_2) {
        $an_assignment_1 = reset($assignments_1);
        $workflow_1 = $an_assignment_1->workflow;
        $an_assignment_2 = reset($assignments_2);
        $workflow_2 = $an_assignment_2->workflow;
        $this->assertNotEquals($workflow_1->id, $workflow_2->id);

        $stage_1_1 = $workflow_1->latest_version->stages->first();
        $stage_2_1 = $workflow_1->latest_version->get_next_stage($stage_1_1->id);
        $level_1_1 = $stage_2_1->approval_levels->first();
        $level_2_1 = $stage_2_1->approval_levels->last();
        $this->assertEquals('Level 1', $level_1_1->name);
        $this->assertEquals('Level 2', $level_2_1->name);

        $stage_1_2 = $workflow_2->latest_version->stages->first();
        $stage_2_2 = $workflow_2->latest_version->get_next_stage($stage_1_2->id);
        $level_1_2 = $stage_2_2->approval_levels->first();
        $level_2_2 = $stage_2_2->approval_levels->last();
        $this->assertEquals('Level 1', $level_1_2->name);
        $this->assertEquals('Level 2', $level_2_2->name);

        foreach ($approver_overrides as $key => $override) {
            $level_1_approver = $this->getDataGenerator()->create_user();
            $approver_1_level_1 = assignment_approver::create(
                $assignments_1[$key],
                $level_1_1,
                approver_type_user::get_code(),
                $level_1_approver->id
            );
            $approver_2_level_1 = assignment_approver::create(
                $assignments_2[$key],
                $level_1_2,
                approver_type_user::get_code(),
                $level_1_approver->id
            );
            $level_2_approver = $this->getDataGenerator()->create_user();
            $approver_1_level_2 = assignment_approver::create(
                $assignments_1[$key],
                $level_2_1,
                approver_type_user::get_code(),
                $level_2_approver->id
            );
            $approver_2_level_2 = assignment_approver::create(
                $assignments_2[$key],
                $level_2_2,
                approver_type_user::get_code(),
                $level_2_approver->id
            );
        }

        // Sort the assignments 2 reverse, and activate.
        krsort($assignments_2);
        foreach ($assignments_2 as $key => $assignment) {
            $assignment->activate();
        }

        // Now each matching assignment should have the same set of approvers
        $this->compare_keyed_assignment_collection_approvers($assignments_1, $assignments_2, $workflow_1, $workflow_2);
    }

    /**
     * Compares two sets of assignments to ensure that the approver inheritance trees match.
     *
     * @param array $assignments_1
     * @param array $assignments_2
     * @param workflow $workflow_1
     * @param workflow $workflow_2
     */
    private function compare_keyed_assignment_collection_approvers(array $assignments_1, array $assignments_2, workflow $workflow_1, workflow $workflow_2) {
        foreach($assignments_1 as $key => $local_assignment_1) {
            $local_assignment_2 = $assignments_2[$key];
            // Ensure entity is not stale.
            $local_assignment_1->refresh(true);
            $local_assignment_2->refresh(true);
            $this->assertNotEquals($local_assignment_1->id, $local_assignment_2->id);
            $this->assertEquals($local_assignment_1->name, $local_assignment_2->name);
            $approvers_1 = $local_assignment_1->approvers;
            $approvers_2 = $local_assignment_2->approvers;
            $this->assertCount($approvers_1->count(), $approvers_2);
            $approval_levels_1 = $workflow_1->latest_version->stages->first()->approval_levels;
            $approval_levels_2 = $workflow_2->latest_version->stages->first()->approval_levels;
            foreach ($approval_levels_1 as $current_level_1) {
                $current_level_2 = $approval_levels_2->current();
                $approval_levels_2->next();
                $this->assertNotEquals($current_level_1->id, $current_level_2->id);
                $this->assertEquals($current_level_1->name, $current_level_2->name);
                $assignment_approval_level_1 = new assignment_approval_level($local_assignment_1, $current_level_1);
                $assignment_approval_level_2 = new assignment_approval_level($local_assignment_2, $current_level_2);
                $approvers_1 = $assignment_approval_level_1->get_approvers(true);
                $approvers_2 = $assignment_approval_level_2->get_approvers(true);
                $this->assertCount(count($approvers_1), $approvers_2, "Count of approvers at {$current_level_1->name} differs for {$local_assignment_1->name} ({$local_assignment_1->id} vs {$local_assignment_2->id})");
                foreach ($approvers_1 as $local_approver_1) {
                    $local_approver_2 = current($approvers_2);
                    next($approvers_2);
                    $this->assertNotEquals($local_approver_1->id, $local_approver_2->id);
                    // Same user.
                    $this->assertEquals($local_approver_1->identifier, $local_approver_2->identifier);
                    // Same ancestor status.
                    $this->assertEquals(is_null($local_approver_1->ancestor_id), is_null($local_approver_2->ancestor_id));
                }
            }
        }
    }

    public function test_rebuild_tree_for_default_assignment() {
        [$assignments_1, $assignments_2, $workflow_1, $workflow_2, $framework] = $this->create_workflows();

        // Add approvers to default assignment, grade_a, and region_a.
        $approver_overrides = [
            'division' => $framework->division,
            'position_a_a' => $framework->division->position_a->grade_a,
            'position_a_a_a' => $framework->division->position_a->grade_a->region_a,
        ];
        $this->add_approver_overrides_and_activate($approver_overrides, $assignments_1, $assignments_2);

        // Now rebuild assignment_2.
        $inheritance_builder = new assignment_approver_inheritance_builder();
        $inheritance_builder->rebuild_tree_for_assignment($workflow_2->default_assignment, $workflow_2->latest_version);
        $this->compare_keyed_assignment_collection_approvers($assignments_1, $assignments_2, $workflow_1, $workflow_2);
    }

    public function test_rebuild_tree_for_leaf_assignment() {
        [$assignments_1, $assignments_2, $workflow_1, $workflow_2, $framework] = $this->create_workflows();

        // Add approvers to default assignment, grade_a, and region_a.
        $approver_overrides = [
            'division' => $framework->division,
            'position_a_a' => $framework->division->position_a->grade_a,
            'position_a_a_a' => $framework->division->position_a->grade_a->region_a,
        ];
        $this->add_approver_overrides_and_activate($approver_overrides, $assignments_1, $assignments_2);

        // Now rebuild position_a_a_a on assignment_2.
        $inheritance_builder = new assignment_approver_inheritance_builder();
        $inheritance_builder->rebuild_tree_for_assignment($assignments_2['position_a_a_a'], $workflow_2->latest_version);
        $this->compare_keyed_assignment_collection_approvers($assignments_1, $assignments_2, $workflow_1, $workflow_2);
    }

    public function test_rebuild_tree_for_middle_assignment() {
        [$assignments_1, $assignments_2, $workflow_1, $workflow_2, $framework] = $this->create_workflows();

        // Add approvers to default assignment, grade_a, and region_a.
        $approver_overrides = [
            'division' => $framework->division,
            'position_a_a' => $framework->division->position_a->grade_a,
            'position_a_a_a' => $framework->division->position_a->grade_a->region_a,
        ];
        $this->add_approver_overrides_and_activate($approver_overrides, $assignments_1, $assignments_2);

        // Now rebuild position_a_a on assignment_2.
        $inheritance_builder = new assignment_approver_inheritance_builder();
        $inheritance_builder->rebuild_tree_for_assignment($assignments_2['position_a_a'], $workflow_2->latest_version);
        $this->compare_keyed_assignment_collection_approvers($assignments_1, $assignments_2, $workflow_1, $workflow_2);
    }

    public function test_rebuild_tree_for_middle_inherited_assignment() {
        [$assignments_1, $assignments_2, $workflow_1, $workflow_2, $framework] = $this->create_workflows();

        // Add approvers to default assignment, grade_a, and region_a.
        $approver_overrides = [
            'division' => $framework->division,
            'position_a_a' => $framework->division->position_a->grade_a,
            'position_a_a_a' => $framework->division->position_a->grade_a->region_a,
        ];
        $this->add_approver_overrides_and_activate($approver_overrides, $assignments_1, $assignments_2);

        // Now rebuild position_a_b (no override) on workflow_2.
        $inheritance_builder = new assignment_approver_inheritance_builder();
        $inheritance_builder->rebuild_tree_for_assignment($assignments_2['position_a'], $workflow_2->latest_version);
        $this->compare_keyed_assignment_collection_approvers($assignments_1, $assignments_2, $workflow_1, $workflow_2);
    }
}
