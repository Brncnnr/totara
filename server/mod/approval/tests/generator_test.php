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

use container_approval\approval as container_approval;
use container_approval\module\approval_module;
use core_phpunit\testcase;
use mod_approval\entity\application\application;
use mod_approval\entity\application\application_action;
use mod_approval\entity\application\application_activity;
use mod_approval\entity\application\application_submission;
use mod_approval\entity\assignment\assignment;
use mod_approval\entity\assignment\assignment_approver;
use mod_approval\entity\form\form;
use mod_approval\entity\form\form_version;
use mod_approval\entity\workflow\workflow;
use mod_approval\entity\workflow\workflow_stage;
use mod_approval\entity\workflow\workflow_stage_approval_level;
use mod_approval\entity\workflow\workflow_stage_formview;
use mod_approval\entity\workflow\workflow_stage_interaction;
use mod_approval\entity\workflow\workflow_stage_interaction_action;
use mod_approval\entity\workflow\workflow_stage_interaction_transition;
use mod_approval\entity\workflow\workflow_type;
use mod_approval\entity\workflow\workflow_version;
use mod_approval\model\application\application_state;
use mod_approval\model\assignment\approver_type\user;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\form\form_data;
use mod_approval\model\form\form as form_model;
use mod_approval\model\status;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow_stage as workflow_stage_model;
use mod_approval\testing\application_activity_generator_object;
use mod_approval\testing\application_generator_object;
use mod_approval\testing\assignment_generator_object;
use mod_approval\testing\assignment_approver_generator_object;
use mod_approval\testing\approval_workflow_test_setup;
use mod_approval\testing\formview_generator_object;
use mod_approval\testing\workflow_generator_object;
use mod_approval\testing\workflow_stage_interaction_action_generator_object;
use mod_approval\testing\workflow_stage_interaction_transition_generator_object;

/**
 * @coversDefaultClass \mod_approval\testing\generator
 *
 * @group approval_workflow
 */
class mod_approval_generator_testcase extends testcase {

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
     * Generates a course container to use for workflow.
     *
     * @return container_approval
     */
    private function generate_container(): container_approval {
        // Create a container
        $container_data = new stdClass();
        $container_data->fullname = "Generated Workflow Container";
        $container_data->category = container_approval::get_default_category_id();
        return container_approval::create($container_data);
    }

    /**
     * Generates a set entities to use as the start of a workflow (no stages or assignments).
     *
     * @return array[workflow_type, form_version, form, workflow_generator_object, workflow_version, workflow]
     */
    private function generate_test_workflow_entities(): array {
        $generator = $this->generator();

        // Create a workflow_type
        $workflow_type = $generator->create_workflow_type('test');

        // Create a form and version
        $form_version = $generator->create_form_and_version();
        $form = $form_version->form;

        // Create a workflow and version
        $workflow_go = new workflow_generator_object($workflow_type->id, $form->id, $form_version->id);
        $workflow_version = $generator->create_workflow_and_version($workflow_go);
        $workflow = $workflow_version->workflow;

        return [$workflow_type, $form_version, $form, $workflow_go, $workflow_version, $workflow];
    }

    public function test_create_workflow_type(): void {
        $this->setAdminUser();
        $generator = $this->generator();
        $repository = workflow_type::repository();

        // Nothing yet.
        $count = $repository->count();
        $this->assertEquals(0, $count);

        // There is one, named test and active
        $time = time();
        $generator->create_workflow_type('test');
        $this->assertEquals(1, $repository->count());
        $workflow_type = $repository->one();
        $this->assertGreaterThanOrEqual(1, $workflow_type->id);
        $this->assertEquals('test', $workflow_type->name);
        $this->assertEquals(true, $workflow_type->active);
        $this->assertGreaterThanOrEqual($time, $workflow_type->created);

        // Try to create another with the same name
        $new_workflow_type = $generator->create_workflow_type('test');
        $this->assertEquals(1, $repository->count());
        $this->assertEquals($new_workflow_type->id, $workflow_type->id);
        $this->assertEquals('test', $new_workflow_type->name);

        // Now create another with a different name
        $new_workflow_type = $generator->create_workflow_type('best');
        $this->assertEquals(2, $repository->count());
        $this->assertNotEquals($new_workflow_type->id, $workflow_type->id);
        $this->assertEquals('best', $new_workflow_type->name);
    }

    public function test_create_form_and_version() {
        global $CFG;

        $this->setAdminUser();
        $generator = $this->generator();
        $form_repository = form::repository();
        $version_repository = form_version::repository();
        $json_schema = file_get_contents($CFG->dirroot . '/mod/approval/tests/fixtures/form/test_form.json');
        $this->assertNotEmpty($json_schema);

        // Nothing yet.
        $this->assertEquals(0, $form_repository->count());
        $this->assertEquals(0, $version_repository->count());

        // Test defaults
        $time = time();
        $generator->create_form_and_version();
        $this->assertEquals(1, $form_repository->count());
        $this->assertEquals(1, $version_repository->count());
        $form = $form_repository->one();
        $this->assertGreaterThanOrEqual(1, $form->id);
        $this->assertEquals('simple', $form->plugin_name);
        $this->assertEquals('Generated Test Form', $form->title);
        $this->assertEquals(true, $form->active);
        $this->assertGreaterThanOrEqual($time, $form->created);
        $this->assertLessThanOrEqual($form->updated, $form->created);

        $form_version = $version_repository->one();
        $this->assertGreaterThanOrEqual(1, $form_version->id);
        $this->assertEquals($form->id, $form_version->form_id);
        $this->assertEquals('2021030200', $form_version->version);
        $this->assertEquals($json_schema, $form_version->json_schema);
        $this->assertEquals(2, $form_version->status);
        $this->assertGreaterThanOrEqual($time, $form_version->created);
        $this->assertLessThanOrEqual($form_version->updated, $form_version->created);

        // Do it again, nothing changes
        $generator->create_form_and_version();
        $this->assertEquals(1, $form_repository->count());
        $this->assertEquals(1, $version_repository->count());

        // Add a draft form_version
        $new_version = new form_version();
        $new_version->form_id = $form->id;
        $new_version->version = '2021030201';
        $new_version->json_schema = $json_schema;
        $new_version->status = status::DRAFT;
        $new_version->save();
        $this->assertEquals(2, $version_repository->count());

        // Do it again. Should get the active form_version not the draft
        $form_version_redux = $generator->create_form_and_version();
        $this->assertEquals(2, $version_repository->count());
        $this->assertNotEquals($new_version->id, $form_version_redux->id);
        $this->assertEquals($form_version->id, $form_version_redux->id);
        $this->assertEquals($form->id, $form_version_redux->form_id);
        $this->assertLessThanOrEqual($form_version_redux->updated, $form_version_redux->created);
    }

