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

use mod_facetoface\output\{
    take_attendance_session_picker,
    take_attendance,
    take_attendance_bulk_action
};
use mod_facetoface\{seminar_event, seminar_session, seminar_session_list};
use moodle_url;
use context;
use stdClass;

/**
 * A class that generate take attendance form here, and this form is generated via widget and it
 * is a custom form. Instead of totara form or moodle form.
 *
 * Class take_attendance_tracking
 * @package mod_facetoface\attendance
 */
final class take_attendance_tracking implements attendance_tracking {
    /**
     * @var seminar_event
     */
    protected $seminarevent;

    /**
     * @var moodle_url
     */
    protected $url;

    /**
     * Default value is 'takeattendance'. As this attribute is for the generating downloading content so far.
     * @var string
     */
    protected $action;

    /**
     * Foreach object within a row, the attributes of object will look as specified below:
     *
     * + id: int                -> {user}.id
     * + idnumber: string       -> {user}.idnumber
     * + submissionid: int      -> {facetoface_signups}.id
     * + firstnamephonetic: string
     * + lastnamephonetic: string
     * + middlename: string
     * + alternatename: string
     * + firstname: string
     * + lastname: string
     * + email: string
     * + facetofaceid: int      -> {facetoface}.id
     * + course: int            -> {course}.id
     * + statuscode: int        -> {facetoface_signups_status|facetoface_signup_date_status}.statuscode
     * + grade: float|null      -> {facetoface_signups_status}.grade|null
     * + deleted: int           -> {user}.deleted
     * + suspended: int         -> {user}.suspended
     *
     * @var event_attendee[]
     */
    protected $rows;

    /**
     * This array containing these keys => values as specified below:
     *
     * + selected-sessiondate-id: int
     * + able-to-take-attendance: boolean
     *
     * @var array
     */
    protected $options;

    /**
     * @var seminar_session_list
     */
    private $sessions;

    /**
     * Determine whether we are going to disable the actions in taking attendance at event level or not.
     * @var bool $disabled
     */
    private $disabled = false;
    /**
     * take_attendance_tracking constructor.
     * @param seminar_event         $seminarevent
     * @param moodle_url            $url            Base Url of take attendance, fo example:
     *                                              $CFG->wwwroot/mod/facetoface/takeattendance.php
     *
     * @param context               $context        Context is needed to calculate whether user is able to take
     *                                              attendance or not.
     *
     * @param seminar_session_list  $sessions       List of session dates of a seminar, if it is not
     *                                              provided at construct, start to load one.
     */
    final public function __construct(seminar_event $seminarevent, moodle_url $url,
                                      context $context, ?seminar_session_list $sessions = null) {
        if (null == $sessions) {
            $sessions = $seminarevent->get_sessions();
        }

        $this->seminarevent = $seminarevent;
        $this->sessions = $sessions;
        $this->action = 'takeattendance';
        $this->rows = [];
        $this->options = [
            'selected-sessiondate-id' => 0,
            'able-to-take-attendance' => has_any_capability(
                [
                    'mod/facetoface:addattendees',
                    'mod/facetoface:removeattendees',
                    'mod/facetoface:takeattendance'
                ],
                $context
            )
        ];

        // We need to clear all of our params here, for the inside usage to add different parameters
        // into the URL here and also for the usage of javsacript at client side.
        $this->url = clone $url;
        $this->url->remove_all_params();
    }

    /**
     * Setting the sessiondate id, this determine whether we are going to generate the session
     * content or event content for attanedance tracking form.
     *
     * @param int $sessiondateid
     * @return take_attendance_tracking
     */
    public function set_sessiondate_id(int $sessiondateid): take_attendance_tracking {
        $this->options['selected-sessiondate-id'] = $sessiondateid;
        return $this;
    }

    /**
     * Start populating the data needed for taking attendance form.
     * @return void
     */
    private function load_attendees(): void {
        if (!empty($this->rows)) {
            return;
        }

        $helper = new attendance_helper();
        $this->rows = $helper->get_attendees(
            $this->seminarevent->get_id(),
            $this->options['selected-sessiondate-id']
        );
    }

    /**
     * Determine whether we are going to disable the actions in taking attendance at event level or not.
     *
     * @return bool
     */
    private function is_disable_bulk_action(): bool {
        if ($this->options['selected-sessiondate-id'] > 0) {
            $session = new seminar_session($this->options['selected-sessiondate-id']);
            return !$session->is_attendance_open();
        }
        // Disabled will happen when seminar_event is not open for attendance.
        return !$this->seminarevent->is_attendance_open();
    }

    /**
     * @return string
     */
    public function generate_content(): string {
        global $OUTPUT;
        $this->load_attendees();
        if (empty($this->rows)) {
            return '';
        }
        $hasarchive = false;
        foreach ($this->rows as $attendee) {
            if ($attendee === null) {
                continue;
            }
            if ($attendee->is_archived()) {
                $hasarchive = true;
                break;
            }
        }

        $formurl = clone $this->url;
        $formurl->params(
            [
                'takeattendance' => '1',
                'sd' => $this->options['selected-sessiondate-id'],
                's' => $this->seminarevent->get_id()
            ]
        );

        $formparams = [
            'action' => $formurl->out(false),
            'method' => 'post',
            'id' => 'attendanceform',
            'class' => 'f2f-attendance-form'
        ];

        $this->disabled = $this->is_disable_bulk_action();
        $content = $OUTPUT->render(
            take_attendance::create(
                $this->seminarevent,
                $this->url,
                $this->do_create_tablecontent(),
                $formparams,
                take_attendance_bulk_action::create($this->disabled),
                $this->options['selected-sessiondate-id'],
                $this->create_session_picker(),
                $hasarchive
            )
        );

        $this->set_form_javascript();
        return $content;
    }

    /**
     * Generating the content of the form, in taking attendance, depending on the selected session
     * date id, otherwise, it will generate the event level content.
     * @return string
     */
    private function do_create_tablecontent(): string {
        ob_start();
        $content = null;
        if ($this->options['selected-sessiondate-id'] > 0) {
            $content = new session_content($this->seminarevent, $this->action, $this->sessions);
        } else {
            $content = new event_content($this->seminarevent, $this->action, $this->sessions);
        }

        $content->generate_allowed_action_content($this->rows, $this->url, $this->disabled);

        $tablecontent = ob_get_contents();
        ob_end_clean();

        return $tablecontent;
    }

    /**
     * Returning the session_picker widget if the config is enabled, otherwise null
     *
     * @return take_attendance_session_picker|null.
     */
    private function create_session_picker(): ?take_attendance_session_picker {
        if ($this->sessions->is_empty() ||
            !$this->seminarevent->get_seminar()->get_sessionattendance()) {
            return null;
        }

        return take_attendance_session_picker::create(
            $this->seminarevent,
            $this->sessions,
            $this->url,
            $this->options['selected-sessiondate-id']
        );
    }

    /**
     * Setting up the form change detector from moodle.
     *
     * @return void
     */
    private function set_form_javascript(): void {
        global $PAGE;

        if ($this->options['able-to-take-attendance']) {
            $PAGE->requires->yui_module(
                'moodle-core-formchangechecker',
                'M.core_formchangechecker.init',
                [
                    ['formid' => 'attendanceform']
                ]
            );

            $PAGE->requires->string_for_js('changesmadereallygoaway', 'moodle');
        }
    }
}