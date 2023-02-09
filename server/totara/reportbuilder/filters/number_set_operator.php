<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara
 * @subpackage reportbuilder
 */

/**
 * Configurable filter for numbers.
 * By passing an operator via the options array this filter can be configured to use
 * the following filter operations:
 * - equal
 * - notequal
 * - greaterthan
 * - lessthan
 * - greaterorequal
 * - lessorequal
 */
class rb_filter_number_set_operator extends rb_filter_type {

    public function __construct($type, $value, $advanced, $region, $report, $defaultvalue) {
        parent::__construct($type, $value, $advanced, $region, $report, $defaultvalue);

        if (isset($this->options['operator'])) {
            $valid_operators = ['equal', 'notequal', 'greaterthan', 'lessthan', 'greaterorequal', 'lessorequal'];
            if (!in_array($this->options['operator'], $valid_operators)) {
                throw new coding_exception('Invalid operator specified for number_set_operator filter');
            }
        } else {
            $this->options['equal'] = 'equal';
        }
    }

    /**
     * Adds controls specific to this filter in the form.
     * @param object $mform a MoodleForm object to setup
     */
    public function setupForm(&$mform) {
        global $SESSION;
        $label = format_string($this->label);
        $advanced = $this->advanced;
        $defaultvalue = $this->defaultvalue;

        $mform->addElement('text', $this->name, $label);
        $mform->setType($this->name, PARAM_TEXT);

        $customhelptext = isset($this->options['customhelptext']) && is_array($this->options['customhelptext']) ? $this->options['customhelptext'] : null;

        // Needed so we can show different help text based on the operator
        $operator = $this->options['operator'];
        $this->add_help_button($mform, $this->name, 'filternumbersetoperator' . $operator, 'filters', $customhelptext);

        if ($advanced) {
            $mform->setAdvanced($this->name);
        }

        // set default values
        if (isset($SESSION->reportbuilder[$this->report->get_uniqueid()][$this->name])) {
            $defaults = $SESSION->reportbuilder[$this->report->get_uniqueid()][$this->name];
        } else if (!empty($defaultvalue)) {
            $this->set_data($defaultvalue);
        }

        if (isset($defaults['value'])) {
            $mform->setDefault($this->name, $defaults['value']);
        }
    }

    /**
     * Retrieves data from the form data
     * @param object $formdata data submited with the form
     * @return mixed array filter data or false when filter not set
     */
    public function check_data($formdata) {
        $field    = $this->name;
        $value = (isset($formdata->$field)) ? $formdata->$field : '';

        if (isset($this->options['operator'])) {
            if ($value == '') {
                return false;
            }

            return array('operator' => (int)$this->options['operator'], 'value' => $value);
        }

        return false;
    }

    /**
     * Returns the condition to be used with SQL where
     * @param array $data filter settings
     * @return array containing filtering condition SQL clause and params
     */
    public function get_sql_filter($data) {
        global $DB;

        $value    = (float) $data['value'];
        $query    = $this->get_field();

        if ($value === '') {
            return array('', array());
        }

        $operator = $this->options['operator'];

        $uniqueparam = rb_unique_param('fn');
        switch ($operator) {
            case 'equal': // equal
                $res = "= :{$uniqueparam}";
                break;
            case 'notequal': // not equal
                $res = "!= :{$uniqueparam}";
                break;
            case 'greaterthan': // greater than
                $res = "> :{$uniqueparam}";
                break;
            case 'lessthan': // less than
                $res = "< :{$uniqueparam}";
                break;
            case 'greaterorequal': // greater or equal to
                $res = ">= :{$uniqueparam}";
                break;
            case 'lessorequal': // less than or equal to
                $res = "<= :{$uniqueparam}";
                break;
            default:
                return ['', []];
        }
        $params = array($uniqueparam => $value);

        // this will cope with empty values but not anything that can't be cast to a float
        // make sure the source column only contains numbers!
        $sql = 'CASE WHEN (' . $query . ') IS NULL THEN 0 ELSE ' . $DB->sql_cast_char2float($query) . ' END ' . $res;

        return array($sql, $params);
    }

    /**
     * Returns a human friendly description of the filter used as label.
     * @param array $data filter settings
     * @return string active filter label
     */
    public function get_label($data) {
        $value     = $data['value'];
        $label     = $this->label;

        $a = new stdClass();
        $a->label    = $label;
        $a->value    = '"' . s($value) . '"';

        return get_string('textlabel', 'filters', $a);
    }

    /**
     * Is this filter performing the filtering of results?
     *
     * @param array $data element filtering data
     * @return bool
     */
    public function is_filtering(array $data): bool {
        $value = $data['value'] ?? '';
        return (strlen((string)$value) > 0);
    }
}
