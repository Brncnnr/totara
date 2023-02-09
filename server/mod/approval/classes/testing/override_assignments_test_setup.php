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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\testing;

use core\entity\user;
use core\orm\query\builder;
use core\testing\generator as core_generator;
use hierarchy_organisation\entity\organisation;
use mod_approval\entity\assignment\assignment_approver;
use mod_approval\entity\workflow\workflow as workflow_entity;
use mod_approval\entity\workflow\workflow_stage_approval_level;
use mod_approval\entity\workflow\workflow_version;
use mod_approval\model\assignment\approver_type\relationship as relationship_approver_type;
use mod_approval\model\assignment\approver_type\user as user_approver_type;
use mod_approval\model\assignment\assignment_approver as assignment_approver_model;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\status;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow as workflow_model;
use mod_approval\model\workflow\workflow_stage as workflow_stage_model;
use totara_core\relationship\relationship;

/**
 * Trait override_assignments_test_setup provides testcase classes with easy access to methods for generating test scenarios.
 *
 * @package mod_approval\testing
 */
trait override_assignments_test_setup {

    use approval_workflow_test_setup;

    /**
     * Creates a workflow, and organization framework, and an assignment for the top-level organization,
     * and four override assignments. No inheritance structure is defined.
     *
     * @param string $workflow_type_name
     * @return array [workflow_model, workflow_stage_model[], assignment_entity[]]
     */
    protected function create_workflow_with_basic_override_assignments(string $workflow_type_name = 'Testing'): array {
        list($workflow_entity, $framework, ) = $this->create_workflow_and_assignment($workflow_type_name, false, false);

        $workflow_model = workflow_model::load_by_entity($workflow_entity);
        $version = $workflow_model->get_latest_version();

        /** @var workflow_stage_model[] $stages */
        $stages[0] = $version->get_stages()->first();
        $stages[1] = $version->get_next_stage($stages[0]->id);
        $approval_level = $this->generator()->create_approval_level($stages[1]->id, 'Extra level', 3);

        $override_entity_ids = [
            $framework->agency->subagency_a->id,
            $framework->agency->subagency_a->program_a->id,
            $framework->agency->subagency_a->program_b->id,
            $framework->agency->subagency_b->id,
        ];

        $override_assignments = [];
        foreach ($override_entity_ids as $override_entity_id) {
            $override_assignment_go = new assignment_generator_object(
                $workflow_entity->course_id,
                assignment_type\organisation::get_code(),
                $override_entity_id
            );
            $override_assignment_go->is_default = false;
            $override_assignment_go->status = status::ACTIVE;
            $override_assignments[] = $this->generator()->create_assignment($override_assignment_go);
        }

        // Create an audience override, too.
        $audience = $this->getDataGenerator()->create_cohort(['name' => 'Audience']);
        $override_assignment_go = new assignment_generator_object(
            $workflow_entity->course_id,
            assignment_type\cohort::get_code(),
            $audience->id
        );
        $override_assignment_go->is_default = false;
        $override_assignment_go->status = status::ACTIVE;
        $override_assignments[] = $this->generator()->create_assignment($override_assignment_go);

        foreach ($override_assignments as $override_assignment) {
            $approver_go = new assignment_approver_generator_object(
                $override_assignment->id,
                $approval_level->id,
                relationship_approver_type::TYPE_IDENTIFIER,
                relationship::load_by_idnumber('manager')->id
            );
            $this->generator()->create_assignment_approver($approver_go);
        }

        $workflow_model->publish($version);

        return [$workflow_model->refresh(), $stages, $override_assignments];
        // In tests:
        // list($workflow, $stages, $override_assignments) = $this->create_workflow_and_override_assignments();
    }

