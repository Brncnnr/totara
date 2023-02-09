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

use core\orm\collection;
use core\orm\lazy_collection;
use core\orm\paginator;
use stdClass;

/**
 * Builder actions represent final actions where the query
 * built with the query builder is finalized and executed.
 */
interface interacts_with_database {

    /**
     * Count the number of results in the query
     *
     * @return int
     */
    public function count(): int;

    /**
     *  Delete record(s) from the database
     *
     * @return $this
     */
    public function delete();

    /**
     * Fetch raw records from the database
     *
     * @return array[]
     */
    public function fetch(): array;

    /**
     * Fetch raw records from the database and at the same time returning the count.
     * This is optimised to run only one query
     *
     * @return array|[array records, int count]
     */
    public function fetch_counted(): array;

    /**
     * Fetch raw records from the database
     *
     * @return lazy_collection
     */
    public function fetch_recordset();

    /**
     * Find item by ID
     *
     * @param int $id
     * @return array|null
     */
    public function find(int $id);

    /**
     * Same as find() but it throws an exception when the record does not exist
     *
     * @param int $id
     * @return array|null
     */
    public function find_or_fail(int $id);

    /**
     * Return the first item matching the search criteria
     *
     * @param bool $strict Fail if not found
     * @return array|null
     */
    public function first(bool $strict = false);

    /**
     * Return the first item matching the search criteria or throw an Exception if not found
     *
     * @return array|null
     */
    public function first_or_fail();

    /**
     * Get exactly one record from the database
     *
     * @param bool $strict Blow up if a record not found
     * @return array|stdClass|null
     */
    public function one(bool $strict = false);

    /**
     * Get items from the database
     *
     * @return collection
     */
    public function get(): collection;

    /**
     * Get items from the database as a lazy loading collection
     *
     * @return lazy_collection
     */
    public function get_lazy(): lazy_collection;

    /**
     * Create a new record in the database
     *
     * @param array|object $attributes Data object
     * @return int
     */
    public function insert($attributes): int;

    /**
     * Return simple paginated results
     *
     * @param int $page Page
     * @return paginator
     */
    public function load_more(int $page): paginator;

    /**
     * Return paginated results
     *
     * @param int $page Page
     * @param int $per_page
     * @return paginator
     */
    public function paginate(int $page = 1, int $per_page = 0): paginator;

    /**
     * Updates attributes of all rows affected by the query
     *
     * @param $attributes
     * @return $this
     */
    public function update($attributes);

    /**
     * Update a single record based on the supplied id attribute
     *
     * @param stdClass|array $record Record array\object, must contain id of the record updated
     * @return $this
     */
    public function update_record($record);

    /**
     * Retrieve a value of a single column
     *
     * @param string|field $column Column name to select
     * @param bool $strict Throw an exception if not found
     * @return string|null
     */
    public function value(string $column, bool $strict = false);

    /**
     * Return whether record(s) matching given where conditions exist
     *
     * @return bool
     */
    public function exists(): bool;

    /**
     * Return whether record(s) matching given where conditions does not exist
     *
     * @return bool
     */
    public function does_not_exist(): bool;

}
