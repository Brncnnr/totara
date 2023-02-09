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
 * @author David Curry <david.curry@totaralearning.com>
 * @package mod_approval
 */

use core\orm\collection;
use core\orm\query\builder;
use core_phpunit\testcase;
use mod_approval\entity\assignment\assignment_approver as assignment_approver_entity;
use mod_approval\exception\model_exception;
use mod_approval\model\assignment\approver_type\relationship as relationship_approver_type;
use mod_approval\model\assignment\approver_type\user as user_approver_type;
use mod_approval\model\assignment\assignment_approval_level;
use mod_approval\model\assignment\assignment_approver as approver_model;
use mod_approval\model\assignment\assignment as assignment_model;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage_approval_level as approval_level_model;
use mod_approval\testing\approval_workflow_test_setup;
use totara_core\relationship\relationship;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\assignment\assignment_approver
 */
class mod_approval_assignment_approver_model_testcase extends testcase {

    use approval_workflow_test_setup;

    /**
     * Setup and return a basic assignment object.
     *
     * @return array
     */
    private function setup_workflow_assignment(): array {
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $workflow = workflow::load_by_entity($workflow);

        $stage1 = $workflow->latest_version->stages->first();
        $stage2 = $workflow->latest_version->get_next_stage($stage1->id);
        /** @var approval_level_model $approval_level*/
        $approval_level = $stage2->approval_levels->first();

        return [
            assignment_model::load_by_entity($assignment),
            $approval_level
        ];
    }

    /**
     * @covers ::create
     */
    public function test_creation_success(): void {
        $core_generator = \core\testing\generator::instance();
        $user1 = $core_generator->create_user();
        list($assignment, $approval_level) = $this->setup_workflow_assignment();
        $time = time();

        $approver = approver_model::create(
            $assignment,
            $approval_level,
            user_approver_type::TYPE_IDENTIFIER,
            $user1->id
        );

        $this->assertInstanceOf(approver_model::class, $approver);
        $this->assertNotEmpty($approver->id);
        $this->assertEquals($assignment->id, $approver->get_assignment()->id);
        $this->assertEquals($approval_level->id, $approver->get_approval_level()->id);
        $this->assertEquals($user1->id, $approver->identifier);
        $this->assertTrue($approver->active);
        $this->assertGreaterThanOrEqual($time, $approver->created);
        $this->assertLessThanOrEqual($approver->updated, $approver->created);
        $this->assertNull($approver->ancestor_id);
        $this->assertNull($approver->ancestor);
    }

    /**
     * @covers ::create
     */
    public function test_creation_failures(): void {
        try {
            approver_model::create(null, null, null, null);
            $this->fail('TypeError expected');
        } catch (TypeError $ex) {
            $this->assertStringContainsString("mod_approval\model\assignment\assignment, null given", $ex->getMessage());
        }
    }

    /**
     * @covers ::create
     */
    public function test_create_with_inactive_approval_level(): void {
        $core_generator = \core\testing\generator::instance();
        $user1 = $core_generator->create_user();
        list($workflow, , $assignment_entity) = $this->create_workflow_and_assignment();
        $workflow = workflow::load_by_entity($workflow);

        $stage1 = $workflow->latest_version->stages->first();
        $stage2 = $workflow->latest_version->get_next_stage($stage1->id);
        /** @var approval_level_model $approval_level*/
        $approval_level = $stage2->approval_levels->first();
        $approval_level->deactivate();

        $assignment = assignment_model::load_by_entity($assignment_entity);
        try {
            approver_model::create($assignment, $approval_level, user_approver_type::TYPE_IDENTIFIER, $user1->id);
            $this->fail('model_exception expected');
        } catch (model_exception $e) {
            $this->assertEquals('Approval level must be active', $e->debuginfo);
        }
    }

