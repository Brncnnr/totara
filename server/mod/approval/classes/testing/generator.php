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

namespace mod_approval\testing;

use coding_exception;
use container_approval\approval as workflow_container;
use core\entity\user;
use core\orm\query\builder;
use core\testing\component_generator;
use core\testing\generator as core_generator;
use core_container\module\module;
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
use mod_approval\entity\workflow\workflow_stage_approval_level as approval_level;
use mod_approval\entity\workflow\workflow_stage_formview;
use mod_approval\entity\workflow\workflow_stage_interaction;
use mod_approval\entity\workflow\workflow_stage_interaction_action;
use mod_approval\entity\workflow\workflow_stage_interaction_transition;
use mod_approval\entity\workflow\workflow_type;
use mod_approval\entity\workflow\workflow_version;
use mod_approval\model\application\action\action;
use mod_approval\model\assignment\approver_type\user as user_approver_type;
use mod_approval\model\assignment\assignment as assignment_model;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\form\form_data;
use mod_approval\model\status;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\stage_type\finished;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow as workflow_model;
use mod_approval\model\workflow\workflow_stage as workflow_stage_model;
use mod_approval\model\workflow\workflow_stage_interaction as interaction_model;
use mod_approval\model\workflow\workflow_version as workflow_version_model;
use ReflectionProperty;
use stdClass;

/**
 * Approval workflows generator
 *
 * @package mod_approval\testing
 */
final class generator extends component_generator {
    use generator_behat;

    /**
     * Creates a workflow assignment module within a workflow container.
     *
     * "This function is required by module generators." You should probably use a different method.
     *
     * @param array|stdClass $data
     * @return module
     */
    public function create_instance($data = []): module {
        $data = (array)$data;
        $core_generator = core_generator::instance();

        if (empty($data['course'])) {
            throw new coding_exception('module generator requires course');
        }
        $container = workflow_container::from_id($data['course']);

        // Is there already a workflow at this course?
        $workflow = workflow::repository()->where('course_id', '=', $container->id);
        if (is_null($workflow)) {
            $type_name = $data['workflow_type'] ?? 'test';
            $workflow_type = $this->create_workflow_type($type_name);
            $form_version = $this->create_form_and_version();
            $form = $form_version->form;
            $workflow_go = new workflow_generator_object($workflow_type->id, $form->id, $form_version->id);
            $workflow_go->course_id = $container->id;
            $this->create_workflow_and_version($workflow_go);
        }

        $assignment_type = $data['assignment_type'] ?? assignment_type\cohort::get_code();
        $assignment_identifier = $data['assignment_identifier'] ?? $core_generator->create_cohort()->id;
        $assignment_go = new assignment_generator_object($container->id, $assignment_type, $assignment_identifier);
        $assignment_go->id_number = $data['id_number'] ?? uniqid('test-assignment');
        $assignment_go->is_default = $data['is_default'] ?? false;
        $assignment_go->status = $data['status'] ?? status::ACTIVE;
        $assignment_go->to_be_deleted = $data['to_be_deleted'] ?? false;

        $this->create_assignment($assignment_go);

        $modules = $container->get_section(0)->get_all_modules();

        return reset($modules);
    }

    /**
     * @param string $workflow_type
     * @param string $workflow_name
     * @param bool $publish_workflow
     * @return workflow
     */
    public function create_simple_request_workflow(
        string $workflow_type = 'Testing',
        string $workflow_name = 'Simple Request Workflow',
        bool $publish_workflow = true
    ): workflow {
        // Create a workflow_type.
        $workflow_type = $this->create_workflow_type($workflow_type);

        // Create a form and version.
        $form_version = $this->create_form_and_version('simple', 'Simple Request Form');
        $form = $form_version->form;

        // Create a workflow and version.
        $workflow_go = new workflow_generator_object($workflow_type->id, $form->id, $form_version->id, status::DRAFT);
        $workflow_go->name = $workflow_name;
        $workflow_version = $this->create_workflow_and_version($workflow_go);
        $workflow = $workflow_version->workflow;

        // Stages
        $this->create_workflow_stage($workflow_version->id, 'Stage 1', form_submission::get_enum());
        $this->create_workflow_stage($workflow_version->id, 'Stage 2', approvals::get_enum());
        $this->create_workflow_stage($workflow_version->id, 'Stage 3', finished::get_enum());

        // Publish workflow
        if ($publish_workflow) {
            workflow_model::load_by_entity($workflow)->publish(workflow_version_model::load_by_entity($workflow_version));
        }

        // Just return the workflow entity.
        return $workflow;
    }

