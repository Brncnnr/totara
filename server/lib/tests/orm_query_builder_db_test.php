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
 * @package core
 * @group orm
 */

use core\dml\sql;
use core\orm\query\builder;
use core\orm\query\exceptions\multiple_records_found_exception;
use core\orm\query\exceptions\record_not_found_exception;
use core\orm\query\field;
use core\orm\query\subquery;

defined('MOODLE_INTERNAL') || die();

/**
 * Class core_orm_builder_db_testcase
 *
 * @package core
 * @group orm
 */
class core_orm_query_builder_db_testcase extends advanced_testcase {

    /**
     * Test goods table
     *
     * @var string
     */
    protected $table_goods = 'test__qb_goods';

    /**
     * Test categories table
     *
     * @var string
     */
    protected $table_cats = 'test_qb_cats';

    /**
     * Test sales table
     *
     * @var string
     */
    protected $table_sales = 'test_qb_sales';

    /**
     * Created items
     *
     * @var array
     */
    public $data = null;

    protected function setUp(): void {
        $this->create_tables()
            ->create_data();

        parent::setUp();
    }

    protected function tearDown(): void {
        parent::tearDown();

        $this->drop_test_tables();
        $this->data = null;
    }

    /**
     * Database link
     *
     * @return moodle_database
     */
    protected function db() {
        return $GLOBALS['DB'];
    }

    /**
     * Database manager link
     *
     * @return database_manager
     */
    protected function db_man() {
        return $this->db()->get_manager();
    }

    public function test_it_selects_records() {
        // Test where field
        $record = $this->new_builder($this->table_goods)
            ->where_field('name', 'description')
            ->results_as_arrays()
            ->order_by('id')
            ->first();

        $this->assertEquals('Gaming desktop', $record['name']);

        // Test where
        $records = $this->new_builder($this->table_goods)
            ->join($this->table_cats, 'cat_id', 'id')
            ->where('price', '>', 2100)
            ->where("{$this->table_cats}.name", '!=', 'Desktops')
            ->order_by('price', 'asc')
            ->get();

        $this->assertCount(2, $records);
        $this->assertEquals(['Apple iPhone X', 'Gaming desktop'], $records->pluck('name'));

        // Test where IN + select fields
        $records = $this->new_builder($this->table_goods)
            ->select(['*', "{$this->table_cats}.name as cat_name"])
            ->join($this->table_cats, 'cat_id', 'id')
            ->where("{$this->table_cats}.name", ['Desktops', 'Ex-lease'])
            ->or_where('discount', 11)
            ->order_by("{$this->table_cats}.name", 'desc')
            ->get();

        $this->assertCount(3, $records);
        $this->assertEquals(['Laptops', 'Ex-lease', 'Desktops'], $records->pluck('cat_name'));

        // Test where LIKE + conditional join
        $records = $this->new_builder($this->table_goods)
            ->select("{$this->table_sales}.id as sale_id")
            ->add_select(['*', "{$this->table_cats}.name as cat_name"])
            ->add_select("{$this->table_sales}.buyer_name")
            ->add_select("{$this->table_sales}.margin")
            ->join($this->table_cats, 'cat_id', 'id')
            ->right_join(
                $this->table_sales,
                function (builder $builder) {
                    $builder->where_field("{{$this->table_goods}}.id", 'item_id')
                        ->where('margin', '>', 300);
                }
            )
            ->where('discount', null)
            ->where("name", 'ilike_starts_with', 'apple iphone')
            ->order_by("{$this->table_sales}.buyer_name", 'asc')
            ->get();

        $this->assertCount(3, $records);
        $this->assertObjectHasAttribute('sale_id', (object) $records->first());
        $this->assertObjectHasAttribute('cat_name', (object) $records->first());
        $this->assertObjectHasAttribute('buyer_name', (object) $records->first());
        $this->assertObjectHasAttribute('margin', (object) $records->first());
        $this->assertObjectHasAttribute('id', (object) $records->first());
        $this->assertObjectHasAttribute('name', (object) $records->first());
        $this->assertObjectHasAttribute('description', (object) $records->first());
        $this->assertObjectHasAttribute('price', (object) $records->first());
        $this->assertObjectHasAttribute('discount', (object) $records->first());
        $this->assertObjectHasAttribute('cat_id', (object) $records->first());
        $this->assertObjectHasAttribute('visible', (object) $records->first());
        $this->assertObjectHasAttribute('created_at', (object) $records->first());
        $this->assertObjectHasAttribute('updated_at', (object) $records->first());
        $this->assertEquals(['Donald Trump', 'Jane Doe', 'John Doe'], $records->pluck('buyer_name'));

        // Test group by and having
        $records = $this->new_builder($this->table_sales)
            ->select("{$this->table_goods}.name as item_name")
            ->select("{$this->table_goods}.price")
            ->select("sum(margin) as margin")
            ->join($this->table_goods, 'item_id', 'id')
            ->group_by("{$this->table_goods}.name")
            ->group_by(["{$this->table_goods}.price"])
            ->having('sum(margin)', '>=', 150)
            ->order_by('sum(margin)', 'desc')
            ->get();

        $this->assertCount(3, $records);
        $this->assertEquals([2500, 300, 150], $records->pluck('margin'));

        // Cross join
        $count = $this->new_builder($this->table_cats)
            ->as($this->table_cats)
            ->cross_join($this->table_goods)
            ->count();

        // We can not really assert the content here due to not very optimal restriction of the moodle db
        // which keys the results by the first column enforcing that some field in the query should be unique
        // However we can try count, during cross join it selects count(table_1) * count(table_2) records
        $this->assertEquals(108, $count);

        $united = $this->new_builder($this->table_goods)
            ->as('goods')
            ->select_raw('DISTINCT goods.name as name')
            ->add_select(['description', 'created_at', 'updated_at'])
            ->join($this->table_cats, 'cat_id', 'id')
            ->where("{$this->table_cats}.name", 'Apple');

        $records = $this->new_builder($this->table_cats)
            ->select(['description', 'name', 'created_at', 'updated_at'])
            ->union($united)
            ->order_by_raw('description')
            ->get();

        $this->assertCount(16, $records);
        $this->assertEquals([
            'A lot of mobile phones',
            'Apple iPhone 6',
            'Apple iPhone 7',
            'Apple iPhone 8',
            'Apple iPhone X',
            'Desktop computers',
            'Gaming consoles',
            'Iphones',
            'Portable computers',
            'Ps',
            'Samsung, LG, etc',
            'Used computers',
            'Various computers',
            'Wii',
            'Windows phones',
            'Xbox',
        ], $records->pluck('description'));

        // Union automatically does distinct records by default, union all does not, however we are not testing
        // union all due to the above mentioned restriction on the uniqueness of the returned dataset. :shrug:

        // Let's try real life raw sql shizzle
        $records = $this->new_builder($this->table_cats)
            ->select(new sql('id, description'))
            ->select(new field(new sql('name')))
            // Even though the following should be valid, it causes some issues on Postgres 9.6, not present in 10+
            // Judging by what comes up in search it might be a bug in Postgres
            // I don't want to remove it, so commented out until better times
            //->add_select(new sql('? as arbitrary_text, name', ['I am a question mark parameter']))
            //->add_select(new field(new sql('$1 as another_field', ['Another field here'])))
            ->where(new sql('parent_id < $1', [1]))
            ->or_where(new sql('description = :good_description', ['good_description' => 'Portable computers']))
            ->order_by_raw(new sql('id'))
            ->get();
        

        $this->assertCount(4, $records->to_array());
        $this->assertEquals(['Mobile phones', 'Computers', 'Consoles', 'Laptops'], $records->pluck('name'));
        //$this->assertEquals('I am a question mark parameter', $records->first()->arbitrary_text);
        //$this->assertEquals('Another field here', $records->first()->another_field);
    }

