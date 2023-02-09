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
 * @package totara_certification
 * @author David Curry <david.curry@totaralearning.com>
 */

namespace totara_certification\webapi\resolver\type;

use totara_certification\formatter\certification_formatter;
use core\format;
use context_program;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use coding_exception;
use coursecat;

/**
 * Certification type
 *
 * Note: It is the responsibility of the query to ensure the user is permitted to see a certification
 */
class certification extends type_resolver {

    /**
     * Resolve certification fields
     *
     * @param string $field
     * @param mixed $certification
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $certification, array $args, execution_context $ec) {
        global $DB, $CFG, $USER;

        require_once($CFG->dirroot . '/totara/program/lib.php');
        require_once($CFG->dirroot . '/totara/program/program.class.php');

        if (!$certification instanceof \program || empty($certification->certifid)) {
            // Note: Currently this is accepting program objects, but only when certifid <> 0.
            throw new \coding_exception('Only certification program objects are accepted: ' . gettype($certification));
        }

        $format = $args['format'] ?? null;
        $program_context = context_program::instance($certification->id);

        if (!self::authorize($field, $format, $program_context)) {
            return null;
        }

        $datefields = ['availablefrom', 'availableuntil'];
        if (in_array($field, $datefields) && empty($certification->{$field})) {
            // Highly unlikely this is set to 1/1/1970, return null for notset dates.
            return null;
        }

        if ($field == 'summaryformat') {
            // Certifications don't actually have a summaryformat, they are just always HTML.
            return 'HTML';
        }

        if ($field == 'category') {
            return \coursecat::get($certification->category);
        }

        if ($field == 'coursesets') {
            $content = $certification->get_content();
            return $content->get_course_sets();
        }

        // Include certification specific fields here.
        if ($field == 'activeperiod') {
            $certif = $DB->get_record('certif', ['id' => $certification->certifid]);

            // Note: this is saved as "1 Month" in the database and won't be translated...
            $certification->activeperiod = $certif->activeperiod;
        }

        $duefields = ['duedate', 'duedate_state'];
        if ($field == 'completion' || in_array($field, $duefields)) {
            // Note: This loads the duedate as well so I've combined them here,
            // however completion is it's own object and duedate is part of the program.
            list($certcompletion, $progcompletion) = certif_load_completion($certification->id, $USER->id, false);
            if (empty($certcompletion) || empty($progcompletion)) {
                return null; // No completion information for this user.
            } else {

                if ($field == 'duedate') {
                    if (!empty($progcompletion->timedue) && $progcompletion->timedue != -1) {
                        $program->duedate = $completion->timedue;
                    } else {
                        return null;
                    }
                }

                // Note: These fields define the state of a notification and shouldn't be translated.
                if ($field == 'duedate_state') {
                    $now =  time();

                    if (empty($progcompletion->timedue) || $progcompletion->timedue == -1) {
                        return '';
                    } else if ($progcompletion->timedue < $now) {
                        // Program overdue.
                        return 'danger';
                    } else {
                        $days = floor(($progcompletion->timedue - $now) / DAYSECS);
                        if ($days == 0) {
                            // Program due immediately.
                            return 'danger';
                        } else if ($days > 0 && $days < 10) {
                            // Program due in the next 1-10 days.
                            return 'warning';
                        } else {
                            return '';
                        }
                    }
                }

                if ($field == 'completion') {
                    // Hand through all the completion information.
                    return [$certcompletion, $progcompletion];
                }
            }
        }

        $formatter = new certification_formatter($certification, $program_context);
        $formatted = $formatter->format($field, $format);

        // For mobile execution context, rewrite pluginfile urls in description and image_src fields.
        // This is clearly a hack, please suggest something more elegant.
        if (is_a($ec, 'totara_mobile\webapi\execution_context') && in_array($field, ['description', 'image_src'])) {
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
        if (in_array($field, ['summary']) && $format == format::FORMAT_RAW) {
            return has_capability('totara/program:configuredetails', $context);
        }
        return true;
    }
}
