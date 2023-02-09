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

use core\dml\sql;

defined('MOODLE_INTERNAL') || die();

/**
 * Cache class
 *
 * This is a decorator class that can be wrapped around another resolver that will cache the data
 * passing through it.
 *
 * @internal
 */
final class cache implements resolver {

    /**
     * The resolver this cache instance is decorating.
     * @var resolver
     */
    private $resolver;

    /**
     * The cache that is used to store data.
     * @var \cache_loader
     */
    private $cache;

    /**
     * Constructor
     *
     * @param resolver $resolver
     * @param \cache_loader $cache
     */
    public function __construct(resolver $resolver, \cache_loader $cache) {
        $this->resolver = $resolver;
        $this->cache = $cache;
    }

    /**
     * Returns the separator to use between table and field names in the query.
     *
     * This is to keep the queries compatible with report builder caching.
     * This is also a pass through to the resolver.
     *
     * @return string
     */
    public function sql_separator(): string {
        return $this->resolver->sql_separator();
    }

    /**
     * Sets the separator to use between table and field in queries.
     *
     * This is to keep the queries compatible with report builder caching.
     * This is also a pass through to the resolver.
     *
     * @param string $separator
     */
    public function set_sql_separator(string $separator) {
        $this->resolver->set_sql_separator($separator);
    }

    /**
     * Returns true if admin orientated quick checks will be made.
     * True by default.
     * This is also a pass through to the resolver.
     * @return bool
     */
    public function skip_checks_for_admin(): bool {
        return $this->resolver->skip_checks_for_admin();
    }

    /**
     * Sets the quick admin check value.
     * True by default.
     * This is also a pass through to the resolver.
     * @param bool $value
     */
    public function set_skip_checks_for_admin(bool $value) {
        $this->resolver->set_skip_checks_for_admin($value);
    }

    /**
     * Returns the map instance for this resolver.
     * This is also a pass through to the resolver.
     * @return map
     */
    public function map(): map {
        return $this->resolver->map();
    }

    /**
     * Returns the name of the field that stores the visible value in the database.
     * This is also a pass through to the resolver.
     * @return string
     */
    public function sql_field_visible(): string {
        return $this->resolver->sql_field_visible();
    }

    /**
     * Returns an SQL snippet to use to limit a query to the items a user can see.
     * This is done view a where clause.
     * @param int $userid
     * @param string $tablealias
     * @return array An array containing two items, SQL and params (named)
     */
    public function sql_where_visible(int $userid, string $tablealias) : sql {
        return $this->resolver->sql_where_visible($userid, $tablealias);
    }

    /**
     * Returns a cache key.
     * @param string $name
     * @param array $bits
     * @return string
     */
    private function get_key($name, array $bits = []) {
        $key = get_class($this->resolver) . '_' . join('_' , $bits) . '_' . ($this->skip_checks_for_admin() ? '1' : '0');
        return sha1($key) . '_' . $name;
    }

    /**
     * Returns a list of categories and for each the count of the visible items within that category.
     * @param int $userid
     * @return array
     */
    public function get_visible_counts_for_all_categories(int $userid): array {
        $key = $this->get_key('all_categories', [$userid]);
        $result = $this->cache->get($key);
        if ($result === false) {
            $result = $this->resolver->get_visible_counts_for_all_categories($userid);
            $this->cache->set($key, $result);
        }
        return $result;
    }

    /**
     * Returns a list of items that are visible to the user within the given category.
     * @param int $categoryid
     * @param int $userid
     * @param array $fields
     * @return array
     */
    public function get_visible_in_category(int $categoryid, int $userid, array $fields = ['*']): array {
        $key = $this->get_key('all_categories', [$userid]);
        if ($this->cache->has($key)) {
            $counts = $this->get_visible_counts_for_all_categories($userid);
            if (empty($counts[$categoryid])) {
                return [];
            }
        }

        return $this->resolver->get_visible_in_category($categoryid, $userid, $fields);
    }

}