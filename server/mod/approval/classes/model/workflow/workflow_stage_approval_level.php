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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\model\workflow;

use core\orm\collection;
use core\orm\entity\model;
use core\orm\query\builder;
use mod_approval\entity\assignment\assignment;
use mod_approval\entity\assignment\assignment_approver as assignment_approver_entity;
use mod_approval\entity\workflow\workflow_stage_approval_level as workflow_stage_approval_level_entity;
use mod_approval\exception\model_exception;
use mod_approval\model\active_trait;
use mod_approval\model\application\application;
use mod_approval\model\application\application_action;
use mod_approval\model\application\application_activity;
use mod_approval\model\assignment\assignment_approver;
use mod_approval\model\model_trait;
use mod_approval\model\workflow\ordinal\allocate;
use mod_approval\model\workflow\ordinal\level_ordinal;

/**
 * Approval Workflow Stage Approval Level model
 *
 * Properties:
 * @property-read int $id Database record ID
 * @property-read int $workflow_stage_id
 * @property-read string $name Human-readable name
 * @property-read int $ordinal_number Ordinal number of this approval level (1, 2, 3, ...)
 * @property-read bool $active Is this approval_level active or not?
 * @property-read int $created Created timestamp
 * @property-read int $updated Last modified timestamp; same as created if not modified
 *
 * Relationships:
 * @property-read workflow_stage $workflow_stage Parent workflow_stage
 * @property-read collection|assignment_approver[] $approvers Active approvers assigned to this particular approval level on the default assignment
 * @property-read collection|application[] $applications using this approval level
 * @property-read collection|application_action[] $application_actions using this approval level
 * @property-read collection|application_activity[] $application_activities using this approval level
 *
 * Methods:
 * @method static self load_by_id(int $id)
 * @method static self load_by_entity(workflow_stage_approval_level_entity $entity)
 */
class workflow_stage_approval_level extends model {

    use active_trait;
    use model_trait;

    /** @var workflow_stage_approval_level_entity */
    protected $entity;

    /** @var string[] */
    protected $entity_attribute_whitelist = [
        'id',
        'workflow_stage_id',
        'name',
        'active',
        'created',
        'updated',
    ];

    /** @var string[] */
    protected $model_accessor_whitelist = [
        'workflow_stage',
        'approvers',
        'applications',
        'application_actions',
        'application_activities',
        'ordinal_number',
    ];

    /** @var string[] */
    protected $deactivate_checklist = [
        assignment_approver::class => 'workflow_stage_approval_level_id',
        application::class => 'current_approval_level_id',
    ];

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    protected static function get_entity_class(): string {
        return workflow_stage_approval_level_entity::class;
    }

    /**
     * Get the parent workflow stage.
     * @return workflow_stage
     */
    public function get_workflow_stage(): workflow_stage {
        return workflow_stage::load_by_entity($this->entity->workflow_stage);
    }

    /**
     * Get active assignment approvers for the default assignment
     * @return collection|assignment_approver[]
     */
    public function get_approvers(): collection {
        return assignment_approver_entity::repository()
            ->filter_by_active()
            ->filter_by_default_assignment_of_approval_level($this->id)
            ->order_by('updated')
            ->get()
            ->map_to(assignment_approver::class);
    }

    /**
     * Get the applications using this workflow stage approval level.
     *
     * @return collection|application[]
     */
    public function get_applications(): collection {
        return $this->entity->applications->map_to(application::class);
    }

    /**
     * Get the application_actions using this workflow stage approval level.
     *
     * @return collection|application_action[]
     */
    public function get_application_actions(): collection {
        return $this->entity->application_actions->map_to(application_action::class);
    }

    /**
     * Get the application_activities using this workflow stage approval level.
     *
     * @return collection|application_activity[]
     */
    public function get_application_activities(): collection {
        return $this->entity->application_activities->map_to(application_activity::class);
    }

    /**
     * Get the ordinal number in the associated workflow stage.
     *
     * @return integer
     */
    public function get_ordinal_number(): int {
        // Secret: the ordinal number is identical to the sort order.
        return $this->entity->sortorder;
    }

    /**
     * Indicates if this approval level is the first in the stage.
     *
     * @return bool
     */
    public function is_first(): bool {
        return $this->entity->sortorder == 1;
    }

    /**
     * Delete the record.
     * @return self
     */
    public function delete(): self {
        // TODO: TL-31413 delete dependencies
        // So far, only delete approvers to avoid foreign key constraint violations.
        assignment_approver_entity::repository()
            ->filter_by_approval_level($this->id)
            ->get()
            ->map_to(function ($entity) {
                assignment_approver::load_by_entity($entity)->delete();
            });
        $this->entity->delete();
        return $this;
    }

    /**
     * Create new workflow_stage_approval_level by cloning itself
     *
     * @param workflow_stage $workflow_stage
     * @return $this
     */
    public function clone(workflow_stage $workflow_stage): self {
        return $workflow_stage->add_approval_level($this->name);
    }
}
