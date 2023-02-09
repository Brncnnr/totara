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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\form;

defined('MOODLE_INTERNAL') || die();

class attendees_add_file extends \moodleform {

    /** @var array $requiredcfnames */
    protected $requiredcfnames = [];

    protected function definition() {

        // Get list of required customfields.
        $customfieldnames = [];
        $cfparams = array('hidden' => '0', 'locked' => '0');
        $customfields = customfield_get_fields_definition('facetoface_signup', $cfparams);
        foreach ($customfields as $customfield) {
            if ($customfield->required) {
                $this->requiredcfnames[] = $customfield->shortname;
            }
            $customfieldnames[] = $customfield->shortname;
        }

        $extrafields = [];
        if ($this->_customdata['seminar']->get_selectjobassignmentonsignup() > 0) {
            $extrafields[] = 'jobassignmentidnumber';
        }

        $mform = $this->_form;

        // $customfieldinfo is used as $a in get_string().
        $a = new \stdClass();
        $a->customfields = '';
        $a->requiredcustomfields = '';

        if (!empty($this->requiredcfnames)) {
            foreach ($this->requiredcfnames as $item) {
                $a->requiredcustomfields .= "* '{$item}'\n";
            }
        }

        $dataoptional = get_string('dataoptional', 'mod_facetoface');
        $optionalfields = array_diff($customfieldnames, $this->requiredcfnames);
        if (!empty($optionalfields)) {
            foreach ($optionalfields as $item) {
                $a->customfields .= "* '{$item}' ({$dataoptional})\n";
            }
        }

        if (!empty($extrafields)) {
            foreach ($extrafields as $item) {
                $a->customfields .= "* '{$item}' ({$dataoptional})\n";
            }
        }

        $mform->addElement('hidden', 's', $this->_customdata['s']);
        $mform->setType('s', PARAM_INT);

        $mform->addElement('hidden', 'listid', $this->_customdata['listid']);
        $mform->setType('listid', PARAM_ALPHANUM);

        $mform->addElement('header', 'addattendees', get_string('addattendees', 'mod_facetoface'));

        $fileoptions = array('accepted_types' => array('.csv'));
        $mform->addElement('filepicker', 'userfile', get_string('csvtextfile', 'mod_facetoface'), null, $fileoptions);
        $mform->setType('userfile', PARAM_FILE);
        $mform->addRule('userfile', null, 'required');

        $encodings = \core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'mod_facetoface'), $encodings);

        $delimiters = \mod_facetoface\import_helper::csv_get_delimiter_list();
        $mform->addElement('select', 'delimiter', get_string('delimiter', 'mod_facetoface'), $delimiters);
        $mform->setDefault('delimiter', get_config('facetoface', 'defaultcsvdelimiter'));

        $mform->addElement('advcheckbox', 'ignoreconflicts', get_string('allowscheduleconflicts', 'mod_facetoface'));
        $mform->setType('ignoreconflicts', PARAM_BOOL);

        $help = get_string('csvtextfile_help', 'mod_facetoface', $a);
        if (!empty($a->customfields)) {
            $help .= get_string('csvtextfileoptionalcolumns_help', 'mod_facetoface', $a);
        }
        $mform->addelement('html', format_text($help, FORMAT_MARKDOWN));

        $this->add_action_buttons(true, get_string('continue'));
    }

    /**
     * Return submitted data if properly submitted or returns NULL if validation fails or
     * if there is no submitted data.
     *
     * @return object submitted data; NULL if not valid or not submitted or cancelled
     */
    public function get_data() {
        $data = parent::get_data();
        if ($data) {
            $data->content = $this->get_file_content('userfile');
            $data->requiredcfnames = $this->requiredcfnames;
        }
        return $data;
    }
}