    public function test_it_checks_records_existence() {
        $this->assertTrue($this->new_builder($this->table_goods)->exists());

        $this->assertFalse($this->new_builder($this->table_goods)->where('id', -1)->exists());
        $this->assertTrue($this->new_builder($this->table_goods)->where('id', -1)->does_not_exist());

        $this->assertTrue($this->new_builder($this->table_goods)
            ->where('name', 'Gaming desktop')
            ->exists()
        );
    }

    public function test_it_selects_a_single_value() {

        $this->assertEquals(1650, $this->new_builder($this->table_goods)
            ->where('name', 'Apple iPhone 7')
            ->order_by('id')
            ->value('price')
        );

        $this->assertEquals(4900, $this->new_builder($this->table_goods)
            ->results_as_objects()
            ->value('MAX(price)')
        );

        $this->assertEquals(4900, $this->new_builder($this->table_goods)
            ->value('MAX(price) as X')
        );

        $this->assertEquals(1350, $this->new_builder($this->table_goods)
            ->value('min(price) AS x')
        );

        $this->assertEquals(19148, $this->new_builder($this->table_goods)
            ->results_as_objects()
            ->value('SUM(price)')
        );

        $this->assertEquals(9, $this->new_builder($this->table_goods)
            ->value('cOuNt(id) As IDDDDCccooounnttt')
        );

        $this->assertEquals('Apple iPhone 6', $this->new_builder($this->table_goods)
            ->where('description', 'New sleek iPhone')
            ->value(new field('name'))
        );

        $this->assertNull($this->new_builder($this->table_goods)
            ->where('cat_id', 6996)
            ->value('discount')
        );

        $this->expectException(record_not_found_exception::class);
        $this->expectExceptionMessage('Can not find data record in database.');

        $this->new_builder($this->table_goods)
            ->where('cat_id', 6996)
            ->value('discount', true);
    }

