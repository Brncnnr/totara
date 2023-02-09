<?php
/**
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
 * @package core
 * @group orm
 */

use core\collection;

defined('MOODLE_INTERNAL') || die();

/**
 * Class core_orm_collection_testcase
 *
 * @covers \core\collection
 * @package core
 * @group orm
 */
class core_collection_testcase extends advanced_testcase {

    public function test_it_returns_collection() {
        $collection = new collection([]);

        $this->assertInstanceOf(collection::class, $collection);
    }

    public function test_it_collects_items() {
        $collection = new collection([]);

        $this->assertEmpty($collection->all(), 'Must return an empty collection');

        $items = $this->get_dummy_unkeyed_items();
        $collection = new collection($items);

        $this->assertIsArray($collection->all());
        $this->assertSameSize($items, $collection->all());
        $this->assertSame($items, $collection->all());
        $this->assertSame($items, $collection->all(true));
    }

    public function test_it_handles_no_items() {
        $collection = new collection();
        $this->assertIsArray($collection->all());
        $this->assertEmpty($collection->all());
        $this->assertSame([], $collection->to_array());
        $this->assertSame([], $collection->keys());
        $this->assertNull($collection->first());
    }

    public function test_it_converts_items_to_array() {
        $items = $this->get_dummy_unkeyed_items();
        $collection = new collection($items);
        $this->assertSame(array_values($items), $collection->to_array());

        $items = $this->get_dummy_keyed_sequential_ordered_items();
        $collection = new collection($items);
        $this->assertSame(array_values($items), $collection->to_array());

        $items = $this->get_dummy_keyed_mismatched_key_items();
        $collection = new collection($items);
        $this->assertSame(array_values($items), $collection->to_array());

        $items = $this->get_dummy_keyed_non_sequential_unordered_items();
        $collection = new collection($items);
        $this->assertSame(array_values($items), $collection->to_array());
    }

    public function test_it_returns_item_keys() {
        $items = $this->get_dummy_unkeyed_items();
        $collection = new collection($items);
        $this->assertSame(array_keys($items), $collection->keys());

        $items = $this->get_dummy_keyed_sequential_ordered_items();
        $collection = new collection($items);
        $this->assertSame(array_keys($items), $collection->keys());

        $items = $this->get_dummy_keyed_mismatched_key_items();
        $collection = new collection($items);
        $this->assertSame(array_keys($items), $collection->keys());

        $items = $this->get_dummy_keyed_non_sequential_unordered_items();
        $collection = new collection($items);
        $this->assertSame(array_keys($items), $collection->keys());
    }

    public function test_it_compacts_array_to_a_single_dimension() {
        $items = $this->get_dummy_unkeyed_items();
        $collection = new collection($items);
        $this->assertSame(array_column($items, 'name'), $collection->pluck('name'));

        $items = $this->get_dummy_keyed_sequential_ordered_items();
        $collection = new collection($items);
        $this->assertSame(array_column($items, 'name'), $collection->pluck('name'));

        $items = $this->get_dummy_keyed_mismatched_key_items();
        $collection = new collection($items);
        $this->assertSame(array_column($items, 'name'), $collection->pluck('name'));

        $items = $this->get_dummy_keyed_non_sequential_unordered_items();
        $collection = new collection($items);
        $this->assertSame(array_column($items, 'name'), $collection->pluck('name'));
    }

    public function test_it_returns_all_items_in_collection() {
        $items = $this->get_dummy_unkeyed_items();
        $collection = new collection($items);
        $this->assertSame($items, $collection->all());
        $this->assertSame($items, $collection->all(true));

        $items = $this->get_dummy_keyed_sequential_ordered_items();
        $collection = new collection($items);
        $this->assertNotSame($items, $collection->all());
        $this->assertSame(array_values($items), $collection->all());
        $this->assertSame($items, $collection->all(true));

        $items = $this->get_dummy_keyed_mismatched_key_items();
        $collection = new collection($items);
        $this->assertNotSame($items, $collection->all());
        $this->assertSame(array_values($items), $collection->all());
        $this->assertSame($items, $collection->all(true));

        $items = $this->get_dummy_keyed_non_sequential_unordered_items();
        $collection = new collection($items);
        $this->assertNotSame($items, $collection->all());
        $this->assertSame(array_values($items), $collection->all());
        $this->assertSame($items, $collection->all(true));
    }

