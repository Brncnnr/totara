<?php
/*
 * This file is part of Totara LMS
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
 * @package modules
 * @subpackage facetoface
 */

namespace mod_facetoface\internal;

use coding_exception;
use core\output\flex_icon;
use core_date;
use html_table;
use html_table_cell;
use html_table_row;
use html_writer;
use mod_facetoface\asset_list;
use mod_facetoface\attendance_taking_status;
use mod_facetoface\attendees_helper;
use mod_facetoface\event_time;
use mod_facetoface\reservations;
use mod_facetoface\room;
use mod_facetoface\seminar;
use mod_facetoface\seminar_event;
use mod_facetoface\seminar_event_helper;
use mod_facetoface\seminar_event_list;
use mod_facetoface\seminar_session;
use mod_facetoface\signup;
use mod_facetoface\signup_helper;
use mod_facetoface\trainer_helper;
use mod_facetoface\internal\session_data;

use mod_facetoface\dashboard\filter_list;
use mod_facetoface\dashboard\render_session_option;
use mod_facetoface\dashboard\filters\event_time_filter;
use mod_facetoface\dashboard\filters\room_filter;

use mod_facetoface\output\session_time;
use mod_facetoface\output\show_previous_events;

use mod_facetoface\query\event\query;
use mod_facetoface\query\event\filter\event_times_filter as query_event_times_filter;
use mod_facetoface\query\event\sortorder\future_sortorder;
use mod_facetoface\query\event\sortorder\past_sortorder;

use mod_facetoface\signup\state\attendance_state;
use mod_facetoface\signup\state\booked;
use mod_facetoface\signup\state\event_cancelled;
use mod_facetoface\signup\state\requested;
use mod_facetoface\signup\state\requestedadmin;
use mod_facetoface\signup\state\requestedrole;
use mod_facetoface\signup\state\unable_to_attend;
use mod_facetoface\signup\state\declined;
use mod_facetoface\signup\state\not_set;
use mod_facetoface\signup\state\user_cancelled;
use mod_facetoface\signup\state\waitlisted;
use moodle_url;
use pix_icon;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Deprecated renderer functions.
 */
trait mod_facetoface_renderer_deprecated {

