<?php
/**
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

namespace core\performance_statistics;

use ReflectionClass;

/**
 * Any class implementing this can return performance related stats which
 * is collected for displaying performance information
 */
abstract class provider {
    
    /**
     * Returns the name of the component this provider belongs to.
     * By default this is the root namespace, i.e. core or totara_webapi
     *
     * @return string
     */
    public function get_component(): string {
        $reflection = new ReflectionClass($this);
        $namespace = $reflection->getNamespaceName();
        $parts = explode('\\', $namespace);
        return array_shift($parts);
    }

    /**
     * Returns key for this provider used in the performance result.
     * By default this is the classname
     *
     * @return string
     */
    public function get_key(): string {
        $reflection = new ReflectionClass($this);
        return $reflection->getShortName();
    }

    /**
     * Return data for performance
     *
     * @return mixed|null
     */
    abstract public function get_data();

}