    /**
     * @covers ::create
     */
    public function test_create_with_invalid_approver_type() {
        $core_generator = \core\testing\generator::instance();
        $user1 = $core_generator->create_user();
        list($assignment, $approval_level) = $this->setup_workflow_assignment();

        try {
            approver_model::create($assignment, $approval_level, -1, $user1->id);
            $this->fail('model_exception expected');
        } catch (model_exception $e) {
            $this->assertEquals('Unknown assignment_approver type code', $e->debuginfo);
        }
    }

    /**
     * @covers ::create
     */
    public function test_create_with_not_found_approver_entity() {
        $core_generator = \core\testing\generator::instance();
        $user1 = $core_generator->create_user();
        list($assignment, $approval_level) = $this->setup_workflow_assignment();

        try {
            approver_model::create($assignment, $approval_level, user_approver_type::TYPE_IDENTIFIER, $user1->id + 42);
            $this->fail('model_exception expected');
        } catch (model_exception $e) {
            $this->assertEquals('Invalid assignment_approver identifier', $e->debuginfo);
        }
    }

    /**
     * @covers ::create
     * @covers ::load_by_type_identifier_and_assignment_approver_level
     */
    public function test_create_with_same_approver_entity() {
        $core_generator = \core\testing\generator::instance();
        $user1 = $core_generator->create_user();
        list($assignment, $approval_level) = $this->setup_workflow_assignment();

        $original = approver_model::create($assignment, $approval_level, user_approver_type::TYPE_IDENTIFIER, $user1->id);
        try {
            approver_model::create($assignment, $approval_level, user_approver_type::TYPE_IDENTIFIER, $user1->id);
            $this->fail('model_exception expected');
        } catch (model_exception $e) {
            $this->assertEquals('Matching active approver already exists for this assignment and approval level', $e->debuginfo);
        }

        // Test re-create when inactive.
        $original->deactivate();
        $this->assertFalse($original->active);
        $redux = approver_model::create($assignment, $approval_level, user_approver_type::TYPE_IDENTIFIER, $user1->id);
        $this->assertEquals($original->id, $redux->id);
        $this->assertTrue($redux->active);
    }

    /**
     * Test the activate and deactivate functions of the approver model
     *
     * @covers ::activate
     * @covers ::deactivate
     */
    public function test_activate(): void {
        $relationship = relationship::load_by_idnumber('manager');
        list($assignment, $approval_level) = $this->setup_workflow_assignment();
        $user = $this->getDataGenerator()->create_user();
        $context = $assignment->get_context();
        $approver_role_id = builder::table('role')->where('shortname', 'approvalworkflowapprover')->value('id');

        $approver = approver_model::create(
            $assignment,
            $approval_level,
            relationship_approver_type::TYPE_IDENTIFIER,
            $relationship->id
        );
        $approver2 = approver_model::create(
            $assignment,
            $approval_level,
            user_approver_type::TYPE_IDENTIFIER,
            $user->id
        );

        $this->assertTrue($approver->active);
        $approver->deactivate();
        $this->assertFalse($approver->active);
        $approver->activate();
        $this->assertTrue($approver->active);

        $this->assertTrue($approver2->active);
        $this->assertTrue( user_has_role_assignment($user->id, $approver_role_id, $context->id));
        $approver2->deactivate();
        $this->assertFalse($approver2->active);
        $this->assertFalse( user_has_role_assignment($user->id, $approver_role_id, $context->id));
        $approver2->activate();
        $this->assertTrue($approver2->active);
        $this->assertTrue( user_has_role_assignment($user->id, $approver_role_id, $context->id));
    }

