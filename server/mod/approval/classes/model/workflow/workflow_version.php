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

namespace mod_approval\model\workflow;

use coding_exception;
use core\orm\collection;
use core\orm\entity\model;
use core\orm\query\builder;
use core\orm\query\order;
use lang_string;
use mod_approval\entity\workflow\workflow_stage as workflow_stage_entity;
use mod_approval\entity\workflow\workflow_stage_approval_level as approval_level_entity;
use mod_approval\entity\workflow\workflow_version as workflow_version_entity;
use mod_approval\exception\model_exception;
use mod_approval\model\application\application_state;
use mod_approval\model\form\form_version;
use mod_approval\model\status;
use mod_approval\model\status_trait;
use mod_approval\model\application\application;
use mod_approval\model\model_trait;
use mod_approval\model\workflow\ordinal\move;
use mod_approval\model\workflow\ordinal\stage_ordinal;

/**
 * Approval workflow version model
 *
 * Properties:
 * @property-read int $id Database record ID
 * @property-read int $workflow_id Parent workflow ID
 * @property-read int $form_version_id ID of the form_version this workflow uses
 * @property-read int $status Status code (status::DRAFT|ACTIVE|ARCHIVED)
 * @property-read string $status_label Status label (draft|active|archived)
 * @property-read int $created Creation timestamp
 * @property-read int $updated Last-modified timestamp; same as created if not modified
 *
 * Relationships:
 * @property-read workflow $workflow Parent workflow
 * @property-read form_version $form_version Form_version this workflow uses
 * @property-read collection|workflow_stage[] $stages Sorted collection of active stages of this workflow_version
 * @property-read collection|application[] $applications using this workflow_version
 *
 * Methods:
 * @method static self load_by_id(int $id)
 * @method static self load_by_entity(workflow_version_entity $entity)
 */
final class workflow_version extends model {

    use model_trait;
    use status_trait;

    /** @var workflow_version_entity */
    protected $entity;

    /** @var string[] */
    protected $entity_attribute_whitelist = [
        'id',
        'workflow_id',
        'form_version_id',
        'status',
        'created',
        'updated',
    ];

    /** @var string[] */
    protected $model_accessor_whitelist = [
        'status_label',
        'workflow',
        'form_version',
        'stages',
        'applications',
    ];

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    protected static function get_entity_class(): string {
        return workflow_version_entity::class;
    }

    /**
     * Get the parent workflow.
     *
     * @return workflow
     */
    public function get_workflow(): workflow {
        return workflow::load_by_entity($this->entity->workflow);
    }

    /**
     * Get the status label.
     *
     * @return lang_string
     */
    public function get_status_label(): lang_string {
        return status::label($this->entity->status);
    }

    /**
     * Get the form_version this workflow_version uses.
     *
     * @return form_version
     */
    public function get_form_version(): form_version {
        return form_version::load_by_entity($this->entity->form_version);
    }

    /**
     * Get the active workflow stages for this workflow_version, sorted by sortorder.
     *
     * @return collection|workflow_stage[]
     */
    public function get_stages(): collection {
        return $this->entity->active_stages->map_to(workflow_stage::class);
    }

    /**
     * Get the applications using this workflow version.
     *
     * @return collection|application[]
     */
    public function get_applications(): collection {
        return $this->entity->applications->map_to(application::class);
    }

    /**
     * Create a workflow version.
     *
     * @param workflow $workflow Parent workflow
     * @param form_version $form_version Related form_version
     * @return self
     */
    public static function create(workflow $workflow, form_version $form_version): self {
        // Parent workflow must be active.
        if (!$workflow->active) {
            throw new model_exception("Workflow must be active");
        }
        // Form version must be active.
        if (!$form_version->is_active()) {
            throw new model_exception("Form version must be active");
        }
        $entity = new workflow_version_entity();
        $entity->workflow_id = $workflow->id;
        $entity->form_version_id = $form_version->id;
        $entity->status = status::DRAFT;
        $entity->save();
        return self::load_by_entity($entity);
    }

    /**
     * Load the latest workflow version of the workflow.
     *
     * @param integer $workflow_id Parent workflow ID
     * @return self
     */
    public static function load_latest_by_workflow_id(int $workflow_id): self {
        return self::load_by_entity(
            workflow_version_entity::repository()
                ->where('workflow_id', $workflow_id)
                ->order_by('id', order::DIRECTION_DESC)
                ->first(true)
        );
    }

    /**
     * Load the latest active workflow version of the workflow.
     *
     * @param integer $workflow_id Parent workflow ID
     * @return self||null
     */
    public static function load_active_by_workflow_id(int $workflow_id): ?self {
        /** @var workflow_version_entity $active_version */
        $active_version = workflow_version_entity::repository()
            ->where('workflow_id', $workflow_id)
            ->where('status', status::ACTIVE)
            ->one();
        if (!$active_version) {
            return null;
        } else {
            return self::load_by_entity($active_version);
        }
    }

    /**
     * Get the next workflow stage for the given stage, the one after the current stage - if any.
     *
     * If the specified stage provided is the last stage, then null will be returned.
     *
     * @param int $initial_stage_id
     * @return workflow_stage|null
     */
    public function get_next_stage(int $initial_stage_id): ?workflow_stage {
        $stages = $this->stages;
        $stages->rewind();
        // count - 1 here prevents us from going over the end
        for ($i = 0, $c = $stages->count() - 1; $i < $c; $i++) {
            $stage = $stages->current();
            if ($stage->id == $initial_stage_id) {
                $stages->next();
                return $stages->current();
            }
            $stages->next();
        }
        return null;
    }

    /**
     * Get the stage before the specified stage id.
     *
     * @param int $stage_id
     *
     * @return workflow_stage|null
     */
    public function get_previous_stage(int $stage_id): ?workflow_stage {
        $stages = $this->stages;
        $previous_stage = null;

        foreach ($stages as $stage) {
            if ($stage->id === $stage_id) {
                break;
            }
            $previous_stage = $stage;
        }

        return $previous_stage;
    }

    /**
     * Check if the given approval level belongs to any stage within this workflow version
     *
     * @param int $approval_level_id
     * @return bool
     */
    public function has_approval_level(int $approval_level_id): bool {
        return builder::table(approval_level_entity::TABLE, 'l')
            ->join([workflow_stage_entity::TABLE, 's'], 'workflow_stage_id', 'id')
            ->where('s.workflow_version_id', $this->id)
            ->where('l.id', $approval_level_id)
            ->exists();
    }

    /**
     * Delete a stage from this workflow.
     *
     * Reorders remaining stage ordinal numbers.
     *
     * @param workflow_stage $stage The stage to delete, must belong to this version
     * @return self
     */
    public function delete_stage(workflow_stage $stage): self {
        if ($stage->workflow_version_id != $this->id) {
            throw new coding_exception('Cannot delete a foreign stage');
        }

        if ($this->status !== status::DRAFT) {
            throw new model_exception("Can only delete stage from a draft workflow version");
        }

        builder::get_db()->transaction(function () use ($stage) {
            $stage->delete();

            // Move all following stages back one ordinal number.
            (new move(new stage_ordinal()))->execute($this, $stage);
        });

        if ($this->entity->relation_loaded('stages')) {
            $this->entity->load_relation('stages');
        }

        return $this;
    }
}