    public function test_it_returns_first_item_in_collection() {
        $items = $this->get_dummy_unkeyed_items();
        $collection = new collection($items);
        $this->assertSame(reset($items), $collection->first());

        $items = $this->get_dummy_keyed_sequential_ordered_items();
        $collection = new collection($items);
        $this->assertSame(reset($items), $collection->first());

        $items = $this->get_dummy_keyed_mismatched_key_items();
        $collection = new collection($items);
        $this->assertSame(reset($items), $collection->first());

        $items = $this->get_dummy_keyed_non_sequential_unordered_items();
        $collection = new collection($items);
        $this->assertSame(reset($items), $collection->first());
    }

    public function test_it_returns_requested_item_in_collection() {
        $items = $this->get_dummy_unkeyed_items();
        $collection = new collection($items);
        $this->assertSame($items[2], $collection->item(2));
        $this->assertSame($items[0], $collection->item(0));
        $this->assertNull($collection->item(13));

        $items = $this->get_dummy_keyed_sequential_ordered_items();
        $collection = new collection($items);
        $this->assertEquals($items[2], $collection->item(2));
        $this->assertNull($collection->item(0));
        $this->assertNull($collection->item(13));

        $items = $this->get_dummy_keyed_mismatched_key_items();
        $collection = new collection($items);
        $this->assertEquals($items[18], $collection->item(18));
        $this->assertNull($collection->item(0));
        $this->assertNull($collection->item(13));

        $items = $this->get_dummy_keyed_non_sequential_unordered_items();
        $collection = new collection($items);
        $this->assertEquals($items[64], $collection->item(64));
        $this->assertNull($collection->item(0));
        $this->assertNull($collection->item(13));
    }

    public function test_transform_to() {

        $callback = function ($item) {
            return new class($item) {
                private $record;
                public function __construct($record) {
                    $this->record = $record;
                }
                public function name() {
                    return $this->record['name'];
                }
            };
        };

        $items = $this->get_dummy_unkeyed_items();
        $collection = (new collection($items))->transform_to($callback);
        $this->assertSameSize($items, $collection->all());
        $this->assertSame(reset($items)['name'], $collection->first()->name());
        $this->assertSame(array_keys($items), $collection->keys());

        $items = $this->get_dummy_keyed_sequential_ordered_items();
        $collection = (new collection($items))->transform_to($callback);
        $this->assertSameSize($items, $collection->all());
        $this->assertSame(reset($items)['name'], $collection->first()->name());
        $this->assertSame(array_keys($items), $collection->keys());

        $items = $this->get_dummy_keyed_non_sequential_unordered_items();
        $collection = (new collection($items))->transform_to($callback);
        $this->assertSameSize($items, $collection->all());
        $this->assertSame(reset($items)['name'], $collection->first()->name());
        $this->assertSame(array_keys($items), $collection->keys());

        $items = $this->get_dummy_keyed_mismatched_key_items();
        $collection = (new collection($items))->transform_to($callback);
        $this->assertSameSize($items, $collection->all());
        $this->assertSame(reset($items)['name'], $collection->first()->name());
        $this->assertSame(array_keys($items), $collection->keys());
    }

