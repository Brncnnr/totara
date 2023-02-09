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

use core\entity\user as user_entity;
use core\event\base;
use core\orm\query\exceptions\record_not_found_exception;
use mod_approval\entity\assignment\assignment;
use mod_approval\entity\assignment\assignment_approver;
use mod_approval\entity\workflow\workflow_version as workflow_version_entity;
use mod_approval\event\workflow_assignment_archived;
use mod_approval\event\workflow_assignment_created;
use mod_approval\event\workflow_assignment_deleted;
use mod_approval\event\workflow_stage_assignment_approvers_for_level_changed;
use mod_approval\exception\model_exception;
use mod_approval\model\assignment\approver_type\user;
use mod_approval\model\assignment\assignment as assignment_model;
use core_phpunit\testcase;
use mod_approval\model\assignment\assignment_approver as assignment_approver_model;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\form\form;
use mod_approval\model\status;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_approval_level;
use mod_approval\model\workflow\workflow_type;
use mod_approval\testing\approval_workflow_test_setup;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\assignment\assignment
 */
class mod_approval_assignment_model_testcase extends testcase {

    use approval_workflow_test_setup;

    /**
     * Gets the generator instance
     *
     * @return \mod_approval\testing\generator
     */
    protected function generator(): \mod_approval\testing\generator {
        return \mod_approval\testing\generator::instance();
    }

    private function create_assignment($data = []) {
        if (empty($data['identifier'])) {
            $data['identifier'] = $this->getDataGenerator()->create_cohort()->id;
        }

        $workflow_type = workflow_type::create('Test');
        $form = form::create('simple', 'Test');
        $workflow = workflow::create(
            $workflow_type,
            $form,
            'Test',
            '',
            $data['type'] ?? assignment_type\cohort::get_code(),
            $data['identifier']
        );

        return $workflow->get_default_assignment();
    }

    /**
     * @covers \mod_approval\model\status::label
     */
    public function test_get_status_label(): void {
        $tests = [
            status::DRAFT => 'Draft',
            status::ACTIVE => 'Active',
            status::ARCHIVED => 'Archived',
        ];
        foreach ($tests as $test => $expected) {
            $this->assertEquals($expected, status::label($test));
        }

        // Test exception on unknown status code
        try {
            status::label(-1);
            $this->fail('Expected model_exception');
        } catch (model_exception $e) {
            $this->assertEquals('Unknown status code', $e->debuginfo);
        }
    }

    /**
     * @covers ::create
     * @covers ::is_draft
     */
    public function test_creation_success(): void {
        $time = time();
        $data = ['identifier' => $this->getDataGenerator()->create_cohort(['idnumber' => 'AUD001'])->id];

        // Create event sink
        $sink = $this->redirectEvents();

        $assignment = $this->create_assignment($data);

        // Assert event triggered.
        $events = $sink->get_events();
        $has_event_triggered = array_filter($events, function (base $event) {
            return $event instanceof workflow_assignment_created;
        });
        $this->assertCount(1, $has_event_triggered, 'Duplicate events fired');

        $this->assertInstanceOf(assignment_model::class, $assignment);
        $this->assertNotEmpty($assignment->id);
        $this->assertEquals('AUD001', $assignment->id_number);
        $this->assertEquals(assignment_type\cohort::get_code(), $assignment->assignment_type);
        $this->assertEquals($data['identifier'], $assignment->assignment_identifier);
        $this->assertEquals(status::ACTIVE, $assignment->status);
        $this->assertGreaterThanOrEqual($time, $assignment->created);
        $this->assertLessThanOrEqual($assignment->updated, $assignment->created);
    }

    /**
     * @covers ::create
     * @covers ::is_draft
     */
    public function test_create_on_draft_workflow_version() {
        // Given a workflow with a draft version
        $workflow = $this->create_assignment()->workflow;

        // When an assignment is created
        $assignment = assignment_model::create(
            $workflow->course_id,
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id,
            false
        );

        // Then the assignment is draft
        $this->assertTrue($assignment->is_draft());
    }

