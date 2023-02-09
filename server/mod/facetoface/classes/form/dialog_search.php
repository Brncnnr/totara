<?php
/*
* This file is part of Totara Learn
*
* Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package mod_facetoface
*/

namespace mod_facetoface\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/formslib.php');

class dialog_search extends \moodleform {

    // Define the form
    function definition() {
        global $OUTPUT;

        $mform =& $this->_form;

        // Hack to get around form namespacing
        static $formcounter = 1;
        $mform->updateAttributes(array('id' => 'mform_dialog_' . $formcounter));
        $formcounter++;

        // Generic hidden values
        $mform->addElement('hidden', 'dialog_form_target', '#search-tab');
        $mform->setType('dialog_form_target', PARAM_TEXT);
        $mform->addElement('hidden', 'search', 1);
        $mform->setType('search', PARAM_INT);

        // Custom hidden values
        if (!empty($this->_customdata['hidden'])) {
            foreach ($this->_customdata['hidden'] as $key => $value) {
                $mform->addElement('hidden', $key);
                $mform->setType($key, PARAM_TEXT);
                $mform->setDefault($key, $value);
            }
        }

        // Create actual form elements query box
        $searcharray = array();
        $searcharray[] =& $mform->addElement('text', 'query', get_string('searchlabel', 'mod_facetoface'), 'maxlength="254"');
        $mform->setType('query', PARAM_TEXT);
        $mform->setDefault('query', $this->_customdata['query']);
        // Show search button and close markup
        // Pad search string to make it look nicer
        $searcharray[] =&  $mform->addElement('submit', 'dialogsearchsubmitbutton', get_string('search'));
    }
}