    /**
     * Outputs a table showing a list of sessions along with other information.
     *
     * Note some aspects of the API used here that may not be obvious:
     *
     * It's assumed that these sessions are all from the same facetoface activity.
     *
     * *** If a session has been booked by the user ***
     * If a session has been booked, then that one session should have a bookedsession property
     * containing that information. An appropriate object for this property would be the
     * first result from facetoface_get_user_submissions, obtained via array_shift.
     *
     * If 'multiple signups' is not allowed for the facetoface activity, then if a session has been booked, it should
     * be added to the bookedsession property of each session. It'll display the right data for each session
     * by checking for whether the id of booked session matches that of the session row being processed.
     *
     * @param array $sessions - array of session objects.
     * @param bool $viewattendees - true if the current user has this capability ('mod/facetoface:viewattendees').
     * @param bool $editevents - true if the current user has this capability ('mod/facetoface:editevents').
     * @param bool $displaytimezones - true if the timezones should be displayed.
     * @param array $reserveinfo - if managereserve if set to true for the facetoface, use facetoface_can_reserve_or_allocate
     * to fill out this array.
     * @param string|null $currenturl - generally this would be $PAGE->url.
     * @param bool $minimal - setting this to true will hide customfields, session status, attendance tracking and registration dates
     * in a tooltip when hovering over the signup link rather than in a column.
     * @param bool $returntoallsessions Returns the user to view all sessions after they signup/cancel.
     * @param bool|int $sessionattendance - one of seminar::SESSION_ATTENDANCE_xxx
     * @param int $eventattendance - one of seminar::EVENT_ATTENDANCE_xxx
     * @param bool $viewsignupperiod - true to display the "Sign-up period" table column
     * @param bool $viewactions - true to display the "Actions" table column
     * @return string containing html for this table.
     * @throws coding_exception
     * @deprecated since Totara 13
     */
    public function print_session_list_table(array $sessions, bool $viewattendees, bool $editevents, bool $displaytimezones, array $reserveinfo = array(),
                                             string $currenturl = null, bool $minimal = false, bool $returntoallsessions = true,
                                             $sessionattendance = seminar::SESSION_ATTENDANCE_DISABLED,
                                             int $eventattendance = seminar::EVENT_ATTENDANCE_LAST_SESSION_END,
                                             bool $viewsignupperiod = true, bool $viewactions = true): string {
        debugging('mod_facetoface_renderer::' . __FUNCTION__ . '() is deprecated. Please use mod_facetoface_renderer::render_session_list_table() instead.', DEBUG_DEVELOPER);

        $output = '';

        if (empty($sessions)) {
            // If there's no sessions, just return an empty string.
            return $output;
        }

        $sessionattendance = seminar::fix_up_session_attendance_time_with($eventattendance, $sessionattendance);
        $attendancetracking = $sessionattendance != seminar::SESSION_ATTENDANCE_DISABLED;
        if ($this->context === null || !has_capability('mod/facetoface:takeattendance', $this->context)) {
            // overwrite $attendancetracking if a user does not have permission to take attendance
            $attendancetracking = false;
        }

        $tableheader = array();

        // If we want the minimal table, no customfield columns are shown.
        if (!$minimal) {
            $customfields = customfield_get_fields_definition('facetoface_session', array('hidden' => 0));
            foreach ($customfields as $customfield) {
                if (!empty($customfield->showinsummary)) {
                    $tableheader[] = format_string($customfield->fullname);
                }
            }
        }

        if ($viewattendees) {
            $tableheader[] = get_string('capacity', 'mod_facetoface');
        } else {
            $tableheader[] = get_string('seatsavailable', 'mod_facetoface');
        }
        $tableheader[] = get_string('sessionslist:eventstatus', 'mod_facetoface');

        // If we want the minimal table, the registration dates are shown in a tooltip instead of a column.
        if (!$minimal && $viewsignupperiod) {
            $tableheader[] = get_string('signupperiodheader', 'mod_facetoface');
        }
        if (!empty($displaytimezones)) {
            $tableheader[] = get_string('sessionslist:timeandtimezone', 'mod_facetoface');
        } else {
            $tableheader[] = get_string('sessionslist:time', 'mod_facetoface');
        }
        $tableheader[] = get_string('room', 'mod_facetoface');
        if (!$minimal) {
            $tableheader[] = get_string('sessionslist:sessionstatus', 'mod_facetoface');
            if ($attendancetracking) {
                $tableheader[] = get_string('sessionslist:attendancetracking', 'mod_facetoface');
            }
        }

        if ($viewactions) {
            $tableheader[] = get_string('sessionslist:actions', 'mod_facetoface');
        }

        $table = new html_table();
        $table->summary = get_string('sessionslist', 'mod_facetoface');
        $table->attributes['class'] = 'mod_facetoface__sessionlist__table';
        $table->head = array_map(
            function($value) {
                return s($value);
            },
            $tableheader
        );
        $table->data = array();

        $includedeleted = has_capability('totara/core:seedeletedusers', $this->getcontext());

        foreach ($sessions as $session) {
            $seminarevent = (new seminar_event())->from_record_with_dates($session, false); // Set false to ignore extra fields
            $helper = new attendees_helper($seminarevent);

            $isbookedsession = (!empty($session->bookedsession) && ($session->id == $session->bookedsession->sessionid));
            $sessionstarted = $seminarevent->is_first_started();

            if ($seminarevent->get_cancelledstatus()) {
                $statuscodes = [event_cancelled::get_code()];
            } else if ($seminarevent->is_sessions()) {
                $statuscodes = attendance_state::get_all_attendance_code_with([booked::class]);
            } else {
                $statuscodes = [waitlisted::get_code()];
            }

            // Need to include reserved spaces here. If there is any.
            $signupcount = $helper->count_attendees_with_codes($statuscodes, $includedeleted, true);
            $sessionfull = ($signupcount >= $seminarevent->get_capacity());

            $rooms = \mod_facetoface\room_list::get_event_rooms($seminarevent->get_id());

            if (!$seminarevent->is_sessions()) {
                // An event without session dates, is a wait-listed event
                $sessionrow = array();

                if (!$minimal) {
                    $sessionrow = array_merge($sessionrow, $this->session_customfield_table_cells($session, $customfields));
                }

                // Capacity
                $sessionrow[] = $this->session_capacity_table_cell($seminarevent, $viewattendees, $signupcount);

                // Event status
                $sessionrow[] = $this->event_status_table_cell($session, $signupcount, 0, $eventattendance);

                if (!$minimal && $viewsignupperiod) {
                    // Sign-up period
                    $sessionrow[] = $this->session_resgistrationperiod_table_cell($seminarevent, 0, $displaytimezones);
                }

                // Session times (empty cell)
                $sessionrow[] = '';

                // Room (empty cell)
                $sessionrow[] = '';

                if (!$minimal) {
                    // Session status
                    $sessionrow[] = $this->session_status_table_cell($session, null, 0);

                    if ($attendancetracking) {
                        // Attendance tracking (empty cell)
                        $sessionrow[] = '';
                    }
                }

                if ($viewactions) {
                    // Actions
                    $reservelink = $this->session_options_reserve_link($seminarevent, $signupcount, $reserveinfo);
                    $signuplink = $this->session_options_signup_link($seminarevent, $minimal, $returntoallsessions, $displaytimezones);
                    $sessionrow[] = $this->session_options_table_cell($seminarevent, $viewattendees, $editevents, $reservelink, $signuplink);
                }

                $row = new html_table_row($sessionrow);

                // Set the CSS class for the row.
                if ($sessionstarted || $seminarevent->get_cancelledstatus()) {
                    $row->attributes = array('class' => 'dimmed_text');
                } else if ($isbookedsession) {
                    $row->attributes = array('class' => 'highlight');
                } else if ($sessionfull && !$seminarevent->get_allowoverbook()) {
                    $row->attributes = array('class' => 'dimmed_text');
                } else {
                    $row->attributes = array('class' => '');
                }
                $row->attributes['class'] .= ' mod_facetoface__sessionlist__table__sessionrow waitlisted';

                // Add row to table.
                $table->data[] = $row;
            } else {
                // If there are session dates, we create one row per session date, but some will be
                // given a rowspan value as they apply to the whole session rather than just the session date.

                // Make a copy of sessiondates to prevent seminar_event::get_xxx() from trashing it
                $sessiondates = clone $seminarevent->get_sessions();
                $datescount = $sessiondates->count();
                $firstsessiondate = true;

                /** @var \mod_facetoface\seminar_session $date */
                foreach ($sessiondates as $date) {
                    $sessionrow = [];
                    if ($firstsessiondate && !$minimal) {
                        $sessionrow = array_merge($sessionrow, $this->session_customfield_table_cells($session, $customfields, $datescount));
                    }

                    if ($firstsessiondate) {
                        // Capacity
                        $sessionrow[] = $this->session_capacity_table_cell($seminarevent, $viewattendees, $signupcount, $datescount);
                        // Event status
                        $sessionrow[] = $this->event_status_table_cell($session, $signupcount, $datescount, $eventattendance);
                        if (!$minimal && $viewsignupperiod) {
                            // Sign-up period
                            $sessionrow[] = $this->session_resgistrationperiod_table_cell($seminarevent, $datescount, $displaytimezones);
                        }
                    }

                    // Session times
                    $sessionrow[] = session_time::to_html($date->get_timestart(), $date->get_timefinish(), $date->get_sessiontimezone(), $displaytimezones);

                    // Room
                    $time = time();
                    if ($date->has_room() && $rooms->contains($date->get_roomid())) {
                        /** @var \mod_facetoface\room $room */
                        $room = $rooms->get($date->get_roomid());
                        $cell = new html_table_cell($this->get_room_details_html($room, $currenturl));
                        $cell->attributes['class'] = 'mod_facetoface__sessionlist__room';
                        $sessionrow[] = $cell;
                    } else {
                        $sessionrow[] = ''; // (empty)
                    }

                    if (!$minimal) {
                        $daterecord = $date->to_record();
                        // Session status
                        $sessionrow[] = $this->session_status_table_cell($session, $daterecord, $time);

                        if ($attendancetracking) {
                            // Attendance tracking
                            $sessionrow[] = $this->attendance_tracking_table_cell(
                                $session,
                                $daterecord,
                                $sessionattendance
                            );
                        }
                    }

                    if ($firstsessiondate) {
                        if ($viewactions) {
                            // Actions
                            $reservelink = $this->session_options_reserve_link($seminarevent, $signupcount, $reserveinfo);
                            $signuplink = $this->session_options_signup_link($seminarevent, $session->cancellationcutoff ?? 0, $minimal, $returntoallsessions, $displaytimezones);
                            $sessionrow[] = $this->session_options_table_cell($seminarevent, $viewattendees, $editevents, $reservelink, $signuplink, $datescount);
                        }
                    }

                    $row = new html_table_row($sessionrow);

                    // Set the CSS class for the row.
                    if ($sessionstarted || $seminarevent->get_cancelledstatus()) {
                        $row->attributes = array('class' => 'dimmed_text');
                    } else if ($isbookedsession) {
                        $row->attributes = array('class' => 'highlight');
                    } else if ($sessionfull && !$seminarevent->get_allowoverbook()) {
                        $row->attributes = array('class' => 'dimmed_text');
                    } else {
                        $row->attributes = array('class' => '');
                    }
                    $row->attributes['class'] .= ' mod_facetoface__sessionlist__table__sessionrow';
                    if ($firstsessiondate) {
                        $row->attributes['class'] .= ' firstsession';
                    }

                    // $firsessiondate should only be true on the iteration of this foreach loop.
                    $firstsessiondate = false;

                    // Add row to table.
                    $table->data[] = $row;
                }
            }
        }

        if (empty($table->data)) {
            // There were sessions when we checked at the beginning, but they've been eliminated
            // for one reason or another, so just return an empty string.
            return '';
        }

        $output .= $this->render($table);

        return $output;
    }