    /**
     * @covers ::create
     * @covers ::is_active
     */
    public function test_create_on_active_workflow_version() {
        // Given a workflow with a published version
        $workflow = $this->create_assignment()->workflow;
        $workflow->publish($workflow->latest_version);

        // When an assignment is created
        $assignment = assignment_model::create(
            $workflow->course_id,
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id,
            false
        );

        // Then the assignment is active
        $this->assertTrue($assignment->is_active());
    }

    /**
     * @covers ::create
     * @covers ::is_draft
     */
    public function test_create_on_archived_workflow_version() {
        // Given a workflow with a published version
        $workflow = $this->create_assignment()->workflow;
        $workflow->publish($workflow->latest_version);
        $workflow->archive();

        // When an assignment is created
        $assignment = assignment_model::create(
            $workflow->course_id,
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id,
            false
        );

        // Then the assignment is active
        $this->assertTrue($assignment->is_draft());
    }

    /**
     * @covers ::create
     * @covers ::is_draft
     */
    public function test_recreate_archived_assignment() {
        // Given a workflow
        $workflow = $this->create_assignment()->workflow;

        // With an archived assignment
        $audience_id = $this->getDataGenerator()->create_cohort()->id;
        $assignment = assignment_model::create(
            $workflow->course_id,
            assignment_type\cohort::get_code(),
            $audience_id,
            false
        );
        $assignment->archive();

        // When the assignment is recreated
        $recreated_assignment = assignment_model::create(
            $workflow->course_id,
            assignment_type\cohort::get_code(),
            $audience_id,
            false
        );

        // Then a new assignment is created & the archived remains
        $this->assertNotEquals($assignment->id, $recreated_assignment->id);
        $this->assertTrue($assignment->is_archived());
        $this->assertTrue($recreated_assignment->is_draft());
    }

    public function test_recreate_non_archived_assignment_throws_an_exception() {
        // Given a workflow
        $workflow = $this->create_assignment()->workflow;

        // With an override assignment
        $audience_id = $this->getDataGenerator()->create_cohort()->id;
        assignment_model::create(
            $workflow->course_id,
            assignment_type\cohort::get_code(),
            $audience_id,
            false
        );

        // When a non-archived assignment is recreated
        // Then an exception is thrown.
        $this->expectException(model_exception::class);
        $this->expectExceptionMessage('Assignment already exists');
        assignment_model::create(
            $workflow->course_id,
            assignment_type\cohort::get_code(),
            $audience_id,
            false
        );
    }

    public function test_create_additional_default_assignment_throws_an_exception() {
        // Given a workflow with a specific default assignment
        $audience_id = $this->getDataGenerator()->create_cohort()->id;
        $default_assignment = $this->create_assignment([
            'type' => assignment_type\cohort::get_code(),
            'identifier' => $audience_id,
        ]);

        $this->expectException(model_exception::class);
        $this->expectExceptionMessage("Default assignment already exists");
        assignment_model::create(
            $default_assignment->workflow->course_id,
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id,
            true
        );
    }

    /**
     * @covers ::create
     */
    public function test_creation_failures(): void {
        $cohort = $this->getDataGenerator()->create_cohort();
        try {
            assignment_model::create(null, assignment_type\cohort::get_code(), $cohort->id);
            $this->fail('model_exception expected');
        } catch (model_exception $ex) {
            $this->assertStringContainsString("Course cannot be empty", $ex->getMessage());
        }
    }

    /**
     * @covers ::create
     */
    public function test_creation_failure_identifier(): void {
        $cohort = $this->getDataGenerator()->create_cohort();
        $workflow_type = workflow_type::create('Test');
        $form = form::create('simple', 'Test');
        $workflow = workflow::create(
            $workflow_type,
            $form,
            'Test',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );

        try {
            assignment_model::create($workflow->container, assignment_type\cohort::get_code(), 0, false);
            $this->fail('record_not_found_exception expected');
        } catch (record_not_found_exception $ex) {
            $this->assertStringContainsString("Can not find data record in database", $ex->getMessage());
        }
    }

