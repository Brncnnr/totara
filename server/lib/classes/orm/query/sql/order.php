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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package core
 * @group orm
 */

namespace core\orm\query\sql;


use core\orm\query\raw_field;

/**
 * Class order
 *
 * Generate ORDER_BY statement using order::class fields stored on the builder
 *
 * @internal This is not meant to be used as external API
 * @package core\orm\query\sql
 */
final class order extends sql {

    /**
     * Generate SQL for the order in the galaxy.
     *
     * @param bool $count_only
     * @return array [SQL, [Params]]
     */
    public function build(bool $count_only = false): array {
        $sql = '';
        $params = [];

        // Getting order by, it's omitted in unions.
        if (!$this->properties->united && !$count_only && !empty($this->properties->orders)) {
            $sql = "ORDER BY " . implode(', ', $this->properties->orders);

            $params = array_reduce($this->properties->orders, function ($params, raw_field $order) {
                return array_merge($params, $order->get_params());
            }, []);
        }

        return [$sql, $params];
    }

}