    public function test_create_where_used_to_be_inherited() {
        list($workflow_entity, $framework, $assignment_entity, $overrides) = $this->create_workflow_and_assignment('Testing', true);
        $workflow = workflow::load_by_entity($workflow_entity);
        $assignment = assignment_model::load_by_entity($assignment_entity);
        $override1 = assignment_model::load_by_entity($overrides[0]);
        $stage1 = $workflow->latest_version->stages->first();
        $stage2 = $workflow->latest_version->get_next_stage($stage1->id);
        $approval_level = $stage2->approval_levels->first();

        // First create approvers on default assignment.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $approver1 = approver_model::create($assignment, $approval_level, user_approver_type::get_code(), $user1->id);
        $approver2 = approver_model::create($assignment, $approval_level, user_approver_type::get_code(), $user2->id);
        $this->assertTrue($approver1->active);

        // Create assignment approval level for override + approval_level.
        $override1_assignment_approval_level = new assignment_approval_level($override1, $approval_level);
        $this->assertCount(0, $override1_assignment_approval_level->get_approvers());
        $this->assertCount(2, $override1_assignment_approval_level->get_approvers_with_inheritance());

        // Deactivate approvers on default assignment.
        $approver1->deactivate();
        $approver2->deactivate();
        $this->assertCount(0, $override1_assignment_approval_level->get_approvers());
        $this->assertCount(0, $override1_assignment_approval_level->get_approvers_with_inheritance());

        // Create same approver but on override assignment.
        $new_approver1 = approver_model::create($override1, $approval_level, user_approver_type::get_code(), $user1->id);
        $this->assertTrue($new_approver1->active);
        $this->assertCount(1, $override1_assignment_approval_level->get_approvers());
        $this->assertCount(1, $override1_assignment_approval_level->get_approvers_with_inheritance());

        // Ensure that there are no active approvers for this level on the default assignment.
        $default_assignment_approval_level = new assignment_approval_level($assignment, $approval_level);
        $this->assertCount(0, $default_assignment_approval_level->get_approvers());
        $this->assertCount(0, $default_assignment_approval_level->get_approvers_with_inheritance());
    }

    /**
     * @covers ::refresh
     */
    public function test_refresh(): void {
        $core_generator = \core\testing\generator::instance();
        $user1 = $core_generator->create_user();
        $relationship = relationship::load_by_idnumber('manager');
        list($assignment, $approval_level) = $this->setup_workflow_assignment();

        $approver = approver_model::create(
            $assignment,
            $approval_level,
            relationship_approver_type::TYPE_IDENTIFIER,
            $relationship->id
        );
        $this->assertEquals(relationship_approver_type::TYPE_IDENTIFIER, $approver->type);
        builder::table(assignment_approver_entity::TABLE)
            ->update(['type' => user_approver_type::TYPE_IDENTIFIER, 'identifier' => $user1->id]);
        $approver->refresh();
        $this->assertEquals(user_approver_type::TYPE_IDENTIFIER, $approver->type);
    }

    /**
     * @covers ::delete
     */
    public function test_delete(): void {
        $core_generator = \core\testing\generator::instance();
        $user = $core_generator->create_user();
        $relationship = relationship::load_by_idnumber('manager');
        list($assignment, $approval_level) = $this->setup_workflow_assignment();
        $context = $assignment->get_context();
        $approver_role_id = builder::table('role')->where('shortname', 'approvalworkflowapprover')->value('id');

        $approver = approver_model::create(
            $assignment,
            $approval_level,
            relationship_approver_type::TYPE_IDENTIFIER,
            $relationship->id
        );

        $this->assertNotEmpty($approver->id);
        $approver->delete();
        $this->assertEmpty($approver->id);

        // Create a user approver.
        $approver = approver_model::create(
            $assignment,
            $approval_level,
            user_approver_type::TYPE_IDENTIFIER,
            $user->id
        );

        $this->assertNotEmpty($approver->id);
        // Check user role is created for this kind of approver.
        $this->assertTrue(user_has_role_assignment($user->id, $approver_role_id, $context->id));

        // Delete the approver.
        $approver->delete();

        $this->assertEmpty($approver->id);
        // Check user role is removed after deletion.
        $this->assertFalse(user_has_role_assignment($user->id, $approver_role_id, $context->id));
    }

