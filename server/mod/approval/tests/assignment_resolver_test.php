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

use core\entity\user;
use core_phpunit\testcase;
use mod_approval\model\assignment\assignment_resolver;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\status;
use mod_approval\model\workflow\workflow;
use mod_approval\testing\approval_workflow_test_setup;
use mod_approval\testing\assignment_generator_object;
use mod_approval\webapi\schema_object\new_application_menu_item;

/**
 * @coversDefaultClass \mod_approval\model\assignment\assignment_resolver
 *
 * @group approval_workflow
 */
class mod_approval_assignment_resolver_testcase extends testcase {

    use approval_workflow_test_setup;

    /**
     * Gets the generator instance
     *
     * @return \mod_approval\testing\generator
     */
    protected function generator(): \mod_approval\testing\generator {
        return \mod_approval\testing\generator::instance();
    }

    /**
     * Gets the cohort generator instance
     *
     * @return \totara_cohort\testing\generator
     */
    protected function cohort_generator(): \totara_cohort\testing\generator {
        return \totara_cohort\testing\generator::instance();
    }

    /**
     * @covers ::resolve
     * @covers ::resolve_hierarchical_assignments
     * @covers ::get_menu_items
     */
    public function test_single_sub_organisation_job_assignment() {
        $this->setAdminUser();

        // The framework created is a simple organisation hierarchy.
        /**
         * $framework->agency = $agency;
         * $framework->agency->subagency_a = $subagency_a;
         * $framework->agency->subagency_a->program_a = $program_a;
         * $framework->agency->subagency_a->program_b = $program_b;
         * $framework->agency->subagency_b = $subagency_b;
         */

        // Generate a workflow
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();

        // Add a user and assign to program_a
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity = new user($user1->id);

        $ja = \totara_job\job_assignment::create([
            'userid' => $user1->id,
            'idnumber' => '001',
            'organisationid' => $framework->agency->subagency_a->program_a->id,
            'fullname' => 'Test Job Assignment'
        ]);

        $creator = clone $user_entity;
        $resolver = new assignment_resolver($user_entity, $creator);
        $resolver->resolve();
        $items = $resolver->get_menu_items();
        $this->assertCount(1, $items);

        $menu_item = $items->first();
        $this->assertInstanceOf(new_application_menu_item::class, $menu_item);
        $this->assertEquals($assignment->id, $menu_item->assignment_id);
        $this->assertEquals($workflow->workflow_type->name, $menu_item->workflow_type);
        $this->assertEquals($ja->fullname, $menu_item->job_assignment);
        $this->assertEquals($ja->id, $menu_item->job_assignment_id);
    }

    /**
     * @covers ::resolve
     * @covers ::resolve_hierarchical_assignments
     * @covers ::get_menu_items
     */
    public function test_multiple_job_assignmets_to_single_sub_organisation() {
        $this->setAdminUser();

        // Generate a simple organisation hierarchy
        $framework = $this->generate_org_hierarchy();

        // Generate a workflow
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();

        // Add a user and assign to program_a
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity = new user($user1->id);

        $ja1 = \totara_job\job_assignment::create([
            'userid' => $user1->id,
            'idnumber' => '001',
            'organisationid' => $framework->agency->subagency_a->program_a->id,
            'fullname' => 'Test Job Assignment 1'
        ]);
        $ja2 = \totara_job\job_assignment::create([
            'userid' => $user1->id,
            'idnumber' => '002',
            'organisationid' => $framework->agency->subagency_a->program_a->id,
            'fullname' => 'Test Job Assignment 2'
        ]);

        $creator = clone $user_entity;
        $resolver = new assignment_resolver($user_entity, $creator);
        $resolver->resolve();
        $items = $resolver->get_menu_items();
        $this->assertCount(2, $items);

        $menu_item = $items->first();
        $this->assertInstanceOf(new_application_menu_item::class, $menu_item);
        $this->assertEquals($assignment->id, $menu_item->assignment_id);
        $this->assertEquals($workflow->workflow_type->name, $menu_item->workflow_type);
        $this->assertEquals($ja1->fullname, $menu_item->job_assignment);
        $this->assertEquals($ja1->id, $menu_item->job_assignment_id);

        $items->next();
        $menu_item = $items->current();
        $this->assertInstanceOf(new_application_menu_item::class, $menu_item);
        $this->assertEquals($assignment->id, $menu_item->assignment_id);
        $this->assertEquals($workflow->workflow_type->name, $menu_item->workflow_type);
        $this->assertEquals($ja2->fullname, $menu_item->job_assignment);
        $this->assertEquals($ja2->id, $menu_item->job_assignment_id);
    }

