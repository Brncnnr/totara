<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package core_orm
 */

namespace core\orm\entity;

use coding_exception;
use core\orm\collection;
use core\orm\entity\relations\belongs_to;
use core\orm\entity\relations\has_many;
use core\orm\entity\relations\has_many_through;
use core\orm\entity\relations\has_one;
use core\orm\entity\relations\has_one_through;
use core\orm\query\builder;
use core_component;
use JsonSerializable;
use stdClass;

/**
 * ORM entity represents a record in the database
 *
 * @property int $id
 */
abstract class entity implements JsonSerializable {

    /**
     * entity table
     *
     * @var string
     */
    public const TABLE = '';

    /**
     * Model attributes
     *
     * @var array
     */
    private $attributes = [];

    /**
     * Model relations
     *
     * @var array
     */
    protected $relations = [];

    /**
     * Dirty attributes to be saved in the database
     *
     * @var array
     */
    private $dirty = [];

    /**
     * Extra attributes to export to array (json).
     *
     * @var array
     */
    protected $extra_attributes = [];

    /**
     * A flag whether the model exists
     *
     * @var bool
     */
    protected $exists = false;

    /**
     * List of eager-loaded relations.
     *
     * @var string[]
     */
    protected $with = [];

    /**
     * A constant to indicate that a entity has a field with timestamp when it was created
     *
     * @var string
     */
    public const CREATED_TIMESTAMP = '';

    /**
     * A constant to indicate that a entity has a field with timestamp when it was updated
     *
     * @var string
     */
    public const UPDATED_TIMESTAMP = '';

    /**
     * A flag to indicate whether an updated timestamp should be populated when entity is created
     *
     * @var bool
     */
    public const SET_UPDATED_WHEN_CREATED = false;

    /**
     * Update timestamps flag
     *
     * @var bool
     */
    protected $update_timestamps = true;

    /**
     * @var bool
     */
    private $is_deleted = false;

    /**
     * Internal attributes controlled via constants
     */
    private $table;
    private $created_timestamp;
    private $updated_timestamp;
    private $set_updated_when_created;

    /**
     * If id is passed it tries to find entity in the database.
     * if you pass an array or object it would try to instantiate the entity assuming that the data
     * has already been queried from the database.
     *
     * If nothing is passed it would instantiate a new empty entity object.
     *
     * If an array of attributes is passed we validate those by default, this can be switched off
     * for trusted cases. For example, when the repository instantiates new instances we trust
     * the attributes coming from the database
     *
     * @param array|object|int|null $id
     * @param bool $validate by default we validate all attributes
     * @param bool|null $exists A flag whether this model already exists
     */
    public function __construct($id = null, bool $validate = true, ?bool $exists = null) {
        $this->init_internal_properties();

        switch (true) {
            // If it's really an id try to load this entity
            case is_numeric($id):
                $attributes = builder::table($this->get_table())->find_or_fail($id);
                $this->exists = true;
                break;
            // All those formats can be casted to an array
            case $id instanceof stdClass:
            case is_array($id):
            case $id === null:
                $attributes = (array) $id;
                // Auto-detect whether the entity exists if the exists attribute is not explicitly passed.
                // If id is present among attributes and > 0 then we mark model as exists.
                if ($exists === null) {
                    $this->exists = isset($attributes['id']) && intval($attributes['id']) > 0;
                } else {
                    $this->exists = $exists;
                }
                break;
            default:
                throw new coding_exception("Invalid param '{\$id}' for entity " . static::class);
        }

        // Set and validate attributes
        $this->set_attributes_raw($attributes, ($validate && !is_numeric($id)));
        $this->reset_dirty();
    }

    /**
     * For easier debugging we store the values of the constants in
     * private properties
     */
    private function init_internal_properties() {
        if (empty(static::TABLE)) {
            throw new coding_exception('Missing table name for entity ' . static::class);
        }
        $this->table = static::TABLE;
        $this->created_timestamp = static::CREATED_TIMESTAMP;
        $this->updated_timestamp = static::UPDATED_TIMESTAMP;
        $this->set_updated_when_created = static::SET_UPDATED_WHEN_CREATED;
    }

