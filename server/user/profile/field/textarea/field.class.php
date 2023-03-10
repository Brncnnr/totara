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
 * Textarea profile field define.
 *
 * @package   profilefield_textarea
 * @copyright  2007 onwards Shane Elliot {@link http://pukunui.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\format;
use core\json_editor\helper\document_helper;

/**
 * Class profile_field_textarea.
 *
 * @copyright  2007 onwards Shane Elliot {@link http://pukunui.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile_field_textarea extends profile_field_base {

    /**
     * Adds elements for this field type to the edit form.
     * @param moodleform $mform
     */
    public function edit_field_add($mform) {
        // Create the form field.
        $mform->addElement('editor', $this->inputname, format_string($this->field->name), null, null);
        $mform->setType($this->inputname, PARAM_RAW); // We MUST clean this before display!
    }

    /**
     * Sets the default value for this field instance
     * Overwrites the base class method
     *
     * @param  moodleform $mform instance of the moodleform class
     */
    public function edit_field_set_default($mform) {
        if (!empty($this->field->defaultdata)) {
            $mform->setDefault($this->inputname, array('text' => $this->field->defaultdata));
        }
    }

    /**
     * Overwrite base class method, data in this field type is potentially too large to be included in the user object.
     * @return bool
     */
    public function is_user_object_data() {
        return false;
    }

    /**
     * Process incoming data for the field.
     * @param stdClass $data
     * @param stdClass $datarecord
     * @return mixed|stdClass
     */
    public function edit_save_data_preprocess($data, $datarecord) {
        if (is_array($data)) {
            $datarecord->dataformat = $data['format'];
            $data = $data['text'];
        }
        return $data;
    }

    /**
     * Load user data for this profile field, ready for editing.
     * @param stdClass $user
     */
    public function edit_load_user_data($user) {
        if ($this->data !== null) {
            $this->data = clean_text($this->data, $this->dataformat);
            $user->{$this->inputname} = array('text' => $this->data, 'format' => $this->dataformat);
        }
    }

    /**
     * Loads a user object with data for this field ready for the export, such as a spreadsheet.
     *
     * @param object a user object
     */
    function export_load_user_data($user) {
        if ($this->data !== NULL) {
            $this->data = clean_text($this->data, $this->dataformat);
            $user->{$this->inputname} = $this->data;
        }
    }

    /**
     * Display the data for this field
     * @return string
     */
    public function display_data() {
        return format_text($this->data, $this->dataformat, array('overflowdiv' => true));
    }

    /**
     * Validate the form field from profile page.
     *
     * @param stdClass $usernew
     * @return string contains error message otherwise null
     */
    public function edit_validate_field($usernew) {
        // Make sure we're using the right value for the text.
        if (isset($usernew->{$this->inputname}) && is_array($usernew->{$this->inputname}) && isset($usernew->{$this->inputname}['text'])) {
            if (isset($usernew->{$this->inputname}['format'])) {
                $usernew->{$this->inputname}['format'] = $usernew->{$this->inputname}['format'];
                $usernew->{$this->inputname}['text'] = $usernew->{$this->inputname}['text'];
            } else {
                $usernew->{$this->inputname} = $usernew->{$this->inputname}['text'];
            }
        }

        return parent::edit_validate_field($usernew);
    }

    /**
     * @inheritDoc
     */
    function validate_field_from_inputs(array $params): void {
        $field = $this->field->shortname;
        if (!isset($params['data_format'])) {
            throw new moodle_exception("{$field}: data_format is required for textarea fields.");
        }

        if ($params['data_format'] == FORMAT_JSON_EDITOR) {
            if (!document_helper::is_valid_json_document($params['data'])) {
                throw new moodle_exception("{$field}: data_format set to JSON but data does not appear to be in JSON format.");
            }
        }

        parent::validate_field_from_inputs($params);
    }

}