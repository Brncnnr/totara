<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\activity;

use core\collection;
use core\orm\entity\model;
use mod_perform\entity\activity\activity_type as activity_type_entity;

/**
 * Represents a single performance activity type.
 *
 * @property-read int $id
 * @property-read string $name
 * @property-read string $display_name
 * @property-read bool $is_system
 * @property-read collection|activity[] $activities
 *
 * @package mod_perform\models\activity
 */
class activity_type extends model {
    /**
     * @var activity_type_entity
     */
    protected $entity;

    /**
     * {@inheritdoc}
     */
    protected $entity_attribute_whitelist = [
        'id',
        'name',
        'is_system'
    ];

    /**
     * {@inheritdoc}
     */
    protected $model_accessor_whitelist = [
        'activities',
        'display_name'
    ];

    /**
     * {@inheritdoc}
     */
    protected static function get_entity_class(): string {
        return activity_type_entity::class;
    }

    /**
     * Creates a new activity type instance.
     *
     * @param string $name type name.
     *
     * @return activity_type the newly created type.
     */
    public static function create(string $name): activity_type {
        $entity = new activity_type_entity();
        $entity->name = $name;
        $entity->is_system = false;
        $entity->save();

        return new activity_type($entity);
    }

    /**
     * Retrieves a type by its id.
     *
     * @param int $id the id of the type to load.
     *
     * @return activity_type if it exists, null otherwise.
     */
    public static function load_by_id(int $id): ?activity_type {
        return activity_type_entity::repository()
            ->where('id', $id)
            ->get()
            ->map_to(activity_type::class)
            ->first();
    }

    /**
     * Retrieves a type by its name.
     *
     * @param string $name the type name.
     *
     * @return activity_type if it exists, null otherwise.
     */
    public static function load_by_name(string $name): ?activity_type {
        return activity_type_entity::repository()
            ->where('name', $name)
            ->get()
            ->map_to(activity_type::class)
            ->first();
    }

    /**
     * Returns the activities with this type.
     *
     * @return collection|activity[] the activities
     */
    public function get_activities(): collection {
        return $this->entity->activities->map_to(activity::class);
    }

    /**
     * Returns the display text. This is just the lang string generated from the
     * type name.
     *
     * @return string the display name.
     */
    public function get_display_name(): string {
        if ($this->entity->is_system) {
            return get_string('system_activity_type:' . $this->entity->name, 'mod_perform');
        }

        return format_string($this->entity->name);
    }
}
