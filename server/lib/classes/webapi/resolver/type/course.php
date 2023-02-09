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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @author David Curry <david.curry@totaralearning.com>
 * @package core
 */

namespace core\webapi\resolver\type;

use core\webapi\execution_context;
use core_course\formatter\course_formatter;
use core\format;
use core\webapi\type_resolver;
use context_course;
use coursecat;

class course extends type_resolver {
    public static function resolve(string $field, $course, array $args, execution_context $ec) {
        global $DB, $USER, $CFG, $OUTPUT;

        if ($course instanceof \core\entity\course) {
            $course = $course->to_record();
        }

        // Note: There's no good way of checking course object since it returns a db object.
        if (!$course instanceof \stdClass) {
            throw new \coding_exception('Only course objects are accepted: ' . gettype($course));
        }

        $format = $args['format'] ?? null;
        if (!$course_context = context_course::instance($course->id, IGNORE_MISSING)) {
            // If there is no matching context we have a bad object, ignore missing so we can do our own error.
            throw new \coding_exception('Only valid course objects are accepted');
        }

        if (!self::authorize($field, $format, $course_context)) {
            return null;
        }

        if (empty($course->icon)) {
            $course->icon = 'default';
        }

        $datefields = ['timecreated', 'timemodified', 'startdate', 'enddate'];
        if (in_array($field, $datefields) && empty($course->{$field})) {
            // Highly unlikely this is set to 1/1/1970, return null for notset dates.
            return null;
        }

        if ($field == 'categoryid') {
            return $course->category;
        }

        if ($field == 'category') {
            // Front page course
            if ($course->category == 0) {
                return null;
            } else {
                return coursecat::get($course->category);
            }
        }

        // Course view url.
        if ($field === "url") {
            return course_get_url($course->id)->out();
        }

        // Transform the format field from the constants to a core_format string.
        if ($field == 'summaryformat') {
            return format::from_moodle($course->summaryformat);
        }

        if ($field == 'image') {
            if (!isset($course->image)) {
                $course->image = course_get_image($course);
            }

            if ($course->image instanceof \moodle_url) {
                $course->image = $course->image->out();
            }
        }

        if ($field == 'showgrades') {
            return !empty($course->showgrades);
        }

        if ($field == 'completionenabled') {
            return !empty($course->enablecompletion);
        }

        if ($field == 'completion') {
            $params = ['userid' => $USER->id, 'course' => $course->id];
            return new \completion_completion($params);
        }

        if ($field == 'criteria') {
            // Organise activity completions according to the course display order.
            // Obtain the display order of activity modules.
            $sections = $DB->get_records('course_sections', array('course' => $course->id), 'section ASC', 'id, sequence');
            $moduleorder = array();
            foreach ($sections as $section) {
                if (!empty($section->sequence)) {
                    $moduleorder = array_merge(array_values($moduleorder), array_values(explode(',', $section->sequence)));
                }
            }

            $info = new \completion_info($course);
            $completions = $info->get_completions($USER->id);
            $modulecriteria = [];
            $nonactivitycompletions = [];
            foreach ($completions as $completion) {
                $criteria = $completion->get_criteria();
                $completion->typeaggregation = $info->get_aggregation_method($criteria->criteriatype);
                if ($criteria->criteriatype == COMPLETION_CRITERIA_TYPE_ACTIVITY) {
                    if (!empty($criteria->moduleinstance)) {
                        $modulecriteria[$criteria->moduleinstance] = $completion;
                    }
                } else {
                    $nonactivitycompletions[] = $completion;
                }
            }

            // Compare to the course module order to put the activities in the same order as on the course view.
            $activitycompletions = [];
            foreach ($moduleorder as $module) {
                // Some modules may not have completion criteria and can be ignored.
                if (isset($modulecriteria[$module])) {
                    $activitycompletions[] = $modulecriteria[$module];
                }
            }

            $orderedcompletions = [];

            // Put the activity completions at the top.
            foreach ($activitycompletions as $completion) {
                $orderedcompletions[] = $completion;
            }

            foreach ($nonactivitycompletions as $completion) {
                $orderedcompletions[] = $completion;
            }

            return $orderedcompletions;
        }

        /**
         * Constants defined in lib completionlib
         * define('COMPLETION_AGGREGATION_ALL', 1);
         * define('COMPLETION_AGGREGATION_ANY', 2);
         */
        if ($field == 'criteriaaggregation') {
            $info = new \completion_info($course);
            $aggregationtypes = $info::get_aggregation_methods();
            return $aggregationtypes[$info->get_aggregation_method()];
        }

        // Course format metadata.
        if ($field === "course_format") {
            require_once("{$CFG->dirroot}/course/format/lib.php");
            return course_get_format($course);
        }

        if (in_array($field, ['sections', 'modcount'])) {
            $modinfo = \course_modinfo::instance($course->id, $USER->id);

            // Does a quick visibility check and counts visible modules.
            if ($field == 'modcount') {
                $count = 0;
                foreach ($modinfo->get_cms() as $cm) {
                    if ($cm->__get('uservisible')) {
                        $count++;
                    }
                }
                return $count;
            }

            // Return the raw course section information, let the type handle the rest.
            if ($field === 'sections') {
                $course_format = course_get_format($course);
                $numsections = $course_format->get_last_section_number();
                $uses_sections = $course_format->uses_sections();
                $rawsections = $modinfo->get_section_info_all();

                // The user can see everything, just return everything.
                if (has_capability('moodle/course:viewhiddensections', $course_context, $USER->id)) {
                    return $rawsections;
                }

                $sections = [];
                // Quickly loop through all the sections, and remove non-visible ones.
                foreach ($rawsections as $key => $section) {
                    if ($uses_sections && $key == 0 && !($section->summary || !empty($modinfo->sections[0]))) {
                        continue;
                    }
                    if ($key <= $numsections && $section->__get('visible')) {
                        $sections[$key] = $section;
                    }
                }

                return $sections;
            }
        }

        $formatter = new course_formatter($course, $course_context);
        $formatted = $formatter->format($field, $format);

        // For mobile execution context, rewrite pluginfile urls in description and image_src fields.
        // This is clearly a hack, please suggest something more elegant.
        if (is_a($ec, 'totara_mobile\webapi\execution_context') && in_array($field, ['summary', 'description', 'image', 'mobile_image'])) {
            $formatted = str_replace($CFG->wwwroot . '/pluginfile.php', $CFG->wwwroot . '/totara/mobile/pluginfile.php', $formatted);
        }

        return $formatted;
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
