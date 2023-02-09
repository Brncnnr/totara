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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */
namespace totara_notification\resolver;

use coding_exception;
use core_component;
use totara_notification\local\helper;
use totara_notification\resolver\abstraction\additional_criteria_resolver;
use totara_notification\resolver\abstraction\audit_resolver;
use totara_notification\resolver\abstraction\permission_resolver;
use totara_notification\resolver\abstraction\scheduled_event_resolver;

class resolver_helper {
    /**
     * resolver_helper constructor.
     * Preventing this class from instantiation.
     */
    private function __construct() {
    }

    /**
     * Return the resolver class name that associate with the notifiable event class.
     *
     * @param string $event_class_name
     * @return string
     */
    public static function get_resolver_class_name_from_notifiable_event(string $event_class_name): string {
        global $CFG;
        if (!helper::is_valid_notifiable_event($event_class_name)) {
            throw new coding_exception("Event class name is an invalid notifiable event");
        }

        $event_class_name = ltrim($event_class_name, '\\');

        if (defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
            // We are in test environment. Check that if the event class name is equal
            // to the mock event notifiable event or not.
            $mock_classes = [
                'totara_notification_mock_notifiable_event',
                'totara_notification_mock_scheduled_event_with_on_event',
            ];

            if (in_array($event_class_name, $mock_classes)) {
                $resolver_class_name = "{$event_class_name}_resolver";
                if (!class_exists($resolver_class_name)) {
                    $fixture_directory = "{$CFG->dirroot}/totara/notification/tests/fixtures";
                    require_once("{$fixture_directory}/{$resolver_class_name}.php");
                }

                return $resolver_class_name;
            }
        }

        $parts = explode("\\", $event_class_name);

        $component = reset($parts);
        $resolver_name = end($parts);

        $resolver_class_name = "{$component}\\totara_notification\\resolver\\{$resolver_name}";
        if (!class_exists($resolver_class_name)) {
            throw new coding_exception(
                "Cannot find the resolver for notifiable event '{$event_class_name}'"
            );
        }

        return $resolver_class_name;
    }

    /**
     * @param string           $event_class_name
     * @param array            $event_data
     *
     * @return notifiable_event_resolver
     */
    public static function get_resolver_from_notifiable_event(
        string $event_class_name,
        array $event_data
    ): notifiable_event_resolver {
        $resolver_class_name = static::get_resolver_class_name_from_notifiable_event($event_class_name);
        return static::instantiate_resolver_from_class($resolver_class_name, $event_data);
    }

    /**
     * @param string           $resolver_class_name
     * @param array            $event_data
     *
     * @return notifiable_event_resolver
     */
    public static function instantiate_resolver_from_class(
        string $resolver_class_name,
        array $event_data
    ): notifiable_event_resolver {
        /**
         * This is metadata programming, which we are going to invoke the construction of
         * {@see notifiable_event_resolver::__construct()}. Let the native PHP validate the
         * instance of the class here as we declared the type return from this function.
         */
        return new $resolver_class_name($event_data);
    }

    /**
     * Checking whether the $resolver_class_name is the subclass of notifiable event resolver.
     *
     * @param string $resolver_class_name
     * @return bool
     */
    public static function is_valid_event_resolver(string $resolver_class_name): bool {
        return is_subclass_of($resolver_class_name, notifiable_event_resolver::class);
    }

    /**
     * @param string $resolver_class_name
     * @return void
     */
    public static function validate_event_resolver(string $resolver_class_name): void {
        if (!static::is_valid_event_resolver($resolver_class_name)) {
            throw new coding_exception(
                "The resolver class '{$resolver_class_name}' is invalid notifiable event resolver"
            );
        }
    }

    /**
     * @param string $resolver_class_name
     * @return bool
     */
    public static function is_valid_scheduled_event_resolver(string $resolver_class_name): bool {
        if (!static::is_valid_event_resolver($resolver_class_name)) {
            return false;
        }

        return is_a($resolver_class_name, scheduled_event_resolver::class, true);
    }