    /**
     * @covers ::resolve_hierarchical_assignments
     */
    public function test_single_sub_organisation_job_assignment_with_multiple_workflow_assignments() {
        $this->setAdminUser();

        // Generate a simple organisation hierarchy
        $framework = $this->generate_org_hierarchy();
        /**
         * $framework->agency = $agency;
         * $framework->agency->subagency_a = $subagency_a;
         * $framework->agency->subagency_a->program_a = $program_a;
         * $framework->agency->subagency_a->program_b = $program_b;
         * $framework->agency->subagency_b = $subagency_b;
         */

        // Generate a workflow
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();

        $assignment_go = new assignment_generator_object($workflow->course_id, assignment_type\organisation::get_code(), $framework->agency->subagency_a->id);
        $subagency_assignment = $this->generator()->create_assignment($assignment_go);

        // Add a user and assign to program_a
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity = new user($user1->id);

        $ja = \totara_job\job_assignment::create([
            'userid' => $user1->id,
            'idnumber' => '001',
            'organisationid' => $framework->agency->subagency_a->program_a->id,
            'fullname' => 'Test Job Assignment'
        ]);

        $creator = clone $user_entity;
        $resolver = new assignment_resolver($user_entity, $creator);
        $resolver->resolve();
        $items = $resolver->get_menu_items();
        $this->assertCount(1, $items);

        $menu_item = $items->first();
        $this->assertInstanceOf(new_application_menu_item::class, $menu_item);
        $this->assertEquals($subagency_assignment->id, $menu_item->assignment_id);
        $this->assertEquals($workflow->workflow_type->name, $menu_item->workflow_type);
        $this->assertEquals($ja->fullname, $menu_item->job_assignment);
        $this->assertEquals($ja->id, $menu_item->job_assignment_id);
    }

    public function test_cohort_assignment_resolution() {
        $this->setAdminUser();

        // Generate a workflow
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();

        // Generate some cohorts
        $cohort1 = $this->cohort_generator()->create_cohort();
        $cohort2 = $this->cohort_generator()->create_cohort();
        $cohort3 = $this->cohort_generator()->create_cohort();

        // Create assignments for cohorts 2 and 3
        $assignment_go = new assignment_generator_object($workflow->course_id, assignment_type\cohort::get_code(), $cohort2->id);
        $assignment_go->status = status::ACTIVE;
        $assignment2 = $this->generator()->create_assignment($assignment_go);
        $assignment_go = new assignment_generator_object($workflow->course_id, assignment_type\cohort::get_code(), $cohort3->id);
        $assignment3 = $this->generator()->create_assignment($assignment_go);

        // Add a user and assign to cohort2
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity = new user($user1->id);
        $this->cohort_generator()->cohort_assign_users($cohort2->id, [$user1->id]);

        $creator = clone $user_entity;
        $resolver = new assignment_resolver($user_entity, $creator);
        $resolver->resolve();
        $items = $resolver->get_menu_items();
        $this->assertCount(1, $items);

        $menu_item = $items->first();
        $this->assertInstanceOf(new_application_menu_item::class, $menu_item);
        $this->assertEquals($assignment2->id, $menu_item->assignment_id);
        $this->assertEquals($workflow->workflow_type->name, $menu_item->workflow_type);
        $this->assertNull($menu_item->job_assignment);
        $this->assertNull($menu_item->job_assignment_id);

        // Add user to cohort3 and re-resolve, there should still only be one, for cohort 2.
        $this->cohort_generator()->cohort_assign_users($cohort3->id, [$user1->id]);
        $creator = clone $user_entity;
        $resolver2 = new assignment_resolver($user_entity, $creator);
        $resolver2->resolve();
        $assignments = $resolver2->get_assignments();
        $this->assertCount(1, $assignments);
        $this->assertEquals($assignment2->id, $assignments->first()->id);
    }

