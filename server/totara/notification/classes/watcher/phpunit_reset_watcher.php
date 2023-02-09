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
namespace totara_notification\watcher;

use core\hook\phpunit_reset;
use ReflectionProperty;
use totara_notification\factory\built_in_notification_factory;
use totara_notification\placeholder\abstraction\placeholder_instance_cache;
use totara_notification_mock_built_in_notification;
use totara_notification_mock_notifiable_event_resolver;
use totara_notification_mock_scheduled_aware_event_resolver;
use totara_notification_mock_scheduled_built_in_notification;
use totara_notification_mock_single_placeholder;

class phpunit_reset_watcher {
    /**
     * phpunit_reset_watcher constructor.
     * Prevent this class from instantiation.
     */
    private function __construct() {
    }

    /**
     * @var array
     */
    protected static array $cached_placeholder_classes = [];

    /**
     * @param phpunit_reset $hook
     * @return void
     */
    public static function watch_phpunit_reset(phpunit_reset $hook): void {
        self::reset_built_in_notification_factory();

        if (class_exists('totara_notification_mock_notifiable_event_resolver')) {
            totara_notification_mock_notifiable_event_resolver::clear();
        }

        if (class_exists('totara_notification_mock_scheduled_aware_event_resolver')) {
            totara_notification_mock_scheduled_aware_event_resolver::clear();
        }

        if (class_exists('totara_notification_mock_built_in_notification')) {
            totara_notification_mock_built_in_notification::clear();
        }

        if (class_exists('totara_notification_mock_single_placeholder')) {
            totara_notification_mock_single_placeholder::clear();
        }

        if (class_exists('totara_notification_mock_scheduled_built_in_notification')) {
            totara_notification_mock_scheduled_built_in_notification::clear();
        }

        // Clear the placeholder cache after every test.
        // So clear_instance_cache is no longer required in every placeholder test.
        foreach (self::$cached_placeholder_classes as $class => $unused) {
            /** @var $class placeholder_instance_cache */
            $class::clear_instance_cache();
        }

        self::$cached_placeholder_classes = [];
    }

    /**
     * Expecting this will function will throw an exception on event when the property does
     * not exist. Though, we are doing nothing here, as we would want the test to fail anyway.
     * A helper to reset value of {@see built_in_notification_factory::$built_in_notification_classes}
     *
     * @return void
     */
    private static function reset_built_in_notification_factory(): void {
        $property = new ReflectionProperty(
            built_in_notification_factory::class,
            'built_in_notification_classes'
        );

        $property->setAccessible(true);
        $property->setValue(null);

        // Remove the accessible of the property.
        $property->setAccessible(false);
    }

    /**
     * @param string $class
     * @return void
     */
    public static function register_cached_placeholder_class(string $class): void {
        self::$cached_placeholder_classes[$class] = true;
    }
}