    public function test_it_selects_one_record() {

        try {
            $this->new_builder($this->table_cats)
                ->where('parent_id', 0)
                ->one();
            $this->fail('An exception that multiple records found should have been thrown');
        } catch (multiple_records_found_exception $exception) {
            $this->assertTrue(true);
        }

        $cat = $this->new_builder($this->table_cats)
            ->where('name', 'Mobile phones')
            ->one();

        $this->assertEquals('A lot of mobile phones', $cat->description);

        try {
            $this->new_builder($this->table_cats)
                ->where('parent_id', -96)
                ->one(true);
            $this->fail('An exception that record not found found should have been thrown');
        } catch (record_not_found_exception $exception) {
            $this->assertTrue(true);
        }

        $this->assertNull($this->new_builder($this->table_cats)
            ->where('parent_id', -96)
            ->one()
        );
    }

    public function test_it_selects_subquery_as_field() {
        $control = $this->new_builder($this->table_cats)
            ->select('id')
            ->add_select('name')
            ->as('z')
            ->add_select((new subquery(function (builder $builder) {
                $builder->from($this->table_goods)
                    ->select('max(price) as max_price')
                    ->where_field('z.id', 'cat_id');
            }))->as('most_expensive_stuff'))
            ->where_exists(function (builder $builder) {
                $builder->from($this->table_goods)
                    ->where_field('cat_id', 'z.id');
            })
            ->order_by((new field('most_expensive_stuff'))->do_not_prefix(), 'desc');

        $this->assertEquals([1499, 2100, 2199, 2400, 4900], $control->get()->pluck('most_expensive_stuff'));
    }

    public function test_it_can_have_having_in_subquery() {

        $control = $this->new_builder($this->table_cats)
            ->select('id')
            ->add_select('name')
            ->as('z')
            ->add_select((new subquery(function (builder $builder) {
                $builder->from($this->table_goods)
                    ->select('max(price) as max_price')
                    ->where_field('z.id', 'cat_id')
                    ->having('max(price)', '>', 2500);
            }))->as('most_expensive_stuff'))
            ->where_exists(function (builder $builder) {
                $builder->from($this->table_goods)
                    ->where('price', '>', 2500)
                    ->where_field('cat_id', 'z.id');
            })
            ->order_by((new field('most_expensive_stuff'))->do_not_prefix(), 'desc');

        $this->assertEquals([4900], $control->get()->pluck('most_expensive_stuff'));
    }

    public function test_it_can_have_having_when_selecting_from_subquery() {

        $subquery = $this->new_builder($this->table_cats);

        $control = builder::table($subquery)
            ->select('id')
            ->add_select('name')
            ->as('z')
            ->add_select((new subquery(function (builder $builder) {
                $builder->from($this->table_goods)
                    ->select('max(price) as max_price')
                    ->where_field('z.id', 'cat_id')
                    ->having('max(price)', '>', 2500);
            }))->as('most_expensive_stuff'))
            ->where_exists(function (builder $builder) {
                $builder->from($this->table_goods)
                    ->where('price', '>', 2500)
                    ->where_field('cat_id', 'z.id');
            })
            ->group_by('name')
            ->group_by('id')
            ->having('name', '!=', 'Name does not exist')
            ->order_by((new field('most_expensive_stuff'))->do_not_prefix(), 'desc');

        $this->assertEquals([4900], $control->get()->pluck('most_expensive_stuff'));
    }

    public function test_it_selects_from_subquery() {
        $subquery = builder::table($this->table_goods)
            ->select('name')
            ->add_select('description');

        $builder = builder::table($subquery)
            ->as('sub')
            ->select('*');

        $this->assertEquals(
            [
                'name' => 'Apple iPhone 6',
                'description' => 'New sleek iPhone',
            ],
            $builder
                ->results_as_arrays()
                ->order_by('name')
                ->first()
        );
    }

