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
use core\orm\query\field;

/**
 * Class like
 *
 * Like filter
 *
 * @method __construct(string|string[] $columns = null, string $pattern = '%[text]%', bool $case_sensitive = false, bool $raw_column = false)
 * @method $this set_params(string|string[] $columns, string $pattern = '%[text]%', bool $case_sensitive = false, bool $raw_column = false)
 */
class like extends filter {

    /**
     * Apply filter
     * Equal filter, adds a condition column like value
     */
    public function apply() {
        $cols = $this->params[0];
        $pattern = $this->params[1] ?? '%[text]%';
        $case_sensitive = $this->params[2] ?? false;
        $raw_column = $this->params[3] ?? false;

        if (!is_string($cols) && !is_array($cols)) {
            $cols = (string) $cols;
        }
        if (!is_string($pattern)) {
            throw new \coding_exception('pattern param for like filter needs to be a string');
        }
        if (!is_bool($case_sensitive)) {
            throw new \coding_exception('case_sensitive param for like filter needs to be boolean');
        }

        $value = str_replace('[text]', $this->builder->get_db()->sql_like_escape($this->value), $pattern);

        if (is_string($cols)) {
            $cols = [$cols];
        }

        $this->builder->where(
            function (builder $builder) use ($cols, $value, $case_sensitive, $raw_column) {
                foreach ($cols as $col) {
                    $builder->or_where($raw_column ? field::raw($col) : $col, $case_sensitive ? 'like_raw' : 'ilike_raw', $value);
                }
            }
        );
    }

}
