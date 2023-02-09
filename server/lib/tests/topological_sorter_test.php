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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

class core_topological_sorter_testcase extends basic_testcase {
    public function test_single_node() {
        $sorter = new \core\topological_sorter();
        $sorter->add('foo');
        $this->assertEquals(['foo'], $sorter->sort());
    }

    public function test_single_node_with_deps() {
        $sorter = new \core\topological_sorter();
        $sorter->add('foo', ['bar']);
        $this->assertEquals(['bar', 'foo'], $sorter->sort());
    }

    public function test_multiple_graphs() {
        $sorter = new \core\topological_sorter();
        $sorter->add('foo', ['bar']);
        $sorter->add('bar', ['bar-1', 'bar-2']);
        $sorter->add('baz', ['qux']);
        $sorter->add('qux', ['qux-1', 'qux-2']);
        $this->assertEquals(['bar-1', 'bar-2', 'bar', 'foo', 'qux-1', 'qux-2', 'qux', 'baz'], $sorter->sort());
    }

    public function test_dependencies_are_ordered_correctly() {
        $sorter = new \core\topological_sorter();
        // let's make a stir fry!
        $sorter->add('sauce', ['oyster_sauce', 'soy_sauce']);
        $sorter->add('chopped_broccoli', ['raw_broccoli', 'knife']);
        $sorter->add('stir_fry', ['chopped_broccoli', 'chopped_carrots', 'prepared_tofu', 'sauce']);
        $sorter->add('chopped_carrots', ['raw_carrots', 'knife']);
        $sorter->add('prepared_tofu', ['raw_tofu', 'knife', 'tofu_press']);
        $sorter->add('oyster_sauce', ['oysters', 'industrial_oyster_saucer']);
        $sorter->add('soy_sauce', ['soy', 'mold']);
        $sorter->add('dinner_plate');
        $sorter->add('drink', ['water', 'drinking_glass']);

        $expected = [
            'oysters',
            'industrial_oyster_saucer',
            'oyster_sauce',
            'soy',
            'mold',
            'soy_sauce',
            'sauce',
            'raw_broccoli',
            'knife',
            'chopped_broccoli',
            'raw_carrots',
            'chopped_carrots',
            'raw_tofu',
            'tofu_press',
            'prepared_tofu',
            'stir_fry',
            'dinner_plate',
            'water',
            'drinking_glass',
            'drink'
        ];

        $this->assertEquals($expected, $sorter->sort());
    }

    public function test_circular_dependency_throws() {
        $this->expectException(\core\topological_sorter_circular_dependency_exception::class);
        $this->expectExceptionMessage('There is a circular dependency between "foo" and "bar".');
        $sorter = new \core\topological_sorter();
        $sorter->add('foo', ['bar']);
        $sorter->add('bar', ['foo']);
        $sorter->sort();
    }

    public function test_circular_dependency_3_node_throws() {
        $this->expectException(\core\topological_sorter_circular_dependency_exception::class);
        $this->expectExceptionMessage('There is a circular dependency between "foo" and "baz".');
        $sorter = new \core\topological_sorter();
        $sorter->add('foo', ['bar']);
        $sorter->add('bar', ['baz']);
        $sorter->add('baz', ['foo']);
        $sorter->sort();
    }

    public function test_self_reference_throws() {
        $this->expectException(\core\topological_sorter_circular_dependency_exception::class);
        $this->expectExceptionMessage('There is a circular dependency in "foo".');
        $sorter = new \core\topological_sorter();
        $sorter->add('foo', ['foo']);
        $sorter->sort();
    }
}