    public function test_it_maps_results_to_a_given_class_or_callback() {

        // Our fake mapper class (object)
        $mapper = new class([]) {
            protected $thing;

            public function __construct($thing) {
                $this->thing = (array) $thing;
            }

            public function get_thing() {
                return $this->thing;
            }

            public function add_data($key, $value) {
                $this->thing[$key] = $value;

                return $this;
            }

            public static function callback_get_id($item) {
                return ((array) $item)['id'];
            }
        };

        // Control group
        $control = $this->new_builder($this->table_goods)
            ->results_as_arrays()
            ->fetch();

        // Class map
        // Fight the power
        $mapper_class = get_class($mapper);
        $results = $this->new_builder($this->table_goods)
            ->map_to($mapper_class)
            ->fetch();

        $this->assertNotEmpty($results);

        foreach ($results as $key => $result) {
            $this->assertInstanceOf($mapper_class, $result);
            $this->assertEquals($control[$key], $result->get_thing());
        }

        // Map to a callable
        $results = $this->new_builder($this->table_goods)
            ->map_to([$mapper, 'callback_get_id'])
            ->fetch();

        $this->assertNotEmpty($results);

        foreach ($results as $key => $result) {
            $this->assertEquals($control[$key]['id'], $result);
        }

        // Map to a callback
        $results = $this->new_builder($this->table_goods)
            ->map_to(function (stdClass $item) {
                return $item->name ?? null;
            })
            ->fetch();

        $this->assertNotEmpty($results);

        foreach ($results as $key => $result) {
            $this->assertEquals($control[$key]['name'], $result);
        }

        // Class map
        // Test with lazy collection (recordset)
        // Control group
        $control = $this->new_builder($this->table_goods)
            ->results_as_arrays()
            ->fetch_recordset();

        $results = $this->new_builder($this->table_goods)
            ->map_to($mapper_class)
            ->fetch_recordset();

        $has_results = false;
        foreach ($results as $result) {
            $this->assertInstanceOf($mapper_class, $result);
            $this->assertEquals($control->current(), $result->get_thing());

            $has_results = true;
            $control->next();
        }
        $this->assertTrue($has_results, 'No results were fetched');


        // Callable map
        // Control group
        // Need to re-fetch control as we can't rewind a recordset
        $control = $this->new_builder($this->table_goods)
            ->results_as_arrays()
            ->fetch_recordset();

        $results = $this->new_builder($this->table_goods)
            ->map_to("{$mapper_class}::callback_get_id")
            ->fetch_recordset();

        $has_results = false;
        foreach ($results as $result) {
            $this->assertEquals($control->current()['id'], $result);

            $has_results = true;
            $control->next();
        }
        $this->assertTrue($has_results, 'No results were fetched');


        // Closure map
        // Control group
        // Need to re-fetch control as we can't rewind a recordset
        $control = $this->new_builder($this->table_goods)
            ->results_as_arrays()
            ->fetch_recordset();

        $control = array_column(iterator_to_array($control, false), 'description');

        $results = $this->new_builder($this->table_goods)
            ->map_to(function (array $item) {
                return $item['description'] ?? null;
            })
            ->results_as_arrays()
            ->fetch_recordset();

        $results = iterator_to_array($results, false);

        $this->assertEquals($control, $results);

        // Let's also test that get_lazy alias works as expected
        $results = $this->new_builder($this->table_goods)
            ->map_to(function (array $item) {
                return $item['description'] ?? null;
            })
            ->results_as_arrays()
            ->get_lazy();

        $results = iterator_to_array($results, false);

        $this->assertEquals($control, $results);
    }

    public function test_it_maps_results_correctly_for_all_methods_returning_data() {
        // Fetch recordset is covered already, so we need to cover the following methods:

        // Control group
        $control = $this->new_builder($this->table_goods)
            ->results_as_arrays()
            ->fetch();

        $keyless_control = array_values($control);

        // Get()
        $results = $this->new_builder($this->table_goods)
            ->map_to(function (stdClass $item) {
                return $item->description ?? null;
            })
            ->get()
            ->to_array();

        $this->assertNotEmpty($results);

        foreach ($results as $key => $result) {
            $this->assertEquals($keyless_control[$key]['description'], $result);
        }

        // fetch_counted()
        // This thing doesn't play nice with order of things
        [$results, $count] = $this->new_builder($this->table_goods)
            ->map_to(function (stdClass $item) {
                return [$item->id, $item->description];
            })
            ->fetch_counted();

        $this->assertEquals(count($control), $count);

        foreach ($results as $result) {
            $this->assertEquals($control[$result[0]]['description'], $result[1]);
        }

        // first()
        $result = $this->new_builder($this->table_goods)
            ->map_to(function (stdClass $item) {
                return $item->created_at ?? null;
            })
            ->order_by('id')
            ->first();

        $this->assertEquals($keyless_control[0]['created_at'], $result);

        // find_or_fail()
        $result = $this->new_builder($this->table_goods)
            ->map_to(function (stdClass $item) {
                return $item->updated_at ?? null;
            })
            ->order_by('id')
            ->first_or_fail();

        $this->assertEquals($keyless_control[0]['updated_at'], $result);

        // find()
        $result = $this->new_builder($this->table_goods)
            ->map_to(function (stdClass $item) {
                return $item->updated_at ?? null;
            })
            ->find($keyless_control[1]['id']);

        $this->assertEquals($keyless_control[1]['updated_at'], $result);

        // find_or_fail()
        $result = $this->new_builder($this->table_goods)
            ->map_to(function (stdClass $item) {
                return $item->updated_at ?? null;
            })
            ->find_or_fail($keyless_control[1]['id']);

        $this->assertEquals($keyless_control[1]['updated_at'], $result);
    }

