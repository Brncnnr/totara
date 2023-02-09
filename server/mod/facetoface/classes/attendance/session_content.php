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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\attendance;
defined('MOODLE_INTERNAL') || die();

use mod_facetoface\output\take_attendance_status_picker;
use mod_facetoface\seminar_session;
use mod_facetoface\signup\state\state;
use moodle_url;
use totara_table;
use mod_facetoface\signup\state\not_set;
use html_writer;

/**
 * This class was created with the purpose to only be used within this sub-package. And the purpose
 * of it is to generate the content table of session attendance tracking, which is either interactive
 * content or downloadable content.
 *
 * Please do not instantiate it somewhere else apart from this sub-package and test place.
 *
 * Class session_content
 * @package mod_facetoface\attendance
 */
final class session_content extends content_generator {

    /**
     * Creating the table for readonly content.
     *
     * @param moodle_url $url
     * @return totara_table
     */
    private function do_create_table(moodle_url $url): totara_table {
        $table = new totara_table('event-take-attendance');
        $actionurl = clone $url;
        $actionurl->params(
            [
                'sesskey' => sesskey(),
                'onlycontent' => true,
                'action' => $this->action,
                'takeattendance' => 1
            ]
        );

        $table->define_baseurl($actionurl);
        $table->set_attribute('class', 'generalbox mod-facetoface-attendees ' . $this->action);

        $headers = [
            '',
            get_string('learner'),
            get_string('sessionattendanceheader', 'mod_facetoface'),
        ];
        $columns = ['checkbox', 'name', 'attendance'];

        $table->define_columns($columns);
        $table->define_headers($headers);
        $table->setup();
        return $table;
    }

    /**
     * Generating the sessions table that is allowed to be edit on browser for user user.
     *
     * @param event_attendee[] $rows
     * @param moodle_url       $url
     * @param bool $disabled
     * @return totara_table
     */
    public function generate_allowed_action_content(array $rows, moodle_url $url, bool $disabled = false): totara_table {

        $this->disabled = $disabled;
        $table = $this->do_create_table($url);

        $seminar = $this->seminarevent->get_seminar();
        $course = get_course($seminar->get_course());

        foreach ($rows as $row) {
            $data = [];
            $attendee = clone($row);
            if (!$attendee->id) {
                // We only populate the attendee name if it is has an Id associated with, otherwise,
                // we skip it. This happened because a manager could reserve a space for their staff
                // but the staff never completed the reservation, therefore it should not be count
                // as taking attendance.
                continue;
            }

            $this->reset_attendee_statuscode($attendee);

            $data[] = $this->create_checkbox($attendee);

            $url = user_get_profile_url($attendee->id, $course);
            if ($url) {
                $data[] = html_writer::link($url, fullname($attendee));
            } else {
                $data[] = fullname($attendee);
            }

            // Attendance's status of the single attendee
            $data[] = $this->create_attendance_status($attendee);
            $table->add_data($data);
        }

        $table->finish_html();
        return $table;
    }

    /**
     * @inheritdoc
     * @param event_attendee[] $rows
     * @param $format export format string csv|excel|ods|etc
     * @return array Array<string, array>
     */
    public function generate_downloadable_content(array $rows, string $format): array {

        if ($format == 'csvforupload') {
            return $this->generate_export_for_upload($rows, $format);
        }
        return $this->generate_export_content($rows, $format);
    }

    /**
     * Generate export content
     * @param event_attendee[] $rows
     * @param $format export format string csv|excel|ods|etc
     * @return array Array<string, array>
     */
    private function generate_export_content(array $rows, string $format): array {
        global $PAGE;

        $headers = [];
        $useridentity = get_extra_user_fields($PAGE->context);
        $showemail = in_array('email', $useridentity);
        $showidnumber = in_array('idnumber', $useridentity);
        if ($showemail) {
            $headers[] = get_string('email');
        }
        if ($showidnumber) {
            $headers[] = get_string('idnumber');
        }
        $headers[] = get_string('learner');
        $headers[] = get_string('attendancetime_start', 'mod_facetoface');
        $headers[] = get_string('attendancetime_end', 'mod_facetoface');
        $headers[] = get_string('sessionattendanceheader', 'mod_facetoface');

        $definition = [
            'headers' => $headers,
            'rows' => [],
        ];

        if (empty($rows)) {
            return $definition;
        }

        $exportrows = [];

        foreach ($rows as $attendee) {
            $data = [];
            if (!$attendee->id) {
                continue;
            }

            $this->reset_attendee_statuscode($attendee);

            if ($showemail) {
                $data[] = $attendee->email;
            }
            if ($showidnumber) {
                $data[] = $attendee->idnumber;
            }

            $data[] = fullname($attendee);
            $data[] = \mod_facetoface\output\session_time::format_datetime($attendee->timestart, $format);
            $data[] = \mod_facetoface\output\session_time::format_datetime($attendee->timefinish, $format);

            $attendancestatus = not_set::get_code();
            if (isset($this->statusoptions[$attendee->statuscode])) {
                $attendancestatus = $this->statusoptions[$attendee->statuscode];
            }
            $data[] = $attendancestatus;

            $exportrows[] = $data;
        }

        $definition['rows'] = $exportrows;
        return $definition;
    }

    /**
     * Generate export CSV content for Upload event attendance
     * @param event_attendee[] $rows
     * @param $format export format string csv|excel|ods|etc
     * @return array Array<string, array>
     */
    private function generate_export_for_upload(array $rows, string $format): array {

        $definition = [
            'headers' => ['signupid', get_string('learner'), 'eventattendance'],
            'rows'    => [],
        ];

        if (empty($rows)) {
            return $definition;
        }

        $statecsvcodes = [];
        $states = state::get_all_states();
        foreach ($states as $state) {
            $statecsvcodes[$state::get_code()] = $state::get_csv_code();
        }

        $exportrows = [];

        foreach ($rows as $attendee) {
            $data = [];
            if (!$attendee->id) {
                continue;
            }

            $this->reset_attendee_statuscode($attendee);

            $data[] = $attendee->signupid;
            $data[] = fullname($attendee);

            $attendancestatus = not_set::get_code();
            if (isset($this->statusoptions[$attendee->statuscode])) {
                $attendancestatus = $statecsvcodes[$attendee->statuscode];
            }
            $data[] = $attendancestatus;

            $exportrows[] = $data;
        }

        $definition['rows'] = $exportrows;
        return $definition;
    }
}