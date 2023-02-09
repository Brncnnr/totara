<?php
/**
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\exception\helper;

use coding_exception;

/**
 * Throw an exception in a common scenario.
 */
final class validation {
    /**
     * Throw coding_exception if $object is not the instance of any of the specified classes
     *
     * @param object $object
     * @param string[] $classes fully qualified path to classes
     * @param string $hint short description of the exception
     * @throws coding_exception
     */
    public static function instance_of_any($object, array $classes, string $hint = 'Wrong object is passed'): void {
        foreach ($classes as $class) {
            if ($object instanceof $class) {
                return;
            }
        }
        throw new coding_exception($hint);
    }

    /**
     * Throw coding_exception if $object is not the instance of the specified class
     *
     * @param object $object
     * @param string $class fully qualified path to a class
     * @param string $hint short description of the exception
     * @throws coding_exception
     */
    public static function instance_of($object, string $class, string $hint = 'Wrong object is passed'): void {
        self::instance_of_any($object, [$class], $hint);
    }

    /**
     * Throw coding_exception if $object is neither null nor the instance of the specified classes
     *
     * @param object|null $object
     * @param string $class fully qualified path to a class
     * @param string $hint short description of the exception
     * @throws coding_exception
     */
    public static function null_or_instance_of($object, string $class, string $hint = 'Wrong object is passed'): void {
        if ($object !== null) {
            self::instance_of($object, $class, $hint);
        }
    }
}
