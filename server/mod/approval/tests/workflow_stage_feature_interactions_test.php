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

use mod_approval\entity\workflow\workflow_stage_interaction as workflow_stage_interaction_entity;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\form\form;
use mod_approval\model\form\form_version;
use mod_approval\model\workflow\stage_feature\interactions;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\stage_type\finished;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\stage_type\waiting;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_interaction;
use mod_approval\model\workflow\workflow_type;
use mod_approval\testing\approval_workflow_test_setup;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\workflow\stage_feature\interactions
 */
class mod_approval_workflow_stage_feature_interactions_testcase extends mod_approval_testcase {

    use approval_workflow_test_setup;

    private $workflow_version;

    public function setUp(): void {
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
        $this->workflow_version = $workflow->latest_version;
        parent::setUp();
    }

    protected function tearDown(): void {
        $this->workflow_version = null;
        parent::tearDown();
    }

    public function stage_types(): array {
        return [
            'approvals' => [new approvals()],
            'finished' => [new finished()],
            'form_submission' => [new form_submission()],
            'waiting' => [new waiting()],
        ];
    }

    /**
     * @dataProvider stage_types
     */
    public function test_add_default(\mod_approval\model\workflow\stage_type\base $stage_type) {
        // Check no interactions.
        $this->assertEquals(0, workflow_stage_interaction_entity::repository()->count());

        // Create a stage entity and model (avoid stage::create).
        $stage = $this->create_stage_via_entity($this->workflow_version->id, 'Test', $stage_type->code, 1);

        // Instantiate the feature and call add_default().
        $interactions_feature = new interactions($stage);
        $interactions_feature->add_default();

        // Check that there is an interaction for each action.
        $actions = $stage_type::get_available_actions();
        $this->assertEquals(count($actions), workflow_stage_interaction_entity::repository()->count());

        // Check that each interaction has the proper default transition.
        $interactions = workflow_stage_interaction_entity::repository()->get()->map_to(workflow_stage_interaction::class);
        foreach ($interactions as $interaction) {
            /* @var workflow_stage_interaction $interaction */
            $action = $interaction->application_action;
            $expected_default_transition = $action::get_default_transition();
            $this->assertEquals(get_class($expected_default_transition), get_class($interaction->default_transition->transition));
        }
    }
}