    /**
     * @covers ::delete
     */
    public function test_delete_with_override(): void {
        $core_generator = \core\testing\generator::instance();
        $user1 = $core_generator->create_user();
        $user2 = $core_generator->create_user();

        // Create a workflow with assignment overrides
        list($workflow_entity, $framework, $assignment_entity, $override_entities) = $this->create_workflow_and_assignment('Testing', true);
        $workflow = workflow::load_by_entity($workflow_entity);
        $stage1 = $workflow->latest_version->stages->first();
        $stage2 = $workflow->latest_version->get_next_stage($stage1->id);
        $assignment = assignment_model::load_by_entity($assignment_entity);
        $approval_level = $stage2->approval_levels->first();
        $overrides = new collection();
        foreach ($override_entities as $override_entity) {
            $overrides->append(assignment_model::load_by_entity($override_entity));
        }

        /**
         * $framework->agency - approver
         * $framework->agency->subagency_a - newapprover
         * $framework->agency->subagency_a->program_a - (newapprover)
         * $framework->agency->subagency_a->program_b - (newapprover)
         * $framework->agency->subagency_b - (approver)
         */
        $approver = approver_model::create(
            $assignment,
            $approval_level,
            user_approver_type::TYPE_IDENTIFIER,
            $user1->id
        );
        $newapprover = approver_model::create(
            $overrides->find('assignment_identifier', $framework->agency->subagency_a->id),
            $approval_level,
            user_approver_type::TYPE_IDENTIFIER,
            $user2->id
        );

        // Storing approvers ID to be used later.
        $approverid = $approver->id;
        $newapproverid = $newapprover->id;

        // Check descendants for approvers.
        $this->assertCount(1, $approver->descendants);
        $this->assertCount(2, $newapprover->descendants);

        $approver->delete();

        // Check approver's descendants has been deleted as well.
        $hasdescendantsapprover = builder::table('approval_approver')->where('ancestor_id', $approverid)->exists();
        $hasdescendantsnewapprover = builder::table('approval_approver')->where('ancestor_id', $newapproverid)->exists();
        $this->assertFalse($hasdescendantsapprover);
        $this->assertTrue($hasdescendantsnewapprover);

        // Check user role are removed after deletion.
        $context = $assignment->get_context();
        $approver_role_id = builder::table('role')->where('shortname', 'approvalworkflowapprover')->value('id');
        $this->assertFalse(user_has_role_assignment($user1->id, $approver_role_id, $context->id));
        $this->assertFalse(user_has_role_assignment($user2->id, $approver_role_id, $context->id));
    }

