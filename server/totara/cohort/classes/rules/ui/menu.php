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

use totara_cohort\rules\ui\base_form as base_form;

/**
 * UI for a rule defined by a multi-select menu, and a equals/notequals operator
 */
class menu extends base_form {
    /**
     * @var array
     */
    public $params = array(
        'equal' => 0,
        'listofvalues' => 1
    );

    /**
     * The list of options in the menu. $value=>$label
     * @var array
     */
    public $options;

    /**
     * Create a menu, passing in the list of options
     * @param $menu mixed An array of menu options (value=>label), or a user_info_field1 id
     */
    public function __construct($description, $options){
        $this->description = $description;

        // This may be a string rather than a proper array, but we'll wait to clean
        // it up until it's actually needed.
        $this->options = $options;
    }


    /**
     * The form fields needed for this dialog. That'll be, the "equal/notequal" menu, plus
     * the menu of options. Since the menu of options is a multiple select, it needs validation
     * @param MoodleQuickForm $mform
     */
    protected function addFormFields(&$mform) {

        // Put the two menus on one row so it'll look cooler
        $row = array();
        $row[0] = $mform->createElement(
            'select',
            'equal',
            '',
            array(
                // TODO TL-7096 - use COHORT_RULES_OP_IN_ISEQUALTO and COHORT_RULES_OP_IN_NOTEQUALTO, it will require db upgrade.
                COHORT_RULES_OP_IN_EQUAL    => get_string('equalto','totara_cohort'),
                COHORT_RULES_OP_IN_NOTEQUAL => get_string('notequalto', 'totara_cohort')
            )
        );
        if (is_object($this->options)) {
            $options = $this->options_from_sqlobj($this->options);
        } else {
            $options = $this->options;
        }
        // Remove empty values from select $options.
        // Should not use UserCustomField(Choose) to select empty values.
        $options = array_filter($options);
        $row[1] = $mform->createElement(
            'select',
            'listofvalues',
            '',
            $options,
            array('size' => 10, 'data-error-message' => get_string('rule_selector_failure', 'totara_cohort'))
        );
        // todo: The UI mockup shows a fancy ajax thing to add/remove selected items.
        // For now, using a humble multi-select
        $row[1]->setMultiple(true);
        $mform->addGroup($row, 'row1', '', '', false);
    }


    /**
     * Process the data returned by this UI element's form elements
     * @param cohort_rule_sqlhandler $sqlhandler
     */
    public function handleDialogUpdate($sqlhandler) {
        $equal = required_param('equal', PARAM_INT);
        $listofvalues = required_param_array('listofvalues', PARAM_TEXT);
        if (!is_array($listofvalues)) {
            $listofvalues = array($listofvalues);
        }
        array_walk(
            $listofvalues,
            function(&$value, $key){
                $value = trim($value);
            }
        );
        $this->equal = $sqlhandler->equal = $equal;
        $this->listofvalues = $sqlhandler->listofvalues = $listofvalues;
        $sqlhandler->write();
    }

    /**
     * Get the description of this rule for the list of rules
     * @param int $ruleid
     * @param boolean $static only display static description, without action controls
     * @return string
     */
    public function getRuleDescription($ruleid, $static=true) {
        global $COHORT_RULES_OP_IN;
        // TODO TL-7096 - use COHORT_RULES_OP_IN_ISEQUALTO and COHORT_RULES_OP_IN_NOTEQUALTO, it will require db upgrade.

        if (!isset($this->equal) || !isset($this->listofvalues)) {
            return get_string('error:rulemissingparams', 'totara_cohort');
        }

        $strvar = new \stdClass();
        $strvar->desc = $this->description;
        $strvar->join = get_string("is{$COHORT_RULES_OP_IN[$this->equal]}to", 'totara_cohort');

        if (is_object($this->options)) {
            $selected = $this->options_from_sqlobj($this->options, $this->listofvalues);
        } else {
            $selected = array_intersect_key($this->options, array_flip($this->listofvalues));
        }

        array_walk($selected, function (&$value, $key) {
            // Adding quotations marks here.
            $value = htmlspecialchars("\"{$value}\"");
        });

        $this->add_missing_rule_params($selected, $ruleid, $static);
        // append the list of selected items
        $strvar->vars = implode(', ', $selected);

        return get_string('ruleformat-descjoinvars', 'totara_cohort', $strvar);
    }

