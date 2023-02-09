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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package core
 * @group orm
 */

use \core\orm\lazy_collection;

defined('MOODLE_INTERNAL') || die();

class core_orm_lazy_collection_testcase extends basic_testcase {

    public function test_basic_iterator_functionality() {
        $rs = self::get_recordset([
            1 => [
                'name' => 'test'
            ],
            2 => [
                'name' => 'foo'
            ],
            32 => [
                'name' => 'bar'
            ],
        ]);

        $collection = new lazy_collection($rs);
        $this->assertSame('test', $collection->key());
        $this->assertSame('test', $collection->current()->name);
        $collection->next();
        $this->assertSame('foo', $collection->key());
        $this->assertSame('foo', $collection->current()->name);
        $collection->next();
        $this->assertSame('bar', $collection->key());
        $this->assertSame('bar', $collection->current()->name);
        $collection->rewind();
        $this->assertSame('foo', $collection->key());
        $this->assertSame('foo', $collection->current()->name);
        $collection->close();
        unset($collection);
    }

    public function test_mapping_as_object() {
        $rs = self::get_recordset([
            1 => [
                'name' => 'test'
            ],
            2 => [
                'name' => 'foo'
            ],
            32 => [
                'name' => 'bar'
            ],
        ]);
        $collection = new lazy_collection($rs);
        $this->assertEquals((object)['name' => 'test'], $collection->current());
        $this->assertSame($collection, $collection->as_array(false));
        $this->assertEquals((object)['name' => 'test'], $collection->current());
    }

    public function test_mapping_as_array() {
        $rs = self::get_recordset([
            1 => [
                'name' => 'test'
            ],
            2 => [
                'name' => 'foo'
            ],
            32 => [
                'name' => 'bar'
            ],
        ]);
        $collection = new lazy_collection($rs);
        $this->assertSame($collection, $collection->as_array());
        $this->assertSame(['name' => 'test'], $collection->current());
        $this->assertSame($collection, $collection->as_array(true));
        $this->assertSame(['name' => 'test'], $collection->current());
    }

    public function test_mapping_as_callable() {
        $callback = function (array $item) {
            return new class($item) {
                private $detail;
                public function __construct(array $item) {
                    $this->detail = $item['name'];
                }
                public function detail() {
                    return $this->detail;
                }
            };
        };
        $rs = self::get_recordset([
            1 => [
                'name' => 'test'
            ],
            2 => [
                'name' => 'foo'
            ],
            32 => [
                'name' => 'bar'
            ],
        ]);
        $collection = new lazy_collection($rs);
        $collection->map_to($callback);
        $collection->as_array();
        $this->assertEquals('test', $collection->current()->detail());
        $collection->next();
        $this->assertEquals('foo', $collection->current()->detail());
    }

    public function test_mapping_forced_madness() {
        $rs = self::get_recordset([
            1 => [
                'name' => 'test'
            ],
            2 => [
                'name' => 'foo'
            ],
            32 => [
                'name' => 'bar'
            ],
        ]);
        $collection = new lazy_collection($rs);
        $property = new ReflectionProperty($collection, 'map_to');
        $property->setAccessible(true);
        $property->setValue($collection, true);

        $this->assertSame($collection, $collection->as_array());
        $this->assertSame(['name' => 'test'], $collection->current());
        $this->assertSame($collection, $collection->as_array(true));
        $this->assertSame(['name' => 'test'], $collection->current());
    }

    public function test_mapping_as_invalid() {
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('Cannot map to something that is not callable or a class');
        $rs = self::get_recordset([]);
        $collection = new lazy_collection($rs);
        $collection->map_to(true);
    }

    private static function get_recordset(array $result) {
        $class = new class($result) extends moodle_recordset {
            protected $result;
            public function __construct(array $result) {
                $this->result  = $result;
            }

            public function __destruct() {
                $this->close();
            }

            #[\ReturnTypeWillChange]
            public function current() {
                return (object)current($this->result);
            }

            #[\ReturnTypeWillChange]
            public function key() {
                $current = current($this->result);
                $key = reset($current);
                return $key;
            }

            #[\ReturnTypeWillChange]
            public function next() {
                next($this->result);
            }

            #[\ReturnTypeWillChange]
            public function rewind() {
                prev($this->result);
            }

            #[\ReturnTypeWillChange]
            public function valid() {
                return !empty(current($this->result));
            }

            public function close() {
                $this->result  = null;
            }
        };
        return $class;
    }

}