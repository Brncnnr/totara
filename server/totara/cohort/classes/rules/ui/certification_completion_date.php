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

use totara_cohort\rules\ui\base_selector_certification as base_selector;

class certification_completion_date extends base_selector {
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
    public function getExtraSelectedItemsPaneWidgets() {
        global $OUTPUT;

        $html = '';
        $html .= \html_writer::start_div();
        $html .= \html_writer::start_tag('form', array('id' => 'form_course_program_date', 'class' => 'mform cohort-treeview-dialog-extrafields'));

        $opmenufix = array(); // Operator menu for fixed date options.
        $opmenurel = array(); // Operator menu for relative date options.

        $opmenufix[COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN] = get_string('datemenufixeddatebefore', 'totara_cohort');
        $opmenufix[COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN] = get_string('datemenufixeddateafter', 'totara_cohort');

        $opmenurel[COHORT_RULE_COMPLETION_OP_WITHIN_PAST_DURATION] = get_string('datemenudurationwithinpast', 'totara_cohort');
        $opmenurel[COHORT_RULE_COMPLETION_OP_BEFORE_PAST_DURATION] = get_string('datemenudurationbeforepast', 'totara_cohort');

        // Set default values.
        $selected = isset($this->operator) ? $this->operator : '';
        $htmldate = get_string('datepickerlongyearplaceholder', 'totara_core');
        $class = 'cohorttreeviewsubmitfield';
        $duration = '';
        $radio2prop = $radio1prop = array('type' => 'radio', 'name' => 'fixeddynamic', 'checked' => 'checked', 'class' => $class);
        if (isset($this->operator) && array_key_exists($this->operator, $opmenufix)) {
            array_splice($radio2prop, 2);
            $htmldate = userdate($this->date, get_string('datepickerlongyearphpuserdate', 'totara_core'), 99, false);
        } else if (isset($this->operator) && array_key_exists($this->operator, $opmenurel)) {
            array_splice($radio1prop, 2);
            $duration = htmlspecialchars($this->date);
        } else {
            array_splice($radio2prop, 2);
        }

        // Fixed date.
        $html .= get_string('completionusercertifiedbeforeafter', 'totara_cohort');
        $html .= $OUTPUT->help_icon('rulehelp-cert-date', 'totara_cohort');
        $html .= \html_writer::start_tag('fieldset');

        $html .= \html_writer::start_tag('legend', array('class' => 'sr-only'));
        $html .= get_string('rulelegend-completedfixeddate', 'totara_cohort');
        $html .= \html_writer::end_tag('legend');

        $html .= \html_writer::start_tag('label', array('for' => 'fixedordynamic1', 'class' => 'sr-only'));
        $html .= get_string('rulelabel-fixeddate', 'totara_cohort');
        $html .= \html_writer::end_tag('label');

        $html .= \html_writer::empty_tag('input', array_merge(array('id' => 'fixedordynamic1', 'value' => '1'), $radio1prop));

        $html .= \html_writer::start_tag('label', array('for' => 'beforeaftermenu', 'class' => 'sr-only'));
        $html .= get_string('rulelabel-beforeorafter', 'totara_cohort');
        $html .= \html_writer::end_tag('label');

        $html .= \html_writer::select($opmenufix, 'beforeaftermenu', $selected, array(), array('id' => 'beforeaftermenu', 'class' => $class));

        $html .= \html_writer::start_tag('label', array('for' => 'completiondate', 'class' => 'sr-only'));
        $html .= get_string('rulelabel-completiondate', 'totara_cohort');
        $html .= \html_writer::end_tag('label');

        $html .= \html_writer::empty_tag('input', array('type' => 'text', 'size' => '10', 'id' => 'completiondate',
            'name' => 'date', 'value' => htmlspecialchars($htmldate), 'class' => $class));
        $html .= \html_writer::end_tag('fieldset');

        // Relative date.
        $html .= get_string('or', 'totara_cohort');
        $html .= \html_writer::start_tag('fieldset');

        $html .= \html_writer::start_tag('legend', array('class' => 'sr-only'));
        $html .= get_string('rulelegend-completedrelativedate', 'totara_cohort');
        $html .= \html_writer::end_tag('legend');

        $html .= \html_writer::start_tag('label', array('for' => 'fixedordynamic2', 'class' => 'sr-only'));
        $html .= get_string('rulelabel-relativedate', 'totara_cohort');
        $html .= \html_writer::end_tag('label');

        $html .= \html_writer::empty_tag('input', array_merge(array('id' => 'fixedordynamic2', 'value' => '2'), $radio2prop));

        $html .= \html_writer::start_tag('label', array('for' => 'menudurationmenu', 'class' => 'sr-only'));
        $html .= get_string('rulelabel-withinorbeforeprevious', 'totara_cohort');
        $html .= \html_writer::end_tag('label');

        $html .= \html_writer::select($opmenurel, 'durationmenu', $selected, array(), array('class' => $class));

        $html .= \html_writer::start_tag('label', array('for' => 'completiondurationdate', 'class' => 'sr-only'));
        $html .= get_string('rulelabel-days', 'totara_cohort');
        $html .= \html_writer::end_tag('label');

        $html .= \html_writer::empty_tag('input', array('type' => 'text', 'size' => '3', 'id' => 'completiondurationdate',
            'name' => 'durationdate', 'value' => $duration, 'class' => $class));
        $html .= get_string('completiondurationdays', 'totara_cohort');
        $html .= \html_writer::end_tag('fieldset');

        $html .= \html_writer::end_tag('form');
        $html .= \html_writer::end_div();

        return $html;
    }

