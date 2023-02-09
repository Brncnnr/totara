<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Aaron Wells <aaronw@catalyst.net.nz>
 * @package totara_cohort
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/totara/cohort/rules/sqlhandlers/job_assignments.php');
require_once($CFG->dirroot . '/totara/cohort/classes/rules/ui/none_min_max_exactly.php');
use \totara_cohort\rules\ui\none_min_max_exactly as ui;

/**
 * Deprecated rule which indicates whether or not a user has anyone who reports directly to them.
 * @deprecated since Totara 13.0
 */
class cohort_rule_sqlhandler_hasreports extends cohort_rule_sqlhandler {

    // We actually only need one scalar param for this ruletype; but using these ones allows us to re-use the "Checkbox" ui type
    public $params = array(
        'equal' => 0,
        'listofvalues' => 1
    );

    /**
     * cohort_rule_sqlhandler_hasreports constructor.
     * @deprecated since Totara 13.0
     */
    public function __construct() {
        debugging('cohort_rule_sqlhandler_hasreports{} class has been deprecated, please use cohort_rule_sqlhandler_has_direct_reports{}',
            DEBUG_DEVELOPER);
    }

    public function get_sql_snippet() {
        $sqlhandler = new stdClass();
        $hasreports = array_pop($this->listofvalues);
        $sqlhandler->sql = ($hasreports ? '' : 'NOT ') . "EXISTS (
                SELECT 1
                  FROM {job_assignment} staffja
                  JOIN {job_assignment} managerja
                    ON staffja.managerjaid = managerja.id
                 WHERE managerja.userid = u.id
            ) ";
        $sqlhandler->params = array();
        return $sqlhandler;
    }
}

/**
 * A rule which indicates whether or not a user has anyone who reports directly to them.
 */
class cohort_rule_sqlhandler_has_direct_reports extends cohort_rule_sqlhandler_job_assignments {

    /**
     * Return job_assignment join column for has direct reports rule.
     * @return string
     */
    public function get_join_column(): string {
        return 'managerjaid';
    }
}

/**
 * A rule which indicates whether or not a user has anyone who reports temporary to them.
 */
class cohort_rule_sqlhandler_has_temporary_reports extends cohort_rule_sqlhandler_job_assignments {

    /**
     * Return job_assignment join column for has temporary reports rule.
     * @return string
     */
    public function get_join_column(): string {
        return 'tempmanagerjaid';
    }
}

/**
 * A rule which indicates whether or not a user has anyone who reports appraisal to them.
 */
class cohort_rule_sqlhandler_has_appraisees extends cohort_rule_sqlhandler_job_assignments {

    /**
     * Return job_assignment join column for has appraisees rule.
     * @return string
     */
    public function get_join_column(): string {
        return 'appraiserid';
    }

    public function get_sql_snippet() {
        global $DB;

        $column = 'ja.' . $this->get_join_column();
        $sqlhandler = new stdClass();
        $sqlhandler->sql = '';
        $sqlhandler->params = [];
        switch ($this->equal) {
            case ui::COHORT_RULES_OP_NONE:
                $sqlhandler->sql = "
                    NOT EXISTS (
                        SELECT 1
                          FROM {job_assignment} ja
                         WHERE {$column} IS NOT NULL
                           AND ja.userid = u.id
                        )
                ";
                break;
            case ui::COHORT_RULES_OP_MIN:
                $comparison = '>=';
                break;
            case ui::COHORT_RULES_OP_MAX:
                $comparison = '<=';
                break;
            case ui::COHORT_RULES_OP_EXACT:
                $comparison = '=';
                break;
        }
        if (empty($sqlhandler->sql)) {
            $sql = "SELECT {$column}
                      FROM {job_assignment} ja
                  GROUP BY {$column}
                    HAVING COUNT(*) {$comparison} ?";
            if ($staff = $DB->get_records_sql($sql, $this->listofvalues)) {
                $userids = [];
                foreach ($staff as $person) {
                    $userids[] = $person->appraiserid;
                }
                list($sqlin, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'iu'.$this->ruleid);
                $sqlhandler->sql = "u.id {$sqlin}";
                $sqlhandler->params = $params;
            } else {
                $sqlhandler->sql = '0 = 1';
            }
        }
        return $sqlhandler;
    }
}