    /**
     * Create a workflow_type entity.
     *
     * @param string $type_name
     * @return workflow_type
     */
    public function create_workflow_type(string $type_name): workflow_type {
        /** @var workflow_type $workflow_type */
        $workflow_type = workflow_type::repository()->where('name', '=', $type_name)->one();
        if (is_null($workflow_type)) {
            $workflow_type = new workflow_type();
            $workflow_type->name = $type_name;
            return $workflow_type->save();
        }
        return $workflow_type;
    }

    /**
     * Create matching form and form_version entities.
     *
     * @param string $plugin_name
     * @param string $title
     * @param string|null $schema_path
     * @return form_version - you can derive the form entity from that
     */
    public function create_form_and_version(
        string $plugin_name = 'simple',
        string $title = 'Generated Test Form',
        string $schema_path = null
    ): form_version {
        global $CFG;
        if ($schema_path === null) {
            $schema_path = $CFG->dirroot . '/mod/approval/tests/fixtures/form/test_form.json';
        }
        /** @var form $form */
        $form = form::repository()->where('plugin_name', '=', $plugin_name)->order_by('id', 'DESC')->first();
        if (is_null($form)) {
            $form = new form();
            $form->plugin_name = $plugin_name;
            $form->title = $title;
            $form->save();
        }
        $form_version = $form->versions()->where('form_id', '=', $form->id)->where('status', '=', status::ACTIVE)->one();
        if (is_null($form_version)) {
            $schema = file_get_contents($schema_path);
            $form_version = new form_version();
            $form_version->form_id = $form->id;
            $form_version->version = json_decode($schema, false, 512, JSON_THROW_ON_ERROR)->version ?? '2021030200';
            $form_version->json_schema = $schema;
            $form_version->status = status::ACTIVE;
            $form_version->save();
        }
        return $form_version;
    }

    /**
     * Create form_version entity.
     *
     * @param int $form_id
     * @param string $version
     * @param string $json_schema
     * @param int $status
     * @return form_version
     */
    public function create_form_version(
        int $form_id,
        string $version,
        string $json_schema,
        int $status = status::ACTIVE
    ): form_version {
        $form_version = new form_version();
        $form_version->form_id = $form_id;
        $form_version->version = $version;
        $form_version->json_schema = $json_schema;
        $form_version->status = $status;
        $form_version->save();

        return $form_version;
    }

    /**
     * Create matching workflow and workflow_version entities, as well as a container_approval object and context if not supplied.
     *
     * @param workflow_generator_object $workflow_go
     * @return workflow_version
     */
    public function create_workflow_and_version(workflow_generator_object $workflow_go): workflow_version {
        global $DB;

        $workflow = null;
        if (!empty($workflow_go->course_id)) {
            /** @var workflow $workflow */
            $workflow = workflow::repository()->where('course_id', '=', $workflow_go->course_id)->one();
        }
        if (is_null($workflow)) {
            $workflow = $DB->transaction(function () use ($workflow_go) {
                // Create course container if necessary
                if (!empty($workflow_go->course_id)) {
                    workflow_container::from_id($workflow_go->course_id);
                } else {
                    // Create a container
                    $container_data = new stdClass();
                    $container_data->fullname = $workflow_go->name . " Generated Container";
                    $container_data->category = workflow_container::get_default_category_id();
                    $container = workflow_container::create($container_data);
                    $workflow_go->course_id = $container->id;
                }

                $workflow = new workflow();
                $workflow->course_id = $workflow_go->course_id;
                $workflow->workflow_type_id = $workflow_go->workflow_type_id;
                $workflow->name = $workflow_go->name;
                $workflow->description = $workflow_go->description;
                $workflow->id_number = $workflow_go->id_number;
                $workflow->form_id = $workflow_go->form_id;
                $workflow->template_id = $workflow_go->template_id;
                $workflow->active = $workflow_go->active;
                $workflow->to_be_deleted = $workflow_go->to_be_deleted;
                $workflow->save();
                return $workflow;
            });
        }
        $workflow_version = $workflow->versions()
            ->where('form_version_id', '=', $workflow_go->form_version_id)
            ->where('status', '=', $workflow_go->status)
            ->one();
        if (is_null($workflow_version)) {
            $workflow_version = new workflow_version();
            $workflow_version->workflow_id = $workflow->id;
            $workflow_version->form_version_id = $workflow_go->form_version_id;
            $workflow_version->status = $workflow_go->status;
            $workflow_version->save();
        }
        return $workflow_version;
    }

