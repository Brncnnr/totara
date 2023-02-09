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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core_orm
 */

namespace core\orm\entity\filter;

use core\orm\query\builder;

/**
 * Class equal
 * Equal filter, adds a condition column = value
 *
 * @method __construct(string|string[] $columns = null)
 * @method $this set_params(string|string[] $columns)
 */
class equal extends filter {

    /**
     * Apply filter
     */
    public function apply() {
        $cols = $this->params[0];

        if (!is_string($cols) && !is_array($cols)) {
            $cols = (string) $cols;
        }

        if (is_string($cols)) {
            $cols = [$cols];
        }

        $this->builder->where(
            function (builder $builder) use ($cols) {
                foreach ($cols as $col) {
                    $builder->or_where($col, '=', $this->value);
                }
            }
        );
    }

}