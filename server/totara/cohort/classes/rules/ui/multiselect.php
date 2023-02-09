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
 * @package totara_cohort
 */

namespace totara_cohort\rules\ui;

defined('MOODLE_INTERNAL') || die();

use totara_cohort\rules\ui\base_form as base_form;

/**
 * UI for a rule defined by a multi-select menu, and a equals/notequals operator
 */
class multiselect extends base_form {

    /**
     * @var array
     */
    public $params = array(
        'equal' => 0,
        'exact' => 0,
        'listofvalues' => []
    );

    /**
     * The list of options in the menu. $value=>$label
     * @var array
     */
    public $options;

    /**
     * The name of the multi-select field the rule is based on.
     * @var string
     */
    private $fieldname;

    /**
     * Create a multiselect dialog, passing in the list of options
     * @param string $fieldname   - The name of the multiselect field
     * @param string $description - The description of the rule
     * @param array  $options     - All the options for the multi-select
     */
    public function __construct($fieldname, $description, $options){
        $this->fieldname = $fieldname;
        $this->description = $description;

        // This may be a string rather than a proper array, but we'll wait to clean
        // it up until it's actually needed.
        $this->options = $options;
    }

    /**
     * The form fields needed for this multiselect dialog.
     * That'll be the title, the equals & exact operators, and checkboxes for each option.
     * @param MoodleQuickForm $mform
     */
    protected function addFormFields(&$mform) {
        global $CFG;

        require_once($CFG->dirroot . '/totara/core/totara.php'); // We need this to display the icon.

        // Group the operators together (not/any)
        $operators = [];
        $operators[] = $mform->createElement('static', null, null, format_string($this->fieldname));

        $equaloptions = [
            COHORT_RULES_OP_IN_ISEQUALTO => get_string('rule_selector_is', 'totara_cohort'),
            COHORT_RULES_OP_IN_NOTEQUALTO => get_string('rule_selector_not', 'totara_cohort')
        ];
        $operators[] = $mform->createElement('select', 'equal', '', $equaloptions);

        $exactoptions = [
            COHORT_RULES_OP_IN_ANY => get_string('rule_selector_any', 'totara_cohort'),
            COHORT_RULES_OP_IN_ALL => get_string('rule_selector_all', 'totara_cohort')
        ];
        $operators[] = $mform->createElement('select', 'exact', '', $exactoptions);
        $mform->addGroup($operators, 'operators', '', ' ', false);

        // Group all of the options together.
        $options = [];
        $options[] = $mform->createElement('static', null, null, 'options:');
        foreach ($this->options as $hash => $option) {
            $name = format_string($option['option']);
            $icon = totara_icon_picker_preview('course', $option['icon'], '', $option['option']);
            $element = $mform->createElement('advcheckbox', "options[{$hash}]", null, "{$icon} {$name}", [], [0, 1]);
            if (!empty($this->listofvalues) && in_array($hash, $this->listofvalues))  {
                $element->setValue(1);
            }
            $options[] = $element;
        }
        $mform->addGroup($options, 'listofvalues', ' ', \html_writer::empty_tag('br'), false);
    }

    /**
     * Process the data returned by this UI element's form elements
     * @param cohort_rule_sqlhandler $sqlhandler
     */
    public function handleDialogUpdate($sqlhandler) {
        $equal = required_param('equal', PARAM_INT);
        $exact = required_param('exact', PARAM_INT);
        $options = required_param_array('options', PARAM_RAW);

        $this->equal = $sqlhandler->equal = $equal;
        $this->exact = $sqlhandler->exact = $exact;

        $listofvalues = [];
        foreach ($options as $key => $option) {
            if (!empty($option)) {
                $listofvalues[] = $key;
            }
        }
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
        if (!isset($this->equal) || !isset($this->exact) || !isset($this->listofvalues)) {
            return get_string('error:rulemissingparams', 'totara_cohort');
        }

        $strvar = new \stdClass();
        $strvar->desc = $this->description;

        $joinkey1 = $this->equal == COHORT_RULES_OP_IN_ISEQUALTO ? 'is' : 'not';
        $joinkey2 = $this->exact == COHORT_RULES_OP_IN_ALL ? 'all' : 'any';
        $strvar->join = get_string("ruleformat-descjoin-{$joinkey1}{$joinkey2}", 'totara_cohort');

        $items = [];
        foreach ($this->options as $hash => $option) {
            // Loop through options rather than values to get them in the correct order.
            if (in_array($hash, $this->listofvalues)) {
                $items[] = format_string($option['option']);
            }
        }

        $strvar->vars = '"' . htmlspecialchars(implode('", "', $items)) . '"';

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
        if  ($data = $form->get_submitted_data()) {

            if (!isset($data->equal) || !in_array($data->equal, [COHORT_RULES_OP_IN_ISEQUALTO, COHORT_RULES_OP_IN_NOTEQUALTO])) {
                $form->_form->addElement('html',
                    $OUTPUT->notification(get_string('rule_selector_failure', 'totara_cohort'), \core\output\notification::NOTIFY_ERROR)
                );
                return false;
            }

            if (!isset($data->exact) || !in_array($data->exact, [COHORT_RULES_OP_IN_ANY, COHORT_RULES_OP_IN_ALL])) {
                $form->_form->addElement('html',
                    $OUTPUT->notification(get_string('rule_selector_failure', 'totara_cohort'), \core\output\notification::NOTIFY_ERROR)
                );
                return false;
            }

            $lov = $data->options;
            $success = false;
            foreach ($this->options as $hash => $option) {
                if (!empty($lov[$hash])) {
                    // At least one has been selected, no need to check further.
                    $success = true;
                    break;
                }
            }
            // Checking whether the listofvalues being passed is empty or not. If it is empty, error should be returned
            if (!$success) {
                $form->_form->addElement('html',
                    $OUTPUT->notification(get_string('rule_selector_failure', 'totara_cohort'), \core\output\notification::NOTIFY_ERROR)
                );
            }
            return $success;
        }

        // If the form is not submitted at all, then there is no point to validate and false should be returned here
        return false;
    }
}
