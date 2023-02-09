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

namespace mod_approval\model\workflow;

use core\orm\collection;
use core\orm\entity\model;
use mod_approval\entity\workflow\workflow_stage_interaction as workflow_stage_interaction_entity;
use mod_approval\exception\model_exception;
use mod_approval\model\application\action\action;
use mod_approval\model\model_trait;

/**
 * Approval Workflow Stage Interaction model
 *
 * Properties:
 * @property-read int $id Database record ID
 * @property-read int $workflow_stage_id Parent workflow_stage ID
 * @property-read int $action_code Application action code
 * @property-read action $application_action Application action associated with this interaction
 * @property-read int $created Created timestamp
 * @property-read int $updated Last modified timestamp; same as created if not modified
 *
 * Relationships:
 * @property-read workflow_stage $workflow_stage Parent workflow_stage
 * @property-read collection|workflow_stage_interaction_transition[] $conditional_transitions Collection of conditional transitions on this interaction
 * @property-read workflow_stage_interaction_transition $default_transition The default transition for this interaction
 *
 * Methods:
 * @method static self load_by_id(int $id)
 * @method static self load_by_entity(workflow_stage_interaction_entity $entity)
 */
final class workflow_stage_interaction extends model {

    use model_trait;

    /** @var workflow_stage_interaction_entity */
    protected $entity;

    /** @var string[] */
    protected $entity_attribute_whitelist = [
        'id',
        'workflow_stage_id',
        'action_code',
        'created',
        'updated',
    ];

    /** @var string[] */
    protected $model_accessor_whitelist = [
        'workflow_stage',
        'application_action',
        'conditional_transitions',
        'default_transition'
    ];

    /** @var string[] */
    protected $deactivate_checklist = [];

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    protected static function get_entity_class(): string {
        return workflow_stage_interaction_entity::class;
    }

    /**
     * Create a workflow stage interaction.
     *
     * @param workflow_stage $workflow_stage Parent workflow_stage
     * @param action $application_action The application/workflow action this interaction relates to
     * @return self
     */
    public static function create(
        workflow_stage $workflow_stage,
        action $application_action
    ): self {
        if (!$workflow_stage->active) {
            throw new model_exception("Workflow stage must be active");
        }
        $entity = new workflow_stage_interaction_entity();
        $entity->workflow_stage_id = $workflow_stage->id;
        $entity->action_code = $application_action->get_code();
        $entity->save();
        return self::load_by_entity($entity);
    }

    /**
     * Get the parent workflow stage.
     *
     * @return workflow_stage
     */
    public function get_workflow_stage(): workflow_stage {
        return workflow_stage::load_by_entity($this->entity->workflow_stage);
    }

    /**
     * Gets the application action type configured by this interaction.
     *
     * @return action
     */
    public function get_application_action(): action {
        return action::from_code($this->action_code);
    }

    /**
     * Gets conditional workflow_stage_interaction_transitions attached to this interaction.
     *
     * @return collection|workflow_stage_interaction_transition[]
     */
    public function get_conditional_transitions(): collection {
        return $this->entity->conditional_transitions->map_to(workflow_stage_interaction_transition::class);
    }

    /**
     * Gets the default workflow_stage_interaction_transition attached to this interaction.
     *
     * @return workflow_stage_interaction_transition
     */
    public function get_default_transition(): workflow_stage_interaction_transition {
        return workflow_stage_interaction_transition::load_by_entity($this->entity->default_transition);
    }

    /**
     * Delete the record.
     * @return self
     */
    public function delete(): self {
        $this->entity->delete();
        return $this;
    }

    /**
     * Create new workflow_stage_interaction by cloning itself
     *
     * @param workflow_stage $workflow_stage
     * @return $this
     */
    public function clone(workflow_stage $workflow_stage): self {
        return $workflow_stage->add_interaction(action::from_code($this->action_code));
    }
}