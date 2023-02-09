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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_competency
 */

namespace pathway_manual\data_providers;

use core\orm\collection;
use core\orm\entity\repository;

abstract class provider {

    /**
     * Array of filters to apply when fetching the data
     *
     * @var array
     */
    protected $filters = [];

    /**
     * Return whether data has been fetched
     *
     * @var bool
     */
    protected $fetched = false;

    /**
     * @var collection
     */
    protected $items;

    /**
     * Get the names of the filters that we want to display options of.
     *
     * @return string[]
     */
    protected static function get_enabled_filter_options(): array {
        return [];
    }

    /**
     * Get the filter options available for the filters enabled.
     *
     * @return array[] Array of filter name => filter options array
     */
    final protected function fetch_filter_options() {
        $this->fetch();

        $filter_options = [];

        foreach (static::get_enabled_filter_options() as $filter) {
            if (method_exists($this, 'get_' . $filter . '_filter_options')) {
                $filter_options[$filter] = $this->{'get_' . $filter . '_filter_options'}();
            } else {
                throw new \coding_exception("Filtering by '{$filter}' is currently not supported");
            }
        }

        return $filter_options;
    }

    /**
     * Add filters for this provider.
     *
     * @param array $filters
     * @return $this
     */
    final public function add_filters(array $filters) {
        $this->filters = array_merge($this->filters, $filters);

        return $this;
    }

    /**
     * Apply filters to a given repository.
     *
     * @param repository $repository Repository to apply filters
     * @return $this
     */
    final protected function apply_filters(repository $repository) {
        foreach ($this->filters as $key => $value) {
            if (is_null($value)) {
                continue;
            } else if (method_exists($this, 'filter_by_' . $key)) {
                $this->{'filter_by_' . $key}($repository, $value);
            } else {
                throw new \coding_exception("Filtering by '{$key}' is currently not supported");
            }
        }

        return $this;
    }

    /**
     * Run the ORM query and mark the data provider as already fetched.
     */
    final protected function fetch(): self {
        if (!$this->fetched) {
            $this->items = $this->fetch_from_query();
            $this->fetched = true;
        }

        return $this;
    }

    /**
     * Build the base ORM query using the relevant repository.
     *
     * @return repository
     */
    abstract protected function build_query(): repository;

    /**
     * Run the build ORM query.
     *
     * @return collection
     */
    protected function fetch_from_query(): collection {
        return $this
            ->build_query()
            ->get();
    }

    /**
     * Get the queries items.
     *
     * @return mixed
     */
    abstract public function get();

    /**
     * Count the queried items.
     *
     * @return int
     */
    public function count(): int {
        return $this->fetch()->items->count();
    }

}
