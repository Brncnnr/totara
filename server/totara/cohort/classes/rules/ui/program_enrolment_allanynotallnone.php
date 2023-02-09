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
 * @author Vernon Denny <vernon.denny@totaralearning.com>
 * @package totara_cohort
 */

namespace totara_cohort\rules\ui;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/totara/cohort/rules/settings.php');

use totara_cohort\rules\ui\base_selector_program as base_selector;

class program_enrolment_allanynotallnone extends base_selector {
    /**
     * @var array
     */
    public $params = [
        'operator' => 0,
        'listofids' => 1
    ];

    /**
     * @return string
     */
    public function getExtraSelectedItemsPaneWidgets() {
        $operatormenu = [];
        $operatormenu[COHORT_RULE_ENROLMENT_OP_ALL] = get_string('enrolmentmenuallpgm', 'totara_cohort');
        $operatormenu[COHORT_RULE_ENROLMENT_OP_ANY] = get_string('enrolmentmenuanypgm', 'totara_cohort');
        $operatormenu[COHORT_RULE_ENROLMENT_OP_NOTALL] = get_string('enrolmentmenunotallpgm', 'totara_cohort');
        $operatormenu[COHORT_RULE_ENROLMENT_OP_NONE] = get_string('enrolmentmenunotanypgm', 'totara_cohort');
        $selected = isset($this->operator) ? $this->operator : '';

        return \html_writer::select(
            $operatormenu, 'operator', $selected, [],
            ['id' => 'id_operator', 'class' => 'cohorttreeviewsubmitfield']
        );
    }

    /**
     * @param cohort_rule_sqlhandler $sqlhandler
     */
    public function handleDialogUpdate($sqlhandler) {
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
    public function getRuleDescription($ruleid, $static = true) {
        global $DB;

        if (!isset($this->operator) || !isset($this->listofids)) {
            return get_string('error:rulemissingparams', 'totara_cohort');
        }

        switch ($this->operator) {
            case COHORT_RULE_ENROLMENT_OP_ALL:
                $getstr = 'pgmenroldescall';
                break;
            case COHORT_RULE_ENROLMENT_OP_ANY:
                $getstr = 'pgmenroldescany';
                break;
            case COHORT_RULE_ENROLMENT_OP_NOTALL:
                $getstr = 'pgmenroldescnotall';
                break;
            default:
                $getstr = 'pgmenroldescnotany';
        }

        $strvar = new \stdClass();
        $strvar->desc = get_string($getstr, 'totara_cohort');

        list($sqlin, $sqlparams) = $DB->get_in_or_equal($this->listofids);
        $sqlparams[] = $ruleid;
        $sqlparams[] = CONTEXT_PROGRAM;
        $context_columns = \context_helper::get_preload_record_columns_sql('ctx');

        $sql = "SELECT p.id, p.fullname, crp.id AS paramid, {$context_columns}
            FROM {prog} p
            INNER JOIN {cohort_rule_params} crp ON p.id = " . $DB->sql_cast_char2int('crp.value') . "
            JOIN {context} ctx ON (ctx.instanceid = p.id)
            WHERE p.id {$sqlin}
            AND crp.name = 'listofids' AND crp.ruleid = ?
            AND ctx.contextlevel = ?
            ORDER BY sortorder, fullname";
        $programlist = $DB->get_records_sql($sql, $sqlparams);

        foreach ($programlist as $index => $program) {
            \context_helper::preload_from_record($program);
            $value = '"' . format_string($program->fullname, true, ['context' => \context_program::instance($program->id)]) . '"';
            if (!$static) {
                $value .= $this->param_delete_action_icon($program->paramid);
            }
            $programlist[$index] = \html_writer::tag('span', $value, array('class' => 'ruleparamcontainer'));
        }

        $this->add_missing_rule_params($programlist, $ruleid, $static);
        $paramseparator = \html_writer::tag('span', ', ', array('class' => 'ruleparamseparator'));
        $strvar->vars = implode($paramseparator, $programlist);

        return get_string('ruleformat-descvars', 'totara_cohort', $strvar);
    }

    /**
     * Identify and list missing rule components.
     *
     * @param array $programlists
     * @param int   $ruleinstanceid
     * @param bool  $static
     * @inheritdoc
     */
    protected function add_missing_rule_params(array &$ruledescriptions, $ruleinstanceid, $static = true) {
        global $DB;

        if (count($ruledescriptions) < count($this->listofids)) {
            $ruleparams = $DB->get_records(
                "cohort_rule_params", [
                    'ruleid' => $ruleinstanceid,
                    'name'   => 'listofids',
                ],
                "", "value, id AS paramid"
            );

            foreach ($this->listofids as $id) {
                if (!isset($ruledescriptions[$id])) {
                    // Missing program.
                    $item = isset($ruleparams[$id]) ? $ruleparams[$id] : null;
                    if (!$item) {
                        debugging("Missing a rule parameter for program id $id");
                        continue;
                    }

                    $value = "\"". get_string('deleteditem', 'totara_cohort', $id) . "\"";
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