    /**
     * Print the list of a sessions
     *
     * @param seminar $seminar
     * @param int $roomid room id
     * @param int $eventtime one of constants defined in '\mod_facetoface\event_time'
     * @return string
     * @deprecated since Totara 13
     */
    public function print_session_list(seminar $seminar, int $roomid, int $eventtime = event_time::ALL): string {
        debugging('mod_facetoface_renderer::' . __FUNCTION__ . '() is deprecated. Please use mod_facetoface\output\session_list_table instead.', DEBUG_DEVELOPER);

        // Create a dummy filter_list on the fly.
        $filters = new filter_list(function (string $parname, $default, string $type) use ($roomid, $eventtime) {
            if ($parname === self::PARAM_FILTER_ROOMID) {
                return $roomid;
            } else if ($parname === self::PARAM_FILTER_EVENTTIME) {
                return $eventtime;
            }
            return $default;
        });

        // Create a dummy render_session_option on the fly.
        $option = (new render_session_option())
            ->set_displayactions(true)
            ->set_displayreservation(true)
            ->set_displaysignupperiod(true)
            ->set_displaytimezones(null)
            ->set_eventascendingorder(false)
            ->set_sessionascendingorder(false)
            ->set_eventtimes([]);

        // Pass through render_session_list().
        return $this->render_session_list($seminar, $filters, $option);
    }

    /**
     * Print the list of a sessions
     *
     * @param seminar               $seminar    seminar instance
     * @param filter_list           $filters    filter_list instance to filter out session events
     * @param render_session_option $option     render_session_option instance for extra rendering options
     * @param integer|null          $userid     user ID or null to use the current user
     * @return string
     * @deprecated since Totara 13
     */
    public function render_session_list(seminar $seminar, filter_list $filters, render_session_option $option, int $userid = null): string {
        debugging('mod_facetoface_renderer::' . __FUNCTION__ . '() is deprecated. Please use mod_facetoface\output\session_list_table instead.', DEBUG_DEVELOPER);

        global $USER, $OUTPUT, $PAGE;

        if (empty($userid)) {
            $userid = $USER->id;
        }

        $output = '';

        $context = $this->getcontext();
        $query = $filters->to_query($seminar, $context, $userid);
        $query->with_filter(new query_event_times_filter($option->get_eventtimes()));
        if ($option->get_eventascendingorder()) {
            $query->with_sortorder(new past_sortorder());
        } else {
            $query->with_sortorder(new future_sortorder());
        }
        $seminarevents = seminar_event_list::from_query($query);

        $viewattendees = has_capability('mod/facetoface:viewattendees', $context);
        $editevents = has_capability('mod/facetoface:editevents', $context);

        $sessionarray = [];
        if (!$seminarevents->is_empty()) {
            /** @var seminar_event $seminarevent */
            foreach ($seminarevents as $seminarevent) {
                $signup = signup::create($userid, $seminarevent);
                $sessiondata = \mod_facetoface\seminar_event_helper::get_sessiondata($seminarevent, $signup, $option->get_sessionascendingorder());
                $sessionarray[] = $sessiondata;
            }
        }

        if (empty($sessionarray)) {
            $output .= html_writer::tag('p',
                $OUTPUT->flex_icon('info') . get_string('noresults', 'mod_facetoface'),
                [ 'class' => 'mod_facetoface__sessionlist--empty' ]
            );
        } else {
            // We need to attach reservation information here on top of the upcoming session list table if managerreserve is set.
            $reserveinfo = [];
            if ($option->get_displayreservation()) {
                if (!empty($seminar->get_managerreserve())) {
                    // Include information about reservations when drawing the list of sessions.
                    $reserveinfo = \mod_facetoface\reservations::can_reserve_or_allocate($seminar, $sessionarray, $this->context);
                    $output .= html_writer::tag('p', get_string('lastreservation', 'mod_facetoface', $seminar->get_properties()));
                }
            }

            $sessionlist = $this->print_session_list_table(
                $sessionarray,
                $viewattendees,
                $editevents,
                $option->get_displaytimezones(),
                $reserveinfo,
                $PAGE->url,
                false,
                true,
                $seminar->get_sessionattendance(),
                $seminar->get_attendancetime(),
                $option->get_displaysignupperiod(),
                ($viewattendees || $editevents || $option->is_upcoming()) && $option->get_displayactions()
            );

            $class = 'mod_facetoface__sessionlist';
            /** Both upcomingsessionlist and previoussessionlist CSS classes should be considered @deprecated as of Totara 13. */
            if ($option->is_upcoming()) {
                $class .= ' upcomingsessionlist';
            } else {
                $class .= ' previoussessionlist';
            }
            $output .= html_writer::div($sessionlist, $class);
        }

        return $output;
    }

    /**
     * Add a table cells for each customfield value associated with a session.
     *
     * @param stdClass $session
     * @param array $customfields - as returned by facetoface_get_session_customfields().
     * @param int $datescount - this determines the rowspan. Count the number of session dates to get this figure.
     * @return array of table cells to be merged with an array for the rest of the cells.
     * @deprecated since Totara 13
     */
    private function session_customfield_table_cells(\stdClass $session, array $customfields, int $datescount = 0): array {
        debugging('mod_facetoface_renderer::' . __FUNCTION__ . '() is deprecated. Please do not use it.', DEBUG_DEVELOPER);

        $customfieldsdata = customfield_get_data($session, 'facetoface_session', 'facetofacesession', false);
        $sessionrow = array();

        foreach ($customfields as $customfield) {
            if (empty($customfield->showinsummary)) {
                continue;
            }
            if (array_key_exists($customfield->shortname, $customfieldsdata)) {
                $cell = new html_table_cell($customfieldsdata[$customfield->shortname]);
                if ($datescount > 1) {
                    $cell->rowspan = $datescount;
                }
                $sessionrow[] = $cell;
            } else {
                $cell = new html_table_cell('&nbsp;');
                if ($datescount > 1) {
                    $cell->rowspan = $datescount;
                }
                $sessionrow[] = $cell;
            }
        }

        return $sessionrow;
    }