    /**
     * Validate attribute against columns and extra attributes
     * Throws an exception if validation failed
     *
     * @param $attribute
     * @return void
     * @throws coding_exception
     */
    protected function validate_attribute_for_insert(string $attribute) {
        // Always allow ID
        if ($attribute == 'id') {
            return;
        }

        // If the attribute is already set we assume it's OK
        if (array_key_exists($attribute, $this->attributes)) {
            return;
        }

        global $CFG;
        // For performance reasons we validate the column only in debug mode
        if (!$CFG->debugdeveloper || $this->db_column_exists($attribute)) {
            return;
        }

        throw new coding_exception("Invalid attribute '{$attribute}' passed to the entity " . static::class);
    }

    /**
     * Return whether an attribute exists without the exception
     *
     * @param string $attribute
     * @return bool
     */
    public function has_attribute(string $attribute) {
        // Always allow ID
        if ($attribute == 'id') {
            return true;
        }

        // If the attribute is already set we assume it's OK
        if (array_key_exists($attribute, $this->attributes)) {
            return true;
        }

        if (!in_array($attribute, $this->extra_attributes) && !$this->build_getter_method_name($attribute) && !$this->db_column_exists($attribute)) {
            return false;
        }

        return true;
    }

    /**
     * Return whether database column exists
     *
     * @param $column
     * @return bool
     */
    protected function db_column_exists($column) {
        // The columns are cached so this should be relatively fast.  ¯\_(=))_/¯
        $columns = $this->get_db_columns();
        return array_key_exists($column, $columns);
    }

    /**
     * Get database columns
     *
     * @param bool $force Force reload
     * @return array
     */
    protected function get_db_columns(bool $force = false) {
        return builder::get_db()->get_columns(static::TABLE, !$force);
    }

    /**
     * This is a factory method returning either an instance of the generic repository
     * or a custom repository if there's a class with the same name suffixed by _repository
     * in the same folder as the entity
     *
     * Example:
     *  my_entity and my_entity_repository
     *
     * @return repository
     */
    final public static function repository(): repository {
        $repository_name = static::repository_class_name();
        // Checking if the repository class exists using class_exists() with autoloading will have a
        // performance impact as it always checks the PSR classes. Therefore we use our own class_exists
        // method which will have less of an impact in this case
        if (!core_component::class_exists($repository_name, false) && !class_exists($repository_name, false)) {
            $repository_name = repository::class;
        } else if (!is_subclass_of($repository_name, repository::class)) {
            throw new coding_exception('Custom repositories must extend the repository class.');
        }
        return new $repository_name(static::class);
    }

    /**
     * Get repository class name.
     *
     * Defaults to entity_class_repository
     *
     * @return string
     */
    public static function repository_class_name(): string {
        return static::class.'_repository';
    }

    /**
     * Shortcut to repository method
     * If you need to extend this method do it on the repository.
     *
     * @return $this
     */
    final public function save(): entity {
        static::repository()->save_entity($this);
        return $this;
    }

    /**
     * Shortcut to repository method
     * If you need to extend this method do it on the repository.
     *
     * @return $this
     */
    final public function delete(): entity {
        static::repository()->delete_entity($this);
        return $this;
    }

    /**
     * Shortcut to repository method
     * If you need to extend this method do it on the repository.
     *
     * @return $this
     */
    final public function create(): entity {
        static::repository()->create_entity($this);
        return $this;
    }

    /**
     * Shortcut to repository method
     * If you need to extend this method do it on the repository.
     *
     * @return $this
     */
    final public function update(): entity {
        static::repository()->update_entity($this);
        return $this;
    }


    /**
     * Update relationship for this model.
     *
     * @param string $name Relation name
     * @param entity|collection|null $result Loaded relation results
     * @return $this
     */
    public function relate(string $name, $result) {
        if (!$this->relation_exists($name)) {
            throw new coding_exception('relation does not exist in entity');
        }
        $this->relations[$name] = $result;

        return $this;
    }

    /**
     * Reload this entity from the database.
     *
     * @return $this
     */
    public function refresh() {
        if (!$this->id) {
            throw new coding_exception('Cannot refresh an entity without id.');
        }

        // To be on a safe side a new query builder is used.
        $attributes = builder::table($this->get_table())->find_or_fail($this->id);

        $this->set_attributes_raw((array)$attributes, false);
        $this->reset_dirty();

        return $this;
    }

    /**
     * Reset dirty attributes
     *
     * @return entity
     */
    public function reset_dirty() {
        $this->dirty = [];

        return $this;
    }

    public function get_dirty(): array {
        return $this->dirty;
    }

    /**
     * Mark attribute as dirty
     *
     * @param string $attribute
     * @param mixed $value
     * @return $this
     */
    protected function set_dirty(string $attribute, $value) {
        $this->dirty[$attribute] = $value;

        return $this;
    }

