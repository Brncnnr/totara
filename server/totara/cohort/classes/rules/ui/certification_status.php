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

class certification_status extends base_selector {
    /**
     * @var array
     */
    public $params = array(
        'listofids' => 1,
        'status' => 0,
        'assignmentstatus' => 0
    );

    /**
     * @return string
     */
    public function getExtraSelectedItemsPaneWidgets() {

        $status = isset($this->status) ? explode(',', $this->status) : array(\cohort_rule_sqlhandler_certification_status::CERTIFIED);
        $assignmentstatus = isset($this->assignmentstatus) ? explode(',', $this->assignmentstatus) :
            array(\cohort_rule_sqlhandler_certification_status::ASSIGNED, \cohort_rule_sqlhandler_certification_status::UNASSIGNED);

        $checkboxatr = array('type' => 'checkbox', 'value' => '0', 'class' => 'cohorttreeviewsubmitfield');

        $html = '';
        $html .= \html_writer::start_div();
        $html .= \html_writer::start_tag('form', array('id' => 'form_certification_status', 'class' => 'mform cohort-treeview-dialog-extrafields'));

        // Certification status.
        $html .= \html_writer::start_tag('fieldset', array('id' => 'certifstatus', 'name' => 'certifstatus', 'class' => 'cohorttreeviewsubmitfield'));
        $html .= \html_writer::start_tag('legend', array('class' => 'sr-only'));
        $html .= get_string('rulelegend-certificationstatus', 'totara_cohort');
        $html .= \html_writer::end_tag('legend');
        $html .= \html_writer::tag('p', get_string('rulename-learning-certificationstatus', 'totara_cohort'));

        $atr = array_merge($checkboxatr, array('id' => 'certifstatus_currentlycertified', 'name' => 'certifstatus_currentlycertified'));
        if (in_array(\cohort_rule_sqlhandler_certification_status::CERTIFIED, $status)) {
            $atr['checked'] = 'checked';
            $atr['value'] = '1';
        }
        $html .= \html_writer::start_div();
        $html .= \html_writer::start_tag('label', array('for' => 'certifstatus_currentlycertified', 'class' => 'sr-only'));
        $html .= get_string('ruledesc-learning-certificationstatus-currentlycertified', 'totara_cohort');
        $html .= \html_writer::end_tag('label');
        $html .= \html_writer::empty_tag('input', $atr);
        $html .= get_string('ruledesc-learning-certificationstatus-currentlycertified', 'totara_cohort');
        $html .= \html_writer::end_div();

        $atr = array_merge($checkboxatr, array('id' => 'certifstatus_currentlyexpired', 'name' => 'certifstatus_currentlyexpired'));
        if (in_array(\cohort_rule_sqlhandler_certification_status::EXPIRED, $status)) {
            $atr['checked'] = 'checked';
            $atr['value'] = '1';
        }
        $html .= \html_writer::start_div();
        $html .= \html_writer::start_tag('label', array('for' => 'certifstatus_currentlyexpired', 'class' => 'sr-only'));
        $html .= get_string('ruledesc-learning-certificationstatus-currentlyexpired', 'totara_cohort');
        $html .= \html_writer::end_tag('label');
        $html .= \html_writer::empty_tag('input', $atr);
        $html .= get_string('ruledesc-learning-certificationstatus-currentlyexpired', 'totara_cohort');
        $html .= \html_writer::end_div();

        $atr = array_merge($checkboxatr, array('id' => 'certifstatus_nevercertified', 'name' => 'certifstatus_nevercertified'));
        if (in_array(\cohort_rule_sqlhandler_certification_status::NEVER_CERTIFIED, $status)) {
            $atr['checked'] = 'checked';
            $atr['value'] = '1';
        }
        $html .= \html_writer::start_div();
        $html .= \html_writer::start_tag('label', array('for' => 'certifstatus_nevercertified', 'class' => 'sr-only'));
        $html .= get_string('ruledesc-learning-certificationstatus-nevercertified', 'totara_cohort');
        $html .= \html_writer::end_tag('label');
        $html .= \html_writer::empty_tag('input', $atr);
        $html .= get_string('ruledesc-learning-certificationstatus-nevercertified', 'totara_cohort');
        $html .= \html_writer::end_div();
        $html .= \html_writer::end_tag('fieldset');

        $html .= \html_writer::empty_tag('br');

        // Assignment status.
        $html .= \html_writer::start_tag('fieldset', array('id' => 'certifassignmentstatus', 'name' => 'certifassignmentstatus', 'class' => 'cohorttreeviewsubmitfield'));

        $html .= \html_writer::start_tag('legend', array('class' => 'sr-only'));
        $html .= get_string('rulelegend-certificationassignmentstatus', 'totara_cohort');
        $html .= \html_writer::end_tag('legend');

        $html .= \html_writer::tag('p', get_string('ruledesc-learning-certificationstatus-assignmentstatus', 'totara_cohort'));

        $atr = array_merge($checkboxatr, array('id' => 'certifassignmentstatus_assigned', 'name' => 'certifassignmentstatus_assigned'));
        if (in_array(\cohort_rule_sqlhandler_certification_status::ASSIGNED, $assignmentstatus)) {
            $atr['checked'] = 'checked';
            $atr['value'] = '1';
        }
        $html .= \html_writer::start_div();
        $html .= \html_writer::start_tag('label', array('for' => 'certifassignmentstatus_assigned', 'class' => 'sr-only'));
        $html .= get_string('ruledesc-learning-certificationstatus-assigned', 'totara_cohort');
        $html .= \html_writer::end_tag('label');
        $html .= \html_writer::empty_tag('input', $atr);
        $html .= get_string('ruledesc-learning-certificationstatus-assigned', 'totara_cohort');
        $html .= \html_writer::end_div();

        $atr = array_merge($checkboxatr, array('id' => 'certifassignmentstatus_unassigned', 'name' => 'certifassignmentstatus_unassigned'));
        if (in_array(\cohort_rule_sqlhandler_certification_status::UNASSIGNED, $assignmentstatus)) {
            $atr['checked'] = 'checked';
            $atr['value'] = '1';
        }
        $html .= \html_writer::start_div();
        $html .= \html_writer::start_tag('label', array('for' => 'certifassignmentstatus_unassigned', 'class' => 'sr-only'));
        $html .= get_string('ruledesc-learning-certificationstatus-unassigned', 'totara_cohort');
        $html .= \html_writer::end_tag('label');
        $html .= \html_writer::empty_tag('input', $atr);
        $html .= get_string('ruledesc-learning-certificationstatus-unassigned', 'totara_cohort');
        $html .= \html_writer::end_div();
        $html .= \html_writer::end_tag('fieldset');

        $html .= \html_writer::empty_tag('br');
        $html .= \html_writer::end_tag('form');
        $html .= \html_writer::end_div();

        return $html;
    }

