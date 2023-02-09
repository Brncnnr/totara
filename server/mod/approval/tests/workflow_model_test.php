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

use container_approval\approval as approval_container;
use core\entity\tenant;
use core\event\base;
use core\orm\query\builder;
use core_phpunit\testcase;
use mod_approval\entity\form\form_version as form_version_entity;
use mod_approval\entity\workflow\workflow as workflow_entity;
use mod_approval\entity\workflow\workflow_version as workflow_version_entity;
use mod_approval\event\workflow_cloned;
use mod_approval\event\workflow_created;
use mod_approval\event\workflow_deleted;
use mod_approval\event\workflow_edited;
use mod_approval\event\workflow_version_archived;
use mod_approval\event\workflow_version_published;
use mod_approval\event\workflow_version_unarchived;
use mod_approval\exception\model_exception;
use mod_approval\model\assignment\assignment;
use mod_approval\model\form\form;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\status;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_type;
use mod_approval\model\workflow\workflow_version;
use mod_approval\testing\workflow_generator_object;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\workflow\workflow
 */
class mod_approval_workflow_model_testcase extends testcase {
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
    public function test_creation_succeeds_on_singletenant(): void {
        $this->setAdminUser();
        $time = time();

        // Create event sink
        $sink = $this->redirectEvents();

        $workflow = workflow::create(
            $this->type,
            $this->form,
            'adm!n',
            'lorem',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id,
            'ipsum'
        );

        // Assert event triggered.
        $events = $sink->get_events();
        $has_event_triggered = array_filter($events, function (base $event) {
            return $event instanceof workflow_created;
        });
        $this->assertCount(1, $has_event_triggered, 'Duplicate events fired');

        $this->assertNotEmpty($workflow->id);
        $this->assertEquals($workflow->workflow_type_id, $this->type->id);
        $this->assertEquals($workflow->form->id, $this->form->id);
        $this->assertNull($workflow->template);
        $this->assertEquals('adm!n', $workflow->name);
        $this->assertEquals('adm!n', $workflow->container->fullname);
        $this->assertEquals('lorem', $workflow->description);
        $this->assertEquals('ipsum', $workflow->id_number);
        $this->assertTrue($workflow->active);
        $this->assertGreaterThanOrEqual($time, $workflow->created);
        $this->assertLessThanOrEqual($workflow->updated, $workflow->created);
        $this->assertFalse($workflow->to_be_deleted);
        $this->assertCount(1, $workflow->versions);
        $this->assertEquals($this->form->latest_version->id, $workflow->latest_version->form_version_id);
        $this->assertEquals(status::DRAFT, $workflow->latest_version->status);
        $this->assertTrue($workflow->default_assignment->is_active());
    }

    /**
     * @covers ::publish
    */
    public function test_publish_workflow_version() {
        $this->setAdminUser();
        $workflow = workflow::create(
            $this->type,
            $this->form,
            'adm!n',
            'lorem',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id,
            'ipsum'
        );

        // Create event sink
        $sink = $this->redirectEvents();

        $workflow->publish($workflow->latest_version);

        // Assert event triggered.
        $events = $sink->get_events();
        $has_event_triggered = array_filter($events, function (base $event) {
            return $event instanceof workflow_version_published;
        });
        $this->assertCount(1, $has_event_triggered, 'Duplicate events fired');

        // Assert version is published.
        $this->assertEquals(status::ACTIVE, $workflow->latest_version->status);
    }

