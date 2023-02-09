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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

use core\event\base;
use core\orm\query\builder;
use core_phpunit\testcase;
use mod_approval\entity\workflow\workflow_stage as workflow_stage_entity;
use mod_approval\entity\workflow\workflow_stage_approval_level;
use mod_approval\entity\workflow\workflow_stage_interaction;
use mod_approval\entity\workflow\workflow_version as workflow_version_entity;
use mod_approval\event\workflow_stage_approval_level_created;
use mod_approval\event\workflow_stage_approval_level_deleted;
use mod_approval\event\workflow_stage_approval_levels_reordered;
use mod_approval\event\workflow_stage_created;
use mod_approval\event\workflow_stage_deleted;
use mod_approval\event\workflow_stage_form_views_updated;
use mod_approval\event\workflow_stage_edited;
use mod_approval\exception\model_exception;
use mod_approval\model\application\action\approve;
use mod_approval\model\application\action\reject;
use mod_approval\model\application\action\withdraw_in_approvals;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\form\form;
use mod_approval\model\form\form_version;
use mod_approval\model\status;
use mod_approval\model\workflow\stage_feature\formviews;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\stage_type\finished;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage_approval_level as workflow_stage_approval_level_model;
use mod_approval\model\workflow\workflow_version;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_type;
use mod_approval\testing\application_generator_object;
use mod_approval\testing\approval_workflow_test_setup;
use mod_approval\testing\assignment_generator_object;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\workflow\workflow_stage
 */
class mod_approval_workflow_stage_model_testcase extends testcase {

    use approval_workflow_test_setup;

    /** @var workflow_type */
    private $type;

    /** @var form */
    private $form;

    public function setUp(): void {
        parent::setUp();
        $this->type = workflow_type::create('kia ora');
        $this->form = form::create('simple', 'kia ora');
    }

    public function tearDown(): void {
        $this->form = null;
        $this->type = null;
        parent::tearDown();
    }

    /**
     * Gets the generator instance
     *
     * @return \mod_approval\testing\generator
     */
    protected function generator(): \mod_approval\testing\generator {
        return \mod_approval\testing\generator::instance();
    }

    /**
     * @covers ::create
     */
    public function test_create(): void {
        $this->setAdminUser();
        $time = time();
        $workflow = workflow::create(
            $this->type,
            $this->form,
            'kia ora',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );
        $form_schema = file_get_contents(__DIR__ . '/fixtures/form/test_form.json');
        $form_version = form_version::create($this->form, '1', $form_schema);
        $workflow_version = workflow_version::create($workflow, $form_version);

        // Create event sink
        $sink = $this->redirectEvents();
        $workflow_stage = workflow_stage::create($workflow_version, 'kia kaha', form_submission::get_enum());

        // Assert event triggered.
        $events = $sink->get_events();
        $has_event_triggered = array_filter($events, function (base $event) {
            return $event instanceof workflow_stage_created;
        });
        $this->assertCount(1, $has_event_triggered, 'Duplicate events fired');

        $this->assertNotEmpty($workflow_stage->id);
        $this->assertEquals($workflow_version->id, $workflow_stage->workflow_version->id);
        $this->assertEquals('kia kaha', $workflow_stage->name);
        $this->assertEquals(1, $workflow_stage->ordinal_number);
        $this->assertTrue($workflow_stage->active);
        $this->assertEquals(form_submission::class, $workflow_stage->type);
        $this->assertGreaterThanOrEqual($time, $workflow_stage->created);
        $this->assertLessThanOrEqual($workflow_stage->updated, $workflow_stage->created);
    }