    /**
     * @covers ::create
     */
    public function test_create_with_inactive_workflow(): void {
        $cohort = $this->getDataGenerator()->create_cohort();

        $workflow_type = workflow_type::create('Test');
        $form = form::create('simple', 'Test');
        $workflow = workflow::create(
            $workflow_type,
            $form,
            'Test',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );
        $workflow->deactivate();

        try {
            assignment_model::create($workflow->container, assignment_type\cohort::get_code(), $cohort->id);
            $this->fail('model_exception expected');
        } catch (\mod_approval\exception\model_exception $e) {
            $this->assertEquals('Workflow must be active', $e->debuginfo);
        }
    }

    /**
     * @covers ::create
     */
    public function test_create_with_invalid_assignment_type() {
        $cohort = $this->getDataGenerator()->create_cohort();

        $workflow_type = workflow_type::create('Test');
        $form = form::create('simple', 'Test');
        $workflow = workflow::create(
            $workflow_type,
            $form,
            'Test',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );

        try {
            $assignment = assignment_model::create($workflow->container, - 1, $cohort->id, false);
            $this->fail('model_exception expected');
        } catch (\mod_approval\exception\model_exception $e) {
            $this->assertEquals('Unknown assignment type code: -1', $e->debuginfo);
        }
    }

    /**
     * @covers ::set_approvers_for_level
     */
    public function test_set_approvers_for_level() {
        [$workflow, $framework, $default_assignment] = $this->create_workflow_and_assignment();
        $workflow_model = workflow::load_by_entity($workflow);
        $stage1 = $workflow_model->latest_version->stages->first();
        $stage2 = $workflow_model->latest_version->get_next_stage($stage1->id);
        $approval_level = $stage2->approval_levels->first();
        $default_assignment_model = assignment_model::load_by_entity($default_assignment);

        // test adding unknown approver type.
        $this->test_adding_approvers($default_assignment_model, $approval_level);
        $this->test_adding_unknown_approver_type($default_assignment_model, $approval_level);
    }

    /**
     * Used by test_set_approvers_for_level
     * @param assignment_model $assignment
     * @param workflow_stage_approval_level $approval_level
     */
    private function test_adding_unknown_approver_type(
        assignment_model $assignment,
        workflow_stage_approval_level $approval_level
    ) {
        $this->expectException(model_exception::class);
        $this->expectExceptionMessage('Unknown approver type provided');
        $assignment->set_approvers_for_level(
            $approval_level,
            [
                [
                    'assignment_approver_type' => 007
                ],
            ]
        );
    }

