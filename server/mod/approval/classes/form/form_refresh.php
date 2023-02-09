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

use mod_approval\model\form\form;
use mod_approval\plugininfo\approvalform;

defined('MOODLE_INTERNAL') || die();

class form_refresh extends \moodleform {

    /**
     * @inheritDoc
     */
    protected function definition() {
        $mform = $this->_form;

        $rid = $this->_customdata['rid'];
        $form = $this->_customdata['form'];
        $form_version = $this->_customdata['form_version'];
        $plugin_version = $this->_customdata['plugin_version'];

        $mform->addElement('hidden', 'id', $form->id);
        $mform->setType('id', PARAM_INT);

        // Report id, for returning to a specific report.
        $mform->addElement('hidden', 'rid', $rid);
        $mform->setType('rid', PARAM_INT);

        $this->add_action_buttons(true, get_string('refresh'));
    }
}