    /**
     * Create a table cell containing a sessions capacity or seats remaining.
     *
     * If the user has viewattendees permissions, this will show capacity.
     * If not, then this will show seats remanining.
     *
     * @param seminar_event $seminarevent - An event, not session date
     * @param bool $viewattendees - true if they do have permissions.
     * @param int $signupcount - number currently signed up to this session.
     * @param int $datescount - this determines the rowspan. Count the number of session dates to get this figure.
     * @return html_table_cell
     * @throws coding_exception
     * @deprecated since Totara 13
     */
    private function session_capacity_table_cell(seminar_event $seminarevent, bool $viewattendees, int $signupcount, int $datescount = 0): \html_table_cell {
        debugging('mod_facetoface_renderer::' . __FUNCTION__ . '() is deprecated. Please do not use it.', DEBUG_DEVELOPER);

        $context = $this->context;
        if (null == $context) {
            // Using $PAGE->context if the internal $context had not been set.
            $context = $this->page->context;
        }

        $includedeleted = has_capability('totara/core:seedeletedusers', $context);
        $helper = new attendees_helper($seminarevent);

        if ($viewattendees) {
            if ($seminarevent->is_sessions()) {
                // All attendance statuses with booked and waitlisted status. If the event is not a wailisted event,
                // then we need to get all the attendees that have code from waitlisted to the fully_attendeed. So that
                // we can start figuring out the number of how many user is in waitlisted.
                $statuscodes = \mod_facetoface\signup\state\attendance_state::get_all_attendance_code_with(
                    [
                        \mod_facetoface\signup\state\booked::class,
                        \mod_facetoface\signup\state\waitlisted::class,
                    ]
                );


                $a = array('current' => $signupcount, 'maximum' => $seminarevent->get_capacity());
                $stats = get_string('capacitycurrentofmaximum', 'mod_facetoface', $a);
                if ($signupcount > $seminarevent->get_capacity()) {
                    $stats .= get_string('capacityoverbooked', 'mod_facetoface');
                }

                $waitlisted = $helper->count_attendees_with_codes($statuscodes, $includedeleted);
                $waitlisted -= $signupcount;

                if ($waitlisted > 0) {
                    $stats .= get_string('status_capacity_waitlisted', 'mod_facetoface', $waitlisted);
                }
            } else {
                // Since within the event that has no sesison date, and user that are in wait-list could be moved to
                // attendees, and it caused the number of wait-listed user being calculated and rendered wrong.
                // If there is any user that confirm as booked, then it should display the number of booked user within current
                $currentbookeduser = $helper->count_attendees_with_codes([booked::get_code()], $includedeleted);
                $a = array('current' => $currentbookeduser, 'maximum' => $seminarevent->get_capacity());
                $stats = get_string('capacitycurrentofmaximum', 'mod_facetoface', $a);
                if ($currentbookeduser > $seminarevent->get_capacity()) {
                    $stats .= get_string('capacityoverbooked', 'mod_facetoface');
                }
                if ($signupcount > 0) {
                    $stats .= get_string('status_capacity_waitlisted', 'mod_facetoface', $signupcount);
                }
            }
        } else {
            $stats = max(0, $seminarevent->get_capacity() - $signupcount);
        }

        $sessioncell = new html_table_cell($stats);
        if ($datescount > 1) {
            $sessioncell->rowspan = $datescount;
        }

        return $sessioncell;
    }

    /**
     * Create a table cell containing the link to a session attendance tracking page.
     *
     * @param \stdClass $session
     * @param \stdClass $date
     * @param int $attendancetime one of seminar::SESSION_ATTENDANCE_xxx
     * @return \mod_facetoface\output\attendance_tracking_table_cell
     * @throws coding_exception
     * @deprecated since Totara 13
     */
    protected function attendance_tracking_table_cell(\stdClass $session, \stdClass $date, int $attendancetime): \mod_facetoface\output\attendance_tracking_table_cell {
        debugging('mod_facetoface_renderer::' . __FUNCTION__ . '() is deprecated. Please do not use it.', DEBUG_DEVELOPER);

        $cell = new \mod_facetoface\output\attendance_tracking_table_cell();

        if (!empty($session->cancelledstatus)) {
            return $cell->set_state('cancelled');
        }

        $seminarevent = (new seminar_event())->from_record_with_dates($session, false);
        $helper = new attendees_helper($seminarevent);
        if (!$helper->count_attendees()) {
            return $cell
                ->set_text_l10n('attendancetracking:noattendees')
                ->set_state('none');
        }

        $seminasession = (new seminar_session())->from_record($date, false);
        $status = $seminasession->get_attendance_taking_status($attendancetime);
        switch ($status) {
            case attendance_taking_status::CLOSED_UNTILEND:
                return $cell
                    ->set_text_l10n('attendancetracking:openatend')
                    ->set_state('locked');

            case attendance_taking_status::CLOSED_UNTILSTART:
                return $cell
                    ->set_text_l10n('attendancetracking:openatstart')
                    ->set_state('locked');

            case attendance_taking_status::OPEN:
                // no break

            case attendance_taking_status::ALLSAVED:
                $url = new \moodle_url('/mod/facetoface/attendees/takeattendance.php', ['s' => $session->id, 'sd' => $date->id]);

                if ($status == attendance_taking_status::ALLSAVED) {
                    return $cell
                        ->set_link_l10n($url, 'attendancetracking:saved')
                        ->set_icon('check-circle-success')
                        ->set_state('saved');
                } else {
                    return $cell
                        ->set_link_l10n($url, 'attendancetracking:open')
                        ->set_state('open');
                }
        }

        throw new coding_exception("Invalid attendance taking status: {$status}");
    }

    /**
     * Create a table cell containing the status of a session.
     *
     * @param \stdClass $session
     * @param \stdClass|null $date
     * @param integer $timenow current time
     * @return html_table_cell
     * @deprecated since Totara 13
     */
    protected function session_status_table_cell(\stdClass $session, ?\stdClass $date, int $timenow): \html_table_cell {
        debugging('mod_facetoface_renderer::' . __FUNCTION__ . '() is deprecated. Please do not use it.', DEBUG_DEVELOPER);

        $status = \mod_facetoface\seminar_session_helper::get_status($session, $date, $timenow);
        if ($status === false) {
            return new html_table_cell();
        }
        return new html_table_cell($status);
    }