    public function test_it_returns_results_as_arrays_or_objects() {
        // Control group
        $control = array_values($this->new_builder($this->table_goods)
            ->results_as_arrays()
            ->fetch()
        );

        $this->assertIsArray($control[0]);

        // It returns objects by default
        // Get()
        $results = $this->new_builder($this->table_goods)
            ->limit(1)
            ->get();
        
        $this->assertInstanceOf(stdClass::class, $results->first());
        
        $results = $this->new_builder($this->table_goods)
            ->limit(1)
            ->results_as_objects()
            ->get();
        
        $this->assertInstanceOf(stdClass::class, $results->first());

        $results = $this->new_builder($this->table_goods)
            ->limit(1)
            ->results_as_arrays()
            ->get();

        $this->assertIsArray($results->first());

        // It returns objects by default
        // fetch()
        $results = $this->new_builder($this->table_goods)
            ->limit(1)
            ->fetch();

        $this->assertInstanceOf(stdClass::class, array_values($results)[0]);

        $results = $this->new_builder($this->table_goods)
            ->limit(1)
            ->results_as_objects()
            ->fetch();

        $this->assertInstanceOf(stdClass::class, array_values($results)[0]);

        $results = $this->new_builder($this->table_goods)
            ->limit(1)
            ->results_as_arrays()
            ->fetch();

        $this->assertIsArray(array_values($results)[0]);

        // It returns objects by default
        // fetch_counted()
        $results = $this->new_builder($this->table_goods)
            ->limit(1)
            ->fetch_counted()[0];


        $this->assertInstanceOf(stdClass::class, array_values($results)[0]);

        $results = $this->new_builder($this->table_goods)
            ->limit(1)
            ->results_as_objects()
            ->fetch_counted()[0];

        $this->assertInstanceOf(stdClass::class, array_values($results)[0]);

        $results = $this->new_builder($this->table_goods)
            ->limit(1)
            ->results_as_arrays()
            ->fetch_counted()[0];

        $this->assertIsArray(array_values($results)[0]);

        // It returns objects by default
        // first()
        $result = $this->new_builder($this->table_goods)
            ->order_by('id')
            ->first();

        $this->assertInstanceOf(stdClass::class, $result);

        $result = $this->new_builder($this->table_goods)
            ->results_as_objects()
            ->order_by('id')
            ->first();

        $this->assertInstanceOf(stdClass::class, $result);

        $result = $this->new_builder($this->table_goods)
            ->results_as_arrays()
            ->order_by('id')
            ->first();

        $this->assertIsArray($result);

        // It returns objects by default
        // first_or_fail()
        $result = $this->new_builder($this->table_goods)
            ->order_by('id')
            ->first_or_fail();

        $this->assertInstanceOf(stdClass::class, $result);

        $result = $this->new_builder($this->table_goods)
            ->results_as_objects()
            ->order_by('id')
            ->first_or_fail();

        $this->assertInstanceOf(stdClass::class, $result);

        $result = $this->new_builder($this->table_goods)
            ->results_as_arrays()
            ->order_by('id')
            ->first_or_fail();

        $this->assertIsArray($result);

        // It returns objects by default
        // find_or_fail()
        $result = $this->new_builder($this->table_goods)
            ->find_or_fail($control[0]['id']);

        $this->assertInstanceOf(stdClass::class, $result);

        $result = $this->new_builder($this->table_goods)
            ->results_as_objects()
            ->find_or_fail($control[0]['id']);

        $this->assertInstanceOf(stdClass::class, $result);

        $result = $this->new_builder($this->table_goods)
            ->results_as_arrays()
            ->find_or_fail($control[0]['id']);

        $this->assertIsArray($result);

        // It returns objects by default
        // one()
        $result = $this->new_builder($this->table_goods)
            ->where('id', $control[0]['id'])
            ->one();

        $this->assertInstanceOf(stdClass::class, $result);

        $result = $this->new_builder($this->table_goods)
            ->results_as_objects()
            ->where('id', $control[0]['id'])
            ->one();

        $this->assertInstanceOf(stdClass::class, $result);

        $result = $this->new_builder($this->table_goods)
            ->results_as_arrays()
            ->where('id', $control[0]['id'])
            ->one();
        $this->assertIsArray($result);
    }

    public function test_it_updates_records() {
        $apple_cat = $this->new_builder($this->table_cats)
            ->where('name', 'Apple')
            ->order_by('id')
            ->first();

        $builder = $this->new_builder($this->table_goods)
            ->as('goods')
            ->where('cat_id', $apple_cat->id)
            ->update([
                'price' => 1999,
                'description' => 'An overpriced phone'
            ]);

        $this->assertEquals([1999, 1999, 1999, 1999], $builder->get()->pluck('price'));
        $this->assertEquals([
            'An overpriced phone',
            'An overpriced phone',
            'An overpriced phone',
            'An overpriced phone'
        ], $builder->get()->pluck('description'));

        // Test that you can't try to get id in the attributes array, that's to prevent false expectations
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('You cannot supply an id here. To update a single record please use \core\orm\query\builder::update_record() instead');

        $this->new_builder($this->table_cats)
            ->update([
                'id' => $apple_cat->id,
                'description' => 'New description',
            ]);
    }

