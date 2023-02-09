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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Yuliya Bozhko <yuliya.bozhko@totaralearning.com>
 * @package totara_program
 */

namespace totara_program;

/**
 * Class providing various utility functions for use by programs but which can
 * be used independently of and without instantiating a program object
 */
class utils {
    // Duration constants.
    const DURATION_MINUTE = 60;
    const DURATION_HOUR   = 60  * self::DURATION_MINUTE;
    const DURATION_DAY    = 24  * self::DURATION_HOUR;
    const DURATION_WEEK   = 7   * self::DURATION_DAY;
    const DURATION_MONTH  = 30  * self::DURATION_DAY;
    const DURATION_YEAR   = 365 * self::DURATION_DAY;

    // Time selector constants.
    const TIME_SELECTOR_HOURS     = 1;
    const TIME_SELECTOR_DAYS      = 2;
    const TIME_SELECTOR_WEEKS     = 3;
    const TIME_SELECTOR_MONTHS    = 4;
    const TIME_SELECTOR_YEARS     = 5;
    const TIME_SELECTOR_NOMINIMUM = 6;

    // Time selector strings.
    public static $timeallowancestrings = [
        self::TIME_SELECTOR_HOURS     => 'hours',
        self::TIME_SELECTOR_DAYS      => 'days',
        self::TIME_SELECTOR_WEEKS     => 'weeks',
        self::TIME_SELECTOR_MONTHS    => 'months',
        self::TIME_SELECTOR_YEARS     => 'years',
        self::TIME_SELECTOR_NOMINIMUM => 'nominimumtime',
    ];

    /**
     * Given an integer and a time period (e.g. a day = 60*60*24) this function
     * calculates the length covered by the period and returns it as a timestamp
     *
     * E.g. if $num = 4 and $period = 1 (hours) then the timestamp returned
     * would be the equivalent of 4 hours.
     *
     * @param int $num The number of units of the time period to calculate
     * @param int $period An integer denoting the time period (hours, days, weeks, etc)
     * @return int A timestamp
     */
    public static function duration_implode($num, $period): int {
        if ($period == self::TIME_SELECTOR_YEARS) {
            return $num * self::DURATION_YEAR;
        } else if ($period == self::TIME_SELECTOR_MONTHS) {
            return $num * self::DURATION_MONTH;
        } else if ($period == self::TIME_SELECTOR_WEEKS) {
            return $num * self::DURATION_WEEK;
        } else if ($period == self::TIME_SELECTOR_DAYS) {
            return $num * self::DURATION_DAY;
        } else if ($period == self::TIME_SELECTOR_HOURS) {
            return $num * self::DURATION_HOUR;
        }

        return 0;
    }

    /**
     * Given a timestamp representing a duration, this function factors the
     * timestamp out into a time period (e.g. an hour, a day, a week, etc)
     * and the number of units of the time period.
     *
     * This is mainly for use in forms which provide 2 fields for specifying
     * a duration.
     *
     * @param int $duration
     * @return \stdClass An object containing $num and $period properties
     */
    public static function duration_explode($duration) {
        $ob = new \stdClass();

        if ($duration == 0) {
            $ob->num = 0;
            $ob->period = self::TIME_SELECTOR_NOMINIMUM;
        } else if ($duration % self::DURATION_YEAR == 0) {
            $ob->num = $duration / self::DURATION_YEAR;
            $ob->period = self::TIME_SELECTOR_YEARS;
        } else if ($duration % self::DURATION_MONTH == 0) {
            $ob->num = $duration / self::DURATION_MONTH;
            $ob->period = self::TIME_SELECTOR_MONTHS;
        } else if ($duration % self::DURATION_WEEK == 0) {
            $ob->num = $duration / self::DURATION_WEEK;
            $ob->period = self::TIME_SELECTOR_WEEKS;
        } else if ($duration % self::DURATION_DAY == 0) {
            $ob->num = $duration / self::DURATION_DAY;
            $ob->period = self::TIME_SELECTOR_DAYS;
        } else if ($duration % self::DURATION_HOUR == 0) {
            $ob->num = $duration / self::DURATION_HOUR;
            $ob->period = self::TIME_SELECTOR_HOURS;
        } else {
            $ob->num = 0;
            $ob->period = 0;
        }

        if (array_key_exists($ob->period, self::$timeallowancestrings)) {
            $ob->periodstr = strtolower(get_string(self::$timeallowancestrings[$ob->period], 'totara_program'));
        } else {
            $ob->periodstr = '';
        }

        return $ob;
    }

