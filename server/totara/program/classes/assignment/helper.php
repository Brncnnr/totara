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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_program
 */

namespace totara_program\assignment;

final class helper {

    const MAX_RESULTS = 100;

    /**
     * Get all assignment records for a program
     *
     * @param int $programid
     *
     * @return array All assignments for a program (or certification)
     */
    public static function get_assignments(int $programid): array {
        global $DB;

        $types_without_ui = self::get_types_with_ui(true);
        if ($types_without_ui) {
            list($excludeuisql, $excludeuiparams) = $DB->get_in_or_equal(array_flip($types_without_ui), SQL_PARAMS_NAMED, 'param', false);
            $sql = "programid = :programid AND assignmenttype {$excludeuisql}";
            $params = array_merge($excludeuiparams, ['programid' => $programid]);
            $assignment_records = $DB->get_records_select('prog_assignment', $sql, $params, '', 'id,assignmenttype');
        } else {
            $assignment_records = $DB->get_records('prog_assignment', ['programid' => $programid], '', 'id,assignmenttype');
        }

        $assignments = [];

        if (count($assignment_records) > self::MAX_RESULTS) {
            // Too many assignments
            return [
                'assignments' => $assignments,
                'toomany' => true
            ];
        }

        foreach ($assignment_records as $record) {
            $assignment = base::create_from_record($record);
            $assignments[] = $assignment;
        }

        \core_collator::asort_objects_by_method($assignments, 'get_name', \core_collator::SORT_NATURAL);

        return [
            'assignments' => $assignments,
            'toomany' => false
        ];
    }

    /**
     * Helper function to get the types and the
     * IDs associated with them
     * Note: These are copied from the constants in totara/program/program_assignments.class.php
     * and need to be kept in line with them.
     *
     * @return array
     */
    public static function get_types(): array {

        $assignmenttypes = [
            1 => 'organisation',
            2 => 'position',
            3 => 'cohort',
            // 4 is unused
            5 => 'individual',
            6 => 'manager',
            7 => 'plan'
        ];

        return $assignmenttypes;
    }

    /**
     * Get the type name
     *
     * @param int $typeid
     * @return string
     */
    public static function get_type_name(int $typeid) : string {
        $types = self::get_types();

        if (!isset($types[$typeid])) {
            throw new \coding_exception('Assignment type not found');
        }

        return $types[$typeid];
    }

    /**
     * Get the string for an assignment type
     *
     * @param int $typeid
     *
     * @return string
     */
    public static function get_type_string(int $typeid): string {
        return get_string(self::get_type_name($typeid), 'totara_program');
    }

    /**
     * Get all assignment types that have a user interface
     *
     * @param bool $without Return types without a user interface
     * @return array
     */
    public static function get_types_with_ui(bool $without = false) : array {
        static $types_with_ui = null;

        if (is_null($types_with_ui) || PHPUNIT_TEST) {
            $types_with_ui = [];
            foreach (self::get_types() as $typeid => $name) {
                $class = "\\totara_program\assignment\\" . $name;
                if ($class::show_in_ui()) {
                    $types_with_ui[$typeid] = $name;
                }
            }
        }

        if ($without) {
            return array_diff(self::get_types(), $types_with_ui);
        }

        return $types_with_ui;
    }

    /**
     * Create string to show on program assignments page
     *
     * @param \stdClass $data
     *
     * @return string
     */
    public static function build_status_string(\stdClass $data): string {
        $programstatusstring = get_string($data->statusstr, $data->certifid ? 'totara_certification' : 'totara_program');

        if (($data->statusstr === 'notduetostartuntil') or ($data->statusstr === 'nolongeravailabletolearners')) {
            $statusmessage = $programstatusstring;
        } else {
            $learnerinfo = \html_writer::empty_tag('br') . \html_writer::start_tag('span', array('class' => 'assignmentcount'));
            $learnerinfo .= get_string('learnersassignedbreakdown', 'totara_program', $data);
            $learnerinfo .= \html_writer::end_tag('span');

            $coursevisibilityinfo = \html_writer::empty_tag('br') . \html_writer::start_tag('span');
            if ($data->audiencevisibilitywarning) {
                $coursevisibilityinfo .= get_string('audiencevisibilityconflictmessage', 'totara_program');
            }
            if ($data->assignmentsdeferred) {
                $coursevisibilityinfo .= get_string('assignmentsdeferred', 'totara_program');
            }
            $coursevisibilityinfo .= \html_writer::end_tag('span');

            $statusmessage = $programstatusstring . $learnerinfo . $coursevisibilityinfo;
        }

        return $statusmessage;
    }

    /**
     * Determine if the current user has necessary permissions to update
     * program assignments
     *
     * @param int $programid
     * @param int $typeid The assignment type id
     *
     * @return bool
     */
    public static function can_update(int $programid, int $typeid): bool {
        global $CFG;

        // Check permission on the assignment type.
        $class = "\\totara_program\assignment\\" . self::get_type_name($typeid);
        if (!$class::can_be_updated($programid)) {
            return false;
        }

        // Needed for program class
        require_once($CFG->dirroot . '/totara/program/program.class.php');

        // Cannot update if no longer available.
        $program = new \program($programid);
        if ($program->has_expired()) {
            return false;
        }

        return true;
    }
}
