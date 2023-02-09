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

/**
 * Add user confirmation form
 */
class attendees_add_confirm extends \moodleform {

    protected function definition() {
        $mform = & $this->_form;

        $mform->addElement('hidden', 's', $this->_customdata['s']);
        $mform->setType('s', PARAM_INT);

        $mform->addElement('hidden', 'listid', $this->_customdata['listid']);
        $mform->setType('listid', PARAM_ALPHANUM);

        $mform->addElement('hidden', 'ignoreconflicts', $this->_customdata['ignoreconflicts']);
        $mform->setType('ignoreconflicts', PARAM_BOOL);

        // Only display notification checkboxes if they're active.
        if ($this->_customdata['is_notification_active']) {
            $mform->addElement('header', 'notifications', get_string('notifications', 'facetoface'));

            $mform->addElement('advcheckbox', 'notifyuser', '', get_string('notifynewuser', 'facetoface'));
            $mform->setDefault('notifyuser', 1);

            $mform->addElement('advcheckbox', 'notifymanager', '', get_string('notifynewusermanager', 'facetoface'));
            $mform->setDefault('notifymanager', 1);
        }

        if ($this->_customdata['isapprovalrequired']) {
            $mform->addElement('header', 'bookingoptions', get_string('bookingoptions', 'facetoface'));
            $mform->setExpanded('bookingoptions', true);
            $mform->addElement('advcheckbox', 'ignoreapproval', '', get_string('ignoreapprovalwhenaddingattendees', 'facetoface'));

            // Disabling suppress notification if approval required and not ignored.
            $mform->disabledIf('notifyuser', 'ignoreapproval', 'notchecked', 1);
            $mform->disabledIf('notifymanager', 'ignoreapproval', 'notchecked', 1);
        }

        // Custom fields.
        if ($this->_customdata['enablecustomfields']) {
            $mform->addElement('header', 'signupfields', get_string('signupfields', 'facetoface'));
            $fileurl = new \moodle_url('/mod/facetoface/attendees/list/addfile.php', array('s' => $this->_customdata['s']));
            $mform->addElement('static', 'signupfieldslimitation', '', get_string('signupfieldslimitation', 'facetoface', $fileurl->out()));
            $signup = new \stdClass();
            $signup->id = 0;
            customfield_definition($mform, $signup, 'facetofacesignup', 0, 'facetoface_signup', true);
        }

        $this->add_action_buttons(true, get_string('confirm'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        // Custom fields.
        // Skip validation if it is uploaded data from csv file, we do validate it in other place.
        if ($this->_customdata['enablecustomfields']) {
            $data['id'] = 0;
            $err = customfield_validation((object)$data, 'facetofacesignup', 'facetoface_signup');
            if (!empty($err)) {
                $errors['signupfieldslimitation'] = $err;
            }
        }
        return $errors;
    }

    /**
     * Get user list by ids.
     * @param $userlist
     * @param int $offset
     * @param int $limit
     * @return array
     *
     * @deprecated since Totara 13
     */
    public static function get_user_list($userlist, $offset = 0, $limit = 0) {

        debugging('attendees_add_confirm::get_user_list() function has been deprecated, please use attendees_list_helper::get_user_list()',
            DEBUG_DEVELOPER);

        return \mod_facetoface\attendees_list_helper::get_user_list($userlist, $offset, $limit);
    }
}