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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_notification
 */
namespace totara_notification\factory;

use coding_exception;
use core_component;
use totara_notification\event\notifiable_event;
use totara_notification\local\helper;
use totara_notification\notification\built_in_notification;
use totara_notification\resolver\notifiable_event_resolver;
use totara_notification\resolver\resolver_helper;

/**
 * Factory class to create the customized notification based on the component.
 */
final class built_in_notification_factory {
    /**
     * An array of all the class names that extend the class {@see built_in_notification}
     * A hash map of component's name and a list of notification within that classes.
     *
     * @var array
     */
    private static $built_in_notification_classes;

    /**
     * notification_factory constructor.
     */
    private function __construct() {
    }

    /**
     * Build the cache map for the list of notification classes and return it.
     * @return array
     */
    private static function get_map(): array {
        if (!isset(self::$built_in_notification_classes)) {
            self::$built_in_notification_classes = [];
        }

        if (empty(self::$built_in_notification_classes)) {
            // Adding core subsystem first.
            $core_notification_classes = core_component::get_namespace_classes(
                'totara_notification\\notification',
                built_in_notification::class,
                'core'
            );

            if (!empty($core_notification_classes)) {
                self::$built_in_notification_classes['core'] = $core_notification_classes;
            }

            // Then add the plugins.
            $plugin_types = core_component::get_plugin_types();
            $plugin_types = array_keys($plugin_types);

            foreach ($plugin_types as $plugin_type) {
                $plugin_names = core_component::get_plugin_list($plugin_type);
                $plugin_names = array_keys($plugin_names);

                foreach ($plugin_names as $plugin_name) {
                    $component = "{$plugin_type}_{$plugin_name}";
                    $classes = core_component::get_namespace_classes(
                        'totara_notification\\notification',
                        built_in_notification::class,
                        $component
                    );

                    if (empty($classes)) {
                        continue;
                    }

                    self::$built_in_notification_classes[$component] = $classes;
                }
            }
        }

        return self::$built_in_notification_classes;
    }

    /**
     * Returning an array of all the notification classes implemented in the system.
     *
     * @param string|null $component Whether we should return the notification classes within the component only or not.
     * @return string[]
     */
    public static function get_notification_classes(?string $component = null): array {
        // Note: for this function, please do not include any sort of global $USER or $PAGE
        // because it is being used in the upgrade step and installation code as well.
        $map = self::get_map();
        if (!empty($component)) {
            return $map[$component] ?? [];
        }

        $return_classes = [];

        foreach ($map as $component => $classes) {
            $return_classes = array_merge($return_classes, $classes);
        }

        return $return_classes;
    }

    /**
     * Returns all the event class names that are built for the resolver (given by $resolver_class_name)
     *
     * @param string $resolver_class_name
     * @return array
     */
    public static function get_notification_classes_of_event_resolver(string $resolver_class_name): array {
        if (!resolver_helper::is_valid_event_resolver($resolver_class_name)) {
            throw new coding_exception(
                "Expecting the argument resolver class name to extend the class " . notifiable_event_resolver::class
            );
        }

        $notification_classes = self::get_notification_classes();
        $resolver_class_name = ltrim($resolver_class_name, '\\');

        return array_filter(
            $notification_classes,
            function (string $built_in_class) use ($resolver_class_name): bool {
                /** @see built_in_notification::get_resolver_class_name() */
                $built_in_resolver_class_name = call_user_func([$built_in_class, 'get_resolver_class_name']);
                return $built_in_resolver_class_name === $resolver_class_name;
            }
        );
    }
}