    public function test_cohort_assignment_resolution_with_job_assignments() {
        $this->setAdminUser();

        // Generate a workflow
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();

        // Generate some cohorts
        $cohort1 = $this->cohort_generator()->create_cohort();
        $cohort2 = $this->cohort_generator()->create_cohort();
        $cohort3 = $this->cohort_generator()->create_cohort();

        // Create assignments for cohorts 2 and 3
        $assignment_go = new assignment_generator_object($workflow->course_id, assignment_type\cohort::get_code(), $cohort2->id);
        $assignment_go->status = status::ACTIVE;
        $assignment2 = $this->generator()->create_assignment($assignment_go);
        $assignment_go = new assignment_generator_object($workflow->course_id, assignment_type\cohort::get_code(), $cohort3->id);
        $assignment3 = $this->generator()->create_assignment($assignment_go);

        // Add a user and assign to cohort2
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity = new user($user1->id);
        $this->cohort_generator()->cohort_assign_users($cohort2->id, [$user1->id]);

        // Create some job assignments
        $ja1 = \totara_job\job_assignment::create([
            'userid' => $user1->id,
            'idnumber' => '001',
            'fullname' => 'Test Job Assignment 1'
        ]);
        $ja2 = \totara_job\job_assignment::create([
            'userid' => $user1->id,
            'idnumber' => '002',
            'fullname' => 'Test Job Assignment 2'
        ]);

        $creator = clone $user_entity;
        $resolver = new assignment_resolver($user_entity, $creator);
        $resolver->resolve();
        $items = $resolver->get_menu_items();
        $this->assertCount(2, $items);

        $menu_item = $items->first();
        $this->assertInstanceOf(new_application_menu_item::class, $menu_item);
        $this->assertEquals($assignment2->id, $menu_item->assignment_id);
        $this->assertEquals($workflow->workflow_type->name, $menu_item->workflow_type);
        $this->assertEquals($ja1->fullname, $menu_item->job_assignment);
        $this->assertEquals($ja1->id, $menu_item->job_assignment_id);

        $items->next();
        $menu_item = $items->current();
        $this->assertInstanceOf(new_application_menu_item::class, $menu_item);
        $this->assertEquals($assignment2->id, $menu_item->assignment_id);
        $this->assertEquals($workflow->workflow_type->name, $menu_item->workflow_type);
        $this->assertEquals($ja2->fullname, $menu_item->job_assignment);
        $this->assertEquals($ja2->id, $menu_item->job_assignment_id);

        // Add user to cohort3 and re-resolve, there should still only be one, for cohort 2
        $this->cohort_generator()->cohort_assign_users($cohort3->id, [$user1->id]);
        $creator = clone $user_entity;
        $resolver2 = new assignment_resolver($user_entity, $creator);
        $resolver2->resolve();
        $assignments = $resolver2->get_assignments();
        $this->assertCount(1, $assignments);
        $this->assertEquals($assignment2->id, $assignments->first()->id);
        $items = $resolver2->get_menu_items();
        $this->assertCount(2, $items);
        $this->assertEquals($assignment2->id, $items->first()->assignment_id);
        $this->assertEquals($ja1->id, $items->first()->job_assignment_id);
        $this->assertEquals($assignment2->id, $items->last()->assignment_id);
        $this->assertEquals($ja2->id, $items->last()->job_assignment_id);
    }