    /**
     * Create workflow_version entity
     *
     * @param int $workflow_id
     * @param int $form_version_id
     * @param int $status
     * @return workflow_version
     */
    public function create_workflow_version(int $workflow_id, int $form_version_id, int $status = status::DRAFT): workflow_version {
        $workflow_version = new workflow_version();
        $workflow_version->workflow_id = $workflow_id;
        $workflow_version->form_version_id = $form_version_id;
        $workflow_version->status = $status;
        $workflow_version->save();
        return $workflow_version;
    }

    /**
     * Create a workflow_stage entity.
     *
     * @param int $workflow_version_id
     * @param string $name
     * @param string $stage_type_enum
     * @return workflow_stage
     */
    public function create_workflow_stage(int $workflow_version_id, string $name, string $stage_type_enum): workflow_stage {
        $workflow_stage = workflow_stage::repository()
            ->where('workflow_version_id', '=', $workflow_version_id)
            ->where('name', '=', $name)
            ->one();
        if (is_null($workflow_stage)) {
            $workflow_version = workflow_version_model::load_by_id($workflow_version_id);
            $stage = workflow_stage_model::create($workflow_version, $name, $stage_type_enum);

            $reflection_property = new ReflectionProperty($stage, 'entity');
            $reflection_property->setAccessible(true);
            /** @var workflow_stage $workflow_stage*/
            $workflow_stage = $reflection_property->getValue($stage);
        }
        return $workflow_stage;
    }

    /**
     * Create a workflow formview entity.
     *
     * @param formview_generator_object $workflow_formview_go
     * @return workflow_stage_formview
     *
     */
    public function create_formview(formview_generator_object $workflow_formview_go): workflow_stage_formview {
        /** @var workflow_stage_formview $workflow_stage_formview */
        $workflow_stage_formview = workflow_stage_formview::repository()
            ->where('workflow_stage_id', '=', $workflow_formview_go->workflow_stage)
            ->where('field_key', '=', $workflow_formview_go->field_key)
            ->one();

        if (is_null($workflow_stage_formview)) {
            $workflow_stage_formview = new workflow_stage_formview();
            $workflow_stage_formview->workflow_stage_id = $workflow_formview_go->workflow_stage;
            $workflow_stage_formview->field_key = $workflow_formview_go->field_key;
        }
        // Ability to update some fields after formview is created.
        $workflow_stage_formview->required = $workflow_formview_go->required;
        $workflow_stage_formview->disabled = $workflow_formview_go->disabled;
        $workflow_stage_formview->default_value = $workflow_formview_go->default_value;
        $workflow_stage_formview->active = $workflow_formview_go->active;
        $workflow_stage_formview->save();

        return $workflow_stage_formview;
    }

    /**
     * Create a workflow stage approval_level entity.
     *
     * @param int $workflow_stage_id
     * @param string $name
     * @param int $sortorder
     * @return approval_level
     */
    public function create_approval_level(int $workflow_stage_id, string $name, int $sortorder): approval_level {
        $approval_level = approval_level::repository()
            ->where('workflow_stage_id', '=', $workflow_stage_id)
            ->where('name', '=', $name)
            ->one();
        if (is_null($approval_level)) {
            if (approval_level::repository()
                ->where('workflow_stage_id', '=', $workflow_stage_id)
                ->where('sortorder', $sortorder)
                ->exists()
            ) {
                throw new coding_exception("sortorder {$sortorder} is already taken");
            }
            $approval_level = new approval_level();
            $approval_level->workflow_stage_id = $workflow_stage_id;
            $approval_level->name = $name;
            $approval_level->sortorder = $sortorder;
            $approval_level->save();
        }
        return $approval_level;
    }

    /**
     * Create a workflow assignment entity, with associated mod_approver module and activity context.
     *
     * @param assignment_generator_object $assignment_go
     * @return assignment
     */
    public function create_assignment(assignment_generator_object $assignment_go): assignment {
        $assignment = assignment::repository()
            ->where('course', '=', $assignment_go->course)
            ->where('assignment_type', '=', $assignment_go->assignment_type)
            ->where('assignment_identifier', '=', $assignment_go->assignment_identifier)
            ->one();
        if (is_null($assignment)) {
            $assignment_model = assignment_model::create(
                $assignment_go->course,
                $assignment_go->assignment_type,
                $assignment_go->assignment_identifier,
                $assignment_go->is_default,
                $assignment_go->id_number ?? ''
            );
            if ($assignment_go->status === status::ACTIVE) {
                $assignment_model->activate();
            }
            if ($assignment_go->status === status::ARCHIVED) {
                $assignment_model->archive();
            }
            $reflection = new ReflectionProperty($assignment_model, 'entity');
            $reflection->setAccessible(true);
            $assignment = $reflection->getValue($assignment_model);
        }
        return $assignment;
    }