    public function test_it_maps_callback_to_collection_items() {
        $items = $this->get_dummy_unkeyed_items();

        $collection = new collection($items);

        $new = $collection->map(function ($item) {
            return $item['id'];
        });

        $this->assertEquals(array_column($items, 'id'), $new->all());

        $mapper = new class(['id' => null]) {
            public $new_mapped_id;

            public function __construct($item) {
                $this->new_mapped_id = $item['id'];
            }
        };

        $new = (new collection($items))
            ->map_to(get_class($mapper))
            ->all();

        $this->assertEquals(array_column($items, 'id'), array_column($new, 'new_mapped_id'));
    }

    public function test_it_transforms_collection_items() {
        $items = $this->get_dummy_unkeyed_items();

        $collection = new collection($items);

        $collection->transform(function ($item) {
            return $item['id'];
        });

        $this->assertEquals(array_column($items, 'id'), $collection->all());

        $mapper = new class(['id' => null]) {
            public $new_mapped_id;

            public function __construct($item) {
                $this->new_mapped_id = $item['id'];
            }
        };

        $collection = (new collection($items))
            ->map_to(get_class($mapper))
            ->all();

        $this->assertEquals(array_column($items, 'id'), array_column($collection, 'new_mapped_id'));
    }

    public function test_it_keys_items_by_a_given_column() {
        $items = $this->get_dummy_unkeyed_items();

        $collection = new collection($items);

        $collection->key_by('name');

        $this->assertEquals(array_column($items, 'name'), $collection->keys());
    }

    public function test_it_appends_an_item_to_collection() {
        // Append sequential item
        $items = $this->get_dummy_unkeyed_items();

        $collection = new collection($items);

        $appended = ['hey' => 'bro'];

        $collection->append($appended);

        $this->assertCount(count($items) + 1, $collection->all());

        $item = $collection->all()[count($collection) - 1];

        $this->assertEquals($appended, $item);

        // Append item with a key
        $items = $this->get_dummy_keyed_sequential_ordered_items();

        $collection = new collection($items);

        $collection->set($appended, 'my_key');

        $this->assertCount(count($items) + 1, $collection->all());

        $item = $collection->all(true)['my_key'];

        $this->assertEquals($appended, $item);
    }

    public function test_it_counts_items_properly() {
        $items = $this->get_dummy_unkeyed_items();

        $collection = new collection($items);

        $this->assertCount(count($items), $collection);
        $this->assertEquals(count($items), $collection->count());
    }

    public function test_it_does_isset_and_unset_properly() {
        $items = $this->get_dummy_keyed_sequential_ordered_items();


        $collection = new collection($items);

        $this->assertTrue(isset($collection->{1}));
        $this->assertFalse(isset($collection->undefined));

        unset($collection->{1});

        $this->assertFalse(isset($collection->{1}));
        $this->assertCount(count($items) - 1, $collection);
    }

    public function test_it_converts_items_to_json() {
        $items = $this->get_dummy_unkeyed_items();

        $json = json_encode($items);

        $collection = new collection($items);

        $this->assertEquals($json, json_encode($collection));
        $this->assertEquals($json, (string) $collection);
    }

    public function test_it_can_filter_collection_items() {
        $collection = new collection($this->get_dummy_keyed_non_sequential_unordered_items());

        $this->assertEquals(['Jane'], $collection->filter('id', '64')->pluck('name'));
        $this->assertEmpty($collection->filter('id', '64', true)->pluck('name'));
        $this->assertEquals(['Jane'], $collection->filter('id', 64, true)->pluck('name'));

        $this->assertEquals(
            [2, '2', 2],
            collection::new([2, '2', 3, 19, 29, 2])
                ->filter('any', 2)
                ->all()
        );

        $mixed_collection = new collection($this->get_dummy_mixed_items());

        $this->assertEquals(
            [(object) ['field' => 5], 5, ['field' => 5]],
            $mixed_collection->filter('field', 5, true)->all()
        );

        // Now using callback
        $collection = new collection($this->get_dummy_keyed_non_sequential_unordered_items());

        $this->assertEquals(['Jane', 'Ashley'], $collection->filter(function ($item) {
            return $item['id'] === 64 || $item['name'] === 'Ashley';
        })->pluck('name'));

        // Now trying to call it incorrectly
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Column must be either callable or string');

        $collection->filter([]);
    }

