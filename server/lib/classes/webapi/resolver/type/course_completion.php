<?php
/**
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package core
 */

namespace core\webapi\resolver\type;

use core\webapi\execution_context;
use core_course\formatter\course_completion_formatter;
use core\format;
use core\webapi\type_resolver;
use context_course;
use coursecat;

/**
 * Warning: This script does not explicitly reject guest users, since we want to show a record with no progress for them.
 * However do not not use anything here which would cause aggregation as it will create erroneous records for unenrolled users,
 * see the progress field resolver for more info.
 */
class course_completion extends type_resolver {

    public static function resolve(string $field, $ccomp, array $args, execution_context $ec) {
        global $DB, $USER, $CFG;

        require_once($CFG->libdir . '/grade/grade_grade.php');
        require_once($CFG->libdir . '/grade/grade_item.php');

        if (!$ccomp instanceof \completion_completion) {
            throw new \coding_exception('Only completion_completion objects are accepted: ' . gettype($ccomp));
        }

        $format = $args['format'] ?? null;
        $course = get_course($ccomp->course);
        $info = new \completion_info($course);

        // If completion is disabled at site or course level, theres nothing to return.
        if (!$info->is_enabled()) {
            return false;
        }

        $data = new \stdClass();
        if ($field == 'id') {
            // Override null ids for mobile caching (all empty records are the same).
            if (empty($ccomp->id)) {
                $data->id = 0;
            } else {
                $data->id = $ccomp->id;
            }
        }

        /**
         * The raw value accompanying the statuskey. e.g. 50 => complete
         */
        if ($field == 'status') {
            return $ccomp->status;
        }

        /**
         * From completion/completion_completion.php, these can be used in get_string for longer descriptions.
         */
        if ($field == 'statuskey') {
            return \completion_completion::get_status($ccomp);
        }

        if ($field == 'progress') {
            // NOTE: get_percentagecomplete() calls aggregate before calculating, updating records in the course_completions table,
            // and creating new records if they dont already exist. Meaning if you call this for unenrolled "guest" users to see their progress
            // is zero then this will ceate a completion record for them even if they are unenrolled. Best to return 0 for unenrolled users and
            // avoid the extra records.
            if ($ccomp->id == null) {
                return 0;
            } else {
                return $ccomp->get_percentagecomplete();
            }
        }

        if ($field == 'timecompleted') {

            if (empty($ccomp->timecompleted) || $ccomp->timecompleted == -1) {
                return null; // For consistency.
            } else {
                $data->timecompleted = $ccomp->timecompleted;
            }
        }

        $gradefields = ['gradefinal', 'grademax', 'gradepercentage'];
        if (in_array($field, $gradefields)) {
            $course_item = \grade_item::fetch_course_item($course->id);
            $grade = new \grade_grade(array('itemid' => $course_item->id, 'userid' => $USER->id));

            if ($field == 'gradefinal') {
                $data->gradefinal = $grade->finalgrade;
            }

            if ($field == 'grademax') {
                $data->grademax = $grade->rawgrademax;
            }

            if ($field == 'gradepercentage') {
               $data->gradepercentage = ((float)$grade->finalgrade / (float)$grade->rawgrademax) * 100;
            }
        }

        $formatter = new course_completion_formatter($data, $ec->get_relevant_context());
        return $formatter->format($field, $format);
    }
}
