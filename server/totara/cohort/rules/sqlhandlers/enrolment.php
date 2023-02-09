<?php
/*
 * This file is part of Totara LMS
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
 * @author Vernon Denny <vernon.denny@totaralearning.com>
 * @package totara_cohort
 */
/**
 * This file contains sqlhandlers for rules based on course enrolment and program enrolment.
 */

defined('MOODLE_INTERNAL') || die();

define('COHORT_RULE_ENROLMENT_OP_NONE', 0);
define('COHORT_RULE_ENROLMENT_OP_ANY', 10);
define('COHORT_RULE_ENROLMENT_OP_NOTALL', 30);
define('COHORT_RULE_ENROLMENT_OP_ALL', 40);

define('COHORT_PICKER_PROGRAM_ENROLMENT', 0);
define('COHORT_PICKER_COURSE_ENROLMENT', 1);

require_once($CFG->dirroot . '/totara/program/program.class.php');
/**
 * A rule for checking whether a user's enrolled on any/all/some/none of the courses/programs
 * in a list
 */
abstract class cohort_rule_sqlhandler_enrolment_list extends cohort_rule_sqlhandler {
    public $params = [
        'operator' => 0,
        'listofids' => 1
    ];

    public function get_sql_snippet() {

        if (count($this->listofids) == 0) {
            // TODO TL-7094 This needs to use sql snippet stdClass instead, for now this string means all users.
            return '1=0';
        }

        switch ($this->operator) {
            case COHORT_RULE_ENROLMENT_OP_NONE:
                $count = 0;
                $operator = '=';
                break;
            case COHORT_RULE_ENROLMENT_OP_ANY:
                $count = 0;
                $operator = '<';
                break;
            case COHORT_RULE_ENROLMENT_OP_NOTALL:
                $count = count($this->listofids);
                $operator = '>';
                break;
            case COHORT_RULE_ENROLMENT_OP_ALL:
                $count = count($this->listofids);
                $operator = '=';
                break;
            default:
                return false;
        }

        return $this->construct_sql_snippet($count, $operator, $this->listofids);
    }

    abstract protected function construct_sql_snippet($count, $operator, $lov);
}

/**
 * Rule for determining whether a learner is enrolled on all/any/some/none of the courses in a list.
 */
class cohort_rule_sqlhandler_enrolment_list_course extends cohort_rule_sqlhandler_enrolment_list {
    protected function construct_sql_snippet($count, $operator, $lov) {
        global $DB;
        $sqlhandler = new stdClass();
        list($sqlin, $params) = $DB->get_in_or_equal($lov, SQL_PARAMS_NAMED, 'clc'.$this->ruleid);

        $now = time();
        $sqlhandler->sql = "{$count} {$operator}
                  (
                   SELECT count(DISTINCT courseid)
                   FROM {user_enrolments} ue
                   JOIN {enrol} e ON (ue.enrolid = e.id)
                   WHERE ue.userid = u.id
                   AND e.courseid {$sqlin}
                   AND u.suspended = 0
                   AND u.deleted = 0
                   AND ue.timestart <= {$now}
                   AND (ue.timeend > {$now} OR ue.timeend = 0)
                   AND ue.status = 0
                   AND e.status = 0
                  )";
        $sqlhandler->params = $params;
        return $sqlhandler;
    }
}

/**
 * Rule for determining whether a learner is assigned to all/any/some/none of the programs in a list.
 */
class cohort_rule_sqlhandler_enrolment_list_program extends cohort_rule_sqlhandler_enrolment_list {
    protected function construct_sql_snippet($count, $operator, $lov) {
        global $DB;
        $sqlhandler = new stdClass();
        list($sqlin, $params) = $DB->get_in_or_equal($lov, SQL_PARAMS_NAMED, 'clp'.$this->ruleid);

        $sqlhandler->sql = "{$count} {$operator}
                  (
                   SELECT count(DISTINCT id)
                   FROM {prog_user_assignment} pua
                   WHERE pua.userid = u.id
                   AND pua.programid {$sqlin}
                   AND u.suspended = 0
                   AND u.deleted = 0
                   AND (pua.exceptionstatus = :none OR pua.exceptionstatus = :resolved) 
                  )";
        $params['none'] = PROGRAM_EXCEPTION_NONE;
        $params['resolved'] = PROGRAM_EXCEPTION_RESOLVED;
        $sqlhandler->params = $params;
        return $sqlhandler;
    }
}
