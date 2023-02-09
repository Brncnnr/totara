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

use core\formatter\category_formatter;
use core\format;
use coursecat;
use context_coursecat;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use coding_exception;

class category extends type_resolver {
    public static function resolve(string $field, $category, array $args, execution_context $ec) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        $format = $args['format'] ?? null;
        if (!self::do_authorize($field, $format, $category->id)) {
            throw new coding_exception('You are not allowed to view category name');
        }

        $datefields = ['timemodified'];
        if (in_array($field, $datefields) && empty($category->{$field})) {
            // Highly unlikely this is set to 1/1/1970, return null for notset dates.
            return null;
        }


        if ($field == 'parent') {
            // Top-level category.
            if (empty($category->parent)) {
                return null;
            } else {
                return coursecat::get($category->parent);
            }
        }

        // Transform the format field from the constants to a core_format string.
        if ($field == 'descriptionformat') {
            return format::from_moodle($category->descriptionformat);
        }

        if ($field == 'children') {
            $cat = coursecat::get($category->id);
            return $cat->get_children();
        }

        if ($field == 'courses') {
            $cat = coursecat::get($category->id);
            $courseids = $cat->get_courses(['idonly' => true]);
            if (empty($courseids)) {
                return [];
            } else {
                [$insql, $inparams] = $DB->get_in_or_equal($courseids);
                $courses = $DB->get_records_select('course', "id {$insql}", $inparams);
                foreach ($courses as $course) {
                    $course->image = course_get_image($course);
                }
                return $courses;
            }
        }

        $formatter = new category_formatter(
            $category,
            context_coursecat::instance($category->id)
        );

        return $formatter->format($field, $format);
    }

    /**
     * Checks if the user is authorized to see any of the fields with FORMAT_RAW or not.
     *
     * @param string      $field
     * @param string|null $format
     * @param int         $category_id
     *
     * @return bool
     */
    private static function do_authorize(string $field, ?string $format, int $category_id): bool {
        if (in_array($field, ['name', 'description']) && $format == format::FORMAT_RAW) {
            // Note that we are trying to instantiate the context_category as latest as possible,
            // so that it can help to improve the performance to be a bit faster.
            $context_category = context_coursecat::instance($category_id);
            return has_capability('moodle/category:manage', $context_category);
        }

        return true;
    }

    /**
     * @deprecated since Totara 15.0
     *
     * @param string            $field
     * @param string|null       $format
     * @param context_coursecat $context
     * @return bool
     */
    public static function authorize(string $field, ?string $format, context_coursecat $context) {
        debugging(
            sprintf(
                "The function %s had been deprecated and should not be used publicly outside of the class, " .
                "please use %s instead",
                __FUNCTION__,
                static::class . "::do_authorize"
            ),
            DEBUG_DEVELOPER
        );

        return self::do_authorize($field, $format, $context->instanceid);
    }
}