    public function test_create_sortorder() {
        $this->setAdminUser();
        $workflow = workflow::create(
            $this->type,
            $this->form,
            'kia ora',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );
        $form_schema = file_get_contents(__DIR__ . '/fixtures/form/test_form.json');
        $form_version = form_version::create($this->form, '1', $form_schema);
        $workflow_version = workflow_version::create($workflow, $form_version);

        // First create a finished stage. This is an invalid state for activation, but is valid before activation.
        workflow_stage::create(
            $workflow_version,
            'ST1',
            finished::get_enum()
        );

        // Then create a form stage, which should appear before the finished stage.
        workflow_stage::create(
            $workflow_version,
            'ST2',
            form_submission::get_enum()
        );

        // Create another finished stage, which should appear after the existing finished stage.
        workflow_stage::create(
            $workflow_version,
            'ST3',
            finished::get_enum()
        );

        // Last we'll create an approvals stage, which should be inserted after the form stage, before the finished stages.
        workflow_stage::create(
            $workflow_version,
            'ST4',
            approvals::get_enum()
        );

        // Verify that they are ordered ST2, ST4, ST1, ST3, and that sortders are 1, 2, 3, 4 (no gaps or duplicates).
        $stages = workflow_stage_entity::repository()
            ->order_by('sortorder')
            ->get()->to_array();
        self::assertCount(4, $stages);
        self::assertEquals(1, $stages[0]['sortorder']);
        self::assertEquals('ST2', $stages[0]['name']);
        self::assertEquals(2, $stages[1]['sortorder']);
        self::assertEquals('ST4', $stages[1]['name']);
        self::assertEquals(3, $stages[2]['sortorder']);
        self::assertEquals('ST1', $stages[2]['name']);
        self::assertEquals(4, $stages[3]['sortorder']);
        self::assertEquals('ST3', $stages[3]['name']);
    }

    public function test_create_with_a_specified_type() {
        $this->setAdminUser();
        $workflow = workflow::create(
            $this->type,
            $this->form,
            'kia ora',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );
        $form_schema = file_get_contents(__DIR__ . '/fixtures/form/test_form.json');
        $form_version = form_version::create($this->form, '1', $form_schema);
        $workflow_version = workflow_version::create($workflow, $form_version);

        $approvals_stage = workflow_stage::create($workflow_version, 'kia kaha', approvals::get_enum());
        $this->assertEquals(approvals::class, $approvals_stage->type);
    }

    /**
     * @covers ::activate
     * @covers ::deactivate
     */
    public function test_toggle(): void {
        $this->setAdminUser();
        $workflow = workflow::create(
            $this->type,
            $this->form,
            'kia ora',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );
        $form_schema = file_get_contents(__DIR__ . '/fixtures/form/test_form.json');
        $form_version = form_version::create($this->form, '1', $form_schema);
        $workflow_version = workflow_version::create($workflow, $form_version);
        $workflow_stage = workflow_stage::create($workflow_version, 'kia kaha', form_submission::get_enum());
        $this->assertTrue($workflow_stage->active);
        $workflow_stage->deactivate();
        $this->assertFalse($workflow_stage->active);
        $workflow_stage->activate();
        $this->assertTrue($workflow_stage->active);
    }

    /**
     * @covers ::can_deactivate
     */
    public function test_toggle_with_dependencies(): void {
        $this->setAdminUser();
        $workflow = workflow::create(
            $this->type,
            $this->form,
            'kia ora',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );
        $form_schema = file_get_contents(__DIR__ . '/fixtures/form/test_form.json');
        $form_version = form_version::create($this->form, '1', $form_schema);
        $workflow_version = workflow_version::create($workflow, $form_version);
        $workflow_stage = workflow_stage::create($workflow_version, 'kia kaha', form_submission::get_enum());

        $assignment_go = new assignment_generator_object(
            $workflow->course_id,
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );
        $assignment = $this->generator()->create_assignment($assignment_go);
        $application_go = new application_generator_object($workflow_version->id, $form_version->id, $assignment->id);
        $application = $this->generator()->create_application($application_go);

        $this->assertTrue($workflow_stage->active);
        try {
            $workflow_stage->deactivate();
            $this->fail("No model exception thrown");
        } catch (model_exception $e) {
            $this->assertEquals('Cannot deactivate object with active dependencies', $e->debuginfo);
        }
        $application->completed = time();
        $application->save();
        $workflow_stage->deactivate();
        $this->assertFalse($workflow_stage->active);
    }

