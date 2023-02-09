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

/**
 * Class filter
 * Base class for the filter to extend
 */
abstract class filter {

    /**
     * Filter identifier
     *
     * @var string
     */
    protected $id;

    /**
     * Link to the entity repository builder
     *
     * @var builder
     */
    protected $builder;

    /**
     * Filter options
     *
     * @var array
     */
    protected $params;

    /**
     * Filter value to compare to
     *
     * @var mixed
     */
    protected $value;

    /**
     * Entity class name
     *
     * @var string
     */
    protected $entity_class;

    /**
     * A flag whether a filter should trim value first
     *
     * @var bool
     */
    protected $trim = true;

    /**
     * filter constructor.
     * @param mixed ...$params Filter options to pass
     */
    public function __construct(...$params) {
        $this->id = uniqid('filters_');

        if (!empty($params)) {
            $this->set_params(...$params);
        }
    }

    /**
     * Pass filter options
     *
     * @param mixed ...$params
     * @return $this
     */
    public function set_params(...$params): self {
        $this->params = $params;
        return $this;
    }

    /**
     * Set value to filter by, is by default automatically trimmed
     *
     * @param mixed $value Value to filter by
     * @return $this
     */
    public function set_value($value): self {
        if (is_string($value) && $this->trim) {
            $value = trim($value);
        }
        $this->value = $value;
        return $this;
    }

    /**
     * Disabled default trimming of strings
     *
     * @return $this
     */
    public function dont_trim() {
        $this->trim = false;
        return $this;
    }

    /**
     * Apply filter
     *
     * @return void
     */
    abstract public function apply();

    /**
     * Set builder instance
     *
     * @param builder $builder
     * @return $this
     */
    public function set_builder(builder $builder) {
        $this->builder = $builder;
        return $this;
    }

    /**
     * Set entity class name so it can be referred to from a filter
     *
     * @param string $class
     * @return $this
     */
    public function set_entity_class(string $class) {
        $this->entity_class = $class;

        return $this;
    }

}
