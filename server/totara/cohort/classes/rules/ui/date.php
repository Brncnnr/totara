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
 * UI for a rule that needs a date picker
 */
class date extends base_form {
    /**
     * @var array
     */
    public $params = array(
        'operator' => 0,
        'date' => 0,
    );

    /**
     * @var strin
     */
    public $description;

    /**
     * @var string
     */
    public $formclass = 'totara_cohort\rules\ui\form_date';

    /** @var array $operatorsfixed */
    private static $operatorsfixed = [];

    /** @var array $operatorsdynamic */
    private static $operatorsdynamic = [];

    const COHORT_RULE_DATE_FIXED = 1;
    const COHORT_RULE_DATE_DYNAMIC = 2;


    /**
     * cohort_rule_ui_date constructor.
     * @param $description
     */
    public function __construct($description){
        $this->description = $description;

        self::$operatorsfixed = [
            COHORT_RULE_DATE_OP_BEFORE_FIXED_DATE => get_string('datemenufixeddatebeforeandon', 'totara_cohort'),
            COHORT_RULE_DATE_OP_AFTER_FIXED_DATE  => get_string('datemenufixeddateafterandon', 'totara_cohort')
        ];

        self::$operatorsdynamic = [
            COHORT_RULE_DATE_OP_BEFORE_PAST_DURATION   => get_string('datemenudurationbeforepast', 'totara_cohort'),
            COHORT_RULE_DATE_OP_WITHIN_PAST_DURATION   => get_string('datemenudurationwithinpast', 'totara_cohort'),
            COHORT_RULE_DATE_OP_WITHIN_FUTURE_DURATION => get_string('datemenudurationwithinfuture', 'totara_cohort'),
            COHORT_RULE_DATE_OP_AFTER_FUTURE_DURATION  => get_string('datemenudurationafterfuture', 'totara_cohort')
        ];

        if ($this->rule == 'systemaccess-firstlogin' || $this->rule == 'systemaccess-lastlogin') {
            // Don't add future options for login dates as they are impossible.
            self::$operatorsdynamic = [
                COHORT_RULE_DATE_OP_BEFORE_PAST_DURATION   => get_string('datemenudurationbeforepast', 'totara_cohort'),
                COHORT_RULE_DATE_OP_WITHIN_PAST_DURATION   => get_string('datemenudurationwithinpast', 'totara_cohort'),
            ];
        } else {
            self::$operatorsdynamic = [
                COHORT_RULE_DATE_OP_BEFORE_PAST_DURATION   => get_string('datemenudurationbeforepast', 'totara_cohort'),
                COHORT_RULE_DATE_OP_WITHIN_PAST_DURATION   => get_string('datemenudurationwithinpast', 'totara_cohort'),
                COHORT_RULE_DATE_OP_WITHIN_FUTURE_DURATION => get_string('datemenudurationwithinfuture', 'totara_cohort'),
                COHORT_RULE_DATE_OP_AFTER_FUTURE_DURATION  => get_string('datemenudurationafterfuture', 'totara_cohort')
            ];
        }
    }

    /**
     * Fill in the default form values. For this dialog, we need to specify which of the two
     * rows is active based on the selected operator. And if it's the date row, we need to
     * format the date from a timestamp to a user date
     */
    protected function addFormData() {
        // Set up default values and stuff
        $formdata = array();
        $formdata['fixedordynamic'] = self::COHORT_RULE_DATE_FIXED;
        if (isset($this->operator)) {
            if (in_array($this->operator, array_keys(self::$operatorsfixed))) {
                $formdata['fixedordynamic'] = self::COHORT_RULE_DATE_FIXED;
                $formdata['beforeaftermenu'] = $this->operator;
                if (!empty($this->date)) {
                    $formdata['beforeafterdatetime'] = $this->date;
                }
            } else if (in_array($this->operator, array_keys(self::$operatorsdynamic))) {
                $formdata['fixedordynamic'] = self::COHORT_RULE_DATE_DYNAMIC;
                $formdata['durationmenu'] = $this->operator;
                if (isset($this->date)) {
                    $formdata['durationdate'] = $this->date;
                }
            } else {
                $formdata['fixedordynamic'] = self::COHORT_RULE_DATE_FIXED;
            }
        }
        return $formdata;
    }

    /**
     * Form fields for this dialog. We have the elements on two rows, with the top row being for before/after a fixed date,
     * and the bottom row being for before/after/within a fixed present/past duration. A radio button called "fixedordynamic"
     * indicates which one is selected
     *
     * @param MoodleQuickForm $mform
     */
    public function addFormFields(&$mform) {
        global $PAGE;
        $mform->updateAttributes(array('class' => 'dialog-nobind mform'));

        // Put everything on two rows to make it look cooler.
        $row = array();
        $row[0] = $mform->createElement('radio', 'fixedordynamic', '', '', self::COHORT_RULE_DATE_FIXED);
        $row[1] = $mform->createElement(
            'select',
            'beforeaftermenu',
            '',
            self::$operatorsfixed
        );
        $row[2] = $mform->createElement('date_time_selector', 'beforeafterdatetime', '', array('showtimezone' => true));
        $mform->addGroup($row, 'beforeafterrow', '', null, false);

        $row = array();
        $row[0] = $mform->createElement('radio', 'fixedordynamic', '', '', 2);
        $row[1] = $mform->createElement('select', 'durationmenu', '', self::$operatorsdynamic);
        $row[2] = $mform->createElement('text', 'durationdate', '');
        $row[3] = $mform->createElement('static', '', '', get_string('durationdays', 'totara_cohort'));
        $mform->addGroup($row, 'durationrow', '', '', false);

        $mform->disabledIf('beforeaftermenu','fixedordynamic','neq',1);
        $mform->disabledIf('beforeafterdatetime[day]','fixedordynamic','neq',1);
        $mform->disabledIf('beforeafterdatetime[month]','fixedordynamic','neq',1);
        $mform->disabledIf('beforeafterdatetime[year]','fixedordynamic','neq',1);
        $mform->disabledIf('beforeafterdatetime[hour]','fixedordynamic','neq',1);
        $mform->disabledIf('beforeafterdatetime[minute]','fixedordynamic','neq',1);
        $mform->disabledIf('beforeafterdatetime[calendar]','fixedordynamic','neq',1);
        $mform->disabledIf('beforeafterdatetime[timezone]','fixedordynamic','neq',1);
        $mform->disabledIf('durationmenu','fixedordynamic','neq',2);
        $mform->disabledIf('durationdate','fixedordynamic','neq',2);
    }