    /**
     * Used by test_set_approvers_for_level
     * @param assignment_model $assignment
     * @param workflow_stage_approval_level $approval_level
     */
    private function test_adding_approvers(assignment_model $assignment, workflow_stage_approval_level $approval_level) {
        // Create users
        $user_a = $this->getDataGenerator()->create_user();
        $user_b = $this->getDataGenerator()->create_user();
        $user_c = $this->getDataGenerator()->create_user();

        // Initially, no assignment approvers.
        $existing_assignment_approvers = assignment_approver::repository()
            ->where('approval_id', $assignment->id)
            ->where('workflow_stage_approval_level_id', $approval_level->id)
            ->get();
        $this->assertEmpty($existing_assignment_approvers);

        // Create event sink
        $sink = $this->redirectEvents();

        $assignment->set_approvers_for_level(
            $approval_level,
            [
                [
                    'assignment_approver_type' => user::TYPE_IDENTIFIER,
                    'identifier' => $user_a->id,
                ],
                [
                    'assignment_approver_type' => user::TYPE_IDENTIFIER,
                    'identifier' => $user_b->id,
                ],
                [
                    'assignment_approver_type' => user::TYPE_IDENTIFIER,
                    'identifier' => $user_c->id,
                ],
            ]
        );

        // Assert event triggered.
        $events = $sink->get_events();
        $has_event_triggered = array_filter($events, function (base $event) {
            return $event instanceof workflow_stage_assignment_approvers_for_level_changed;
        });
        $this->assertCount(1, $has_event_triggered, 'Duplicate events fired');

        $existing_assignment_approvers = assignment_approver::repository()
            ->where('approval_id', $assignment->id)
            ->where('workflow_stage_approval_level_id', $approval_level->id)
            ->get()->map_to(assignment_approver_model::class);
        $this->assertCount(3, $existing_assignment_approvers);

        foreach ($existing_assignment_approvers as $existing_assignment_approver) {
            $this->assertTrue($existing_assignment_approver->active);
        }

        // test each user has it's approver.
        $user_a_approver = $existing_assignment_approvers->find(function ($approver) use ($user_a) {
            return $approver->type === user::TYPE_IDENTIFIER
                && $approver->identifier === $user_a->id
                && $approver->active === true;
        });
        $this->assertNotNull($user_a_approver);

        $user_b_approver = $existing_assignment_approvers->find(function ($approver) use ($user_b) {
            return $approver->type === user::TYPE_IDENTIFIER
                && $approver->identifier === $user_b->id
                && $approver->active === true;
        });
        $this->assertNotNull($user_b_approver);

        $user_c_approver = $existing_assignment_approvers->find(function ($approver) use ($user_c) {
            return $approver->type === user::TYPE_IDENTIFIER
                && $approver->identifier === $user_c->id
                && $approver->active === true;
        });
        $this->assertNotNull($user_c_approver);

        // test removing user_b.
        $assignment->set_approvers_for_level(
            $approval_level,
            [
                [
                    'assignment_approver_type' => user::TYPE_IDENTIFIER,
                    'identifier' => $user_a->id,
                ],
                [
                    'assignment_approver_type' => user::TYPE_IDENTIFIER,
                    'identifier' => $user_c->id,
                ],
            ]
        );

        // Test Still has 3 approvers however one is deactivated.
        $existing_assignment_approvers = assignment_approver::repository()
            ->where('approval_id', $assignment->id)
            ->where('workflow_stage_approval_level_id', $approval_level->id)
            ->get();
        $this->assertCount(3, $existing_assignment_approvers);

        // test each user has it's approver.
        $user_a_approver = $existing_assignment_approvers->find(function ($approver) use ($user_a) {
            return $approver->type === user::TYPE_IDENTIFIER
                && $approver->identifier === $user_a->id
                && $approver->active === true;
        });
        $this->assertNotNull($user_a_approver);

        // user_b is deactivated.
        $user_b_approver = $existing_assignment_approvers->find(function ($approver) use ($user_b) {
            return $approver->type === user::TYPE_IDENTIFIER
                && $approver->identifier === $user_b->id
                && $approver->active === false;
        });
        $this->assertNotNull($user_b_approver);

        $user_c_approver = $existing_assignment_approvers->find(function ($approver) use ($user_c) {
            return $approver->type === user::TYPE_IDENTIFIER
                && $approver->identifier === $user_c->id
                && $approver->active === true;
        });
        $this->assertNotNull($user_c_approver);

        $this->test_re_adding_an_inactive_approver($assignment, $approval_level, $user_a, $user_c, $user_b);
    }

