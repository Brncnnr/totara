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

use core\orm\entity\model;
use mod_approval\entity\workflow\workflow_stage_interaction as workflow_stage_interaction_entity;
use mod_approval\entity\workflow\workflow_stage_interaction_transition as workflow_stage_interaction_transition_entity;
use mod_approval\exception\model_exception;
use mod_approval\model\model_trait;
use mod_approval\model\workflow\interaction\condition\conditional_interface;
use mod_approval\model\workflow\interaction\condition\conditional_trait;
use mod_approval\model\workflow\interaction\condition\interaction_condition;
use mod_approval\model\workflow\interaction\transition\transition_base;

/**
 * Approval Workflow Stage Interaction Transition model
 *
 * Properties:
 * @property-read int $id Database record ID
 * @property-read int $workflow_stage_interaction_id Parent workflow_stage_interaction ID
 * @property-read interaction_condition $condition Condition for making this transition
 * @property-read transition_base $transition transition implementation for resolving the new state to transition to
 * @property-read int $priority Priority, determines which of several possible transitions to execute (highest priority wins)
 * @property-read int $created Created timestamp
 * @property-read int $updated Last modified timestamp; same as created if not modified
 *
 * Relationships:
 * @property-read workflow_stage_interaction $workflow_stage_interaction Parent workflow_stage_interaction
 *
 * Methods:
 * @method static self load_by_id(int $id)
 * @method static self load_by_entity(workflow_stage_interaction_transition_entity $entity)
 */
final class workflow_stage_interaction_transition extends model implements conditional_interface {

    use model_trait;
    use conditional_trait {
        set_condition as trait_set_condition;
    }

    /** @var workflow_stage_interaction_entity */
    protected $entity;

    /** @var string[] */
    protected $entity_attribute_whitelist = [
        'id',
        'workflow_stage_interaction_id',
        'priority',
        'created',
        'updated',
    ];

    /** @var string[] */
    protected $model_accessor_whitelist = [
        'condition',
        'transition',
        'workflow_stage_interaction',
    ];

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    protected static function get_entity_class(): string {
        return workflow_stage_interaction_transition_entity::class;
    }

    /**
     * Create a workflow stage interaction transition.
     *
     * @param workflow_stage_interaction $workflow_stage_interaction Parent workflow_stage_interaction
     * @param interaction_condition|null $condition Condition on which to execute this transition, or null for default
     * @param transition_base $transition To-stage resolver for this transition
     * @param int $priority Priority for conditional transition
     * @return self
     */
    public static function create(
        workflow_stage_interaction $workflow_stage_interaction,
        ?interaction_condition     $condition,
        transition_base            $transition,
        int                        $priority = 1
    ): self {
        $entity = new workflow_stage_interaction_transition_entity();
        $entity->workflow_stage_interaction_id = $workflow_stage_interaction->id;
        if (!is_null($condition)) {
            $entity->condition_key = $condition->condition_key_field();
            $entity->condition_data = $condition->condition_data_field();
        }
        $entity->transition = $transition->transition_field();
        $entity->priority = $priority;
        $entity->save();
        return self::load_by_entity($entity);
    }

    /**
     * Get the parent workflow stage interaction.
     *
     * @return workflow_stage_interaction
     */
    public function get_workflow_stage_interaction(): workflow_stage_interaction {
        return workflow_stage_interaction::load_by_entity($this->entity->workflow_stage_interaction);
    }

    /**
     * Get the transition resolver for this transition.
     *
     * @return transition_base
     */
    public function get_transition(): transition_base {
        return transition_base::from_transition($this);
    }

    /**
     * Get the transition value from the entity.
     *
     * @return string
     */
    public function get_transition_field(): string {
        return $this->entity->transition;
    }

    /**
     * Change this transition's transition resolver.
     *
     * @param transition_base $transition
     * @return self
     */
    public function set_transition(transition_base $transition): self {
        $this->entity->transition = $transition->transition_field();
        $this->entity->save();
        return $this;
    }

    /**
     * Change this transition's priority.
     *
     * @param int $priority
     * @return self
     */
    public function set_priority(int $priority): self {

        // Should we enforce that the priority of an unconditional transition be 1?

        $this->entity->priority = $priority;
        $this->entity->save();
        return $this;
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
     * Prevents a default transition from being changed to conditional, then calls conditional_trait::set_condition().
     *
     * @param interaction_condition $condition
     * @return conditional_trait
     */
    public function set_condition(interaction_condition $condition): conditional_trait {
        if (empty($this->entity->condition_key)) {
            throw new model_exception('Unable to convert an unconditional transition to a conditional one.');
        }
        return $this->trait_set_condition($condition);
    }

    /**
     * Checked if transition is conditional.
     *
     * @return bool
     */
    public function is_conditional(): bool {
        if (empty($this->entity->condition_key)) {
            return false;
        }
        return true;
    }
}