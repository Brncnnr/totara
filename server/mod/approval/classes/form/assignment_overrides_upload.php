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

use mod_approval\csv_import_helper;
use mod_approval\model\assignment\helper\csv_upload;

defined('MOODLE_INTERNAL') || die();

/**
 * Class assignment overrides upload csv file
 */
class assignment_overrides_upload extends \moodleform {

    /**
     * @inheritDoc
     */
    protected function definition() {

        $mform = $this->_form;

        $mform->addElement('hidden', 'workflow_id', $this->_customdata['workflow_id']);
        $mform->setType('workflow_id', PARAM_INT);

        $mform->addElement('hidden', 'process_id', $this->_customdata['process_id']);
        $mform->setType('process_id', PARAM_INT);

        $mform->addElement('header', 'assignment_overrides', get_string('upload_csv_file', 'mod_approval'));

        $fileoptions = array('accepted_types' => array('.csv'));
        $mform->addElement('filepicker', 'assignment_override_file', get_string('csv_text_file', 'mod_approval'), null, $fileoptions);
        $mform->setType('assignment_override_file', PARAM_FILE);
        $mform->addRule('assignment_override_file', null, 'required');

        $encodings = \core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('csv_file_encoding', 'mod_approval'), $encodings);

        $delimiters = csv_import_helper::get_delimiter_string_list();
        $mform->addElement('select', 'delimiter', get_string('csv_file_delimiter', 'mod_approval'), $delimiters);
        $mform->setDefault('delimiter', 'comma');

        $help = get_string('upload_csv_file_help', 'mod_approval', (object)['level_list' => $this->_customdata['help_substring'], 'remove_code' => csv_upload::REMOVE_CODE]);
        $mform->addelement('html', format_text($help, FORMAT_MARKDOWN));

        $this->add_action_buttons(true, get_string('continue'));
    }

    /**
     * Return submitted data if properly submitted or returns NULL if validation fails or
     * if there is no submitted data.
     *
     * @return object submitted data; NULL if not valid or not submitted or cancelled
     */
    public function get_data() {
        $data = parent::get_data();
        if ($data) {
            $data->content = $this->get_file_content('assignment_override_file');
        }
        return $data;
    }
}