    public function test_it_updates_record() {
        $original = $this->new_builder($this->table_goods, 'goods')
            ->where('name', 'Galaxy Note 4')
            ->select(['id', 'created_at', 'cat_id'])
            ->order_by('id')
            ->first();

        $this->new_builder($this->table_goods)
            ->as('goods')
            ->where('id', $original->id)
            ->update([
                'description' => 'Android based phone',
                'updated_at' => $updated = time(),
            ]);

        $record = $this->new_builder($this->table_goods)
            ->where('id', $original->id)
            ->order_by('id')
            ->first();

        $this->assertEquals((object) [
            'id' => $original->id,
            'name' => 'Galaxy Note 4',
            'description' => 'Android based phone',
            'price' => '1499',
            'discount' => '10',
            'cat_id' => $original->cat_id,
            'visible' => '1',
            'created_at' => $original->created_at,
            'updated_at' => $updated,
        ], $record);
    }

    public function test_it_updates_a_single_record() {

        $original_record = $this->new_builder($this->table_goods)->order_by('id')->results_as_arrays()->first();
        $record = $original_record;

        $record['price'] += 1;

        $this->new_builder($this->table_goods)
            ->update_record($record);

        $this->assertEquals($record['price'], $this->new_builder($this->table_goods)
            ->where('id', $original_record['id'])
            ->value('price')
        );

        // Now let's try again, but pass price as object
        $record['price'] += 1;

        $this->new_builder($this->table_goods)
            ->update_record((object) $record);

        $this->assertEquals($record['price'], $this->new_builder($this->table_goods)
            ->where('id', $original_record['id'])
            ->value('price')
        );

        // Let's check for exceptions
        try {
            $this->new_builder($this->table_goods)
                ->update_record(['price' => 5]);
            $this->fail('Exception should have been thrown');
        } catch (coding_exception $exception) {
            $this->assertStringContainsString('Id is required to update a single record. Please use \core\orm\query\builder::update() instead', $exception->getMessage());
        }

        // And now as object, just in case
        try {
            $this->new_builder($this->table_goods)
                ->update_record((object) ['price' => 5]);
            $this->fail('Exception should have been thrown');
        } catch (coding_exception $exception) {
            $this->assertStringContainsString('Id is required to update a single record. Please use \core\orm\query\builder::update() instead', $exception->getMessage());
        }
    }

    public function test_it_deletes_records() {
        $this->new_builder($this->table_goods, 'sales')
            ->where('name', 'like_starts_with', 'Apple')
            ->delete();

        $this->assertEquals(5, $this->new_builder($this->table_goods)->count());
    }

    public function test_it_deletes_record() {
        $item = $this->new_builder($this->table_cats)
            ->where('name', 'Nintendo')
            ->results_as_arrays()
            ->order_by('id')
            ->first();

        $this->new_builder($this->table_cats, 'cats')
            ->where('id', $item['id'])
            ->delete();

        $this->assertNull($this->new_builder($this->table_cats)->where('id', $item['id'])->order_by('id')->first());
        $this->assertEquals(11, $this->new_builder($this->table_cats)->count());
    }

    public function test_it_creates_record() {
        $item = $this->new_builder($this->table_goods)
            ->results_as_arrays()
            ->order_by('id')
            ->first();

        $record = [
            'item_id' => $item['id'],
            'buyer_name' => 'Chuck Norris',
            'margin' => '123',
            'created_at' => '1451179043',
            'updated_at' => '1482801443',
        ];

        $id = $this->new_builder($this->table_sales)
            ->insert($record);

        $this->assertEquals([
            'id' => (string) $id,
            'item_id' => $item['id'],
            'buyer_name' => 'Chuck Norris',
            'margin' => '123',
            'created_at' => '1451179043',
            'updated_at' => '1482801443',
        ], $this->new_builder($this->table_sales)->results_as_arrays()->find($id));

        $this->assertEquals(9, $this->new_builder($this->table_sales)->count());
    }

    public function test_it_fails_insert_with_conditions() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('conditions cannot be used with action insert()');

        $record = [
            'item_id' => 1,
            'buyer_name' => 'Chuck Norris',
            'margin' => '123',
            'created_at' => '1451179043',
            'updated_at' => '1482801443',
        ];

