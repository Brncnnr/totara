<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\form;

use mod_approval\plugininfo\approvalform;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

class form_manage extends \moodleform {

    const MAX_LENGTH = 255;

    /**
     * @inheritDoc
     */
    protected function definition() {
        $mform = $this->_form;

        $rid = $this->_customdata['rid'];
        $form = $this->_customdata['form'];

        $mform->addElement('hidden', 'id', $form->id);
        $mform->setType('id', PARAM_INT);

        // Report id, for returning to a specific report.
        $mform->addElement('hidden', 'rid', $rid);
        $mform->setType('rid', PARAM_INT);

        $mform->addElement('text', 'title', get_string('form_name_label', 'mod_approval'), ['size' => 45]);
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->addRule('title', get_string('error:form_name_value_too_long', 'mod_approval', self::MAX_LENGTH), 'maxlength', self::MAX_LENGTH);

        // If this is a new form, select a plugin.
        if (empty($form->id)) {
            $plugin_selects = [];
            $plugins = approvalform::get_enabled_plugins();
            if (empty($plugins)) {
                // Generate an error.
                $link = new moodle_url('/mod/approval/form/index.php');
                print_error('error:no_plugins_enabled', 'mod_approval', $link->out());
            }
            foreach ($plugins as $plugin_name) {
                $plugin_info = approvalform::from_plugin_name($plugin_name);
                $plugin_selects[$plugin_name] = $plugin_info->displayname;
            }
            $mform->addElement('select', 'plugin_name', get_string('form_plugin_name', 'mod_approval'), $plugin_selects);
            $mform->addRule('title', null, 'required', null, 'client');
        }


        $this->add_action_buttons(true, get_string('savechanges'));

        // Set default/existing data.
        $formdata = (object)[
            'title' => $form->title,
        ];
        $this->set_data($formdata);
    }
}