    /**
     * @param cohort_rule_sqlhandler $sqlhandler
     */
    public function handleDialogUpdate($sqlhandler) {
        $certifstatus_currentlycertified = optional_param('certifstatus_currentlycertified', false, PARAM_TEXT);
        $certifstatus_currentlyexpired = optional_param('certifstatus_currentlyexpired', false, PARAM_TEXT);
        $certifstatus_nevercertified = optional_param('certifstatus_nevercertified', false, PARAM_TEXT);

        $certifassignmentstatus_assigned = optional_param('certifassignmentstatus_assigned', false, PARAM_TEXT);
        $certifassignmentstatus_unassigned = optional_param('certifassignmentstatus_unassigned', false, PARAM_TEXT);

        $status = array();

        if ($certifstatus_currentlycertified) {
            $status[] = \cohort_rule_sqlhandler_certification_status::CERTIFIED;
        }

        if ($certifstatus_currentlyexpired) {
            $status[] = \cohort_rule_sqlhandler_certification_status::EXPIRED;
        }

        if ($certifstatus_nevercertified) {
            $status[] = \cohort_rule_sqlhandler_certification_status::NEVER_CERTIFIED;
        }

        if (empty($status)) {
            throw new \coding_exception('Dynamic audience certification rule has missing status');
        }

        $assignmentstatus = array();

        if ($certifassignmentstatus_assigned) {
            $assignmentstatus[] = \cohort_rule_sqlhandler_certification_status::ASSIGNED;
        }

        if ($certifassignmentstatus_unassigned) {
            $assignmentstatus[] = \cohort_rule_sqlhandler_certification_status::UNASSIGNED;
        }

        if (empty($assignmentstatus)) {
            throw new \coding_exception('Dynamic audience certification rule has missing assignment status');;
        }

        $this->listofids = $sqlhandler->listofids = explode(',', required_param('selected', PARAM_SEQUENCE));
        $this->status = $sqlhandler->status = implode(',', $status);
        $this->assignmentstatus = $sqlhandler->assignmentstatus = implode(',', $assignmentstatus);

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

        if (!isset($this->status) || !isset($this->assignmentstatus) || !isset($this->listofids)) {
            return get_string('error:rulemissingparams', 'totara_cohort');
        }

        $strvar = new \stdClass();
        $strvar->desc = $this->description;

        // Status.
        $status = explode(',', $this->status);
        array_walk($status, function(&$item) {
            $item = "'" . get_string(\cohort_rule_sqlhandler_certification_status::get_status($item), 'totara_cohort') . "'";
        });
        $strvar->status = implode(', ', $status);

        // Assignment status.
        $assignmentstatus = explode(',', $this->assignmentstatus);
        array_walk($assignmentstatus, function(&$item) {
            $item = "'" . get_string(\cohort_rule_sqlhandler_certification_status::get_assignment_status($item), 'totara_cohort') . "'";
        });
        $strvar->assignmentstatus = implode(', ', $assignmentstatus);

        list($sqlin, $sqlparams) = $DB->get_in_or_equal($this->listofids);
        $sqlparams[] = $ruleid;

        $sql = "SELECT p.id, p.fullname, crp.id AS paramid
            FROM {prog} p
            INNER JOIN {cohort_rule_params} crp ON p.id = " . $DB->sql_cast_char2int('crp.value') . "
            WHERE p.id {$sqlin}
            AND crp.name = 'listofids' AND crp.ruleid = ?
            ORDER BY sortorder, fullname";

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

        return get_string('ruleformat-certificationstatus', 'totara_cohort', $strvar);
    }


