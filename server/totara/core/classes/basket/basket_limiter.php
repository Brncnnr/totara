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
 */

namespace totara_core\basket;

defined('MOODLE_INTERNAL') || die();

/**
 * DEPRECATED
 *
 * Class which contains the logic to determine if a basket has hit a given limit.
 *
 * System wide default is set by constant DEFAULT_LIMIT, but can be overriden by passing a limit to the constructor.
 *
 * @deprecated since Totara 13
 */
class basket_limiter {

    const DEFAULT_LIMIT = 5000;

    /**
     * @var basket_interface
     */
    private $basket;

    private $limit = 0;

    /**
     * @param basket_interface $basket
     * @param int|null $limit optional, defaults to the limit defined in DEFAULT_LIMIT, 0 sets it to unlimited
     */
    public function __construct(basket_interface $basket, int $limit = null) {
        $this->basket = $basket;
        $this->limit = $limit ?? self::DEFAULT_LIMIT;
    }

    /**
     * @return int
     *
     * @deprecated since Totara 13
     */
    public function get_limit(): int {
        return $this->limit;
    }

    /**
     * checks if the limit is reached with theoretically adding the items passed to this function
     *
     * @param array $items_to_add
     * @return bool
     *
     * @deprecated since Totara 13
     */
    public function is_limit_reached(array $items_to_add = []): bool {
        if ($this->limit === 0) {
            return false;
        }

        return count($this->basket->load()) + count($items_to_add) > $this->limit;
    }

}