<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @author Aaron Wells <aaronw@catalyst.net.nz>
 * @package totara_cohort
 */

namespace totara_cohort\rules\ui;

defined('MOODLE_INTERNAL') || die();

use totara_cohort\rules\ui\base_selector_course as base_selector;

class course_allanynotallnone extends base_selector {
    /**
     * @var array
     */
    public $params = array(
        'operator' => 0,
        'listofids' => 1
    );

    /**
     * @return string
     */
    public function getExtraSelectedItemsPaneWidgets(){
        $operatormenu = array();
        $operatormenu[COHORT_RULE_COMPLETION_OP_ALL] = get_string('completionmenuall', 'totara_cohort');
        $operatormenu[COHORT_RULE_COMPLETION_OP_ANY] = get_string('completionmenuany', 'totara_cohort');
        $operatormenu[COHORT_RULE_COMPLETION_OP_NOTALL] = get_string('completionmenunotall', 'totara_cohort');
        $operatormenu[COHORT_RULE_COMPLETION_OP_NONE] = get_string('completionmenunotany', 'totara_cohort');
        $selected = isset($this->operator) ? $this->operator : '';

        return \html_writer::select($operatormenu, 'operator', $selected, array(),
            array('id' => 'id_operator', 'class' => 'cohorttreeviewsubmitfield'));
    }

    /**
     * @param cohort_rule_sqlhandler $sqlhandler
     */
    public function handleDialogUpdate($sqlhandler){
        $operator = required_param('operator', PARAM_INT);
        $listofids = required_param('selected', PARAM_SEQUENCE);
        $listofids = explode(',',$listofids);
        $this->operator = $sqlhandler->operator = $operator;
        $this->listofids = $sqlhandler->listofids = $listofids;
        $sqlhandler->write();
    }

    /**
     * Get the description of this rule for the list of rules
     * @param int $ruleid
     * @param boolean $static only display static description, without action controls
     * @return string
     */
    public function getRuleDescription($ruleid, $static=true) {
        global $DB;
        if (!isset($this->operator) || !isset($this->listofids)) {
            return get_string('error:rulemissingparams', 'totara_cohort');
        }

        $strvar = $this->get_description_string();

        list($sqlin, $sqlparams) = $DB->get_in_or_equal($this->listofids);
        $sqlparams[] = $ruleid;
        $sql = "SELECT c.id, c.fullname, crp.id AS paramid
            FROM {course} c
            INNER JOIN {cohort_rule_params} crp ON c.id = " . $DB->sql_cast_char2int('crp.value') . "
            WHERE c.id {$sqlin}
            AND crp.name = 'listofids' AND crp.ruleid = ?
            ORDER BY sortorder, fullname";
        $courselist = $DB->get_records_sql($sql, $sqlparams);

        foreach ($courselist as $i => $c) {
            $value = '"' . format_string($c->fullname) . '"';
            if (!$static) {
                $value .= $this->param_delete_action_icon($c->paramid);
            }
            $courselist[$i] = \html_writer::tag('span', $value, array('class' => 'ruleparamcontainer'));
        };

        $this->add_missing_rule_params($courselist, $ruleid, $static);
        $paramseparator = \html_writer::tag('span', ', ', array('class' => 'ruleparamseparator'));
        $strvar->vars = implode($paramseparator, $courselist);

        return get_string('ruleformat-descvars', 'totara_cohort', $strvar);
    }

    /**
     * @param array $courselists
     * @param int   $ruleinstanceid
     * @param bool  $static
     * @inheritdoc
     */
    protected function add_missing_rule_params(array &$courselists, $ruleinstanceid, $static = true) {
        global $DB;

        if (count($courselists) < count($this->listofids)) {
            $fullparams = $DB->get_records("cohort_rule_params", array(
                'ruleid'    => $ruleinstanceid,
                'name'  => 'listofids'
            ), "", "value AS courseid, id AS paramid");

            foreach ($this->listofids as $courseid) {
                if (!isset($courselists[$courseid])) {
                    // Missing couse here
                    $item = isset($fullparams[$courseid]) ? $fullparams[$courseid] : null;
                    if(!$item) {
                        debugging("Missing the rule parameter for course {$courseid}");
                        continue;
                    }

                    $a = (object) array('id' => $courseid);
                    $value = "\"". get_string('deleteditem', 'totara_cohort', $a) . "\"";
                    if (!$static) {
                        $value .= $this->param_delete_action_icon($item->paramid);
                    }

                    $courselists[$courseid]  =
                        \html_writer::tag('span', $value, array('class' => 'ruleparamcontainer cohortdeletedparam'));
                }
            }
        }
    }

    /**
     * Get description string depends from operator for course completion history.
     *
     * @return \stdClass
     */
    protected function get_description_string(): \stdClass {
        $strvar = new \stdClass();
        switch ($this->operator) {
            case COHORT_RULE_COMPLETION_OP_ALL:
                $strvar->desc = get_string('ccdescall', 'totara_cohort');
                break;
            case COHORT_RULE_COMPLETION_OP_ANY:
                $strvar->desc = get_string('ccdescany', 'totara_cohort');
                break;
            case COHORT_RULE_COMPLETION_OP_NOTALL:
                $strvar->desc = get_string('ccdescnotall', 'totara_cohort');
                break;
            default:
                $strvar->desc = get_string('ccdescnotany', 'totara_cohort');
        }
        return $strvar;
    }
}