    public function test_create_form_version() {
        global $CFG;

        $this->setAdminUser();
        $generator = $this->generator();
        $form_version_repository = form_version::repository();
        $json_schema = file_get_contents($CFG->dirroot . '/mod/approval/tests/fixtures/form/test_form.json');
        $this->assertNotEmpty($json_schema);

        // Nothing yet.
        $this->assertEquals(0, $form_version_repository->count());

        $form = form_model::create('simple', 'New Test Form');
        $version = '2021041300';
        $status = status::ACTIVE;
        $time = time();
        $form_version = $generator->create_form_version($form->id, $version, $json_schema, $status);
        $this->assertEquals(2, $form_version_repository->count());

        $this->assertGreaterThanOrEqual(1, $form_version->id);
        $this->assertEquals($form->id, $form_version->form_id);
        $this->assertEquals('2021041300', $form_version->version);
        $this->assertEquals($json_schema, $form_version->json_schema);
        $this->assertEquals($status, $form_version->status);
        $this->assertGreaterThanOrEqual($time, $form_version->created);
        $this->assertLessThanOrEqual($form_version->updated, $form_version->created);
    }

    public function test_create_workflow_and_version() {
        $this->setAdminUser();
        $generator = $this->generator();
        $workflow_repository = workflow::repository();
        $version_repository = workflow_version::repository();

        // Create a workflow_type
        $workflow_type = $generator->create_workflow_type('test');

        // Create a form and version
        $form_version = $generator->create_form_and_version();
        $form = $form_version->form;

        // Create a container
        $container = $this->generate_container();

        // Create a workflow generator object from a pre-existing container
        $workflow_go = new workflow_generator_object($workflow_type->id, $form->id, $form_version->id);
        $workflow_go->course_id = $container->id;

        // Nothing yet.
        $this->assertEquals(0, $workflow_repository->count());
        $this->assertEquals(0, $version_repository->count());

        // Test defaults
        $time = time();
        $generator->create_workflow_and_version($workflow_go);
        $this->assertEquals(1, $workflow_repository->count());
        $this->assertEquals(1, $version_repository->count());

        $workflow = $workflow_repository->one();
        $this->assertGreaterThanOrEqual(1, $workflow->id);
        $this->assertEquals($container->id, $workflow->course_id);
        $this->assertEquals($workflow_type->id, $workflow->workflow_type_id);
        $this->assertEquals($workflow_go->name, $workflow->name);
        $this->assertEquals($workflow_go->description, $workflow->description);
        $this->assertEquals($workflow_go->id_number, $workflow->id_number);
        $this->assertEquals($form->id, $workflow->form_id);
        $this->assertEquals(0, $workflow->template_id);
        $this->assertEquals(true, $workflow->active);
        $this->assertGreaterThanOrEqual($time, $workflow->created);
        $this->assertLessThanOrEqual($workflow->updated, $workflow->created);
        $this->assertEquals(false, $workflow->to_be_deleted);
        // Test container context
        $context = context_course::instance($workflow->course_id);

        $workflow_version = $version_repository->one();
        $this->assertGreaterThanOrEqual(1, $workflow_version->id);
        $this->assertEquals($workflow->id, $workflow_version->workflow_id);
        $this->assertEquals($form_version->id, $workflow_version->form_version_id);
        $this->assertEquals(2, $workflow_version->status);
        $this->assertGreaterThanOrEqual($time, $workflow_version->created);
        $this->assertLessThanOrEqual($workflow_version->updated, $workflow_version->created);

        // Do it again, nothing changes
        $generator->create_workflow_and_version($workflow_go);
        $this->assertEquals(1, $workflow_repository->count());
        $this->assertEquals(1, $version_repository->count());

        // Add a draft workflow_version with no pre-existing container
        $workflow_go->status = status::DRAFT;
        $workflow_go->course_id = null;
        $workflow_go->id_number .= '1';
        $draft_version = $generator->create_workflow_and_version($workflow_go);
        $draft_workflow = $draft_version->workflow;
        $this->assertEquals(2, $workflow_repository->count());
        $this->assertEquals(2, $version_repository->count());
        $this->assertNotEquals($workflow_version->id, $draft_version->id);
        $this->assertNotEquals($workflow->id, $draft_workflow->id);
        $this->assertEquals($form_version->id, $draft_version->form_version_id);
        $this->assertEquals(1, $draft_version->status);
        // Test container context
        $context = context_course::instance($draft_workflow->course_id);

        // Refetch the active workflow_version
        $workflow_go->status = status::ACTIVE;
        $workflow_go->course_id = $container->id;
        $workflow_version_redux = $generator->create_workflow_and_version($workflow_go);
        $this->assertEquals(2, $workflow_repository->count());
        $this->assertEquals(2, $version_repository->count());
        $this->assertNotEquals($workflow_version_redux->id, $draft_version->id);
        $this->assertEquals($workflow_version_redux->id, $workflow_version->id);
        $this->assertLessThanOrEqual($workflow_version_redux->updated, $workflow_version_redux->created);
    }