    /**
     * @covers ::refresh
     */
    public function test_refresh(): void {
        $this->setAdminUser();
        $workflow = workflow::create(
            $this->type,
            $this->form,
            'kia ora',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );
        $form_schema = file_get_contents(__DIR__ . '/fixtures/form/test_form.json');
        $form_version = form_version::create($this->form, '1', $form_schema);
        $workflow_version = workflow_version::create($workflow, $form_version);
        $workflow_stage = workflow_stage::create($workflow_version, 'kia ora koutou', form_submission::get_enum());
        $this->assertNotEmpty($workflow_stage->id);
        $this->assertEquals('kia ora koutou', $workflow_stage->name);
        builder::table(workflow_stage_entity::TABLE)->update(['name' => 'kia ora koutou katoa']);
        $workflow_stage->refresh();
        $this->assertEquals('kia ora koutou katoa', $workflow_stage->name);
    }

    /**
     * @covers ::delete
     */
    public function test_delete(): void {
        $this->setAdminUser();
        $workflow = workflow::create(
            $this->type,
            $this->form,
            'kia ora',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );
        $form_schema = file_get_contents(__DIR__ . '/fixtures/form/test_form.json');
        $form_version = form_version::create($this->form, '1', $form_schema);
        $workflow_version = workflow_version::create($workflow, $form_version);
        $workflow_stage1 = workflow_stage::create($workflow_version, 'kia ora koutou', form_submission::get_enum());
        $workflow_stage2 = workflow_stage::create($workflow_version, 'kia ora koutou katoa', form_submission::get_enum());
        $this->assertNotEmpty($workflow_stage1->id);
        $this->assertNotEmpty($workflow_stage2->id);

        // Create event sink
        $sink = $this->redirectEvents();

        $workflow_stage2->delete();

        // Assert event triggered.
        $events = $sink->get_events();
        $has_event_triggered = array_filter($events, function (base $event) {
            return $event instanceof workflow_stage_deleted;
        });
        $this->assertCount(1, $has_event_triggered, 'Duplicate events fired');

        $this->assertEmpty($workflow_stage2->id);
        $workflow->delete();
        $this->assertFalse(builder::table(workflow_stage_entity::TABLE)->where('id', $workflow_stage1->id)->exists());
        // Check that the other stages have been reordered correctly.
    }

    public function test_configure_formview() {
        // Setup a workflow stage to configure formviews for.
        $this->setAdminUser();
        $form = form::create('simple', 'test form');
        $json_schema = file_get_contents(__DIR__ . "/fixtures/schema/formview_management.json");
        form_version::create($form, 'test form version', $json_schema);
        $workflow = workflow::create(
            workflow_type::create('test workflow type'),
            $form,
            'Test workflow',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );
        $workflow_stage = workflow_stage::create($workflow->latest_version, 'Start', form_submission::get_enum());

        // Create event sink
        $sink = $this->redirectEvents();

        $workflow_stage->configure_formview([
            [
                'field_key' => 'agency_code',
                'visibility' => formviews::EDITABLE,
            ],
        ]);
        $workflow_stage->refresh(true);
        $stage_formviews = $workflow_stage->formviews;

        // Assert event triggered.
        $events = $sink->get_events();
        $has_event_triggered = array_filter($events, function (base $event) {
            return $event instanceof workflow_stage_form_views_updated;
        });
        $this->assertCount(1, $has_event_triggered, 'Duplicate events fired');
        $this->assertCount(7, $workflow_stage->formviews->all());
        $workflow_stage->configure_formview([
            [
                'field_key' => 'agency_code',
                'visibility' => formviews::HIDDEN,
            ],
        ]);
        $workflow_stage->refresh(true);
        $stage_formviews = $workflow_stage->formviews;
        $this->assertCount(6, $stage_formviews->all());
    }

