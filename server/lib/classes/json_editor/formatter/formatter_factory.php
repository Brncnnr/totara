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
 * @package core
 */
namespace core\json_editor\formatter;

/**
 * A factory class that help to create the formatter base on the
 * front-end framework/component.
 */
final class formatter_factory {
    /**
     * formatter_creator constructor.
     * Preventing this class from construction.
     */
    private function __construct() {
    }

    /**
     * Given the $formatter_component - which it should be a full franken-style component.
     * This function will try to look it up for the formatter class within a special namespace
     * and returning the instance of it.
     *
     * If the formatter is not found, then {@see default_formatter} will be returned.
     *
     * @param string|null $formatter_component
     * @return formatter
     */
    public static function create_formatter(?string $formatter_component = null): formatter {
        if (null === $formatter_component || '' === $formatter_component) {
            return new default_formatter();
        }

        $class_name = "\\{$formatter_component}\\json_editor\\formatter\\formatter";
        if (!class_exists($class_name)) {
            debugging(
                "The json editor formatter for component '{$formatter_component}' is not found, " .
                "make sure that the class '{$class_name}' exist within the system",
                DEBUG_DEVELOPER
            );

            return new default_formatter();
        } else if (!is_subclass_of($class_name, formatter::class)) {
            $base_class = formatter::class;
            throw new \coding_exception("Your formatter class need to extends '{$base_class}'", $class_name);
        }

        return new $class_name();
    }
}