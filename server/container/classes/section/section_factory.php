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
namespace core_container\section;

use container_course\course;
use container_site\site;

/**
 * Factory class for section
 */
final class section_factory {
    /**
     * @var array
     */
    private static $sectionclassmap;

    /**
     * Build up the map of container type and the class name of section.
     * @return void
     */
    private static function init(): void {
        if (isset(static::$sectionclassmap) && !empty(static::$sectionclassmap)) {
            return;
        }

        static::$sectionclassmap = [];
        $plugins = \core_component::get_plugin_list('container');

        foreach ($plugins as $plugin => $location) {
            $component = "container_{$plugin}";
            $classes = \core_component::get_namespace_classes('section', section::class, $component);

            if (empty($classes)) {
                debugging("There are no section class for component '{$component}'", DEBUG_DEVELOPER);
                continue;
            } else if (1 !== count($classes)) {
                debugging("There are more than one class for section in component '{$component}'", DEBUG_DEVELOPER);
            }

            static::$sectionclassmap[$component] = (string) reset($classes);
        }
    }

    /**
     * @param string $containertype
     * @return string
     */
    private static function get_section_class(string $containertype): string {
        static::init();

        if (!array_key_exists($containertype, static::$sectionclassmap)) {
            throw new \coding_exception("No such container type '{$containertype}'");
        }

        return static::$sectionclassmap[$containertype];
    }

    /**
     * This function will invoke {@see section::from_id()} to instantiate an object of section from an id.
     *
     * @param int $id
     * @param bool $strict
     *
     * @return section|null
     */
    public static function from_id(int $id, bool $strict = true): ?section {
        global $DB;

        $strictness = IGNORE_MISSING;
        if ($strict) {
            $strictness = MUST_EXIST;
        }

        $sql = '
            SELECT c.id, c.containertype FROM "ttr_course" c
            INNER JOIN "ttr_course_sections" cs ON cs.course = c.id
            WHERE cs.id = ?';

        $record = $DB->get_record_sql($sql, [$id], $strictness);
        if (!$record) {
            return null;
        }

        $containertype = $record->containertype;

        if (null == $record->containertype) {
            // Container type for course and site are empty, therefore, we need the field {course}.id to determine which
            // container type it is.
            if (SITEID == $record->id) {
                $containertype = site::get_type();
            } else {
                $containertype = course::get_type();
            }
        }

        $classname = static::get_section_class($containertype);
        if (!method_exists($classname, 'from_id')) {
            throw new \coding_exception("No function 'from_id' for class '{$classname}'");
        }

        return call_user_func_array([$classname, 'from_id'], [$id, $strict]);
    }

    /**
     * This function will invoke {@see section::from_record()} to instantiate an object of section from
     * a dummy data object.
     *
     * @param \stdClass $record
     * @return section
     */
    public static function from_record(\stdClass $record): section {
        global $DB;

        if (!property_exists($record, 'course')) {
            throw new \coding_exception("No property 'course' found");
        }

        if (!property_exists($record, 'id')) {
            throw new \coding_exception("No property 'id' found");
        }

        $containertype = null;

        if (SITEID == $record->course) {
            $containertype = site::get_type();
        } else {
            if (property_exists($record, 'containertype')) {
                $containertype = $record->containertype;
            } else {
                $sql = '
                    SELECT c.containertype FROM "ttr_course" c
                    INNER JOIN "ttr_course_sections" cs ON cs.course = c.id
                    WHERE cs.id = ?';

                $containertype = $DB->get_field_sql($sql, [$record->id], MUST_EXIST);
            }

            if (null == $containertype) {
                $containertype = course::get_type();
            }
        }

        $classname = static::get_section_class($containertype);
        if (!method_exists($classname, 'from_record')) {
            throw new \coding_exception("No method 'from_record' found for class '{$classname}'");
        }

        return call_user_func_array([$classname, 'from_record'], [$record]);
    }

    /**
     * This function will invoke {@see section::from_section_number()} to instantiate an object of section.
     *
     * @param int $sectionnumber
     * @param int $containerid
     * @param bool $strict
     *
     * @return section|null
     */
    public static function from_section_number(int $containerid, int $sectionnumber, bool $strict = true): ?section {
        global $DB;

        if (SITEID == $containerid) {
            $containertype = site::get_type();
        } else {
            $sql = 'SELECT c.id, c.containertype FROM "ttr_course" c WHERE c.id = ?';

            $strictnes = IGNORE_MISSING;
            if ($strict) {
                $strictnes = MUST_EXIST;
            }

            $record = $DB->get_record_sql($sql, [$containerid], $strictnes);
            if (null == $record) {
                return null;
            }

            $containertype = $record->containertype;
            if (null == $containertype) {
                $containertype = course::get_type();
            }
        }

        $sectionclass = static::get_section_class($containertype);
        if (!method_exists($sectionclass, 'from_section_number')) {
            throw new \coding_exception(
                "No method 'from_section_number' found for section class '{$sectionclass}'"
            );
        }

        return call_user_func_array(
            [$sectionclass, 'from_section_number'],
            [$containerid, $sectionnumber, $strict]
        );
    }

    /**
     * This function will invoke {@see section::create()} to create a new record of section, and then return
     * the right section object depending on the container type.
     *
     * @param int $containerid
     * @param int $sectionnumber
     *
     * @return section
     */
    public static function create_section(int $containerid, int $sectionnumber): section {
        global $DB;

        if (SITEID == $containerid) {
            $containertype = site::get_type();
        } else {
            $containertype = $DB->get_field('course', 'containertype', ['id' => $containerid], MUST_EXIST);

            if (null === $containertype || '' === $containertype) {
                $containertype = course::get_type();
            }
        }

        $classname = static::get_section_class($containertype);
        if (!method_exists($classname, 'create')) {
            throw new \coding_exception("No function 'create' found for the section class '{$classname}'");
        }

        return call_user_func_array([$classname, 'create'], [$containerid, $sectionnumber]);
    }

    /**
     * Create an array of given section.
     *
     * @param int $container_id
     * @param array $section_numbers
     *
     * @return section[]
     */
    public static function create_sections(int $container_id, array $section_numbers): array {
        $sections = [];

        foreach ($section_numbers as $section_number) {
            $sections[] = static::create_section($container_id, $section_number);
        }

        return $sections;
    }
}