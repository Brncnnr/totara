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
 * @group orm
 */

namespace core\orm\query\sql;

use core\orm\query\builder_base;

/**
 * Class where
 *
 * Generates where SQL statement from conditions stored on the builder
 *
 * @internal This is not meant to be used as external API
 * @package core\orm\query\sql
 */
final class where extends sql {

    /**
     * Generate SQL for the query itself. Woo-hoo.
     *
     * @param bool|null $parenthesis
     * @return array [SQL, [Params]]
     */
    public function build(?bool $parenthesis = null): array {
        $conditions = [];
        $params = [];

        // First is needed to keep aggregation keywords correct.
        $first = true;

        foreach ($this->properties->conditions as $condition) {
            if ($condition instanceof builder_base) {
                [$sql, $local_params] = where::from_builder($condition)->build();
                $sql = "($sql)";
            } else {
                [$sql, $local_params] = $condition->where_sql();
            }

            if (!$first) {
                $conditions[] = $this->agg_to_sql($condition->get_aggregation());
            }

            $conditions[] = $sql;

            $params = array_merge($params, $local_params);

            $first = false;
        }

        // To follow a predictable pattern if no conditions have been supplied the query would have one WHERE 1=1
        if (empty($conditions)) {
            $conditions[] = '1 = 1';
        }

        $sql = implode(' ', $conditions);

        // Use 'nested' option if parenthesis isn't defined. If it is, use it to wrap sql
        if ((is_null($parenthesis) && $this->properties->nested) || (!is_null($parenthesis) && $parenthesis)) {
            $sql = "({$sql})";
        }

        return [$sql, $params];
    }

}