    /**
     * Returns whether an entity has changed since saved last time
     *
     * @return bool
     */
    public function changed(): bool {
        return !empty($this->dirty);
    }

    /**
     * Return whether the model exists
     *
     * @return bool
     */
    public function exists(): bool {
        return !$this->is_deleted && $this->exists;
    }

    /**
     * Return whether the model is deleted
     *
     * @return bool
     */
    public function deleted(): bool {
        return $this->is_deleted;
    }

    /**
     * ID attribute getter
     *
     * @param int|string $id
     * @return int|null
     */
    protected function get_id_attribute($id = null): ?int {
        if ($this->deleted()) {
            return null;
        }

        return !is_null($id) ? intval($id) : null;
    }

    /**
     * Get attribute
     *
     * @param string $name
     * @return mixed|null
     */
    public function get_attribute(string $name) {
        // If there's a custom getter method use it.
        if ($method = $this->build_getter_method_name($name)) {
            // If the attribute is set then pass it to the function
            // otherwise omit it and the getter has to have it's own defaults
            if (array_key_exists($name, $this->attributes)) {
                return $this->$method($this->attributes[$name]);
            } else {
                return $this->$method();
            }
        }

        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }

        if ($this->relation_loaded($name) || $this->relation_exists($name)) {
            return $this->get_relation($name);
        }

        global $CFG;
        // For performance reasons we validate the column only in debug mode
        if ($CFG->debugdeveloper && !$this->db_column_exists($name)) {
            debugging("Unknown attribute '{$name}' of entity " . static::class);
        }