    /**
     * Test re-adding an inactive approver.
     *
     * @param assignment_model $assignment
     * @param workflow_stage_approval_level $approval_level
     * @param $user_a
     * @param $user_c
     * @param $user_b
     *
     * @throws model_exception
     */
    private function test_re_adding_an_inactive_approver(
        assignment_model $assignment,
        workflow_stage_approval_level $approval_level,
        $user_a,
        $user_c,
        $user_b
    ): void {
        $assignment->set_approvers_for_level(
            $approval_level,
            [
                [
                    'assignment_approver_type' => user::get_code(),
                    'identifier' => $user_a->id,
                ],
                [
                    'assignment_approver_type' => user::get_code(),
                    'identifier' => $user_c->id,
                ],
                [
                    'assignment_approver_type' => user::get_code(),
                    'identifier' => $user_b->id,
                ],
            ]
        );
        $approvers = $approval_level->get_approvers()->all();
        $this->assertCount(3, $approvers);
        $this->assertEqualsCanonicalizing(
            [
                $user_a->id,
                $user_b->id,
                $user_c->id,
            ],
            [
                $approvers[0]->identifier,
                $approvers[1]->identifier,
                $approvers[2]->identifier,
            ]
        );
    }

    /**
     * @covers ::activate
     * @covers ::is_active
     * @covers ::archive
     * @covers ::is_archived
     */
    public function test_activate_and_archive(): void {
        $assignment = $this->create_assignment();
        $assignment->activate();
        $this->assertFalse($assignment->is_draft());
        $this->assertTrue($assignment->is_active());
        $this->assertFalse($assignment->is_archived());

        $assignment->archive();
        $this->assertFalse($assignment->is_draft());
        $this->assertFalse($assignment->is_active());
        $this->assertTrue($assignment->is_archived());
    }

    public function test_archive_assignment() {
        [$workflow_entity, , , $override_assignments] = $this->create_workflow_and_assignment('Testing', true, true);
        $workflow = workflow::load_by_entity($workflow_entity);

        /** @var workflow_stage $approval_stage*/
        $approval_stage = $workflow->latest_version->stages->find('type', approvals::class);
        /** @var workflow_stage_approval_level $approval_level*/
        $approval_level = $approval_stage->approval_levels->first();

        // Use first override assignment and set user approvers.
        $override_assignment = assignment_model::load_by_entity($override_assignments[0]);
        for ($i = 0; $i < 2; $i++) {
            $approvers[] = [
                'assignment_approver_type' => user::TYPE_IDENTIFIER,
                'identifier' => $this->getDataGenerator()->create_user()->id,
            ];
        }
        $override_assignment->set_approvers_for_level($approval_level, $approvers);
        $this->assertCount(2, $override_assignment->approvers);

        // Create event sink
        $sink = $this->redirectEvents();

        $override_assignment->archive();

        // Assert event triggered.
        $events = $sink->get_events();
        $has_event_triggered = array_filter($events, function (base $event) {
            return $event instanceof workflow_assignment_archived;
        });
        $this->assertCount(1, $has_event_triggered, 'Duplicate events fired');

        // Assignment is archived with no active approvers.
        $this->assertEquals(status::ARCHIVED, $override_assignment->status);
        $this->assertEmpty($override_assignment->approvers);
    }

