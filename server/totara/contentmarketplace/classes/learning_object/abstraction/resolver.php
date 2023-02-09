<?php
/**
 * This file is part of Totara Core
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_contentmarketplace
 */
namespace totara_contentmarketplace\learning_object\abstraction;

use core\orm\entity\entity;
use totara_contentmarketplace\learning_object\abstraction\metadata\model;

abstract class resolver {
    /**
     * resolver constructor.
     */
    final public function __construct() {
        // Prevent complicate construction.
    }

    /**
     * Get the entity class that is used.
     *
     * @return string|entity
     */
    abstract public static function get_entity_class(): string;

    /**
     * Get the field in the table that is used as a unique identifier for the learning object record outside of Totara.
     *
     * @return string
     */
    abstract public static function get_external_id_field(): string;

    /**
     * Load a model instance from an entity instance.
     *
     * @param entity $entity
     * @return model
     */
    abstract protected static function load_model_from_entity(entity $entity): model;

    /**
     * Finding the learning object record via id.
     *
     * @param int  $id
     * @param bool $strict
     *
     * @return model|null
     */
    public function find(int $id, bool $strict = false): ?model {
        $repository = static::get_entity_class()::repository();

        if ($strict) {
            $entity = $repository->find_or_fail($id);
        } else {
            $entity = $repository->find($id);

            if (null === $entity) {
                return null;
            }
        }

        return static::load_model_from_entity($entity);
    }

    /**
     * Find the learning object record by the unique identifier for the learning object record that is used outside of Totara.
     *
     * @param string $external_id
     * @param bool $strict
     * @return model|null
     */
    public function find_by_external_identifier(string $external_id, bool $strict = false): ?model {
        $repository = static::get_entity_class()::repository();

        $entity = $repository->where(static::get_external_id_field(), $external_id)->one($strict);
        if (!$entity) {
            return null;
        }

        return static::load_model_from_entity($entity);
    }

    /**
     * @return string
     */
    public static function get_component(): string {
        $class_name = static::class;
        $parts = explode('\\', $class_name);

        return reset($parts);
    }

    /**
     * Checking whether user had been completed or not with the condition from
     * content marketplace provider.
     *
     * @param int $user_id
     * @param int $learning_object_id
     * @return bool
     */
    public function has_user_completed_on_marketplace_condition(int $user_id, int $learning_object_id): bool {
        return false;
    }

}