        return $this->attributes[$name] ?? $this->get_default_db_value($name);
    }

    /**
     * Get default column value from db
     *
     * @param string $column
     * @return string|null
     */
    public function get_default_db_value(string $column) {
        /** @var \database_column_info $c */
        $c = $this->get_db_columns()[$column] ?? null;

        if (!$c) {
            return null;
        }

        return $c->has_default ? $c->default_value : null;
    }

    /**
     * Get attribute
     *
     * @param $name
     * @return mixed|null
     */
    public function __get($name) {
        return $this->get_attribute($name);
    }


    /**
     * Set the attribute directly without magic method.
     *
     * @param array $attributes
     * @param bool $validate check if it's valid columns, defaults to true
     * @return $this
     */
    protected function set_attributes_raw($attributes, bool $validate = true) {
        $attributes = (array) $attributes;
        foreach ($attributes as $name => $value) {
            $this->set_attribute_raw($name, $value, $validate);
        }
        return $this;
    }

    /**
     * Set the attribute directly without magic method.
     *
     * @param string $name
     * @param $value
     * @param bool $validate check if it's a valid column, defaults to true
     * @return $this
     */
    protected function set_attribute_raw(string $name, $value, bool $validate = true) {
        if ($validate) {
            $this->validate_attribute_for_insert($name);
        }
        $this->attributes[$name] = $value;
        return $this->set_dirty($name, $value);
    }

    /**
     * Set attribute
     *
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function set_attribute(string $name, $value) {
        $this->validate_attribute_for_insert($name);

        // If there's a custom setter method use it.
        if ($method = $this->build_setter_method_name($name)) {
            $this->$method($value);
            return $this;
        }

        return $this->set_attribute_raw($name, $value, false);
    }

    /**
     * Custom ID setter that prevents resetting id on the entity
     *
     * @param $value
     */
    final public function set_id_attribute($value) {
        if (!empty($this->attributes['id'])) {
            throw new coding_exception('Id on this entity has already been set and cannot be set again.');
        }

        $this->exists = true;
        $this->attributes['id'] = $value;
    }

    /**
     * Build the setter method name for an attribute aka mutators
     *
     * @param string $name
     * @return string|null
     */
    private function build_setter_method_name(string $name): ?string {
        $method_name = "set_{$name}_attribute";
        if (method_exists($this, $method_name)) {
            return $method_name;
        }
        return null;
    }

    /**
     * Build the getter method name for an attribute aka mutators
     *
     * @param string $name
     * @return string|null
     */
    private function build_getter_method_name(string $name): ?string {
        $method_name = "get_{$name}_attribute";
        if (method_exists($this, $method_name)) {
            return $method_name;
        }
        return null;
    }

    /**
     * Set attribute
     *
     * @param $name
     * @param $value
     */
    public function __set($name, $value) {
        $this->set_attribute($name, $value);
    }

    /**
     * Check whether an attribute exists
     *
     * @param $name
     * @return bool
     */
    public function __isset($name) {
        // Either the attribute is set on attributes or there's a getter method or it's a relation
        return isset($this->attributes[$name]) ||
            ($this->relation_exists($name) && $this->get_relation($name)) ||
            $this->build_getter_method_name($name);
    }

    /**
     * Unset an attribute
     *
     * @param $name
     */
    public function __unset($name) {
        unset($this->attributes[$name]);
    }

    /**
     * Mark entity as deleted
     *
     * @return $this
     */
    public function set_deleted() {
        $this->is_deleted = true;
        $this->exists = false;
        return $this;
    }

    /**
     * Add extra attribute on the fly
     *
     * @param string $name
     * @return $this
     */
    public function add_extra_attribute(string $name) {
        if (!in_array($name, $this->extra_attributes)) {
            $this->extra_attributes[] = $name;
        }

        return $this;
    }

    /**
     * Set created timestamp field to the current time if functionality is enabled
     *
     * @param int|null $time Timestamp to use, uses current timestamp if null
     * @return $this
     */
    public function set_created_timestamp(?int $time = null) {
        // Do the checks that timestamp field is set and updating timestamps is enabled
        if (!empty($this->created_timestamp) && $this->update_timestamps) {
            if (!isset($this->attributes[$this->created_timestamp])) {
                $this->set_attribute($this->created_timestamp, $time ?? time());
            }
        }

        return $this;
    }

    /**
     * Set updated timestamp field to the current time if functionality is enabled
     *
     * @param bool $is_created Flag to indicate whether it is called during entity creation
     * @return $this
     */
    public function set_updated_timestamp(bool $is_created = false) {
        // Do the checks that timestamp field is set and updating timestamps is enabled
        if (!empty($this->updated_timestamp) && $this->update_timestamps) {
            // make sure we set the updated timestamp on creation if configured to do so
            if (!$is_created || $this->set_updated_when_created) {
                // on creation use the created_timestamp to make sure they are absolutely the same
                $time = time();
                if ($is_created && !empty($this->created_timestamp)) {
                    $time = $this->get_attribute($this->created_timestamp);
                }
                $this->set_attribute($this->updated_timestamp, $time);
            }
            // Make sure at least a null value is automatically set
            if ($is_created && !$this->set_updated_when_created) {
                if (!array_key_exists($this->updated_timestamp, $this->attributes)) {
                    $this->set_attribute_raw($this->updated_timestamp, null, false);
                }
            }
        }

        return $this;
    }

    /**
     * Set that timestamps should be updated automatically
     *
     * @param bool $update Flag true or false
     * @return $this
     */
    public function update_timestamps(bool $update = true) {
        $this->update_timestamps = $update;

        return $this;
    }

    /**
     * Set that timestamps should not be updated automatically
     *
     * @return $this
     */
    public function do_not_update_timestamps() {
        return $this->update_timestamps(false);
    }

    /**
     * Get table name for a given entity
     *
     * @return string
     */
    public function get_table() {
        return $this->table;
    }

    /**
     * Returns raw attributes without going through any modifications or extra attributes.
     *
     * @return array
     */
    public function get_attributes_raw(): array {
        return $this->attributes;
    }

    /**
     * Convert the entity to object
     *
     * @return array
     */
    public function to_array() {
        $attribute_keys = array_keys($this->attributes);
        // Take extra attributes as well
        $attribute_keys = array_merge($attribute_keys, $this->extra_attributes);

        $attributes = [];
        foreach ($attribute_keys as $attribute_key) {
            $attributes[$attribute_key] = $this->get_attribute($attribute_key);
        }

        foreach ($this->relations as $key => $relation) {
            if (array_key_exists($key, $attributes)) {
                debugging('Duplicating relation name "' . $key . '", please use unique collection name');
            }

            if (is_object($relation) && method_exists($relation, 'to_array')) {
                $attributes[$key] = $relation->to_array();
            } else {
                $attributes[$key] = $relation;
            }
        }

        return $attributes;
    }

    /**
     * Convert the entity into a legacy compatible DB record object.
     *
     * @param bool $raw If true, just the DB fields are returned without any modifications.
     * @return stdClass
     */
    public function to_record(bool $raw = false): stdClass {
        if ($raw) {
            return (object) $this->get_attributes_raw();
        }
        return (object) $this->to_array();
    }

    /**
     * Define has many relationship
     * Technically it's just a convenience method.
     *
     * @param string $entity Entity related class
     * @param string $foreign_key related entity key to use
     * @param string $key This model key to use
     * @return has_many
     */
    protected function has_many(string $entity, $foreign_key, $key = 'id'): has_many {
        return new has_many($this, $entity, $foreign_key, $key);
    }

    /**
     * Define has many through relationship
     * Technically it's just a convenience method.
     *
     * @param string $intermediate Intermediate model
     * @param string $related Entity related class
     * @param string $key The key in the given entity used as foreign_key in the intermediate table, usually 'id'
     * @param string $intermediate_foreign_key The foreign_key in the intermediate table matching the key in the entity table
     * @param string $intermediate_related_foreign_key The foreign_key in the intermed. table matching the key in the related table
     * @param string $related_key The key of the related table, usually 'id'
     * @return has_many_through
     */
    protected function has_many_through(
        string $intermediate,
        string $related,
        string $key,
        string $intermediate_foreign_key,
        string $intermediate_related_foreign_key,
        string $related_key
    ): has_many_through {
        return new has_many_through(
            $this,
            $intermediate,
            $related,
            $key,
            $intermediate_foreign_key,
            $intermediate_related_foreign_key,
            $related_key
        );
    }

    /**
     * Define has one through relationship
     * Technically it's just a convenience method.
     *
     * @param string $intermediate Intermediate model
     * @param string $related Entity related class
     * @param string $key The key in the given entity used as foreign_key in the intermediate table, usually 'id'
     * @param string $intermediate_foreign_key The foreign_key in the intermediate table matching the key in the entity table
     * @param string $intermediate_related_foreign_key The foreign_key in the intermed. table matching the key in the related table
     * @param string $related_key The key of the related table, usually 'id'
     * @return has_one_through
     */
    protected function has_one_through(
        string $intermediate,
        string $related,
        string $key,
        string $intermediate_foreign_key,
        string $intermediate_related_foreign_key,
        string $related_key
    ): has_one_through {
        return new has_one_through(
            $this,
            $intermediate,
            $related,
            $key,
            $intermediate_foreign_key,
            $intermediate_related_foreign_key,
            $related_key
        );
    }

    /**
     * Define has one relationship
     * Technically it's just a convenience method.
     *
     * @param string $entity Entity related class
     * @param string $foreign_key related entity key to use
     * @param string $key This model key to use
     * @return has_one
     */
    protected function has_one(string $entity, $foreign_key, $key = 'id'): has_one {
        return new has_one($this, $entity, $foreign_key, $key);
    }

    /**
     * Define has one relationship
     * Technically it's just a convenience method.
     *
     * @param string $entity Entity related class
     * @param string $key This model key to use
     * @param string $foreign_key related entity key to use
     * @return belongs_to
     */
    protected function belongs_to(string $entity, $key, $foreign_key = 'id'): belongs_to {
        return new belongs_to($this, $entity, $foreign_key, $key);
    }

    /**
     * Check whether a relation is defined on the model
     *
     * @param string $name
     * @return bool
     */
    public function relation_exists(string $name) {
        return method_exists($this, $name);
    }

    /**
     * Check whether a relation is loaded on the model
     *
     * @param string $name
     * @return bool
     */
    public function relation_loaded(string $name) {
        return array_key_exists($name, $this->relations);
    }

    /**
     * Get a relationship and load it if it hasn't been...
     *
     * @param string $name
     * @return mixed|null
     */
    public function get_relation(string $name) {
        if (!$this->relation_exists($name)) {
            debugging("Relation '$name' does not exist");
            return null;
        }

        // If the relation hasn't been eager loaded, let's lazy load it...
        if (!$this->relation_loaded($name)) {
            $this->load_relation($name);
        }

        return $this->relation_loaded($name) ? $this->relations[$name] : null;
    }

    /**
     * Load a relationship
     *
     * @param string $name
     * @return $this
     */
    public function load_relation(string $name) {
        if (!$this->relation_exists($name)) {
            debugging("Relation '$name' does not exist", DEBUG_DEVELOPER);
            return $this;
        }
        if (!$this->exists()) {
            debugging("Entity does not exist.", DEBUG_DEVELOPER);
            // The entity might have been just deleted, in this case remove already loaded relations
            unset($this->relations[$name]);
            return $this;
        }

        $this->relations[$name] = $this->{$name}()->load_for_entity();

        return $this;
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize() {
        return $this->to_array();
    }

    /**
     * Return the list of always eager-loaded relationships.
     *
     * @return string[]
     */
    public function get_permanent_relations() {
        return $this->with;
    }
}