    /**
     * @covers ::add_approval_level
     */
    public function test_add_approval_level(): void {
        $this->setAdminUser();
        $workflow = workflow::create(
            $this->type,
            $this->form,
            'kia ora',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );
        $form_schema = file_get_contents(__DIR__ . '/fixtures/form/test_form.json');
        $form_version = form_version::create($this->form, '1', $form_schema);
        $workflow_version = workflow_version::create($workflow, $form_version);
        workflow_version_entity::repository()->where('id', $workflow_version->id)->update(['status' => status::DRAFT]);
        $workflow_stage1 = workflow_stage::create($workflow_version, 'kia ora koutou', approvals::get_enum());
        // Expect default level
        $this->assertCount(1, $workflow_stage1->approval_levels);

        // Create event sink
        $sink = $this->redirectEvents();

        $workflow_stage1->add_approval_level();

        // Assert event triggered.
        $events = $sink->get_events();
        $has_event_triggered = array_filter($events, function (base $event) {
            return $event instanceof workflow_stage_approval_level_created;
        });
        $this->assertCount(1, $has_event_triggered, 'Duplicate events fired');

        $workflow_stage1->add_approval_level("APL001");
        $workflow_stage1->add_approval_level("APL002");
        $workflow_stage1->add_approval_level("APL003");
        $approval_levels_created = workflow_stage_approval_level::repository()
            ->where('workflow_stage_id', $workflow_stage1->id)
            ->count();
        $this->assertEquals(5, $approval_levels_created);
    }

    /**
     * @return workflow_stage
     */
    private function create_workflow_stage_for_user(): workflow_stage {
        $workflow = workflow::create(
            $this->type,
            $this->form,
            'kia ora',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );
        $form_schema = file_get_contents(__DIR__ . '/fixtures/form/test_form.json');
        $form_version = form_version::create($this->form, '1', $form_schema);
        $workflow_version = workflow_version::create($workflow, $form_version);
        workflow_version_entity::repository()->where('id', $workflow_version->id)->update(['status' => status::DRAFT]);
        $workflow_stage1 = workflow_stage::create($workflow_version, 'kia ora koutou', approvals::get_enum());
        $workflow_stage1->add_approval_level('Level 2');
        $workflow_stage1->add_approval_level('Level 3');
        $workflow_stage1->refresh(true);
        return $workflow_stage1;
    }

    public function data_delete_approval_level(): array {
        return [
            'Level 1' => [1, ['Level 2', 'Level 3']],
            'Level 2' => [2, ['Level 1', 'Level 3']],
            'Level 3' => [3, ['Level 1', 'Level 2']],
        ];
    }

    /**
     * @covers ::delete_approval_level
     * @dataProvider data_delete_approval_level
     */
    public function test_delete_approval_level(int $ordinal_number, array $expected): void {
        $this->setAdminUser();
        $stage1 = $this->create_workflow_stage_for_user();
        $stage2 = workflow_stage::create($stage1->workflow_version, 'kia kaha', approvals::get_enum());

        // Create new levels
        $stage2->add_approval_level('Level 2');
        $stage2->add_approval_level('Level 3');
        $stage2_levels = $stage2->approval_levels->pluck('id');
        $level_to_be_deleted = $stage1->approval_levels->filter('ordinal_number', $ordinal_number)->first();

        // Create event sink
        $sink = $this->redirectEvents();

        $stage1->delete_approval_level($level_to_be_deleted);

        // Assert event triggered.
        $events = $sink->get_events();
        $has_event_triggered = array_filter($events, function (base $event) {
            return $event instanceof workflow_stage_approval_level_deleted;
        });
        $this->assertCount(1, $has_event_triggered, 'Duplicate events fired');

        $ordinals = $stage1->approval_levels->pluck('ordinal_number');
        $names = $stage1->approval_levels->pluck('name');
        $this->assertEquals([1, 2], $ordinals);
        $this->assertEquals($expected, $names);
        $stage2->refresh(true);
        $this->assertEquals($stage2_levels, $stage2->approval_levels->pluck('id'));
    }