    /**
     * @param cohort_rule_sqlhandler $sqlhandler
     * @return bool
     */
    public function handleDialogUpdate($sqlhandler){
        $fixedordynamic = required_param('fixeddynamic', PARAM_INT);
        switch($fixedordynamic) {
            case 1:
                $operator = required_param('beforeaftermenu', PARAM_INT);
                $date = totara_date_parse_from_format(get_string('datepickerlongyearparseformat', 'totara_core'),
                    required_param('date', PARAM_TEXT));
                break;
            case 2:
                $operator = required_param('durationmenu', PARAM_INT);
                $date = required_param('durationdate', PARAM_INT); // Convert number to seconds.
                break;
            default:
                return false;
        }
        $this->date = $sqlhandler->date = $date;
        $this->operator = $sqlhandler->operator = $operator;
        $this->listofids = $sqlhandler->listofids = explode(',', required_param('selected', PARAM_SEQUENCE));
        $sqlhandler->write();
    }

    /**
     * Get the description of this rule for the list of rules
     * @param int $ruleid
     * @param boolean $static only display static description, without action controls
     * @return string
     */
    public function getRuleDescription($ruleid, $static=true) {
        global $DB, $COHORT_RULE_COMPLETION_OP;
        if (!isset($this->operator) || !isset($this->listofids)) {
            return get_string('error:rulemissingparams', 'totara_cohort');
        }

        $strvar = new \stdClass();
        $strvar->desc = $this->description;
        $stringkey = '';
        switch ($this->operator) {
            case COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN:
                $stringkey = 'dateisbefore';
                $a = userdate($this->date, get_string('datepickerlongyearphpuserdate', 'totara_core'), 99, false);
                break;
            case COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN:
                $stringkey = 'dateisonorafter';
                $a = userdate($this->date, get_string('datepickerlongyearphpuserdate', 'totara_core'), 99, false);
                break;
            case COHORT_RULE_COMPLETION_OP_BEFORE_PAST_DURATION:
            case COHORT_RULE_COMPLETION_OP_WITHIN_PAST_DURATION:
            case COHORT_RULE_COMPLETION_OP_WITHIN_FUTURE_DURATION:
            case COHORT_RULE_COMPLETION_OP_AFTER_FUTURE_DURATION:
                $a = $this->date;
                $stringkey = "dateis{$COHORT_RULE_COMPLETION_OP[$this->operator]}";
                break;
        }
        $strvar->join = get_string($stringkey, 'totara_cohort', $a);

        list($sqlin, $sqlparams) = $DB->get_in_or_equal($this->listofids);
        $sqlparams[] = $ruleid;
        if ($this->pickertype == COHORT_PICKER_COURSE_COMPLETION) {
            $sql = "SELECT c.id, c.fullname, crp.id AS paramid
                FROM {course} c
                INNER JOIN {cohort_rule_params} crp ON c.id = " . $DB->sql_cast_char2int('crp.value') . "
                WHERE c.id {$sqlin}
                AND crp.name = 'listofids' AND crp.ruleid = ?
                ORDER BY sortorder, fullname";
        } else {
            $sql = "SELECT p.id, p.fullname, crp.id AS paramid
                FROM {prog} p
                INNER JOIN {cohort_rule_params} crp ON p.id = " . $DB->sql_cast_char2int('crp.value') . "
                WHERE p.id {$sqlin}
                AND crp.name = 'listofids' AND crp.ruleid = ?
                ORDER BY sortorder, fullname";
        }

        $courseprogramlist = $DB->get_records_sql($sql, $sqlparams);

        foreach ($courseprogramlist as $i => $c) {
            $value = '"' . format_string($c->fullname) . '"';
            if (!$static) {
                $value .= $this->param_delete_action_icon($c->paramid);
            }
            $courselist[$i] = \html_writer::tag('span', $value, array('class' => 'ruleparamcontainer'));
        };

        $this->add_missing_rule_params($courselist, $ruleid, $static);
        $paramseparator = \html_writer::tag('span', ', ', array('class' => 'ruleparamseparator'));
        $strvar->vars = implode($paramseparator, $courselist);

        return get_string('ruleformat-descjoinvars', 'totara_cohort', $strvar);
    }

    /**
     * @param array $ruledescriptions
     * @param int $ruleinstanceid
     * @param bool $static
     *
     * @return void
     */
    public function add_missing_rule_params(array &$ruledescriptions, $ruleinstanceid, $static = true) {
        global $DB;

        if (count($ruledescriptions) < count($this->listofids)) {
            $fullparams = $DB->get_records("cohort_rule_params", array(
                'ruleid' => $ruleinstanceid,
                'name' => 'listofids'
            ), "", "value AS certid, id AS paramid");

            foreach ($this->listofids as $certid) {
                if (!isset($ruledescriptions[$certid])) {
                    $item = isset($fullparams[$certid]) ? $fullparams[$certid] : null;

                    if (!$item) {
                        debugging("Missing certification id: {$certid} in rule params");
                        continue;
                    }

                    $a = (object) array('id' => $item->certid);
                    $value = get_string('deleteditem', 'totara_cohort', $a);
                    if (!$static) {
                        $value .= $this->param_delete_action_icon($item->paramid);
                    }

                    $ruledescriptions[$certid] = \html_writer::tag("span", $value, array(
                        'class' => 'ruleparamcontainer cohortdeletedparam'
                    ));
                }
            }
        }
    }
}