    public function test_creat_workflow_version() {

        $this->setAdminUser();
        $generator = $this->generator();
        $workflow_version_repository = workflow_version::repository();
        $workflow = $generator->create_simple_request_workflow();
        $form_version = $generator->create_form_and_version('Not simple', 'Complicated form');
        // We have one
        $this->assertEquals(1, $workflow_version_repository->count());

        $time = time();
        $workflow_version = $generator->create_workflow_version($workflow->id, $form_version->id);
        // We have new workflow version
        $this->assertEquals(2, $workflow_version_repository->count());

        $this->assertGreaterThanOrEqual(1, $workflow_version->id);
        $this->assertEquals($workflow->id, $workflow_version->workflow_id);
        $this->assertEquals($form_version->id, $workflow_version->form_version_id);
        $this->assertEquals(status::DRAFT, $workflow_version->status);
        $this->assertGreaterThanOrEqual($time, $workflow_version->created);
        $this->assertLessThanOrEqual($workflow_version->updated, $workflow_version->created);
    }

    public function test_create_workflow_stage() {
        $this->setAdminUser();
        $generator = $this->generator();
        $stage_repository = workflow_stage::repository();

        // Generate test workflow entities
        list($workflow_type, $form_version, $form, $workflow_go, $workflow_version, $workflow) = $this->generate_test_workflow_entities();

        /** @var workflow_version $workflow_version */
        $workflow_version->status = status::DRAFT;
        $workflow_version->save();

        // Nothing yet.
        $this->assertEquals(0, $stage_repository->count());

        // Test defaults
        $time = time();
        $generator->create_workflow_stage($workflow_version->id, 'Test Stage', form_submission::get_enum());
        $this->assertEquals(1, $stage_repository->count());
        $stage = $stage_repository->one();
        $this->assertGreaterThanOrEqual(1, $stage->id);
        $this->assertEquals($workflow_version->id, $stage->workflow_version_id);
        $this->assertEquals('Test Stage', $stage->name);
        $this->assertEquals(1, $stage->sortorder);
        $this->assertEquals(true, $stage->active);
        $this->assertGreaterThanOrEqual($time, $stage->created);
        $this->assertLessThanOrEqual($stage->updated, $stage->created);

        // Do it again, nothing changes
        $generator->create_workflow_stage($workflow_version->id, 'Test Stage', form_submission::get_enum());
        $this->assertEquals(1, $stage_repository->count());

        // Add another stage with a different name
        $new_stage = $generator->create_workflow_stage($workflow_version->id, 'New Stage', form_submission::get_enum());
        $this->assertEquals(2, $stage_repository->count());
        $this->assertNotEquals($stage->id, $new_stage->id);
        $this->assertEquals(2, $workflow_version->stages()->count());

        // Refetch the same generated stage
        $stage_redux = $generator->create_workflow_stage($workflow_version->id, 'Test Stage', form_submission::get_enum());
        $this->assertEquals(2, $stage_repository->count());
        $this->assertNotEquals($stage_redux->id, $new_stage->id);
        $this->assertEquals($stage_redux->id, $stage->id);
        $this->assertLessThanOrEqual($stage_redux->updated, $stage_redux->created);
    }

    public function test_create_approval_level() {
        $this->setAdminUser();
        $generator = $this->generator();
        $approval_level_repository = workflow_stage_approval_level::repository();

        // Generate test workflow entities
        list($workflow_type, $form_version, $form, $workflow_go, $workflow_version, $workflow) = $this->generate_test_workflow_entities();

        /** @var workflow_version $workflow_version */
        $workflow_version->status = status::DRAFT;
        $workflow_version->save();

        // Create a stage
        $stage = $generator->create_workflow_stage($workflow_version->id, 'Test Stage', form_submission::get_enum());

        // Nothing yet.
        $this->assertEquals(0, $approval_level_repository->count());

        // Test defaults
        $time = time();
        $generator->create_approval_level($stage->id, 'Level 1', 1);
        $this->assertEquals(1, $approval_level_repository->count());
        $approval_level = $approval_level_repository->one();
        $this->assertGreaterThanOrEqual(1, $approval_level->id);
        $this->assertEquals($stage->id, $approval_level->workflow_stage_id);
        $this->assertEquals('Level 1', $approval_level->name);
        $this->assertEquals(1, $approval_level->sortorder);
        $this->assertEquals(true, $approval_level->active);
        $this->assertGreaterThanOrEqual($time, $approval_level->created);
        $this->assertLessThanOrEqual($approval_level->updated, $approval_level->created);

        // Do it again, nothing changes
        $generator->create_approval_level($stage->id, 'Level 1', 42);
        $this->assertEquals(1, $approval_level_repository->count());

        // Add another approval_level with a different name
        $new_approval_level = $generator->create_approval_level($stage->id, 'Level 2', 2);
        $this->assertEquals(2, $approval_level_repository->count());
        $this->assertNotEquals($approval_level->id, $new_approval_level->id);
        $this->assertEquals(2, $stage->approval_levels()->count());

        // Refetch the same generated approval_level
        $approval_level_redux = $generator->create_approval_level($stage->id, 'Level 1', 42);
        $this->assertEquals(2, $approval_level_repository->count());
        $this->assertNotEquals($approval_level_redux->id, $new_approval_level->id);
        $this->assertEquals($approval_level_redux->id, $approval_level->id);
        $this->assertLessThanOrEqual($approval_level_redux->updated, $approval_level_redux->created);
    }

