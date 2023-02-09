<?php
/*
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package core
 */

namespace core\orm\entity;

/**
 * Class model
 *
 * Provides common properties and functionality to link a model and an entity. By default, it exposes
 * the properties of the entity through the model, without allowing direct access, and can be overridden
 * to restrict access.
 *
 * @property-read int $id
 */
abstract class model {

    /**
     * Entity this model is based upon
     *
     * @var entity
     */
    protected $entity;

    /**
     * This represents a whitelist of entity attributes to be exposed via the magic getter.
     * NULL means all entity attributes can be accessed.
     *
     * Warning: Do not whitelist an entity attribute which is also available through model_accessor_whitelist.
     *
     * @var string[]|null
     */
    protected $entity_attribute_whitelist = null;

    /**
     * This represents a whitelist of model methods to be exposed via the magic getter.
     * The attribute will be prefixed with 'get_' before being called on the method.
     *
     * Warning: Do not whitelist a model accessor which is also available through entity_attribute_whitelist.
     *
     * @var string[]
     */
    protected $model_accessor_whitelist = [];

    /**
     * This represents the entity class related to the model
     *
     * @return string
     */
    abstract protected static function get_entity_class(): string;

    public function __construct(entity $entity) {
        if (!$entity->exists()) {
            throw new \coding_exception('Can load only existing entities');
        }

        $this->entity = $entity;
    }

    /**
     * Get associated entity ID
     *
     * @return int
     */
    public function get_id(): int {
        return $this->entity->id;
    }

    /**
     * Magic attribute getter
     *
     * @param string $name
     * @return mixed|null
     */
    public function __get(string $name) {
        if (!$this->has_attribute($name)) {
            throw new \coding_exception("Tried to access a property that is not available: " . $name);
        }

        if (in_array($name, $this->model_accessor_whitelist)) {
            $get_function = 'get_' . $name;
            return $this->$get_function();
        }

        if (is_null($this->entity_attribute_whitelist)
            || in_array($name, $this->entity_attribute_whitelist)
        ) {
            return $this->entity->get_attribute($name);
        }

        throw new \coding_exception('Tried to access an unknown entity or method attribute: ' . $name);
    }

    /**
     * Sub class not allowed set attributes via magic setter
     *
     * @param string $name
     * @param mixed $value
     * @throws \coding_exception
     */
    final public function __set(string $name, $value) {
        throw new \coding_exception("setting the ".clean_string($name) ." attribute is not allowed ");
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool {
        $has_attribute = $this->has_attribute($name);
        if ($has_attribute === true) {
            $value = $this->{$name};
            if ($value !== null) {
                return true;
            }
        }
        return false;
    }

    /**
     * A helper to check whether an attribute with a given name can be accessed
     *
     * @param string $name
     * @return bool
     */
    public function has_attribute(string $name): bool {
        if (in_array($name, $this->model_accessor_whitelist)) {
            $get_function = 'get_' . $name;
            if (!method_exists($this, $get_function)) {
                throw new \coding_exception('Tried to access a method attribute which should exist but does not: ' . $get_function);
            }
            return true;
        }

        if (is_null($this->entity_attribute_whitelist)) {
            return $this->entity->has_attribute($name) || $this->entity->relation_exists($name);
        }

        if (in_array($name, $this->entity_attribute_whitelist)) {
            if (!$this->entity->has_attribute($name) && !$this->entity->relation_exists($name)) {
                throw new \coding_exception('Tried to access an entity attribute which should exist but does not: ' . $name);
            }
            return true;
        }

        return false;
    }

    /**
     * Gets a model object based on the given entity
     *
     * The entity class must match the type used by the model subclass.
     *
     * @param $entity entity
     * @return static
     */
    public static function load_by_entity(entity $entity) {
        $entity_class = static::get_entity_class();

        if (!$entity instanceof $entity_class) {
            throw new \coding_exception('Expected entity class to match model class');
        }

        if (!$entity->exists()) {
            throw new \coding_exception('Can load only existing entities');
        }

        return new static($entity);
    }

    /**
     * Gets a model object based on the given id
     *
     * @param int $id
     * @return static
     */
    public static function load_by_id(int $id) {
        $entity_class = static::get_entity_class();

        $entity = new $entity_class($id);

        return static::load_by_entity($entity);
    }

    /**
     * Get a clone of the entity that this model encapsulates.
     * Note that we do not allow direct access to the model's entity as we do not want the model's state to be externally modifiable.
     *
     * @return entity
     */
    final public function get_entity_copy(): entity {
        $entity_class = static::get_entity_class();
        $entity_data = $this->entity->to_record(true);
        return new $entity_class($entity_data, false, true);
    }

}