    /**
     * Test activation creating inherited approvers on newly-activated assignments
     * @covers ::activate
     */
    public function test_activate_with_inherited_approvers(): void {
        // Create some users to be approvers.
        $agency_level_1 = new user_entity($this->getDataGenerator()->create_user()->id);
        $agency_level_21 = new user_entity($this->getDataGenerator()->create_user()->id);
        $agency_level_22 = new user_entity($this->getDataGenerator()->create_user()->id);

        list($workflow_entity, $framework, $assignment_entity) = $this->create_workflow_and_assignment('Testing', false, false);
        $workflow = workflow::load_by_entity($workflow_entity);
        $assignment = assignment_model::load_by_entity($assignment_entity);
        $this->assertTrue($assignment->is_active());
        $this->assertEquals($assignment->assignment_identifier, $framework->agency->id);

        $stage1 = $workflow->latest_version->stages->first();
        $stage2 = $workflow->latest_version->get_next_stage($stage1->id);
        $level1 = $stage2->approval_levels->first();
        $level2 = $stage2->add_approval_level('Level 2');

        // Add approvers to default assignment
        $assignment->set_approvers_for_level(
            $level1,
            [
                [
                    'assignment_approver_type' => user::TYPE_IDENTIFIER,
                    'identifier' => $agency_level_1->id
                ]
            ]
        );
        $assignment->set_approvers_for_level(
            $level2,
            [
                [
                    'assignment_approver_type' => user::TYPE_IDENTIFIER,
                    'identifier' => $agency_level_21->id
                ],
                [
                    'assignment_approver_type' => user::TYPE_IDENTIFIER,
                    'identifier' => $agency_level_22->id
                ]
            ]
        );

        // Add an override for subagency_a
        $sub_a = assignment_model::create(
            $workflow->get_container(),
            assignment_type\organisation::get_code(),
            $framework->agency->subagency_a->id
        );
        // Add an override for progarm_a
        $prog_a = assignment_model::create(
            $workflow->get_container(),
            assignment_type\organisation::get_code(),
            $framework->agency->subagency_a->program_a->id
        );

        // No approvers until activated.
        $this->assertCount(0, $sub_a->get_approvers());
        $this->assertCount(0, $prog_a->get_approvers());

        // Publish workflow
        $workflow->publish($workflow->latest_version);
        $sub_a->refresh(true);
        $prog_a->refresh(true);
        $this->assertCount(3, $sub_a->get_approvers());
        $this->assertCount(3, $prog_a->get_approvers());
    }

    /**
     * Test activation creating inherited and descendant approvers on newly-activated assignments
     * @covers ::activate
     */
    public function test_activate_with_inherited_and_descendant_approvers(): void {
        // Create some users to be approvers.
        $agency_level_1 = new user_entity($this->getDataGenerator()->create_user()->id);
        $agency_level_2 = new user_entity($this->getDataGenerator()->create_user()->id);
        $sub_a_level_1 = new user_entity($this->getDataGenerator()->create_user()->id);
        $prog_a_level_2 = new user_entity($this->getDataGenerator()->create_user()->id);

        /**
         * $framework->agency
         * $framework->agency->subagency_a
         * $framework->agency->subagency_a->program_a
         */
        list($workflow_entity, $framework, $assignment_entity) = $this->create_workflow_and_assignment('Testing', false, false);
        $workflow = workflow::load_by_entity($workflow_entity);
        $assignment = assignment_model::load_by_entity($assignment_entity);
        $this->assertTrue($assignment->is_active());
        $this->assertEquals($assignment->assignment_identifier, $framework->agency->id);

        // Add a second level.
        $stage1 = $workflow->latest_version->stages->first();
        $stage2 = $workflow->latest_version->get_next_stage($stage1->id);
        $level1 = $stage2->approval_levels->first();
        $level2 = $stage2->add_approval_level('Level 2');

        // Add approvers to both levels of default assignment
        $assignment->set_approvers_for_level(
            $level1,
            [
                [
                    'assignment_approver_type' => user::TYPE_IDENTIFIER,
                    'identifier' => $agency_level_1->id
                ]
            ]
        );
        $assignment->set_approvers_for_level(
            $level2,
            [
                [
                    'assignment_approver_type' => user::TYPE_IDENTIFIER,
                    'identifier' => $agency_level_2->id
                ]
            ]
        );

        // Add an override for subagency_a
        $sub_a = assignment_model::create(
            $workflow->get_container(),
            assignment_type\organisation::get_code(),
            $framework->agency->subagency_a->id
        );
        // Add an approver on level 1 for subagency_a
        $sub_a->set_approvers_for_level(
            $level1,
            [
                [
                    'assignment_approver_type' => user::TYPE_IDENTIFIER,
                    'identifier' => $sub_a_level_1->id
                ]
            ]
        );

        // Add an override for program_a
        $prog_a = assignment_model::create(
            $workflow->get_container(),
            assignment_type\organisation::get_code(),
            $framework->agency->subagency_a->program_a->id
        );
        // Add an approver on level 2 for program_a
        $prog_a->set_approvers_for_level(
            $level2,
            [
                [
                    'assignment_approver_type' => user::TYPE_IDENTIFIER,
                    'identifier' => $prog_a_level_2->id
                ]
            ]
        );

        // Before activating any
        $this->assertCount(1, $sub_a->get_approvers());
        $this->assertEquals(status::DRAFT, $sub_a->status);
        $this->assertCount(1, $prog_a->get_approvers());
        $this->assertEquals(status::DRAFT, $prog_a->status);

        // Publish workflow and prog_a and sub_a are activated.
        $workflow->publish($workflow->latest_version);

        $prog_a->refresh(true);
        $sub_a->refresh(true);
        $sub_a_approvers = $sub_a->get_approvers();
        $prog_a_approvers = $prog_a->get_approvers();

        $this->assertCount(2, $sub_a_approvers);
        $this->assertEquals($sub_a_level_1->id, $sub_a_approvers->first()->identifier);
        $this->assertEquals($agency_level_2->id, $sub_a_approvers->last()->identifier);
        $this->assertCount(2, $prog_a_approvers);
        $this->assertEquals($prog_a_level_2->id, $prog_a_approvers->first()->identifier);
        $this->assertEquals($sub_a_level_1->id, $prog_a_approvers->last()->identifier);
    }