    /**
     * @covers ::create
     */
    public function test_creation_succeeds_on_multitenant(): void {
        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tengen = $this->getDataGenerator()->get_plugin_generator('totara_tenant');
        $tengen->enable_tenants();

        $ten1 = new tenant($tengen->create_tenant());
        $ten2 = new tenant($tengen->create_tenant());

        $ten1user1 = $this->getDataGenerator()->create_user(['tenantid' => $ten1->id]);
        $ten1user2 = $this->getDataGenerator()->create_user(['tenantid' => $ten1->id]);
        $ten2user1 = $this->getDataGenerator()->create_user(['tenantid' => $ten2->id]);

        $this->setAdminUser();
        $ten0workflow = workflow::create(
            $this->type,
            $this->form,
            'adm!n',
            'lorem',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id,
            'ipsum'
        );

        $this->setUser($ten1user1);
        $ten1workflow1 = workflow::create(
            $this->type,
            $this->form,
            'user11',
            'dolor',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id,
            'sit'
        );

        $this->setUser($ten1user2);
        $ten1workflow2 = workflow::create(
            $this->type,
            $this->form,
            'user12',
            'amet',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id,
            'consectetur'
        );

        $this->setUser($ten2user1);
        $ten2workflow1 = workflow::create(
            $this->type,
            $this->form,
            'user21',
            'adipiscing',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id,
            'elit'
        );

        $this->assertNotEquals($ten0workflow->container->category, $ten1workflow1->container->category);
        $this->assertNotEquals($ten0workflow->container->category, $ten2workflow1->container->category);
        $this->assertEquals($ten1workflow1->container->category, $ten1workflow2->container->category);

        $this->assertNotEmpty($ten0workflow->id);
        $this->assertEquals($ten0workflow->workflow_type_id, $this->type->id);
        $this->assertEquals($ten0workflow->form->id, $this->form->id);
        $this->assertEquals('adm!n', $ten0workflow->name);
        $this->assertEquals('lorem', $ten0workflow->description);
        $this->assertEquals('ipsum', $ten0workflow->id_number);

        $this->assertNotEmpty($ten1workflow1->id);
        $this->assertEquals($ten1workflow1->workflow_type_id, $this->type->id);
        $this->assertEquals($ten1workflow1->form->id, $this->form->id);
        $this->assertEquals('user11', $ten1workflow1->name);
        $this->assertEquals('dolor', $ten1workflow1->description);
        $this->assertEquals('sit', $ten1workflow1->id_number);

        $this->assertNotEmpty($ten1workflow2->id);
        $this->assertEquals($ten1workflow2->workflow_type_id, $this->type->id);
        $this->assertEquals($ten1workflow2->form->id, $this->form->id);
        $this->assertEquals('user12', $ten1workflow2->name);
        $this->assertEquals('amet', $ten1workflow2->description);
        $this->assertEquals('consectetur', $ten1workflow2->id_number);

        $this->assertNotEmpty($ten2workflow1->id);
        $this->assertEquals($ten2workflow1->workflow_type_id, $this->type->id);
        $this->assertEquals($ten2workflow1->form->id, $this->form->id);
        $this->assertEquals('user21', $ten2workflow1->name);
        $this->assertEquals('adipiscing', $ten2workflow1->description);
        $this->assertEquals('elit', $ten2workflow1->id_number);
    }

    public function data_creation_fails_on_invalid_parameter(): array {
        return [
            'empty name' => ['', '', '', 'Workflow name cannot be empty'],
        ];
    }

    /**
     * @param string $name
     * @param string $description
     * @param string $id_number
     * @param string $exception
     * @dataProvider data_creation_fails_on_invalid_parameter
     * @covers ::create
     */
    public function test_creation_fails_on_invalid_parameter(
        string $name,
        string $description,
        string $id_number,
        string $exception
    ): void {
        $this->setAdminUser();
        try {
            workflow::create(
                $this->type,
                $this->form,
                $name,
                $description,
                assignment_type\cohort::get_code(),
                $this->getDataGenerator()->create_cohort()->id,
                $id_number
            );
            $this->fail('model_exception expected');
        } catch (model_exception $ex) {
            $this->assertStringContainsString($exception, $ex->getMessage());
        }
    }

    /**
     * @covers ::create
     */
    public function test_creation_fails_with_inactive_workflow_type(): void {
        $this->setAdminUser();
        // Deactivate workflow_type
        $this->type->deactivate();
        try {
            workflow::create(
                $this->type,
                $this->form,
                'Test',
                '',
                assignment_type\cohort::get_code(),
                $this->getDataGenerator()->create_cohort()->id,
                '001'
            );
            $this->fail('model_exception expected');
        } catch (model_exception $ex) {
            $this->assertEquals('Workflow_type must be active', $ex->debuginfo);
        }
    }