    /**
     * @param string $resolver_class_name
     * @return string
     */
    public static function get_human_readable_resolver_name(string $resolver_class_name): string {
        if (!static::is_valid_event_resolver($resolver_class_name)) {
            throw new coding_exception("Resolver class name is an invalid notifiable event resolver");
        }

        return call_user_func([$resolver_class_name, 'get_notification_title']);
    }

    /**
     * @param string $resolver_class_name
     * @return string
     */
    public static function get_component_of_resolver_class_name(string $resolver_class_name): string {
        $resolver_class_name = ltrim($resolver_class_name, '\\');

        if (!static::is_valid_event_resolver($resolver_class_name)) {
            throw new coding_exception(
                'The resolver class name is not a valid notifiable event resolver'
            );
        }

        if (defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
            $mock_classes = [
                'totara_notification_mock_notifiable_event_resolver',
                'totara_notification_mock_scheduled_aware_event_resolver',
                'totara_notification_mock_scheduled_event_with_on_event_resolver',
            ];

            if (in_array($resolver_class_name, $mock_classes)) {
                // Default to totara_notification for any mock resolver classes.
                return 'totara_notification';
            }
        }

        $parts = explode('\\', $resolver_class_name);
        $component = reset($parts);

        $component = clean_param($component, PARAM_COMPONENT);
        $component_directory = null;

        if (!empty($component)) {
            // If it is a valid component within the system, its directory must had been
            // exist, and should be a valid dir path. Otherwise, its directory will not appear
            // from the result.
            $component_directory = core_component::get_component_directory($component);
        }

        if (empty($component) || empty($component_directory)) {
            throw new coding_exception("Cannot find the component from the resolver class name");
        }

        return $component;
    }

    /**
     * Get a human-readable plugin name for a resolver.
     *
     * This will be used to group the resolvers, e.g. in the UI.
     *
     * We check in this order to find the name:
     * 1. get_plugin_name() implemented by the resolver
     * 2. Lang string 'pluginname_totara_notification' for the resolver's component.
     * 3. Lang string 'pluginname' for the resolver's component.
     * 4. resolver's component name
     *
     * @param string|notifiable_event_resolver $resolver_class_name
     * @return string
     */
    public static function get_human_readable_plugin_name(string $resolver_class_name): string {
        if ($plugin_name = $resolver_class_name::get_plugin_name()) {
            return $plugin_name;
        }

        $component = static::get_component_of_resolver_class_name($resolver_class_name);

        if (get_string_manager()->string_exists('pluginname_totara_notification', $component)) {
            $plugin_name = get_string('pluginname_totara_notification', $component);
        } else if (get_string_manager()->string_exists('pluginname', $component)) {
            $plugin_name = get_string('pluginname', $component);
        } else {
            // If component does not define pluginname in langstring, we just fallback to the name of component, then
            // put debugging here to let dev know they need to define the pluginname for each plugin.
            $plugin_name = $component;
            debugging("pluginnanme need to be defined in langstring for the {$plugin_name}", DEBUG_DEVELOPER);
        }

        return $plugin_name;
    }

    /**
     * @param string $resolver_class_name
     * @return bool
     */
    public static function is_valid_permission_resolver(string $resolver_class_name): bool {
        if (!static::is_valid_event_resolver($resolver_class_name)) {
            return false;
        }

        return is_a($resolver_class_name, permission_resolver::class, true);
    }

    /**
     * @param string $resolver_class_name
     * @return bool
     */
    public static function is_valid_audit_resolver(string $resolver_class_name): bool {
        if (!static::is_valid_event_resolver($resolver_class_name)) {
            return false;
        }

        return is_a($resolver_class_name, audit_resolver::class, true);
    }

    /**
     * @param string $resolver_class_name
     * @return bool
     */
    public static function is_additional_criteria_resolver(string $resolver_class_name): bool {
        if (!static::is_valid_event_resolver($resolver_class_name)) {
            return false;
        }

        return is_a($resolver_class_name, additional_criteria_resolver::class, true);
    }
}