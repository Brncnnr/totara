<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2023 onwards Totara Learning Solutions LTD
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
 * @author Chris Snyder <chris.snyder@totara.com>
 * @package tool_smtp_test
 */

namespace tool_smtp_test;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class smtp_test_form extends \moodleform {
    function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'to', get_string('label_to', 'tool_smtp_test'), array('maxlength' => 128, 'size' => 32));
        $mform->setType('to', PARAM_EMAIL);
        $mform->addRule('to', null, 'required', null, 'client');

        $mform->addElement('text', 'subject', get_string('label_subject', 'tool_smtp_test'), array('maxlength' => 255, 'size' => 64));
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', null, 'required', null, 'client');

        $mform->addElement('textarea', 'message', get_string('label_message', 'tool_smtp_test'), array('cols' => 64, 'rows' => 12));
        $mform->setType('message', PARAM_RAW);
        $mform->addRule('message', null, 'required', null, 'client');

        $this->add_action_buttons(false, get_string('smtp_test', 'tool_smtp_test'));
    }
}
