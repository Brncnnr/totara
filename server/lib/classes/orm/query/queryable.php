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

namespace core\orm\query;

/**
 * Interface queryable that defines that an object might be used in a building of where condition
 * 
 * @package core
 * @group orm
 */
interface queryable {

    /**
     * Get the SQL and Params and Limit for the database query
     *
     * @return array
     */
    public function where_sql(): array;

    /**
     * Get aggregation type
     *
     * @return bool
     */
    public function get_aggregation(): bool;

    /**
     * Set aggregation type
     *
     * @param bool $aggregation
     * @return queryable
     */
    public function set_aggregation(bool $aggregation): queryable;
}