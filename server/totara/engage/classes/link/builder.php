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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_engage
 */

namespace totara_engage\link;

use coding_exception;
use core_component;

/**
 * The base link generation class. It will find the generator for the
 * specified component or page and return an instance of it.
 *
 * The rest of the behaviour is handled in the extended generators.
 *
 * @package totara_engage\link
 */
final class builder {
    /**
     * Divider for the fields
     */
    const FIELD_SPLIT = '.';

    /**
     * Simple request cache of link generators (to speed subsequent calls up)
     *
     * @var array
     */
    private static $generator_cache;

    /**
     * Simple hash map of the unique source key => component name
     *
     * @var array
     */
    private static $keys_cache;

    /**
     * Start building a link to the following component or page
     *
     * @param string $component_or_page
     * @param array $attributes
     * @return destination_generator
     */
    public static function to(string $component_or_page, array $attributes = []): destination_generator {
        static::init();

        if (!isset(static::$generator_cache['destination'][$component_or_page])) {
            throw new coding_exception("Unknown link destination for component or page '$component_or_page'");
        }

        return call_user_func([static::$generator_cache['destination'][$component_or_page], 'make'], $attributes);
    }

    /**
     * @return library_destination
     */
    public static function to_library(): library_destination {
        /** @var library_destination $library */
        $library = static::to('page_library');
        return $library;
    }

    /**
     * @param string $component_or_page
     * @param array $attributes
     * @return source_generator
     */
    public static function find_source_generator(string $component_or_page, array $attributes = []): source_generator {
        static::init();

        if (!isset(static::$generator_cache['source'][$component_or_page])) {
            throw new coding_exception("Unknown link source for component or page '$component_or_page'");
        }

        return call_user_func([static::$generator_cache['source'][$component_or_page], 'make'], $attributes);
    }

    /**
     * Find the matching generator for the provided source string.
     * This method will return null if an incorrect string was provided, since
     * the strings can be manipulated by a user directly.
     *
     * Note that this function will try to invoke {@see source_generator::convert_source_to_attributes()}
     *
     * @param string $source_string
     * @return destination_generator
     */
    public static function from_source(string $source_string): destination_generator {
        static::init();

        // make the generator
        $params = explode(static::FIELD_SPLIT, $source_string);

        $key = array_shift($params);
        $component_or_page = static::$keys_cache[$key] ?? null;
        if (!$component_or_page || !isset(static::$generator_cache['source'][$component_or_page])) {
            return empty_destination::make([]);
        }

        // Validate it first, if it fails then return an empty result
        $params = array_values($params);
        $attributes = call_user_func([static::$generator_cache['source'][$component_or_page], 'convert_source_to_attributes'], $params);

        $is_valid = call_user_func([static::$generator_cache['source'][$component_or_page], 'validate'], $attributes);
        if (!$is_valid) {
            return empty_destination::make([]);
        }

        // Try to create the destination
        try {
            return static::to($component_or_page, $attributes);
        } catch (coding_exception $ex) {
            return empty_destination::make([]);
        }
    }

    /**
     * @return array
     */
    public static function get_generators_for_tests(): array {
        if (defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
            static::init();
            return static::$generator_cache;
        }

        throw new coding_exception('link builder get_generator_for_tests is only available for unit tests.');
    }

    /**
     * Helper method that preloads the generator classes so we can find the correct version
     */
    private static function init(): void {
        if (static::$generator_cache !== null) {
            return;
        }

        static::$generator_cache = [
            'destination' => [],
            'source' => [],
        ];

        // Library page is magical and special
        // We go first so it cannot be overridden by other classes
        $component = 'page_library';
        $key = library_source::get_source_key();
        static::$keys_cache[$key] = $component;
        static::$generator_cache['source'][$component] = library_source::class;
        static::$generator_cache['destination'][$component] = library_destination::class;

        // Load our classes, and store them in our simple cache to speed lookups up
        $classes = core_component::get_namespace_classes('totara_engage\\link', source_generator::class);
        foreach ($classes as $generator) {
            // Figure out which component it's for
            $component = strtok($generator, '\\');
            $key = call_user_func([$generator, 'get_source_key']);

            if (isset(static::$keys_cache[$key])) {
                throw new coding_exception("Link source key '$key' is already in use, it must be unique.");
            }

            static::$keys_cache[$key] = $component;
            static::$generator_cache['source'][$component] = $generator;
        }

        $classes = core_component::get_namespace_classes('totara_engage\\link', destination_generator::class);
        foreach ($classes as $generator) {
            // Figure out which component it's for
            $component = strtok($generator, '\\');
            static::$generator_cache['destination'][$component] = $generator;
        }
    }
}