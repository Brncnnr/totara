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

use core\orm\collection;
use core_phpunit\testcase;
use mod_approval\entity\assignment\assignment_approver;
use mod_approval\model\assignment\approver_type\relationship as relationship_approver_type;
use mod_approval\model\assignment\approver_type\user;
use mod_approval\model\assignment\assignment as assignment_model;
use mod_approval\model\assignment\assignment_approver as assignment_approver_model;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage_approval_level as approval_level_model;
use mod_approval\testing\approval_workflow_test_setup;
use totara_core\entity\relationship;

require_once(__DIR__ . '/../db/upgradelib.php');

/**
 * @group approval_workflow
 */
class mod_approval_upgradelib_testcase extends testcase {

    use approval_workflow_test_setup;

    private function create_approver_via_entity(assignment_model $assignment, approval_level_model $level, int $type, int $identifier): assignment_approver_model {
        // Need to fake this so that descendant approvers aren't created.
        $entity = new assignment_approver();
        $entity->approval_id = $assignment->id;
        $entity->workflow_stage_approval_level_id = $level->id;
        $entity->type = $type;
        $entity->identifier = $identifier;
        $entity->active = true;
        $entity->ancestor_id = null;
        $entity->save();
        return assignment_approver_model::load_by_entity($entity);
    }

    public function test_create_inherited_assignment_approvers() {
        $this->setAdminUser();
        $core_generator = \core\testing\generator::instance();
        $user1 = $core_generator->create_user();
        $user2 = $core_generator->create_user();
        $user3 = $core_generator->create_user();
        $manager_relationship = relationship::repository()->where('idnumber', '=', 'manager')->one();

        // Create a workflow with assignment overrides
        list($workflow_entity, $framework, $assignment_entity, $override_entities) = $this->create_workflow_and_assignment('Testing', true, false);
        $assignment = assignment_model::load_by_entity($assignment_entity);
        $workflow = workflow::load_by_entity($workflow_entity);
        $stage_1 = $workflow->latest_version->stages->first();
        $stage_2 = $workflow->latest_version->get_next_stage($stage_1->id);
        $level1 = $stage_2->approval_levels->first();
        $overrides = new collection();
        foreach ($override_entities as $override_entity) {
            $overrides->append(assignment_model::load_by_entity($override_entity));
        }

        // Add another two levels
        $workflow_stage = $level1->workflow_stage;
        $level2 = $workflow_stage->add_approval_level('Level 2');
        $level3 = $workflow_stage->add_approval_level( 'Level 3');

        // Create a manager approver for level 1
        $this->create_approver_via_entity($assignment, $level1, relationship_approver_type::TYPE_IDENTIFIER, $manager_relationship->id);

        /**
         * Level 2:
         * $framework->agency - user1
         * $framework->agency->subagency_a - (user1)
         * $framework->agency->subagency_a->program_a - user2
         * $framework->agency->subagency_a->program_b - (user1)
         * $framework->agency->subagency_b - user2
         */
        $this->create_approver_via_entity($assignment, $level2, user::TYPE_IDENTIFIER, $user1->id);
        $this->create_approver_via_entity(
            $overrides->find('assignment_identifier', $framework->agency->subagency_a->program_a->id),
            $level2,
            user::TYPE_IDENTIFIER,
            $user2->id
        );
        $this->create_approver_via_entity(
            $overrides->find('assignment_identifier', $framework->agency->subagency_b->id),
            $level2,
            user::TYPE_IDENTIFIER,
            $user2->id
        );

        // Create a user approver for level 3
        $this->create_approver_via_entity($assignment, $level3, user::TYPE_IDENTIFIER, $user3->id);

        // Ok, we should have exactly five approvers.
        $all_approvers = assignment_approver::repository()->get();
        $this->assertCount(5, $all_approvers);

        // Now upgrade.
        mod_approval_upgrade_create_inherited_assignment_approvers();

        // Now we should have 15.
        $all_approvers = assignment_approver::repository()->get();
        $this->assertCount(15, $all_approvers);

        $level1_approvers =  assignment_approver::repository()
            ->where('workflow_stage_approval_level_id', '=', $level1->id)
            ->get();
        $this->assertCount(5, $level1_approvers);

        $level2_approvers =  assignment_approver::repository()
            ->where('workflow_stage_approval_level_id', '=', $level2->id)
            ->get();
        $this->assertCount(5, $level2_approvers);

        // Check the level 2 approvers in detail.
        $agency = $level2_approvers->find('approval_id', $assignment->id);
        $this->assertEquals($user1->id, $agency->identifier);
        $this->assertNull($agency->ancestor_id);

        $sub_agency_a = $level2_approvers->find('approval_id', $overrides->find('assignment_identifier', $framework->agency->subagency_a->id)->id);
        $this->assertEquals($user1->id, $sub_agency_a->identifier);
        $this->assertEquals($agency->id, $sub_agency_a->ancestor_id);

        $program_a = $level2_approvers->find('approval_id', $overrides->find('assignment_identifier', $framework->agency->subagency_a->program_a->id)->id);
        $this->assertEquals($user2->id, $program_a->identifier);
        $this->assertNull($program_a->ancestor_id);

        $program_b = $level2_approvers->find('approval_id', $overrides->find('assignment_identifier', $framework->agency->subagency_a->program_b->id)->id);
        $this->assertEquals($user1->id, $program_b->identifier);
        $this->assertEquals($agency->id, $program_b->ancestor_id);

        $sub_agency_b = $level2_approvers->find('approval_id', $overrides->find('assignment_identifier', $framework->agency->subagency_b->id)->id);
        $this->assertEquals($user2->id, $sub_agency_b->identifier);
        $this->assertNull($sub_agency_b->ancestor_id);

        $level3_approvers =  assignment_approver::repository()
            ->where('workflow_stage_approval_level_id', '=', $level3->id)
            ->get();
        $this->assertCount(5, $level3_approvers);
    }
}
