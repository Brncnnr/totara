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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_tenant
 */

namespace totara_tenant\form;

use core_user;
use totara_tenant\local\util;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/user/editlib.php');

/**
 * Provision a new tenant
 */
final class user_create extends \moodleform {
    public function definition () {
        global $CFG;

        $mform = $this->_form;
        $editoroptions = ['maxfiles'];
        $filemanageroptions = $this->_customdata['filemanageroptions'];
        $user = $this->_customdata['user'];

        $mform->addElement('hidden', 'tenantid');
        $mform->setType('tenantid', PARAM_INT);

        $mform->addElement('header', 'moodle', get_string('general'));

        $mform->addElement('text', 'username', get_string('username'), 'size="20"');
        $mform->addHelpButton('username', 'username', 'auth');
        $mform->addRule('username', get_string('required'), 'required', null, 'client');
        $mform->setType('username', PARAM_RAW);

        $mform->addElement('advcheckbox', 'suspended', get_string('suspended', 'auth'));
        $mform->addHelpButton('suspended', 'suspended', 'auth');

        $mform->addElement('checkbox', 'createpassword', get_string('createpassword', 'auth'));

        if (!empty($CFG->passwordpolicy)) {
            $mform->addElement('static', 'passwordpolicyinfo', '', print_password_policy());
        }
        $mform->addElement('passwordunmask', 'newpassword', get_string('newpassword'), 'size="20"');
        $mform->addHelpButton('newpassword', 'newpassword');
        $mform->setType('newpassword', core_user::get_property_type('password'));
        $mform->disabledIf('newpassword', 'createpassword', 'checked');

        $mform->addElement('advcheckbox', 'preference_auth_forcepasswordchange', get_string('forcepasswordchange'));
        $mform->addHelpButton('preference_auth_forcepasswordchange', 'forcepasswordchange');
        $mform->disabledIf('preference_auth_forcepasswordchange', 'createpassword', 'checked');

        // Shared fields.
        useredit_shared_definition($mform, $editoroptions, $filemanageroptions, $user);

        // Next the customisable profile fields.
        profile_definition($mform, $user->id);

        $this->add_action_buttons(true, get_string('createuser'));

        $this->set_data($user);
    }

    /**
     * Extend the form definition after data has been parsed.
     */
    public function definition_after_data() {
        $mform = $this->_form;

        // Trim required name fields.
        foreach (useredit_get_required_name_fields() as $field) {
            $mform->applyFilter($field, 'trim');
        }

        // Next the customisable profile fields.
        profile_definition_after_data($mform, -1);
    }

    /**
     * Validate the form data.
     *
     * NOTE: username, email and idnumber validation is a bit more strict to prevent potential problems.
     *
     * @param array $data
     * @param array $files
     * @return array|bool
     */
    public function validation($data, $files) {
        $usernew = (object)$data;
        $usernew->id = 0;

        $err = array();

        if (!empty($usernew->createpassword)) {
            if ($usernew->suspended) {
                // Show some error because we can not mail suspended users.
                $err['suspended'] = get_string('error');
            }
        } else {
            if (!empty($usernew->newpassword)) {
                $errmsg = ''; // Prevent eclipse warning.
                if (!check_password_policy($usernew->newpassword, $errmsg)) {
                    $err['newpassword'] = $errmsg;
                }
            } else {
                // Internal accounts require password!
                $err['newpassword'] = get_string('required');
            }
        }

        $errmsg = util::validate_user_username($usernew->username);
        if ($errmsg !== null) {
            $err['username'] = $errmsg;
        }

        $errmsg = util::validate_user_email($usernew->email);
        if ($errmsg !== null) {
            $err['email'] = $errmsg;
        }

        $errmsg = util::validate_user_idnumber($usernew->idnumber);
        if ($errmsg !== null) {
            $err['idnumber'] = $errmsg;
        }

        // Next the customisable profile fields.
        $err += profile_validation($usernew, $files);

        if (count($err) == 0) {
            return true;
        } else {
            return $err;
        }
    }
}