    public function test_it_can_find_an_item_in_the_collection() {
        $collection = new collection($this->get_dummy_keyed_non_sequential_unordered_items());

        $this->assertEquals('Jane', $collection->find('id', '64')['name']);
        $this->assertNull($collection->find('id', '64', true));
        $this->assertEquals('Jane', $collection->find('id', 64, true)['name']);

        $this->assertEquals(
            2,
            collection::new([2, '2', 3, 19, 29, 2])
                ->find('any', 2)
        );

        $mixed_collection = new collection($this->get_dummy_mixed_items());

        $this->assertEquals(
            (object) ['field' => 5],
            $mixed_collection->find('field', 5, true)
        );

        // Now using callback
        $collection = new collection($this->get_dummy_keyed_non_sequential_unordered_items());

        $this->assertEquals('Jane', $collection->find(function ($item) {
            return $item['id'] === 64 || $item['name'] === 'Ashley';
        })['name']);
    }

    public function test_can_check_if_collection_has_item() {
        $collection = new collection($this->get_dummy_keyed_non_sequential_unordered_items());

        $this->assertTrue($collection->has('id', 64));
        $this->assertFalse($collection->has('id', '007'));
        $this->assertFalse($collection->has('id', '64', true));

        // Test using a callback.
        $this->assertTrue(
            $collection->has(function ($item) {
                return $item['id'] === 64 && $item['name'] === 'Jane';
            })
        );
    }

    public function test_it_can_sort_simple_collection() {
        $unsorted_collection = [1, 23, 4, 5, 7, 123, 9, 14, 40];
        $sorted_collection = [1, 4, 5, 7, 9, 14, 23, 40, 123];

        $this->assertEquals(
            $sorted_collection,
            collection::new($unsorted_collection)
                ->sort('12345')
                ->all()
        );

        $this->assertEquals(
            array_reverse($sorted_collection),
            collection::new($unsorted_collection)
                ->sort('12345', 'desc')
                ->all()
        );

        // Now trying to call it incorrectly
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Column must be either callable or string');

        collection::new([])->sort([]);
    }

    public function test_it_can_sort_collection_using_callback() {
        $callback = function ($a, $b) {
            return $a['id'] <=> $b['id'];
        };

        $sorted_collection = $this->get_dummy_keyed_mismatched_key_items();
        uasort($sorted_collection, $callback);

        $this->assertEquals(
            array_values($sorted_collection),
            collection::new($this->get_dummy_keyed_mismatched_key_items())
                ->sort($callback)
                ->all()
        );
    }

    public function test_it_behaves_like_array() {
        // To test that array functions work correctly, let's rebuild the current collection in the loop
        $collection = new collection($this->get_dummy_keyed_mismatched_key_items());

        $first = $collection->current();
        $new = [];

        do {
            $new[$collection->key()] = $collection->current();
            $collection->next();
        } while ($collection->valid());

        $this->assertFalse($collection->valid());
        $collection->rewind();
        $this->assertEquals($first, $collection->current());
        $this->assertEquals($collection->all(true), $new);
    }

    public function test_it_can_sort_a_mixed_collection() {
        $unsorted_collection = [
            (object) [
                'field' => 5,
                'another_field' => 1,
            ],
            [
                'field' => 1,
                'another_field' => 7,
            ],
            [
                'field' => 3,
                'another_field' => 7,
            ],
            9
        ];

        $sorted_collection = [
            [
                'field' => 1,
                'another_field' => 7,
            ],
            [
                'field' => 3,
                'another_field' => 7,
            ],
            (object) [
                'field' => 5,
                'another_field' => 1,
            ],
            9
        ];

        $this->assertEquals(
            $sorted_collection,
            collection::new($unsorted_collection)
                ->sort('field')
                ->all()
        );

        $this->assertEquals(
            array_reverse($sorted_collection),
            collection::new($unsorted_collection)
                ->sort('field', 'desc')
                ->all()
        );
    }

