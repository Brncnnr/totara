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
use totara_core\basket\basket_interface;
use totara_core\basket\basket_limiter;
use totara_core\basket\storage\simple_adapter;

defined('MOODLE_INTERNAL') || die();

class totara_core_basket_limiter_testcase extends advanced_testcase {

    /**
     * @var basket_interface
     */
    private $basket;

    protected function setUp(): void {
        parent::setUp();
        $this->basket = new basket('my_basket', new simple_adapter());
    }

    protected function tearDown(): void {
        parent::tearDown();
        $this->basket = null;
    }

    /**
     * @param int $limit the limit to set
     * @param array $basket_items the items pre existing in basket
     * @param array $items_to_add the items which should be added
     * @param $result true if limit is reached, false if not
     *
     * @dataProvider limiter_data_provider
     */
    public function test_limit(int $limit, array $basket_items, array $items_to_add, $result) {
        $limiter = new basket_limiter($this->basket, $limit);

        if (!empty($basket_items)) {
            $this->basket->add($basket_items);
        }
        $this->assertSame($result, $limiter->is_limit_reached($items_to_add));
    }

    /**
     * @return array
     */
    public function limiter_data_provider(): array {
        return [
            // limit not reached
            [10, [1, 2, 3, 4], [5, 6, 7], false],
            // limit reached
            [5, [], [1, 2, 3, 4, 5, 6], true],
            [5, [1, 2, 3], [4, 5, 6], true],
            // unlimited
            [0, [1, 2, 3], [4, 5, 6, 7, 8, 9, 10, 11, 12, 13], false]
        ];
    }

}