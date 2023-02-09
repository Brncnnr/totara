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

/**
 * Trait has_filters
 *
 * filter support for entities
 */
trait has_filters {

    /**
     * @var filter[]
     */
    protected $filters = [];

    /**
     * A flag whether filters have already been applied
     *
     * @var bool
     */
    protected $filters_applied = false;

    /**
     * Get default list of filters
     *
     * @return array
     */
    protected function get_default_filters(): array {
        // This can be overridden by the entity to define it's default filters
        return [];
    }

    /**
     * Sets the actual filter and the values in one go
     *
     * @param mixed $name can be either a filter key, a filter class name or a filter instance
     * @param mixed $value not needed if a filter object is passed for name
     * @return $this
     */
    public function set_filter($name, $value = null) {
        $default_filters = $this->get_default_filters();
        $filter = null;

        switch (true) {
            case is_string($name) && isset($default_filters[$name]):
                // there's a default for the key so just use it
                $filter = $default_filters[$name];
                break;
            case $name instanceof filter:
                // it's a pre-set filter object, just pass it along
                $filter = $name;
                break;
            default:
                $filter = null;
                break;
        }

        if (!empty($filter)) {
            $filter->set_entity_class($this->entity_classname);
            $filter->set_builder($this->builder);
            if ($value !== null) {
                $filter->set_value($value);
            }
            $this->filters[] = $filter;
        }

        return $this;
    }

    /**
     * Apply filters to the builder
     *
     * @return $this
     */
    public function apply_filters() {
        if ($this->filters_applied) {
            return $this;
        }

        foreach ($this->filters as $filter) {
            $filter->apply();
        }

        $this->filters_applied = true;

        return $this;
    }

    /**
     * Set an array of filters
     *
     * @param array $filters could be an array of ['filter_name' => 'value'] (if filter_name is not defined it will be just ignored)
     *                       or it could be an array of [filter::class => 'value']
     *                       or it could be an array of filter objects
     * @return $this
     */
    public function set_filters(array $filters) {
        $this->filters = [];
        foreach ($filters as $name => $value) {
            if ($value instanceof filter) {
                $this->set_filter($value);
            } else {
                $this->set_filter($name, $value);
            }
        }
        return $this;
    }

    /**
     * Return filters that have been set on the entity
     *
     * @return array
     */
    public function get_filters(): array {
        return $this->filters;
    }

}