    /**
     * Create a table cell containing the status of an event.
     *
     * Examples would include 'In progress' or 'Booking open'.
     *
     * @param \stdClass $session
     * @param int $signupcount - number currently signed up to this session.
     * @param int $datescount - this determines the rowspan. Count the number of session dates to get this figure.
     * @param int|null $eventattendance - One of seminar::EVENT_ATTENDANCE_xxx or null to load from the seminar setting.
     * @return html_table_cell
     * @throws coding_exception
     * @deprecated since Totara 13
     */
    protected function event_status_table_cell(\stdClass $session, int $signupcount, int $datescount = 0, int $eventattendance = null): html_table_cell {
        debugging('mod_facetoface_renderer::' . __FUNCTION__ . '() is deprecated. Please do not use it.', DEBUG_DEVELOPER);

        [ $event_status, $booking_status, $user_status ] = seminar_event_helper::event_status($session, $signupcount, false);
        $statuses = \html_writer::tag('li', clean_string($event_status), [ 'class' => 'mod_facetoface__sessionlist__event-status__event']);
        if ($booking_status !== '') {
            $statuses .= \html_writer::tag('li', clean_string($booking_status), [ 'class' => 'mod_facetoface__sessionlist__event-status__booking']);
        }
        if ($user_status !== '') {
            $user_status = get_string('eventstatususerbooking', 'mod_facetoface', $user_status);
            $statuses .= \html_writer::tag('li', clean_string($user_status), [ 'class' => 'mod_facetoface__sessionlist__event-status__user']);
        }

        if ($this->context !== null && has_capability('mod/facetoface:takeattendance', $this->context)) {
            $attendance_status = $this->event_status_attendance_taking_html($session, $eventattendance);
            $statuses .= \html_writer::tag('li', $attendance_status, [ 'class' => 'mod_facetoface__sessionlist__event-status__attendance']);
        }
        $html = \html_writer::tag('ul', $statuses, [ 'class' => 'mod_facetoface__sessionlist__event-status' ]);
        $sessioncell = new html_table_cell($html);
        if ($datescount > 1) {
            $sessioncell->rowspan = $datescount;
        }
        return $sessioncell;
    }

    /**
     * Create a table cell containing the link to an event attendance tracking page.
     *
     * @param \stdClass $session
     * @param integer|null $eventattendance One of seminar::EVENT_ATTENDANCE_xxx, or null to load the seminar setting
     * @return string of HTML
     * @deprecated since Totara 13
     */
    public function event_status_attendance_taking_html(\stdClass $session, ?int $eventattendance): string {
        debugging('mod_facetoface_renderer::' . __FUNCTION__ . '() is deprecated. Please do not use it.', DEBUG_DEVELOPER);

        $seminarevent = (new seminar_event())->from_record_with_dates($session, false);
        $status = $seminarevent->get_attendance_taking_status($eventattendance, 0, true, true);
        // Put the event attendance link only when it is open.
        if ($status != attendance_taking_status::OPEN && $status != attendance_taking_status::ALLSAVED) {
            return '';
        }

        $url = new \moodle_url('/mod/facetoface/attendees/takeattendance.php', ['s' => $session->id]);
        if ($status == attendance_taking_status::ALLSAVED) {
            $html = $this->flex_icon('check-circle-success') . get_string('eventattendancetracking:saved', 'mod_facetoface');
            $state = 'saved';
        } else {
            $html = get_string('eventattendancetracking:open', 'mod_facetoface');
            $state = 'open';
        }
        return \html_writer::link($url, $html, ['class' => "mod_facetoface__sessionlist__attendance--{$state}__link"]);
    }

    /**
     * Creates a table cell containing the registration period, if any, for a session.
     *
     * @param seminar_event $seminarevent
     * @param int $datescount - determines the number for the rowspan.
     * @param boolean|null $displaytimezones true/false or null to use facetoface_displaysessiontimezones config
     * @return html_table_cell
     * @throws coding_exception
     * @deprecated since Totara 13
     */
    private function session_resgistrationperiod_table_cell(seminar_event $seminarevent, int $datescount = 0, bool $displaytimezones = null): \html_table_cell {
        debugging('mod_facetoface_renderer::' . __FUNCTION__ . '() is deprecated. Please do not use it.', DEBUG_DEVELOPER);

        // Signup Start Dates/times.
        $registrationstring = \mod_facetoface\output\session_time::to_html($seminarevent->get_registrationtimestart(), $seminarevent->get_registrationtimefinish(), 99, $displaytimezones);

        $sessioncell = new html_table_cell($registrationstring);
        if ($datescount > 1) {
            $sessioncell->rowspan = $datescount;
        }

        return $sessioncell;
    }

