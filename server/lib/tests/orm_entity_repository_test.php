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
use core\orm\entity\entity;
use core\orm\entity\filter\filter;
use core\orm\entity\repository;
use core\orm\lazy_collection;
use core\orm\paginator;
use core\orm\query\builder;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tests/orm_entity_testcase.php');

/**
 * Class core_orm_repository_testcase
 *
 * @package core
 * @group orm
 */
class core_orm_repository_testcase extends orm_entity_testcase {

    public function test_it_allows_to_override_custom_repository_class() {
        $this->assertInstanceOf(orm___my_sample__repository::class, sample_entity::repository());
    }

    public function test_it_returns_you_builder() {
        $repo = sample_entity::repository();

        $this->assertInstanceOf(builder::class, $repo->get_builder());
        $this->assertEquals($repo->get_table(), $repo->get_builder()->get_table());
    }

    public function test_it_allows_creating_repositories_for_entities_only() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Expected entity class name');

        new repository(stdClass::class);
    }

    public function test_it_works_with_query_builder() {
        $this->create_sample_records();

        $entities = sample_entity::repository()
            ->select(['id', 'name'])
            ->where('name', 'Roxanne')
            ->or_where('name', 'doesnotexist')
            ->order_by('name', 'asc')
            ->when(true, function (repository $builder) {
                $builder->add_select('created_at');
            })
            ->when(false,
                function (repository $builder) {
                    // This code will not be executed
                    $builder->select('update_at');
                },
                function (repository $repository) {
                    $repository->group_by('id')
                        ->group_by('created_at');
                }
            )
            ->offset(0)
            ->limit(1)
            ->having('name', 'Roxanne')
            ->group_by('name')
            ->get()
            ->all();

        $this->assertCount(1, $entities);
        $this->assertEquals('Roxanne', $entities[0]->name);
        $this->assertEquals('1544501054', $entities[0]->created_at);

        $count = sample_entity::repository()
            ->count();
        $this->assertGreaterThan(0, $count);

        $entity = sample_entity::repository()
            ->find($entities[0]->id);
        $this->assertInstanceOf(entity::class, $entity);

        $entity = sample_entity::repository()
            ->find_or_fail($entity->id);
        $this->assertInstanceOf(entity::class, $entity);

        $entity = sample_entity::repository()
            ->order_by('id')
            ->first();
        $this->assertInstanceOf(entity::class, $entity);

        $entity = sample_entity::repository()
            ->where('name', 'Roxanne')
            ->one();
        $this->assertInstanceOf(entity::class, $entity);
    }

    public function test_it_fails_if_an_unknown_builder_method_is_called() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Unknown method on the builder: foo');

        sample_entity::repository()
            ->foo()
            ->get();
    }

    public function test_it_fails_if_a_blacklisted_builder_method_is_called() {
        $repository = sample_entity::repository();

        $reflection = new ReflectionClass(repository::class);
        $reflection_property = $reflection->getProperty('blacklisted_builder_methods');
        $reflection_property->setAccessible(true);

        $blacklisted_methods = $reflection_property->getValue($repository);

        foreach ($blacklisted_methods as $blacklisted_method) {
            try {
                $repository->$blacklisted_method();
                $this->fail('Expected method to be blacklisted');
            } catch (Exception $exception) {
                $this->assertInstanceOf(coding_exception::class, $exception);
                $this->assertMatchesRegularExpression("/Called method '{$blacklisted_method}\(\)' not allowed for forwarding to the builder./", $exception->getMessage());
            }
        }
    }

    /**
     * @dataProvider filterable_methods_data_provider
     *
     * @param string $method name of the method to check
     * @param bool $applied should the filter be applied or not
     * @param array $params params passed to the method provided
     * @throws coding_exception
     */
    public function test_it_applies_filters_on_certain_methods(string $method, bool $applied, array $params = []) {

        $records = $this->create_sample_records();

        if (strpos($method, 'find') === 0) {
            $params = [$records[0]['id']];
        }

        $builder = builder::table(sample_entity::TABLE);

        // Create a observer to check if filter got applied or not
        $filter_observer = new class extends filter {
            protected $applied = false;

            public function apply() {
                $this->applied = true;
            }

            public function was_applied(): bool {
                return $this->applied;
            }
        };

        $repository = (new repository(sample_entity::class, $builder))
            ->set_filter($filter_observer, true);

        $this->assertFalse($filter_observer->was_applied());

        if ($method == 'first' || $method == 'first_or_fail') {
            $repository->order_by('id');
        }
        if ($method == 'one') {
            $repository->where('id', $records[0]['id']);
        }

        $repository->$method(...$params);

        if ($applied) {
            $this->assertTrue($filter_observer->was_applied());
        } else {
            $this->assertFalse($filter_observer->was_applied());
        }
    }

    /**
     * Returns method names, if filters should be applied and optional params for the method
     * @return array
     */
    public function filterable_methods_data_provider() {
        return [
            // Some negatives
            ['find', false, [1]],
            ['find_or_fail', false, [1]],
            ['where', false, ['name', 'foo']],
            ['order_by', false, ['col', 'asc']],
            ['limit', false, [1]],
            ['offset', false, [0]],
            // The following methods should be filtered
            ['count', true],
            ['delete', true],
            ['first', true],
            ['first_or_fail', true],
            ['one', true],
            ['get', true],
            ['get_lazy', true],
            ['load_more', true, [1]],
            ['paginate', true],
            [
                'update',
                true,
                [
                    [
                        'created_at' => 0
                    ]
                ]
            ],
        ];
    }

    public function test_it_sorts_entities_properly() {
        $records = $this->create_sample_records();

        // Sort by ID in descending order
        $ids = array_column($records, 'id');
        sort($ids);

        $ids = array_reverse($ids);

        $entities = sample_entity::repository()
            ->order_by('id', 'desc')
            ->get()->pluck('id');

        $this->assertEquals($ids, $entities, 'It should sort by a column in a descending order properly!');

        // Sort by name in ascending order
        $names = array_column($records, 'name');
        sort($names);

        $entities = sample_entity::repository()
            ->order_by('name')
            ->get()->pluck('name');

        $this->assertEquals($names, $entities, 'It should sort by a column in a ascending order properly!');
    }

    public function test_it_gets_collection_of_entities() {
        $records = $this->create_sample_records();

        $entities = sample_entity::repository()->get();

        $this->assertInstanceOf(collection::class, $entities);
        $this->assertCount(count($records), $entities->all());

        foreach ($entities->all() as $entity) {
            $this->assertInstanceOf(sample_entity::class, $entity);
        }
    }

    public function test_it_paginates_collection_of_entities() {
        $this->create_sample_records();

        $repository = sample_entity::repository();

        // Lowering the number of items per page for testing purposes
        $pagination = $repository->paginate(1, 2);

        $this->assertInstanceOf(paginator::class, $pagination);

        $this->assertEquals(5, $pagination->to_array()['total']);
        $this->assertEquals(2, $pagination->to_array()['next']);
        $this->assertEquals(null, $pagination->to_array()['prev']);
        $this->assertEquals(2, $pagination->to_array()['per_page']);
        $this->assertEquals(3, $pagination->to_array()['pages']);
        $this->assertEquals(1, $pagination->to_array()['page']);
    }

    public function test_it_finds_entity_in_db() {
        $records = $this->create_sample_records();

        $record = $records[3];
        $entity = sample_entity::repository()->find($record['id']);

        $this->assertEquals($record['id'], $entity->id);
        $this->assertEquals($record['name'], $entity->name);
        $this->assertEquals($record['type'], $entity->type);
        $this->assertEquals($record['parent_id'], $entity->parent_id);
        $this->assertEquals($record['is_deleted'], $entity->is_deleted);
        $this->assertEquals($record['params'], $entity->params);
        $this->assertEquals($record['created_at'], $entity->created_at);
        $this->assertEquals($record['updated_at'], $entity->updated_at);

        // Trying to find a not existing entity should return null
        $this->assertNull(sample_entity::repository()->find(123456789));
    }

    public function test_it_counts_items_to_be_selected() {
        $records = $this->create_sample_records();

        $repository = sample_entity::repository();

        $this->assertEquals(count($records), $repository->count());

        $repository->where('is_deleted', 0);

        $this->assertEquals(4, $repository->count());
    }

    public function test_it_converts_records_into_entity_collection() {
        $records = $this->create_sample_records();

        $collection = sample_entity::repository()->from_records($records);

        $this->assertInstanceOf(collection::class, $collection);
        $this->assertEquals(count($records), count($collection));

        foreach ($collection as $entity) {
            $this->assertInstanceOf(entity::class, $entity);
        }
    }

    public function test_it_converts_records_into_entity_collection_with_invalid_attributes() {
        $records = $this->create_sample_records();

        foreach ($records as $key => $record) {
            $records[$key]['foo'] = 'bar';
        }

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Invalid attribute 'foo' passed to the entity");

        sample_entity::repository()->from_records($records);
    }

    public function test_it_converts_records_into_entity_collection_without_validation() {
        $records = $this->create_sample_records();

        foreach ($records as $key => $record) {
            $records[$key]['foo'] = 'bar';
        }

        $collection = sample_entity::repository()->from_records($records, false);

        $this->assertInstanceOf(collection::class, $collection);
        $this->assertEquals(count($records), count($collection));

        foreach ($collection as $entity) {
            $this->assertEquals('bar', $entity->foo);
        }
    }

    public function test_it_returns_first() {
        global $DB;

        $this->create_sample_records();

        $entity = sample_entity::repository()->order_by('id')->first();
        $this->assertInstanceOf(entity::class, $entity);

        $records = $DB->get_records($this->table_name, [], '', '*', 0, 1);
        $record = array_pop($records);
        $this->assertEquals($record->id, $entity->id);

        $entity = sample_entity::repository()->order_by('name')->first();
        $this->assertInstanceOf(entity::class, $entity);
        $this->assertEquals('Basil', $entity->name);
    }

    public function test_it_acts_as_a_query_builder() {
        $this->create_sample_records();

        $entity = sample_entity::repository()
            ->where('name', 'Basil')
            ->order_by('name')
            ->first();

        $this->assertEquals('Basil', $entity->name);
        $this->assertInstanceOf(entity::class, $entity);

        $entities = sample_entity::repository()
            ->where('type', [1, 2])
            ->order_by('name')
            ->get();

        $this->assertCount(3, $entities);
        $this->assertEquals(['Basil', 'Jane', 'Roxanne'], $entities->pluck('name'));

        $count = sample_entity::repository()
            ->where('type', [1, 2])
            ->count();

        $this->assertEquals($count, $count);
    }

    public function test_it_sets_alias_when_repository_is_instantiated() {
        $repo = sample_entity::repository();

        $this->assertEquals(sample_entity::TABLE, $repo->get_alias());
    }

    public function test_it_loads_a_lazy_collection() {
        $this->create_sample_records();

        $entities = sample_entity::repository()->get_lazy();
        $this->assertInstanceOf(lazy_collection::class, $entities);

        foreach ($entities as $entity) {
            $this->assertInstanceOf(entity::class, $entity);
        }
    }

}
