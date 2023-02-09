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
 * @package totara_certification
 * @author David Curry <david.curry@totaralearning.com>
 */

namespace totara_certification\webapi\resolver\type;

use totara_certification\formatter\completion_formatter;
use context_program;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use stdClass;
use coding_exception;
use core\format;

/**
 * Certification completion type
 */
class completion extends type_resolver {

    /**
     * Resolve completion fields
     *
     * @param string $field
     * @param mixed $completion - expected to be the output of certif_load_completion()
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $completion, array $args, execution_context $ec) {
        global $CFG, $USER;

        require_once($CFG->dirroot . '/totara/program/lib.php');
        require_once($CFG->dirroot . '/totara/program/program.class.php');

        // We expect $completion to be [$certcompletion, $progcompletion].
        if (empty($completion) || !is_array($completion) || count($completion) != 2) {
            throw new coding_exception('Expected $completion to match certif_load_completion() output, recieved: ' . gettype($completion));
        }

        $certcompletion = array_shift($completion);
        if (!$certcompletion instanceof stdClass || empty($certcompletion->id)) {
            throw new coding_exception('invalid certification completion dataobject');
        }

        $progcompletion = array_shift($completion);
        if (!$progcompletion instanceof stdClass || !isset($progcompletion->coursesetid) || $progcompletion->coursesetid != 0) {
            throw new coding_exception('invalid certification prog_completion dataobject');
        }

        // You are only allowed to see your own completion in the certification.
        if (empty($certcompletion->userid) || $USER->id != $certcompletion->userid) {
            return null;
        }

        if ($field == 'statuskey') {
            switch ($certcompletion->status) {
                case CERTIFSTATUS_ASSIGNED:
                    return 'assigned';
                    break;
                case CERTIFSTATUS_INPROGRESS:
                    return 'inprogress';
                    break;
                case CERTIFSTATUS_COMPLETED:
                    return 'completed';
                    break;
                case CERTIFSTATUS_EXPIRED:
                    return 'expired';
                    break;
                default:
                    throw new coding_exception('Unexpected or unset certification status found');
            }
        }

        if ($field == 'renewalstatuskey') {
            switch ($certcompletion->renewalstatus) {
                case CERTIFRENEWALSTATUS_NOTDUE:
                    return 'notdue';
                    break;
                case CERTIFRENEWALSTATUS_DUE:
                    return 'dueforrenewal';
                    break;
                case CERTIFRENEWALSTATUS_EXPIRED:
                    return 'expired';
                    break;
                default:
                    throw new coding_exception('Unexpected certification renewalstatus found');
            }
        }

        if ($field == 'progress') {
            $certcompletion->progress = totara_program_get_user_percentage_complete($progcompletion->programid, $USER->id);
        }

        $format = $args['format'] ?? null;
        $program_context = context_program::instance($progcompletion->programid);

        $formatter = new completion_formatter($certcompletion, $program_context);
        return $formatter->format($field, $format);
    }
}
