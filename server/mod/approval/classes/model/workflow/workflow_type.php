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
use mod_approval\entity\workflow\workflow_type as workflow_type_entity;
use mod_approval\exception\model_exception;
use mod_approval\model\active_trait;
use mod_approval\model\model_trait;

/**
 * Approval workflow type model
 *
 * Properties:
 * @property-read int $id Database record ID
 * @property-read string $name Human readable type name
 * @property-read string|null $description Workflow type description
 * @property-read bool $active Is this workflow_type active or not?
 * @property-read int $created Created timestamp
 *
 * Relationships:
 * @property-read collection|workflow[] $workflows Collection of workflows of this type
 *
 * Methods:
 * @method static self load_by_id(int $id)
 * @method static self load_by_entity(workflow_type_entity $entity)
 */
final class workflow_type extends model {

    use active_trait;
    use model_trait;

    /** @var workflow_type_entity */
    protected $entity;

    /** @var string[] */
    protected $entity_attribute_whitelist = [
        'id',
        'name',
        'description',
        'active',
        'created',
    ];

    /** @var string[] */
    protected $model_accessor_whitelist = [
        'workflows',
    ];

    /** @var string[] */
    protected $deactivate_checklist = [
        workflow::class => 'workflow_type_id'
    ];

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    protected static function get_entity_class(): string {
        return workflow_type_entity::class;
    }

    /**
     * Returns the workflow_type name
     *
     * @return string
     */
    public function __toString() {
        return $this->name;
    }

    /**
     * Create a workflow stage type.
     *
     * @param string $name Human-readable type name
     * @param string $description Workflow type description
     * @return self
     */
    public static function create(string $name, string $description = ''): self {
        if (empty($name)) {
            throw new model_exception('Workflow type name cannot be empty');
        }

        $entity = new workflow_type_entity();
        $entity->name = $name;
        $entity->description = $description;
        $entity->active = true;
        $entity->save();
        return self::load_by_entity($entity);
    }

    /**
     * Get the workflows which have this workflow_type
     * @return collection|workflow[]
     */
    public function get_workflows(): collection {
        return $this->entity->workflows->map_to(workflow::class);
    }

    /**
     * Delete the record.
     * @return self
     */
    public function delete(): self {
        $this->entity->delete();
        return $this;
    }
}