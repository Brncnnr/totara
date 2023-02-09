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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package performelement_linked_review
 */

namespace performelement_linked_review;

use coding_exception;
use context;
use core\collection;
use core_component;

final class content_type_factory {

    /**
     * @var collection|string[]
     */
    private static $type_classes;

    private function __construct() {
        // Don't allow the construction of the factory.
    }

    /**
     * Return a collection of all content type classes in the system.
     * Note that content_type class name strings are returned, and not actual instances.
     *
     * @return string[]|content_type[]|collection
     */
    public static function get_all(): collection {
        if (!isset(static::$type_classes)) {
            static::$type_classes = collection::new(
                core_component::get_namespace_classes('performelement_linked_review', content_type::class)
            );
        }
        return static::$type_classes;
    }

    /**
     * Return a collection of content type classes that are enabled and can be displayed.
     * Note that content_type class name strings are returned, and not actual instances.
     *
     * @return string[]|content_type[]|collection
     */
    public static function get_all_enabled(): collection {
        return static::get_all()->filter(static function ($type) {
            return $type::is_enabled();
        });
    }

    /**
     * Factory method to get a content_type instance
     *
     * @param string $identifier
     * @param context $context
     * @return content_type
     */
    public static function get_from_identifier(string $identifier, context $context): content_type {
        $class_name = self::get_class_name_from_identifier($identifier);
        return new $class_name($context);
    }

    /**
     * Return the class name for the given content type identifier.
     * Note that the content_type class name is returned, and not an instance.
     *
     * @param string $identifier
     * @return string|content_type
     */
    public static function get_class_name_from_identifier(string $identifier): string {
        $type = static::get_all()
            ->filter(static function ($type) use ($identifier) {
                return $type::get_identifier() === $identifier;
            })
            ->first();

        if ($type) {
            return $type;
        }

        throw new coding_exception("Couldn't locate a review content type with the identifier '{$identifier}'");
    }

}
