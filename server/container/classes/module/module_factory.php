<?php
/*
 * This file is part of Totara LMS
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
 * @package core_container
 */
namespace core_container\module;

use container_course\course;
use container_site\site;

/**
 * Factory class for module.
 */
final class module_factory {
    /**
     * @var array
     */
    private static $moduleclassmap;

    /**
     * Prevent any instantiation of this class.
     * module_factory constructor.
     */
    private function __construct() {
    }

    /**
     * Build up the map between container's type and the module classname.
     *
     * @return void
     */
    public static function init(): void {
        if (isset(static::$moduleclassmap) && !empty(static::$moduleclassmap)) {
            return;
        }

        static::$moduleclassmap = [];
        $plugins = \core_component::get_plugin_list('container');

        foreach ($plugins as $plugin => $location) {
            $component = "container_{$plugin}";
            $classes = \core_component::get_namespace_classes('module', module::class, $component);

            if (empty($classes)) {
                debugging("The component '{$component}' does not have the module", DEBUG_DEVELOPER);
                continue;
            } else if (1 !== count($classes)) {
                debugging("The component '{$component}' has more than one class for module", DEBUG_DEVELOPER);
            }

            static::$moduleclassmap[$component] = (string) reset($classes);
        }
    }

    /**
     * Get the module class map
     *
     * @param string $containertype
     * @return string
     */
    private static function get_module_class(string $containertype): string {
        static::init();

        if (!array_key_exists($containertype, static::$moduleclassmap)) {
            throw new \coding_exception("No container type found for '{$containertype}'");
        }

        return static::$moduleclassmap[$containertype];
    }

    /**
     * This function will call to method {@see module::from_id()}
     *
     * @param int   $id
     * @param bool $strict
     *
     * @return module|null
     */
    public static function from_id(int $id, bool $strict = true): ?module {
        global $DB;

        $strictness = $strict ? MUST_EXIST : IGNORE_MISSING;

        $sql = '
            SELECT c.id, c.containertype FROM "ttr_course" c
            INNER JOIN "ttr_course_modules" cm ON cm.course = c.id
            WHERE cm.id = ?';

        $record = $DB->get_record_sql($sql, [$id], $strictness);
        if (!$record) {
            // No record found.
            return null;
        }

        $containertype = $record->containertype;

        if (null === $record->containertype) {
            // Empty containertype means it is a legacy course or a site, we need to make sure either one of it.
            if (SITEID == $record->id) {
                $containertype = site::get_type();
            } else {
                $containertype = course::get_type();
            }
        }

        $moduleclass = static::get_module_class($containertype);
        if (!method_exists($moduleclass, 'from_id')) {
            throw new \coding_exception("No function 'from_id' found for class '{$moduleclass}'");
        }

        return call_user_func_array([$moduleclass, 'from_id'], [$id, $strict]);
    }

    /**
     * This function will call to {@see module::from_record()} to build a module object out of the record.
     *
     * @param \stdClass $record
     * @return module
     */
    public static function from_record(\stdClass $record): module {
        global $DB;

        if (!property_exists($record, 'course')) {
            throw new \coding_exception("Cannot instantiate module from a record without course id");
        }

        if (!property_exists($record, 'id')) {
            throw new \coding_exception("No property 'id' found");
        }

        if (SITEID === $record->course) {
            $containertype = site::get_type();
        } else {
            $containertype = null;

            if (property_exists($record, 'containertype')) {
                $containertype = $record->containertype;

                // Start removing property containertype, so that the factory method from module will not
                // complaining about the extra properties. However, we must make sure that original data is not
                // being mutated.
                $record = clone $record;
                unset($record->containertype);
            } else {
                $sql = '
                    SELECT c.containertype FROM "ttr_course" c
                    INNER JOIN "ttr_course_modules" cm ON cm.course = c.id
                    WHERE cm.id = ?';

                $containertype = $DB->get_field_sql($sql, [$record->id], MUST_EXIST);
            }

            if (null == $containertype) {
                $containertype = course::get_type();
            }
        }

        $moduleclass = static::get_module_class($containertype);
        if (!method_exists($moduleclass, 'from_record')) {
            throw new \coding_exception("No function 'from_record' existing in class '{$moduleclass}'");
        }

        return call_user_func_array([$moduleclass, 'from_record'], [$record]);
    }

    /**
     * This function will invoke {@see module::create()} to create a specific instance of module.
     *
     * @param int       $containerid
     * @param \stdClass $newcm
     *
     * @return module
     */
    public static function create_module(int $containerid, \stdClass $newcm): module {
        global $DB;

        if (SITEID == $containerid) {
            $containertype = site::get_type();
        } else {
            $containertype = $DB->get_field('course', 'containertype', ['id' => $containerid], MUST_EXIST);

            if (null === $containertype || '' === $containertype) {
                // Empty container type. Fall back to course.
                $containertype = course::get_type();
            }
        }

        $classname = static::get_module_class($containertype);
        if (!method_exists($classname, 'create')) {
            throw new \coding_exception("No function 'create' for class '{$classname}'");
        }

        return call_user_func_array([$classname, 'create'], [$newcm]);
    }
}