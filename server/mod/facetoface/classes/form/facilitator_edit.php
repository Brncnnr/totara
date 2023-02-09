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

use html_writer;
use mod_facetoface\facilitator;
use mod_facetoface\facilitator_user;
use mod_facetoface\facilitator_type;
use mod_facetoface\facilitator_helper;
use mod_facetoface\customfield_area\facetofacefacilitator as facilitatorcustomfield;
use mod_facetoface\seminar_event;
use mod_facetoface\user_helper;

class facilitator_edit extends \moodleform {
    /**
     * Definition of the facilitator form
     */
    public function definition() {
        global $TEXTAREA_OPTIONS;

        $mform = $this->_form;

        /** @var \mod_facetoface\facilitator_user $facilitator */
        $facilitator = $this->_customdata['facilitator'];
        /** @var \mod_facetoface\seminar $seminar */
        $seminar = empty($this->_customdata['seminar']) ? null : $this->_customdata['seminar'];
        /** @var \mod_facetoface\seminar_event $seminarevent */
        $seminarevent = empty($this->_customdata['seminarevent']) ? null : $this->_customdata['seminarevent'];
        /** @var string */
        $backurl = $this->_customdata['backurl'] ?? '';

        $prefix = $filearea = facilitatorcustomfield::get_area_name();
        $tblprefix = facilitatorcustomfield::get_prefix();
        $component = facilitatorcustomfield::get_component();
        $syscontext = facilitatorcustomfield::get_context();

        $facilitatornamelength = \mod_facetoface\facilitator::FACILITATOR_NAME_LENGTH;

        $mform->addElement('hidden', 'id', $facilitator->get_id());
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'b', $backurl);
        $mform->setType('b', PARAM_URL);

        if (!empty($seminar)) {
            $mform->addElement('hidden', 'f', $seminar->get_id());
            $mform->setType('f', PARAM_INT);
        }

        if (!empty($seminarevent)) {
            $mform->addElement('hidden', 's', $seminarevent->get_id());
            $mform->setType('s', PARAM_INT);
        }

        // Facilitator name.
        $mform->addElement('text', 'name', get_string('facilitatorname', 'mod_facetoface'), ['size' => '45']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('facilitatornameedittoolong', 'mod_facetoface', $facilitatornamelength), 'maxlength', $facilitatornamelength);

        // Facilitator type/user.
        $adhoc = $this->_customdata['adhoc'];
        if (!$adhoc && !defined('AJAX_SCRIPT') || !AJAX_SCRIPT) {
            $class = '';
            if ((bool)$facilitator->get_userid()) {
                $class = 'nonempty';
            }
            $span = html_writer::span($facilitator->get_fullname(), $class, ['id' => 'facilitatortitle']);
            $username = [];
            $username[] =& $mform->createElement('hidden', 'userid');
            $typeoptions = [
                facilitator_type::INTERNAL => get_string('facilitatorinternal', 'mod_facetoface'),
                facilitator_type::EXTERNAL => get_string('facilitatorexternal', 'mod_facetoface'),
            ];
            $username[] =& $mform->createElement('select', 'facilitatortype', get_string('facilitatortype_label', 'mod_facetoface'), $typeoptions);
            $username[] =& $mform->createElement('button', 'facilitatorselector', get_string('selectuserwithdot', 'mod_facetoface'),
                ['id' => 'show-facilitator-dialog']);
            $username[] = $mform->createElement('static', 'facilitatortitle', null, $span)->set_allow_xss(true);
            $mform->setDefault('facilitatortype', facilitator_type::INTERNAL);
            $mform->addGroup($username, 'labeltype', get_string('facilitatortype', 'mod_facetoface'), null, false);
            $mform->setType('userid', PARAM_INT);
            $mform->addHelpButton('labeltype', 'facilitatortype', 'mod_facetoface');
            $mform->disabledIf('facilitatorselector', 'facilitatortype', 'eq', facilitator_type::EXTERNAL);
        } else {
            $mform->addElement('hidden', 'userid', 0);
            $mform->setType('userid', PARAM_INT);
            $mform->addElement('hidden', 'facilitatortype', facilitator_type::EXTERNAL);
            $mform->setType('facilitatortype', PARAM_INT);
        }

        // Allow booking conflicts.
        $mform->addElement('advcheckbox', 'allowconflicts', get_string('allowfacilitatorconflicts', 'mod_facetoface'));
        $mform->addHelpButton('allowconflicts', 'allowfacilitatorconflicts', 'mod_facetoface');
        $mform->setType('allowconflicts', PARAM_INT);

        // We don't need autosave here
        $editoropts = $TEXTAREA_OPTIONS;
        $editoropts['autosave'] = false;
        // Facilitator description.
        $mform->addElement('editor', 'description_editor', get_string('facilitatordescriptionlabel', 'mod_facetoface'), ['rows' => 7], $editoropts);

        // Facilitator custom fields.
        customfield_definition($mform, (object)['id' => $facilitator->get_id()], $prefix, 0, $tblprefix);

        // Add to sitewide list.
        $capability = has_capability('mod/facetoface:managesitewidefacilitators', \context_system::instance());
        if ($capability and !empty($seminar) and $facilitator->get_custom()) {
            $mform->addElement('advcheckbox', 'notcustom', get_string('addtositewidelist', 'mod_facetoface'));
        } else {
            $mform->addElement('hidden', 'notcustom');
        }
        $mform->setType('notcustom', PARAM_INT);
        $mform->closeHeaderBefore('notcustom');

