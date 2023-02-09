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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_scorm
 */

namespace mod_scorm\webapi\resolver\type;

use core\webapi\execution_context;
use mod_scorm\formatter\current_status_formatter;

/**
 * SCORM activity completion info
 */
class current_status extends \core\webapi\type_resolver {
    public static function resolve(string $field, $scorm, array $args, execution_context $ec) {
        global $DB, $USER, $CFG;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->libdir . '/grade/grade_grade.php');
        require_once($CFG->libdir . '/grade/grade_item.php');

        if (empty($scorm) or empty($scorm->id) or empty($scorm->course)) {
            throw new \coding_exception('Invalid SCORM current status request');
        }

        $format = $args['format'] ?? null;
        $context = $ec->get_relevant_context();
        $cm = get_coursemodule_from_instance('scorm', $scorm->id, $scorm->course, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $scorm->course], '*', MUST_EXIST);

        $data = new \stdClass();

        if ($field === 'maxattempt') {
            $data->maxattempt = $scorm->maxattempt > 0 ? $scorm->maxattempt : null;
        }

        if ($field === 'attempts_current') {
            // Find current attempt number.
            $data->attempts_current = scorm_get_attempt_count($USER->id, $scorm);
        }

        if ($field === 'completion') {
            // Translate completion property to string
            switch ($cm->completion) {
                case COMPLETION_TRACKING_NONE:
                    $data->completion = 'tracking_none';
                    break;
                case COMPLETION_TRACKING_MANUAL:
                    $data->completion = 'tracking_manual';
                    break;
                case COMPLETION_TRACKING_AUTOMATIC:
                    $data->completion = 'tracking_automatic';
                    break;
                default:
                    $data->completion = 'unknown';
                    break;
            }
        }

        if ($field === 'completionview') {
            $data->completionview = $cm->completionview;
        }

        if ($field === 'completionstatusrequired' || $field === 'completionscorerequired') {
            $data->{$field} = $scorm->{$field} != '' ? $scorm->{$field} : null;
        }

        if ($field === 'completionstatusallscos') {
            $data->completionstatusallscos = $scorm->completionstatusallscos;
        }

        if ($field === 'completionstatus') {
            // Generate completion status
            $completioninfo = new \completion_info($course);
            $completiondata = $completioninfo->get_data($cm);
            switch ($completiondata->completionstate) {
                case COMPLETION_INCOMPLETE:
                    $data->completionstatus = 'incomplete';
                    break;
                case COMPLETION_COMPLETE:
                    $data->completionstatus = 'complete';
                    break;
                case COMPLETION_COMPLETE_PASS:
                    $data->completionstatus = 'complete_pass';
                    break;
                case COMPLETION_COMPLETE_FAIL:
                    $data->completionstatus = 'complete_fail';
                    break;
                default:
                    $data->completionstatus = 'unknown';
                    break;
            }
        }

        if (in_array($field, ['gradefinal', 'grademax', 'gradepercentage'])) {
            // Find grade
            $item = \grade_item::fetch([
                'itemtype' => 'mod',
                'itemmodule' => 'scorm',
                'iteminstance' => $scorm->id,
            ]);
            $grade = new \grade_grade(array('itemid' => $item->id, 'userid' => $USER->id));
            $data->gradefinal = $grade->finalgrade;
            $data->grademax = $grade->rawgrademax;
            $data->gradepercentage = ((float)$grade->finalgrade / (float)$grade->rawgrademax) * 100;
        }

        $formatter = new current_status_formatter($data, $context);
        $formatted = $formatter->format($field, $format);

        return $formatted;
    }
}