    /**
     * @covers ::get_course_module
     */
    public function test_get_course_module(): void {
        $assignment = $this->create_assignment();
        $cm = $assignment->get_course_module();

        $this->assertIsObject($cm);
        $this->assertSame("approval", $cm->modname);
        $this->assertSame("Cohort 1", $cm->name);
        $this->assertEquals($assignment->id, $cm->instance);
    }

    /**
     * @covers ::get_context
     */
    public function test_get_context(): void {
        $assignment = $this->create_assignment();
        $context = $assignment->get_context();
        $cm = $assignment->get_course_module();

        $this->assertInstanceOf('context_module', $context);
        $this->assertEquals(CONTEXT_MODULE, $context->contextlevel);
        $this->assertEquals($cm->id, $context->instanceid);
    }

    /**
     * @covers ::delete
     */
    public function test_delete(): void {
        $default_assignment = $this->create_assignment();
        $assignment = assignment_model::create(
            $default_assignment->course_id,
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );
        // Create event sink
        $sink = $this->redirectEvents();

        $assignment->delete();

        // Assert event triggered.
        $events = $sink->get_events();
        $has_event_triggered = array_filter($events, function (base $event) {
            return $event instanceof workflow_assignment_deleted;
        });
        $this->assertCount(1, $has_event_triggered, 'Duplicate events fired');

        $this->assertFalse(assignment::repository()->where('id', $assignment->id)->exists());
    }

    /**
     * @covers ::delete
     */
    public function test_unable_to_delete_active(): void {
        $assignment = $this->create_assignment();
        $assignment->activate();
        try {
            $assignment->delete();
            $this->fail('Expected model_exception');
        } catch (model_exception $e) {
            $this->assertEquals('Only draft assignments can be deleted', $e->debuginfo);
        }
    }

    /**
     * @covers ::delete_later
     */
    public function test_delete_later(): void {
        global $DB;

        $assignment = $this->create_assignment();
        $this->assertCount(1, $DB->get_records('approval'));
        $this->assertCount(1, $DB->get_records('course_modules'));
        $this->assertCount(1, $DB->get_records('approval'));

        $assignment->delete_later();
        $this->assertCount(1, $DB->get_records('approval'));
        $this->assertCount(1, $DB->get_records('course_modules'));
        $this->assertCount(1, $DB->get_records('approval'));

        $assignment->refresh();
        $this->assertEquals(1, $assignment->to_be_deleted);
    }
}
