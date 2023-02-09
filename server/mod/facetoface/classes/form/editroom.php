<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package mod_facetoface
 */

namespace mod_facetoface\form;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use mod_facetoface\user_helper;
use mod_facetoface\room_virtualmeeting;
use totara_core\virtualmeeting\virtual_meeting;

global $CFG;
require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/totara/customfield/field/location/define.class.php');

class editroom extends \moodleform {

    /**
     * Definition of the room form
     */
    public function definition() {
        global $TEXTAREA_OPTIONS, $PAGE;

        $mform = $this->_form;

        /** @var \mod_facetoface\room $room */
        $room = $this->_customdata['room'];
        /** @var room_virtualmeeting $virtual_meeting */
        $virtual_meeting = $this->_customdata['virtual_meeting'];
        /** @var \mod_facetoface\seminar $seminar */
        $seminar = empty($this->_customdata['seminar']) ? null : $this->_customdata['seminar'];
        /** @var \mod_facetoface\seminar_event $seminarevent */
        $seminarevent = empty($this->_customdata['seminarevent']) ? null : $this->_customdata['seminarevent'];
        /** @var string */
        $backurl = $this->_customdata['backurl'] ?? '';

        $roomnamelength = \mod_facetoface\room::ROOM_NAME_LENGTH;

        $mform->addElement('hidden', 'id', $room->get_id());
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

        // Room name.
        $mform->addElement('text', 'name', get_string('roomnameedit', 'mod_facetoface'), array('size' => '45'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('roomnameedittoolong', 'mod_facetoface', $roomnamelength), 'maxlength', $roomnamelength);

        // This form is loaded as ajax into page that has "capacity" so give it different name to avoid conflicts.
        $mform->addElement('text', 'roomcapacity', get_string('roomcapacity', 'mod_facetoface'));
        $mform->setType('roomcapacity', PARAM_INT);
        $mform->addRule('roomcapacity', null, 'required', null, 'client');
        $mform->addRule('roomcapacity', null, 'numeric', null, 'client');

        // 'Allow room booking conflicts' checkbox
        $mform->addElement('advcheckbox', 'allowconflicts', get_string('allowroomconflicts', 'mod_facetoface'));
        $mform->addHelpButton('allowconflicts', 'allowroomconflicts', 'mod_facetoface');
        $mform->setType('allowconflicts', PARAM_INT);

        // We don't need autosave here
        $editoropts = $TEXTAREA_OPTIONS;
        $editoropts['autosave'] = false;
        // Room Description.
        $mform->addElement('editor', 'description_editor', get_string('roomdescriptionedit', 'mod_facetoface'), ['rows' => 7], $editoropts);

        $mform->addElement('header', 'virtualroom', get_string('virtualroom_heading', 'mod_facetoface'));
        // Virtual meeting room.
        $adhoc = $this->_customdata['adhoc'];
        $meeting_options = [
            room_virtualmeeting::VIRTUAL_MEETING_NONE => get_string('none', 'mod_facetoface'),
            room_virtualmeeting::VIRTUAL_MEETING_INTERNAL => get_string('internal', 'mod_facetoface')
        ];
        $pluginsadded = [];

        if ($adhoc) {
            $plugindata = virtual_meeting::get_availale_plugins_info(null, true);
            $conditions_no_auth = array_keys($meeting_options);
            foreach ($plugindata as $pluginname => $info) {
                $meeting_options[$pluginname] = $info['name'];
                if (!empty($info['auth_endpoint'])) {
                    $auth_ep_id = $pluginname . '_auth_endpoint';
                    $mform->addElement('hidden', $auth_ep_id, $info['auth_endpoint']);
                    $mform->setType($auth_ep_id, PARAM_RAW);
                    $mform->hideIf('sitewide', 'plugin', 'eq', $pluginname);
                    $auth_plugins[] = $pluginname;
                } else {
                    $mform->hideIf('connections', 'plugin', 'eq', $pluginname);
                    $conditions_no_auth[] = $pluginname;
                }
            }

            $pluginsadded = array_keys($plugindata);
            $pluginname = $virtual_meeting->get_plugin();
            if ($virtual_meeting->exists() && room_virtualmeeting::is_virtual_meeting($pluginname) && !isset($plugindata[$pluginname])) {
                $pluginsadded[] = $pluginname;
                $meeting_options[$pluginname] = get_string('unavailableplugin', 'mod_facetoface');
                $mform->hideIf('connections', 'plugin', 'eq', $pluginname);
            }

            // Once a virtualmeeting provider is created and saved, an indeterminate state is created which is difficult
            // to resolve in real time if a manager changed a mind, so we disable it in meantime
            $attrs = $virtual_meeting->exists() ? ['disabled' => 'disabled'] : [];
            $mform->addElement('select', 'plugin', get_string('virtual_meeting_add', 'mod_facetoface'), $meeting_options, $attrs);
            $mform->setType('plugin', PARAM_TEXT);

            $mform->addElement('hidden', 'connected', '', ['id' => 'plugin-connection-state']);
            $mform->setType('connected', PARAM_INT);

            $connections = [];
            $connections[] =& $mform->createElement('button', 'virtual_meeting_authorise',
                 get_string('virtual_meeting_connect', 'mod_facetoface'), ['id' => 'show-authorise-dialog']);
            $connections[] =& $mform->createElement('static', 'connected_text', '', html_writer::span('', 'mod_facetoface-connected'));
            $mform->addGroup($connections, 'connections', get_string('virtual_meeting_service_provider', 'mod_facetoface'), null, false);
            $mform->addHelpButton('connections', 'virtual_meeting_service_provider', 'mod_facetoface');
            foreach ($conditions_no_auth as $condition) {
                $mform->hideIf('connections', 'plugin', 'eq', $condition);
            }
            $PAGE->requires->js_call_amd('mod_facetoface/room_integration', 'init');
            $PAGE->requires->strings_for_js(['connectedas', 'connectedasx', 'editroom'], 'mod_facetoface');
        } else {
            $mform->addElement('select', 'plugin', get_string('virtual_meeting_add', 'mod_facetoface'), $meeting_options);
            $mform->setType('plugin', PARAM_TEXT);
        }

        // Virtual room link.
        $mform->addElement('text', 'url', get_string('roomurl', 'mod_facetoface'), ['maxlength' => '1024', 'size' => '45']);
        $mform->addHelpButton('url', 'roomurl', 'mod_facetoface');
        $mform->setType('url', PARAM_URL);
        $mform->hideIf('url', 'plugin', 'noteq', room_virtualmeeting::VIRTUAL_MEETING_INTERNAL);

        // Custom fields: Building and Location.
        customfield_definition($mform, (object)['id' => $room->get_id()], 'facetofaceroom', 0, 'facetoface_room');

        // Add to sitewide list.
        $capability = has_capability('mod/facetoface:managesitewiderooms', \context_system::instance());
        if ($capability && !empty($seminar) && (!$room->exists() || $room->get_custom())) {
            $mform->addElement('advcheckbox', 'notcustom', get_string('addtositewidelist', 'mod_facetoface'));
            foreach ($pluginsadded as $pluginname) {
                $mform->hideIf('notcustom', 'plugin', 'eq', $pluginname);
            }
        } else {
            $mform->addElement('hidden', 'notcustom');
        }
        $mform->setType('notcustom', PARAM_INT);
        $mform->closeHeaderBefore('notcustom');

        // Version control.
        if (!empty($room) && $room->exists()) {
            $mform->addElement('header', 'versions', get_string('versioncontrol', 'mod_facetoface'));

            $mform->addElement(
                'static',
                'versioncreated',
                get_string('created', 'mod_facetoface'),
                user_helper::get_timestamp_and_profile($room->get_timecreated(), $room->get_usercreated())
            );

            if (!empty($room->get_timemodified()) and $room->get_timemodified() != $room->get_timecreated()) {
                $mform->addElement(
                    'static',
                    'versionmodified',
                    get_string('modified'),
                    user_helper::get_timestamp_and_profile($room->get_timemodified(), $room->get_usermodified())
                );
            }
        }

        // Buttons.
        if (empty($seminar)) {
            $label = null;
            if (!$room->get_id()) {
                $label = get_string('addroom', 'facetoface');
            }
            $this->add_action_buttons(true, $label);
        }
        // Get current plugin value.
        if ($virtual_meeting->get_plugin()) {
            $plugin = $virtual_meeting->get_plugin();
        } else if (!empty($room->get_url())) {
            $plugin = room_virtualmeeting::VIRTUAL_MEETING_INTERNAL;
        } else {
            $plugin = room_virtualmeeting::VIRTUAL_MEETING_NONE;
        }

        // Set default/existing data.
        $formdata = (object)[
            'id' => $room->get_id(),
            'name' => $room->get_name(),
            'roomcapacity' => $room->get_capacity(),
            'allowconflicts' => $room->get_allowconflicts(),
            'url' => $room->get_url(),
            'plugin' => $plugin,
            'description_editor' => ['text' => $room->get_description()],
            'notcustom' => $room->get_custom() ? 0 : 1,
            'description' => $room->get_description(),
            'descriptionformat' => FORMAT_HTML,
        ];
        customfield_load_data($formdata, 'facetofaceroom', 'facetoface_room');
        $formdata = file_prepare_standard_editor(
            $formdata,
            'description',
            $editoropts,
            $editoropts['context'],
            'mod_facetoface',
            'room',
            $room->get_id()
        );
        $this->set_data($formdata);
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        /** @var \mod_facetoface\room $room */
        $room = $this->_customdata['room'];
        /** @var room_virtualmeeting $virtual_meeting */
        $virtual_meeting = $this->_customdata['virtual_meeting'];

        if ((int)$data['roomcapacity'] <= 0) {
            // Client side JS validation does not work much in the hacky dialog forms - do it on server side!
            $errors['roomcapacity'] = get_string('required');
        }

        if ($room->get_id() and $room->get_allowconflicts() and $data['allowconflicts'] == 0) {
            // Make sure there are no existing conflicts before we switch the setting!
            if ($room->has_conflicts()) {
                $errors['allowconflicts'] = get_string('error:roomconflicts', 'mod_facetoface');
            }
        }

        if (room_virtualmeeting::is_virtual_meeting($data['plugin']) && empty($data['connected'])) {
            $errors['connections'] = get_string('error:disconnected', 'mod_facetoface');
        }

        if ($data['plugin'] == room_virtualmeeting::VIRTUAL_MEETING_INTERNAL) {
            if (empty($data['url'])) {
                $errors['url'] = get_string('err_required', 'form');
            } else if (!filter_var($data['url'], FILTER_VALIDATE_URL)) {
                $errors['url'] = get_string('error:urlformat', 'mod_facetoface');
            }
        }

        if ($virtual_meeting->exists() && !room_virtualmeeting::is_virtual_meeting($data['plugin'])) {
            $errors['plugin'] = get_string('error:plugin_is_disabled', 'mod_facetoface');
        }
        return $errors;
    }
}