    /**
     * Creates a table cell for the options available for a session.
     *
     * @param seminar_event $seminarevent
     * @param bool $viewattendees - true if the user has this permission.
     * @param bool $editevents - true if the user has this permission.
     * @param string $reservelink - html generated with the method session_options_reserve_link().
     * @param string $signuplink - html generated with the method session_options_signup_link().
     * @param int $datescount - determines the number for the rowspan.
     * @return html_table_cell
     * @throws coding_exception
     * @deprecated since Totara 13
     */
    private function session_options_table_cell(seminar_event $seminarevent, bool $viewattendees, bool $editevents, string $reservelink, string $signuplink, int $datescount = 0): \html_table_cell {
        debugging('mod_facetoface_renderer::' . __FUNCTION__ . '() is deprecated. Please do not use it.', DEBUG_DEVELOPER);

        global $CFG;

        $actionlinks = '';
        $editbuttons = '';
        $timenow = time();

        // NOTE: This is not a nice hack, we can only guess where to return because there is no argument above.
        $bas = 0;
        if ($this->page->has_set_url() && $this->page->url->compare(new moodle_url('/mod/facetoface/view.php'), URL_MATCH_BASE)) {
            $bas = 1;
        }

        // Can edit sessions.
        if ($editevents) {
            if (!$seminarevent->get_cancelledstatus()) {
                $editbuttons .= $this->output->action_icon(new moodle_url('/mod/facetoface/events/edit.php', array('s' => $seminarevent->get_id(), 'backtoallsessions' => $bas)), new pix_icon('t/edit', get_string('editsession', 'mod_facetoface')));
                if (!$seminarevent->is_first_started($timenow)) {
                    $editbuttons .= $this->output->action_icon(new moodle_url('/mod/facetoface/events/cancel.php', array('s' => $seminarevent->get_id(), 'backtoallsessions' => $bas)), new pix_icon('t/block', get_string('cancelsession', 'mod_facetoface')));
                }
            }
            $editbuttons .= $this->output->action_icon(new moodle_url('/mod/facetoface/events/edit.php', array('s' => $seminarevent->get_id(), 'c' => 1, 'backtoallsessions' => $bas)), new pix_icon('t/copy', get_string('copysession', 'mod_facetoface')));
            $editbuttons .= $this->output->action_icon(new moodle_url('/mod/facetoface/events/delete.php', array('s' => $seminarevent->get_id(), 'backtoallsessions' => $bas)), new pix_icon('t/delete', get_string('deletesession', 'mod_facetoface')));
        }

        // Can view attendees.
        if ($viewattendees) {
            $actionlinks .= html_writer::link(new moodle_url('/mod/facetoface/attendees/view.php', array('s' => $seminarevent->get_id())), s(get_string('attendees', 'mod_facetoface')), array('title' => get_string('seeattendees', 'mod_facetoface'), 'class' => 'mod_facetoface__sessionlist__action__link'));
        }

        if (!empty($reservelink)) {
            $actionlinks .= $reservelink;
        }

        $showsignuplink = true;

        if (!enrol_is_enabled('totara_facetoface') || $CFG->enableavailability) {
            $cm = get_coursemodule_from_instance('facetoface', $seminarevent->get_facetoface());
            $modinfo = get_fast_modinfo($cm->course);
            $cm = $modinfo->get_cm($cm->id);

            // If Seminar enrolment plugin is not enabled check visibility of the activity.
            if (!enrol_is_enabled('totara_facetoface')) {
                // Check visibility of activity (includes visible flag, conditional availability, etc) before adding Sign up link.
                $showsignuplink = $cm->uservisible;
            }

            if ($CFG->enableavailability) {
                // Check whether this activity is available for the user. However if it's available, but not visible
                // for some reason we're still not displaying a link.
                $showsignuplink &= $cm->available;
            }
        }

        if (!empty($signuplink) && $showsignuplink) {
            $actionlinks .= $signuplink;
        }

        $options = '';
        if (empty($editbuttons) && empty($actionlinks)) {
            $options .= get_string('none', 'mod_facetoface');
        } else {
            if (!empty($editbuttons)) {
                $options .= html_writer::tag('nav', $editbuttons, ['class' => 'mod_facetoface__sessionlist__action__buttons']);
            }
            if (!empty($actionlinks)) {
                $options .= html_writer::tag('nav', $actionlinks, ['class' => 'mod_facetoface__sessionlist__action__links']);
            }
        }

        $sessioncell = new html_table_cell($options);
        if ($datescount > 1) {
            $sessioncell->rowspan = $datescount;
        }

        return $sessioncell;
    }

    /**
     * Returns the text containing registration start and end dates if there are any.
     *
     * @param seminar_event $seminarevent
     * @param boolean $displaytimezones Set true to display timezone
     * @return string to add to the tooltip and aria-label attributes of an html link.
     * @throws coding_exception
     * @deprecated since Totara 13
     */
    private function get_regdates_tooltip_info(seminar_event $seminarevent, bool $displaytimezones): string {
        debugging('mod_facetoface_renderer::' . __FUNCTION__ . '() is deprecated. Please do not use it.', DEBUG_DEVELOPER);

        $tooltip = array();
        if (!empty($seminarevent->get_registrationtimestart())) {
            $start = new stdClass();
            $start->startdate = userdate($seminarevent->get_registrationtimestart(), get_string('strftimedate', 'langconfig'));
            $start->starttime = userdate($seminarevent->get_registrationtimestart(), get_string('strftimetime', 'langconfig'));
            if ($displaytimezones) {
                $start->timezone = core_date::get_user_timezone();
                $tooltip[] = get_string('registrationhoverhintstarttz', 'facetoface', $start);
            } else {
                $tooltip[] = get_string('registrationhoverhintstart', 'facetoface', $start);
            }
        }
        if (!empty($seminarevent->get_registrationtimefinish())) {
            $finish = new stdClass();
            $finish->enddate = userdate($seminarevent->get_registrationtimefinish(), get_string('strftimedate', 'langconfig'));
            $finish->endtime = userdate($seminarevent->get_registrationtimefinish(), get_string('strftimetime', 'langconfig'));
            if ($displaytimezones) {
                $finish->timezone = core_date::get_user_timezone();
                $tooltip[] = get_string('registrationhoverhintendtz', 'facetoface', $finish);
            } else {
                $tooltip[] = get_string('registrationhoverhintend', 'facetoface', $finish);
            }
        }

        return implode("\n", $tooltip);
    }

    /**
     * Create the html for a reserve spaces link in the session list table.
     * This needs to be inserted into a table cell. E.g. add it to the options table cell.
     *
     * @param seminar_event $seminarevent
     * @param int $signupcount - number currently signed up to this session.
     * @param array $reserveinfo - if managereserve if set to true for the facetoface, use facetoface_can_reserve_or_allocate
     * to fill out this array.
     * @return string
     * @throws coding_exception
     * @deprecated since Totara 13
     */
    private function session_options_reserve_link(seminar_event $seminarevent, int $signupcount, array $reserveinfo = array()): string {
        debugging('mod_facetoface_renderer::' . __FUNCTION__ . '() is deprecated. Please do not use it.', DEBUG_DEVELOPER);

        $reservelink = '';
        if ($seminarevent->get_cancelledstatus()) {
            return $reservelink;
        }

        $currentime = time();
        if ($seminarevent->is_first_started($currentime) || $seminarevent->is_over($currentime)) {
            return $reservelink;
        }

        // Output links to reserve/allocate spaces.
        if (!empty($reserveinfo)) {
            $sessreserveinfo = $reserveinfo;
            if (!$seminarevent->get_allowoverbook()) {
                $sessreserveinfo = \mod_facetoface\reservations::limit_info_to_capacity_left($seminarevent, $sessreserveinfo,
                    max(0, $seminarevent->get_capacity() - $signupcount));
            }
            $sessreserveinfo = \mod_facetoface\reservations::limit_info_by_session_date($seminarevent, $sessreserveinfo);
            if (!empty($sessreserveinfo['allocate']) && !is_int($sessreserveinfo['maxallocate']) && $sessreserveinfo['maxallocate'][$seminarevent->get_id()] > 0) {
                // Able to allocate and not used all allocations for other sessions.
                $allocateurl = new moodle_url('/mod/facetoface/reservations/allocate.php', ['s' => $seminarevent->get_id(), 'backtoallsessions' => 1]);
                $reservelink .= html_writer::start_span('mod_facetoface__sessionlist__action__reserve');
                $reservelink .= html_writer::link($allocateurl, get_string('allocate', 'mod_facetoface'), array('class' => 'mod_facetoface__sessionlist__action__reserve__link'));
                $reservelink .= ' (' . $sessreserveinfo['allocated'][$seminarevent->get_id()] . '/' . $sessreserveinfo['maxallocate'][$seminarevent->get_id()] . ')';
                $reservelink .= html_writer::end_span();
            }
            if (!empty($sessreserveinfo['reserve']) && !is_int($sessreserveinfo['maxreserve']) && $sessreserveinfo['maxreserve'][$seminarevent->get_id()] > 0) {
                if (empty($sessreserveinfo['reservepastdeadline'])) {
                    $reserveurl = new moodle_url('/mod/facetoface/reservations/reserve.php', ['s' => $seminarevent->get_id(), 'backtoallsessions' => 1]);
                    $reservelink .= html_writer::start_span('mod_facetoface__sessionlist__action__reserve');
                    $reservelink .= html_writer::link($reserveurl, get_string('reserve', 'mod_facetoface'), array('class' => 'mod_facetoface__sessionlist__action__reserve__link'));
                    $reservelink .= ' (' . $sessreserveinfo['reserved'][$seminarevent->get_id()] . '/' . $sessreserveinfo['maxreserve'][$seminarevent->get_id()] . ')';
                    $reservelink .= html_writer::end_span();
                }
            } else if (!empty($sessreserveinfo['reserveother']) && empty($sessreserveinfo['reservepastdeadline'])) {
                $reserveurl = new moodle_url('/mod/facetoface/reservations/reserve.php', ['s' => $seminarevent->get_id(), 'backtoallsessions' => 1]);
                $reservelink .= html_writer::start_span('mod_facetoface__sessionlist__action__reserve');
                $reservelink .= html_writer::link($reserveurl, get_string('reserveother', 'mod_facetoface'), array('class' => 'mod_facetoface__sessionlist__action__reserve__link'));
                $reservelink .= html_writer::end_span();
            }

            if (has_capability('mod/facetoface:managereservations', $this->context)) {
                $managereserveurl = new moodle_url('/mod/facetoface/reservations/manage.php', array('s' => $seminarevent->get_id()));

                $reservelink .= html_writer::start_span('mod_facetoface__sessionlist__action__reserve');
                $reservelink .= html_writer::link($managereserveurl, get_string('managereservations', 'mod_facetoface'), array('class' => 'mod_facetoface__sessionlist__action__reserve__link'));
                $reservelink .= html_writer::end_span();
            }
        }

        return $reservelink;
    }

