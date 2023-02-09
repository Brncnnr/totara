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

namespace core\orm\entity\relations;

use coding_exception;
use core\orm\collection;
use core\orm\entity\entity;
use core\orm\entity\repository;
use core\orm\lazy_collection;
use core\orm\paginator;
use core\orm\query\builder;
use core\orm\query\builder_base;
use core\orm\query\condition;
use core\orm\query\join;
use core\orm\query\order;
use core\orm\query\queryable;

/**
 * Class relation
 * This class outlines a scaffolding for defining a relationship between entities
 *
 * This is an automatically generated docblock
 * Please do not edit it directly
 * See {@see core_orm_builder_proxied_docblock_testcase::test_relation_has_phpdoc()}
 *
 * @method $this select($what)
 * @method $this add_select($what)
 * @method $this reset_select()
 * @method $this select_raw(string $what, array $params = [])
 * @method $this add_select_raw(string $what, array $params = [])
 * @method $this where($attribute, $condition = null, $value = null, bool $or = false)
 * @method $this where_field($attribute, $condition = null, $raw_value = null, bool $or = false)
 * @method $this where_null($attribute, bool $or = false)
 * @method $this or_where_null($attribute)
 * @method $this where_not_null($attribute, bool $or = false)
 * @method $this or_where_not_null($attribute)
 * @method $this where_in($attribute, array $values, bool $or = false)
 * @method $this or_where_in($attribute, array $values)
 * @method $this where_not_in($attribute, array $values, bool $or = false)
 * @method $this or_where_not_in($attribute, array $values)
 * @method $this where_like($attribute, string $value = '', bool $or = false)
 * @method $this where_like_raw($attribute, string $value, bool $or = false)
 * @method $this where_like_starts_with($attribute, string $value, bool $or = false)
 * @method $this where_like_ends_with($attribute, string $value, bool $or = false)
 * @method $this where_exists($builder, bool $or = false)
 * @method $this where_not_exists($builder, bool $or = false)
 * @method $this or_where_field($attribute, $condition = null, $raw_value = null)
 * @method $this or_where($attribute, $condition = null, $value = null)
 * @method $this or_where_like($attribute, string $value = '')
 * @method $this or_where_like_raw($attribute, string $value)
 * @method $this or_where_like_starts_with($attribute, string $value)
 * @method $this or_where_like_ends_with($attribute, string $value)
 * @method $this or_where_exists(\core\orm\query\builder $builder)
 * @method $this or_where_not_exists(\core\orm\query\builder $builder)
 * @method $this where_raw(string $sql, array $params = [], bool $or = false)
 * @method $this or_where_raw(string $sql, array $params = [])
 * @method $this nested_where(\Closure $closure, bool $or = false)
 * @method $this remove_where($attribute)
 * @method $this join($table, $source = null, $condition = null, $target = null, string $type = 'inner')
 * @method $this left_join($table, $source = null, $condition = null, $target = null)
 * @method $this right_join($table, $source = null, $condition = null, $target = null)
 * @method $this full_join($table, $source = null, $condition = null, $target = null)
 * @method $this cross_join($table)
 * @method $this reset_union()
 * @method $this order_by($column, string $direction = 'ASC')
 * @method $this order_by_raw($what)
 * @method $this reset_order_by()
 * @method bool has_order_by()
 * @method array|order[] get_orders()
 * @method $this group_by($fields)
 * @method $this group_by_raw(string $field_sql)
 * @method $this reset_group_by()
 * @method $this having($attribute, $condition = null, $value = null, bool $or = false)
 * @method $this or_having($attribute, $condition = null, $value = null)
 * @method $this having_raw(string $sql, array $params = [], bool $or = false)
 * @method $this or_having_raw(string $sql, array $params = [])
 * @method $this limit(?int $limit)
 * @method $this offset(?int $offset)
 * @method $this when(bool $condition, callable $true, ?callable $false = null)
 * @method $this unless(bool $condition, callable $true, ?callable $false = null)
 * @method \core\orm\entity\entity|null find(int $id)
 * @method \core\orm\entity\entity find_or_fail(int $id)
 * @method \core\orm\entity\entity|null first(bool $strict = false)
 * @method \core\orm\entity\entity first_or_fail()
 * @method \core\orm\entity\entity|null one(bool $strict = false)
 * @method paginator paginate(int $page = 1, int $per_page = 0)
 * @method paginator load_more(int $page, int $per_page = 0)
 * @method collection get(bool $unkeyed = false)
 * @method lazy_collection get_lazy()
 * @method int count()
 * @method $this update($attributes)
 * @method $this update_record($record)
 * @method $this delete()
 * @method bool exists()
 * @method bool does_not_exist()
 * @method array get_last_executed_queries()
 * @method array get_last_executed_query()
 * @method $this set_parent(\core\orm\query\builder $builder)
 * @method builder|builder_base get_parent(bool $topmost = false)
 * @method $this set_aggregation(bool $aggregation)
 * @method string get_table()
 * @method $this as(string $alias)
 * @method string get_alias()
 * @method bool is_alias_used()
 * @method string get_alias_sql()
 * @method bool has_conditions()
 * @method array|condition[]|queryable[] get_conditions()
 * @method join|null get_join($table, ?string $alias = null)
 * @method bool has_join($table, ?string $alias = null)
 */
abstract class relation {

    /**
     * A flag indicating whether this relation is able to save related models.
     *
     * @var bool
     */
    protected $can_save = false;

    /**
     * Related entity repository to fetch data
     *
     * @var repository
     */
    protected $repo = null;

    /**
     * Local key
     *
     * @var string
     */
    protected $key = '';

