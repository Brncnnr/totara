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

use mod_facetoface\bulk_list;
use mod_facetoface\import_helper;
use mod_facetoface\seminar_event;

class import_attendance extends \moodleform {

    protected function definition() {

        $mform = $this->_form;

        $mform->addElement('hidden', 's', $this->_customdata['s']);
        $mform->setType('s', PARAM_INT);

        $mform->addElement('hidden', 'sd', $this->_customdata['sd']);
        $mform->setType('sd', PARAM_INT);

        $mform->addElement('hidden', 'listid', $this->_customdata['listid']);
        $mform->setType('listid', PARAM_ALPHANUM);

        $mform->addElement('header', 'uploadcsvfile', get_string('uploadcsvfile', 'mod_facetoface'));

        $fileoptions = array('accepted_types' => array('.csv'));
        $mform->addElement('filepicker', 'userfile', get_string('csvtextfile', 'mod_facetoface'), null, $fileoptions);
        $mform->setType('userfile', PARAM_FILE);
        $mform->addRule('userfile', null, 'required');

        $encodings = \core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'mod_facetoface'), $encodings);

        $delimiters = import_helper::csv_get_delimiter_list();
        $mform->addElement('select', 'delimiter', get_string('delimiter', 'mod_facetoface'), $delimiters);
        $mform->setDefault('delimiter', get_config('facetoface', 'defaultcsvdelimiter'));

        $help = get_string('uploadattendance_help', 'mod_facetoface');
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
            $data->content = $this->get_file_content('userfile');
        }
        return $data;
    }

    /**
     * Clean up the upload data.
     * @param bulk_list $list
     */
    public function cancel(bulk_list $list) {
        $cir = new \csv_import_reader($list->get_list_id(), $list->get_srctype());
        $cir->cleanup();
        $list->clean();
    }

    /**
     * Upload event attendance and grades via file.
     *
     * @param \stdClass $formdata users to add to seminar event via file
     *      @var s seminar event id
     *      @var listid list id
     *      data via file
     * @param seminar_event $seminarevent
     * @param bulk_list $list
     */
    public static function upload($formdata, seminar_event $seminarevent, bulk_list $list) {

        $listid  = $list->get_list_id();
        $scrtype = $list->get_srctype();
        $optionalfields = [];
        if ((bool)$seminarevent->get_seminar()->get_eventgradingmanual()) {
            $optionalfields = ['eventgrade'];
        }

        // Large files are likely to take their time and memory. Let PHP know
        // that we'll take longer, and that the process should be recycled soon
        // to free up memory.
        \core_php_time_limit::raise(0);
        @raise_memory_limit(MEMORY_EXTRA);

        $errors = [];
        $cir = new \csv_import_reader($listid, $scrtype);
        $delimiter = import_helper::csv_detect_delimiter($formdata);
        if (!$delimiter) {
            $errors[] = get_string('error:delimiternotfound', 'mod_facetoface');
        } else {
            $readcount = $cir->load_csv_content($formdata->content, $formdata->encoding, $delimiter);
            if (!$readcount) {
                $errors[] = $cir->get_error();
            }
        }

        $headers = $cir->get_columns();
        if (!$headers) {
            $errors[] = get_string('error:csvcannotparse', 'mod_facetoface');
        }

        $cir->init();
        // Get headers and id column.
        $idfield = '';
        if (empty($errors)) {
            // Validate user identification fields.
            foreach ($headers as $header) {
                if (in_array($header, ['idnumber', 'username', 'email', 'signupid'])) {
                    if ($idfield != '') {
                        $errors[] = get_string('error:csvtoomanyidfields', 'mod_facetoface');
                        break;
                    }
                    $idfield = $header;
                }
            }
            if (empty($idfield)) {
                $errors[] = get_string('error:csvnoidfields', 'mod_facetoface');
            }
        }
        // Check that all required fields are provided.
        $requiredfields = [$idfield, 'eventattendance'];
        if (empty($errors)) {
            $notfound = array_diff($requiredfields, $headers);
            if (!empty($notfound)) {
                $errors[] = get_string('error:csvnorequiredcf', 'mod_facetoface', implode('\', \'', $notfound));
            }
        }
        // Convert headers to field names required for data storing.
        if (empty($errors)) {
            $fieldnames = [];
            foreach ($headers as $header) {
                $fieldnames[] = $header;
            }
        }
        // Prepare add users information.
        $rawdata = [];
        $errordata = [];
        if (empty($errors)) {
            $iter = 0;
            $inconsistentlines = [];
            $helper = new \mod_facetoface\attendance\attendance_helper();
            $attendees = $helper->get_attendees($seminarevent->get_id());
            while ($attempt = $cir->next()) {
                $iter++;

                $data = array_combine($fieldnames, $attempt);
                if (!$data) {
                    $inconsistentlines[] = $iter;
                    continue;
                }

                // Check that user exists.
                $userfound = false;
                foreach ($attendees as $id => $user) {
                    if (!isset($user->{$idfield})) {
                        continue;
                    }

                    $field = $user->{$idfield};
                    $fieldvalue = $data[$idfield];

                    // Force email to lowercase
                    if ($idfield === 'email') {
                        $field = mb_strtolower($field);
                        $fieldvalue = mb_strtolower($fieldvalue);
                    }

                    if ($field != $fieldvalue) {
                        continue;
                    }

                    $userfound = true;
                    $rawdata[$user->id] = $data;
                    break;
                }
                if (!$userfound) {
                    $errordata[] = $data;
                    continue;
                }
            }

            if (!empty($inconsistentlines)) {
                $errors[] = get_string('error:csvinconsistentrows', 'mod_facetoface', implode(', ', $inconsistentlines));
            }
        }

        if (!empty($errors)) {
            $errors = array_unique($errors);
            foreach ($errors as $error) {
                \core\notification::error($error);
            }
        } else {
            if (!array_diff($optionalfields, $headers)) {
                $requiredfields = array_merge($requiredfields, $optionalfields);
            }

            if (!empty($errordata)) {
                $csvdata = static::filter_data($errordata, $requiredfields);
                $list->set_validaton_results($csvdata);
            }
            $csvdata = static::filter_data($rawdata, $requiredfields);
            $list->set_all_user_data($csvdata);
            $cir->cleanup();
            redirect(new \moodle_url(
                '/mod/facetoface/attendees/list/import_attendance_confirm.php',
                ['s' => $seminarevent->get_id(), 'sd' => $formdata->sd, 'listid' => $listid]
            ));
        }
    }

    /**
     * Collect required csv data only.
     * @param array $rawdata
     * @param array $requiredfields
     * @return array
     */
    private static function filter_data(array $rawdata, array $requiredfields): array {
        $csvdata = [];
        array_walk(
            $rawdata,
            function ($data, $keyid) use (&$csvdata, $requiredfields) {
                $arrayreindexed = [];
                array_walk(
                    $requiredfields,
                    function ($field) use (&$arrayreindexed, $data) {
                        $arrayreindexed[$field] = $data[$field];
                    }
                );
                $csvdata[$keyid] = $arrayreindexed;
            }
        );
        return $csvdata;
    }
}