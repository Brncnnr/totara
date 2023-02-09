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
 * @package core
 */

namespace core\orm\entity\filter;

use totara_core\basket\basket_interface;
use totara_core\basket\session_basket;

/**
 * DEPRECATED
 *
 * Filters for all ids in the given basket, used for showing selected items in the interface
 *
 * @package core\entity\filters
 *
 * @deprecated since Totara 13
 */
class basket extends filter {
    /**
     * @deprecated since Totara 13
     */
    public function apply() {
        $val = $this->value;
        if ($val instanceof basket_interface) {
            $basket = $val;
        } else {
            $basket = new session_basket($val);
        }
        // Do not ignore empty IDs this is an unexpected behaviour
        $this->builder->where('id', $this->sanitise_ids($basket->load()));
    }

    /**
     * Sanitise array of ids, filter out non-numeric, negative values and making it unique
     *
     * @param $ids
     * @return array
     */
    private function sanitise_ids(array $ids): array {
        $ids = array_filter(
            $ids,
            function ($id) {
                return is_numeric($id) && $id > 0;
            }
        );
        return array_unique(array_map('intval', $ids));
    }

}