<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @package core
 */

namespace core\webapi\resolver;

use core\webapi\execution_context;

/**
 * A data holder for encapsulating the date passed to query, type and mutation resolvers.
 * This is used in the middleware layer. It enables us to more easily change what is passed
 * down through the middleware layer without changing the middleware api itself.
 * NOTE: the payload object is not shared across resolvers
 *
 * @package core\webapi\resolver
 */
class payload {

    /** @var array */
    protected $variables;

    /** @var execution_context */
    protected $execution_context;

    /**
     * @param $args
     * @param execution_context $execution_context
     */
    public function __construct($args, execution_context $execution_context) {
        $this->variables = $args;
        $this->execution_context = $execution_context;
    }

    /**
     * Shortcut function to instantiate a new payload instance
     *
     * @param $args
     * @param execution_context $execution_context
     * @return payload
     */
    public static function create($args, execution_context $execution_context): self {
        return new self($args, $execution_context);
    }

    /**
     * Get all variables passed to this operation
     *
     * @return array
     */
    public function get_variables() {
        return $this->variables;
    }

    /**
     * Returns the execution context as set by the GraphQL server
     *
     * @return execution_context
     */
    public function get_execution_context(): execution_context {
        return $this->execution_context;
    }

    /**
     * Returns a single variable, null if it does not exist
     *
     * @param string $name
     * @return mixed|null
     */
    public function get_variable(string $name) {
        return $this->variables[$name] ?? null;
    }

    /**
     * Sets a single variable by name, overwrites existing variables
     *
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    public function set_variable(string $name, $value) {
        return $this->variables[$name] = $value;
    }

    /**
     * Returns whether the variable with the given name exists
     *
     * @param string $name
     * @return bool
     */
    public function has_variable(string $name): bool {
        return array_key_exists($name, $this->variables);
    }

    /**
     * Magic method for easier access
     *
     * @param string $name
     * @return bool
     */
    public function __isset($name) {
        return isset($this->variables[$name]);
    }

    /**
     * Magic method for easier access
     *
     * @param string $name
     * @return mixed|null
     */
    public function __get($name) {
        return $this->get_variable($name);
    }

    /**
     * Magic method for easier access
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value) {
        $this->set_variable($name, $value);
    }

}