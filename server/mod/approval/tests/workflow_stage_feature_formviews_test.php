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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package mod_approval
 */

use mod_approval\exception\model_exception;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\form\form;
use mod_approval\model\form\form_version;
use mod_approval\model\workflow\stage_feature\formviews;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\stage_type\finished;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_formview;
use mod_approval\model\workflow\workflow_type;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\workflow\stage_feature\formviews
 */
class mod_approval_workflow_stage_feature_formviews_testcase extends mod_approval_testcase {

    private $formviews_feature;

    private $stage;

    private $workflow;

    public function setUp(): void {
        $this->setAdminUser();
        $form = form::create('simple', 'test form');
        $json_schema = file_get_contents(__DIR__ . "/fixtures/schema/formview_management.json");
        form_version::create($form, 'test form version', $json_schema);
        $this->workflow = $workflow = workflow::create(
            workflow_type::create('test workflow type'),
            $form,
            'Test workflow',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );

        $stage = workflow_stage::create($workflow->latest_version, 'Start', form_submission::get_enum());
        workflow_stage::create($workflow->latest_version, 'Start', finished::get_enum());
        $this->erase_formviews_from_stages($workflow->latest_version);
        $this->formviews_feature = new formviews($stage);
        $this->stage = $stage;
        parent::setUp();
    }

    protected function tearDown(): void {
        $this->formviews_feature = null;
        $this->stage = null;
        $this->workflow = null;
        parent::tearDown();
    }

    public function test_configure_for_non_draft_workflow() {
        $version = $this->stage->workflow_version;
        $this->stage->workflow_version->workflow->publish($version);
        $this->stage->refresh(true);

        $this->expectException(model_exception::class);
        $this->expectExceptionMessage('Can not configure formviews for non-draft workflow');
        $this->formviews_feature->configure('agency_code', formviews::EDITABLE);
    }

    public function test_configure_make_field_editable() {
        $this->formviews_feature->configure('agency_code', formviews::EDITABLE);
        $this->stage->refresh(true);
        $formviews = $this->stage->formviews;

        /** @var workflow_stage_formview $agency_code_formview*/
        $agency_code_formview = $formviews->find('field_key', 'agency_code');

        // Assert agency_code is created.
        $this->assertEquals('agency_code', $agency_code_formview->field_key);
        $this->assertFalse($agency_code_formview->disabled);
        $this->assertFalse($agency_code_formview->required);

        // Configuring the same field doesn't create duplicate records.
        $this->formviews_feature->configure('agency_code', formviews::EDITABLE);
        $this->stage->refresh(true);
        $this->assertCount(1, $this->stage->formviews->filter('field_key', 'agency_code')->all());
    }

    public function test_configure_make_field_editable_and_required() {
        $this->formviews_feature->configure('agency_code', formviews::EDITABLE_AND_REQUIRED);
        $this->stage->refresh(true);
        $formviews = $this->stage->formviews;

        /** @var workflow_stage_formview $agency_code_formview*/
        $agency_code_formview = $formviews->find('field_key', 'agency_code');

        // Checks the columns are configured as editable and required.
        $this->assertEquals('agency_code', $agency_code_formview->field_key);
        $this->assertFalse($agency_code_formview->disabled);
        $this->assertTrue($agency_code_formview->required);

        // Configuring the same field doesn't create duplicate records.
        $this->formviews_feature->configure('agency_code', formviews::EDITABLE_AND_REQUIRED);
        $this->stage->refresh(true);
        $this->assertCount(1, $this->stage->formviews->filter('field_key', 'agency_code')->all());
    }

    public function test_configure_make_field_read_only() {
        $this->formviews_feature->configure('agency_code', formviews::READ_ONLY);
        $this->stage->refresh(true);
        $formviews = $this->stage->formviews;

        /** @var workflow_stage_formview $agency_code_formview*/
        $agency_code_formview = $formviews->find('field_key', 'agency_code');

        // Checks the columns are configured as read_only.
        $this->assertEquals('agency_code', $agency_code_formview->field_key);
        $this->assertTrue($agency_code_formview->disabled);
        $this->assertFalse($agency_code_formview->required);

        // Configuring the same field doesn't create duplicate records.
        $this->formviews_feature->configure('agency_code', formviews::READ_ONLY);
        $this->stage->refresh(true);
        $this->assertCount(1, $this->stage->formviews->filter('field_key', 'agency_code')->all());
    }

    public function test_configure_make_field_hidden() {
        // Initially fileds created by default
        $this->assertNull($this->stage->formviews->find('field_key', 'agency_code'));

        $this->formviews_feature->configure('agency_code', formviews::HIDDEN);
        $this->stage->refresh(true);

        /** @var workflow_stage_formview $agency_code_formview*/
        $agency_code_formview = $this->stage->formviews->find('field_key', 'agency_code');

        // Still doesn't exist.
        $this->assertNull($agency_code_formview);
    }

    public function test_configure_invalid_field() {
        $this->expectExceptionMessage('unknown_name field key not available in schema');
        $this->expectException(coding_exception::class);
        $this->formviews_feature->configure('unknown_name', formviews::EDITABLE);

    }

    public function test_resolve_visibility_enum() {
        $this->assertEquals(formviews::EDITABLE, formviews::resolve_visibility_enum(false, false));
        $this->assertEquals(formviews::EDITABLE_AND_REQUIRED, formviews::resolve_visibility_enum(true, false));
        $this->assertEquals(formviews::READ_ONLY, formviews::resolve_visibility_enum(false, true));
    }

    public function test_add_default() {
        // Create some stages and check formviews
        $stage = workflow_stage::create($this->workflow->latest_version, 'Start2', form_submission::get_enum());
        $this->assertEquals(7, $stage->formviews->count());

        // Disable some
        $formviews_feature = new formviews($stage);
        $formviews_feature->configure('agency_code', formviews::HIDDEN);
        $stage->refresh(true);
        $this->assertEquals(6, $stage->formviews->count());

        // Next approvals stage create the same formviews as previous one
        $approvals_stage = workflow_stage::create($this->workflow->latest_version, 'Start3', approvals::get_enum());
        $this->assertEquals(6, $approvals_stage->formviews->count());

        // Next form_submission stage create just 1 missing formview from previous form_submission stage
        $follow_up_stage = workflow_stage::create($this->workflow->latest_version, 'Start4', form_submission::get_enum());
        $this->assertEquals(1, $follow_up_stage->formviews->count());
    }
}