    public function test_create_stage_formview() {
        $this->setAdminUser();
        $generator = $this->generator();
        $stage_formview_repository = workflow_stage_formview::repository();

        // Generate test workflow entities
        list($workflow_type, $form_version, $form, $workflow_go, $workflow_version, $workflow) = $this->generate_test_workflow_entities();

        /** @var workflow_version $workflow_version */
        $workflow_version->status = status::DRAFT;
        $workflow_version->save();

        // Create a stage
        $stage = $generator->create_workflow_stage($workflow_version->id, 'Test Stage', form_submission::get_enum());

        // Delete default formviews
        $default_formviews = $stage->formviews->all();
        foreach ($default_formviews as $formview) {
            $formview->delete();
        }

        // Nothing yet.
        $this->assertEquals(0, $stage_formview_repository->count());

        // Create formview.
        $formview_go = new formview_generator_object('agency_code', $stage->id);
        $formview_go->active = false;
        $formview_go->default_value = '1098';
        $formview_go->required = true;
        $stage_formview = $generator->create_formview($formview_go);

        // Test defaults
        $this->assertEquals(1, $stage_formview_repository->count());
        $this->assertGreaterThanOrEqual(1, $stage_formview->id);
        $this->assertEquals($stage->id, $stage_formview->workflow_stage_id);
        $this->assertEquals('agency_code', $stage_formview->field_key);
        $this->assertEquals(false, $stage_formview->active);
        $this->assertEquals(false, $stage_formview->disabled);
        $this->assertEquals('1098', $stage_formview->default_value);

        // Add another stage_formview with a different field_key
        $formview_go = new formview_generator_object('applicant_name', $stage->id);
        $formview_go->default_value = 'Gordon Freeman';
        $new_stage_formview = $generator->create_formview($formview_go);
        $this->assertEquals(2, $stage_formview_repository->count());
        $this->assertNotEquals($stage_formview->id, $new_stage_formview->id);
    }

    public function test_create_stage_interaction() {
        $this->setAdminUser();
        $generator = $this->generator();
        $stage_interaction_repository = workflow_stage_interaction::repository();

        // Generate test workflow entities
        list($workflow_type, $form_version, $form, $workflow_go, $workflow_version, $workflow) = $this->generate_test_workflow_entities();

        /** @var workflow_version $workflow_version */
        $workflow_version->status = status::DRAFT;
        $workflow_version->save();

        // Create a stage entity - can't use model::create() because default interactions would be created.
        $stage = $this->create_stage_via_entity($workflow_version->id, 'Test Stage', form_submission::get_code(), 20);

        // Nothing yet.
        $this->assertEquals(0, $stage_interaction_repository->count());

        // Create stage_interaction.
        $action_code = \mod_approval\model\application\action\approve::get_code();

        $stage_interaction = $generator->create_workflow_stage_interaction($stage->id, $action_code);

        // Test defaults
        $this->assertEquals(1, $stage_interaction_repository->count());
        $this->assertGreaterThanOrEqual(1, $stage_interaction->id);
        $this->assertEquals($stage->id, $stage_interaction->workflow_stage_id);
        $this->assertEquals($action_code, $stage_interaction->action_code);
    }

    public function test_create_interaction_transition() {
        $this->setAdminUser();
        $generator = $this->generator();
        $interaction_transition_repository = workflow_stage_interaction_transition::repository();

        // Generate test workflow entities
        list($workflow_type, $form_version, $form, $workflow_go, $workflow_version, $workflow) = $this->generate_test_workflow_entities();

        /** @var workflow_version $workflow_version*/
        $workflow_version->status = status::DRAFT;
        $workflow_version->save();

        // Create two stages
        $stage1 = $this->create_stage_via_entity($workflow_version->id, 'Test Stage 1', form_submission::get_code(), 20);
        $stage2 = $this->create_stage_via_entity($workflow_version->id, 'Test Stage 2', approvals::get_code(), 21);

        // Create an interaction.
        $action_code = \mod_approval\model\application\action\approve::get_code();
        $interaction = $generator->create_workflow_stage_interaction($stage2->id, $action_code);

        // Nothing yet.
        $this->assertEquals(0, $interaction_transition_repository->count());

        // Create an interaction transition.
        $transition_go = new workflow_stage_interaction_transition_generator_object(
            $interaction->id,
            'next',
        );
        $interaction_transition = $generator->create_workflow_stage_interaction_transition($transition_go);

        // Test creation
        $this->assertEquals(1, $interaction_transition_repository->count());
        $this->assertGreaterThanOrEqual(1, $interaction_transition->id);
        $this->assertEquals($interaction->id, $interaction_transition->workflow_stage_interaction_id);
        $this->assertNull($interaction_transition->condition_key);
        $this->assertNull($interaction_transition->condition_data);
        $this->assertEquals('next', $interaction_transition->transition);
        $this->assertEquals(1, $interaction_transition->priority);

        // Create a conditional interaction transition.
        $transition_go = new workflow_stage_interaction_transition_generator_object(
            $interaction->id,
            'previous'
        );
        $transition_go->condition_key = 'request';
        $transition_go->condition_data = '{"comparison":"equals","value":"quux"}';
        $transition_go->priority = 2;
        $interaction_transition2 = $generator->create_workflow_stage_interaction_transition($transition_go);

        // Test creation
        $this->assertEquals(2, $interaction_transition_repository->count());
        $this->assertNotEquals($interaction_transition->id, $interaction_transition2->id);
        $this->assertEquals($interaction->id, $interaction_transition2->workflow_stage_interaction_id);
        $this->assertEquals('request', $interaction_transition2->condition_key);
        $this->assertEquals('{"comparison":"equals","value":"quux"}', $interaction_transition2->condition_data);
        $this->assertEquals('previous', $interaction_transition2->transition);
        $this->assertEquals(2, $interaction_transition2->priority);
    }

