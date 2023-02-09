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
use mod_approval\entity\workflow\workflow_stage as workflow_stage_entity;
use mod_approval\entity\workflow\workflow_stage_approval_level as workflow_stage_approval_level_entity;
use mod_approval\entity\workflow\workflow_stage_formview as workflow_stage_formview_entity;
use mod_approval\entity\workflow\workflow_stage_interaction as workflow_stage_interaction_entity;
use mod_approval\event\workflow_stage_created;
use mod_approval\event\workflow_stage_deleted;
use mod_approval\event\workflow_stage_form_views_updated;
use mod_approval\event\workflow_stage_edited;
use mod_approval\exception\model_exception;
use mod_approval\model\active_trait;
use mod_approval\model\application\action\action;
use mod_approval\model\application\application;
use mod_approval\model\model_trait;
use mod_approval\model\status;
use mod_approval\model\workflow\workflow_stage_interaction;
use mod_approval\model\workflow\stage_feature\base as stage_feature_base;
use mod_approval\model\workflow\stage_feature\feature_manager;
use mod_approval\model\workflow\stage_type\base as stage_type_base;
use mod_approval\model\workflow\stage_type\provider as stage_type_provider;
use mod_approval\model\workflow\stage_type\finished;
use mod_approval\model\workflow\stage_type\state_manager\base as state_manager;

/**
 * Approval workflow stage model
 *
 * Properties:
 *
 * @property-read int $id Database record ID
 * @property-read int $workflow_version_id Parent workflow_version ID
 * @property-read string $name Human-readable name
 * @property-read int $ordinal_number Ordinal number of this stage (1, 2, 3, ...)
 * @property-read bool $active Is this stage active or not?
 * @property-read stage_type_base|string $type The type of the workflow stage.
 * @property-read feature_manager $feature_manager Feature manager for the workflow stage.
 * @property-read state_manager $state_manager State manager for an application in the workflow stage.
 * @property-read array $features Features available in the workflow stage.
 * @property-read int $created Creation timestamp
 * @property-read int $updated Last-modified timestamp; same as created if not modified
 *
 * Relationships:
 * @property-read workflow_version $workflow_version Parent workflow_version
 * @property-read collection|workflow_stage_approval_level[] $approval_levels Collection of approval levels at this stage
 * @property-read collection|workflow_stage_formview[] $formviews Collection of formview definitions at this stage
 * @property-read collection|workflow_stage_interaction[] $interactions Collection of interactions at this stage
 *
 * Methods:
 * @method static self load_by_id(int $id)
 * @method static self load_by_entity(workflow_stage_entity $entity)
 */
class workflow_stage extends model {

    use active_trait;
    use model_trait;

    /** @var workflow_stage_entity */
    protected $entity;

    /** @var string[] */
    protected $entity_attribute_whitelist = [
        'id',
        'workflow_version_id',
        'name',
        'active',
        'created',
        'updated',
    ];

    /** @var string[] */
    protected $model_accessor_whitelist = [
        'workflow_version',
        'approval_levels',
        'formviews',
        'interactions',
        'type',
        'features',
        'feature_manager',
        'state_manager',
        'ordinal_number',
    ];

    /** @var string[] */
    protected $deactivate_checklist = [
        application::class => 'current_stage_id',
    ];

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    protected static function get_entity_class(): string {
        return workflow_stage_entity::class;
    }

    /**
     * Get the workflow_version this workflow_stage uses.
     *
     * @return workflow_version
     */
    public function get_workflow_version(): workflow_version {
        return workflow_version::load_by_entity($this->entity->workflow_version);
    }

    /**
     * Get the approval levels using this workflow stage.
     *
     * @return collection|workflow_stage_approval_level[]
     */
    public function get_approval_levels(): collection {
        return $this->entity->approval_levels->map_to(workflow_stage_approval_level::class);
    }

