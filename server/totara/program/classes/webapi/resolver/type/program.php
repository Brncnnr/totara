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
 * @package totara_program
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 */

namespace totara_program\webapi\resolver\type;

use totara_program\formatter\program_formatter;
use core\format;
use context_program;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use coding_exception;
use coursecat;

/**
 * Program type
 *
 * Note: It is the responsibility of the query to ensure the user is permitted to see a program
 */
class program extends type_resolver {

    /**
     * Resolve program fields
     *
     * @param string $field
     * @param mixed $program
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $program, array $args, execution_context $ec) {
        global $CFG, $USER;

        require_once($CFG->dirroot . '/totara/program/lib.php');
        require_once($CFG->dirroot . '/totara/program/program.class.php');

        if (!$program instanceof \program) {
             throw new \coding_exception('Only program objects are accepted: ' . gettype($program));
        }

        $format = $args['format'] ?? null;
        $program_context = context_program::instance($program->id);

        if (!self::authorize($field, $format, $program_context)) {
            return null;
        }

        $datefields = ['availablefrom', 'availableuntil'];
        if (in_array($field, $datefields) && empty($program->{$field})) {
            // Highly unlikely this is set to 1/1/1970, return null for notset dates.
            return null;
        }

        if ($field == 'summaryformat') {
            // Programs don't actually have a summaryformat, they are just always HTML.
            return 'HTML';
        }

        if ($field == 'category') {
            return coursecat::get($program->category);
        }

        if ($field == 'coursesets') {
            $content = $program->get_content();

            return $content->get_course_sets();
        }

        $duefields = ['duedate', 'duedate_state'];
        if ($field == 'completion' || in_array($field, $duefields)) {
            // Note: This loads the duedate as well so I've combined them here,
            // however completion is it's own object and duedate is part of the program.
            if ($completion = prog_load_completion($program->id, $USER->id, false)) {

                if ($field == 'duedate') {
                    if (!empty($completion->timedue) && $completion->timedue != -1) {
                        $program->duedate = $completion->timedue;
                    } else {
                        return null;
                    }
                }

                // Note: These fields define the state of a notification and shouldn't be translated.
                if ($field == 'duedate_state') {
                    $now =  time();

                    if (empty($completion->timedue) || $completion->timedue == -1) {
                        return '';
                    } else if ($completion->timedue < $now) {
                        // Program overdue.
                        return 'warning';
                    } else {
                        $days = floor(($completion->timedue - $now) / DAYSECS);
                        if ($days == 0) {
                            // Program due immediately.
                            return 'warning';
                        } else if ($days > 0 && $days < 10) {
                            // Program due in the next 1-10 days.
                            return 'warning';
                        } else {
                            return '';
                        }
                    }
                }

                if ($field == 'completion') {
                    return $completion;
                }

            } else {
                return null;
            }
        }

        $formatter = new program_formatter($program, $program_context);
        $formatted = $formatter->format($field, $format);

        // For mobile execution context, rewrite pluginfile urls in description and image_src fields.
        // This is clearly a hack, please suggest something more elegant.
        if (is_a($ec, 'totara_mobile\webapi\execution_context') && in_array($field, ['description', 'image'])) {
            $formatted = str_replace($CFG->wwwroot . '/pluginfile.php', $CFG->wwwroot . '/totara/mobile/pluginfile.php', $formatted);
        }

        return $formatted;
    }

    public static function authorize(string $field, ?string $format, context_program $context) {
        // Permission to see RAW formatted string fields
        if (in_array($field, ['fullname', 'shortname']) && $format == format::FORMAT_RAW) {
            return has_capability('totara/program:configuredetails', $context);
        }
        // Permission to see RAW formatted text fields
        if (in_array($field, ['summary', 'endnote']) && $format == format::FORMAT_RAW) {
            return has_capability('totara/program:configuredetails', $context);
        }
        return true;
    }
}
