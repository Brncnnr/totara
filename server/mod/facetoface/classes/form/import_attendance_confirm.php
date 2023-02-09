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
use mod_facetoface\seminar_event;
use mod_facetoface\signup\state\{attendance_state};

class import_attendance_confirm  extends \moodleform {

    protected function definition() {
        $mform = & $this->_form;

        $mform->addElement('hidden', 's', $this->_customdata['s']);
        $mform->setType('s', PARAM_INT);

        $mform->addElement('hidden', 'sd', $this->_customdata['sd']);
        $mform->setType('sd', PARAM_INT);

        $mform->addElement('hidden', 'listid', $this->_customdata['listid']);
        $mform->setType('listid', PARAM_ALPHANUM);

        $this->add_action_buttons(true, get_string('confirm'));
    }

    /**
     * Clean up the upload data.
     * @param \mod_facetoface\bulk_list $list
     */
    public function cancel(bulk_list $list) {
        $cir = new \csv_import_reader($list->get_list_id(), $list->get_srctype());
        $cir->cleanup();
        $list->clean();
    }

    /**
     * Process and save csv data to database.
     * @param seminar_event $seminarevent
     * @param bulk_list $list
     * @param \context_module $context
     */
    public function process_attendance(seminar_event $seminarevent, bulk_list $list, \context_module $context) {

        $status = attendance_state::get_all_attendance_csv();

        $signuplist = \mod_facetoface\signup_list::signups_for_event($seminarevent->get_id());
        // Adding new attendees.
        $userlist = $list->get_user_ids();
        $grades = [];
        $attendance = [];
        foreach ($signuplist as $signup) {
            /** @var \mod_facetoface\signup $signup */
            // User with the archived course completion record is not allowed to update the eventattendance and eventgrade values.
            if (in_array($signup->get_userid(), $userlist) && $signup->get_archived() == 0) {
                $data = $list->get_user_data($signup->get_userid());
                $state = $data['eventattendance'];
                if (in_array($state, array_keys($status))) {
                    $attendance[$signup->get_id()] = $status[$state]::get_code();
                    if (isset($data['eventgrade'])) {
                        $value = trim($data['eventgrade']);
                        if (is_numeric($value) && $value >= 0 && $value <= 100) {
                            $grades[$signup->get_id()] = (float)$value;
                        } else {
                            unset($attendance[$signup->get_id()]);
                        }
                    }
                }
            }
        }
        $result = \mod_facetoface\signup_helper::process_attendance($seminarevent, $attendance, $grades);
        if ($result) {
            $params = ['s' => $seminarevent->get_id(), 'sd' => $this->_customdata['sd']];
            // Trigger take attendance update event.
            \mod_facetoface\event\attendance_updated::create_from_session(
                $seminarevent->to_record(),
                $context
            )->trigger();
            $url  = new \moodle_url('/mod/facetoface/attendees/takeattendance.php', $params);
            \core\notification::success(get_string('updateattendeessuccessful', 'mod_facetoface'));
        } else {
            $url = $list->get_returnurl();
            \core\notification::error(get_string('error:takeattendance', 'mod_facetoface'));
        }
        $this->cancel($list);
        redirect($url);
    }
}