        $this->new_builder($this->table_sales)
            ->where('margin', 234)
            ->insert($record);
    }

    /**
     * @param builder $builder
     * @param $property
     * @dataProvider modification_restrictions_dataprovider
     */
    public function test_it_fails_insert(builder $builder, $property) {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage($property.' cannot be used with action insert()');

        $record = [
            'item_id' => 1,
            'buyer_name' => 'Chuck Norris',
            'margin' => '123',
            'created_at' => '1451179043',
            'updated_at' => '1482801443',
        ];

        $builder->insert($record);
    }

    /**
     * @param builder $builder
     * @param $property
     * @dataProvider modification_restrictions_dataprovider
     */
    public function test_it_fails_update(builder $builder, $property) {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage($property.' cannot be used with action update()');

        $record = [
            'item_id' => 1,
            'buyer_name' => 'Chuck Norris',
            'margin' => '123',
            'created_at' => '1451179043',
            'updated_at' => '1482801443',
        ];
        $builder->update($record);
    }

    /**
     * @param builder $builder
     * @param $property
     * @dataProvider modification_restrictions_dataprovider
     */
    public function test_it_fails_delete(builder $builder, $property) {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage($property.' cannot be used with action delete()');

        $builder->delete();
    }

    /**
     * Provides the data for restricted properties on modifications, like update, delete and insert
     *
     * @return array
     */
    public function modification_restrictions_dataprovider(): array {
        return [
            [$this->new_builder($this->table_sales)->offset(10), 'offset'],
            [$this->new_builder($this->table_sales)->limit(10), 'limit'],
            [$this->new_builder($this->table_sales)->join($this->table_cats, 'cat_id', 'id'), 'joins'],
            [$this->new_builder($this->table_sales)->union($this->new_builder($this->table_goods, 'goods')), 'unions'],
            [$this->new_builder($this->table_sales)->select('id'), 'selects'],
            [$this->new_builder($this->table_sales)->order_by('id'), 'orders'],
            [$this->new_builder($this->table_sales)->group_by('id'), 'group_by'],
            [$this->new_builder($this->table_sales)->having('id', 5), 'having'],
        ];
    }

    /**
     * Get new instance of query builder for a given table
     *
     * @param string $table Table name
     * @param string $alias Alias
     * @return builder
     */
    protected function new_builder(string $table, string $alias = '') {
        return (new builder())
            ->from($table)
            ->as($alias);
    }

    protected function create_tables() {
        return $this->create_cats_table()
            ->create_goods_table()
            ->create_sales_table();
    }

    protected function create_goods_table() {

        if ($this->db_man()->table_exists($this->table_goods)) {
            return $this;
        }

        $table = new xmldb_table($this->table_goods);

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('description', XMLDB_TYPE_TEXT);
        $table->add_field('price', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('discount', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('cat_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('visible', XMLDB_TYPE_INTEGER, '1', null, true, null, 1);

        $table->add_field('created_at', XMLDB_TYPE_INTEGER, '10', null);
        $table->add_field('updated_at', XMLDB_TYPE_INTEGER, '10', null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        $this->db_man()->create_table($table);

        return $this;
    }

    protected function create_cats_table() {

        if ($this->db_man()->table_exists($this->table_cats)) {
            return $this;
        }

        $table = new xmldb_table($this->table_cats);

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('description', XMLDB_TYPE_TEXT);
        $table->add_field('parent_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('visible', XMLDB_TYPE_INTEGER, '1');

        $table->add_field('created_at', XMLDB_TYPE_INTEGER, '10', null);
        $table->add_field('updated_at', XMLDB_TYPE_INTEGER, '10', null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        $this->db_man()->create_table($table);

        return $this;
    }

    protected function create_sales_table() {

        if ($this->db_man()->table_exists($this->table_sales)) {
            return $this;
        }

        $table = new xmldb_table($this->table_sales);

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('item_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('buyer_name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('margin', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('created_at', XMLDB_TYPE_INTEGER, '10', null);
        $table->add_field('updated_at', XMLDB_TYPE_INTEGER, '10', null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        $this->db_man()->create_table($table);

        return $this;
    }

    protected function create_data() {
        // Create categories
        $categories = [
            [
                'name' => 'Mobile phones',
                'description' => 'A lot of mobile phones',
                'parent_id' => 0,
                'created_at' => time(),
                'updated_at' => time(),
            ],
            [
                'name' => 'Computers',
                'description' => 'Various computers',
                'parent_id' => 0,
                'created_at' => time(),
                'updated_at' => time(),
            ],
            [
                'name' => 'Consoles',
                'description' => 'Gaming consoles',
                'parent_id' => 0,
                'created_at' => time(),
                'updated_at' => time(),
            ],
        ];
        $subcats = [
            'Mobile phones' => [
                [
                    'name' => 'Apple',
                    'description' => 'Iphones',
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                [
                    'name' => 'Android',
                    'description' => 'Samsung, LG, etc',
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                [
                    'name' => 'Microsoft',
                    'description' => 'Windows phones',
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
            ],
            'Computers' => [
                [
                    'name' => 'Laptops',
                    'description' => 'Portable computers',
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                [
                    'name' => 'Desktops',
                    'description' => 'Desktop computers',
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                [
                    'name' => 'Ex-lease',
                    'description' => 'Used computers',
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
            ],
            'Consoles' => [
                [
                    'name' => 'Sony',
                    'description' => 'Ps',
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                [
                    'name' => 'Microsoft',
                    'description' => 'Xbox',
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                [
                    'name' => 'Nintendo',
                    'description' => 'Wii',
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
            ]
        ];

        $cats = [];

        foreach ($categories as $category) {
            $category['id'] = $this->db()->insert_record($this->table_cats, (object) $category);
            $cats[$category['name']] = $category;
        }

        foreach ($subcats as $name => $category) {
            foreach ($category as $item) {
                $item['parent_id'] = $cats[$name]['id'];
                $item['id'] = $this->db()->insert_record($this->table_cats, (object) $item);
                $cats[$item['name']] = $item;
            }
        }

        // Create goods
        $goods = [
            [
                'name' => 'Apple iPhone 6',
                'description' => 'New sleek iPhone',
                'price' => '1350',
                'cat_id' => 'Apple',
                'created_at' => time(),
                'updated_at' => time(),
            ],
            [
                'name' => 'Apple iPhone 7',
                'description' => 'Newer sleek iPhone',
                'price' => '1650',
                'cat_id' => 'Apple',
                'created_at' => time(),
                'updated_at' => time(),
            ],
            [
                'name' => 'Apple iPhone 8',
                'description' => 'New sleeker iPhone',
                'price' => '1650',
                'cat_id' => 'Apple',
                'created_at' => time(),
                'updated_at' => time(),
            ],
            [
                'name' => 'Apple iPhone X',
                'description' => 'Newer sleeker iPhone',
                'price' => '2199',
                'cat_id' => 'Apple',
                'created_at' => time(),
                'updated_at' => time(),
            ],
            [
                'name' => 'Galaxy Note 4',
                'description' => 'Newer sleeker iPhone',
                'price' => '1499',
                'discount' => 10,
                'cat_id' => 'Android',
                'created_at' => time(),
                'updated_at' => time(),
            ],
            [
                'name' => 'MacBook pro',
                'description' => '2015 model sale',
                'price' => '2100',
                'discount' => 25,
                'cat_id' => 'Laptops',
                'created_at' => time(),
                'updated_at' => time(),
            ],
            [
                'name' => 'MacBook air',
                'description' => '2017 model sale',
                'price' => '1400',
                'discount' => 11,
                'cat_id' => 'Laptops',
                'created_at' => time(),
                'updated_at' => time(),
            ],
            [
                'name' => 'HP Workstation',
                'description' => 'Powerful workstation',
                'price' => '4900',
                'cat_id' => 'Desktops',
                'created_at' => time(),
                'updated_at' => time(),
            ],
            [
                'name' => 'Gaming desktop',
                'description' => 'Gaming desktop',
                'price' => '2400',
                'cat_id' => 'Ex-lease',
                'created_at' => time(),
                'updated_at' => time(),
            ],
        ];

        foreach ($goods as &$item) {
            $item['cat_id'] = $cats[$item['cat_id']]['id'];
            $item['id'] = $this->db()->insert_record($this->table_goods, (object) $item);
        }

        // Key by name
        $goods = array_combine(array_column($goods, 'name'), $goods);

        $sales = [
            [
                'item_id' => 'Apple iPhone X',
                'buyer_name' => 'John Doe',
                'margin' => 500,
                'created_at' => time(),
                'updated_at' => time(),
            ],
            [
                'item_id' => 'Apple iPhone X',
                'buyer_name' => 'Jane Doe',
                'margin' => 600,
                'created_at' => time(),
                'updated_at' => time(),
            ],
            [
                'item_id' => 'Apple iPhone X',
                'buyer_name' => 'Donald Trump',
                'margin' => 900,
                'created_at' => time(),
                'updated_at' => time(),
            ],
            [
                'item_id' => 'Apple iPhone X',
                'buyer_name' => 'George Bush',
                'margin' => 300,
                'created_at' => time(),
                'updated_at' => time(),
            ],
            [
                'item_id' => 'Apple iPhone X',
                'buyer_name' => 'Mr. Smith',
                'margin' => 200,
                'created_at' => time(),
                'updated_at' => time(),
            ],
            [
                'item_id' => 'MacBook pro',
                'buyer_name' => 'Mr. Smith',
                'margin' => 100,
                'created_at' => time(),
                'updated_at' => time(),
            ],
            [
                'item_id' => 'HP Workstation',
                'buyer_name' => 'Mr. Smith',
                'margin' => 300,
                'created_at' => time(),
                'updated_at' => time(),
            ],
            [
                'item_id' => 'MacBook air',
                'buyer_name' => 'John Doe',
                'margin' => 150,
                'created_at' => time(),
                'updated_at' => time(),
            ],
        ];

        foreach ($sales as &$sale) {
            $sale['item_id'] = $goods[$sale['item_id']]['id'];
            $sale['id'] = $this->db()->insert_record($this->table_sales, (object) $sale);
        }

        $this->data = [
            'cats' => $cats,
            'goods' => $goods,
            'sales' => $sales,
        ];

        return $this;
    }

    public function drop_test_tables() {

        if ($this->db_man()->table_exists($this->table_sales)) {
            $this->db_man()->drop_table(new xmldb_table($this->table_sales));
        }

        if ($this->db_man()->table_exists($this->table_goods)) {
            $this->db_man()->drop_table(new xmldb_table($this->table_goods));
        }

        if ($this->db_man()->table_exists($this->table_cats)) {
            $this->db_man()->drop_table(new xmldb_table($this->table_cats));
        }

        return $this;
    }

}
