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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_core
 * @category test
 */

use totara_core\basket\basket;
use totara_core\basket\storage\session_adapter;
use totara_core\basket\storage\simple_adapter;
use totara_core\basket\storage\adapter;

defined('MOODLE_INTERNAL') || die();

class totara_core_basket_testcase extends advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
    }

    /**
     * @param $id
     * @param adapter $storage
     *
     * @dataProvider basket_data_provider
     */
    public function test_load_empty_basket($id, adapter $storage) {
        $basket = new basket($id, $storage);
        $items = $basket->load();
        $this->assertIsArray($items);
        $this->assertEmpty($items);
    }

    /**
     * @param $id
     * @param adapter $storage
     *
     * @dataProvider basket_data_provider
     */
    public function test_basket_id($id, adapter $storage) {
        $basket = new basket($id, $storage);
        $this->assertEquals($id, $basket->get_key());
    }

    /**
     * @param $id
     * @param adapter $storage
     *
     * @dataProvider basket_data_provider
     */
    public function test_add_items_numerical($id, adapter $storage) {
        $basket = new basket($id, $storage);
        $basket->add([1, 2, 4, 3]);
        $this->assertEquals([1, 2, 3, 4], $basket->load());

        $basket->add(['bla' => 1, 5, 7, 'sddsf' => 6]);
        $this->assertEquals([1, 2, 3, 4, 5, 6, 7], $basket->load());
    }

    /**
     * @param $id
     * @param adapter $storage
     *
     * @dataProvider basket_data_provider
     */
    public function test_add_items_string($id, adapter $storage) {
        $basket = new basket($id, $storage);
        $basket->add(['def', 'abc', 'bcd', 'fgh']);
        $this->assertEquals(['abc', 'bcd', 'def', 'fgh'], $basket->load());

        $basket->add(['bla' => 'bbb', 'aab', 'sddsf' => 'ghi']);
        $this->assertEquals(['aab', 'abc', 'bbb', 'bcd', 'def', 'fgh', 'ghi'], $basket->load());
    }

    /**
     * @param $id
     * @param adapter $storage
     *
     * @dataProvider basket_data_provider
     */
    public function test_remove_items($id, adapter $storage) {
        $basket = new basket($id, $storage);
        $basket->add([1, 2, 4, 3]);

        $basket->remove([2, 4, 5, 7, 0]);
        $this->assertEquals([1, 3], $basket->load());
    }

    /**
     * @param $id
     * @param adapter $storage
     *
     * @dataProvider basket_data_provider
     */
    public function test_remove_all_items($id, adapter $storage) {
        $basket = new basket($id, $storage);
        $basket->add([1, 2, 4, 3]);

        $basket->remove([2, 4, 3, 1]);
        $this->assertEquals([], $basket->load());
    }

    /**
     * @param $id
     * @param adapter $storage
     *
     * @dataProvider basket_data_provider
     */
    public function test_delete($id, adapter $storage) {
        $basket = new basket($id, $storage);
        $basket->add([1, 2, 4, 3]);

        $basket->delete();
        $this->assertEquals([], $basket->load());
    }

    /**
     * @param $id
     * @param adapter $storage
     *
     * @dataProvider basket_data_provider
     */
    public function test_replace($id, adapter $storage) {
        $basket = new basket($id, $storage);
        $basket->add([1, 2, 4, 3]);

        $basket->replace([6, 7, 8]);
        $this->assertEquals([6, 7, 8], $basket->load());
    }

    /**
     * @return array
     */
    public function basket_data_provider(): array {
        return [
            ['my_basket', new simple_adapter()],
            ['my_session_basket', new session_adapter('baskets')]
        ];
    }

}