    /**
     * Creates the html for the signup/cancel/'more info' links. Basically the links where
     * their set up depends on the user's signup status and abilities around signing up (such
     * as whether they can cancel).
     *
     * @param seminar_event $seminarevent
     * @param int $cancellationcutoff - event cancellation cut off.
     * @param bool $regdatestooltip - true if we want the dates in a tooltip for the signup link.
     * @param bool $returntoallsessions True if we want the user to return to view all sessions after an action.
     * @param bool $displaytimezones
     * @return string to be put into an options cell in the sessions table.
     * @throws coding_exception
     * @deprecated since Totara 13
     */
    private function session_options_signup_link(seminar_event $seminarevent, int $cancellationcutoff, bool $regdatestooltip = false, bool $returntoallsessions = true, bool $displaytimezones = true): string {
        debugging('mod_facetoface_renderer::' . __FUNCTION__ . '() is deprecated. Please do not use it.', DEBUG_DEVELOPER);

        global $USER;
        $signuplink = '';

        $sessionstarted = $seminarevent->is_first_started();

        $timenow = time();
        // Registration status.
        if (!empty($seminarevent->get_registrationtimestart()) && $seminarevent->get_registrationtimestart() > $timenow) {
            $registrationopen = false;
        } else {
            $registrationopen = true;
        }

        if (!empty($seminarevent->get_registrationtimefinish()) && $timenow > $seminarevent->get_registrationtimefinish()) {
            $registrationclosed = true;
        } else {
            $registrationclosed = false;
        }

        // Prepare singup and cancel links.
        $urlparams = array('s' => $seminarevent->get_id());
        if ($returntoallsessions) {
            $urlparams['backtoallsessions'] = 1;
        }

        $signupurl = new moodle_url($this->get_signup_link($seminarevent), $urlparams);
        $cancelurl = new moodle_url('/mod/facetoface/cancelsignup.php', $urlparams);

        // Temporarility set the cancellation cut off
        $oldcutoff = $seminarevent->get_cutoff();
        $seminarevent->set_cutoff($cancellationcutoff);
        $seminar = $seminarevent->get_seminar();

        $signup = signup::create($USER->id, $seminarevent);
        $state = $signup->get_state();

        $statuscodes = attendance_state::get_all_attendance_code_with(
            [
                requested::class,
                requestedrole::class,
                requestedadmin::class,
                waitlisted::class,
                booked::class
            ]
        );

        // Check if the user is allowed to cancel his booking.
        $allowcancellation = $signup->can_switch(\mod_facetoface\signup\state\user_cancelled::class);

        if ($signup->exists() && in_array($state::get_code(), $statuscodes)) {
            // The session is booked where signup is exist and the state of signup is matching with the
            // states above.
            if (!$sessionstarted) {
                $signuplink .= html_writer::link($signupurl, get_string('eventmoreinfo', 'mod_facetoface'), array('title' => get_string('eventmoreinfo', 'mod_facetoface'), 'class' => 'mod_facetoface__sessionlist__action__link'));
            }
            if ($allowcancellation) {
                $canceltext = ($state instanceof \mod_facetoface\signup\state\waitlisted) ? 'cancelwaitlist' : 'cancelbooking';
                $signuplink .= html_writer::link($cancelurl, get_string($canceltext, 'mod_facetoface'), array('title' => get_string($canceltext, 'mod_facetoface'), 'class' => 'mod_facetoface__sessionlist__action__link'));
            }
        } else if (!$sessionstarted) {
            if (!$seminarevent->has_capacity() && !$seminarevent->get_allowoverbook()) {
                // No sign-up link
            } else {
                if (!$seminarevent->get_cancelledstatus() && $registrationopen == true && $registrationclosed == false) {
                    if (!$seminar->has_unarchived_signups() || $seminar->get_multiplesessions() == 1) {
                        // Ok to register.
                        if ($regdatestooltip) {
                            $tooltip = $this->get_regdates_tooltip_info($seminarevent, $displaytimezones);
                        } else {
                            $tooltip = '';
                        }
                        $signuptext = \mod_facetoface\signup_helper::expected_signup_state($signup)->get_action_label();
                        if (empty($signuptext)) {
                            $signuptext = get_string('eventmoreinfo', 'mod_facetoface');
                        }
                        $signuplink .= html_writer::link($signupurl, $signuptext, array('title' => $tooltip, 'aria-label' => $tooltip, 'class' => 'mod_facetoface__sessionlist__action__link'));
                    } else {
                        $signuplink .= html_writer::span(get_string('error:alreadysignedup', 'mod_facetoface'), '',
                            array('aria-label' => get_string('error:alreadysignedup', 'mod_facetoface')));
                    }
                }
            }
        }

        if (empty($signuplink)) {
            if ($sessionstarted && $allowcancellation) {
                $canceltext = ($state instanceof \mod_facetoface\signup\state\waitlisted) ? 'cancelwaitlist' : 'cancelbooking';
                $signuplink = html_writer::link($cancelurl, get_string($canceltext, 'mod_facetoface'), array('title' => get_string($canceltext, 'mod_facetoface'), 'class' => 'mod_facetoface__sessionlist__action__link'));
            }
        }

        // Revert to the original cut off.
        $seminarevent->set_cutoff($oldcutoff);
        return $signuplink;
    }

