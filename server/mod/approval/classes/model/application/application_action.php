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
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\model\application;

use core\entity\user;
use core\orm\entity\model;
use mod_approval\entity\application\application_action as application_action_entity;
use mod_approval\model\application\action\action;
use mod_approval\model\form\form_data;
use mod_approval\model\model_trait;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_approval_level;

/**
 * Approval application action model
 *
 * Properties:
 * @property-read int $id Database record ID
 * @property-read int $application_id Parent application ID
 * @property-read int $user_id Applicant or approver id
 * @property-read int $workflow_stage_id Related workflow_stage ID
 * @property-read int|null $workflow_stage_approval_level_id Related workflow_stage_approval_level ID
 * @property-read int $code Action taken (approved, rejected, withdrawn)
 * @property-read int $created Creation timestamp
 * @property-read bool $superseded Whether this action has been superseded
 * @property-read string $form_data JSON blob of form field state at the time of the approval
 * @property-read form_data $form_data_parsed Parsed form data
 *
 * Relationships:
 * @property-read application $application Parent application
 * @property-read user $user Related user entity
 * @property-read workflow_stage_approval_level|null $approval_level
 *
 * Methods:
 * @method static self load_by_id(int $id)
 * @method static self load_by_entity(application_action_entity $entity)
 * @package mod_approval\models\application
 */
class application_action extends model {

    use model_trait;

    /** @var application_action_entity */
    protected $entity;

    /** @var string[] */
    protected $entity_attribute_whitelist = [
        'id',
        'application_id',
        'user_id',
        'workflow_stage_id',
        'workflow_stage_approval_level_id',
        'code',
        'created',
        'superseded',
        'form_data'
    ];

    /** @var string[] */
    protected $model_accessor_whitelist = [
        'application',
        'user',
        'form_data_parsed',
        'approval_level'
    ];

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    protected static function get_entity_class(): string {
        return application_action_entity::class;
    }

    /**
     * Get the parent application
     *
     * @return application
     */
    public function get_application(): application {
        return application::load_by_entity($this->entity->application);
    }

    /**
     * Create an application action and set application state.
     *
     * @param application $application Parent application
     * @param int $actor_id ID of the user who performed the action
     * @param action $action The action instance
     * @return self
     */
    public static function create(
        application $application,
        int $actor_id,
        action $action
    ): self {
        $entity = new application_action_entity();
        $entity->application_id = $application->id;
        $entity->user_id = $actor_id;
        $entity->workflow_stage_id = $application->current_state->get_stage_id();
        $entity->workflow_stage_approval_level_id = $application->current_state->get_approval_level_id();
        $entity->code = $action->get_code();
        $entity->superseded = false;
        $entity->form_data = $application->last_submission->form_data;
        $entity->save();

        return self::load_by_entity($entity);
    }

    /**
     * Mark all actions on the application for the stage as superseded.
     *
     * @param application $application
     * @param workflow_stage $stage
     * @return void
     */
    public static function supercede_actions_for_stage(application $application, workflow_stage $stage): void {
        application_action_entity::repository()
            ->where('application_id', $application->id)
            ->where('workflow_stage_id', $stage->id)
            ->update([
                'superseded' => 1,
            ]);
    }

    /**
     * Get the applicant or approver.
     *
     * @return user
     */
    public function get_user(): user {
        return $this->entity->user;
    }

    /**
     * Get the workflow_stage_approval_level.
     *
     * @return workflow_stage_approval_level|null
     */
    public function get_approval_level(): ?workflow_stage_approval_level {
        $approval_level = $this->entity->workflow_stage_approval_level;
        if (!is_null($approval_level)) {
            return workflow_stage_approval_level::load_by_entity($approval_level);
        }

        return null;
    }

    /**
     * Get the form data.
     *
     * @return form_data
     */
    public function get_form_data_parsed(): form_data {
        return form_data::from_instance($this);
    }

    /**
     * Delete the record.
     *
     * @return self
     */
    public function delete(): self {
        $this->entity->delete();
        return $this;
    }
}