    /**
     * Foreign key of the relation
     *
     * @var string
     */
    protected $foreign_key = null;

    /**
     * Related model class name
     *
     * @var string
     */
    protected $related = null;

    /**
     * INTERNAL flag to apply constraints needed when lazy-loading a model
     *
     * @var bool
     */
    protected static $apply_entity_constraints = true;

    /**
     * Link to a parent entity
     * Storing a link doesn't play a a significant role when using eager-loading.
     *
     * @var entity|null
     */
    protected $entity = null;

    /**
     * relation constructor.
     *
     * @param entity $entity Parent entity link
     * @param string $related Related model class name
     * @param string $foreign_key
     * @param string $key
     */
    public function __construct(entity $entity, string $related, string $foreign_key, string $key = 'id') {

        if (!is_subclass_of($related, entity::class, true)) {
            throw new coding_exception("'$related' must be a valid entity subclass.");
        }

        $this->foreign_key = $foreign_key;
        $this->key = $key;
        $this->related = $related;
        $this->set_entity($entity);

        // Related is entity::class
        $this->repo = call_user_func($related . '::repository');

        $this->apply_constraints_for_entity();
    }

    /**
     * Set "parent" entity
     *
     * @param entity $entity
     * @return $this
     */
    protected function set_entity(entity $entity) {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Get entity repository for the related entity
     *
     * @return repository
     */
    public function get_repo() {
        return $this->repo;
    }

    /**
     * Get key
     *
     * @return string
     */
    public function get_key() {
        return $this->key;
    }

    /**
     * Get foreign key
     *
     * @return string
     */
    public function get_foreign_key() {
        return $this->foreign_key;
    }

    /**
     * Get related model class name
     *
     * @return string
     */
    public function get_related() {
        return $this->related;
    }

    /**
     * Save new related entities
     *
     * @param entity[]|entity $children
     * @return $this
     */
    public function save($children) {
        if (!$this->can_save) {
            throw new coding_exception('This relation does not allow saving models...');
        }

        if (!($this->entity->exists() ?? false)) {
            throw new coding_exception('Your parent entity must be defined and exist...');
        }

        if (!$this->entity->has_attribute($this->get_key()) ||
            is_null($this->entity->get_attribute($this->get_key()))
        ) {
            throw new coding_exception("Entity must have '{$this->get_key()}' attribute set and be other than null");
        }

        if (!is_array($children)) {
            $children = [$children];
        }

        $collection = new collection($children);

        $collection->map(function (entity $item) {
            if (!$item instanceof $this->related) {
                throw new coding_exception("Related model must be an instance of '{$this->related}'");
            }

            $item->set_attribute($this->get_foreign_key(), $this->entity->get_attribute($this->get_key()));
            $item->save();
        });

        return $this;
    }

    /**
     * Allow access to related repository by chaining methods on the relationship
     *
     * @param $name
     * @param $arguments
     * @return $this
     */
    public function __call($name, $arguments) {
        // We attempt to call a method that doesn't exist directly on the repository,
        // If a method doesn't exist on the repository you will get an exception anyway...
        $result = $this->get_repo()->{$name}(...$arguments);

        // Let's see whether we need to return native results to the user...
        // Same logic applied for methods forwarded from repository to builder.
        if ($result !== $this->get_repo()) {
            return $result;
        }

        return $this;
    }

    /**
     * Conditionally apply constraints when results are lazy-loaded or queried on the go.
     */
    protected function apply_constraints_for_entity() {
        if (self::$apply_entity_constraints) {
            $this->constraints_for_entity();
        }
    }

    /**
     * INTERNAL
     *
     * See description below.
     *
     * Enable lazy loading constraints when instantiating the relationship.
     *
     * @param bool $yes
     */
    public static function apply_entity_constraints($yes = true) {
        self::$apply_entity_constraints = $yes;
    }

    /**
     * INTERNAL
     *
     * Disable lazy loading constraints when instantiating the relationship.
     *
     * Ok the idea here is that it is used during eager-loading relations for a collections of objects
     * since a different logic is applied there, this method is called from repository before the relations are loaded
     * as instead of applying individual entity related key condition we aggregate all possible key values from the whole
     * collection and then filtering it down for individual items. After relation is loaded the flag is returned to its
     * default state using the function above...
     */
    public static function do_not_apply_entity_constraints() {
        self::$apply_entity_constraints = false;
    }

    /**
     * INTERNAL
     *
     * Return whether lazy loading constraints are enabled
     */
    public static function should_apply_entity_constraints(): bool {
        return self::$apply_entity_constraints;
    }

    /**
     * A function to load related models for a collection which each individual relation should implement
     *
     * @param string $name Relation name to append to the model
     * @param collection $collection Collection of models to append the relation to
     * @return $this
     */
    abstract public function load_for_collection(string $name, collection $collection);

    /**
     * By default this relationship loads a collection, overrideable for example when you need only one result.
     *
     * @return collection|null
     */
    public function load_for_entity() {
        if ($this->entity->{$this->get_key()} === null) {
            return null;
        }

        return $this->get_repo()->get();
    }

    /**
     * A function to apply constraints when loading relationships directly for a single entity
     *
     * @return $this
     */
    abstract public function constraints_for_entity();

    /**
     * Get uniqued keys from collection, making sure no null values are included
     *
     * @param collection $collection
     * @return array
     */
    protected function get_keys_from_collection(collection $collection) {
        $keys = array_unique($collection->pluck($this->get_key()));
        // There could be null values in there, filter them out
        $keys = array_filter($keys, function ($value) {
            return $value !== null;
        });

        return $keys;
    }
}
