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

use core\orm\entity\model;
use mod_approval\entity\workflow\workflow_stage_formview as workflow_stage_formview_entity;
use mod_approval\exception\model_exception;
use mod_approval\model\active_trait;
use mod_approval\model\model_trait;
use mod_approval\model\workflow\stage_feature\formviews;

/**
 * Approval Workflow Stage Formview (form field) model
 *
 * Properties:
 * @property-read int $id Database record ID
 * @property-read string $field_key Form field key (from JSON schema) this formview references
 * @property-read string $visibility Configured visibility of the form field
 * @property-read int $workflow_stage_id Parent workflow_stage
 * @property-read bool $required Is the field required at this stage?
 * @property-read bool $disabled Is the field disabled at this stage?
 * @property-read string|null $default_value Override the default value at this stage
 * @property-read bool $active Is this formview active or not?
 * @property-read int $created Created timestamp
 * @property-read int $updated Last modified timestamp; same as created if not modified
 *
 * Relationships:
 * @property-read workflow_stage $workflow_stage Parent workflow_stage
 *
 * Methods:
 * @method static self load_by_id(int $id)
 * @method static self load_by_entity(workflow_stage_formview_entity $entity)
 */
final class workflow_stage_formview extends model {

    use active_trait;
    use model_trait;

    /** @var workflow_stage_formview_entity */
    protected $entity;

    /** @var string[] */
    protected $entity_attribute_whitelist = [
        'id',
        'field_key',
        'workflow_stage_id',
        'required',
        'disabled',
        'default_value',
        'active',
        'created',
        'updated',
    ];

    /** @var string[] */
    protected $model_accessor_whitelist = [
        'workflow_stage',
        'visibility',
    ];

    /** @var string[] */
    protected $deactivate_checklist = [];

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    protected static function get_entity_class(): string {
        return workflow_stage_formview_entity::class;
    }

    /**
     * Create a workflow stage formview.
     *
     * @param workflow_stage $workflow_stage Parent workflow_stage
     * @param string $field_key Form field key from JSON schema
     * @param boolean $required Is the field required?
     * @param boolean $disabled Is the field disabled?
     * @param string|null $default_value Override the default value at this stage
     * @return self
     */
    public static function create(
        workflow_stage $workflow_stage,
        string $field_key,
        bool $required,
        bool $disabled,
        ?string $default_value
    ): self {
        // Todo TL-33350: Deprecate this method. Formviews should be set through the feature manager.
        // That way we can enforce the stage type logic and also avoid the possibility of having duplicate formviews
        // which violates the model.
        if (empty($field_key)) {
            throw new model_exception('Workflow stage form view field key cannot be empty');
        }
        if (!$workflow_stage->active) {
            throw new model_exception("Workflow stage must be active");
        }
        $entity = new workflow_stage_formview_entity();
        $entity->field_key = $field_key;
        $entity->workflow_stage_id = $workflow_stage->id;
        $entity->required = $required;
        $entity->disabled = $disabled;
        $entity->default_value = $default_value;
        $entity->active = true;
        $entity->save();
        return self::load_by_entity($entity);
    }

    /**
     * Get the parent workflow stage.
     * @return workflow_stage
     */
    public function get_workflow_stage(): workflow_stage {
        return workflow_stage::load_by_entity($this->entity->workflow_stage);
    }

    /**
     * Get enum representing the configured visibility.
     *
     * @return string
     */
    public function get_visibility(): string {
        return formviews::resolve_visibility_enum($this->required, $this->disabled);
    }

    /**
     * Create new workflow_stage_formview by cloning itself
     *
     * @param workflow_stage $workflow_stage
     * @return $this
     */
    public function clone(workflow_stage $workflow_stage): self {
        return self::create(
            $workflow_stage,
            $this->field_key,
            $this->required,
            $this->disabled,
            $this->default_value
        );
    }
}