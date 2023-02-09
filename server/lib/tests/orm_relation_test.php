<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @category test
 */

use core\orm\collection;
use core\orm\entity\repository;
use core\orm\entity\relations\relation;
use core\orm\query\builder;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tests/orm_entity_relation_testcase.php');

/**
 * Class core_orm_relation_test
 *
 * @package core
 * @group orm
 */
class core_orm_relation_test extends orm_entity_relation_testcase {

    public function test_it_instantiates_relationship() {
        $entity = new sample_parent_entity([]);

        $relation = new sample_relation($entity, sample_child_entity::class, 'foreign_key', 'key');

        $this->assertInstanceOf(sample_relation::class, $relation);

        // Check foreign key has been set
        $this->assertEquals('foreign_key', $relation->get_foreign_key());

        // Check key has been set
        $this->assertEquals('key', $relation->get_key());

        // Check related entity class been set
        $this->assertEquals(sample_child_entity::class, $relation->get_related());

        // And now let's try to slip something annoying as standard class as related entity and see it going up in smokes.
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("'stdClass' must be a valid entity subclass.");

        new sample_relation($entity, stdClass::class, 'foreign_key');
    }

    public function test_it_returns_you_a_repository() {
        $entity = new sample_parent_entity([]);

        $relation = new sample_relation($entity, sample_child_entity::class, 'foreign_key', 'key');

        $this->assertInstanceOf(repository::class, $relation->get_repo());
    }

    public function test_it_throws_an_exception_when_loading_relations_on_something_sketchy() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Loading relations is currently supported on an entity, paginator or a collection');