    /**
     * Print a description of the rule in text, for the rules list page
     * @param int $ruleid
     * @param boolean $static only display static description, without action controls
     * @return string
     */
    public function getRuleDescription($ruleid, $static=true) {
        global $COHORT_RULE_DATE_OP;

        if (!isset($this->operator) || !isset($this->date)) {
            return get_string('error:rulemissingparams', 'totara_cohort');
        }

        $strvar = new \stdClass();
        $strvar->desc = $this->description;

        $a = '';
        $stringkey = '';
        if (in_array($this->operator, array_keys(self::$operatorsfixed))) {
            $a = userdate($this->date, get_string('strftimedatetimelong', 'langconfig'));
            // Unlike the date (no timezone) field, the date/time field "before/after" rule includes current date as well.
            // So, we need to use different strings here.
            if ($this->operator == COHORT_RULE_DATE_OP_AFTER_FIXED_DATE) {
                $stringkey = 'dateisonorafter';
            } else {
                $stringkey = 'dateisonorbefore';
            }
        } else if (in_array($this->operator, array_keys(self::$operatorsdynamic))) {
            $stringkey = "dateis{$COHORT_RULE_DATE_OP[$this->operator]}";
            $a = $this->date;
        }

        $strvar->vars = get_string($stringkey, 'totara_cohort', $a);

        return get_string('ruleformat-descvars', 'totara_cohort', $strvar);
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
            if (!isset($data->fixedordynamic)) {
                $form->_form->addElement('html',
                    $OUTPUT->notification(get_string('rule_selector_failure', 'totara_cohort'), \core\output\notification::NOTIFY_ERROR)
                );
                return false;
            }

            if ($data->fixedordynamic == self::COHORT_RULE_DATE_FIXED) {
                if (!isset($data->beforeaftermenu) || !in_array($data->beforeaftermenu, array_keys(self::$operatorsfixed))) {
                    $form->_form->addElement('html',
                        $OUTPUT->notification(get_string('rule_selector_failure', 'totara_cohort'), \core\output\notification::NOTIFY_ERROR)
                    );
                    return false;
                }

                if (empty($data->beforeafterdatetime_raw)) {
                    $form->_form->addElement('html',
                        $OUTPUT->notification(get_string('rule_selector_failure', 'totara_cohort'), \core\output\notification::NOTIFY_ERROR)
                    );
                    return false;
                }

                if (empty($data->beforeafterdatetime_timezone)) {
                    $form->_form->addElement('html',
                        $OUTPUT->notification(get_string('rule_selector_failure', 'totara_cohort'), \core\output\notification::NOTIFY_ERROR)
                    );
                    return false;
                }

                if (!isset($data->beforeafterdatetime) || !is_numeric($data->beforeafterdatetime)) {
                    $form->_form->addElement('html',
                        $OUTPUT->notification(get_string('rule_selector_failure', 'totara_cohort'), \core\output\notification::NOTIFY_ERROR)
                    );
                    return false;
                }
            } else if ($data->fixedordynamic == self::COHORT_RULE_DATE_DYNAMIC) {
                if (!isset($data->durationmenu) || !in_array($data->durationmenu, array_keys(self::$operatorsdynamic))) {
                    $form->_form->addElement('html',
                        $OUTPUT->notification(get_string('rule_selector_failure', 'totara_cohort'), \core\output\notification::NOTIFY_ERROR)
                    );
                    return false;
                }

                if (!isset($data->durationdate) || !is_numeric($data->durationdate)) {
                    $form->_form->addElement('html',
                        $OUTPUT->notification(get_string('rule_selector_failure', 'totara_cohort'), \core\output\notification::NOTIFY_ERROR)
                    );
                    return false;
                }
            } else {
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
     *
     * @param cohort_rule_sqlhandler $sqlhandler
     */
    public function handleDialogUpdate($sqlhandler){
        $formdata = $this->form->get_data();
        $fixedordynamic = $formdata->fixedordynamic;
        switch($fixedordynamic) {
            case 1:
                $operator =  $formdata->beforeaftermenu;
                $date = $formdata->beforeafterdatetime;
                break;
            case 2:
                $operator =  $formdata->durationmenu;
                $date = $formdata->durationdate;
                break;
            default:
                return false;
        }
        $this->operator = $sqlhandler->operator = $operator;
        $this->date = $sqlhandler->date = $date;
        $sqlhandler->write();
    }
}