    /**
     * Creates a workflow, and organization framework, and an assignment for the top-level organization,
     * and four override assignments with approvers in various places.
     *
     * The inheritance structure:
     * override 0: sub a, approvers at level 1, inherits level 2 from default
     * override 1: sub a prog a, approvers at level 1, inherits level 2 from default
     * override 2: sub a prog b, approvers at level 2, inherits level 1 from override 0
     * override 3: sub b, no approver overrides, inherits both levels from default
     *
     * @return array containing a control and test workflow, with many other properties
     */
    protected function create_workflow_with_complex_override_assignments(): array {
        $result = [];

        // Create control workflow, version, stage, default assignment.
        /** @var workflow_entity $control_workflow_entity */
        list($control_workflow_entity, $control_framework) = $this->create_workflow_and_assignment('Control workflow type');
        $result['control_workflow'] = ['workflow' => $control_workflow_entity];

        // Create a control override assignment.
        $control_override_assignment_go = new assignment_generator_object(
            $control_workflow_entity->course_id,
            assignment_type\organisation::get_code(),
            $control_framework->agency->subagency_a->id
        );
        $control_override_assignment_go->is_default = false;
        $control_override_assignment_go->status = status::ACTIVE;
        $control_override_assignment = $this->generator()->create_assignment($control_override_assignment_go);
        $result['control_workflow']['override_assignment'] = $control_override_assignment;

        // Get the control approval level.
        $control_approval_level = workflow_stage_approval_level::repository()
            ->order_by('id')
            ->first(true);
        $result['control_workflow']['approval_level'] = $control_approval_level;

        // Create a control approver.
        $control_approver_go = new assignment_approver_generator_object(
            $control_override_assignment->id,
            $control_approval_level->id,
            relationship_approver_type::TYPE_IDENTIFIER,
            relationship::load_by_idnumber('manager')->id
        );
        $control_approver = $this->generator()->create_assignment_approver($control_approver_go);
        $result['control_workflow']['approver'] = $control_approver;

        // Create main workflow.
        list($workflow_entity, $framework) = $this->create_workflow_and_assignment('Test workflow type', false, false);
        $workflow_model = workflow_model::load_by_entity($workflow_entity);
        $version = $workflow_model->get_latest_version();
        $result['test_workflow'] = [
            'workflow' => $workflow_model,
            'version' => $version,
        ];

        // Load first stage (used as another control) and create another.
        /** @var workflow_stage_model $stage1 */
        $stage1 = $version->get_stages()->first();
        $stage2 = $version->get_next_stage($stage1->id);
        $result['test_workflow']['control_stage'] = $stage1;
        $result['test_workflow']['stage'] = $stage2;

        // Create four override assignments - one for each framework item.
        $override_entities = [
            $framework->agency->subagency_a,
            $framework->agency->subagency_a->program_a,
            $framework->agency->subagency_a->program_b,
            $framework->agency->subagency_b,
        ];
        $override_assignments = [];
        foreach ($override_entities as $override_entity) {
            $override_assignment_go = new assignment_generator_object(
                $workflow_entity->course_id,
                assignment_type\organisation::get_code(),
                $override_entity->id
            );
            $override_assignment_go->is_default = false;
            $override_assignment_go->status = status::ACTIVE;
            $override_assignments[] = $this->generator()->create_assignment($override_assignment_go);
        }
        // Create an audience override, too.
        $audience = $this->getDataGenerator()->create_cohort(['name' => 'Audience']);
        $override_assignment_go = new assignment_generator_object(
            $workflow_entity->course_id,
            assignment_type\cohort::get_code(),
            $audience->id
        );
        $override_assignment_go->is_default = false;
        $override_assignment_go->status = status::ACTIVE;
        $override_assignments[] = $this->generator()->create_assignment($override_assignment_go);
        // Create an unrelated organisation override (use an org from the control framework)
        $override_assignment_go = new assignment_generator_object(
            $workflow_entity->course_id,
            assignment_type\organisation::get_code(),
            $control_framework->agency->subagency_a->program_a->id
        );
        $override_assignment_go->is_default = false;
        $override_assignment_go->status = status::ACTIVE;

        // Hack to modify the control assignment name
        organisation::repository()->where('id', $control_framework->agency->subagency_a->program_a->id)
            ->update([
                'fullname' => 'Control ' . $control_framework->agency->subagency_a->program_a->fullname
            ]);

        $override_assignments[] = $this->generator()->create_assignment($override_assignment_go);
        $result['test_workflow']['override_assignments'] = $override_assignments;

        // Set up levels.
        $stage2_level1 = $stage2->approval_levels->first();
        $stage2_level2 = $stage2->add_approval_level('Level 2');
        $result['test_workflow']['level1'] = $stage2_level1;
        $result['test_workflow']['level2'] = $stage2_level2;

        // Give the control an approver.
        $approver_go = new assignment_approver_generator_object(
            $control_override_assignment->id,
            $control_approval_level->id,
            relationship_approver_type::TYPE_IDENTIFIER,
            relationship::load_by_idnumber('manager')->id
        );
        $result['control_workflow']['approver'] = $this->generator()->create_assignment_approver($approver_go);

        // Give stage1 (another control) an approver.
        $approver_go = new assignment_approver_generator_object(
            $override_assignments[0]->id,
            $stage2_level1->id,
            relationship_approver_type::TYPE_IDENTIFIER,
            relationship::load_by_idnumber('manager')->id
        );
        $result['test_workflow']['control_approver'] = $this->generator()->create_assignment_approver($approver_go);

        // Give stage 2 some approvers - these are the ones we will be retrieving.

        // The default assignment needs some approvers.
        $approver_go = new assignment_approver_generator_object(
            $workflow_model->get_default_assignment()->id,
            $stage2_level1->id,
            relationship_approver_type::TYPE_IDENTIFIER,
            relationship::load_by_idnumber('manager')->id
        );
        $result['test_workflow']['approver_default_l1'] = $this->generator()->create_assignment_approver($approver_go);
        $approver_go = new assignment_approver_generator_object(
            $workflow_model->get_default_assignment()->id,
            $stage2_level2->id,
            relationship_approver_type::TYPE_IDENTIFIER,
            relationship::load_by_idnumber('manager')->id
        );
        $result['test_workflow']['approver_default_l2'] = $this->generator()->create_assignment_approver($approver_go);

        /**
         * override 0: sub a, only level 1, inherits level 2 from default
         * override 1: sub a prog a, only level 1, inherits level 2 from default
         * override 2: sub a prog b, only level 2, inherits level 1 from override 0
         * override 3: sub b, none, inherits both from default
         * override 4: audience, only level 1, inherits level 2 from default
         * override 5: control sub a prog a, none, inherits both from default
         */
        $approver_go = new assignment_approver_generator_object(
            $override_assignments[0]->id,
            $stage2_level1->id,
            relationship_approver_type::TYPE_IDENTIFIER,
            relationship::load_by_idnumber('manager')->id
        );
        $result['test_workflow']['approver_a0_l1'] = $this->generator()->create_assignment_approver($approver_go);

        $approver_go = new assignment_approver_generator_object(
            $override_assignments[1]->id,
            $stage2_level1->id,
            relationship_approver_type::TYPE_IDENTIFIER,
            relationship::load_by_idnumber('manager')->id
        );
        $result['test_workflow']['approver_a1_l1'] = $this->generator()->create_assignment_approver($approver_go);

        $core_generator = core_generator::instance();
        $user1 = $core_generator->create_user();
        $user2 = $core_generator->create_user();
        $result['test_workflow']['user1'] = new user($user1->id);
        $result['test_workflow']['user2'] = new user($user2->id);
        $approver_go = new assignment_approver_generator_object(
            $override_assignments[2]->id,
            $stage2_level2->id,
            user_approver_type::TYPE_IDENTIFIER,
            $user1->id
        );
        $result['test_workflow']['approver_a2_l2_1'] = $this->generator()->create_assignment_approver($approver_go);
        $approver_go = new assignment_approver_generator_object(
            $override_assignments[2]->id,
            $stage2_level2->id,
            user_approver_type::TYPE_IDENTIFIER,
            $user2->id
        );
        $result['test_workflow']['approver_a2_l2_2'] = $this->generator()->create_assignment_approver($approver_go);

        $approver_go = new assignment_approver_generator_object(
            $override_assignments[4]->id,
            $stage2_level1->id,
            relationship_approver_type::TYPE_IDENTIFIER,
            relationship::load_by_idnumber('manager')->id
        );
        $result['test_workflow']['approver_a4_l1'] = $this->generator()->create_assignment_approver($approver_go);

        // Create correct descendants
        self::create_inherited_assignment_approvers();

        workflow_version::repository()->where('id', $version->id)
            ->update([
                'status' => status::ACTIVE
            ]);
        $version->refresh();

        return $result;
    }

    private static function create_inherited_assignment_approvers(): void {
        $local_approvers = assignment_approver::repository()
            ->where_null('ancestor_id')
            ->where('active', '=', true)
            ->order_by('id')
            ->get();
        $transaction = builder::get_db()->start_delegated_transaction();
        foreach ($local_approvers as $approver_entity) {
            $approver = assignment_approver_model::load_by_entity($approver_entity);
            $approver->create_descendants($transaction);
        }
        $transaction->allow_commit();
    }
}