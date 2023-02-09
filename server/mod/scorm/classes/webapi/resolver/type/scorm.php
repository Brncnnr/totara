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
 * @author David Curry <david.curry@totaralearning.com>
 * @package mod_scorm
 */

namespace mod_scorm\webapi\resolver\type;

use core\format;
use core\webapi\execution_context;
use mod_scorm\formatter\scorm_formatter;

/**
 * Basic SCORM details type
 */
class scorm extends \core\webapi\type_resolver {
    public static function resolve(string $field, $scorm, array $args, execution_context $ec) {
        global $DB, $USER, $CFG;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/mod/scorm/locallib.php');

        if (empty($scorm) || empty($scorm->id) || empty($scorm->course) || empty($scorm->version)) {
            throw new \coding_exception('Invalid SCORM request');
        }

        $version = $scorm->version;
        if (!file_exists($CFG->dirroot.'/mod/scorm/datamodels/'.$version.'lib.php')) {
            $version = 'scorm_12';
        }
        require_once($CFG->dirroot.'/mod/scorm/datamodels/'.$version.'lib.php');

        $format = $args['format'] ?? null;
        $course = get_course($scorm->course);
        $context = $ec->get_relevant_context();
        $cm = false;

        // Translate from DB fields or derived properties to GraphQL properties.
        $data = new \stdClass();

        // Courseid comes from course.
        if ($field === 'courseid') {
            $data->courseid = $course->id;
        }

        if ($field === 'showgrades') {
            return !empty($course->showgrades);
        }

        /**
         * A URL to download the scorm package
         */
        if ($field === 'package_url') {
            if ($scorm->scormtype == 'local') {
                $fs = get_file_storage();
                $files = $fs->get_area_files($context->id, 'mod_scorm', 'package', 0, "timemodified DESC", false);
                if ($files) {
                    // URL defaults to forcedownload, that's the last boolean in the call below if we need to change that.
                    $url = \moodle_url::make_pluginfile_url($context->id, 'mod_scorm', 'package', 0, '/', $scorm->reference, true);
                    $data->package_url = $url->out();
                } else {
                    return null;
                }
            } else {
                // Return null for now, but perhaps later we can return $scorm->reference containing the external url?
                return null;
            }
        }

        // Transform the format field from the constants to a core_format string.
        if ($field == 'introformat') {
            return format::from_moodle($scorm->introformat);
        }

        /**
         * The maximum amount of attempts per user for the scorm
         * TODO: This field is apparently null if 0 for the mobile implementation? Or why?
         */
        if ($field === 'maxattempt') {
            $data->maxattempt = $scorm->maxattempt > 0 ? $scorm->maxattempt : null;
        }

        /**
         * The current users number of attempts of the scorm
         */
        if ($field === 'attempts_current') {
            $data->attempts_current = scorm_get_attempt_count($USER->id, $scorm);
        }

        // Left over from the initial scorm query, unsure whether to keep.
        if ($field === 'calculated_grade') {
            $calculatedgrade = scorm_grade_user($scorm, $USER->id);
            if ($scorm->grademethod !== GRADESCOES && !empty($scorm->maxgrade)) {
                $calculatedgrade = $calculatedgrade / $scorm->maxgrade;
                $calculatedgrade = number_format($calculatedgrade * 100, 0) .'%';
            }
            $data->calculated_grade = $calculatedgrade;
        }

        // URL for new attempt in SCORM player.
        if ($field === 'launch_url') {
            $data->launch_url = null;
            list($available, $warnings) = scorm_get_availability_status($scorm);
            if ($available) {
                $attemptcount = scorm_get_attempt_count($USER->id, $scorm);
                if ($scorm->maxattempt < 1 or $attemptcount <= $scorm->maxattempt) {
                    if (empty($cm)) {
                        $cm = get_coursemodule_from_instance("scorm", $scorm->id, $scorm->course, false, MUST_EXIST);
                    }
                    if (has_capability('mod/scorm:launch', $context)) {
                        $data->launch_url = $CFG->wwwroot . '/mod/scorm/player.php?mode=normal&newattempt=on&cm=' . $cm->id . '&scoid=0';
                    }
                }
            }
        }

        if ($field === 'repeat_url') {
            $data->repeat_url = null;
            list($available, $warnings) = scorm_get_availability_status($scorm);
            if ($available) {
                $attemptcount = scorm_get_attempt_count($USER->id, $scorm);
                if ($attemptcount >= 1) {
                    if (empty($cm)) {
                        $cm = get_coursemodule_from_instance("scorm", $scorm->id, $scorm->course, false, MUST_EXIST);
                    }
                    if (has_capability('mod/scorm:launch', $context)) {
                        $data->repeat_url = $CFG->wwwroot . '/mod/scorm/player.php?mode=normal&newattempt=off&cm=' . $cm->id . '&scoid=0';
                    }
                }
            }
        }

        // Completion-related fields from course_module.
        if ($field === 'completion' or $field === 'completionview') {
            if (empty($cm)) {
                $cm = get_coursemodule_from_instance("scorm", $scorm->id, $scorm->course, false, MUST_EXIST);
            }
            $data->{$field} = $cm->{$field};
        }

        if ($field === 'completionstatusrequired' or $field === 'completionscorerequired') {
            $data->{$field} = $scorm->{$field} != '' ? $scorm->{$field} : null;
        }

        /**
         * Default settings for new attempts.
         */
        if ($field == 'attempt_defaults') {
            $def = new \stdClass();
            if ($scoes = $DB->get_records('scorm_scoes', array('scorm' => $scorm->id), 'sortorder, id')) {
                $userdata = new \stdClass();
                $attempt = scorm_get_attempt_count($USER->id, $scorm);
                if (empty($scorm->maxattempt) || $attempt < $scorm->maxattempt) {

                    // Drop keys so that it is a simple array.
                    $scoes = array_values($scoes);
                    foreach ($scoes as $sco) {
                        $def->{($sco->identifier)} = new \stdClass();
                        $userdata->{($sco->identifier)} = new \stdClass();
                        $def->{($sco->identifier)} = \get_scorm_default($userdata->{($sco->identifier)}, $scorm, $sco->id, ++$attempt, 'normal');
                    }
                }
            }

            $data->attempt_defaults = json_encode($def);
        }

        // Attempts.
        if ($field === 'attempts') {
            // Try to add an additional attempt (to be the next attempt) to get a clean set of default properties...
            $attemptcount = scorm_get_attempt_count($USER->id, $scorm);
            // ... unless the maximum number of attempts has been reached.
            if ($scorm->maxattempt != 0 && $attemptcount >= $scorm->maxattempt) {
                $attemptcount = $scorm->maxattempt;
            }
            $attempts = [];
            for ($attempt = 1; $attempt <= $attemptcount; $attempt++) {
                $att = new \stdClass();
                $att->attempt = $attempt;
                $att->scormid = $scorm->id;
                $attempts[] = $att;
            }
            $data->attempts = $attempts;
        }

        // Catch-all for database properties -> GraphQL properties. Using property_exists() in case value is null.
        if (!property_exists($data, $field) && property_exists($scorm, $field)) {
            $data->{$field} = $scorm->{$field};
        }

        $formatter = new scorm_formatter($data, $context);
        $formatted = $formatter->format($field, $format);

        // For mobile execution context, rewrite pluginfile urls in description and image_src fields.
        // This is clearly a hack, please suggest something more elegant.
        if (is_a($ec, 'totara_mobile\webapi\execution_context') && in_array($field, ['intro', 'package_url', 'launch_url', 'repeat_url'])) {
            // Prevent passing null as input parameter in PHP 8.1
            $formatted = str_replace($CFG->wwwroot . '/pluginfile.php', $CFG->wwwroot . '/totara/mobile/pluginfile.php', $formatted ?? '');
        }

        return $formatted;
    }
}