    public function test_it_can_reduce_collection_items() {
        $callback = function ($carry, $item) {
            return intval($item['id']) + intval($carry);
        };

        $this->assertEquals(
            array_reduce($this->get_dummy_keyed_non_sequential_unordered_items(), $callback, 0),
            collection::new($this->get_dummy_keyed_non_sequential_unordered_items())->reduce($callback, 0)
        );
    }

    public function test_it_pops_collection_item() {
        $items = [1, 3, 7, 9, 15];
        $collection = new collection($items);

        $this->assertEquals(array_pop($items), $collection->pop());
        $this->assertEquals($items, $collection->all(true));

        $this->assertNull(collection::new([])->pop());
    }

    public function test_it_shifts_collection_item() {
        $items = [1, 3, 7, 9, 15];
        $collection = new collection($items);

        $this->assertEquals(array_shift($items), $collection->shift());
        $this->assertEquals($items, $collection->all(true));

        $this->assertNull(collection::new([])->shift());
    }

    public function test_it_returns_last_collection_item() {
        $items = [1, 3, 7, 9, 15];
        $collection = new collection($items);

        $this->assertEquals(15, $collection->last());
        $this->assertEquals($items, $collection->all());
    }

    public function test_it_converts_collection_of_entities_to_array() {
        $callback = function ($item) {
            return new class($item) {
                private $record;
                public function __construct($record) {
                    $this->record = $record;
                }
                public function name() {
                    return $this->record['name'];
                }
                public function to_array() {
                    return $this->record;
                }
            };
        };

        $items = $this->get_dummy_keyed_sequential_ordered_items();
        $collection = (new collection($items))->transform($callback);
        $this->assertSameSize($items, $collection->to_array());
        $this->assertSame(array_values($items), $collection->to_array());
    }

    public function get_dummy_unkeyed_items() {
        // It's have non-sequential keys that are out of order to ensure that keys are handled correctly.
        return [
            [
                'id' => 1,
                'name' => 'John',
            ],
            [
                'id' => 2,
                'name' => 'Jane',
            ],
            [
                'id' => 3,
                'name' => 'Ashley',
            ],
        ];
    }

    public function get_dummy_keyed_sequential_ordered_items() {
        // It's have non-sequential keys that are out of order to ensure that keys are handled correctly.
        return [
            1 => [
                'id' => 1,
                'name' => 'John',
            ],
            2 => [
                'id' => 2,
                'name' => 'Jane',
            ],
            3 => [
                'id' => 3,
                'name' => 'Ashley',
            ],
        ];
    }

    public function get_dummy_keyed_mismatched_key_items() {
        // It's have non-sequential keys that are out of order to ensure that keys are handled correctly.
        return [
            1 => [
                'id' => 37,
                'name' => 'John',
            ],
            18 => [
                'id' => 2,
                'name' => 'Jane',
            ],
            69 => [
                'id' => 96,
                'name' => 'Ashley',
            ],
        ];
    }

    public function get_dummy_keyed_non_sequential_unordered_items() {
        // It's have non-sequential keys that are out of order to ensure that keys are handled correctly.
        return [
            57 => [
                'id' => 57,
                'name' => 'John',
            ],
            64 => [
                'id' => 64,
                'name' => 'Jane',
            ],
            59 => [
                'id' => 59,
                'name' => 'Ashley',
            ],
        ];
    }

    public function get_dummy_mixed_items() {
        return [
            (object) [
                'field' => 5,
            ],
            ['field' => '5'],
            5,
            24,
            '5',
            ['field' => 5],
            (object) [
                'field' => '21',
            ],
            (object) [
                'field' => '5',
            ],
        ];
    }
}
