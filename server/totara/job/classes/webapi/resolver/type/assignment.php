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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_job
 */

namespace totara_job\webapi\resolver\type;

use core\date_format;
use core\format;
use core\webapi\execution_context;
use core\webapi\formatter\field\date_field_formatter;
use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\field\text_field_formatter;
use core\webapi\type_resolver;
use totara_core\advanced_feature;
use totara_job\job_assignment;

/**
 * Job assignment type
 *
 * Please be aware that it is the responsibility of the query to ensure that the user is allowed to
 * see this job assignment.
 * The following fields are protected (nullable) by this type class:
 *   - description: If RAW is specified then raw data will only be returned IF the user holds the
 *     permissions required to edit the job assignment.
 */
class assignment extends type_resolver {

    /**
     * Resolves a job assignment field.
     *
     * @param string $field
     * @param job_assignment $job
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $job, array $args, execution_context $ec) {

        if (!$job instanceof job_assignment) {
            throw new \coding_exception('Only job_assignment objects are accepted: ' . gettype($job));
        }

        // Basic field handling, these fields require no formatting, but may or may not be nullable.
        // The key is the field, and the value is whether the field is nullable or not.
        $basicdata = [
            'id' => false,
            'userid' => false,
            'idnumber' => false,
            'managerjaid' => true,
            'tempmanagerjaid' => true,
            'appraiserid' => true
        ];
        if (isset($basicdata[$field])) {
            if (!isset($job->{$field})) {
                if ($basicdata[$field]) {
                    // Field not set and nullable
                    return null;
                }
                throw new \coding_exception('Expected value, but was not found and was not nullable.', $field);
            }
            return $job->{$field};
        }

        switch ($field) {
            case 'user':
                return self::get_user($job->userid);
            case 'fullname':
            case 'shortname':
                $format = $args['format'] ?? format::FORMAT_PLAIN;
                return self::format_string($job, $field, $format, $ec);
            case 'description':
                $value = $job->description ?? null;
                if ($value === null) {
                    return null;
                }

                $format = $args['format'] ?? format::FORMAT_HTML;
                $context = \context_system::instance();
                $formatter = new text_field_formatter($format, $context);
                $formatter->set_pluginfile_url_options($context, 'totara_job', 'job_assignment', $job->id);

                if ($format === format::FORMAT_RAW && !self::can_edit($job)) {
                    // They do not have permission to edit, therefor they cannot see the raw.
                    return null;
                }

                return $formatter->format($value);
            case 'startdate':
            case 'enddate':
            case 'tempmanagerexpirydate':
                if (empty($job->{$field})) {
                    return null;
                }
                $format = $args['format'] ?? date_format::FORMAT_TIMESTAMP;
                return (new date_field_formatter($format, \context_system::instance()))->format($job->{$field});
            case 'positionid':
                if (empty($job->positionid) || !self::can_view_position($job)) {
                    return null;
                }
                return $job->positionid;
            case 'position':
                if (empty($job->positionid) || !self::can_view_position($job)) {
                    return null;
                }
                return self::get_position($job->positionid);
            case 'organisationid':
                if (empty($job->organisationid) || !self::can_view_organisation($job)) {
                    return null;
                }
                return $job->organisationid;
            case 'organisation':
                if (empty($job->organisationid) || !self::can_view_organisation($job)) {
                    return null;
                }
                return self::get_organisation($job->organisationid);
            case 'managerja':
                if (empty($job->managerjaid)) {
                    return null;
                }
                return job_assignment::get_with_id($job->managerjaid);
            case 'tempmanagerja':
                if (empty($job->tempmanagerjaid)) {
                    return null;
                }
                return job_assignment::get_with_id($job->tempmanagerjaid);
            case 'appraiser':
                if (empty($job->appraiserid)) {
                    return null;
                }
                return self::get_user($job->appraiserid);
            case 'staffcount':
                return job_assignment::get_count_managed_users($job->id);
            case 'tempstaffcount':
                return job_assignment::get_count_temp_managed_users($job->id);
        }

        throw new \coding_exception('Unknown field', $field);
    }

    /**
     * Can the user view this jobs positions.
     *
     * @param job_assignment $job
     * @return bool
     */
    private static function can_view_position(job_assignment $job): bool {
        if (advanced_feature::is_disabled('positions')) {
            return false;
        }
        return has_capability('totara/hierarchy:viewposition', \context_user::instance($job->userid));
    }

    /**
     * Can the user view this jobs organisations.
     *
     * @param job_assignment $job
     * @return bool
     */
    private static function can_view_organisation(job_assignment $job): bool {
        if (advanced_feature::is_disabled('organisations')) {
            return false;
        }
        return has_capability('totara/hierarchy:vieworganisation', \context_user::instance($job->userid));
    }

    /**
     * Returns the organisation with the given ID.
     * @param int $id
     * @return \stdClass Organisation record from the database.
     */
    private static function get_organisation(int $id) {
        global $CFG;
        require_once($CFG->dirroot . '/totara/hierarchy/lib.php');
        $hierarchy = \hierarchy::load_hierarchy('organisation');
        $organisation = $hierarchy->get_item($id);
        if (!$organisation) {
            // This should never happen.
            throw new \coding_exception('The linked organisation does not exist.');
        }
        return $organisation;
    }

    /**
     * Returns the position with the given ID.
     * @param int $id
     * @return \stdClass Organisation record from the database.
     */
    private static function get_position(int $id) {
        global $CFG;
        require_once($CFG->dirroot . '/totara/hierarchy/lib.php');
        $hierarchy = \hierarchy::load_hierarchy('position');
        $position = $hierarchy->get_item($id);
        if (!$position) {
            // This should never happen.
            throw new \coding_exception('The linked organisation does not exist.');
        }
        return $position;
    }

    /**
     * Returns the user with the given ID.
     * If the userid matches the current user id then $USER is returned.
     * @param int $userid
     * @return \stdClass
     */
    private static function get_user(int $userid): \stdClass {
        global $DB, $USER;
        if ($USER->id == $userid) {
            return $USER;
        }
        return $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
    }

    /**
     * Formats the given string for the given job assigmnent.
     * @param job_assignment $job_assignment
     * @param string $field
     * @param string $format
     * @return string
     */
    private static function format_string(job_assignment $job_assignment, string $field, string $format, execution_context $ec) {
        $value = $job_assignment->{$field};
        if ($format === format::FORMAT_RAW) {
            if (!self::can_edit($job_assignment)) {
                return null;
            }
            return $value;
        }
        return (new string_field_formatter($format, \context_system::instance()))->format($value);
    }

    /**
     * Returns true if the user can edit the job assignment.
     * @param job_assignment $job_assignment
     * @return bool
     */
    private static function can_edit(job_assignment $job_assignment) {
        global $CFG;
        require_once($CFG->dirroot . '/totara/job/lib.php');
        return \totara_job_can_edit_job_assignments($job_assignment->userid);
    }
}
