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
 * Implementation of a topological sort
 *
 * This is useful to determine ordering for dependencies, etc.
 *
 * More info: https://en.wikipedia.org/wiki/Topological_sorting
 */
class topological_sorter {
    /**
     * @var array Map of node keys to their dependencies.
     * This is the dependency graph represented as an adjacency list.
     */
    protected $nodes = [];

    /**
     * @var array Sorted list of node keys
     */
    private $sorted_nodes;

    /**
     * @var array Map used to store node state
     */
    private $node_state_map;

    /**
     * Add a node to the graph with dependencies
     *
     * @param string $node_key
     * @param string[] $deps
     */
    public function add(string $node_key, array $deps = []) {
        $this->nodes[$node_key] = $deps;
    }

    public function has(string $node_key) {
        return array_key_exists($node_key, $this->nodes);
    }

    /**
     * Sort the graph and return a list of node keys
     *
     * @return string[]
     */
    public function sort(): array {
        $this->node_state_map = [];
        $this->sorted_nodes = [];
        foreach ($this->nodes as $node_key => $deps) {
            $this->visit($node_key);
        }
        return $this->sorted_nodes;
    }

    /**
     * Visitor implementing depth-first search topological sort
     *
     * @param string $node_key
     * @param string $dependent_node_key
     */
    private function visit(string $node_key, string $dependent_node_key = null) {
        if ($this->is_node_processed($node_key)) {
            return;
        }
        if ($this->is_node_processing($node_key)) {
            // we're already processing this dependency, so there must be a cycle in the graph
            throw new topological_sorter_circular_dependency_exception($node_key, $dependent_node_key);
        }

        $this->mark_node_as_processing($node_key);

        // process every dependency
        if (isset($this->nodes[$node_key])) {
            foreach ($this->nodes[$node_key] as $dep_key) {
                $this->visit($dep_key, $node_key);
            }
        }

        $this->mark_node_as_processed($node_key);

        // all of our dependencies, and their dependencies, and so on have been added at this point, so add ourselves
        $this->sorted_nodes[] = $node_key;
    }

    /**
     * Mark the provided node as currently processing
     *
     * @param string $node_key Node key
     */
    private function mark_node_as_processing(string $node_key) {
        $this->node_state_map[$node_key] = false;
    }

    /**
     * Mark the provided node as finished processing
     *
     * @param string $node_key Node key
     */
    private function mark_node_as_processed(string $node_key) {
        $this->node_state_map[$node_key] = true;
    }

    /**
     * Check if the provided node is currently processing
     *
     * @param string $node_key Node key
     * @return bool
     */
    private function is_node_processing(string $node_key) {
        return isset($this->node_state_map[$node_key]) && $this->node_state_map[$node_key] === false;
    }

    /**
     * Check if the provided node has finished processing
     *
     * @param string $node_key Node key
     * @return bool
     */
    private function is_node_processed(string $node_key) {
        return isset($this->node_state_map[$node_key]) && $this->node_state_map[$node_key] === true;
    }
}