    /**
     * @covers ::resolve_hierarchical_assignments
     */
    public function test_resolve_hierarchical_assignment_from_multiple_workflows() {
        $this->setAdminUser();

        // The framework created is a simple organisation hierarchy.
        /**
         * $framework->agency = $agency;
         * $framework->agency->subagency_a = $subagency_a;
         * $framework->agency->subagency_a->program_a = $program_a;
         * $framework->agency->subagency_a->program_b = $program_b;
         * $framework->agency->subagency_b = $subagency_b;
         */

        // Generate a workflow
        list($workflow1, $framework1, $assignment1) = $this->create_workflow_and_assignment();

        // Generate two more
        list($workflow2, $framework2, $assignment2) = $this->create_workflow_and_assignment_on_framework($framework1);
        list($workflow3, $framework3, $assignment3) = $this->create_workflow_and_assignment_on_framework($framework1);

        // Check some assumptions about the generator
        $this->assertEquals($workflow2->workflow_type->name, $workflow3->workflow_type->name);
        $this->assertNotEquals($assignment2->id, $assignment3->id);
        $this->assertEquals($framework2->agency->subagency_a->program_a->id, $framework3->agency->subagency_a->program_a->id);

        // Archive workflow1 - it is now the oldest, but archived so unusable for new applications
        $workflow1_model = workflow::load_by_entity($workflow1);
        $workflow1_model->archive();

        // Add a user and assign to program_a
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity = new user($user1->id);

        $ja1 = \totara_job\job_assignment::create([
            'userid' => $user1->id,
            'idnumber' => '001',
            'organisationid' => $framework1->agency->subagency_a->program_a->id,
            'fullname' => 'Test Job Assignment'
        ]);

        $creator = clone $user_entity;
        $resolver = new assignment_resolver($user_entity, $creator);
        $resolver->resolve();
        $items = $resolver->get_menu_items();
        $this->assertCount(1, $items);

        // Check that resolver has picked the oldest active workflow.
        $menu_item = $items->first();
        $this->assertInstanceOf(new_application_menu_item::class, $menu_item);
        $this->assertEquals($assignment2->id, $menu_item->assignment_id);

        // Unarchive workflow1 and try again
        $workflow1_model->unarchive();
        $resolver = new assignment_resolver($user_entity, $creator);
        $resolver->resolve();
        $items = $resolver->get_menu_items();
        $this->assertCount(1, $items);
        $menu_item = $items->first();
        $this->assertEquals($assignment1->id, $menu_item->assignment_id);

        // Make workflow1 a draft and try again
        $version = $workflow1->versions->first();
        $version->status = status::DRAFT;
        $version->save();
        $resolver = new assignment_resolver($user_entity, $creator);
        $resolver->resolve();
        $items = $resolver->get_menu_items();
        $this->assertCount(1, $items);
        $menu_item = $items->first();
        $this->assertEquals($assignment2->id, $menu_item->assignment_id);

        // Create another workflow of same type, with a default assignment in a different framework
        list($workflow4, $framework4, $assignment4) = $this->create_workflow_and_assignment();
        $ja4 = \totara_job\job_assignment::create([
            'userid' => $user1->id,
            'idnumber' => '004',
            'organisationid' => $framework4->agency->subagency_a->program_a->id,
            'fullname' => 'Test Job Assignment 2'
        ]);

        // Now there should be two menu items, one for each job assignment
        $resolver = new assignment_resolver($user_entity, $creator);
        $resolver->resolve();
        $items = $resolver->get_menu_items();
        $this->assertCount(2, $items);
        $menu_item1 = $items->first();
        $this->assertEquals($assignment2->id, $menu_item1->assignment_id);
        $menu_item2 = $items->last();
        $this->assertEquals($assignment4->id, $menu_item2->assignment_id);
    }

