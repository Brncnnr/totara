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

class program_allanynotallnone extends base_selector {
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
        switch ($this->operator) {
            case COHORT_RULE_COMPLETION_OP_ALL:
                $getstr = 'pcdescall';
                break;
            case COHORT_RULE_COMPLETION_OP_ANY:
                $getstr = 'pcdescany';
                break;
            case COHORT_RULE_COMPLETION_OP_NOTALL:
                $getstr = 'pcdescnotall';
                break;
            default:
                $getstr = 'pcdescnotany';
        }

        $strvar = new \stdClass();
        $strvar->desc = get_string($getstr, 'totara_cohort');

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
     * @param array     $ruledescriptions
     * @param int       $ruleinstanceid
     * @param bool      $static
     * @inheritdoc
     */
    protected function add_missing_rule_params(array &$ruledescriptions, $ruleinstanceid, $static=true) {
        global $DB;
        if (count($ruledescriptions) < count($this->listofids)) {
            // There are missing records, might be a posibility of deleted records, therefore, add some helper message
            // here for user to update. For retrieving what parameter of the rule is invalid, we need to know that
            // which $value (this reference to the program's) is missing in database
            $ruleparams = $DB->get_records("cohort_rule_params", array(
                'ruleid' => $ruleinstanceid,
                'name'   => 'listofids',
            ), "", "value, id AS paramid");

            foreach ($this->listofids as $id) {
                if (!isset($ruledescriptions[$id])) {
                    // So this $id is missing from the tracker, which indicate that it has been removed
                    // therefore, add the message here.
                    $item = isset($ruleparams[$id]) ? $ruleparams[$id] : null;
                    if (!$item) {
                        debugging("Missing the rule parameter for program id $id");
                        continue;
                    }

                    $a = (object) array('id' => $id);
                    $value = "\"". get_string('deleteditem', 'totara_cohort', $a) . "\"";
                    if (!$static) {
                        $value .= $this->param_delete_action_icon($item->paramid);
                    }

                    $ruledescriptions[$id] =
                        \html_writer::tag('span', $value, array('class' => 'ruleparamcontainer cohortdeletedparam'));
                }
            }
        }
    }
}
