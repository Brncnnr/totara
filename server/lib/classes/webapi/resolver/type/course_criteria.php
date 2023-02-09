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
 * @author David Curry <david.curry@totaralearning.com>
 * @package core
 */

namespace core\webapi\resolver\type;

use core\webapi\execution_context;
use core_course\formatter\course_criteria_formatter;
use core\format;
use core\webapi\type_resolver;
use context_course;
use coursecat;

class course_criteria extends type_resolver {

    public static function resolve(string $field, $completion, array $args, execution_context $ec) {
        global $CFG;

        require_once($CFG->libdir . '/completionlib.php');

        if (!$completion instanceof \completion_criteria_completion) {
            throw new \coding_exception('Only completion_criteria_completion objects are accepted: ' . gettype($completion));
        }

        $format = $args['format'] ?? null;
        $criteria = $completion->get_criteria();
        $details = (object) $criteria->get_details($completion);

        if ($field == 'id') {
            $details->id = $criteria->id;
        }

        if ($field == 'typeaggregation') {
            // Since this has to be set in the course type resolver we should account for others not setting it.
            if (!isset($completion->typeaggregation)) {
                return null;
            }

            if ($completion->typeaggregation == COMPLETION_AGGREGATION_ALL) {
                $details->typeaggregation = get_string('all', 'completion');
            } else {
                $details->typeaggregation = get_string('any', 'completion');
            }
        }

        if ($field == 'complete') {
            $details->complete = $completion->is_complete();
        }

        if ($field == 'completiondate') {
            if (empty($completion->timecompleted)) {
                return null;
            }
            $details->completiondate = $completion->timecompleted;
        }

        $formatter = new course_criteria_formatter($details, $ec->get_relevant_context());
        return $formatter->format($field, $format);
    }
}
