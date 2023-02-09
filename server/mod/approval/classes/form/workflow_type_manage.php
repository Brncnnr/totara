<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @package mod_approval
 */

namespace mod_approval\form;

defined('MOODLE_INTERNAL') || die();

use container_approval\approval as container;

class workflow_type_manage extends \moodleform {

    const MAX_LENGTH = 255;

    /**
     * @inheritDoc
     */
    protected function definition() {
        global $TEXTAREA_OPTIONS;

        $mform = $this->_form;

        $rid = $this->_customdata['rid'];
        $workflow_type = $this->_customdata['workflow_type'];

        $mform->addElement('hidden', 'id', $workflow_type->id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'rid', $rid);
        $mform->setType('rid', PARAM_INT);

        $mform->addElement('text', 'name', get_string('workflow_type_name_label', 'mod_approval'), ['size' => 45]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('error:workflow_type_name_value_too_long', 'mod_approval', self::MAX_LENGTH), 'maxlength', self::MAX_LENGTH);

        // We don't need autosave here
        $editor_options = $TEXTAREA_OPTIONS;
        $editor_options['context'] = container::get_default_category_context();
        $editor_options['autosave'] = false;
        // description.
        $mform->addElement('editor', 'description_editor', get_string('workflow_type_description_label', 'mod_approval'), ['rows' => 7], $editor_options);

        $this->add_action_buttons(true, get_string('savechanges'));

        // Set default/existing data.
        $formdata = (object)[
            'name' => $workflow_type->name,
            'description' => '',
        ];
        $this->set_data($formdata);
    }
}