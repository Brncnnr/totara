<?php
/*
 * This file is part of Totara LMS
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\local\visibility;

defined('MOODLE_INTERNAL') || die();

use core\dml\sql;

/**
 * Resolver interface.
 */
interface resolver {

    /**
     * Sets the separator to use when constructing SQL snippets.
     *
     * This is used by report builder report caching.
     * It should not be used by anything else. Ever!
     *
     * @param string $separator The separator to use, either '.' or '_'.
     */
    public function set_sql_separator(string $separator);

    /**
     * Returns the SQL separator to use when constructing SQL snippets.
     *
     * By default this needs to return '.'.
     *
     * This is used by report builder report caching.
     * It should not be used by anything else. Ever!
     *
     * @return string
     */
    public function sql_separator(): string;

    /**
     * Sets whether checks should be skipped for admin users or not.
     *
     * @param bool $value
     */
    public function set_skip_checks_for_admin(bool $value);

    /**
     * Returns true if checks can be skipped for the admin user.
     *
     * By default this needs to return true;
     *
     * @return bool
     */
    public function skip_checks_for_admin(): bool;

    /**
     * Returns the map for this resolver.
     *
     * @return map
     */
    public function map(): map;

    /**
     * Returns the SQL field that this resolver looks at to get the visibility setting of an item.
     *
     * With traditional visibility on this is normally 'visible'.
     * When audience based visibility is on this is normally 'audiencevisible'.
     *
     * @return string
     */
    public function sql_field_visible(): string;

    /**
     * Returns an SQL snippet that can be used to reduce SQL results for items with managed visibility to just those that
     * the given user can see.
     *
     * This is compatible with the old {@see totara_visibility_where()} and in fact that now uses this.
     *
     * If you are using this resolver with report builder and its caching system then you will need to use
     * {@see $this->set_sql_separator()} to set the appropriate separator.
     *
     * @param int $userid
     * @param string $tablealias The item table alias, this is normally one of course, c, prog, p
     * @return array An array with two items, string:SQL, array:params
     */
    public function sql_where_visible(int $userid, string $tablealias) : sql;

    /**
     * Returns an array containing category id's and a count of the items the given user can see within it.
     *
     * @param int $userid
     * @return int[] The key is the category id, the value is the count of visible items.
     */
    public function get_visible_counts_for_all_categories(int $userid): array;

    /**
     * Returns an array of items visible to the given user within the given category.
     *
     * @param int $categoryid
     * @param int $userid
     * @param array $fields The fields to fetch for the item.
     * @return array The resulting items.
     */
    public function get_visible_in_category(int $categoryid, int $userid, array $fields = ['*']): array;
}