    /**
     * Given a timestamp representing a duration, this function factors the
     * timestamp out into a time period (e.g. an hour, a day, a week, etc)
     * and the number of units of the time period.
     *
     * The period is included in two forms:
     * $period - A constant such as self::TIME_SELECTOR_YEARS.
     * $periodkey - A string such as 'years' (not translated, but might be used as part of
     *  a lang string key).
     *
     * @param int $duration
     * @return \stdClass An object containing $num, $period and $periodkey properties
     */
    public static function get_duration_num_and_period($duration) {
        $object = new \stdClass();

        if ($duration == 0) {
            $object->num = 0;
            $object->period = self::TIME_SELECTOR_NOMINIMUM;
            $object->periodkey = 'nominimum';
        } else if ($duration % self::DURATION_YEAR == 0) {
            $object->num = $duration / self::DURATION_YEAR;
            $object->period = self::TIME_SELECTOR_YEARS;
            $object->periodkey = 'years';
        } else if ($duration % self::DURATION_MONTH == 0) {
            $object->num = $duration / self::DURATION_MONTH;
            $object->period = self::TIME_SELECTOR_MONTHS;
            $object->periodkey = 'months';
        } else if ($duration % self::DURATION_WEEK == 0) {
            $object->num = $duration / self::DURATION_WEEK;
            $object->period = self::TIME_SELECTOR_WEEKS;
            $object->periodkey = 'weeks';
        } else if ($duration % self::DURATION_DAY == 0) {
            $object->num = $duration / self::DURATION_DAY;
            $object->period = self::TIME_SELECTOR_DAYS;
            $object->periodkey = 'days';
        } else if ($duration % self::DURATION_HOUR == 0) {
            $object->num = $duration / self::DURATION_HOUR;
            $object->period = self::TIME_SELECTOR_HOURS;
            $object->periodkey = 'hours';
        } else {
            throw new \coding_exception('Unrecognised datetime');
        }

        return $object;
    }

    /**
     * Prints or returns the html for the time allowance fields
     *
     * @param string $prefix
     * @param string $periodelementname
     * @param string $periodvalue
     * @param int    $numberelementname
     * @param int    $numbervalue
     * @param bool   $includehours
     *
     * @return string
     */
    public static function print_duration_selector($prefix, $periodelementname, $periodvalue, $numberelementname, $numbervalue, $includehours = true) {
        $timeallowances = [];
        if ($includehours) {
            $timeallowances[self::TIME_SELECTOR_HOURS] = get_string('hours', 'totara_program');
        }
        $timeallowances[self::TIME_SELECTOR_DAYS] = get_string('days', 'totara_program');
        $timeallowances[self::TIME_SELECTOR_WEEKS] = get_string('weeks', 'totara_program');
        $timeallowances[self::TIME_SELECTOR_MONTHS] = get_string('months', 'totara_program');
        $timeallowances[self::TIME_SELECTOR_YEARS] = get_string('years', 'totara_program');
        if ($periodvalue == '' || $periodvalue == 0) {
            $periodvalue = '' . self::TIME_SELECTOR_DAYS;
        }

        $m_name = $prefix . $periodelementname;
        $m_id = $prefix . $periodelementname;
        $m_selected = $periodvalue;
        $m_nothing = '';
        $m_nothingvalue = '';
        $m_disabled = false;
        $m_tabindex = 0;

        $out = '';
        $attributes = [
            'type'      => 'text',
            'id'        => $prefix . $numberelementname,
            'name'      => $prefix . $numberelementname,
            'value'     => $numbervalue,
            'size'      => '4',
            'maxlength' => '3'
        ];
        $out .= \html_writer::empty_tag('input', $attributes);

        $attributes = [];
        $attributes['disabled'] = $m_disabled;
        $attributes['tabindex'] = $m_tabindex;
        $attributes['multiple'] = null;
        $attributes['class'] = null;
        $attributes['id'] = $m_id;
        $out .= \html_writer::select($timeallowances, $m_name, $m_selected, [$m_nothingvalue => $m_nothing], $attributes);

        return $out;
    }