    /**
     * @covers ::create
     */
    public function test_creation_fails_with_inactive_form(): void {
        $this->setAdminUser();
        // Deactivate workflow_type
        $form_version_entity = form_version_entity::repository()->where('form_id', '=', $this->form->id)->one();
        $form_version_entity->status = status::DRAFT;
        $form_version_entity->save();
        $this->form->deactivate();
        try {
            workflow::create(
                $this->type,
                $this->form,
                'Test',
                '',
                assignment_type\cohort::get_code(),
                $this->getDataGenerator()->create_cohort()->id,
                '001'
            );
            $this->fail('model_exception expected');
        } catch (model_exception $ex) {
            $this->assertEquals('Form must be active', $ex->debuginfo);
        }
    }

    /**
     * @covers ::create_from_template
     */
    public function test_create_from_template(): void {
        $this->setAdminUser();
        $workflow1 = workflow::create(
            $this->type,
            $this->form,
            'adm!n',
            'lorem',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id,
            'ipsum'
        );
        $this->assertNotEmpty($workflow1->id);
        $this->assertNull($workflow1->template);
        $workflow2 = workflow::create_from_template(
            $workflow1,
            'dolor',
            'sit',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id,
            'amet'
        );
        $this->assertNotEmpty($workflow2->id);
        $this->assertNotEquals($workflow1->id, $workflow2->id);
        $this->assertEquals($workflow2->workflow_type_id, $workflow1->workflow_type_id);
        $this->assertEquals($workflow2->form_id, $workflow1->form_id);
        $this->assertEquals($workflow1->id, $workflow2->template->id);
        $this->assertEquals('dolor', $workflow2->name);
        $this->assertEquals('sit', $workflow2->description);
        $this->assertEquals('amet', $workflow2->id_number);
        $this->assertTrue($workflow2->default_assignment->is_active());
    }

    /**
     * @covers ::create_from_template
     */
    public function test_edit(): void {
        $this->setAdminUser();
        $workflow1 = workflow::create(
            $this->type,
            $this->form,
            'test one',
            'uno desc',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id,
            'eye-dee-73571'
        );
        $workflow2 = workflow::create_from_template(
            $workflow1,
            'test two',
            'dos desc',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id,
            'eye-dee-73572'
        );

        $this->assertEquals('test one', $workflow1->name);
        $this->assertEquals('uno desc', $workflow1->description);
        $this->assertEquals('eye-dee-73571', $workflow1->id_number);
        $this->assertEquals('eye-dee-73572', $workflow2->id_number);

        // Event sink
        $sink = $this->redirectEvents();

        $workflow1->edit('new name', '', 'id73571');
        $this->assertEquals('new name', $workflow1->name);

        // Assert container name is updated.
        $this->assertEquals('new name', $workflow1->container->fullname);
        $this->assertEquals('', $workflow1->description);
        $this->assertEquals('id73571', $workflow1->id_number);

        // Assert event triggered.
        $events = $sink->get_events();
        $has_event_triggered = array_filter($events, function (base $event) {
            return $event instanceof workflow_edited;
        });
        $this->assertCount(1, $has_event_triggered, 'Duplicate events fired');

        try {
            $workflow1->edit('newer name', 'newer desc', 'eye-dee-73572');
            $this->fail('moodle_exception expected');
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('You should enter a unique ID', $ex->getMessage());
        }
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
        $this->assertTrue($workflow->active);
        $workflow->deactivate();
        $this->assertFalse($workflow->active);
        $workflow->activate();
        $this->assertTrue($workflow->active);
    }

    /**
     * @covers ::can_deactivate
     */
    public function test_toggle_with_dependencies(): void {
        $form_version = $this->generator()->create_form_and_version();
        $workflow_go = new workflow_generator_object($this->type->id, $form_version->form_id, $form_version->id);
        $workflow_version_entity = $this->generator()->create_workflow_and_version($workflow_go);
        $workflow_version = workflow_version::load_by_entity($workflow_version_entity);
        $workflow = $workflow_version->workflow;
        $this->assertTrue($workflow->active);
        try {
            $workflow->deactivate();
            $this->fail("No model exception thrown");
        } catch (model_exception $e) {
            $this->assertEquals('Cannot deactivate object with active dependencies', $e->debuginfo);
        }
        $workflow_version_entity->status = status::DRAFT;
        $workflow_version_entity->save();
        $workflow->deactivate();
        $this->assertFalse($workflow->active);
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
        $this->assertEquals('kia ora', $workflow->name);
        builder::table(workflow_entity::TABLE)->update(['name' => 'kia kaha']);
        $workflow->refresh();
        $this->assertEquals('kia kaha', $workflow->name);
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
        $this->assertNotEmpty($workflow->id);

        // Create event sink
        $sink = $this->redirectEvents();

        $workflow->delete();

        // Assert event triggered.
        $events = $sink->get_events();
        $has_event_triggered = array_filter($events, function (base $event) {
            return $event instanceof workflow_deleted;
        });
        $this->assertCount(1, $has_event_triggered, 'Duplicate events fired');
        $this->assertEmpty($workflow->id);
    }

