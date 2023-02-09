<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains the datetime profile field class.
 *
 * @package profilefield_datetime
 * @copyright 2010 Mark Nelson <markn@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

/**
 * Handles displaying and editing the datetime field.
 *
 * @copyright 2010 Mark Nelson <markn@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
class profile_field_datetime extends profile_field_base {

    /**
     * Handles editing datetime fields.
     *
     * @param moodleform $mform
     */
    public function edit_field_add($mform) {
        // Get the current calendar in use - see MDL-18375.
        $calendartype = \core_calendar\type_factory::get_calendar_instance();

        // Check if the field is required.
        if ($this->field->required) {
            $optional = false;
        } else {
            $optional = true;
        }

        // Convert the year stored in the DB as gregorian to that used by the calendar type.
        $startdate = $calendartype->convert_from_gregorian($this->field->param1, 1, 1);
        $stopdate = $calendartype->convert_from_gregorian($this->field->param2, 1, 1);

        $attributes = array(
            'startyear' => $startdate['year'],
            'stopyear'  => $stopdate['year'],
            'optional'  => $optional
        );

        // Check if they wanted to include time as well.
        if (!empty($this->field->param3)) {
            $mform->addElement('date_time_selector', $this->inputname, format_string($this->field->name), $attributes);
        } else {
            $mform->addElement('date_selector', $this->inputname, format_string($this->field->name), $attributes);
        }

    }

    /**
     * If timestamp is in YYYY-MM-DD or YYYY-MM-DD-HH-MM-SS format, then convert it to timestamp.
     *
     * @param string|int $datetime datetime to be converted.
     * @param stdClass $datarecord The object that will be used to save the record
     * @return int timestamp
     * @since Moodle 2.5
     */
    public function edit_save_data_preprocess($datetime, $datarecord) {
        if (!$datetime) {
            return 0;
        }

        if (is_numeric($datetime)) {
            $gregoriancalendar = \core_calendar\type_factory::get_calendar_instance('gregorian');
            $datetime = $gregoriancalendar->timestamp_to_date_string($datetime, '%Y-%m-%d-%H-%M-%S', 99, true, true);
        }

        $datetime = explode('-', $datetime);
        // Bound year with start and end year.
        $datetime[0] = min(max($datetime[0], $this->field->param1), $this->field->param2);

        if (!empty($this->field->param3) && count($datetime) == 6) {
            return make_timestamp($datetime[0], $datetime[1], $datetime[2], $datetime[3], $datetime[4], $datetime[5]);
        } else {
            return make_timestamp($datetime[0], $datetime[1], $datetime[2]);
        }
    }

    /**
     * Display the data for this field.
     */
    public function display_data() {
        // Check if time was specified.
        if (!empty($this->field->param3)) {
            $format = get_string('strftimedaydatetime', 'langconfig');
        } else {
            $format = get_string('strftimedate', 'langconfig');
        }

        // Check if a date has been specified.
        if (empty($this->data)) {
            return get_string('notset', 'profilefield_datetime');
        } else {
            return userdate($this->data, $format);
        }
    }

    /**
     * The Datetime field needs extra logic for saving
     * so override edit_save_data in the lib file.
     *
     * @param   mixed   data coming from the form
     * @return  mixed   returns data id if success of db insert/update,
     *                  false on fail, 0 if not permitted
     */
    function edit_save_data($usernew) {
        global $DB;

        $fieldname = $this->inputname;

        // If a datetime is disabled then remove any existing data
        if (isset($usernew->$fieldname) && empty($usernew->$fieldname)) {
            $DB->delete_records('user_info_data', array('userid' => $usernew->id, 'fieldid' => $this->field->id));
            return;
        }

        parent::edit_save_data($usernew);
    }

    /**
     * Loads a user object with data for this field ready for the export, such as a spreadsheet.
     *
     * @param   object   a user object
     */
    function export_load_user_data($user) {
        // Check if time was specified.
        if (!empty($this->field->param3)) {
            $format = get_string('strftimedaydatetime', 'langconfig');
        } else {
            $format = get_string('strftimedate', 'langconfig');
        }

        // Check if a date has been specified.
        if (empty($this->data)) {
            $user->{$this->inputname} = get_string('notset', 'profilefield_datetime');
        } else {
            $user->{$this->inputname} = userdate($this->data, $format);
        }
    }

    /**
     * Check if the field data is considered empty
     *
     * @return boolean
     */
    public function is_empty() {
        return empty($this->data);
    }

    /*
     * Validate the form field from profile page.
     *
     * @param stdClass $usernew
     * @return string contains error message otherwise null
     */
    public function edit_validate_field($usernew) {
        if (isset($usernew->{$this->inputname})) {
            $datetime = explode('-', $usernew->{$this->inputname});
            if (count($datetime) == 6) {
                $usernew->{$this->inputname} = make_timestamp($datetime[0], $datetime[1], $datetime[2], $datetime[3], $datetime[4], $datetime[5]);
            } else if (count($datetime) == 3) {
                $usernew->{$this->inputname} = make_timestamp($datetime[0], $datetime[1], $datetime[2]);
            }
        }

        return parent::edit_validate_field($usernew);
    }

    /**
     * @inheritDoc
     */
    function validate_field_from_inputs(array $params): void {
        $field = $this->field->shortname;
        if (empty(trim($params['data']))) {
            throw new moodle_exception("{$field}: Date must be provided in format YYYY-MM-DD-HH-MM-SS or YYYY-MM-DD.");
        }
        if (isset($params['data_format'])) {
            throw new moodle_exception("{$field}: data_format should not be passed for datetime fields.");
        }

        $date = explode('-', trim($params['data']));
        if (!in_array(count($date), [3, 6])) {
            throw new moodle_exception("{$field}: Date format should be YYYY-MM-DD-HH-MM-SS or YYYY-MM-DD.");
        }

        if (count($date) === 6) {
            if (!preg_match("/^(\d{4})-(\d{1,2})-(\d{1,2})-(\d{1,2})-(\d{1,2})-(\d{1,2})$/", $params['data'])) {
                throw new moodle_exception("{$field}: Date format should be YYYY-MM-DD-HH-MM-SS.");
            }

            if ($this->field->param3 == 0) {
                throw new moodle_exception("{$field}: This datetime field has not enabled the Include time setting so only date in YYYY-MM-DD format is accepted.");
            }

            if ($date[3] > 23 || $date[4] >= HOURMINS || $date[5] >= HOURMINS) {
                throw new moodle_exception("{$field}: You must pass valid time values that represent an actual 24 hour time.");
            }
        }

        if (count($date) === 3) {
            if (!preg_match("/^(\d{4})-(\d{1,2})-(\d{1,2})$/", $params['data'])) {
                throw new moodle_exception("{$field}: Date format should be YYYY-MM-DD.");
            }
        }

        if (!checkdate($date[1], $date[2], $date[0])) {
            throw new moodle_exception("{$field}: You must pass valid date values that represent an actual date.");
        }
        $min_year = $this->field->param1;
        if (!empty($min_year) && $date[0] < $min_year) {
            throw new moodle_exception("{$field}: Date given has year {$date[0]} which is less than minimum of {$min_year} allowed for field.");
        }
        $max_year = $this->field->param2;
        if (!empty($max_year) && $date[0] > $max_year) {
            throw new moodle_exception("{$field}: Date given has year {$date[0]} which is more than maximum of {$max_year} allowed for field.");
        }

        parent::validate_field_from_inputs($params);
    }
}