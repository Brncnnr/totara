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

use totara_cohort\rules\ui\base_selector_course as base_selector;

class course_enrolment_allanynotallnone extends base_selector {
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
        $operatormenu[COHORT_RULE_ENROLMENT_OP_ALL] = get_string('enrolmentmenuall', 'totara_cohort');
        $operatormenu[COHORT_RULE_ENROLMENT_OP_ANY] = get_string('enrolmentmenuany', 'totara_cohort');
        $operatormenu[COHORT_RULE_ENROLMENT_OP_NOTALL] = get_string('enrolmentmenunotall', 'totara_cohort');
        $operatormenu[COHORT_RULE_ENROLMENT_OP_NONE] = get_string('enrolmentmenunotany', 'totara_cohort');
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
                $getstr = 'crsenroldescall';
                break;
            case COHORT_RULE_ENROLMENT_OP_ANY:
                $getstr = 'crsenroldescany';
                break;
            case COHORT_RULE_ENROLMENT_OP_NOTALL:
                $getstr = 'crsenroldescnotall';
                break;
            default:
                $getstr = 'crsenroldescnotany';
        }

        $strvar = new \stdClass();
        $strvar->desc = get_string($getstr, 'totara_cohort');

        list($sqlin, $sqlparams) = $DB->get_in_or_equal($this->listofids);
        $sqlparams[] = $ruleid;
        $sqlparams[] = CONTEXT_COURSE;
        $context_columns = \context_helper::get_preload_record_columns_sql('ctx');

        $sql = "SELECT c.id, c.fullname, crp.id AS paramid, {$context_columns}
            FROM {course} c
            INNER JOIN {cohort_rule_params} crp ON c.id = " . $DB->sql_cast_char2int('crp.value') . "
            JOIN {context} ctx ON (ctx.instanceid = c.id)
            WHERE c.id {$sqlin}
            AND crp.name = 'listofids' AND crp.ruleid = ?
            AND ctx.contextlevel = ?
            ORDER BY sortorder, fullname";
        $courselist = $DB->get_records_sql($sql, $sqlparams);

        foreach ($courselist as $index => $course) {
            \context_helper::preload_from_record($course);
            $value = '"' . format_string($course->fullname, true, ['context' => \context_course::instance($course->id)]) . '"';
            if (!$static) {
                $value .= $this->param_delete_action_icon($course->paramid);
            }
            $courselist[$index] = \html_writer::tag('span', $value, ['class' => 'ruleparamcontainer']);
        }

        $this->add_missing_rule_params($courselist, $ruleid, $static);
        $paramseparator = \html_writer::tag('span', ', ', ['class' => 'ruleparamseparator']);
        $strvar->vars = implode($paramseparator, $courselist);

        return get_string('ruleformat-descvars', 'totara_cohort', $strvar);
    }

    /**
     * Identify and list missing rule components.
     *
     * @param array $courselists
     * @param int   $ruleinstanceid
     * @param bool  $static
     * @inheritdoc
     */
    protected function add_missing_rule_params(array &$courselists, $ruleinstanceid, $static = true) {
        global $DB;

        if (count($courselists) < count($this->listofids)) {
            $fullparams = $DB->get_records(
                "cohort_rule_params",
                ['ruleid' => $ruleinstanceid, 'name' => 'listofids'],
                "",
                "value AS courseid, id AS paramid"
            );

            foreach ($this->listofids as $courseid) {
                if (!isset($courselists[$courseid])) {
                    // Missing course here.
                    $item = isset($fullparams[$courseid]) ? $fullparams[$courseid] : null;
                    if (!$item) {
                        debugging("Missing the rule parameter for course {$courseid}");
                        continue;
                    }

                    $value = "\"". get_string('deleteditem', 'totara_cohort', $courseid) . "\"";
                    if (!$static) {
                        $value .= $this->param_delete_action_icon($item->paramid);
                    }

                    $courselists[$courseid]  =
                        \html_writer::tag('span', $value, array('class' => 'ruleparamcontainer cohortdeletedparam'));
                }
            }
        }
    }
}