    /**
     * @covers ::delete_later
     */
    public function test_delete_later(): void {
        $this->setAdminUser();
        $workflow = workflow::create(
            $this->type,
            $this->form,
            'kia ora',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );
        $this->assertNotEmpty($workflow->id);
        $this->assertFalse($workflow->to_be_deleted);
        $workflow->delete_later();
        $this->assertNotEmpty($workflow->id);
        $this->assertTrue($workflow->to_be_deleted);
    }

    /**
     * @covers ::get_versions
     */
    public function test_get_versions(): void {
        $this->setAdminUser();
        $workflow1 = workflow::create(
            $this->type,
            $this->form,
            'kia ora',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );
        $workflow2 = workflow::create(
            $this->type,
            $this->form,
            'tena koutou',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );
        workflow_version::create($workflow1, $this->form->latest_version);
        workflow_version::create($workflow1, $this->form->latest_version);
        // NOTE: workflow_version::create should probably append itself to workflow::versions
        $workflow1->refresh();
        $workflow2->refresh();
        $this->assertCount(3, $workflow1->versions);
        $this->assertCount(1, $workflow2->versions);
    }

    /**
     * @covers ::get_active_version
     */
    public function test_get_active_version(): void {
        $this->setAdminUser();
        $workflow = workflow::create(
            $this->type,
            $this->form,
            'kia ora',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );
        $version1 = workflow_version::create($workflow, $this->form->latest_version);
        $version1->activate();
        $version1->archive();
        $version2 = workflow_version::create($workflow, $this->form->latest_version);
        $version2->activate();
        $version3 = workflow_version::create($workflow, $this->form->latest_version);

        $workflow->refresh();

        $this->assertCount(4, $workflow->versions);
        $this->assertEquals($version2, $workflow->active_version);
        $this->assertEquals($version3, $workflow->latest_version);
    }