    /**
     * A method for validating the form submitted data
     * @return bool
     */
    public function validateResponse() {
        /** @var core_renderer $OUTPUT */
        global $OUTPUT;
        $form = $this->constructForm();
        if ($data = $form->get_submitted_data()) {

            // Check that the equal field is set to a valid value.
            if (!isset($data->equal) || !in_array($data->equal, [COHORT_RULES_OP_IN_EQUAL, COHORT_RULES_OP_IN_NOTEQUAL])) {
                $form->_form->addElement('html',
                    $OUTPUT->notification(get_string('rule_selector_failure', 'totara_cohort'), \core\output\notification::NOTIFY_ERROR)
                );
                return false;
            }

            // Checking whether the listofvalues being passed is empty or not. If it is empty, error should be returned
            if (empty($data->listofvalues)) {
                $form->_form->addElement('html',
                    $OUTPUT->notification(get_string('rule_selector_failure', 'totara_cohort'), \core\output\notification::NOTIFY_ERROR)
                );
                return false;
            } else {
                // Note: any values in listofvalues that aren't in options should have been removed on form submission.
                if (is_object($this->options)) {
                    $options = $this->options_from_sqlobj($this->options);
                } else {
                    $options = $this->options;
                }
                $options = array_keys($options);

                foreach ($data->listofvalues as $lov) {
                    if (!in_array($lov, $options)) {
                        $form->_form->addElement('html',
                            $OUTPUT->notification(get_string('rule_selector_failure', 'totara_cohort'), \core\output\notification::NOTIFY_ERROR)
                        );
                        return false;
                    }
                }
            }

            return true;
        }

        // If the form is not submitted at all, then there is no point to validate and false should be returned here
        return false;
    }

    /**
     * Retrieve menu options by constructing sql string from an sql object
     * and then querying the database
     *
     * @param object $sqlobj the sql object instance to construct the query from
     *                      e.g stdClass Object
    (
    [select] => DISTINCT data AS mkey, data AS mval
    [from] => {user_info_data}
    [where] => fieldid = ?
    [orderby] => data
    [valuefield] => data
    [sqlparams] => Array
    (
    [0] => 1
    )

    )
     * @param array $selectedvals selected values (optional)
     * @return array of menu options
     */
    protected function options_from_sqlobj($sqlobj, $selectedvals=null) {
        global $DB;

        $sql = "SELECT {$sqlobj->select} FROM {$sqlobj->from} ";

        $sqlparams = array();
        if ($selectedvals !== null) {
            if (!empty($selectedvals)) {
                list($sqlin, $sqlparams) = $DB->get_in_or_equal($selectedvals);
            } else {
                // dummiez to ensure nothing gets returned :D
                $sqlin = ' IN (?) ';
                $sqlparams = array(0);
            }
        }
        if (empty($sqlobj->where)) {
            $sql .= ' WHERE ';
        } else {
            $sql .= " WHERE {$sqlobj->where} ";
        }
        if (!empty($sqlin)) {
            $sql .= " AND {$DB->sql_compare_text($sqlobj->valuefield, 255)} {$sqlin} ";
        }

        if (!empty($sqlobj->orderby)) {
            $sql .= " ORDER BY {$sqlobj->orderby}";
        }

        if (!empty($sqlobj->sqlparams)) {
            $sqlparams = array_merge($sqlobj->sqlparams, $sqlparams);
        }

        return $DB->get_records_sql_menu($sql, $sqlparams, 0, COHORT_RULES_UI_MENU_LIMIT);
    }

    /**
     * @param array $ruledescriptions
     * @param int $ruleinstanceid
     * @param bool $static
     * @return void
     */
    protected function add_missing_rule_params(array &$ruledescriptions, $ruleinstanceid, $static = true) {
        global $DB;

        if (count($ruledescriptions) < count($this->listofvalues)) {
            // Detected that there are missing records in cohort's rules params.
            $fullparams = $DB->get_records('cohort_rule_params', array(
                'ruleid' => $ruleinstanceid,
                'name' => 'listofvalues'
            ), "", " value AS optionid, id AS paramid");

            if (is_object($this->options)) {
                $options = $this->options_from_sqlobj($this->options);
            } else {
                $options = $this->options;
            }

            foreach ($this->listofvalues as $optioninstanceid) {
                if (!isset($options[$optioninstanceid])) {
                    $item = isset($fullparams[$optioninstanceid]) ? $fullparams[$optioninstanceid] : null;
                    if (!$item) {
                        debugging("Missing {$optioninstanceid} in full params");
                        continue;
                    }

                    $a = (object) array('id' => $optioninstanceid);
                    $value = "\"" . get_string("deleteditem", "totara_cohort", $a) . "\"";

                    $ruledescriptions[$optioninstanceid] = \html_writer::tag('span', $value, array(
                        'class' => 'ruleparamcontainer cohortdeletedparam'
                    ));
                }
            }
        }
    }
}
