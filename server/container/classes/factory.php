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
 * @package totara_core
 */
namespace core_container;

use container_course\course;
use container_site\site;
use core_container\backup\backup_helper;
use core_container\backup\restore_helper;
use ReflectionClass;

final class factory {
    /**
     * A hash-map where the key is the container's type and value is the container class name.
     *
     * @var array
     */
    private static $containers_map;

    /**
     * A hash map where container's id is the key and the value is a container's record/instance.
     * Array<int, container>
     *
     * @var container[]
     */
    private static $containers;

    /**
     * Init the map for containers and the cache of containers.
     *
     * @return void
     */
    private static function init(): void {
        // Setup containers
        if (!isset(static::$containers)) {
            static::$containers = [];
        }

        // Setup container type map
        if (!isset(static::$containers_map)) {
            static::$containers_map = [];
        }

        if (empty(static::$containers_map)) {
            $plugins = \core_component::get_plugin_list('container');

            foreach ($plugins as $plugin => $location) {
                $component = "container_{$plugin}";
                $container_cls = "\\{$component}\\{$plugin}";

                if (!class_exists($container_cls)) {
                    debugging(
                        "The container class '{$container_cls}' is not existing for container {$component}",
                        DEBUG_DEVELOPER
                    );

                    continue;
                }

                static::$containers_map[$component] = $container_cls;
            }
        }
    }

    /**
     * If the containertype is not being set, then it will be falling back to old legacy course,
     * this includes site.
     *
     * @param string|null $container_type
     * @return string|container Container class name
     */
    public static function get_container_class(?string $container_type): string {
        if (null == $container_type) {
            // It is empty string or null, fall back to the old friendly legacy course.
            // Site should be treated as a course too.
            $container_type = course::get_type();
        }

        $classes = static::get_container_classes();
        if (isset($classes[$container_type])) {
            return $classes[$container_type];
        }

        throw new \coding_exception("There is no info class for type '{$container_type}'");
    }

    /**
     * @param int $id
     * @return container|null
     */
    private static function get_from_cache(int $id): ?container {
        if (!isset(static::$containers[$id])) {
            return null;
        }

        return static::$containers[$id];
    }

    /**
     * Adding the container to the cache of this factory.
     *
     * @param container $container
     * @return void
     */
    private static function add_to_cache(container $container): void {
        if (isset(static::$containers[$container->id])) {
            debugging("The container '{$container->id}' has already put in cached", DEBUG_DEVELOPER);
            return;
        }

        static::$containers[$container->id] = $container;
    }

    /**
     * Passing zero as parameter to reset every thing !
     *
     * @param int $containerid
     * @return void
     */
    public static function reset(int $containerid = 0): void {
        if (!isset(static::$containers)) {
            // No point to reset.
            return;
        }

        if (0 == $containerid) {
            static::$containers = [];
        }

        if (isset(static::$containers[$containerid])) {
            unset(static::$containers[$containerid]);
        }
    }

    /**
     * @return void
     */
    public static function reset_containers_map(): void {
        // Reset to empty array so that the init process can re-populate the class maps.
        static::$containers_map = [];
    }

    /**
     * Building container from the data record. Dynamically building the container based on the attribute of container
     * type.
     *
     * @see container::from_record()
     *
     * @param \stdClass     $record
     *
     * @return container
     */
    public static function from_record(\stdClass $record): container {
        global $DB;

        if (!isset($record->id) || 0 == $record->id) {
            // Checking for empty string or actual zero as well.
            throw new \coding_exception("Property 'id' of the container was not set");
        }

        $container = static::get_from_cache($record->id);
        if (null != $container) {
            // We found something in the cache, and check for the cache rev
            if (!isset($record->cacherev) || $container->cacherev == $record->cacherev) {
                return $container;
            }
        }

        // Reset the cache.
        static::reset($record->id);

        if (SITEID == $record->id) {
            $containertype = site::get_type();
        } else {
            if (isset($record->containertype)) {
                $containertype = $record->containertype;
            } else {
                $containertype = $DB->get_field('course', 'containertype', ['id' => $record->id], MUST_EXIST);

                if (null == $containertype) {
                    $containertype = course::get_type();
                }
            }
        }

        $classname = static::get_container_class($containertype);
        if (!method_exists($classname, 'from_record')) {
            throw new \coding_exception("No factory method 'from_record' for container '{$classname}'");
        }

        /** @var container $container */
        $container = call_user_func_array([$classname, 'from_record'], [$record]);

        static::add_to_cache($container);
        return $container;
    }

    /**
     * Fetching container from id. Dynamically fetching the container, via factory method.
     *
     * @see container::from_id()
     * @param int           $id
     * @return container
     */
    public static function from_id(int $id): container {
        global $DB;
        if (0 == $id) {
            // Checking for empty string or actual zero as well.
            throw new \coding_exception("Property 'id' of the container was not set");
        }

        $container = static::get_from_cache($id);
        if (null === $container) {
            $containertype = site::get_type();

            if (SITEID != $id) {
                $containertype = $DB->get_field('course', 'containertype', ['id' => $id], MUST_EXIST);
            }

            $classname = static::get_container_class($containertype);

            if (!method_exists($classname, 'from_id')) {
                throw new \coding_exception("There is no factory method 'from_id' for container '{$classname}'");
            }

            /** @var container $container */
            $container = call_user_func_array([$classname, 'from_id'], [$id]);
            static::add_to_cache($container);
        }

        return $container;
    }

    /**
     * Returning all the container classes.
     *
     * @return string[]|container[] Array of [container type name => container class name]
     */
    public static function get_container_classes(): array {
        static::init();
        return static::$containers_map;
    }

    /**
     * Get the helper for container specific functionality for backing up via the Moodle2 Backup API.
     *
     * @param int|object $course Course ID or record
     * @return backup_helper
     */
    public static function get_backup_helper($course): backup_helper {
        global $CFG;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

        $container = is_object($course) ? self::from_record($course) : self::from_id($course);
        $helper_class_name = (new ReflectionClass($container))->getNamespaceName() . '\backup\backup_helper';

        return class_exists($helper_class_name) ? new $helper_class_name($container) : new backup_helper($container);
    }

    /**
     * Get the helper for container specific functionality for restoring via the Moodle2 Backup API.
     *
     * @param int|object $course Course ID or record
     * @return restore_helper
     */
    public static function get_restore_helper($course): restore_helper {
        global $CFG;
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        $container = is_object($course) ? self::from_record($course) : self::from_id($course);
        $helper_class_name = (new ReflectionClass($container))->getNamespaceName() . '\backup\restore_helper';

        return class_exists($helper_class_name) ? new $helper_class_name($container) : new restore_helper($container);
    }

    /**
     * @param string $type
     * @param string $class_name
     *
     * @return void
     */
    public static function phpunit_add_mock_container_class(string $type, string $class_name): void {
        if (!defined('PHPUNIT_TEST') || !PHPUNIT_TEST) {
            throw new \coding_exception(
                "The function to add mock container class is only available to phpunit environment"
            );
        }

        static::init();

        if (!isset(static::$containers_map[$type])) {
            static::$containers_map[$type] = $class_name;
            return;
        }

        throw new \coding_exception("There is already a container class that set for type '{$type}'");
    }
}