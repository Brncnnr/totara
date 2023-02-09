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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core_orm
 */

namespace core\orm\entity;

use coding_exception;
use core\orm\collection;
use core\orm\entity\filter\has_filters;
use core\orm\entity\relations\has_many_through;
use core\orm\entity\relations\relation;
use core\orm\lazy_collection;
use core\orm\paginator;
use core\orm\query\builder;
use core\orm\query\builder_base;
use core\orm\query\condition;
use core\orm\query\join;
use core\orm\query\order;
use core\orm\query\queryable;
use stdClass;

/**
 * Class repository
 *
 * This is an automatically generated docblock
 * Please do not edit it directly
 * See {@see core_orm_builder_proxied_docblock_testcase::test_repository_has_phpdocs()}
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
class repository {

    use has_filters;

    /**
     * Class name of entity
     *
     * @var string
     */
    protected $entity_classname;

    /**
     * Query builder ref
     *
     * @var builder
     */
    protected $builder = null;

    /**
     * Those methods are blacklisted and will not be forwarded to the builder
     *
     * @var string[]
     */
    protected $blacklisted_builder_methods = [
        'insert',
        'from',
        'union',
        'union_all',
        'fetch',
        'fetch_counted',
        'fetch_recordset',
        'map_to',
        'new',
        'results_as_arrays',
        'results_as_objects',
        'value'
    ];

    /**
     * For the following methods we need to apply the filters before calling them
     *
     * @var string[]
     */
    protected $filterable_methods = [
        'count',
        'delete',
        'first',
        'first_or_fail',
        'one',
        'get',
        'get_lazy',
        'load_more',
        'paginate',
        'update'
    ];

    /**
     * An array of methods that will trigger relations loading before returning results from the database.
     *
     * @var string[]
     */
    protected $with_relations_methods = [
        'first',
        'first_or_fail',
        'one',
        'get',
        'get_lazy',
        'paginate',
        'load_more',
    ];

    /**
     * Stored relations
     *
     * @var relation[]
     */
    protected $relations = [];

    /**
     * @param string $entity_classname the name of the entity class
     * @param builder|null $builder optionally builder can be passed, by default it create a new instance internally
     * @throws coding_exception
     */
    public function __construct(string $entity_classname, builder $builder = null) {
        if (!is_subclass_of($entity_classname, entity::class)) {
            throw new coding_exception('Expected entity class name');
        }

        $table = $entity_classname::TABLE;

        if (!empty($builder)) {
            $this->builder = $builder;
        } else {
            $this->builder = (new builder())
                ->from($table)
                ->as($table)
                ->results_as_objects()
                ->map_to(\Closure::fromCallable([$this, 'map_to']));
        }

        $this->entity_classname = $entity_classname;

        // Let's not beat around the bush and add always eager-loaded relations
        foreach ($this->instantiate_entity()->get_permanent_relations() as $relation) {
            $this->with_relation($relation);
        }
    }

    /**
     * This does our mapping, passed to map_to() on the builder
     *
     * @param array|stdClass|null $record either an array, stdClass or null, null won't map
     * @param bool $validate_attributes defaults to false
     * @return entity
     */
    protected function map_to($record, bool $validate_attributes = false) {
        if (is_null($record)) {
            return null;
        }
        // We want to skip validation so we manually map
        $classname = $this->entity_classname;
        return new $classname($record, $validate_attributes, true);
    }

    /**
     * Gather records fetched from the DB into a collection
     *
     * @param array|array[]|stdClass[] $records
     * @param bool $validate_attributes defaults to true
     * @return collection
     */
    public function from_records(array $records, $validate_attributes = true): collection {
        $records = new collection($records);

        $records->transform(function ($record) use ($validate_attributes) {
            return $this->map_to($record, $validate_attributes);
        });

        return $records;
    }

    /**
     * Save the entity (create or update depending on whether the entity exists)
     *
     * @param entity $entity
     * @return entity
     */
    public function save_entity(entity $entity): entity {
        if ($entity->deleted()) {
            throw new coding_exception('You can not save a entity that was deleted.');
        }

        if (!$entity->exists()) {
            return $this->create_entity($entity);
        } else {
            return $this->update_entity($entity);
        }
    }

    /**
     * Insert new model into the database
     *
     * @param entity $entity
     * @return entity
     */
    public function create_entity(entity $entity): entity {
        if ($entity->exists()) {
            throw new coding_exception('You can not create a entity that already exists.');
        }

        $entity->set_created_timestamp()
            ->set_updated_timestamp(true);

        // To be on a safe side a new query builder is used here God knows what might be in the default one.
        $entity->id = $this->builder->new()->insert($entity->get_attributes_raw());
        // make sure entity is clean
        $entity->reset_dirty();

        return $entity;
    }

    /**
     * Update model record in the database
     *
     * @param entity $entity
     * @return entity
     */
    public function update_entity(entity $entity): entity {
        if (!$entity->exists() || $entity->deleted()) {
            throw new coding_exception('You can not update a entity that does not exist yet or was deleted.');
        }

        if ($entity->exists() && !$entity->id) {
            throw new coding_exception('To update an existing entity you must have an ID attribute present.');
        }

        $entity->set_updated_timestamp();

        if ($entity->changed()) {
            $this->builder->new()
                ->where('id', $entity->id)
                ->update($entity->get_dirty());
        }

        return $entity->reset_dirty();
    }

    /**
     * delete the record
     *
     * @param entity $entity
     * @return entity
     */
    public function delete_entity(entity $entity) {
        if (!$entity->id || !$entity->exists() || $entity->deleted()) {
            throw new coding_exception('Cannot delete entity without id, entity that does not exist or already deleted.');
        }
        // To be on a safe side a new query builder is used.
        $this->builder->new()
            ->where('id', $entity->id)
            ->delete();

        $entity->set_deleted();

        return $entity;
    }

    /**
     * Load relations defined on this repository
     *
     * @param paginator|collection|entity $result
     * @return $this
     */
    public function load_relations($result) {
        // Let's check whether we have anything to load first...
        if (empty($this->relations)) {
            return $this;
        }

        $collection = null;

        switch (true) {
            case $result instanceof collection:
                $collection = $result;
                break;

            case $result instanceof entity:
                $collection = new collection([$result]);
                break;

            case $result instanceof paginator:
                $collection = $result->get_items();
                break;

            case is_null($result):
                // Empty result, nothing to load...
                return $this;

            default:
                throw new coding_exception('Loading relations is currently supported on an entity, paginator or a collection');
        }

        foreach ($this->relations as $name => $relation) {
            $relation->load_for_collection($name, $collection);
        }

        return $this;
    }

    /**
     * Eager load given entity relationship
     *
     * @param array|string $relations
     * @return $this
     */
    public function with($relations) {
        if (!is_array($relations)) {
            $relations = [$relations];
        }

        foreach ($relations as $relation => $callback) {
            if (is_numeric($relation)) {
                $this->with_relation($callback);
            } else {
                $this->with_relation($relation, $callback);
            }
        }

        return $this;
    }

    /**
     * Eager load a relationship
     *
     * @param string $relation
     * @param callable|null $extra
     */
    protected function with_relation(string $relation, callable $extra = null) {
        // We can specify which columns to load on a relationship, using the following syntax
        // relation:column_a,column_b,column_c

        // Before we normalize the relation name, we'll check whether there are any nested relations that
        // have to be loaded.
        $relations = explode('.', $relation);

        if (empty($relations)) {
            return;
        }

        $parent = $this;
        foreach ($relations as $relation) {
            // So, let's normalize it.
            $relation = $parent->normalize_relation_name($relation);
            if ($rel = $parent->get_relation($relation[0])) {
                $parent->relations[$relation[0]] = $rel;

                if (!empty($relation[1])) {
                    // It's not perfect to check for a particular relation here, but it's a limitation of has many through having to select extra things,
                    // we technically may add a necessary thing here.
                    // The other option is to move this code to the relation class itself then we'd be able to override it properly on a particular relation class.
                    // Since it's not a big issue, we'll leave it this way for now, ignoring custom select and notifying devs with a debugging message, not an exception
                    if ($rel instanceof has_many_through) {
                        debugging('Specifying columns is not currently supported for has_many(one)_through relations');

                        $parent = $rel->get_repo();
                        continue;
                    }

                    // Having a foreign key column is required, so let's append it.
                    if (!in_array($rel->get_foreign_key(), $relation[1])) {
                        $relation[1][] = $rel->get_foreign_key();
                    }

                    $rel->get_repo()->select($relation[1]);
                }
            } else {
                debugging("Relationship '{$relation[0]}' does not exist");
                return;
            }

            $parent = $rel->get_repo();
        }
        // We can only allow conditional loading for the "last mile"
        if (!is_null($extra)) {
            $extra($parent);
        }
    }

    /**
     * Let's normalize relation name and columns
     *
     * @param string $name
     * @return array
     */
    protected function normalize_relation_name(string $name): array {
        $relation = explode(':', $name);

        $relation_name = trim($relation[0]);
        $relation_columns = preg_split('/\s*,\s*/', trim($relation[1] ?? ''), -1, PREG_SPLIT_NO_EMPTY);


        if (!empty($relation_columns)) {
            if (!in_array('id', $relation_columns)) {
                array_unshift($relation_columns, 'id');
            }
        }

        return [$relation_name, $relation_columns];
    }

    /**
     * Instantiate a relation if defined
     *
     * @param string $name Relation name
     * @return relation|null
     */
    public function get_relation(string $name): ?relation {
        $entity = $this->instantiate_entity();

        if (method_exists($entity, $name)) {
            // To eager load related entities we don't need to apply individual entity key constraint
            // which is applied by default when the relation is lazy-loaded (accessed directly via a method
            // on a model. So with this flag we are telling the relation not to apply the constraint
            // and after the relation has been initialised we are returning it to the state it was before.

            // This is designed to prevent defining a relation method twice - one that is called from repository
            // and does not apply the constraint and the other one that can be called directly and apply it.

            // These also might be nested oO, so we need to save state and return to the state it was...
            $apply_or_not = relation::should_apply_entity_constraints();

            relation::do_not_apply_entity_constraints();

            $relation = $entity->{$name}();

            relation::apply_entity_constraints($apply_or_not);

            if ($relation instanceof relation) {
                return $relation;
            }
        }

        return null;
    }

    /**
     * Return an instance of the builder
     * This is required for some functionality that expects a builder object, it's not possible
     * to just substitute builder object with repository object because repository is not a
     * descendent of builder, it just relies on it internally, hence exposing underlying builder
     *
     * @return builder
     */
    public function get_builder(): builder {
        return $this->builder;
    }

    /**
     * Create an instance of the related entity from the classname that we have...
     *
     * @return entity
     */
    protected function instantiate_entity(): entity {
        $entity_class = $this->entity_classname;

        return new $entity_class();
    }

    /*****************************************************************************************************************
     *                                                                                                               *
     * FORWARDED QUERY BUILDER METHODS                                                                               *
     *                                                                                                               *
     ****************************************************************************************************************/

    /**
     * Conditionally execute a closure without braking a fluent flow
     * Note, this is a syntactic sugar for simple cases do not abuse it with monstrous logic
     * For example to have something like:
     * ->...
     * ->when($visible_only, function (repository $repo) { $repo->where('visible', true) } )
     * ->...
     *
     * @param bool $condition Condition to check
     * @param callable $true Closure to run if condition is true
     * @param callable|null $false Closure to run if condition is false
     * @return $this
     */
    public function when(bool $condition, callable $true, ?callable $false = null) {
        if ($condition) {
            $true($this);
        } else if ($false) {
            $false($this);
        }

        return $this;
    }

    /**
     * Conditionally execute a closure without braking a fluent flow
     * The opposite of when
     * Note, this is a syntactic sugar for simple cases do not abuse it with monstrous logic
     * For example to have something like:
     * ->...
     * ->unless($hidden, function (repository $repo) { $repo->where('visible', true) } )
     * ->...
     *
     * @param bool $condition Condition to check
     * @param callable $true Closure to run if condition is false
     * @param callable|null $false Closure to run if condition is true
     * @return $this
     */
    public function unless(bool $condition, callable $true, ?callable $false = null) {
        return $this->when(!$condition, $true, $false);
    }

    /**
     * We can forward a lot of methods to the query builder. Instead of defining them again as methods we
     * use the magic __call method to do this.
     *
     * Not all methods are forwarded, some methods do not make sense to call on the repository, therefore we blacklist them.
     *
     * @param string $name
     * @param array $arguments
     * @return $this|mixed
     */
    public function __call($name, $arguments) {
        if (!method_exists($this->builder, $name)) {
            throw new coding_exception('Unknown method on the builder: '.$name);
        }

        // We do not want to forward any of the blacklisted methods
        if (in_array($name, $this->blacklisted_builder_methods)) {
            throw new coding_exception('Called method \''.$name.'()\' not allowed for forwarding to the builder.');
        }

        // Apply filters if needed
        if (in_array($name, $this->filterable_methods)) {
            $this->apply_filters();
        }

        // Forward to the query builder
        $result = $this->builder->$name(...$arguments);
        if ($result === $this->builder) {
            return $this;
        }

        // OK we've got some results. Let's see whether we need to inject related models into them.
        if (in_array($name, $this->with_relations_methods)) {
            $this->load_relations($result);
        }

        return $result;
    }

}