        sample_parent_entity::repository()
            ->with('passport')
            ->load_relations(new stdClass());
    }

    public function test_it_displays_debugging_notice_when_attempting_to_load_relation_that_does_not_exist() {
        $this->create_tables();

        sample_parent_entity::repository()
            ->with('a.b')
            ->get();

        $this->assertDebuggingCalled('Relationship \'a\' does not exist');
    }

    public function test_it_returns_null_when_trying_to_get_relation_that_does_not_exist() {
        $this->assertNull(sample_parent_entity::repository()->get_relation('what'));
        $this->assertNull(sample_parent_entity::repository()->get_relation('changed'));
    }

    public function test_it_plays_nicely_when_names_clash() {
        $this->create_sample_records();

        $entity = sample_child_entity::repository()
            ->order_by('id')
            ->first();

        $this->assertTrue($entity->relation_exists('type'));
        $this->assertFalse($entity->relation_loaded('type'));

        $this->assertEquals(1, $entity->type);


        // Let's check that it hasn't been lazy loaded.
        $this->assertFalse($entity->relation_loaded('type'));

        // Now let's load the relationship manually
        $entity->load_relation('type');
        $this->assertTrue($entity->relation_loaded('type'));

        $entity->to_array();
        $this->assertDebuggingCalled('Duplicating relation name "type", please use unique collection name');
    }

    public function test_isset_on_relations() {
        $this->create_sample_records();

        $entity = sample_child_entity::repository()
            ->where('name', 'Samsung Galaxy Note')
            ->one();

        $this->assertTrue(isset($entity->name));
        $this->assertTrue(isset($entity->type));
        // The relation exists and there's a record
        $this->assertTrue(isset($entity->parent));
        $this->assertTrue(isset($entity->passport));
        // Neither attribute nor relation exists
        $this->assertFalse(isset($entity->doesnotexist));

        $entity = sample_child_entity::repository()
            ->where('name', 'Samsung Galaxy 6')
            ->one();

        // The relation exists and it has been loaded before
        $entity->load_relation('parent');
        $entity->load_relation('passport');
        $this->assertTrue(isset($entity->parent));
        $this->assertTrue(isset($entity->passport));

        $orphaned_entity = sample_child_entity::repository()
            ->where('name', 'The Big Unknown')
            ->one();

        $this->assertInstanceOf(sample_child_entity::class, $orphaned_entity);
        $this->assertTrue(isset($orphaned_entity->type));
        // The parent relation exists but there's no record for it
        $this->assertFalse(isset($orphaned_entity->parent));
    }

    public function test_it_loads_a_relation() {
        $records = $this->create_sample_records()[sample_child_entity::class];

        $entity = sample_parent_entity::repository()
            ->order_by('id')
            ->first();

        $this->assertTrue($entity->relation_exists('reversed_children'));
        $this->assertFalse($entity->relation_loaded('reversed_children'));

        // It's fluent
        $this->assertSame($entity, $entity->load_relation('reversed_children'));

        // It does load a relationship
        $this->assertTrue($entity->relation_loaded('reversed_children'));
        $this->assertTrue(isset($entity->reversed_children));

        // It does load it correctly
        $this->assertEquals(
            array_column(array_reverse(array_slice($records, 0, 3)), 'id'),
            $entity->reversed_children->pluck('id')
        );

        // Bonus, it emits a debugging notice when relationship doesn't exists
        $entity->load_relation('childs');
        $this->assertDebuggingCalled("Relation 'childs' does not exist");
    }

    public function test_it_return_a_relation() {
        $records = $this->create_sample_records()[sample_child_entity::class];

        $entity = sample_parent_entity::repository()
            ->order_by('id')
            ->first();

        $this->assertTrue($entity->relation_exists('reversed_children'));
        $this->assertFalse($entity->relation_loaded('reversed_children'));

        $this->assertInstanceOf(collection::class, $entity->get_relation('reversed_children'));
        $this->assertTrue($entity->relation_loaded('reversed_children'));

        // It does load it correctly
        $this->assertEquals(
            array_column(array_reverse(array_slice($records, 0, 3)), 'id'),
            $entity->reversed_children->pluck('id')
        );
    }

    public function test_it_throws_debugging_notice_when_accessing_non_existent_relation() {
        $this->assertNull((new sample_parent_entity())->get_relation('abracadabra'));
        $this->assertDebuggingCalled('Relation \'abracadabra\' does not exist');
    }

    public function test_it_can_load_relation_for_a_single_entity() {
        $records = $this->create_sample_records()[sample_child_entity::class];

        $entity = sample_parent_entity::repository()
            ->order_by('id')
            ->first();

        // It does load it correctly
        $this->assertEquals(
            array_column(array_reverse(array_slice($records, 0, 3)), 'id'),
            $entity->reversed_children()->load_for_entity()->pluck('id')
        );
    }

    public function test_it_eagerloads_permanent_relations() {
        $records = $this->create_sample_records();

        $parents = array_combine(
            array_column($records[sample_parent_entity::class], 'id'),
            $records[sample_parent_entity::class]
        );

        $entities = sample_passport_entity::repository()
            ->order_by('id')
            ->get();

        foreach ($entities as $entity) {
            $this->assertTrue($entity->relation_loaded('parent'));
            $this->assertEquals($entity->parent_id, $entity->parent->id);
            $this->assertEquals($parents[$entity->parent_id]['name'], $entity->parent->name);
        }
    }

    public function test_it_eagerloads_nested_relations() {
        $records = $this->create_sample_records();

        $children = array_combine(
            array_column($records[sample_child_entity::class], 'id'),
            $records[sample_child_entity::class]
        );

        $queries = $this->db()->perf_get_reads();

        $parents = sample_parent_entity::repository()
            ->with('children.a_type')
            ->get();

        $parents->map(function (sample_parent_entity $parent) use ($children) {
            $this->assertTrue($parent->relation_loaded('children'));

            $parent->children->map(function (sample_child_entity $child) use ($parent, $children) {

                $this->assertEquals($parent->id, $child->parent_id);

                $this->assertEquals($children[$child->id]['name'], $child->name);
                $this->assertEquals($children[$child->id]['type'], $child->type);
                $this->assertEquals($children[$child->id]['description'], $child->description);
                $this->assertEquals($children[$child->id]['created_at'], $child->created_at);
                $this->assertEquals($children[$child->id]['updated_at'], $child->updated_at);

                $this->assertTrue($child->relation_loaded('a_type'));

                $this->assertEquals($children[$child->type]['name'], $child->a_type->name);
                $this->assertEquals($children[$child->type]['type'], $child->a_type->type);
                $this->assertEquals($children[$child->type]['description'], $child->a_type->description);
                $this->assertEquals($children[$child->type]['created_at'], $child->a_type->created_at);
                $this->assertEquals($children[$child->type]['updated_at'], $child->a_type->updated_at);
            });
        });

        // It should take 3 queries to load the data:
        // Get all parents
        // Get all children
        // Get all types
        $this->assertEquals(3, $this->db()->perf_get_reads() - $queries);
    }

    public function test_it_eagerloads_nested_relations_with_added_conditions() {
        $records = $this->create_sample_records();

        $children = array_combine(
            array_column($records[sample_child_entity::class], 'id'),
            $records[sample_child_entity::class]
        );

        $queries = $this->db()->perf_get_reads();

        $parents = sample_parent_entity::repository()
            ->with([
                'children:name,type.a_type:created_at' => function (repository $repository) {
                    // This relates to the LAST nested relation.
                    $repository->where('type', '>', 3);
                }
            ])
            ->get();

        $parents->map(function (sample_parent_entity $parent) use ($children) {
            $this->assertTrue($parent->relation_loaded('children'));

            $parent->children->map(
                function (sample_child_entity $child) use ($parent, $children) {

                    $this->assertEquals($parent->id, $child->parent_id);

                    $this->assertEquals($children[$child->id]['name'], $child->name);
                    $this->assertEquals($children[$child->id]['type'], $child->type);
                    $this->assertFalse(isset($child->description));
                    $this->assertFalse(isset($child->created_at));
                    $this->assertFalse(isset($child->updated_at));

                    $this->assertTrue($child->relation_loaded('a_type'));

                    if ($children[$child->type]['type'] > 3) {
                        $this->assertFalse(isset($child->a_type->name));
                        $this->assertFalse(isset($child->a_type->description));
                        $this->assertFalse(isset($child->a_type->updated_at));

                        $this->assertEquals($children[$child->type]['created_at'], $child->a_type->created_at);
                    } else {
                        $this->assertNull($child->a_type);
                    }
                }
            );
        });

        // It should take 3 queries to load the data:
        // Get all parents
        // Get all children
        // Get all types
        $this->assertEquals(3, $this->db()->perf_get_reads() - $queries);
    }

    public function test_it_eagerloads_nested_relations_with_some_columns() {
        $records = $this->create_sample_records();

        $children = array_combine(
            array_column($records[sample_child_entity::class], 'id'),
            $records[sample_child_entity::class]
        );

        $queries = $this->db()->perf_get_reads();

        $parents = sample_parent_entity::repository()
            ->with('children:name,type.a_type:created_at')
            ->get();

        $parents->map(function (sample_parent_entity $parent) use ($children) {
            $this->assertTrue($parent->relation_loaded('children'));

            $parent->children->map(function (sample_child_entity $child) use ($parent, $children) {

                $this->assertEquals($parent->id, $child->parent_id);

                $this->assertEquals($children[$child->id]['name'], $child->name);
                $this->assertEquals($children[$child->id]['type'], $child->type);
                $this->assertFalse(isset($child->description));
                $this->assertFalse(isset($child->created_at));
                $this->assertFalse(isset($child->updated_at));

                $this->assertTrue($child->relation_loaded('a_type'));

                $this->assertFalse(isset($child->a_type->name));
                $this->assertFalse(isset($child->a_type->description));
                $this->assertFalse(isset($child->a_type->updated_at));

                $this->assertEquals($children[$child->type]['created_at'], $child->a_type->created_at);
            });
        });

        // It should take 3 queries to load the data:
        // Get all parents
        // Get all children
        // Get all types
        $this->assertEquals(3, $this->db()->perf_get_reads() - $queries);
    }

    public function test_it_eagerloads_relations_for_collection() {
        $records = $this->create_sample_records();

        $parents = sample_parent_entity::repository()
            ->order_by('id')
            ->get();

        $this->assertGreaterThan(
            0,
            sample_child_entity::repository()
                ->when('true', function (repository $repository) {
                    $builder = builder::table(sample_parent_entity::TABLE)->select('id');

                    $repository->where('parent_id', 'in', $builder);
                })
                ->count()
        );

        $queries = $this->db()->perf_get_reads();

        $parents->load(['children']);

        // Only one query has been executed.
        $this->assertEquals($queries + 1, $this->db()->perf_get_reads());

        $parents->map(function (sample_parent_entity $entity) use (&$queries) {
            $this->assertTrue($entity->relation_loaded('children'));

            $queries += 1;
            $related = sample_child_entity::repository()
                ->where('parent_id', $entity->id)
                ->order_by('id')
                ->get()
                ->pluck('name');

            $this->assertEquals($related, $entity->children->pluck('name'));
        });

        // Still, only one query has been executed.
        $this->assertEquals($queries + 1, $this->db()->perf_get_reads());
    }

    public function test_it_eagerloads_relations_for_a_paginator() {
        $records = $this->create_sample_records();

        $parents = array_combine(
            array_column($records[sample_parent_entity::class], 'id'),
            $records[sample_parent_entity::class]
        );

        $entities = sample_passport_entity::repository()
            ->order_by('id')
            ->paginate();

        foreach ($entities->get_items() as $entity) {
            $this->assertTrue($entity->relation_loaded('parent'));
            $this->assertEquals($entity->parent_id, $entity->parent->id);
            $this->assertEquals($parents[$entity->parent_id]['name'], $entity->parent->name);
        }
    }

    public function test_it_returns_whether_a_relation_is_loaded_on_collection() {
        $this->create_sample_records();

        $collection = sample_parent_entity::repository()
            ->with('children')
            ->order_by('id')
            ->limit(2)
            ->get();

        $this->assertTrue($collection->relation_loaded('children'));
        $this->assertFalse($collection->relation_loaded('passport'));

        $collection->append(
            sample_parent_entity::repository()
                ->order_by('id', 'desc')
                ->first()
        );

        $this->assertFalse($collection->relation_loaded('children'));
    }

    public function test_it_returns_entity_class_contained_in_a_collection() {
        $collection = new collection([
            new sample_child_entity(),
            new sample_child_entity(),
            new sample_child_entity(),
            new sample_child_entity(),
        ]);

        $this->assertEquals(sample_child_entity::class, $collection->get_entity_class());

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Expected it to be a collection of 'sample_child_entity', but it's not...");

        $collection->append(new sample_parent_entity());
        $collection->get_entity_class();
    }

}

class sample_relation extends relation {

    /**
     * A function to load related models for a collection which each individual relation should implement
     *
     * @param string $name Relation name to append to the model
     * @param collection $collection Collection of models to append the relation to
     * @return $this
     */
    public function load_for_collection(string $name, collection $collection) {
        return $this;
    }

    /**
     * A function to apply constraints when loading relationships directly for a single entity
     *
     * @return $this
     */
    public function constraints_for_entity() {
        return $this;
    }

}