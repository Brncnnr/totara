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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\form;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use mod_facetoface\user_helper;

class asset_edit extends \moodleform {

    /**
     * Definition of the asset form
     */
    public function definition() {
        global $TEXTAREA_OPTIONS;

        $mform = $this->_form;

        /** @var \mod_facetoface\asset $asset */
        $asset = $this->_customdata['asset'];
        /** @var \mod_facetoface\seminar $seminar */
        $seminar = empty($this->_customdata['seminar']) ? null : $this->_customdata['seminar'];
        /** @var \mod_facetoface\seminar_event $seminarevent */
        $seminarevent = empty($this->_customdata['seminarevent']) ? null : $this->_customdata['seminarevent'];
        /** @var string */
        $backurl = $this->_customdata['backurl'] ?? '';

        $assetnamelength = \mod_facetoface\asset::ASSET_NAME_LENGTH;

        $mform->addElement('hidden', 'id', $asset->get_id());
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

        // Asset name.
        $mform->addElement('text', 'name', get_string('assetname', 'mod_facetoface'), array('size' => '45'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('assetnameedittoolong', 'mod_facetoface', $assetnamelength), 'maxlength', $assetnamelength);

        // Asset allow booking conflicts
        $mform->addElement('advcheckbox', 'allowconflicts', get_string('allowassetconflicts', 'mod_facetoface'));
        $mform->addHelpButton('allowconflicts', 'allowassetconflicts', 'mod_facetoface');
        $mform->setType('allowconflicts', PARAM_INT);

        // We don't need autosave here
        $editoropts = $TEXTAREA_OPTIONS;
        $editoropts['autosave'] = false;
        // Asset description.
        $mform->addElement('editor', 'description_editor', get_string('assetdescription', 'mod_facetoface'), ['rows' => 7], $editoropts);

        // Asset customfields.
        customfield_definition($mform, (object)['id' => $asset->get_id()], 'facetofaceasset', 0, 'facetoface_asset');

        // Add to sitewide list.
        $capability = has_capability('mod/facetoface:managesitewideassets', \context_system::instance());
        if ($capability and !empty($seminar) and $asset->get_custom()) {
            $mform->addElement('advcheckbox', 'notcustom', get_string('addtositewidelist', 'mod_facetoface'));
        } else {
            $mform->addElement('hidden', 'notcustom');
        }
        $mform->setType('notcustom', PARAM_INT);
        $mform->closeHeaderBefore('notcustom');

        // Version control.
        if ($asset->exists()) {
            $mform->addElement('header', 'versions', get_string('versioncontrol', 'mod_facetoface'));

            $mform->addElement(
                'static',
                'versioncreated',
                get_string('created', 'mod_facetoface'),
                user_helper::get_timestamp_and_profile($asset->get_timecreated(), $asset->get_usercreated())
            );

            if (!empty($asset->get_timemodified()) and $asset->get_timemodified() != $asset->get_timecreated()) {
                $mform->addElement(
                    'static',
                    'versionmodified',
                    get_string('modified'),
                    user_helper::get_timestamp_and_profile($asset->get_timemodified(), $asset->get_usermodified())
                );
            }
        }

        // Buttons.
        if (empty($seminar)) {
            $label = null;
            if (!$asset->exists()) {
                $label = get_string('addasset', 'mod_facetoface');
            }
            $this->add_action_buttons(true, $label);
        }
        // Set default/existing data.
        $formdata = (object)[
            'id' => $asset->get_id(),
            'name' => $asset->get_name(),
            'allowconflicts' => $asset->get_allowconflicts(),
            'description_editor' => ['text' => $asset->get_description()],
            'notcustom' => $asset->get_custom() ? 0 : 1,
            'description' => $asset->get_description(),
            'descriptionformat' => FORMAT_HTML,
        ];
        customfield_load_data($formdata, 'facetofaceasset', 'facetoface_asset');
        $formdata = file_prepare_standard_editor(
            $formdata,
            'description',
            $editoropts,
            $editoropts['context'],
            'mod_facetoface',
            'asset',
            $asset->get_id()
        );
        $this->set_data($formdata);
    }

    public function validation($data, $files) {

        $errors = parent::validation($data, $files);

        /** @var \mod_facetoface\asset $asset */
        $asset = $this->_customdata['asset'];
        if ($asset->exists() and $asset->get_allowconflicts() and $data['allowconflicts'] == 0) {
            // Make sure there are no existing conflicts before we switch the setting!
            if ($asset->has_conflicts()) {
                $errors['allowconflicts'] = get_string('error:assetconflicts', 'mod_facetoface');
            }
        }
        return $errors;
    }
}
