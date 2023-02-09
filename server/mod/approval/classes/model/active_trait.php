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

namespace mod_approval\model;

use mod_approval\entity\application\application as application_entity;
use mod_approval\exception\model_exception;

/**
 * Trait to provide consistent support for activating and deactivating model instances.
 *
 * Models using this trait must set a `deactivate_checklist` property that is an array of model_class => foreign key pairs
 * See the can_deactivate() method for more information.
 *
 * @package mod_approval\model
 */
trait active_trait {

    /**
     * Activate this object.
     *
     * Changes made to to this function must be synchronised with assigment_approver::activate()
     *
     * @return self
     */
    public function activate(): self {
        if (!$this->entity->active) {
            $this->entity->active = true;
            $this->entity->save();
        }
        return $this;
    }

    /**
     * Deactivate this object.
     *
     * Changes made to to this function must be synchronised with assigment_approver::deactivate()
     *
     * @return self
     */
    public function deactivate(bool $force = false): self {
        if ($this->entity->active) {
            if (!$force) {
                // Check for active dependencies
                if (!$this->can_deactivate()) {
                    throw new model_exception("Cannot deactivate object with active dependencies");
                }
            }
            $this->entity->active = false;
            $this->entity->save();
        }
        return $this;
    }

    /**
     * Checks each of the repositories in this->deactivate_checklist to see if there are any dependent items
     * which might be blocking deactivation.
     *
     * There are three kinds of model object which may be dependencies:
     *   1) Applications, which must all be completed to deactivate this
     *   2) Models with an active field, which must all be inactive to deactivate this
     *   3) Models with a status field, which must all be draft to deactivate this
     *
     * @return bool
     */
    public function can_deactivate(): bool {
        if (!isset($this->deactivate_checklist)) {
            debugging('Model classes which use active_trait must have a deactivate_checklist array property.', DEBUG_DEVELOPER);
            return false;
        }
        foreach ($this->deactivate_checklist as $classname => $fieldname) {
            $entity_classname = $classname::get_entity_class();
            if ($entity_classname === application_entity::class) {
                // Check for in-flight applications
                $dependent_item_exists = application_entity::repository()
                    ->where($fieldname, '=', $this->id)
                    ->where_null('completed')
                    ->exists();
            } else if (method_exists($classname, 'can_deactivate')) {
                // Class implements active_trait, check repository.
                $dependent_item_exists = $entity_classname::repository()
                    ->where($fieldname, '=', $this->id)
                    ->where('active', '=', true)
                    ->exists();
            } else {
                // Check that status of dependencies is not active or archived
                $dependent_item_exists = $entity_classname::repository()
                    ->where($fieldname, '=', $this->id)
                    ->where('status', 'in', [status::ACTIVE, status::ARCHIVED])
                    ->exists();
            }
            if ($dependent_item_exists) {
                return false;
            }
        }
        return true;
    }
}