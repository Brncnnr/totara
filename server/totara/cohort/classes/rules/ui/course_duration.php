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

class course_duration extends base_selector {
    /**
     * @var array
     */
    public $params = array(
        'operator' => 0,
        'date' => 0,
        'listofids' => 1
    );

    /**
     * @return string
     */
    public function getExtraSelectedItemsPaneWidgets(){
        $html = '<div class="mform cohort-treeview-dialog-extrafields">';
        $operatormenu = array();
        $operatormenu[COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN] = get_string('completiondurationmenulessthan', 'totara_cohort');
        $operatormenu[COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN] = get_string('completiondurationmenumorethan', 'totara_cohort');
        $selected = isset($this->operator) ? $this->operator : '';
        $html .= \html_writer::select($operatormenu, 'operator', $selected, array(),
            array('id' => 'id_operator', 'class' => 'cohorttreeviewsubmitfield'));

        $html .= '<fieldset>';
        $html .= '<input class="cohorttreeviewsubmitfield" id="completionduration" name="date" value="';
        if (isset($this->date)) {
            $html .= htmlspecialchars($this->date);
        }
        $html .= '" /> ' . get_string('completiondurationdays', 'totara_cohort');
        $html .= '</fieldset>';
        $html .= '</div>';
        return $html;
    }

    /**
     * @param $sqlhandler
     */
    public function handleDialogUpdate($sqlhandler){
        $date = required_param('date', PARAM_INT);
        $operator = required_param('operator', PARAM_INT);
        $listofids = required_param('selected', PARAM_SEQUENCE);
        $listofids = explode(',',$listofids);
        $this->date = $sqlhandler->date = $date;
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
        switch ($this->operator) {
            case COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN:
                $descstr = 'ccdurationdesclessthan';
                break;
            case COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN:
                $descstr = 'ccdurationdescmorethan';
                break;
        }

        $strvar = new \stdClass();
        $strvar->desc = get_string($descstr, 'totara_cohort', $this->date);

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
            $value = '"' . $c->fullname . '"';
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
     * @param int $ruleinstanceid
     * @param bool $static
     * @throws coding_exception
     * @throws dml_exception
     * @inheritdoc
     * @return void
     */
    protected function add_missing_rule_params(array &$courselists, $ruleinstanceid, $static = true) {
        global $DB;

        if (count($courselists) < count($this->listofids)) {
            // There are missing courses found at rendering.
            $fullparams = $DB->get_records("cohort_rule_params", array(
                'ruleid' => $ruleinstanceid,
                'name'   => 'listofids'
            ), "" , " value as courseid, id as paramid ");

            foreach ($this->listofids as $courseid) {
                if (!isset($courselists[$courseid])) {
                    // Detected that a course with id {$courseid} is missing here
                    $item = isset($fullparams[$courseid]) ? $fullparams[$courseid] : null;
                    if (!$item) {
                        debugging("Missing the rule parameter for course {$courseid}");
                        continue;
                    }

                    $a = (object) array('id' => $courseid);
                    $value =  "\"" . get_string('deleteditem', 'totara_cohort', $a) . "\"";
                    if (!$static) {
                        $value .= $this->param_delete_action_icon($item->paramid);
                    }

                    $courselists[$courseid] =
                        \html_writer::tag('span', $value, array('class' => "ruleparamcontainer cohortdeletedparam"));
                }
            }
        }
    }
}