    /**
     * @covers ::get_context
     */
    public function test_get_context(): void {
        $this->setAdminUser();
        $workflow1 = workflow::create(
            $this->type,
            $this->form,
            'kia ora',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );
        $workflow2 = workflow::create(
            $this->type,
            $this->form,
            'tena koutou',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $context1admin = $workflow1->get_context(null);
        $this->setUser($user1);
        $context1user = $workflow1->get_context($user2->id);
        $concon = context_course::instance($workflow1->container->id);
        $this->assertEquals($concon->id, $context1admin->id);
        $this->assertEquals($context1admin->id, $context1user->id);
    }

    /**
     * @covers ::are_all_draft
     */
    public function test_are_all_draft_by_default(): void {
        $this->setAdminUser();
        $workflow = workflow::create(
            $this->type,
            $this->form,
            'kia ora',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );
        $this->assertTrue($workflow->are_all_draft());
    }

    /**
     * @covers ::are_all_draft
     */
    public function test_are_all_draft_when_no_versions(): void {
        $this->setAdminUser();
        $workflow = workflow::create(
            $this->type,
            $this->form,
            'kia ora',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );
        builder::table(workflow_version_entity::TABLE)->delete();
        $workflow->refresh(true);
        $this->assertTrue($workflow->are_all_draft());
    }

    /**
     * Test are_all_draft with three versions in chronological order of archived, active, draft
     * @covers ::are_all_draft
     */
    public function test_are_all_draft_when_latest_version_is_draft(): void {
        $this->setAdminUser();
        $workflow = workflow::create(
            $this->type,
            $this->form,
            'kia ora',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );
        $this->assertEquals(1, $workflow->versions->count());
        builder::table(workflow_version_entity::TABLE)
            ->where('id', $workflow->latest_version->id)
            ->update(['status' => status::ARCHIVED]);
        workflow_version::create($workflow, $this->form->latest_version);
        $workflow->refresh(true);
        builder::table(workflow_version_entity::TABLE)
            ->where('id', $workflow->latest_version->id)
            ->update(['status' => status::ACTIVE]);
        workflow_version::create($workflow, $this->form->latest_version);
        $workflow->refresh(true);
        $this->assertEquals(3, $workflow->versions->count());
        $this->assertEquals('Draft', $workflow->latest_version->status_label);
        $this->assertFalse($workflow->are_all_draft());
    }

    /**
     * @covers ::clone
     */
    public function test_clone(): void {
        $this->setAdminUser();
        $workflow1 = workflow::create(
            $this->type,
            $this->form,
            'kia ora',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );
        $this->assertNotEmpty($workflow1->id);

        $container = (approval_container::from_id($workflow1->course_id))->to_record();
        unset($container->id);

        // Create event sink
        $sink = $this->redirectEvents();

        $workflow = $workflow1->clone(
            'Be kind',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );

        $this->assertNotEmpty($workflow->id);
        $this->assertEquals($workflow->workflow_type_id, $this->type->id);
        $this->assertEquals($workflow->form->id, $this->form->id);
        $this->assertNull($workflow->template);
        $this->assertEquals('Be kind', $workflow->name);
        $this->assertTrue($workflow->active);

        $this->assertLessThanOrEqual($workflow->updated, $workflow->created);
        $this->assertFalse($workflow->to_be_deleted);
        $this->assertCount(1, $workflow->versions);
        $this->assertEquals($this->form->latest_version->id, $workflow->latest_version->form_version_id);
        $this->assertEquals(status::DRAFT, $workflow->latest_version->status);

        // Assert event triggered.
        $events = $sink->get_events();
        $has_event_triggered = array_filter($events, function (base $event) {
            return $event instanceof workflow_cloned;
        });

        $this->assertCount(1, $has_event_triggered, 'Duplicate events fired');
    }

    public function test_new_workflow_has_active_default_assignment() {
        // Given a newly created workflow
        $workflow = $this->create_workflow('good workflow', [status::DRAFT]);

        // Then the default assignment should be active.
        $this->assertTrue($workflow->default_assignment->is_active());
    }

    public function test_on_workflow_version_publish_draft_override_assignments_are_activated() {
        // Given a workflow with a draft workflow version
        // And draft & archived override assignments.
        $workflow = $this->create_workflow('good workflow', [status::DRAFT]);

        /** @var assignment[] $draft_override_assignments*/
        $draft_override_assignments = [];
        /** @var assignment[] $archived_override_assignments*/
        $archived_override_assignments = [];

        for ($i = 0; $i < 2; $i++) {
            $draft_override_assignments[] = assignment::create(
                $workflow->course_id,
                assignment_type\cohort::get_code(),
                $this->getDataGenerator()->create_cohort()->id,
                false
            );
            $archived_assignment = assignment::create(
                $workflow->course_id,
                assignment_type\cohort::get_code(),
                $this->getDataGenerator()->create_cohort()->id,
                false
            );
            $archived_assignment->archive();
            $archived_override_assignments[] = $archived_assignment;
        }

        // When the workflow version is published
        $workflow->publish($workflow->latest_version);

        // Then all draft override assignments are activated
        foreach ($draft_override_assignments as $assignment) {
            $assignment->refresh();
            $this->assertTrue($assignment->is_active());
        }

        // And all archived override assignments remain archived
        foreach ($archived_override_assignments as $assignment) {
            $assignment->refresh();
            $this->assertTrue($assignment->is_archived());
        }
    }

    /**
     * @covers ::archive
     */
    public function test_archive_fails_on_non_existing_workflow(): void {
        $workflow = $this->create_workflow('ghost workflow', [status::DRAFT]);
        $workflow->delete();
        $this->assertFalse($workflow->exists());
        try {
            $workflow->archive();
            $this->fail('model_exception expected');
        } catch (model_exception $ex) {
            $this->assertStringContainsString('The workflow no longer exists', $ex->getMessage());
        }
    }

    /**
     * @covers ::archive
     */
    public function test_archive_succeeds_in_one_version(): void {
        $workflow = $this->create_workflow('good workflow', [status::ACTIVE]);
        $this->assertEquals('Active', $workflow->latest_version->status_label);

        // Create event sink
        $sink = $this->redirectEvents();
        $workflow->archive();
        $this->assertEquals('Archived', $workflow->latest_version->status_label);

        $events = $sink->get_events();
        $has_event_triggered = array_filter($events, function (base $event) {
            return $event instanceof workflow_version_archived;
        });

        $this->assertCount(1, $has_event_triggered, 'Duplicate events fired');
    }

    /**
     * @return array
     */
    public function data_archive_fails_on_one_version(): array {
        return [
            'draft' => [status::DRAFT, 'Draft'],
            'archived' => [status::ARCHIVED, 'Archived'],
        ];
    }

    /**
     * @param integer $status
     * @param string $status_label
     * @covers ::archive
     * @dataProvider data_archive_fails_on_one_version
     */
    public function test_archive_fails_on_one_version(int $status, string $status_label): void {
        $workflow = $this->create_workflow('bad workflow', [$status]);
        $this->assertEquals($status_label, $workflow->latest_version->status_label);
        try {
            $workflow->archive();
            $this->fail('model_exception expected');
        } catch (model_exception $ex) {
            $this->assertStringContainsString('Cannot archive workflow because it is not active', $ex->getMessage());
        }
    }

    /**
     * @return array
     */
    public function data_archive_succeeds_in_mixed_versions(): array {
        return [
            'active, archived' => [
                [status::ACTIVE, status::ARCHIVED],
                ['Active', 'Archived'],
                ['Archived', 'Archived'],
            ],
            'active, draft, active' => [
                [status::ACTIVE, status::DRAFT, status::ACTIVE],
                ['Active', 'Draft', 'Active'],
                ['Archived', 'Draft', 'Archived'],
            ],
        ];
    }

    /**
     * @param array $initial_statuses
     * @param array $first_statuses
     * @param array $final_statuses
     * @covers ::archive
     * @dataProvider data_archive_succeeds_in_mixed_versions
     */
    public function test_archive_succeeds_in_mixed_versions(
        array $initial_statuses,
        array $first_statuses,
        array $final_statuses
    ): void {
        $initial_statuses = array_reverse($initial_statuses);
        $workflow = $this->create_workflow('test workflow', $initial_statuses);
        $statuses = $this->get_workflow_version_statuses($workflow);
        $this->assertEquals($first_statuses, $statuses);

        $workflow->archive();
        $statuses = $this->get_workflow_version_statuses($workflow);
        $this->assertEquals($final_statuses, $statuses);
    }

    /**
     * @covers ::archive
     */
    public function test_archive_fails_on_mixed_versions(): void {
        $workflow = $this->create_workflow('bad workflow', [status::DRAFT, status::ARCHIVED]);
        try {
            $workflow->archive();
            $this->fail('model_exception expected');
        } catch (model_exception $ex) {
            $this->assertStringContainsString('Cannot archive workflow because it is not active', $ex->getMessage());
        }
    }

    /**
     * @covers ::archive
     */
    public function test_unarchive_fails_on_non_existing_workflow(): void {
        $workflow = $this->create_workflow('ghost workflow', [status::DRAFT]);
        $workflow->delete();
        $this->assertFalse($workflow->exists());
        try {
            $workflow->unarchive();
            $this->fail('model_exception expected');
        } catch (model_exception $ex) {
            $this->assertStringContainsString('The workflow no longer exists', $ex->getMessage());
        }
    }

    /**
     * @covers ::unarchive
     */
    public function test_unarchive_succeeds_in_one_version(): void {
        $workflow = $this->create_workflow('good workflow', [status::ARCHIVED]);

        // Events are triggered.
        $sink = $this->redirectEvents();
        $this->assertEquals('Archived', $workflow->latest_version->status_label);
        $workflow->unarchive();
        $this->assertEquals('Active', $workflow->latest_version->status_label);

        $events = $sink->get_events();
        $has_event_triggered = array_filter($events, function (base $event) {
            return $event instanceof workflow_version_unarchived;
        });

        $this->assertCount(1, $has_event_triggered, 'Duplicate events fired');
    }

    /**
     * @return array
     */
    public function data_unarchive_fails_on_one_version(): array {
        return [
            'draft' => [status::DRAFT, 'Draft', 'Cannot unarchive workflow because it is not archived'],
            'active' => [status::ACTIVE, 'Active', 'Cannot unarchive workflow because it is already active'],
        ];
    }

    /**
     * @covers ::unarchive
     *
     * @param integer $status
     * @param string $status_label
     * @param string $exception_message
     * @dataProvider data_unarchive_fails_on_one_version
     */
    public function test_unarchive_fails_on_one_version(int $status, string $status_label, string $exception_message): void {
        $workflow = $this->create_workflow('bad workflow', [$status]);
        $this->assertEquals($status_label, $workflow->latest_version->status_label);
        try {
            $workflow->unarchive();
            $this->fail('model_exception expected');
        } catch (model_exception $ex) {
            $this->assertStringContainsString($exception_message, $ex->getMessage());
        }
    }

    /**
     * @return array
     */
    public function data_unarchive_succeeds_in_mixed_versions(): array {
        return [
            'draft, archived' => [
                [status::DRAFT, status::ARCHIVED],
                ['Draft', 'Archived'],
                ['Draft', 'Active'],
            ],
            'archived, archived' => [
                [status::ARCHIVED, status::ARCHIVED],
                ['Archived', 'Archived'],
                ['Active', 'Archived'],
            ],
            'archived, draft' => [
                [status::ARCHIVED, status::DRAFT],
                ['Archived', 'Draft'],
                ['Active', 'Draft'],
            ],
            'archived, draft, archived' => [
                [status::ARCHIVED, status::DRAFT, status::ARCHIVED],
                ['Archived', 'Draft', 'Archived'],
                ['Active', 'Draft', 'Archived'],
            ]
        ];
    }

    /**
     * @covers ::unarchive
     *
     * @param array $initial_statuses
     * @param array $first_statuses
     * @param array $final_statuses
     * @dataProvider data_unarchive_succeeds_in_mixed_versions
     */
    public function test_unarchive_succeeds_in_mixed_versions(
        array $initial_statuses,
        array $first_statuses,
        array $final_statuses
    ): void {
        $initial_statuses = array_reverse($initial_statuses);
        $workflow = $this->create_workflow('good workflow', $initial_statuses);
        $statuses = $this->get_workflow_version_statuses($workflow);
        $this->assertEquals($first_statuses, $statuses);

        $workflow->unarchive();
        $statuses = $this->get_workflow_version_statuses($workflow);
        $this->assertEquals($final_statuses, $statuses);
    }

    public function data_unarchive_fails_on_mixed_versions(): array {
        return [
            'archived, active' => [
                [status::ACTIVE, status::ARCHIVED],
            ],
            'active, archived' => [
                [status::ARCHIVED, status::ACTIVE]
            ]
        ];
    }

    /**
     * @covers ::unarchive
     *
     * @param array $initial_statuses
     * @dataProvider data_unarchive_fails_on_mixed_versions
     */
    public function test_unarchive_fails_on_mixed_versions(array $initial_statuses): void {
        $workflow = $this->create_workflow('bad workflow', $initial_statuses);
        try {
            $workflow->unarchive();
            $this->fail('model_exception expected');
        } catch (model_exception $ex) {
            $this->assertStringContainsString('Cannot unarchive workflow because it is already active', $ex->getMessage());
        }
    }

    /**
     * @param string $name
     * @param integer[] $statuses
     * @return workflow
     */
    private function create_workflow(string $name, array $statuses): workflow {
        $workflow = workflow::create(
            $this->type,
            $this->form,
            $name,
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id,
            $name
        );

        // Delete workflow version created by workflow_model::create()
        workflow_version_entity::repository()->where('workflow_id', $workflow->id)->delete();

        foreach ($statuses as $status) {
            $workflow_version = new workflow_version_entity();
            $workflow_version->workflow_id = $workflow->id;
            $workflow_version->form_version_id = $this->form->latest_version->id;
            $workflow_version->status = $status;
            $workflow_version->save();
        }
        $workflow->refresh(true);

        return $workflow;
    }

    /**
     * @param workflow $workflow
     * @return string[]
     */
    private function get_workflow_version_statuses(workflow $workflow): array {
        // workflow->versions are in chronological order
        return array_reverse(
            $workflow->versions->map(function (workflow_version $version) {
                return $version->status_label;
            })->all(false)
        );
    }
}
