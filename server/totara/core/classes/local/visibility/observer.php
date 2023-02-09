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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\local\visibility;

use totara_core\task\visibility_map_regenerate_all;
use totara_core\task\visibility_map_regenerate_course;
use totara_core\task\visibility_map_regenerate_program;
use totara_core\task\visibility_map_regenerate_certification;

/**
 * Visibility controller event observer.
 *
 * @internal
 */
final class observer {

    /**
     * Regenerates all maps when a category is deleted.
     *
     * This is important as the deletion may have involved moving category content.
     * The whole structure will have changed.
     *
     * @param \core\event\course_category_deleted $event
     */
    public static function category_deleted(\core\event\course_category_deleted $event) {
        visibility_map_regenerate_all::queue();
    }

    /**
     * Generates map data for a newly created course.
     *
     * @param \core\event\course_created $event
     */
    public static function course_created(\core\event\course_created $event) {
        visibility_map_regenerate_course::queue($event->objectid);
    }

    /**
     * Updates map data for an updated course.
     *
     * @param \core\event\course_updated $event
     */
    public static function course_updated(\core\event\course_updated $event) {
        visibility_map_regenerate_course::queue($event->objectid);
    }

    /**
     * Cleans up map data for a deleted course.
     *
     * @param \core\event\course_deleted $event
     */
    public static function course_deleted(\core\event\course_deleted $event) {
        visibility_map_regenerate_course::queue($event->objectid);
    }

    /**
     * Generates map data for a restored course.
     *
     * @param \core\event\course_restored $event
     */
    public static function course_restored(\core\event\course_restored $event) {
        visibility_map_regenerate_course::queue($event->objectid);
    }

    /**
     * Generates map data for a newly created program.
     *
     * @param \totara_program\event\program_created $event
     */
    public static function program_created(\totara_program\event\program_created $event) {
        self::regenerate_program_event($event);
    }

    /**
     * Updates map data for an updated program.
     *
     * @param \totara_program\event\program_updated $event
     */
    public static function program_updated(\totara_program\event\program_updated $event) {
        self::regenerate_program_event($event);
    }

    /**
     * Updates map data for an updated certification.
     *
     * @param \totara_certification\event\certification_updated $event
     */
    public static function certification_updated(\totara_certification\event\certification_updated $event) {
        visibility_map_regenerate_certification::queue($event->objectid);
    }

    /**
     * Cleans up map data for a deleted program.
     *
     * @param \totara_program\event\program_deleted $event
     */
    public static function program_deleted(\totara_program\event\program_deleted $event) {
        self::regenerate_program_event($event);
    }

    /**
     * Regenerates program or certification map data for a given event.
     * @param \core\event\base $event
     */
    private static function regenerate_program_event(\core\event\base $event) {
        if (!empty($event->other['certifid'])) {
            visibility_map_regenerate_certification::queue($event->objectid);
            return;
        }
        visibility_map_regenerate_program::queue($event->objectid);
    }
}