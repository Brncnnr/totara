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

use totara_cohort\rules\ui\base_selector_program as base_selector;

class program_duration extends base_selector {
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
        $operatormenu[COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN] =    get_string('completiondurationmenulessthan', 'totara_cohort');
        $operatormenu[COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN] = get_string('completiondurationmenumorethan', 'totara_cohort');
        $selected = isset($this->operator) ? $this->operator : '';
        $html .= \html_writer::select($operatormenu, 'operator', $selected, array(),
            array('id' => 'id_operator', 'class' => 'cohorttreeviewsubmitfield'));
        $html .= '<fieldset>';
        $html .= '<input class="cohorttreeviewsubmitfield" id="completionduration" name="date" value="';
        if (isset($this->date)) {
            $html .= htmlspecialchars($this->date);
        }
        $html .= '" /> day(s)';
        $html .= '</fieldset>';
        $html .= '</div>';
        return $html;
    }

    /**
     * @param cohort_rule_sqlhandler $sqlhandler
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
    public function getRuleDescription($ruleid, $static = true) {
        global $DB;
        if (!isset($this->operator) || !isset($this->listofids)) {
            return get_string('error:rulemissingparams', 'totara_cohort');
        }
        switch ($this->operator) {
            case COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN:
                $getstr = 'pcdurationdesclessthan';
                break;
            case COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN:
                $getstr = 'pcdurationdescmorethan';
                break;
        }

        $strvar = new \stdClass();
        $strvar->desc = get_string($getstr, 'totara_cohort', $this->date);

        list($sqlin, $sqlparams) = $DB->get_in_or_equal($this->listofids);
        $sqlparams[] = $ruleid;
        $sql = "SELECT p.id, p.fullname, crp.id AS paramid
            FROM {prog} p
            INNER JOIN {cohort_rule_params} crp ON p.id = " . $DB->sql_cast_char2int('crp.value') . "
            WHERE p.id {$sqlin}
            AND crp.name = 'listofids' AND crp.ruleid = ?
            ORDER BY sortorder, fullname";
        $proglist = $DB->get_records_sql($sql, $sqlparams);

        foreach ($proglist as $i => $p) {
            $value = '"' . format_string($p->fullname) . '"';
            if (!$static) {
                $value .= $this->param_delete_action_icon($p->paramid);
            }
            $proglist[$i] = \html_writer::tag('span', $value, array('class' => 'ruleparamcontainer'));
        };

        $this->add_missing_rule_params($proglist, $ruleid, $static);
        $paramseparator = \html_writer::tag('span', ', ', array('class' => 'ruleparamseparator'));
        $strvar->vars = implode($paramseparator, $proglist);

        return get_string('ruleformat-descvars', 'totara_cohort', $strvar);
    }

    /**
     * @param array $ruledescriptions
     * @param int   $ruleinstanceid
     * @param bool  $static
     * @inheritdoc
     */
    protected function add_missing_rule_params(array &$ruledescriptions, $ruleinstanceid, $static = true) {
        global $DB;
        if (count($ruledescriptions) < count($this->listofids)) {
            // Detected that there are invalid records. Therefore, this method will automatically state which
            // recorded was deleted.
            $fullparams = $DB->get_records('cohort_rule_params', array(
                'ruleid' => $ruleinstanceid,
                'name'   => 'listofids'
            ), "", "value AS programid, id AS paramid");

            foreach($this->listofids as $programid) {
                if (!isset($ruledescriptions[$programid])) {
                    // If the program id was not found in the $ruledescriptionlists, then it means that the
                    // record/instance was deleted

                    $item = isset($fullparams[$programid]) ? $fullparams[$programid] : null;
                    if (!$item) {
                        debugging("Missing the rule parameter for program id {$programid}");
                        continue;
                    }
                    $a = (object) array('id' => $programid);
                    $value = "\"". get_string("deleteditem", "totara_cohort", $a) . "\"";
                    if (!$static) {
                        $value .= $this->param_delete_action_icon($item->paramid);
                    }

                    $ruledescriptions[$programid] =
                        \html_writer::tag('span', $value, array('class' => 'ruleparamcontainer cohortdeletedparam'));
                }
            }
        }
    }
}
