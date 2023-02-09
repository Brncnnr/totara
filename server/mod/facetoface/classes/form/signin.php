<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\form;

defined('MOODLE_INTERNAL') || die();

class signin extends \moodleform {

    public function definition() {

        $mform = $this->_form;
        $mform->updateAttributes(['class' => 'mform signinsheet']);
        $session = $this->_customdata;

        $options = [];
        foreach ($session->sessiondates as $i => $date) {
            $dateobject = \mod_facetoface\output\session_time::format($date->timestart, $date->timefinish, $date->sessiontimezone);
            $options[$date->id] = get_string('sessionstartdatewithtime', 'mod_facetoface', $dateobject);
        }

        $select = \reportbuilder::get_all_general_export_options();
        if (count($select) == 0 || count($options) == 0) {
            // Something happened which should not.
            // No export options - don't show form.
            return;
        }

        $mform->addElement('header', 'signinsheet', get_string('signinsheet', 'mod_facetoface'));

        $group = [];
        $options[0] = get_string('selectsession', 'mod_facetoface');
        ksort($options, SORT_NUMERIC);
        $group[] = $mform->createElement('select', 'sessiondateid', get_string('sessiondate', 'mod_facetoface'), $options);
        $mform->setType('sessiondateid', PARAM_INT);
        $mform->setDefault('sessiondateid', 0);

        $select = ['none' => get_string('selectfileformat', 'mod_facetoface')] + $select;
        $group[] = $mform->createElement('select', 'docformat', get_string('exportformat', 'totara_core'), $select);
        $mform->setType('docformat', PARAM_PLUGIN);
        $mform->setDefault('docformat', 'none');

        $group[] = $mform->createElement('submit', 'download', get_string('download', 'mod_facetoface'));
        $mform->addGroup($group, 'downloadgroup', get_string('downloadsigninsheet', 'mod_facetoface'), null, false);
        $mform->disabledIf('download', 'sessiondateid', 'eq', '0');
        $mform->disabledIf('download', 'docformat', 'eq', 'none');
    }
}