/**
 * A rule for determining whether or not a user reports to another user in any of their respective job assignments.
 */
class cohort_rule_sqlhandler_allstaff extends cohort_rule_sqlhandler {

    /** @var array $params */
    public $params = array(
        'isdirectreport' => 0,
        'managerid' => 1
    );

    /** @var int $isdirectreport */
    public $isdirectreport = 0;

    /** @var int $managerid */
    public $managerid = 1;

    public function get_sql_snippet() {
        global $DB;

        $sqlhandler = new stdClass();
        $sqlhandler->sql = "EXISTS (
                SELECT 1
                  FROM {job_assignment} staffja
                  LEFT JOIN {job_assignment} managerja
                  ON staffja.managerjaid = managerja.id
                     WHERE staffja.userid = u.id
             ";

        // Both branches of the if statement below need the results of get_in_or_equal.
        list($sqlin, $params) = $DB->get_in_or_equal($this->managerid, SQL_PARAMS_NAMED, 'rt' . $this->ruleid);

        if ($this->isdirectreport) {
            $sqlhandler->sql .= 'AND managerja.userid '.$sqlin;

        } else {
            $sqlhandler->sql .= "AND (";
            $needor = 0;
            $index = 1;
            // We need to get the actual managerpath for each manager for this to work properly.
            $menusql = "SELECT id, userid, managerjapath FROM {job_assignment} WHERE userid {$sqlin}";
            $jobassignpaths = $DB->get_records_sql($menusql, $params);

            foreach ($jobassignpaths as $path) {
                if (!empty($needor)) { //don't add on first iteration.
                    $sqlhandler->sql .= ' OR ';
                }

                $sqlhandler->sql .= $DB->sql_like('staffja.managerjapath', ':rtm'.$this->ruleid.$index);
                $params['rtm'.$this->ruleid.$index] = $path->managerjapath . '/%';
                $needor = true;
                $index++;
            }
            $sqlhandler->sql .= ")";
        }

        $sqlhandler->sql .= ')';
        $sqlhandler->params = $params;
        return $sqlhandler;
    }
}

/**
 * A rule which indicates whether or not a user has anyone who indirectly reports to them.
 */
class cohort_rule_sqlhandler_has_indirect_reports extends cohort_rule_sqlhandler_job_assignments {
    public $params = [
        'equal' => 0,
        'listofvalues' => 1
    ];

    public function get_sql_snippet() {
        global $DB;

        $sqlhandler = new stdClass();
        $sqlhandler->params = [];
        $column = 'ja.' . $this->get_join_column();

        $sql = '';
        $comparison = '';

        switch ($this->equal) {
            case ui::COHORT_RULES_OP_MIN:
                $comparison = '>=';
                break;
            case ui::COHORT_RULES_OP_MAX:
                $comparison = '<=';
                break;
            case ui::COHORT_RULES_OP_EXACT:
                $comparison = '=';
                break;
            case ui::COHORT_RULES_OP_NONE:
                $sql = "
                    SELECT userid
                    FROM {job_assignment}
                    WHERE userid NOT IN (
                        SELECT DISTINCT ja.userid
                        FROM {job_assignment} ja
                        JOIN {job_assignment} direct_report_ja ON direct_report_ja.managerjaid = ja.id
                        JOIN {job_assignment} indirect_report_ja ON indirect_report_ja.managerjaid = direct_report_ja.id
                )
                ";
                break;
        }

        // Are we looking for some kind of reports count?
        if ($sql == '') {
            $sql = "SELECT DISTINCT ja.userid FROM {job_assignment} ja
                     JOIN {job_assignment} staff
                       ON {$column} != staff.managerjaid
                       AND staff.managerjapath LIKE (" . $DB->sql_concat('ja.managerjapath', "'/%'") . ")
                     GROUP BY ja.userid
                     HAVING COUNT(*) {$comparison} " . (string) $this->listofvalues[0];
        }

        $sqlhandler->sql = "u.id IN ({$sql})";
        $sqlhandler->params = [];
        return $sqlhandler;
    }


    /**
     * Return job_assignment join column for har direct reports rule.
     * @return string
     */
    public function get_join_column(): string {
        return 'id';
    }
}
