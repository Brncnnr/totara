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

use core\orm\query\builder;
use core_phpunit\testcase;
use mod_approval\entity\workflow\workflow_stage_formview as workflow_stage_formview_entity;
use mod_approval\exception\model_exception;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\form\form;
use mod_approval\model\form\form_version;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_version;
use mod_approval\model\workflow\workflow_stage_formview;
use mod_approval\model\workflow\workflow_type;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\workflow\workflow_stage_formview
 */
class mod_approval_workflow_stage_formview_model_testcase extends testcase {
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
        $workflow_stage = workflow_stage::create($workflow_version, 'kia kaha', form_submission::get_enum());
        $formview = workflow_stage_formview::create($workflow_stage, 'request', true, true, 'Test this');
        $this->assertNotEmpty($formview->id);
        $this->assertEquals($workflow_stage->id, $formview->workflow_stage->id);
        $this->assertEquals('request', $formview->field_key);
        $this->assertTrue($formview->required);
        // Funny, required AND disabled. Might want to prevent that...
        $this->assertTrue($formview->disabled);
        $this->assertEquals('Test this', $formview->default_value);
        $this->assertTrue($formview->active);
        $this->assertGreaterThanOrEqual($time, $formview->created);
        $this->assertLessThanOrEqual($formview->updated, $formview->created);
    }

    /**
     * @covers ::clone
     */
    public function test_clone(): void {
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
        $workflow_stage = workflow_stage::create($workflow_version, 'kia kaha', form_submission::get_enum());
        $formview_current = workflow_stage_formview::create($workflow_stage, 'request', true, true, 'Test this');

        $formview = $formview_current->clone($workflow_stage);

        $this->assertNotEmpty($formview->id);
        $this->assertEquals($workflow_stage->id, $formview->workflow_stage->id);
        $this->assertEquals('request', $formview->field_key);
        $this->assertTrue($formview->required);
        // Funny, required AND disabled. Might want to prevent that...
        $this->assertTrue($formview->disabled);
        $this->assertEquals('Test this', $formview->default_value);
        $this->assertTrue($formview->active);
        $this->assertGreaterThanOrEqual($time, $formview->created);
        $this->assertLessThanOrEqual($formview->updated, $formview->created);
    }

    /**
     * @covers ::create
     */
    public function test_create_with_inactive_workflow_stage(): void {
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
        $workflow_stage->deactivate();
        try {
            $formview = workflow_stage_formview::create($workflow_stage, 'request', false, false, '');
            $this->fail('Expected model_exception');
        } catch (model_exception $e) {
            $this->assertEquals('Workflow stage must be active', $e->debuginfo);
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
        $form_schema = file_get_contents(__DIR__ . '/fixtures/form/test_form.json');
        $form_version = form_version::create($this->form, '1', $form_schema);
        $workflow_version = workflow_version::create($workflow, $form_version);
        $workflow_stage = workflow_stage::create($workflow_version, 'kia kaha', form_submission::get_enum());
        $formview = workflow_stage_formview::create($workflow_stage, 'request', false, false, '');
        $this->assertTrue($formview->active);
        $formview->deactivate();
        $this->assertFalse($formview->active);
        $formview->activate();
        $this->assertTrue($formview->active);
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
        $formview = workflow_stage_formview::create($workflow_stage, 'request', false, false, '');

        $this->assertNotEmpty($formview->id);
        $this->assertEquals('request', $formview->field_key);
        builder::table(workflow_stage_formview_entity::TABLE)->update(['field_key' => 'comment']);
        $formview->refresh();
        $this->assertEquals('comment', $formview->field_key);
    }
}
