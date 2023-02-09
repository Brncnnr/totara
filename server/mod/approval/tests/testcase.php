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

use core\entity\user;
use core_phpunit\testcase;
use mod_approval\entity\application\application as application_entity;
use mod_approval\entity\assignment\assignment as assignment_entity;
use mod_approval\model\application\application;
use mod_approval\model\assignment\assignment;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\form\form;
use mod_approval\model\form\form_version;
use mod_approval\model\workflow\stage_feature\formviews;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\stage_type\finished;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_type;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_formview;
use mod_approval\model\workflow\workflow_version;
use totara_core\advanced_feature;

/**
 * Base test case class
 */
abstract class mod_approval_testcase extends testcase {
    /** @var integer */
    private $types;

    /** @var integer */
    private $ids;

    public function setUp(): void {
        $this->types = 0;
        $this->ids = 0;
        advanced_feature::disable('engage_resources');
        advanced_feature::disable('container_workspace');
        parent::setUp();
    }

    /**
     * Create a test user
     *
     * @param array|stdClass $record
     * @param array|null $options
     * @return user user record
     */
    protected function create_user($record = null, array $options = null): user {
        return new user($this->getDataGenerator()->create_user($record, $options));
    }

    /**
     * Create a workflow for the current user using models.
     *
     * @param string|null $schema_file
     * @return workflow
     */
    protected function create_workflow_for_user(string $schema_file = null, callable $callback = null): workflow {
        $add_formviews = false;
        if (!$schema_file) {
            $schema_file = 'test';
            $add_formviews = true;
        }
        $type = workflow_type::create(sprintf('test workflow type %03d', ++$this->types));
        $form = form::create('simple', 'test form');
        $json_schema = file_get_contents(__DIR__ . "/fixtures/schema/{$schema_file}.json");
        form_version::create($form, 'test form version', $json_schema);
        $workflow = workflow::create(
            $type,
            $form,
            'test workflow',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id,
            sprintf('test-workflow-id-%03d', ++$this->ids)
        );
        $workflow_version = $workflow->latest_version;
        $workflow->get_default_assignment()->activate();

        if (is_null($callback)) {
            $this->default_workflow_setup($workflow_version, $add_formviews);
        } else {
            call_user_func_array($callback, [$workflow_version]);
        }

        $workflow_version->activate();
        return $workflow;
    }

    /**
     * Default callback for workflow setup
     *
     * @param $workflow_version
     * @param $add_formviews
     * @return void
     */
    private function default_workflow_setup($workflow_version, $add_formviews): void {
        $form_stage = workflow_stage::create($workflow_version, 'stage 1', form_submission::get_enum());

        if ($add_formviews) {
            workflow_stage_formview::create($form_stage, 'kia', true, false, 'KIA');
            workflow_stage_formview::create($form_stage, 'ora', false, false, 'ORA');
        }

        $approval_stage = workflow_stage::create($workflow_version, 'stage 2', approvals::get_enum());

        if ($add_formviews) {
            workflow_stage_formview::create($approval_stage, 'kia', true, false, 'KIA');
            workflow_stage_formview::create($approval_stage, 'ora', false, false, 'ORA');
        }

        workflow_stage::create($workflow_version, 'stage 3', finished::get_enum());
    }

    /**
     * Create an application for the current user using models.
     *
     * @param workflow $workflow
     * @return application
     */
    protected function create_application_for_user_on(workflow $workflow): application {
        $assignment_rep = assignment_entity::repository()
            ->where('course', '=', $workflow->course_id)
            ->where('is_default', '=', true)
            ->one();
        if ($assignment_rep) {
            $assignment = assignment::load_by_entity($assignment_rep);
            if (!$assignment->is_active()) {
                $assignment->activate();
            }
        } else {
            $assignment = assignment::create(
                $workflow->course_id,
                assignment_type\cohort::get_code(),
                $this->getDataGenerator()->create_cohort()->id,
                true
            );
            $assignment->activate();
        }
        return application::create($workflow->latest_version, $assignment, user::logged_in()->id);
    }