    public function test_create_interaction_action() {
        $this->setAdminUser();
        $generator = $this->generator();
        $interaction_action_repository = workflow_stage_interaction_action::repository();

        // Generate test workflow entities
        list($workflow_type, $form_version, $form, $workflow_go, $workflow_version, $workflow) = $this->generate_test_workflow_entities();

        /** @var workflow_version $workflow_version*/
        $workflow_version->status = status::DRAFT;
        $workflow_version->save();

        // Create two stages
        $stage1 = $this->create_stage_via_entity($workflow_version->id, 'Test Stage 1', form_submission::get_code(), 20);
        $stage2 = $this->create_stage_via_entity($workflow_version->id, 'Test Stage 2', approvals::get_code(), 21);

        // Create an interaction.
        $action_code = \mod_approval\model\application\action\approve::get_code();
        $interaction = $generator->create_workflow_stage_interaction($stage2->id, $action_code);

        // Nothing yet.
        $this->assertEquals(0, $interaction_action_repository->count());

        // Create an interaction action.
        $transition_go = new workflow_stage_interaction_action_generator_object(
            $interaction->id,
            'record_learning',
            '{"foo":"bar"}'
        );
        $interaction_action = $generator->create_workflow_stage_interaction_action($transition_go);

        // Test creation
        $this->assertEquals(1, $interaction_action_repository->count());
        $this->assertGreaterThanOrEqual(1, $interaction_action->id);
        $this->assertEquals($interaction->id, $interaction_action->workflow_stage_interaction_id);
        $this->assertNull($interaction_action->condition_key);
        $this->assertNull($interaction_action->condition_data);
        $this->assertEquals('record_learning', $interaction_action->effect);
        $this->assertEquals('{"foo":"bar"}', $interaction_action->effect_data);

        // Create a conditional interaction action.
        $transition_go = new workflow_stage_interaction_action_generator_object(
            $interaction->id,
            'publish_resource'
        );
        $transition_go->condition_key = 'request';
        $transition_go->condition_data = '{"comparison":"equals","value":"quux"}';
        $interaction_action2 = $generator->create_workflow_stage_interaction_action($transition_go);

        // Test creation
        $this->assertEquals(2, $interaction_action_repository->count());
        $this->assertNotEquals($interaction_action->id, $interaction_action2->id);
        $this->assertEquals($interaction->id, $interaction_action2->workflow_stage_interaction_id);
        $this->assertEquals('request', $interaction_action2->condition_key);
        $this->assertEquals('{"comparison":"equals","value":"quux"}', $interaction_action2->condition_data);
        $this->assertEquals('publish_resource', $interaction_action2->effect);
        $this->assertNull($interaction_action2->effect_data);
    }

    public function test_create_application_submission() {
        $this->setAdminUser();
        $generator = $this->generator();
        $application_submission_repository = application_submission::repository();

        // Generate test workflow entities
        list($workflow_type, $form_version, $form, $workflow_go, $workflow_version, $workflow) = $this->generate_test_workflow_entities();

        /** @var workflow_version $workflow_version */
        $workflow_version->status = status::DRAFT;
        $workflow_version->save();

        // Create a stage
        $stage = $generator->create_workflow_stage($workflow_version->id, 'Test Stage', form_submission::get_enum());

        // Generate a simple organisation hierarchy
        $framework = $this->generate_org_hierarchy();

        // Create an assignment
        $assignment_go = new assignment_generator_object($workflow->course_id, assignment_type\organisation::get_code(), $framework->agency->id);
        $assignment_go->is_default = true;
        $assignment = $generator->create_assignment($assignment_go);

        // Create a user
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);

        // Create an application
        $application_go = new application_generator_object($workflow_version->id, $form_version->id, $assignment->id);
        $application = $generator->create_application($application_go);

        // Nothing yet.
        $this->assertEquals(0, $application_submission_repository->count());

