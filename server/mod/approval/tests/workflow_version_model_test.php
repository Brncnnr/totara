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

use core\orm\query\builder;
use mod_approval\entity\workflow\workflow_version as workflow_version_entity;
use mod_approval\exception\model_exception;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\form\form;
use mod_approval\model\form\form_version;
use mod_approval\model\status;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_version;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_type;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\workflow\workflow_version
 */
class mod_approval_workflow_version_model_testcase extends mod_approval_testcase {
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
     * @covers ::create
     * @covers ::is_draft
     */
    public function test_create(): void {
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
        $time = time();
        $workflow_version = workflow_version::create($workflow, $form_version);
        $this->assertNotEmpty($workflow_version->id);
        $this->assertEquals($workflow->id, $workflow_version->workflow->id);
        $this->assertEquals($form_version->id, $workflow_version->form_version->id);
        $this->assertEquals(status::DRAFT, $workflow_version->status);
        $this->assertGreaterThanOrEqual($time, $workflow_version->created);
        $this->assertLessThanOrEqual($workflow_version->updated, $workflow_version->created);
        $this->assertTrue($workflow_version->is_draft());
        $this->assertFalse($workflow_version->is_active());
        $this->assertFalse($workflow_version->is_archived());
    }

    /**
     * @covers ::create
     */
    public function test_creation_fails_with_inactive_workflow(): void {
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
        $workflow->deactivate();
        try {
            workflow_version::create($workflow, $form_version);
            $this->fail('model_exception expected');
        } catch (model_exception $ex) {
            $this->assertEquals('Workflow must be active', $ex->debuginfo);
        }
    }

    /**
     * @covers ::create
     */
    public function test_creation_fails_with_draft_form_version(): void {
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
        $form_version = form_version::create($this->form, '1', $form_schema, status::DRAFT);
        try {
            workflow_version::create($workflow, $form_version);
            $this->fail('model_exception expected');
        } catch (model_exception $ex) {
            $this->assertEquals('Form version must be active', $ex->debuginfo);
        }
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
        $form_version1 = form_version::create($this->form, '1', $form_schema);
        $form_version2 = form_version::create($this->form, '2', $form_schema);
        $workflow_version = workflow_version::create($workflow, $form_version1);
        $this->assertNotEmpty($workflow_version->id);
        $this->assertEquals($form_version1->id, $workflow_version->form_version->id);
        builder::table(workflow_version_entity::TABLE)->update(['form_version_id' => $form_version2->id]);
        $workflow_version->refresh();
        $this->assertEquals($form_version2->id, $workflow_version->form_version_id);
        // not yet reloaded
        $this->assertEquals($form_version1->id, $workflow_version->form_version->id);
        $workflow_version->refresh(true);
        $this->assertEquals($form_version2->id, $workflow_version->form_version->id);
    }

    /**
     * @covers ::activate
     * @covers ::is_active
     */
    public function test_activate(): void {
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
        $form_version->activate();
        $workflow_version = workflow_version::create($workflow, $form_version);
        $workflow_version->activate();
        $this->assertFalse($workflow_version->is_draft());
        $this->assertTrue($workflow_version->is_active());
        $this->assertFalse($workflow_version->is_archived());
    }

    /**
     * @covers ::archive
     * @covers ::is_archived
     */
    public function test_archive(): void {
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
        $form_version->activate();
        $workflow_version = workflow_version::create($workflow, $form_version);
        $workflow_version->activate();
        $workflow_version->archive();
        $this->assertFalse($workflow_version->is_draft());
        $this->assertFalse($workflow_version->is_active());
        $this->assertTrue($workflow_version->is_archived());
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
        $workflow_version1 = workflow_version::create($workflow, $form_version);
        $workflow_version2 = workflow_version::create($workflow, $form_version);
        $this->assertNotEmpty($workflow_version1->id);
        $this->assertNotEmpty($workflow_version2->id);
        $workflow_version2->delete();
        $this->assertEmpty($workflow_version2->id);
        $workflow->delete();
        $this->assertFalse($workflow->exists());
        $this->assertFalse(builder::table(workflow_version_entity::TABLE)->exists());
    }

    /**
     * @covers ::delete
     */
    public function test_unable_to_delete_active(): void {
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
        $workflow_version->activate();
        try {
            $workflow_version->delete();
            $this->fail('Expected model_exception');
        } catch (model_exception $e) {
            $this->assertEquals('Only draft objects can be deleted', $e->debuginfo);
        }
    }

    /**
     * @covers ::get_stages
     */
    public function test_get_stages(): void {
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
        $workflow_version1 = workflow_version::create($workflow, $form_version);
        $workflow_version2 = workflow_version::create($workflow, $form_version);
        $this->assertNotEmpty($workflow_version1->id);
        $this->assertNotEmpty($workflow_version2->id);
        workflow_stage::create($workflow_version1, 'ver1stage1', form_submission::get_enum());
        workflow_stage::create($workflow_version1, 'ver1stage2', form_submission::get_enum());
        // NOTE: workflow_version::create should probably append itself to workflow::versions
        $workflow_version1->refresh();
        $workflow_version2->refresh();
        $this->assertCount(2, $workflow_version1->stages);
        $this->assertCount(0, $workflow_version2->stages);
    }
    /**
     * @covers ::load_active_by_workflow_id
     * @covers ::load_latest_by_workflow_id
     */
    public function test_load_by_workflow_id(): void {
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
        $workflow_version1 = workflow_version::create($workflow, $form_version);
        $workflow_version1->activate();
        $workflow_version2 = workflow_version::create($workflow, $form_version);

        $this->assertEquals($workflow_version1, workflow_version::load_active_by_workflow_id($workflow->id));
        $this->assertEquals($workflow_version2, workflow_version::load_latest_by_workflow_id($workflow->id));
    }

    /**
     * @covers ::has_approval_level
     */
    public function test_has_approval_level(): void {
        $this->setAdminUser();
        $form = form::create('simple', 'form');
        $workflow = workflow::create(
            workflow_type::create('type'),
            $form,
            'workflow',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id,
            '1'
        );
        $form_schema = file_get_contents(__DIR__ . '/fixtures/form/test_form.json');
        $wv1 = workflow_version::create($workflow, form_version::create($form, '1', $form_schema));
        $wv2 = workflow_version::create($workflow, form_version::create($form, '2', $form_schema));
        $stage11 = workflow_stage::create($wv1, 'stage11', approvals::get_enum());
        $stage12 = workflow_stage::create($wv1, 'stage12', approvals::get_enum());
        $stage21 = workflow_stage::create($wv2, 'stage21', approvals::get_enum());
        $level111 = $stage11->add_approval_level('level1');
        $level112 = $stage11->add_approval_level('level2');
        $level121 = $stage12->add_approval_level('level3');
        $level211 = $stage21->add_approval_level( 'level4');
        $this->assertTrue($wv1->has_approval_level($level111->id));
        $this->assertTrue($wv1->has_approval_level($level112->id));
        $this->assertTrue($wv1->has_approval_level($level121->id));
        $this->assertFalse($wv1->has_approval_level($level211->id));
        $this->assertFalse($wv1->has_approval_level(0 * 0));
    }
}