    /**
     * Get the formviews associated with this workflow stage.
     *
     * @return collection|workflow_stage_formview[]
     */
    public function get_formviews(): collection {
        return $this->entity->formviews->map_to(workflow_stage_formview::class);
    }

    /**
     * Get the interactions associated with this workflow stage.
     *
     * @return collection|workflow_stage_interaction[]
     */
    public function get_interactions(): collection {
        return $this->entity->interactions->map_to(workflow_stage_interaction::class);
    }

    /**
     * Get the ordinal number in the associated workflow version.
     *
     * @return integer
     */
    public function get_ordinal_number(): int {
        // Secret: the ordinal number is identical to the sort order.
        return $this->entity->sortorder;
    }

    /**
     * Get workflow stage type.
     *
     * @return stage_type_base|string
     */
    public function get_type(): string {
        return stage_type_provider::get_by_code($this->entity->type_code);
    }

    /**
     * Get features available in this stage.
     *
     * @return stage_feature_base[]|array
     */
    public function get_features(): array {
        return $this->feature_manager->all();
    }

    /**
     * Get feature manager for this stage.
     *
     * @return feature_manager
     */
    public function get_feature_manager(): feature_manager {
        $features = $this->type::get_configured_features();

        return new feature_manager($features, $this);
    }

    /**
     * Get the state manager used by an application in this stage.
     *
     * @return state_manager
     */
    public function get_state_manager(): state_manager {
        return $this->type::state_manager($this);
    }

    /**
     * Add approval level to workflow.
     *
     * @param string $name
     * @return workflow_stage_approval_level
     */
    public function add_approval_level(string $name = ''): workflow_stage_approval_level {
        return $this->feature_manager->approval_levels->add($name);
    }

    /**
     * Delete approval level from workflow.
     *
     * @param workflow_stage_approval_level $level An approval level
     * @return self
     */
    public function delete_approval_level(workflow_stage_approval_level $level): self {
        return $this->feature_manager->approval_levels->delete($level);
    }

    /**
     * Edit approval level from workflow.
     *
     * @param int $level_id
     * @param string $name
     * @return workflow_stage_approval_level
     */
    public function edit_approval_level(int $level_id, string $name): workflow_stage_approval_level {
        return $this->feature_manager->approval_levels->edit($level_id, $name);
    }

    /**
     * Configure formview for the stage - alias method for feature_manager->formviews->configure()
     *
     * @param array $updates
     * @return void
     */
    public function configure_formview(array $updates): void {
        builder::get_db()->transaction(function () use ($updates) {
            foreach ($updates as $formview_update) {
                $field_key = $formview_update['field_key'];
                $visibility_enum = $formview_update['visibility'];
                $this->feature_manager->formviews->configure($field_key, $visibility_enum);
            }
            $this->refresh(true);

            // Trigger event
            workflow_stage_form_views_updated::execute($this);
        });
    }

    /**
     * Add interaction to workflow stage.
     *
     * @param action $action
     * @return workflow_stage_interaction
     */
    public function add_interaction(action $action): workflow_stage_interaction {
        return $this->feature_manager->interactions->add($action);
    }

    /**
     * Delete interaction from workflow stage.
     *
     * @param workflow_stage_interaction $interaction An interaction
     * @return self
     */
    public function delete_interaction(workflow_stage_interaction $interaction): self {
        return $this->feature_manager->interactions->delete($interaction);
    }

    /**
     * Change the order of approval levels.
     *
     * @param workflow_stage_approval_level[] $new_levels all approval levels in this workflow stage.
     * @return self
     */
    public function reorder_approval_levels(array $new_levels): self {
        return $this->feature_manager->approval_levels->reorder($new_levels);
    }

    /**
     * Gives this stage a new name.
     *
     * @param string $name
     * @return self
     */
    public function set_name(string $name): self {
        $this->entity->name = $name;
        $this->entity->save();

        // Trigger event
        workflow_stage_edited::execute($this);
        return $this;
    }