    /**
     * Create a workflow assignment_approver entity
     *
     * @param assignment_approver_generator_object $approver_go
     * @return assignment_approver
     */
    public function create_assignment_approver(assignment_approver_generator_object $approver_go): assignment_approver {
        $approver = assignment_approver::repository()
            ->where('approval_id', '=', $approver_go->approval_id)
            ->where('workflow_stage_approval_level_id', '=', $approver_go->workflow_stage_approval_level_id)
            ->where('type', '=', $approver_go->type)
            ->where('identifier', '=', $approver_go->identifier)
            ->one();
        if (is_null($approver)) {
            $approver = new assignment_approver();
            $approver->approval_id = $approver_go->approval_id;
            $approver->workflow_stage_approval_level_id = $approver_go->workflow_stage_approval_level_id;
            $approver->type = $approver_go->type;
            $approver->identifier = $approver_go->identifier;
            $approver->active = $approver_go->active;
            $approver->save();
        }
        if ($approver->type == user_approver_type::TYPE_IDENTIFIER) {
            // Let's pretend we're a model and add the appropriate role assignment to the activity context.
            $assignment_model = assignment_model::load_by_id($approver_go->approval_id);
            $context = $assignment_model->get_context();
            $approver_role_id = builder::table('role')->where('shortname', 'approvalworkflowapprover')->value('id');
            role_assign($approver_role_id, $approver->identifier, $context->id);
        }
        return $approver;
    }

    /**
     * Create a workflow application entity.
     *
     * @param application_generator_object $application_go
     * @return application
     */
    public function create_application(application_generator_object $application_go): application {
        if (empty($application_go->user_id)) {
            $application_go->user_id = user::logged_in()->id;
        }
        if (empty($application_go->creator_id)) {
            $application_go->creator_id = user::logged_in()->id;
        }
        if (empty($application_go->owner_id)) {
            $application_go->owner_id = $application_go->creator_id;
        }
        // Same user can have as many applications as they like, so do not try to fetch from repository.
        /** @var workflow_version $workflow_version */
        $workflow_version = workflow_version::repository()->where('id', $application_go->workflow_version_id)->one();
        $application = new application();
        $application->user_id = $application_go->user_id;
        $application->job_assignment_id = $application_go->job_assignment_id;
        $application->workflow_version_id = $application_go->workflow_version_id;
        $application->form_version_id = $application_go->form_version_id;
        $application->approval_id = $application_go->assignment_id;
        $application->creator_id = $application_go->creator_id;
        $application->owner_id = $application_go->owner_id;
        $application->current_stage_id = $application_go->current_state->get_stage_id();
        $application->is_draft = $application_go->current_state->is_draft();
        $application->current_approval_level_id = $application_go->current_state->get_approval_level_id();
        $application->submitted = $application_go->submitted;
        $application->completed = $application_go->completed;
        $application->save();
        if (isset($application_go->title)) {
            $application->title = $application_go->title;
        } else {
            $application->title = $workflow_version->workflow->workflow_type->name;
        }
        $rm = new \ReflectionMethod('mod_approval\model\application\application', 'update_id_number');
        $rm->setAccessible(true);
        $rm->invoke(null, $application, $application->title, $application->created);
        return $application;
    }

    /**
     * Create a workflow stage interaction entity.
     *
     * @param int $workflow_stage_id
     * @param int $action_code
     * @return workflow_stage_interaction
     */
    public function create_workflow_stage_interaction(int $workflow_stage_id, int $action_code): workflow_stage_interaction {
        $interaction_model = interaction_model::create(
            workflow_stage_model::load_by_id($workflow_stage_id),
            action::from_code($action_code)
        );

        $reflection = new ReflectionProperty($interaction_model, 'entity');
        $reflection->setAccessible(true);
        $workflow_stage_interaction = $reflection->getValue($interaction_model);
        return $workflow_stage_interaction;
    }