    /**
     * Action bar html output.
     *
     * @param \mod_facetoface\seminar $seminar
     * @deprecated since Totara 13
     */
    public function print_action_bar(\mod_facetoface\seminar $seminar) : void {
        debugging('The method mod_facetoface_renderer::' . __FUNCTION__ . '() has been deprecated. Please use mod_facetoface_renderer::render_action_bar() instead.', DEBUG_DEVELOPER);
        echo $this->render_action_bar($seminar);
    }

    /**
     * Filter bar html output.
     *
     * @param \mod_facetoface\seminar $seminar
     * @param integer $roomid
     * @param integer $eventtime one of \mod_facetoface\event_time::xxx
     * @deprecated since Totara 13
     */
    public function print_filter_bar(\mod_facetoface\seminar $seminar, int &$roomid, int &$eventtime) : void {
        debugging('The method mod_facetoface_renderer::' . __FUNCTION__ . '() has been deprecated. Please use mod_facetoface_renderer::render_filter_bar() instead.', DEBUG_DEVELOPER);

        $id = html_writer::random_id('id-');

        $roomselect = $this->get_filter_by_room($seminar, $roomid);
        $eventtimeselect = $this->get_filter_by_event_time($seminar, $eventtime);

        $filterbar = \mod_facetoface\output\seminarevent_filterbar::builder($id)
            ->add_param(self::PARAM_FILTER_F2FID, $seminar->get_id())
            ->add_param(self::PARAM_FILTER_ROOMID, $roomid)
            ->add_param(self::PARAM_FILTER_EVENTTIME, $eventtime);

        $roomdisabled = count($roomselect) <= 2;
        $filterbar
            ->add_filter(self::PARAM_FILTER_ROOMID, $roomselect, 'room', get_string('filterbyroom', 'facetoface'), $roomdisabled)
            ->add_filter(self::PARAM_FILTER_EVENTTIME, $eventtimeselect, 'eventtime', get_string('filterbyeventtime', 'facetoface'));

        echo $this->render($filterbar->build());
    }

    /**
     * Get an array for room filter.
     *
     * @param \mod_facetoface\seminar $seminar
     * @param integer $roomid
     * @return array consisting of [ id => name ]
     * @deprecated since Totara 13
     */
    protected function get_filter_by_room(seminar $seminar, int &$roomid) : array {
        debugging('The method mod_facetoface_renderer::' . __FUNCTION__ . '() has been deprecated. Please use mod_facetoface\filter_list instead.', DEBUG_DEVELOPER);

        $rooms = (new room_filter())->get_options($seminar);
        if (!array_key_exists($roomid, $rooms)) {
            $roomid = 0;
        }
        return $rooms;
    }

    /**
     * Get an array for the event time filter.
     *
     * @param \mod_facetoface\seminar $seminar
     * @param integer $eventtime one of \mod_facetoface\event_time::xxx
     * @return array consisting of [ event_time::xxx => labeltext ]
     * @deprecated since Totara 13
     */
    protected function get_filter_by_event_time(seminar $seminar, int &$eventtime) : array {
        debugging('The method mod_facetoface_renderer::' . __FUNCTION__ . '() has been deprecated. Please use mod_facetoface\filter_list instead.', DEBUG_DEVELOPER);

        $times = (new event_time_filter())->get_options($seminar);
        if (!array_key_exists($eventtime, $times)) {
            $eventtime = array_key_first($times);
        }
        return $times;
    }

    /**
     * Filter by room html output select input.
     *
     * @param \mod_facetoface\seminar $seminar
     * @param int $roomid
     * @return int
     * @deprecated since Totara 13
     */
    public function filter_by_room(\mod_facetoface\seminar $seminar, int $roomid): int {
        debugging('The method mod_facetoface_renderer::' . __FUNCTION__ . '() has been deprecated. Please use mod_facetoface_renderer::render_filter_bar() instead.', DEBUG_DEVELOPER);

        global $OUTPUT, $PAGE;

        $rooms = \mod_facetoface\room_list::get_seminar_rooms($seminar->get_id());
        if ($rooms->count() > 1) {
            $roomselect = array(0 => get_string('allrooms', 'facetoface'));
            // Here used to be some fancy code that deal with missing room names,
            // that magic cannot be done easily any more, allow selection of named rooms only here.
            foreach ($rooms as $room) {
                /** @var room $room */
                $roomname = format_string($room->get_name());
                if ($roomname === '') {
                    continue;
                }
                $roomselect[$room->get_id()] = $roomname;
            }

            if (!isset($roomselect[$roomid])) {
                $roomid = 0;
            }

            if (count($roomselect) > 2) {
                echo $OUTPUT->single_select($PAGE->url, 'roomid', $roomselect, $roomid, null, null, array('label' => get_string('filterbyroom', 'facetoface')));
            }
        } else {
            $roomid = 0;
        }

        return $roomid;
    }

    /**
     * Gets the HTML output for room details.
     *
     * @param \mod_facetoface\room $room - The room instance to get details for
     * @param string|null $backurl
     * @param bool $joinnow to display virtual room link if exists and time has come(15 min prior to the session start time)
     * @return string containing room details with relevant html tags.
     * @deprecated since Totara 13.2
     */
    public function get_room_details_html(room $room, string $backurl = null, bool $joinnow = false): string {
        debugging('mod_facetoface_renderer::' . __FUNCTION__ . '() is deprecated. Please use mod_facetoface_renderer::get_session_room_details_html() instead.', DEBUG_DEVELOPER);
        $session = new seminar_session();
        return $this->get_session_room_details_html($session, $room, $backurl, $joinnow);
    }

    /**
     * Set signup link when enrolment method is enabled and called.
     *
     * @param string $link
     * @deprecated since Totara 13
     */
    public function set_signup_link(string $link): void {
        debugging('mod_facetoface_renderer::' . __FUNCTION__ . '() is deprecated. Please use mod_facetoface\\hook\\alternative_signup_link instead.', DEBUG_DEVELOPER);
        $this->signuplink = $link;
    }
}
