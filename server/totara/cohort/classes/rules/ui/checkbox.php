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

use totara_cohort\rules\ui\menu as menu;

/**
 * UI for a rule that indicates whether or not a checkbox is ticked
 */
class checkbox extends menu {
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
    public function __construct($description, $options=false){
        $this->description = $description;

        // This may be a string rather than a proper array, but we'll wait to clean
        // it up until it's actually needed.
        if (!$options){
            $this->options = array(
                0=>get_string('checkboxno', 'totara_cohort'),
                1=>get_string('checkboxyes', 'totara_cohort')
            );
        } else {
            $this->options = $options;
        }
    }

    /**
     * The form elements needed for this UI (just the "checked/not-checked" menu!)
     * @param MoodleQuickForm $mform
     */
    protected function addFormFields(&$mform) {
        $mform->addElement(
            'select',
            'listofvalues',
            '',
            $this->options
        );
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
            // Checking whether the listofvalues being passed is set, and in the acceptable options.
            if (!isset($data->listofvalues) || !in_array($data->listofvalues, array_keys($this->options))) {
                $form->_form->addElement('html',
                    $OUTPUT->notification(get_string('rule_selector_failure', 'totara_cohort'), \core\output\notification::NOTIFY_ERROR)
                );
                return false;
            }
            return true;
        }

        // If the form is not submitted at all, then there is no point to validate and false should be returned here
        return false;
    }

    /**
     * Process the data returned by this UI element's form elements
     * @param cohort_rule_sqlhandler $sqlhandler
     */
    public function handleDialogUpdate($sqlhandler) {
        $listofvalues = required_param('listofvalues', PARAM_BOOL);
        if (is_array($listofvalues)) {
            $listofvalues = array_pop($listofvalues);
        }
        // Checkbox operator is always "equal"
        $this->equal = $sqlhandler->equal = 1;
        $this->listofvalues = $sqlhandler->listofvalues = (int) $listofvalues;
        $sqlhandler->write();
        $this->listofvalues = array($listofvalues);
    }
}
