<?php
/**
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_topic
 */
namespace totara_topic\resolver;

final class resolver_factory {
    /**
     * @var resolver|null
     */
    private static $default_resolver;

    /**
     * resolver_factory constructor.
     */
    private function __construct() {
        // preventing this class from being constructed
    }

    /**
     * @param resolver $resolver
     * @return void
     */
    public static function phpunit_set_default_resolver(resolver $resolver): void {
        if (!defined('PHPUNIT_TEST') || !PHPUNIT_TEST) {
            debugging("Default resolver should not be set else where except the unit tests", DEBUG_DEVELOPER);
            return;
        }

        static::$default_resolver = $resolver;
    }

    /**
     * @return void
     */
    public static function phpunit_clear_resolver(): void {
        static::$default_resolver = null;
    }

    /**
     * @param string $component
     * @return resolver
     */
    public static function create_resolver(string $component): resolver {
        $resolver_class = "\\{$component}\\totara_topic\\topic_resolver";
        if (!class_exists($resolver_class)) {
            if (defined('PHPUNIT_TEST') && PHPUNIT_TEST && null !== static::$default_resolver) {
                return static::$default_resolver;
            }

            throw new \coding_exception("Cannot find the resolver of the component '{$component}'");
        }

        return new $resolver_class();
    }
}