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
use mod_facetoface\output\event_grade_input;
use mod_facetoface\signup\state\{not_set, booked, state};
use mod_facetoface\signup_status;
use moodle_url;
use totara_table;
use html_writer;

/**
 * This class was created with the purpose to only be used within this sub-package. And the purpose
 * of it is to generate the content table of event attendance tracking, which is either interactive
 * content or readonly content (downloadable content as well)
 *
 * Please do not instantiate it somewhere else apart from this sub-package and test place.
 *
 * Class event_content
 * @package mod_facetoface\attendance
 */
final class event_content extends content_generator {

    /**
     * Creating the table for content
     *
     * @param moodle_url $url
     * @return totara_table
     */
    private function do_create_table(moodle_url $url): totara_table {
        $table = new totara_table('event-tracking-attendance');
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

        $headers = ['', get_string('learner')];
        $columns = ['checkbox', 'name'];

        $seminar = $this->seminarevent->get_seminar();
        if ($seminar->get_sessionattendance()) {
            foreach ($this->statusoptions as $key => $label) {
                $identifier = "status-code-{$key}";
                $o = new \stdClass();
                $o->statuslabel = $label;

                $headers[] = get_string('sessionstatus', 'mod_facetoface', $o);
                $columns[] = $identifier;
            }
        }

        // Adding the other columns here after those status columns
        $headers = array_merge($headers, [get_string('eventattendanceheader', 'mod_facetoface')]);
        $columns = array_merge($columns, ['attendance']);

        if ($seminar->get_eventgradingmanual()) {
            $headers = array_merge($headers, [get_string('eventgradeheader', 'mod_facetoface')]);
            $columns = array_merge($columns, ['grade']);
        }

        $table->define_headers($headers);
        $table->define_columns($columns);
        $table->setup();
        return $table;
    }

    /**
     * Generating a table of taking attendance, however, with this table, the system is allowing
     * the editor to be able to edit the content. Therefore, it is quite different from downloadable
     * content or readonly table. As there should have no checkbox or selection box included.
     *
     * @param event_attendee[] $rows
     * @param moodle_url $url
     * @param bool $disabled
     * @return totara_table
     */
    public function generate_allowed_action_content(array $rows, moodle_url $url, bool $disabled = false): totara_table {
        global $CFG;

        $this->disabled = $disabled;
        $table = $this->do_create_table($url);

        $seminar = $this->seminarevent->get_seminar();
        $course = get_course($seminar->get_course());
        $isessionattendance = $seminar->get_sessionattendance();

        $helper = new attendance_helper();
        $stats = $helper->get_calculated_session_attendance_status($this->seminarevent->get_id());

        // The step is a step attribute specifies the interval between legal numbers in an <input> element.
        $decimals = grade_get_setting($seminar->get_course(), 'decimalpoints', $CFG->grade_decimalpoints);
        if ((int)$decimals > 0) {
            $separator = get_string('decsep', 'langconfig');
            // Keep in mind localised decimal separator, russian/german <input type="number" step="0,001" .../> is not working
            $this->step = $separator === '.' ? '0.' . str_repeat(0, (int) $decimals - 1) . '1' : null;
        }

        foreach ($rows as $attendee) {
            if ($attendee === null) {
                continue;
            }

            $data = [];

            $this->reset_attendee_statuscode($attendee);
            $data[] = $this->create_checkbox($attendee);
            $url = user_get_profile_url($attendee->id, $course);
            if ($url) {
                $data[] = html_writer::link($url, fullname($attendee));
            } else {
                $data[] = fullname($attendee);
            }

            if ($isessionattendance) {
                $stat = isset($stats[$attendee->id]) ? $stats[$attendee->id] : [];
                // If seminar enabled session tracking, which means that our editor is able to
                // take attendance at session's date level.

                foreach ($this->statusoptions as $key => $label) {
                    $data[] = isset($stat[$key]) ? (int) $stat[$key] : 0;
                }
            }

            $data[] = $this->create_attendance_status($attendee);

            if ($seminar->get_eventgradingmanual()) {
                $data[] = $this->event_grade_status($attendee);
            }

            // If grade is needed, please add it here
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
     * @inheritdoc
     * @param event_attendee $attendee
     * @return string
     */
    protected function event_grade_status(event_attendee $attendee): string {
        global $OUTPUT;

        $status = signup_status::find_current($attendee->get_signupid());

        // Disable it if signup is archived already.
        $disabled = $this->disabled || $attendee->is_archived();

        return $OUTPUT->render(
            event_grade_input::create($attendee, $status, $disabled, $this->step)
        );
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
            $headers[] = get_string('bulkaddsourceidnumber', 'mod_facetoface');
        }

        $headers[] = get_string('learner');

        $issessionattendance = $this->seminarevent->get_seminar()->get_sessionattendance();
        if ($issessionattendance) {
            // Only incude those statuses header, if the session attendance is enabled for seminar.
            foreach ($this->statusoptions as $key => $label) {
                $headers[] = $label;
            }
        }

        $headers[] = get_string('eventattendanceheader', 'mod_facetoface');

        $eventgrade = (bool)$this->seminarevent->get_seminar()->get_eventgradingmanual();
        if ($eventgrade) {
            $headers[] = get_string('eventgradeheader', 'mod_facetoface');
        }

        $definition = [
            'headers' => $headers,
            'rows' => []
        ];

        if (empty($rows)) {
            return $definition;
        }

        $helper = new attendance_helper();
        $stats = $helper->get_calculated_session_attendance_status($this->seminarevent->get_id());

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

            if ($issessionattendance) {
                $stat = isset($stats[$attendee->id]) ? $stats[$attendee->id] : [];
                foreach ($this->statusoptions as $code => $label) {
                    $data[] = isset($stat[$code]) ? (int)$stat[$code] : 0;
                }
            }

            $attendancestatus = not_set::get_string();
            if (isset($this->statusoptions[$attendee->statuscode])) {
                $attendancestatus = $this->statusoptions[$attendee->statuscode];
            }
            $data[] = $attendancestatus;

            if ($eventgrade) {
                $data[] = $attendee->get_grade() !== null ? (string)$attendee->get_grade() : '';
            }

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

        $headers = ['signupid', get_string('learner'), 'eventattendance'];

        $eventgrade = (bool)$this->seminarevent->get_seminar()->get_eventgradingmanual();
        if ($eventgrade) {
            $headers[] = 'eventgrade';
        }

        $definition = [
            'headers' => $headers,
            'rows'    => []
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

            $attendancestatus = not_set::get_string();
            if (isset($this->statusoptions[$attendee->statuscode])) {
                $attendancestatus = $statecsvcodes[$attendee->statuscode];
            }
            $data[] = $attendancestatus;

            if ($eventgrade) {
                $data[] = $attendee->get_grade() !== null ? (string) $attendee->get_grade() : '';
            }

            $exportrows[] = $data;
        }

        $definition['rows'] = $exportrows;
        return $definition;
    }
}
