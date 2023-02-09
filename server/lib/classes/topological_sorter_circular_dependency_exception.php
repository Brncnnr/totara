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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @package core
 */

namespace core;

/**
 * Circular dependency exception.
 *
 * This exception is thrown by topological_sorter when a cycle is detected in the dependency graph.
 */
class topological_sorter_circular_dependency_exception extends \moodle_exception {
    /**
     * @var string The key of the node we were processing.
     */
    public $current_node;

    /**
     * @var string|null The key of the node depending on the node we're processing.
     */
    public $dependent_node;

    /**
     * Constructs a new exception
     *
     * @param string $current_node The key of the node we were processing.
     * @param string $dependent_node The key of the node depending on the node we're processing.
     */
    public function __construct(string $current_node, string $dependent_node = null) {
        $multiple = $dependent_node && $current_node != $dependent_node;
        parent::__construct(
            $multiple ? 'circulardependency' : 'circulardependencyin',
            'error',
            '',
            ['current_node' => $current_node, 'dependent_node' => $dependent_node ?? $current_node]
        );
        $this->current_node = $current_node;
        $this->dependent_node = $dependent_node;
    }
}