    /**
     * @covers ::create
     * @covers ::create_descendants
     * @covers ::get_descendants
     * @covers ::get_ancestor
     */
    public function test_create_descendants(): void {
        $core_generator = \core\testing\generator::instance();
        $user1 = $core_generator->create_user();
        $user2 = $core_generator->create_user();

        // Create a workflow with assignment overrides
        list($workflow_entity, $framework, $assignment_entity, $override_entities) = $this->create_workflow_and_assignment('Testing', true);
        $workflow = workflow::load_by_entity($workflow_entity);

        $stage1 = $workflow->latest_version->stages->first();
        $stage2 = $workflow->latest_version->get_next_stage($stage1->id);
        /** @var approval_level_model $approval_level*/
        $approval_level = $stage2->approval_levels->first();

        $assignment = assignment_model::load_by_entity($assignment_entity);
        $overrides = new collection();
        foreach ($override_entities as $override_entity) {
            $overrides->append(assignment_model::load_by_entity($override_entity));
        }

        /**
         * $framework->agency - approver
         * $framework->agency->subagency_a - (approver)
         * $framework->agency->subagency_a->program_a - (approver)
         * $framework->agency->subagency_a->program_b - (approver)
         * $framework->agency->subagency_b - (approver)
         */
        $approver = approver_model::create(
            $assignment,
            $approval_level,
            user_approver_type::TYPE_IDENTIFIER,
            $user1->id
        );

        $this->assertNotEmpty($approver->id);
        $this->assertNull($approver->ancestor_id);
        $this->assertNull($approver->ancestor);
        $this->assertCount(4, $approver->descendants);
        foreach ($approver->descendants as $descendant) {
            $this->assertNotNull($descendant->ancestor);
            $this->assertEquals($approver->id, $descendant->ancestor_id);
            $this->assertNotEquals($approver->approval_id, $descendant->approval_id);
            $this->assertEquals($approver->workflow_stage_approval_level_id, $descendant->workflow_stage_approval_level_id);
            $this->assertEquals($approver->type, $descendant->type);
            $this->assertEquals($approver->identifier, $descendant->identifier);
            $this->assertTrue($descendant->active);
        }

        /**
         * $framework->agency - approver
         * $framework->agency->subagency_a - neapprover
         * $framework->agency->subagency_a->program_a - (neapprover)
         * $framework->agency->subagency_a->program_b - (neapprover)
         * $framework->agency->subagency_b - (approver)
         */
        $newapprover = approver_model::create(
            $overrides->find('assignment_identifier', $framework->agency->subagency_a->id),
            $approval_level,
            user_approver_type::TYPE_IDENTIFIER,
            $user2->id
        );

        $this->assertNotEmpty($newapprover->id);
        $this->assertNull($newapprover->ancestor_id);
        $this->assertNull($newapprover->ancestor);
        $this->assertCount(2, $newapprover->descendants);
        foreach ($newapprover->descendants as $descendant) {
            $this->assertNotNull($descendant->ancestor);
            $this->assertEquals($newapprover->id, $descendant->ancestor_id);
            $this->assertNotEquals($newapprover->approval_id, $descendant->approval_id);
            $this->assertEquals($newapprover->workflow_stage_approval_level_id, $descendant->workflow_stage_approval_level_id);
            $this->assertEquals($newapprover->type, $descendant->type);
            $this->assertEquals($newapprover->identifier, $descendant->identifier);
            $this->assertTrue($newapprover->active);
        }

        $approver->refresh(true);
        $this->assertCount(1, $approver->descendants);
    }

    /**
     * @covers ::activate
     * @covers ::create_descendants
     * @covers ::deactivate
     * @covers ::deactivate_descendant_approvers
     */
    public function test_deactivate_activate_descendants(): void {
        $core_generator = \core\testing\generator::instance();
        $user1 = $core_generator->create_user();
        $user2 = $core_generator->create_user();

        // Create a workflow with assignment overrides
        list($workflow_entity, $framework, $assignment_entity, $override_entities) = $this->create_workflow_and_assignment('Testing', true);
        $workflow = workflow::load_by_entity($workflow_entity);

        $stage1 = $workflow->latest_version->stages->first();
        $stage2 = $workflow->latest_version->get_next_stage($stage1->id);
        /** @var approval_level_model $approval_level*/
        $approval_level = $stage2->approval_levels->first();

        $assignment = assignment_model::load_by_entity($assignment_entity);
        $overrides = new collection();
        foreach ($override_entities as $override_entity) {
            $overrides->append(assignment_model::load_by_entity($override_entity));
        }

        /**
         * $framework->agency - approver
         * $framework->agency->subagency_a - neapprover
         * $framework->agency->subagency_a->program_a - (neapprover)
         * $framework->agency->subagency_a->program_b - (neapprover)
         * $framework->agency->subagency_b - (approver)
         */
        $approver = approver_model::create(
            $assignment,
            $approval_level,
            user_approver_type::TYPE_IDENTIFIER,
            $user1->id
        );
        $newapprover = approver_model::create(
            $overrides->find('assignment_identifier', $framework->agency->subagency_a->id),
            $approval_level,
            user_approver_type::TYPE_IDENTIFIER,
            $user2->id
        );
        $this->assertCount(1, $approver->descendants);
        $this->assertCount(2, $newapprover->descendants);

        $approver->deactivate();
        $approver->refresh(true);
        $newapprover->refresh(true);
        $this->assertCount(0, $approver->descendants);
        $this->assertCount(2, $newapprover->descendants);

        $approver->activate();
        $approver->refresh(true);
        $newapprover->refresh(true);
        $this->assertCount(1, $approver->descendants);
        $this->assertCount(2, $newapprover->descendants);
    }
}