    /**
     * Create a workflow stage.
     *
     * @param workflow_version $workflow_version Related workflow_version
     * @param string $name Human-readable name
     * @param string $type_enum Enum representing the workflow stage type
     * @param bool $is_clone Flag for cloning
     *
     * @return self
     */
    public static function create(workflow_version $workflow_version, string $name, string $type_enum, bool $is_clone = false): self {
        if ($name === '') {
            throw new coding_exception('name cannot be empty');
        }

        if ($workflow_version->status !== status::DRAFT) {
            throw new model_exception("Can only add stage to a draft workflow version");
        }

        $stage_type = stage_type_provider::get_by_enum($type_enum);

        if ($type_enum == finished::get_enum()) {
            // Finished stages are created at the end of the sortorder.
            $after_stage = workflow_stage_entity::repository()
                ->where('workflow_version_id', '=', $workflow_version->id)
                ->order_by('sortorder', 'DESC')
                ->first();
        } else {
            // Other stages are created before the finished stages.
            $after_stage = workflow_stage_entity::repository()
                ->where('workflow_version_id', '=', $workflow_version->id)
                ->where('type_code', '!=', finished::get_code())
                ->order_by('sortorder', 'DESC')
                ->first();
        }

        $entity = new workflow_stage_entity();
        $entity->workflow_version_id = $workflow_version->id;
        $entity->name = $name;
        $entity->type_code = $stage_type::get_code();
        $entity->active = true;

        /* @var $stage workflow_stage */
        $stage = builder::get_db()->transaction(function () use ($entity, $workflow_version, $after_stage) {
            // Ideally we'd use the stage ordinal class, by saying "insert after this existing item",
            // which would replace all the code below. TODO in TL-33145.

            // Sort order of new stage is after the after_stage's sortorder.
            $new_sortorder = ($after_stage->sortorder ?? 0) + 1;
            $entity->sortorder = $new_sortorder;

            // Make space in the sortorder by incrementing all following stages (if any).
            builder::get_db()->execute(
                "UPDATE {" . workflow_stage_entity::TABLE . "} " .
                "SET sortorder = sortorder + 1 " .
                "WHERE workflow_version_id = :workflow_version_id " .
                "AND sortorder >= :sortorder",
                [
                    'workflow_version_id' => $workflow_version->id,
                    'sortorder' => $new_sortorder,
                ]
            );
            // Replace code above in TL-33145.

            $entity->save();
            $stage = self::load_by_entity($entity);
            return $stage;
        });

        // Execute each feature's add_default method for new stage, skip if clone.
        if(!$is_clone) {
            foreach ($stage_type::get_configured_features() as $feature) {
                /* @var $stage_feature stage_feature_base */
                $stage_feature = new $feature($stage);
                $stage_feature->add_default();
            }
            $stage->refresh(true);
        }

        // Trigger event
        workflow_stage_created::execute($stage);

        return $stage;
    }

    /**
     * Delete the record.
     *
     * @return self
     */
    public function delete(): self {
        builder::get_db()->transaction(function () {
            workflow_stage_approval_level_entity::repository()
                ->where('workflow_stage_id', $this->id)
                ->get()
                ->map_to(function ($level) {
                    return workflow_stage_approval_level::load_by_entity($level)->delete();
                });
            workflow_stage_interaction_entity::repository()
                ->where('workflow_stage_id', $this->id)
                ->get()
                ->map_to(function ($interaction) {
                    return workflow_stage_interaction::load_by_entity($interaction)->delete();
                });

            // Trigger event
            workflow_stage_deleted::execute($this);

            $this->entity->delete();
        });
        return $this;
    }

    /**
     * Create new workflow_stage by cloning itself
     *
     * @param workflow_version $workflow_version
     * @return workflow_stage
     */
    public function clone(workflow_version $workflow_version): workflow_stage {
        return self::create(
            $workflow_version,
            $this->name,
            $this->type::get_enum(),
            true
        );
    }
}
