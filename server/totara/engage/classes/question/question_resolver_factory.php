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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage\question;

final class question_resolver_factory {
    /**
     * @var question_resolver|null
     */
    private static $defaultresolver;

    /**
     * question_resolver_factory constructor.
     * Prevent any instantiation on this class.
     */
    private function __construct() {
    }

    /**
     * @return void
     */
    public static function clear_default_resolver(): void {
        static::$defaultresolver = null;
    }

    /**
     * @param question_resolver $resolver
     * @return void
     */
    public static function phpunit_set_default_resolver(question_resolver $resolver): void {
        if (isset(static::$defaultresolver)) {
            debugging("Default resolver had already been set", DEBUG_DEVELOPER);
            return;
        }

        if (!defined('PHPUNIT_TEST') || !PHPUNIT_TEST) {
            debugging("The environment is not a unit test");
            return;
        }

        static::$defaultresolver = $resolver;
    }

    /**
     * @param string $component
     * @return question_resolver
     */
    public static function get_resolver(string $component): question_resolver {
        $classes = \core_component::get_namespace_classes(
            'totara_engage\\question',
            question_resolver::class,
            $component
        );

        if (empty($classes)) {
            if (isset(static::$defaultresolver) && static::$defaultresolver->get_component() == $component) {
                return static::$defaultresolver;
            }

            throw new \coding_exception("No question resolver found for component '{$component}'");
        } else if (1 != count($classes)) {
            debugging("There are more than one resolver class for the component '{$component}'", DEBUG_DEVELOPER);
        }

        $cls = reset($classes);
        return new $cls();
    }
}