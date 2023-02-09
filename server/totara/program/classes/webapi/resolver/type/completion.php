<?php

/**
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
 * @package totara_program
 * @author David Curry <david.curry@totaralearning.com>
 */

namespace totara_program\webapi\resolver\type;

use totara_program\formatter\completion_formatter;
use context_program;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use stdClass;
use coding_exception;
use core\format;

/**
 * Program completion type
 */
class completion extends type_resolver {

    /**
     * Resolve completion fields
     *
     * @param string $field
     * @param mixed $completion - expected to be the output of prog_load_completion()
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $completion, array $args, execution_context $ec) {
        global $CFG, $USER;

        require_once($CFG->dirroot . '/totara/program/lib.php');
        require_once($CFG->dirroot . '/totara/program/program.class.php');

        if (!$completion instanceof stdClass) {
            throw new coding_exception('Expected \stdClass for $completion, recieved: ' . gettype($completion));
        }

        if (empty($completion->userid) || $USER->id != $completion->userid) {
            return null;
        }

        if (!isset($completion->coursesetid) || $completion->coursesetid != 0) {
            throw new coding_exception('Expected field "coursesetid" missing from $completion record, or recieved unexpected value');
        }

        if ($field == 'statuskey') {
            switch ($completion->status) {
                case STATUS_PROGRAM_INCOMPLETE:
                    return 'incomplete';
                    break;
                case STATUS_PROGRAM_COMPLETE:
                    return 'complete';
                    break;
                default:
                    throw new coding_exception('Unexpected program status found');
            }
        }

        if ($field == 'progress') {
            $completion->progress = totara_program_get_user_percentage_complete($completion->programid, $USER->id);
        }

        $format = $args['format'] ?? null;
        $program_context = context_program::instance($completion->programid);

        $formatter = new completion_formatter($completion, $program_context);
        return $formatter->format($field, $format);
    }
}