    /**
     * Create an application for the current user using models.
     *
     * @param string|null $schema_file
     * @param callable|null $workflow_setup_callback
     * @return application
     */
    protected function create_application_for_user(string $schema_file = null, callable $workflow_setup_callback = null): application {
        $workflow = $this->create_workflow_for_user($schema_file, $workflow_setup_callback);
        return $this->create_application_for_user_on($workflow);
    }

    /**
     * @return array of [application, ['input' => ['application_id' => application->id]]]
     */
    protected function create_application_for_user_input(): array {
        $application = $this->create_application_for_user();
        $input = [
            'input' => [
                'application_id' => $application->id,
            ],
        ];
        return [$application, $input];
    }

    /**
     * Change the current stage and the current approval level without firing events etc.
     *
     * @param application $application
     * @param integer $workflow_stage_id
     * @param integer|null $approval_level_id
     */
    protected function application_update_stage_and_level_silently(
        application $application,
        int $workflow_stage_id,
        ?int $approval_level_id
    ): void {
        // TODO: TL-30122 generators should be able to advance stages/levels instead of setting them directly
        $rp = new ReflectionProperty($application, 'entity');
        $rp->setAccessible(true);
        $entity = $rp->getValue($application);
        $entity->current_stage_id = $workflow_stage_id;
        $entity->is_draft = 0;
        $entity->current_approval_level_id = $approval_level_id;
        $entity->save();
        if ($entity->relation_loaded('current_stage')) {
            $entity->load_relation('current_stage');
        }
        if ($entity->relation_loaded('current_approval_level')) {
            $entity->load_relation('current_approval_level');
        }
    }

    /**
     * @param application $model
     * @param string $stage_type one of "APPROVALS" or "FINISHED"
     */
    public function fake_state_application(application $model, string $stage_type): void {
        $prop = new ReflectionProperty($model, 'entity');
        $prop->setAccessible(true);
        /** @var application_entity $entity */
        $entity = $prop->getValue($model);

        if ($stage_type == "APPROVALS" && !$entity->submitted) {
            $entity->submitted = time();
            $entity->is_draft = 0;
            $entity->current_approval_level_id = $entity->current_stage->active_approval_levels->first()->id ?? null;
            $this->waitForSecond();
        }
        if ($stage_type == "FINISHED" && !$entity->completed) {
            $entity->completed = time();
            $entity->is_draft = 0;
            $entity->current_approval_level_id = null;
            $this->waitForSecond();
        }
        $entity->save();
    }

    /**
     * @param workflow $model
     * @param integer $status
     */
    public function fake_state_workflow(workflow $model, int $status): void {
        $prop = new ReflectionProperty($model, 'entity');
        $prop->setAccessible(true);
        $entity = $prop->getValue($model);

        $this->fake_state_workflow_version($model->latest_version, $status);
        if ($entity->relation_loaded('versions')) {
            $entity->load_relation('versions');
        }
    }

    /**
     * @param workflow_version $model
     * @param integer $status
     */
    public function fake_state_workflow_version(workflow_version $model, int $status): void {
        $prop = new ReflectionProperty($model, 'entity');
        $prop->setAccessible(true);
        $entity = $prop->getValue($model);

        $entity->status = $status;
        $entity->save();
    }

    /**
     * @param workflow_version $workflow_version
     */
    public function erase_formviews_from_stages(workflow_version $workflow_version): void {
        $stages = $workflow_version->stages;
        foreach ($stages as $stage) {
            $default_formviews = $stage->formviews->all();
            /** @var workflow_stage_formview $formview */
            foreach ($default_formviews as $formview) {
                $stage->configure_formview([['field_key' => $formview->field_key, 'visibility' => formviews::HIDDEN]]);
            }
        }
    }
}
