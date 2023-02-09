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

/**
 * Class assignment overrides confirm csv uploaded data
 */
class assignment_overrides_upload_confirm extends \moodleform {

    /**
     * @inheritDoc
     */
    protected function definition() {

        $mform = $this->_form;

        $mform->addElement('hidden', 'workflow_id', $this->_customdata['workflow_id']);
        $mform->setType('workflow_id', PARAM_INT);

        $mform->addElement('hidden', 'process_id', $this->_customdata['process_id']);
        $mform->setType('process_id', PARAM_INT);

        $this->add_action_buttons(true, get_string('confirm'));
    }
}
