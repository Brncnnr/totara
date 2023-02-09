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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package totara_cohort
 */

defined('MOODLE_INTERNAL') || die();

use \totara_cohort\rules\ui\none_min_max_exactly as ui;
/**
 * This file contains sqlhandlers for rules involving job assignments
 */
abstract class cohort_rule_sqlhandler_job_assignments extends cohort_rule_sqlhandler {

    /** @var array $params */
    public $params = array(
        'equal' => 0,
        'listofvalues' => 1
    );

    /** @var int $equal */
    public $equal = 0;

    /** @var int $listofvalues */
    public $listofvalues = 1;

    public function get_sql_snippet() {
        global $DB;

        $column = 'ja.' . $this->get_join_column();
        $sqlhandler = new stdClass();
        $sqlhandler->params = [];
        if ($this->equal == ui::COHORT_RULES_OP_NONE) {
            $sqlhandler->sql = "NOT EXISTS (
                SELECT 1
                  FROM {job_assignment} ja
                  JOIN {job_assignment} staff
                    ON {$column} = staff.id
                 WHERE staff.userid = u.id
            ) ";
        } else {
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
            }
            $sql = "SELECT {$column}, staff.userid
                      FROM {job_assignment} ja
                      JOIN {job_assignment} staff
                        ON {$column} = staff.id
                  GROUP BY {$column}, staff.userid
                    HAVING COUNT(*) {$comparison} ?";
            if ($staff = $DB->get_records_sql($sql, $this->listofvalues)) {
                $userids = [];
                foreach ($staff as $person) {
                    $userids[] = $person->userid;
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

    abstract protected function get_join_column(): string;
}