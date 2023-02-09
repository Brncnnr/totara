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
use totara_core\basket\basket_limit_exception;
use totara_core\basket\session_basket;
use totara_core\basket\storage\session_adapter;

defined('MOODLE_INTERNAL') || die();

/**
 * This class tests specific functionality of the session basket
 */
class totara_core_session_basket_testcase extends advanced_testcase {

    private const BASKETS_KEY = 'baskets';

    protected function setUp(): void {
        parent::setUp();
    }

    public function test_session_basket_add() {
        $basket = new session_basket('mynewbasket');
        $basket->add([1, 2, 3, 4, 5]);

        $this->assertEquals([1, 2, 3, 4, 5], $basket->load());
    }

    public function test_session_basket_replace() {
        $basket = new session_basket('mynewbasket');
        $basket->add([1, 2, 3, 4, 5]);

        $this->assertEquals([1, 2, 3, 4, 5], $basket->load());

        $basket->replace([6, 7, 8]);

        $this->assertEquals([6, 7, 8], $basket->load());
    }

    public function test_session_basket_remove() {
        $basket = new session_basket('mynewbasket');
        $basket->add([1, 2, 3, 4, 5]);
        $this->assertEquals([1, 2, 3, 4, 5], $basket->load());

        $basket->remove([2, 3]);
        $this->assertEquals([1, 4, 5], $basket->load());
    }

    public function test_session_basket_delete() {
        $basket = new session_basket('mynewbasket');
        $basket->add([1, 2, 3, 4, 5]);
        $this->assertEquals([1, 2, 3, 4, 5], $basket->load());

        $basket->delete();
        $this->assertEquals([], $basket->load());
    }

    public function test_session_basket_add_with_limit_set() {
        set_config('basket_item_limit', '7');

        $basket = new session_basket('mynewbasket');
        $basket->add([1, 2, 3, 4, 5]);

        $this->expectException(basket_limit_exception::class);
        $basket->add([6, 7, 8, 9, 10]);
    }

    public function test_session_basket_replace_with_limit_set() {
        set_config('basket_item_limit', '7');

        $basket = new session_basket('mynewbasket');
        $basket->add([1, 2, 3, 4, 5]);

        $basket->replace([6, 7, 8, 9, 10]);

        $this->expectException(basket_limit_exception::class);
        $basket->replace([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);
    }

    public function test_session_basket_add_with_no_limit_set() {
        set_config('basket_item_limit', '0');

        $basket = new session_basket('mynewbasket');
        $basket->add([1, 2, 3, 4, 5]);
        $basket->add([6, 7, 8, 9, 10]);

        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9, 10], $basket->load());
    }

    public function test_session_basket_chained_adds_with_limit_set() {
        set_config('basket_item_limit', '7');

        $basket = new session_basket('mynewbasket');
        $this->expectException(basket_limit_exception::class);
        $basket->add([1, 2, 3])->add([4, 5])->add([6, 7, 8]);
    }

    public function test_with_session_storage_factory_method() {
        $basket = $this->create_basket_instance_with_session_storage('my_session_basket');
        $this->assertInstanceOf(basket::class, $basket);
        $this->assertEquals('my_session_basket', $basket->get_key());
    }

    public function test_with_session_storage_add() {
        global $SESSION;

        $this->assertObjectNotHasAttribute(self::BASKETS_KEY, $SESSION);

        $basket1 = $this->create_basket_instance_with_session_storage('my_session_basket');
        $basket1->add([1, 2, 3, 4, 5]);

        // have two baskets in the session
        $basket2 = $this->create_basket_instance_with_session_storage('my_other_session_basket');
        $basket2->add([9, 8, 7, 6]);

        $this->assertObjectHasAttribute(self::BASKETS_KEY, $SESSION);

        $this->assertArrayHasKey('my_session_basket', $SESSION->{self::BASKETS_KEY});
        $this->assertEquals([1, 2, 3, 4, 5], $SESSION->{self::BASKETS_KEY}['my_session_basket']);

        $this->assertArrayHasKey('my_other_session_basket', $SESSION->{self::BASKETS_KEY});
        $this->assertEquals([6, 7, 8, 9], $SESSION->{self::BASKETS_KEY}['my_other_session_basket']);
    }

    public function test_with_session_storage_remove() {
        global $SESSION;

        $basket1 = $this->create_basket_instance_with_session_storage('my_session_basket');
        $basket1->add([1, 2, 3, 4, 5]);

        $basket2 = $this->create_basket_instance_with_session_storage('my_other_session_basket');
        $basket2->add([9, 8, 7, 6]);

        // remove part of the entries
        $basket1->remove([2, 4]);
        $this->assertEquals([1, 3, 5], $SESSION->{self::BASKETS_KEY}['my_session_basket']);

        // remove the rest
        $basket1->remove([1, 3, 5]);
        // when no items are left the basket key is removed as well
        $this->assertArrayNotHasKey('my_session_basket', $SESSION->{self::BASKETS_KEY});

        // the other basket is untouched
        $this->assertEquals([6, 7, 8, 9], $SESSION->{self::BASKETS_KEY}['my_other_session_basket']);
    }

    public function test_with_session_storage_delete() {
        global $SESSION;

        $basket1 = $this->create_basket_instance_with_session_storage('my_session_basket');
        $basket1->add([1, 2, 3, 4, 5]);

        $basket2 = $this->create_basket_instance_with_session_storage('my_other_session_basket');
        $basket2->add([9, 8, 7, 6]);

        $this->assertEquals([1, 2, 3, 4, 5], $SESSION->{self::BASKETS_KEY}['my_session_basket']);
        $basket1->delete();
        // when we delete the whole basket the key does not exist anymore
        $this->assertArrayNotHasKey('my_session_basket', $SESSION->{self::BASKETS_KEY});

        // the other basket is untouched
        $this->assertEquals([6, 7, 8, 9], $SESSION->{self::BASKETS_KEY}['my_other_session_basket']);

        // once all baskets are deleted we drop the main key as well
        // to keep the session clean
        $basket2->delete();
        $this->assertObjectNotHasAttribute(self::BASKETS_KEY, $SESSION);
    }

    public function test_with_session_storage_persistence() {
        $basket = $this->create_basket_instance_with_session_storage('my_session_basket');
        $basket->add([1, 2, 3, 4, 5]);

        // load basket into new object
        $basket_reloaded = $this->create_basket_instance_with_session_storage('my_session_basket');
        $this->assertEquals($basket->load(), $basket_reloaded->load());
    }

    private function create_basket_instance_with_session_storage(string $id): basket {
        $storage = new session_adapter(self::BASKETS_KEY);
        return new basket($id, $storage);
    }


}