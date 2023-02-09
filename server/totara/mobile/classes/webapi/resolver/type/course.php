<?php
/*
 * This file is part of Totara Learn
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_mobile
 */

namespace totara_mobile\webapi\resolver\type;

use core\webapi\execution_context;
use core\format;
use context_course;

/**
 * Temporary course type
 */
class course extends \core\webapi\type_resolver {
    public static function resolve(string $field, $totara_mobile_course, array $args, execution_context $ec) {
        global $CFG, $USER;

        require_once($CFG->libdir . '/grade/grade_grade.php');
        require_once($CFG->libdir . '/grade/grade_item.php');
        require_once($CFG->libdir . '/gradelib.php');

        // Check to see that there is a course from the query resolver
        if (empty($totara_mobile_course['course'])) {
            throw new \coding_exception('Course property must be resolved first');
        }

        $course = $totara_mobile_course['course'];

        // Note: There's no good way of checking course object since it returns a db object.
        if (!$course instanceof \stdClass) {
            throw new \coding_exception('Only course objects are accepted: ' . gettype($course));
        }

        if (!$course_context = context_course::instance($course->id, IGNORE_MISSING)) {
            // If there is no matching context we have a bad object, ignore missing so we can do our own error.
            throw new \coding_exception('Only valid course objects are accepted');
        }

        $format = $args['format'] ?? null;
        if (!self::authorize($field, $format, $course_context)) {
            return null;
        }

        if (!isset($course->id) or $course->id <= 0) {
            return null;
        }

        if ($field === 'course') {
            return (object)$course;
        }

        $completion = ['formatted_gradefinal', 'formatted_grademax'];
        if (in_array($field, $completion)) {
            $course_item = \grade_item::fetch_course_item($course->id);
            $grade = new \grade_grade(array('itemid' => $course_item->id, 'userid' => $USER->id));

            // The default grade decimals is 2
            $defaultdecimals = 2;
            if (property_exists($CFG, 'grade_decimalpoints')) {
                $defaultdecimals = $CFG->grade_decimalpoints;
            }
            $decimals = grade_get_setting($course->id, 'decimalpoints', $defaultdecimals);

            if ($field == 'formatted_gradefinal') {
                return format_float($grade->finalgrade, $decimals, true);
            }

            if ($field == 'formatted_grademax') {
                return format_float($grade->rawgrademax, $decimals, true);
            }
        }

        if ($field === 'mobile_coursecompat') {
            return $totara_mobile_course['mobile_coursecompat'];
        }

        if ($field === 'mobile_image') {
            // For mobile execution context, rewrite pluginfile urls in description and image_src fields.
            // This is clearly a hack, please suggest something more elegant.
            if (is_a($ec, 'totara_mobile\webapi\execution_context')) {
                $totara_mobile_course['mobile_image'] = str_replace($CFG->wwwroot . '/pluginfile.php', $CFG->wwwroot . '/totara/mobile/pluginfile.php', $totara_mobile_course['mobile_image']);
            }
            return $totara_mobile_course['mobile_image'];
        }

        return null;
    }

    public static function authorize(string $field, ?string $format, context_course $context) {
        // Permission to see RAW formatted string fields
        if (in_array($field, ['shortname', 'fullname']) && $format == format::FORMAT_RAW) {
            return has_capability('moodle/course:update', $context);
        }
        // Permission to see RAW formatted text fields
        if (in_array($field, ['summary']) && $format == format::FORMAT_RAW) {
            return has_capability('moodle/course:update', $context);
        }
        return true;
    }
}