    public function test_cohort_assignment_resolution_with_multiple_workflows() {
        // Generate three workflows
        list($workflow1, $framework1, $assignment1) = $this->create_workflow_and_assignment();
        list($workflow2, $framework2, $assignment2) = $this->create_workflow_and_assignment();
        list($workflow3, $framework3, $assignment3) = $this->create_workflow_and_assignment();

        // Generate some cohorts
        $cohort1 = $this->cohort_generator()->create_cohort();
        $cohort2 = $this->cohort_generator()->create_cohort();
        $cohort3 = $this->cohort_generator()->create_cohort();

        // Create assignments for all cohorts
        $assignment_go = new assignment_generator_object($workflow1->course_id, assignment_type\cohort::get_code(), $cohort1->id);
        $assignment_go->status = status::ACTIVE;
        $assignment11 = $this->generator()->create_assignment($assignment_go);
        $assignment_go->course = $workflow2->course_id;
        $assignment_go->assignment_identifier = $cohort2->id;
        $assignment22 = $this->generator()->create_assignment($assignment_go);
        $assignment_go->course = $workflow3->course_id;
        $assignment_go->assignment_identifier = $cohort3->id;
        $assignment33 = $this->generator()->create_assignment($assignment_go);

        // Add a user and assign to cohorts1-3
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity = new user($user1->id);
        $this->cohort_generator()->cohort_assign_users($cohort1->id, [$user1->id]);
        $this->cohort_generator()->cohort_assign_users($cohort2->id, [$user1->id]);
        $this->cohort_generator()->cohort_assign_users($cohort3->id, [$user1->id]);

        // Should get first (oldest) matching assignment
        $creator = clone $user_entity;
        $resolver = new assignment_resolver($user_entity, $creator);
        $resolver->resolve();
        $items = $resolver->get_menu_items();
        $this->assertCount(1, $items);
        $menu_item = $items->first();
        $this->assertInstanceOf(new_application_menu_item::class, $menu_item);
        $this->assertEquals($assignment11->id, $menu_item->assignment_id);

        // Archive workflow1; workflow2 is now the oldest
        $workflow1_model = workflow::load_by_entity($workflow1);
        $workflow1_model->archive();
        $resolver = new assignment_resolver($user_entity, $creator);
        $resolver->resolve();
        $items = $resolver->get_menu_items();
        $this->assertCount(1, $items);
        $menu_item = $items->first();
        $this->assertInstanceOf(new_application_menu_item::class, $menu_item);
        $this->assertEquals($assignment22->id, $menu_item->assignment_id);

        // Make workflow1 a draft and try again
        $version = $workflow1->versions->first();
        $version->status = status::DRAFT;
        $version->save();
        $resolver = new assignment_resolver($user_entity, $creator);
        $resolver->resolve();
        $items = $resolver->get_menu_items();
        $this->assertCount(1, $items);
        $menu_item = $items->first();
        $this->assertEquals($assignment22->id, $menu_item->assignment_id);

        // Create a workflow with a different type; should now get two items
        list($workflow4, $framework4, $assignment4) = $this->create_workflow_and_assignment('Something');
        $assignment_go = new assignment_generator_object($workflow4->course_id, assignment_type\cohort::get_code(), $cohort3->id);
        $assignment_go->status = status::ACTIVE;
        $assignment44 = $this->generator()->create_assignment($assignment_go);
        $resolver = new assignment_resolver($user_entity, $creator);
        $resolver->resolve();
        $items = $resolver->get_menu_items();
        $this->assertCount(2, $items);
        $menu_item1 = $items->first();
        $this->assertEquals($assignment22->id, $menu_item1->assignment_id);
        $menu_item2 = $items->last();
        $this->assertEquals($assignment44->id, $menu_item2->assignment_id);
    }

    public function test_resolution_with_workflow_type_filter() {
        list($workflow1, $framework1, $assignment1) = $this->create_workflow_and_assignment('Foo');
        list($workflow2, $framework2, $assignment2) = $this->create_workflow_and_assignment('Bar');
        list($workflow3, $framework3, $assignment3) = $this->create_workflow_and_assignment('Quux');

        // Generate a cohort
        $cohort1 = $this->cohort_generator()->create_cohort();

        // Create cohort assignment on each workflow
        $assignment_go = new assignment_generator_object($workflow1->course_id, assignment_type\cohort::get_code(), $cohort1->id);
        $assignment_go->status = status::ACTIVE;
        $assignment11 = $this->generator()->create_assignment($assignment_go);
        $assignment_go->course = $workflow2->course_id;
        $assignment22 = $this->generator()->create_assignment($assignment_go);
        $assignment_go->course = $workflow3->course_id;
        $assignment33 = $this->generator()->create_assignment($assignment_go);

        // Add a user and assign to cohorts1
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity = new user($user1->id);
        $this->cohort_generator()->cohort_assign_users($cohort1->id, [$user1->id]);

        // Should get all three assignments
        $creator = clone $user_entity;
        $resolver = new assignment_resolver($user_entity, $creator);
        $resolver->resolve();
        $items = $resolver->get_menu_items();
        $this->assertCount(3, $items);
        $item_workflow_types = $items->pluck('workflow_type');
        $this->assertEqualsCanonicalizing(['Foo', 'Bar', 'Quux'], $item_workflow_types);

        // Now filter by workflow_type
        $workflow2_model = workflow::load_by_entity($workflow2);
        $resolver = new assignment_resolver($user_entity, $creator, $workflow2_model->workflow_type);
        $resolver->resolve();
        $items = $resolver->get_menu_items();
        $this->assertCount(1, $items);
        $item = $items->first();
        $this->assertEquals('Bar', $item->workflow_type);
        $this->assertEquals($assignment22->id, $item->assignment_id);
    }
}