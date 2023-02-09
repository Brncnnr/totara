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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package engage_survey
 */

namespace totara_engage\query\provider;

use core\orm\query\builder;
use totara_engage\query\query;

class helper {

    /**
     * @return array
     */
    public static function get_providers(): array {
        $cache = \cache::make('totara_engage', 'query_providers');
        $key = 'totara_engage_query_providers';

        // Check if we have it cached.
        $providers = $cache->get($key);
        if (!empty($providers)) {
            return $providers;
        }

        $providers = \core_component::get_namespace_classes(
            'totara_engage\\query\\provider',
            queryable::class
        );

        $cache->set($key, $providers);
        return $providers;
    }

    /**
     * @param query $query
     * @return builder[]
     */
    public static function get_builders(query $query): array {
        $all = [];

        $classes = static::get_providers();

        foreach ($classes as $class) {
            // Skip abstract classes.
            $ref = new \ReflectionClass($class);
            if ($ref->isAbstract()) {
                continue;
            }

            // Get builder.
            /** @var queryable $instance */
            $instance = new $class();
            $builder = $instance->get_builder($query);

            if (empty($builder)) {
                continue;
            }

            $all[] = $builder;
        }

        return $all;
    }

    /**
     * @param bool $container
     * @return array
     */
    public static function get_resource_providers(bool $container = false): array {
        $providers = [];
        $classes = static::get_providers();

        foreach ($classes as $class) {
            /** @var queryable $instance */
            $instance = new $class();

            // If we are looking for containers.
            if (($instance instanceof container) === $container) {
                $providers[] = $instance;
            }
        }

        return $providers;
    }

}