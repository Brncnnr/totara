<?php
/*
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
 * @package totara_mobile
 * @author David Curry
 */

namespace totara_mobile\webapi\resolver\type;

use totara_program\user_learning\courseset as prog_courseset;
use totara_certification\user_learning\courseset as cert_courseset;
use totara_mobile\formatter\mobile_program_courseset_formatter as courseset_formatter;
use core_course\user_learning\item as course_item;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use coding_exception;
use core\format;

/**
 * Program type
 *
 * Note: It is the responsibility of the query to ensure the user is permitted to see a program
 */
class program_courseset extends type_resolver {

    /**
     * Resolve program fields
     *
     * @param string $field
     * @param mixed $program
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $courseset, array $args, execution_context $ec) {
        global $CFG, $USER, $DB;

        require_once($CFG->dirroot . '/totara/program/program.class.php');
        require_once($CFG->dirroot . '/totara/program/program_courseset.class.php');

        if (!is_object($courseset)) {
            throw new coding_exception('Only program courseset objects are accepted: ' . gettype($courseset));
        }

        if ($courseset instanceof prog_courseset || $courseset instanceof cert_courseset) {
            // remove the set from the item wrapper.
            $courseset = $courseset->get_set();
        }

        $context = $ec->get_relevant_context();
        if ($field == 'courses') {
            $courses = [];
            foreach ($courseset->courses as $course) {
                $item = course_item::one($USER, $course->id);
                $item->image_src = $item->get_image();
                $courses[] = $item;
            }

            return $courses;
        }

        if ($field == 'statuskey') {
            $completion = prog_load_courseset_completion($courseset->id, $USER->id, false);

            if (empty($completion) || empty($completion->status)) {
                // This user likely isn't enrolled in the program.
                return '';
            } else {
                switch ($completion->status) {
                    case STATUS_COURSESET_INCOMPLETE:
                        return 'incomplete';
                        break;
                    case STATUS_COURSESET_COMPLETE:
                        return 'complete';
                        break;
                    default:
                        throw new coding_exception('Unexpected program courseset status found');
                }
            }
        }

        if ($field == 'nextsetoperator') {
            switch ($courseset->nextsetoperator) {
                case NEXTSETOPERATOR_THEN:
                    return 'THEN';
                    break;
                case NEXTSETOPERATOR_AND:
                    return 'AND';
                    break;
                case NEXTSETOPERATOR_OR:
                    return 'OR';
                    break;
                default:
                    // This should be the last courseset.
                    return null;
            }
        }

        /**
         * Criteria string code copied from the display() function in program_courseset.class.php
         */
        if ($field == 'criteria') {
            switch ($courseset->completiontype) {
                case COMPLETIONTYPE_ALL:
                    return [get_string('completeallcourses', 'totara_program')];
                    break;
                case COMPLETIONTYPE_ANY:
                    return [get_string('completeanycourse', 'totara_program')];
                    break;
                case COMPLETIONTYPE_SOME:
                    $str = new \stdClass();
                    $str->mincourses = $courseset->mincourses;
                    $str->sumfield = '';

                    if ($coursecustomfield = $DB->get_record('course_info_field', array('id' => $courseset->coursesumfield))) {
                        $str->sumfield = format_string($coursecustomfield->fullname);
                    }
                    $str->sumfieldtotal = $courseset->coursesumfieldtotal;

                    $criteria = [];
                    if (!empty($str->mincourses) && !empty($str->sumfield) && !empty($str->sumfieldtotal)) {
                        $criteria[] = get_string('completemincoursesminsum', 'totara_program', $str);
                    } else if (!empty($courseset->mincourses)) {
                        $criteria[] = get_string('completemincourses', 'totara_program', $str);
                    } else {
                        $criteria[] = get_string('completeminsumfield', 'totara_program', $str);
                    }

                    if (!empty($str->sumfield)) {
                        // Add information about the criteria.
                        $criteria[] = get_string('criteriacompletioncourseset', 'totara_program', $str->sumfield);
                    }

                    return $criteria;
                    break;
                case COMPLETIONTYPE_OPTIONAL:
                    return [get_string('completeoptionalcourses', 'totara_program')];
                    break;
            }
        }

        $format = $args['format'] ?? null;
        if (!self::authorize($field, $format, $ec)) {
            return null;
        }

        $formatter = new courseset_formatter($courseset, $context);
        $formatted = $formatter->format($field, $format);

        return $formatted;
    }

    public static function authorize(string $field, ?string $format, execution_context $ec) {
        // Permission to see RAW formatted string fields
        if (in_array($field, ['label']) && $format == format::FORMAT_RAW) {
            return has_capability('totara/program:configurecontent', $ec->get_relevant_context());
        }
        return true;
    }
}