    /**
     * Create a workflow_stage_interaction_transition entity.
     *
     * @param workflow_stage_interaction_transition_generator_object $workflow_stage_interaction_transition_go
     * @return workflow_stage_interaction_transition
     *
     */
    public function create_workflow_stage_interaction_transition(workflow_stage_interaction_transition_generator_object $workflow_stage_interaction_transition_go): workflow_stage_interaction_transition {
        $workflow_stage_interaction_transition = new workflow_stage_interaction_transition();
        $workflow_stage_interaction_transition->workflow_stage_interaction_id = $workflow_stage_interaction_transition_go->workflow_stage_interaction_id;
        $workflow_stage_interaction_transition->condition_key = $workflow_stage_interaction_transition_go->condition_key;
        $workflow_stage_interaction_transition->condition_data = $workflow_stage_interaction_transition_go->condition_data;
        $workflow_stage_interaction_transition->transition = $workflow_stage_interaction_transition_go->transition;
        $workflow_stage_interaction_transition->priority = $workflow_stage_interaction_transition_go->priority;
        $workflow_stage_interaction_transition->save();
        return $workflow_stage_interaction_transition;
    }

    /**
     * Create a workflow_stage_interaction_action entity.
     *
     * @param workflow_stage_interaction_action_generator_object $workflow_stage_interaction_action_go
     * @return workflow_stage_interaction_action
     *
     */
    public function create_workflow_stage_interaction_action(workflow_stage_interaction_action_generator_object $workflow_stage_interaction_action_go): workflow_stage_interaction_action {
        $workflow_stage_interaction_action = new workflow_stage_interaction_action();
        $workflow_stage_interaction_action->workflow_stage_interaction_id = $workflow_stage_interaction_action_go->workflow_stage_interaction_id;
        $workflow_stage_interaction_action->condition_key = $workflow_stage_interaction_action_go->condition_key;
        $workflow_stage_interaction_action->condition_data = $workflow_stage_interaction_action_go->condition_data;
        $workflow_stage_interaction_action->effect = $workflow_stage_interaction_action_go->effect;
        $workflow_stage_interaction_action->effect_data = $workflow_stage_interaction_action_go->effect_data;
        $workflow_stage_interaction_action->save();
        return $workflow_stage_interaction_action;
    }

    /**
     * Create an application submission entity.
     *
     * @param int $application_id
     * @param int $user_id
     * @param int $workflow_stage_id
     * @param form_data $form_data
     * @return application_submission
     */
    public function create_application_submission(
        int $application_id,
        int $user_id,
        int $workflow_stage_id,
        form_data $form_data
    ): application_submission {
        $application_submission = new application_submission();
        $application_submission->workflow_stage_id = $workflow_stage_id;
        $application_submission->application_id = $application_id;
        $application_submission->user_id = $user_id;
        $application_submission->form_data = $form_data->to_json();
        $application_submission->save();

        return $application_submission;
    }

    /**
     * Create an application action entity.
     *
     * @param int $application_id
     * @param int $user_id
     * @param int $workflow_stage_id
     * @param int $workflow_stage_approval_level_id
     * @param int $code
     * @param form_data $form_data
     * @return application_action
     */
    public function create_application_action(
        int $application_id,
        int $user_id,
        int $workflow_stage_id,
        int $workflow_stage_approval_level_id,
        int $code,
        form_data $form_data
    ): application_action {
        $application_action = new application_action();
        $application_action->workflow_stage_id = $workflow_stage_id;
        $application_action->workflow_stage_approval_level_id = $workflow_stage_approval_level_id;
        $application_action->application_id = $application_id;
        $application_action->user_id = $user_id;
        $application_action->code = $code;
        $application_action->form_data = $form_data->to_json();
        $application_action->save();

        return $application_action;
    }

    /**
     * Create an application activity entity.
     *
     * @param application_activity_generator_object $application_activity_go
     * @return application_activity
     */
    public function create_application_activity(
        application_activity_generator_object $application_activity_go
    ): application_activity {
        $application_activity = new application_activity();
        $application_activity->application_id = $application_activity_go->application_id;
        $application_activity->workflow_stage_id = $application_activity_go->workflow_stage_id;
        $application_activity->workflow_stage_approval_level_id = $application_activity_go->workflow_stage_approval_level_id;
        $application_activity->user_id = $application_activity_go->user_id;
        $application_activity->activity_type = $application_activity_go->activity_type;
        $application_activity->activity_info = $application_activity_go->activity_info;
        $application_activity->save();

        return $application_activity;
    }
}