        // Version control.
        if ($facilitator->exists()) {
            $mform->addElement('header', 'versions', get_string('versioncontrol', 'mod_facetoface'));

            $mform->addElement(
                'static',
                'versioncreated',
                get_string('created', 'mod_facetoface'),
                user_helper::get_timestamp_and_profile($facilitator->get_timecreated(), $facilitator->get_usercreated())
            );

            if (!empty($facilitator->get_timemodified()) && $facilitator->get_timemodified() != $facilitator->get_timecreated()) {
                $mform->addElement(
                    'static',
                    'versionmodified',
                    get_string('modified'),
                    user_helper::get_timestamp_and_profile($facilitator->get_timemodified(), $facilitator->get_usermodified())
                );
            }
        }

        // Buttons.
        if (empty($seminar)) {
            $label = null;
            if (!$facilitator->exists()) {
                $label = get_string('addfacilitator', 'mod_facetoface');
            }
            $this->add_action_buttons(true, $label);
        }
        // Set default/existing data.
        $formdata = (object)[
            'id' => $facilitator->get_id(),
            'userid' => $facilitator->get_userid(),
            'name' => $facilitator->get_name(),
            'allowconflicts' => $facilitator->get_allowconflicts(),
            'description_editor' => ['text' => $facilitator->get_description()],
            'notcustom' => $facilitator->get_custom() ? 0 : 1,
            'description' => $facilitator->get_description(),
            'descriptionformat' => FORMAT_HTML,
        ];
        if ($facilitator->exists()) {
            $formdata->facilitatortype = (bool)$facilitator->get_userid() ? '0' : '1';
        }
        customfield_load_data($formdata, $prefix, $tblprefix);
        $formdata = file_prepare_standard_editor(
            $formdata,
            'description',
            $editoropts,
            $syscontext,
            $component,
            $filearea,
            $facilitator->get_id()
        );
        $this->set_data($formdata);
    }

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        /** @var \mod_facetoface\facilitator $facilitator */
        $facilitator = $this->_customdata['facilitator'];

        if ($facilitator->exists() && $facilitator->get_allowconflicts() && $data['allowconflicts'] == 0) {
            // Make sure there are no existing conflicts before we switch the setting!
            if ($facilitator->has_conflicts()) {
                $errors['allowconflicts'] = get_string('error:facilitatorconflicts', 'mod_facetoface');
            }
        }

        return $errors;
    }

    public function save($data) {
        global $DB;
        // New one.
        if ((int)$data->id == 0) {
            if ((int)$data->userid == 0) {
                return facilitator_helper::save($data);
            } else {
                if (facilitator_user::is_userid_active((int)$data->userid)) {
                    return facilitator_helper::save($data);
                } else {
                    \core\notification::warning(get_string('facilitatoruserdeleted', 'mod_facetoface'));
                    return false;
                }
            }
        }

        // Update one.
        $oldfacilitator = new facilitator($data->id);
        if ($oldfacilitator->get_userid() == (int)$data->userid) {
            // And also for $oldfacilitator->get_userid() == 0 && (int)$data->userid == 0
            // Nothing change for user record, still same user.
            // All good, just lets save the record.
            return facilitator_helper::save($data);
        }
        if ($oldfacilitator->get_userid() > 0 && (int)$data->userid == 0) {
            return facilitator_helper::save($data);
        }
        if ($oldfacilitator->get_userid() == 0 && (int)$data->userid > 0) {
            if (facilitator_user::is_userid_active((int)$data->userid)) {
                return facilitator_helper::save($data);
            } else {
                \core\notification::warning(get_string('facilitatoruserdeleted', 'mod_facetoface'));
                return false;
            }
        }

        // Right, new user, we must load all session records
        // It's probably doesn't matter if the user is in the trouble or not
        // We will update all upcoming sessions and skip the past ones for history records
        // Lets check a new user: for case he/she is active.
        if (!facilitator_user::is_userid_active((int)$data->userid)) {
            // This should not happened, but somehow did.
            \core\notification::warning(get_string('facilitatoruserdeleted', 'mod_facetoface'));
            return false;
        }

        $transaction = $DB->start_delegated_transaction();

        $data->id = 0;
        $newfacilitator = facilitator_helper::save($data);

        $sql = "SELECT DISTINCT ffd.*
                  FROM {facetoface_facilitator_dates} ffd
                  JOIN {facetoface_sessions_dates} fsd ON fsd.id = ffd.sessionsdateid
                  JOIN {facetoface_sessions} fs ON fs.id = fsd.sessionid
                 WHERE ffd.facilitatorid = :facilitatorid
                   AND fsd.timestart > :timenow";
        $params = ['facilitatorid' => $oldfacilitator->get_id(), 'timenow' => time()];

        $sessiondates = $DB->get_records_sql($sql, $params);
        foreach ($sessiondates as $sessiondate) {
            $sql = "UPDATE {facetoface_facilitator_dates}
                       SET facilitatorid = :newfacilitatorid
                     WHERE facilitatorid = :oldfacilitatorid
                       AND id = :sessiondateid";
            $params = [
                'newfacilitatorid' => $newfacilitator->get_id(),
                'oldfacilitatorid' => $oldfacilitator->get_id(),
                'sessiondateid' => $sessiondate->id,
            ];
            $DB->execute($sql, $params);
        }

        $transaction->allow_commit();

        return $newfacilitator;
    }
}