        $data = form_data::from_json('{"request":"If you know the enemy and know yourself you need not fear the results of a hundred battles."}');
        $application_submission = $generator->create_application_submission($application->id, $user1->id, $stage->id, $data);
        // Test defaults
        $this->assertEquals(1, $application_submission_repository->count());
        $this->assertGreaterThanOrEqual(1, $application_submission->id);
        $this->assertEquals($stage->id, $application_submission->workflow_stage_id);
        $this->assertEquals($application->id, $application_submission->application_id);
        $this->assertEquals($user1->id, $application_submission->user_id);
        $this->assertEquals($data->to_json(), $application_submission->form_data);
    }

    public function test_create_application_action() {
        $this->setAdminUser();
        $generator = $this->generator();
        $application_action_repository = application_action::repository();

        // Generate test workflow entities
        list($workflow_type, $form_version, $form, $workflow_go, $workflow_version, $workflow) = $this->generate_test_workflow_entities();

        /** @var workflow_version $workflow_version */
        $workflow_version->status = status::DRAFT;
        $workflow_version->save();

        // Create a stage
        $stage = $generator->create_workflow_stage($workflow_version->id, 'Test Stage', form_submission::get_enum());
        $approval_level = $generator->create_approval_level($stage->id, 'Level 1', 1);

        // Generate a simple organisation hierarchy
        $framework = $this->generate_org_hierarchy();

        // Create an assignment
        $assignment_go = new assignment_generator_object(
            $workflow->course_id,
            assignment_type\organisation::get_code(),
            $framework->agency->id
        );
        $assignment_go->is_default = true;
        $assignment = $generator->create_assignment($assignment_go);

        // Create a user
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);

        // Create an application
        $application_go = new application_generator_object($workflow_version->id, $form_version->id, $assignment->id);
        $application = $generator->create_application($application_go);

        // Nothing yet.
        $this->assertEquals(0, $application_action_repository->count());

        $data = form_data::from_json('{"request":"He will win who knows when to fight and when not to fight."}');
        $code = 3;
        $application_action = $generator->create_application_action(
            $application->id,
            $user1->id,
            $approval_level->workflow_stage_id,
            $approval_level->id,
            $code,
            $data
        );
        // Test defaults
        $this->assertEquals(1, $application_action_repository->count());
        $this->assertGreaterThanOrEqual(1, $application_action->id);
        $this->assertEquals($approval_level->workflow_stage_id, $application_action->workflow_stage_id);
        $this->assertEquals($approval_level->id, $application_action->workflow_stage_approval_level_id);
        $this->assertEquals($application->id, $application_action->application_id);
        $this->assertEquals($user1->id, $application_action->user_id);
        $this->assertEquals($code, $application_action->code);
        $this->assertEquals($data->to_json(), $application_action->form_data);
    }

    public function test_create_application_activity() {
        $this->setAdminUser();
        $generator = $this->generator();
        $application_activity_repository = application_activity::repository();

        // Generate test workflow entities
        list($workflow_type, $form_version, $form, $workflow_go, $workflow_version, $workflow) = $this->generate_test_workflow_entities();
        /** @var workflow_version $workflow_version */
        $workflow_version->status = status::DRAFT;
        $workflow_version->save();

        // Create a stage
        $stage = $generator->create_workflow_stage($workflow_version->id, 'Test Stage', form_submission::get_enum());
        $approval_level = $generator->create_approval_level($stage->id, 'Level 1', 1);

        // Generate a simple organisation hierarchy
        $framework = $this->generate_org_hierarchy();

        // Create an assignment
        $assignment_go = new assignment_generator_object($workflow->course_id, assignment_type\organisation::get_code(), $framework->agency->id);
        $assignment_go->is_default = true;
        $assignment = $generator->create_assignment($assignment_go);

        // Create a user
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);

        // Create an application
        $application_go = new application_generator_object($workflow_version->id, $form_version->id, $assignment->id);
        $application = $generator->create_application($application_go);

        // Nothing yet.
        $this->assertEquals(0, $application_activity_repository->count());

        $data = "Victorious warriors win first and then go to war, while defeated warriors go to war first and then seek to win.";
        $activity_type = 2;

        $application_activity_go = new application_activity_generator_object($application->id, $stage->id, $approval_level->id, $user1->id, $activity_type, $data);
        $application_activity = $generator->create_application_activity($application_activity_go);

        // Test defaults
        $this->assertEquals(1, $application_activity_repository->count());
        $this->assertGreaterThanOrEqual(1, $application_activity->id);
        $this->assertEquals($application->id, $application_activity->application_id);
        $this->assertEquals($stage->id, $application_activity->workflow_stage_id);
        $this->assertEquals($approval_level->id, $application_activity->workflow_stage_approval_level_id);
        $this->assertEquals($user1->id, $application_activity->user_id);
        $this->assertEquals($activity_type, $application_activity->activity_type);
        $this->assertEquals($data, $application_activity->activity_info);
    }

    public function test_create_assignment() {
        $this->setAdminUser();
        $generator = $this->generator();
        $assignment_repository = assignment::repository();

        // Generate test workflow entities
        list($workflow_type, $form_version, $form, $workflow_go, $workflow_version, $workflow) = $this->generate_test_workflow_entities();

        // Generate a simple organisation hierarchy
        $framework = $this->generate_org_hierarchy();

        // Nothing yet.
        $this->assertEquals(0, $assignment_repository->count());

        // Create an assignment generator object
        $assignment_go = new assignment_generator_object($workflow->course_id, assignment_type\organisation::get_code(), $framework->agency->id);

        // Test defaults
        $time = time();
        $generator->create_assignment($assignment_go);
        $this->assertEquals(1, $assignment_repository->count());
        $assignment = $assignment_repository->one();
        $this->assertGreaterThanOrEqual(1, $assignment->id);
        $this->assertEquals($workflow->course_id, $assignment->course);
        $this->assertEquals('Agency', $assignment->name);
        $this->assertEquals('assignment', substr($assignment->id_number, 0, 10));
        $this->assertFalse($assignment->is_default);
        $this->assertEquals(assignment_type\organisation::get_code(), $assignment->assignment_type);
        $this->assertEquals($framework->agency->id, $assignment->assignment_identifier);
        $this->assertEquals(2, $assignment->status);
        $this->assertGreaterThanOrEqual($time, $assignment->created);
        $this->assertLessThanOrEqual($assignment->updated, $assignment->created);
        // Load module instance and context
        $cm = get_coursemodule_from_instance('approval', $assignment->id, 0, false, true);
        $context = context_module::instance($cm->id);

        // Do it again, nothing changes
        $generator->create_assignment($assignment_go);
        $this->assertEquals(1, $assignment_repository->count());

        // Add another assignment, to a different org
        $assignment_go = new assignment_generator_object($workflow->course_id, assignment_type\organisation::get_code(), $framework->agency->subagency_a->id);
        $new_assignment = $generator->create_assignment($assignment_go);
        $this->assertEquals(2, $assignment_repository->count());
        $this->assertNotEquals($assignment->id, $new_assignment->id);
        $this->assertEquals(2, $workflow->assignments()->count());
        // Load new module instance and context
        $cm = get_coursemodule_from_instance('approval', $new_assignment->id, 0, false, true);
        $context = context_module::instance($cm->id);

        // Refetch the same generated assignment
        $assignment_go->assignment_identifier = $framework->agency->id;
        $assignment_redux = $generator->create_assignment($assignment_go);
        $this->assertEquals(2, $assignment_repository->count());
        $this->assertNotEquals($assignment_redux->id, $new_assignment->id);
        $this->assertEquals($assignment_redux->id, $assignment->id);
        $this->assertLessThanOrEqual($assignment_redux->updated, $assignment_redux->created);
    }

    public function test_create_instance_empty() {
        $this->setAdminUser();
        $generator = $this->generator();

        // build an empty data record
        $data = [];
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('module generator requires course');
        $generator->create_instance($data);
    }

    public function test_create_instance_minimal() {
        $this->setAdminUser();
        $generator = $this->generator();
        $assignment_repository = assignment::repository();

        // Generate test workflow entities
        list($workflow_type, $form_version, $form, $workflow_go, $workflow_version, $workflow) = $this->generate_test_workflow_entities();

        // Nothing yet.
        $this->assertEquals(0, $assignment_repository->count());

        // build a minimal data record
        $time = time();
        $data = ['course' => $workflow->course_id];
        $module = $generator->create_instance($data);
        $this->assertEquals(approval_module::class, get_class($module));
        $this->assertEquals(1, $assignment_repository->count());

        $assignment = $assignment_repository->one();

        // Check that module matches assignment entity
        $this->assertEquals($module->get_container_id(), $assignment->course);
        $this->assertEquals($module->get_instance(), $assignment->id);

        // Check that assignment entity is as expected
        $this->assertEquals($workflow->course_id, $assignment->course);
        $this->assertEquals('Cohort 1', $assignment->name);
        $this->assertEquals('test-assignment', substr($assignment->id_number, 0, 15));
        $this->assertGreaterThanOrEqual(28, strlen($assignment->id_number));
        $this->assertFalse($assignment->is_default);
        $this->assertEquals(assignment_type\cohort::get_code(), $assignment->assignment_type);
        $this->assertGreaterThanOrEqual(1, $assignment->assignment_identifier);
        $this->assertEquals(2, $assignment->status);
        $this->assertGreaterThanOrEqual($time, $assignment->created);
        $this->assertLessThanOrEqual($assignment->updated, $assignment->created);
        $this->assertFalse($assignment->to_be_deleted);

        // Load module instance and context
        $cm = get_coursemodule_from_instance('approval', $assignment->id, 0, false, true);
        $context = context_module::instance($cm->id);
    }

    public function test_create_instance_full() {
        $this->setAdminUser();
        $generator = $this->generator();
        $assignment_repository = assignment::repository();

        // Generate test workflow entities
        list($workflow_type, $form_version, $form, $workflow_go, $workflow_version, $workflow) = $this->generate_test_workflow_entities();

        $framework = $this->generate_org_hierarchy();

        // Nothing yet.
        $this->assertEquals(0, $assignment_repository->count());

        // build a delux data record
        $time = time();
        $data = [
            'course' => $workflow->course_id,
            'assignment_type' => assignment_type\organisation::get_code(),
            'assignment_identifier' => $framework->agency->id,
            'id_number' => '001',
            'is_default' => true,
            'status' => 2,
        ];
        $module = $generator->create_instance($data);
        $this->assertEquals(approval_module::class, get_class($module));
        $this->assertEquals(1, $assignment_repository->count());

        $assignment = $assignment_repository->one();

        // Check that module matches assignment entity
        $this->assertEquals($module->get_container_id(), $assignment->course);
        $this->assertEquals($module->get_instance(), $assignment->id);

        // Check that assignment entity is as expected
        $this->assertEquals($workflow->course_id, $assignment->course);
        $this->assertEquals('Agency', $assignment->name);
        $this->assertEquals($data['id_number'], $assignment->id_number);
        $this->assertTrue($assignment->is_default);
        $this->assertEquals(assignment_type\organisation::get_code(), $assignment->assignment_type);
        $this->assertEquals($framework->agency->id, $assignment->assignment_identifier);
        $this->assertEquals($data['status'], $assignment->status);
        $this->assertGreaterThanOrEqual($time, $assignment->created);
        $this->assertLessThanOrEqual($assignment->updated, $assignment->created);

        // Load module instance and context
        $cm = get_coursemodule_from_instance('approval', $assignment->id, 0, false, true);
        $context = context_module::instance($cm->id);
    }

    public function test_create_assignment_approver() {
        $this->setAdminUser();
        $generator = $this->generator();
        $approver_repository = assignment_approver::repository();

        // Generate test workflow entities
        list($workflow_type, $form_version, $form, $workflow_go, $workflow_version, $workflow) = $this->generate_test_workflow_entities();
        /** @var workflow_version $workflow_version */
        $workflow_version->status = status::DRAFT;
        $workflow_version->save();

        $workflow_stage = $generator->create_workflow_stage($workflow_version->id, 'Test Stage', form_submission::get_enum());
        $approval_level = $generator->create_approval_level($workflow_stage->id, 'Level 1', 1);

        // Generate a simple organisation hierarchy
        $framework = $this->generate_org_hierarchy();

        // Create an assignment
        $assignment_go = new assignment_generator_object($workflow->course_id, assignment_type\organisation::get_code(), $framework->agency->id);
        $assignment_go->is_default = true;
        $assignment = $generator->create_assignment($assignment_go);

        // Nothing yet.
        $this->assertEquals(0, $approver_repository->count());

        // Create a couple users
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Create an assignment_approver generator object
        $approver_go = new assignment_approver_generator_object($assignment->id, $approval_level->id, user::TYPE_IDENTIFIER, $user1->id);

        // Test defaults
        $time = time();
        $generator->create_assignment_approver($approver_go);
        $this->assertEquals(1, $approver_repository->count());
        $approver = $approver_repository->one();
        $this->assertGreaterThanOrEqual(1, $approver->id);
        $this->assertEquals($assignment->id, $approver->approval_id);
        $this->assertEquals($approval_level->id, $approver->workflow_stage_approval_level_id);
        $this->assertEquals(2, $approver->type);
        $this->assertEquals($user1->id, $approver->identifier);
        $this->assertEquals(true, $approver->active);
        $this->assertGreaterThanOrEqual($time, $approver->created);
        $this->assertLessThanOrEqual($approver->updated, $approver->created);

        // Do it again, nothing changes
        $generator->create_assignment_approver($approver_go);
        $this->assertEquals(1, $approver_repository->count());

        // Add another approver
        $approver_go->identifier = $user2->id;
        $new_approver = $generator->create_assignment_approver($approver_go);
        $this->assertEquals(2, $approver_repository->count());
        $this->assertNotEquals($approver->id, $new_approver->id);
        $this->assertEquals(2, $assignment->approvers()->count());

        // Refetch the same generated approver
        $approver_go->identifier = $user1->id;
        $approver_redux = $generator->create_assignment_approver($approver_go);
        $this->assertEquals(2, $approver_repository->count());
        $this->assertNotEquals($approver_redux->id, $new_approver->id);
        $this->assertEquals($approver_redux->id, $approver->id);
        $this->assertLessThanOrEqual($approver_redux->updated, $approver_redux->created);
    }

    public function test_create_own_application() {
        $this->setAdminUser();
        $generator = $this->generator();
        $application_repository = application::repository();

        // Generate test workflow entities
        list($workflow_type, $form_version, $form, $workflow_go, $workflow_version, $workflow) = $this->generate_test_workflow_entities();
        /** @var workflow_version $workflow_version */
        $workflow_version->status = status::DRAFT;
        $workflow_version->save();

        $workflow_stage = $generator->create_workflow_stage($workflow_version->id, 'Test Stage', form_submission::get_enum());
        $approval_level = $generator->create_approval_level($workflow_stage->id, 'Level 1', 1);

        // Generate a simple organisation hierarchy
        $framework = $this->generate_org_hierarchy();

        // Create an assignment
        $assignment_go = new assignment_generator_object($workflow->course_id, assignment_type\organisation::get_code(), $framework->agency->id);
        $assignment_go->is_default = true;
        $assignment = $generator->create_assignment($assignment_go);

        // Create a couple users
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);

        // Nothing yet.
        $this->assertEquals(0, $application_repository->count());

        // Create an application generator object
        $application_go = new application_generator_object($workflow_version->id, $form_version->id, $assignment->id);

        // Test defaults
        $time = time();
        $generator->create_application($application_go);
        $this->assertEquals(1, $application_repository->count());
        /** @var application $application */
        $application = $application_repository->one();
        $this->assertGreaterThanOrEqual(1, $application->id);
        $this->assertEquals($user1->id, $application->user_id);
        $this->assertNull($application->job_assignment_id);
        $this->assertEquals($workflow_version->id, $application->workflow_version_id);
        $this->assertEquals($assignment->id, $application->approval_id);
        $this->assertEquals($user1->id, $application->creator_id);
        $this->assertEquals($user1->id, $application->owner_id);
        $this->assertEquals($workflow_stage->id, $application->current_stage_id);
        $this->assertEquals(1, $application->is_draft);
        $this->assertNull($application->current_approval_level_id);
        $this->assertGreaterThanOrEqual($time, $application->created);
        $this->assertLessThanOrEqual($application->updated, $application->created);
        $this->assertNull($application->submitted);
        $this->assertNull($application->completed);

        // Do it again, and a new application is created
        // Yes, this is different!
        $next_application = $generator->create_application($application_go);
        $this->assertEquals(2, $application_repository->count());
        $this->assertNotEquals($application->id, $next_application->id);

        // Create a fake submitted application.
        $application_go->fake_submitted();
        $submitted_application = $generator->create_application($application_go);
        $this->assertEquals(3, $application_repository->count());
        $this->assertEquals($workflow_stage->id, $submitted_application->current_stage_id);
        $this->assertEquals(0, $submitted_application->is_draft);
        $this->assertEquals($approval_level->id, $submitted_application->current_approval_level_id);

        // Create a future fake-submitted application.
        $future = time() + 86400;
        $application_go->fake_submitted($future);
        $submitted_application = $generator->create_application($application_go);
        $this->assertEquals(4, $application_repository->count());
        $this->assertEquals($workflow_stage->id, $submitted_application->current_stage_id);
        $this->assertEquals(0, $submitted_application->is_draft);
        $this->assertEquals($approval_level->id, $submitted_application->current_approval_level_id);
        $this->assertGreaterThanOrEqual($future, $submitted_application->submitted);

        // Now set some other fields.
        $now = time();
        $application_go->user_id = $user2->id;
        $application_go->current_stage_id = $workflow_stage->id;
        $application_go->is_draft = 0;
        $application_go->current_approval_level_id = $approval_level->id;
        $application_go->submitted = $now;
        $application_go->completed = $now;
        $new_application = $generator->create_application($application_go);
        $this->assertEquals(5, $application_repository->count());
        $this->assertGreaterThan($next_application->id, $new_application->id);
        $this->assertEquals($user2->id, $new_application->user_id);
        $this->assertNull($new_application->job_assignment_id);
        $this->assertEquals($workflow_version->id, $new_application->workflow_version_id);
        $this->assertEquals($assignment->id, $new_application->approval_id);
        $this->assertEquals($user1->id, $new_application->creator_id);
        $this->assertEquals($user1->id, $new_application->owner_id);
        $this->assertEquals($workflow_stage->id, $new_application->current_stage_id);
        $this->assertEquals(0, $new_application->is_draft);
        $this->assertEquals($approval_level->id, $new_application->current_approval_level_id);
        $this->assertGreaterThanOrEqual($now, $new_application->created);
        $this->assertLessThanOrEqual($new_application->updated, $new_application->created);
        $this->assertGreaterThanOrEqual($now, $new_application->submitted);
        $this->assertGreaterThanOrEqual($now, $new_application->completed);
    }
}