    /**
     * @covers ::reorder_approval_levels
     */
    public function test_reorder_approval_level(): void {
        $this->setAdminUser();
        $workflow = workflow::create(
            $this->type,
            $this->form,
            'kia ora',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );
        $form_schema = file_get_contents(__DIR__ . '/fixtures/form/test_form.json');
        $form_version = form_version::create($this->form, '1', $form_schema);
        $workflow_version = workflow_version::create($workflow, $form_version);
        workflow_version_entity::repository()->where('id', $workflow_version->id)->update(['status' => status::DRAFT]);
        $workflow_stage1 = workflow_stage::create($workflow_version, 'kia ora koutou', approvals::get_enum());
        /** @var workflow_stage_approval_level_model $default_level*/
        $default_level = $workflow_stage1->approval_levels->first();
        $default_level->delete();
        $workflow_stage1->refresh(true);

        $l1 = $workflow_stage1->add_approval_level("APL001");
        $l2 = $workflow_stage1->add_approval_level("APL002");
        $l3 = $workflow_stage1->add_approval_level("APL003");

        // Create event sink
        $sink = $this->redirectEvents();
        $workflow_stage1->reorder_approval_levels([$l3, $l2, $l1]);
        $workflow_stage1->refresh(true);

        // Assert event triggered.
        $events = $sink->get_events();
        $has_event_triggered = array_filter($events, function (base $event) {
            return $event instanceof workflow_stage_approval_levels_reordered;
        });
        $this->assertCount(1, $has_event_triggered, 'Duplicate events fired');
        $this->assertEquals($l3->id, $workflow_stage1->approval_levels->first()->id);
        $this->assertEquals($l1->id, $workflow_stage1->approval_levels->last()->id);
    }

    public function test_set_name(): void {
        $this->setAdminUser();
        $stage1 = $this->create_workflow_stage_for_user();
        $this->assertEquals('kia ora koutou', $stage1->name);

        // Create event sink
        $sink = $this->redirectEvents();
        $stage1->set_name('tena koe');

        // Assert event triggered.
        $events = $sink->get_events();
        $has_event_triggered = array_filter($events, function (base $event) {
            return $event instanceof workflow_stage_edited;
        });
        $this->assertCount(1, $has_event_triggered, 'Duplicate events fired');
        $this->assertEquals('tena koe', $stage1->name);
    }

    /**
     * @covers ::add_interaction
     */
    public function test_add_interaction(): void {
        $this->setAdminUser();
        $workflow = workflow::create(
            $this->type,
            $this->form,
            'kia ora',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );
        $form_schema = file_get_contents(__DIR__ . '/fixtures/form/test_form.json');
        $form_version = form_version::create($this->form, '1', $form_schema);
        $workflow_version = workflow_version::create($workflow, $form_version);
        workflow_version_entity::repository()->where('id', $workflow_version->id)->update(['status' => status::DRAFT]);
        $workflow_stage1 = $this->create_stage_via_entity($workflow_version->id, 'kia ora koutou', approvals::get_code(), 20);
        $this->assertCount(0, $workflow_stage1->interactions);

        $workflow_stage1->add_interaction(new approve());
        $workflow_stage1->add_interaction(new reject());
        $workflow_stage1->add_interaction(new withdraw_in_approvals());
        $interactions_created = workflow_stage_interaction::repository()
            ->where('workflow_stage_id', $workflow_stage1->id)
            ->count();
        $this->assertEquals(3, $interactions_created);
    }

    /**
     * @covers ::delete_approval_level
     */
    public function test_delete_interaction(): void {
        $this->setAdminUser();
        $stage1 = $this->create_workflow_stage_for_user();
        $stage2 = $this->create_stage_via_entity($stage1->workflow_version->id, 'kia kaha', approvals::get_code(), 20);

        $interaction1 = $stage2->add_interaction(new approve());
        $interaction2 = $stage2->add_interaction(new reject());
        $interaction3 = $stage2->add_interaction(new withdraw_in_approvals());
        $this->assertCount(3, $stage2->interactions);

        // Delete interaction2
        $stage2->delete_interaction($interaction2);
        $stage2->refresh(true);
        $this->assertCount(2, $stage2->interactions);
        $expected = [$interaction1->id, $interaction3->id];
        $this->assertEqualsCanonicalizing($expected, $stage2->interactions->pluck('id'));
    }
}
