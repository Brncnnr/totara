<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package performelement_aggregation
 */

namespace performelement_aggregation;


use FilesystemIterator;
use ReflectionClass;

/**
 * Class aggregation_calculation_plugin
 *
 * Base class for defining a type of aggregation calculation.
 *
 * @package performelement_aggregation
 */
abstract class calculation_method {

    /**
     * 'Cache' of available methods.
     *
     * @var null|calculation_method[]
     */
    private static $methods;
    
    /**
     * Load by method name
     *
     * @param string $method_name
     *
     * @return static
     */
    final public static function load_by_method(string $method_name): self {
        $method_class = static::get_method_class($method_name);

        if ($method_class === null) {
            throw new \coding_exception('Tried to load an unknown aggregation calculation method: ' . $method_class);
        }

        return new $method_class();
    }

    final public static function is_valid_method_name(string $method_name): bool {
        return static::get_method_class($method_name) !== null;
    }

    final protected static function get_method_class(string $method_name): ?string {
        $namespace = __NAMESPACE__ . '\\calculations';
        $method_class = "{$namespace}\\{$method_name}";

        if (!class_exists($method_class) || !is_subclass_of($method_class, self::class)) {
            return null;
        }

        return $method_class;
    }

    /**
     * Get the names of all aggregation calculation methods.
     *
     * @return calculation_method[]
     */
    final public static function get_aggregation_calculation_methods(): array {
        if (self::$methods !== null) {
            return self::$methods;
        }

        self::$methods = [];

        $namespace = __NAMESPACE__ . '\\calculations';
        $fulldir = __DIR__ . '/calculations';
        $items = new FilesystemIterator($fulldir, FilesystemIterator::KEY_AS_FILENAME | FilesystemIterator::SKIP_DOTS);

        foreach ($items as $item) {
            $method_name = $item->getBasename('.php');
            $classname = "{$namespace}\\{$method_name}";

            $rc = new ReflectionClass($classname);
            if ($rc->isSubclassOf(self::class)) {
                self::$methods[] = new $classname();
            }
        }

        usort(self::$methods, function (calculation_method $a, calculation_method $b) {
            return $a->get_label() <=> $b->get_label();
        });
        
        return self::$methods;
    }
    
    /**
     * Get method name, used as a key
     *
     * @return string
     */
    final public static function get_name(): string {
        $elements = explode('\\', static::class);
        return end($elements);
    }

    /**
     * Get name
     *
     * @return string
     */
    abstract public static function get_label(): string;

    /**
     * Perform the aggregation calculation
     *
     * @param mixed[] @values
     * @return mixed @result
     */
    abstract public function aggregate(array $values): float;

}