    /**
     * @param array $ruledescriptions
     * @param int $ruleinstanceid
     * @param bool $static
     * @return void
     */
    protected function add_missing_rule_params(array &$ruledescriptions, $ruleinstanceid, $static = true) {
        global $DB;

        if (count($ruledescriptions) < count($this->listofids)) {
            // Detected there are missing certifications
            $fullparams = $DB->get_records("cohort_rule_params", array(
                'ruleid' => $ruleinstanceid,
                'name' => 'listofids'
            ),  "", "value AS certid, id AS paramid");

            foreach ($this->listofids as $certid) {
                if (!isset($ruledescriptions[$certid])) {
                    $item = isset($fullparams[$certid]) ? $fullparams[$certid] : null;
                    if (!$item) {
                        debugging("Missing certification {$certid} in rule's params");
                        continue;
                    }

                    $a = (object) array('id' => $item->certid);
                    $value = "\"" . get_string('deleteditem', 'totara_cohort', $a) . "\"";
                    if (!$static) {
                        $value .= $this->param_delete_action_icon($item->paramid);
                    }

                    $ruledescriptions[$certid] = \html_writer::tag('span', $value, array(
                        'class' => 'ruleparamcontainer cohortdeletedparam'
                    ));
                }
            }
        }
    }
}