    /**
     * Returns an array of time allowance options that can be used in form select elements.
     *
     * @param bool $includenominimum
     *
     * @return array
     */
    public static function get_standard_time_allowance_options(bool $includenominimum = false): array {
        $timeallowances = [
            self::TIME_SELECTOR_DAYS   => get_string('days', 'totara_program'),
            self::TIME_SELECTOR_WEEKS  => get_string('weeks', 'totara_program'),
            self::TIME_SELECTOR_MONTHS => get_string('months', 'totara_program'),
            self::TIME_SELECTOR_YEARS  => get_string('years', 'totara_program'),
        ];
        if ($includenominimum) {
            $timeallowances[self::TIME_SELECTOR_NOMINIMUM] = get_string('nominimumtime', 'totara_program');
        }
        return $timeallowances;
    }


    /**
     * Find if a user is assigned to a program/certification
     *
     * @param int $programid
     * @param int|null $userid
     *
     * @return bool
     */
    public static function user_is_assigned(int $programid, ?int $userid) :bool {
        global $DB;

        // Check needed for backwards compatibility with program::user_is_assigned method.
        if (!$userid) {
            return false;
        }

        static $prog_assigned = [];
        if (PHPUNIT_TEST) {
            $prog_assigned = [];
        }

        $key = $programid . '-' . $userid;

        if (!isset($prog_assigned[$key])) {
            // Update this when we move constants into an autoloaded class, these
            // are defined in program.class.php which has a lot of extra require calls
            // PROGRAM_EXCEPTION_RAISED = 1
            // PROGRAM_EXCEPTION_DISMISSED = 2
            $statuses = [1,2];
            list($statussql, $statusparams) = $DB->get_in_or_equal($statuses, SQL_PARAMS_NAMED, null, false);

            $params = [
                'programid' => $programid,
                'userid' => $userid
            ];

            $params = array_merge($params, $statusparams);
            $result = $DB->record_exists_select('prog_user_assignment', "programid = :programid AND userid = :userid AND exceptionstatus $statussql", $params);

            if ($result === false) {
                // Check for plan assignment
                $sql = "SELECT COUNT(*) FROM
                    {dp_plan} p
                    JOIN
                    {dp_plan_program_assign} pa
                    ON
                    p.id = pa.planid
                    WHERE
                    p.userid = :userid
                    AND pa.programid = :programid
                    AND pa.approved = :approved
                    AND p.status >= :approvedstatus";
                $params = [
                    'userid' => $userid,
                    'programid' => $programid,
                    'approved' => 50, //DP_APPROVAL_APPROVED,
                    'approvedstatus' => 50 //DP_PLAN_STATUS_APPROVED
                ];

                if ($DB->count_records_sql($sql, $params) > 0) {
                    $result = true;
                } else {
                    $result = false;
                }
            }

            $prog_assigned[$key] = $result;
        }

        return $prog_assigned[$key];
    }

    /**
     * Is certification?
     *
     * @param int $programid
     * @return bool
     */
    public static function is_certif(int $programid): bool {

        static $iscertif = [];

        if (!isset($iscertif[$programid]) || PHPUNIT_TEST) {
            $program = new \program($programid);
            $iscertif[$programid] = $program->is_certif();
        }

        return $iscertif[$programid];
    }
}
