<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Ning Zhou <ning.zhou@totaralearning.com>
 * @package core_orm
 */

namespace core\orm\entity\filter;

/**
 * Class not in
 *
 * Not in filter
 *
 * @method __construct(string|string[] $columns = null)
 * @method $this set_params(string|string[] $columns)
 */
class not_in extends filter {

    public function apply() {
        $col = $this->params[0];

        if (!is_string($col)) {
            throw new \coding_exception('cols param for in filter needs to be a string');
        }

        $values = $this->value;
        if (!is_array($values)) {
            $values = [$values];
        }
        $this->builder->where($col, '!=', $values);
    }
}