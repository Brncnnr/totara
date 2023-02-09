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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\output;

use stdClass;
use moodle_url;
use coding_exception;
use context;
use html_table;
use html_table_cell;
use html_table_row;
use html_writer;
use core\output\flex_icon;
use core\output\template;
use mod_facetoface_renderer;
use mod_facetoface\attendance_taking_status;
use mod_facetoface\attendees_helper;
use mod_facetoface\dashboard\filter_list;
use mod_facetoface\dashboard\render_session_list_config;
use mod_facetoface\internal\session_data;
use mod_facetoface\seminar;
use mod_facetoface\seminar_event;
use mod_facetoface\seminar_event_helper;
use mod_facetoface\seminar_session;
use mod_facetoface\seminar_session_helper;
use mod_facetoface\signup\state\attendance_state;
use mod_facetoface\signup\state\booked;
use mod_facetoface\signup\state\waitlisted;
use mod_facetoface\signup_helper;
use renderer_base;

defined('MOODLE_INTERNAL') || die();

/**
 * The seminar event list table.
 */
final class session_list_table extends html_table {
    /** The name of the mustache template */
    const TEMPLATE_NAME = 'core/table';

    /** CSS class prefix */
    const SESSION_LIST_CSS = 'mod_facetoface__sessionlist__';

    /** @var context */
    private $context;

    /** @var mod_facetoface_renderer */
    private $renderer;

    /**
     * Create an instance of the session list table.
     *
     * @param session_data[] $sessions
     * @param render_session_list_config $config
     * @param string|null $id
     * @return session_list_table
     * @throws coding_exception thrown if no sessions
     */
    public static function create(array $sessions, render_session_list_config $config, string $id = null): session_list_table {
        if (empty($sessions)) {
            // If there are no sessions, throw an exception.
            throw new coding_exception('Cannot create a table with no sessions.');
        }

        $self = new self($config->context);
        $self->id = $id;
        $self->initialise($sessions, $config);
        $self->renderer = null;
        return $self;
    }

    /**
     * Is the table empty?
     *
     * @return boolean
     */
    public function is_empty(): bool {
        return empty($this->head) || empty($this->data);
    }

    /**
     * Retrieve the template name which should be used to render this table.
     * @return string
     */
    public function get_template(): string {
        return self::TEMPLATE_NAME;
    }

    /**
     * Prepare an object that contains data that can be used to output a table using a template.
     * The template data must only be rendered by the template returned by get_template().
     *
     * @param renderer_base $output
     * @return stdClass Object containing the data structure
     */
    public function export_for_template(renderer_base $output) {
        // So far, just ask html_table.
        return parent::export_for_template($output);
    }

    /**
     * Constructor.
     *
     * @param context $context
     */
    private function __construct(context $context) {
        $this->context = $context;
    }

    /**
     * Return a mod_facetoface_renderer instance.
     *
     * @return mod_facetoface_renderer
     */
    private function get_renderer(): mod_facetoface_renderer {
        global $PAGE;
        if (!$this->renderer) {
            $this->renderer = $PAGE->get_renderer('mod_facetoface');
            $this->renderer->setcontext($this->context);
        }
        return $this->renderer;
    }

    /**
     * Create html_table_cell.
     *
     * @param string $text
     * @param integer $rowspan
     * @param string $class
     * @return html_table_cell
     */
    private static function create_table_cell(string $text, int $rowspan, string $class): html_table_cell {
        $text = trim($text);
        // trimming &nbsp;'s
        $text = preg_replace('/^(\&nbsp;)+|(\&nbsp;)+$/', '', $text);
        if ($text === '') {
            $cell = empty_table_cell::create_empty();
        } else {
            $cell = new html_table_cell($text);
        }
        if ($rowspan > 1) {
                $cell->rowspan = $rowspan;
        }
        $cell->attributes['class'] = self::SESSION_LIST_CSS . $class;
        return $cell;
    }

    /**
     * Return a placeholder table cell.
     *
     * @return html_table_cell
     */
    private static function table_cell_placeholder(): html_table_cell {
        static $placeholdercell;
        if (!$placeholdercell) {
            $placeholdercell = empty_table_cell::create_placeholder();
        }
        return $placeholdercell;
    }

    /**
     * Initialiser.
     *
     * @param session_data[] $sessions
     * @param render_session_list_config $config
     * @return void
     */
    private function initialise(array $sessions, render_session_list_config $config): void {
        global $CFG;
        require_once($CFG->dirroot . '/mod/facetoface/renderer.php');

        // If we want the minimal table, no customfield columns are shown.
        $customfields = array();
        if (!$config->minimal) {
            $customfields = customfield_get_fields_definition('facetoface_session', array('hidden' => 0));
        }

        $tableheader = $this->table_header($config, $customfields);

        $this->summary = get_string('sessionslist', 'mod_facetoface');
        $this->attributes['class'] = self::SESSION_LIST_CSS.'table';
        $this->head = array_map(
            function ($key, $value) {
                return self::create_table_cell(s($value), 1, $key);
            },
            array_keys($tableheader),
            $tableheader
        );
        $this->data = array();

        $includedeleted = has_capability('totara/core:seedeletedusers', $config->context);

        foreach ($sessions as $session) {
            $seminarevent = (new seminar_event())->from_record_with_dates($session, false);
            $isbookedsession = (!empty($session->bookedsession) && ($session->id == $session->bookedsession->sessionid));
            $signupcount = attendees_helper::count_signups($seminarevent, $includedeleted);

            $baserowclasses = array_merge([self::SESSION_LIST_CSS.'table__sessionrow'], mod_facetoface_renderer::table_row_status_classes($seminarevent, $isbookedsession, $signupcount, $config->currenttime));

            if (!$seminarevent->is_sessions()) {
                $sessionrowclasses = $baserowclasses;
                // An event without session dates, is a wait-listed event
                $sessionrow = array();

                if (!$config->minimal) {
                    $sessionrow = array_merge($sessionrow, $this->table_cells_customfield($session, $customfields));
                }

                // Event status
                $sessionrow[] = $this->table_cell_event_status($session, $config, $signupcount, 0);

                // Capacity
                $sessionrow[] = $this->table_cell_capacity($seminarevent, $config, $signupcount);

                if (!$config->minimal && $config->viewsignupperiod) {
                    // Sign-up period
                    $sessionrow[] = $this->table_cell_signup_period($seminarevent, $config, 0);
                }

                // Session times (empty cell)
                $sessionrow[] = empty_table_cell::create_empty(self::SESSION_LIST_CSS.'session-time');

                // Rooms (empty cell)
                $sessionrow[] = empty_table_cell::create_empty(self::SESSION_LIST_CSS.'room');

                // Facilitators (empty cell)
                $sessionrow[] = empty_table_cell::create_empty(self::SESSION_LIST_CSS.'facilitator');

                if (!$config->minimal) {
                    // Session status
                    $sessionrow[] = $this->table_cell_session_status($seminarevent, null, $config, false);
                }

                if ($config->viewactions) {
                    // Actions
                    $sessionrow[] = $this->table_cell_actions($seminarevent, $config);
                }

                $row = new html_table_row($sessionrow);

                // Set the CSS class for the row.
                $sessionrowclasses[] = 'firstsession';
                $row->attributes['class'] = implode(' ', $sessionrowclasses);

                // Add row to table.
                $this->data[] = $row;
            } else {
                // If there are session dates, we create one row per session date, but some will be
                // given a rowspan value as they apply to the whole session rather than just the session date.

                // Make a copy of sessiondates to prevent seminar_event::get_xxx() from trashing it
                $sessiondates = clone $seminarevent->get_sessions();
                $datescount = $sessiondates->count();
                $firstsessiondate = true;

                /** @var seminar_session $date */
                foreach ($sessiondates as $date) {
                    $sessionrowclasses = $baserowclasses;
                    $sessionrow = [];
                    if (!$config->minimal) {
                        if ($firstsessiondate) {
                            $sessionrow = $this->table_cells_customfield($session, $customfields, $datescount);
                        } else {
                            // Fill with place holders.
                            $sessionrow = array_fill(0, count($customfields), self::table_cell_placeholder());
                        }
                    }

                    if ($firstsessiondate) {
                        // Event status
                        $sessionrow[] = $this->table_cell_event_status($session, $config, $signupcount, $datescount);
                        // Capacity
                        $sessionrow[] = $this->table_cell_capacity($seminarevent, $config, $signupcount, $datescount);
                        // Sign-up period
                        if (!$config->minimal && $config->viewsignupperiod) {
                            $sessionrow[] = $this->table_cell_signup_period($seminarevent, $config, $datescount);
                        }
                    } else {
                        // Event status
                        $sessionrow[] = self::table_cell_placeholder();
                        // Capacity
                        $sessionrow[] = self::table_cell_placeholder();
                        // Sign-up period
                        if (!$config->minimal && $config->viewsignupperiod) {
                            $sessionrow[] = self::table_cell_placeholder();
                        }
                    }

                    // Session times
                    $sessionrow[] = $this->table_cell_session_time($date, $config);

                    // Rooms.
                    $sessionrow[] = $this->table_cell_rooms($seminarevent, $date, $config);

                    // Facilitators.
                    $sessionrow[] = $this->table_cell_facilitators($seminarevent, $date, $config);

                    if (!$config->minimal) {
                        // Session status & attendance tracking
                        $sessionrow[] = $this->table_cell_session_status($seminarevent, $date, $config, true);
                    }

                    if ($config->viewactions) {
                        // Actions
                        if ($firstsessiondate) {
                            $sessionrow[] = $this->table_cell_actions($seminarevent, $config, $datescount);
                        } else {
                            $sessionrow[] = self::table_cell_placeholder();
                        }
                    }

                    $row = new html_table_row($sessionrow);

                    // Set the CSS class for the row.
                    if ($firstsessiondate) {
                        $sessionrowclasses[] = 'firstsession';
                    }
                    $row->attributes['class'] = implode(' ', $sessionrowclasses);

                    // $firsessiondate should only be true on the iteration of this foreach loop.
                    $firstsessiondate = false;

                    // Add row to table.
                    $this->data[] = $row;
                }
            }
        }

        // Remove empty columns if requested.
        $this->cleanup_columns($config);
    }

    /**
     * Table headers.
     *
     * @param render_session_list_config $config
     * @param array $customfields
     * @return array
     */
    private function table_header(render_session_list_config $config, array $customfields): array {
        $tableheader = array();

        foreach ($customfields as $customfield) {
            if (!empty($customfield->showinsummary)) {
                $tableheader['cf-'.$customfield->shortname] = format_string($customfield->fullname);
            }
        }

        $tableheader['event-status'] = get_string('sessionslist:eventstatus', 'mod_facetoface');
        if ($config->viewattendees) {
            $tableheader['capacity'] = get_string('sessionslist:bookedandcapacity', 'mod_facetoface');
        } else {
            $tableheader['capacity'] = get_string('seatsavailable', 'mod_facetoface');
        }

        if (!$config->minimal && $config->viewsignupperiod) {
            $tableheader['registrationperiod'] = get_string('signupperiodheader', 'mod_facetoface');
        }
        if ($config->displaytimezones) {
            $tableheader['session-time'] = get_string('sessionslist:timeandtimezone', 'mod_facetoface');
        } else {
            $tableheader['session-time'] = get_string('sessionslist:time', 'mod_facetoface');
        }

        $tableheader['room'] = get_string('rooms', 'mod_facetoface');
        $tableheader['facilitator'] = get_string('facilitators', 'mod_facetoface');

        if (!$config->minimal) {
            $tableheader['session-status'] = get_string('sessionslist:sessionstatus', 'mod_facetoface');
        }

        if ($config->viewactions) {
            $tableheader['actions'] = get_string('sessionslist:actions', 'mod_facetoface');
        }

        return $tableheader;
    }

    /**
     * Add a table cells for each customfield value associated with a session.
     *
     * @param session_data $session
     * @param array $customfields - as returned by facetoface_get_session_customfields().
     * @param int $datescount - this determines the rowspan. Count the number of session dates to get this figure.
     * @return array of table cells to be merged with an array for the rest of the cells.
     */
    private function table_cells_customfield(session_data $session, array $customfields, int $datescount = 0): array {
        $customfieldsdata = customfield_get_data($session, 'facetoface_session', 'facetofacesession', false);
        $sessionrow = array();

        foreach ($customfields as $customfield) {
            if (empty($customfield->showinsummary)) {
                continue;
            }
            $class = 'cf-' . $customfield->shortname;
            if (array_key_exists($customfield->shortname, $customfieldsdata)) {
                $sessionrow[] = static::create_table_cell($customfieldsdata[$customfield->shortname], $datescount, $class);
            } else {
                $sessionrow[] = static::create_table_cell('', $datescount, $class);
            }
        }

        return $sessionrow;
    }

    /**
     * Create a table cell containing the status of an event.
     *
     * Examples would include 'In progress' or 'Booking open'.
     *
     * @param session_data $session
     * @param render_session_list_config $config
     * @param int $signupcount - number currently signed up to this session.
     * @param int $datescount - this determines the rowspan. Count the number of session dates to get this figure.
     * @return html_table_cell
     * @throws coding_exception
     */
    private function table_cell_event_status(stdClass $session, render_session_list_config $config, int $signupcount, int $datescount): html_table_cell {
        global $OUTPUT;

        [ $event_status, $booking_status, $user_status ] = seminar_event_helper::event_status($session, $signupcount, false);
        $class = 'mod_facetoface__list-item ' . self::SESSION_LIST_CSS.'event-status__';
        $statuses = html_writer::tag('li', clean_string($event_status), [ 'class' => $class.'event']);
        if ($booking_status !== '') {
            $statuses .= html_writer::tag('li', clean_string($booking_status), [ 'class' => $class.'booking']);
        }
        if ($user_status !== '') {
            $user_status = get_string('eventstatususerbooking', 'mod_facetoface', $user_status);
            $statuses .= html_writer::tag('li', clean_string($user_status), [ 'class' => $class.'user']);
        }

        if ($config->viewattendees) {
            $seminarevent = (new seminar_event())->from_record_with_dates($session, false);
            $status = $seminarevent->get_attendance_taking_status($config->eventattendance, 0, true, true);
            // Show the event attendance link only when it is open.
            if ($status === attendance_taking_status::OPEN || $status === attendance_taking_status::ALLSAVED) {
                $url = new moodle_url('/mod/facetoface/attendees/takeattendance.php', ['s' => $session->id]);
                if ($status == attendance_taking_status::ALLSAVED) {
                    $html = $OUTPUT->flex_icon('check-circle-success') . get_string('eventattendancetracking:saved', 'mod_facetoface');
                    $state = 'saved';
                } else {
                    $html = get_string('eventattendancetracking:open', 'mod_facetoface');
                    $state = 'open';
                }
                $attendance_status = html_writer::link($url, $html, ['class' => self::SESSION_LIST_CSS."attendance--{$state}__link"]);
                $statuses .= html_writer::tag('li', $attendance_status, [ 'class' => $class.'attendance']);
            }
        }

        $html = html_writer::tag('ul', $statuses, [ 'class' => 'mod_facetoface__list-items' ]);
        return static::create_table_cell($html, $datescount, 'event-status');
    }

    /**
     * Create a table cell containing a sessions capacity or seats remaining.
     *
     * If the user has viewattendees permissions, this will show capacity.
     * If not, then this will show seats remanining.
     *
     * @param seminar_event $seminarevent - An event, not session date
     * @param render_session_list_config $config
     * @param int $signupcount - number currently signed up to this session.
     * @param int $datescount - this determines the rowspan. Count the number of session dates to get this figure.
     * @return html_table_cell
     * @throws coding_exception
     */
    private function table_cell_capacity(seminar_event $seminarevent, render_session_list_config $config, int $signupcount, int $datescount = 0): html_table_cell {
        $class = 'mod_facetoface__capacity__';
        $includedeleted = has_capability('totara/core:seedeletedusers', $config->context);
        $helper = new attendees_helper($seminarevent);

        if ($config->viewattendees) {
            if ($seminarevent->is_sessions()) {
                $currenthtml = html_writer::span($signupcount, $class.'current');
                $maximumhtml = html_writer::span($seminarevent->get_capacity(), $class.'maximum');
                $a = array('current' => $currenthtml, 'maximum' => $maximumhtml);
                $stats = [get_string('capacitycurrentofmaximum', 'mod_facetoface', $a)];
                if ($signupcount > $seminarevent->get_capacity()) {
                    $stats[] = get_string('capacityoverbooked', 'mod_facetoface');
                }

                $waitlisted = $helper->count_attendees_with_codes([waitlisted::get_code()], $includedeleted, true);
                if ($waitlisted > 0) {
                    $stats[] = get_string('status_capacity_waitlisted', 'mod_facetoface', $waitlisted);
                }
                $stats = mod_facetoface_renderer::render_unordered_list($stats);
            } else {
                // Since within the event that has no sesison date, and user that are in wait-list could be moved to
                // attendees, and it caused the number of wait-listed user being calculated and rendered wrong.
                // If there is any user that confirm as booked, then it should display the number of booked user within current
                $currentbookeduser = $helper->count_attendees_with_codes([booked::get_code()], $includedeleted);
                $currenthtml = html_writer::span($currentbookeduser, $class.'current');
                $maximumhtml = html_writer::span($seminarevent->get_capacity(), $class.'maximum');
                $a = array('current' => $currenthtml, 'maximum' => $maximumhtml);
                $stats = [get_string('capacitycurrentofmaximum', 'mod_facetoface', $a)];
                if ($currentbookeduser > $seminarevent->get_capacity()) {
                    $stats[] = get_string('capacityoverbooked', 'mod_facetoface');
                }
                if ($signupcount > 0) {
                    $stats[] = get_string('status_capacity_waitlisted', 'mod_facetoface', $signupcount);
                }
                $stats = mod_facetoface_renderer::render_unordered_list($stats);
            }
        } else {
            $stats = max(0, $seminarevent->get_capacity() - $signupcount);
        }

        return static::create_table_cell($stats, $datescount, 'capacity');
    }

    /**
     * Creates a table cell containing the registration period, if any, for a session.
     *
     * @param seminar_event $seminarevent
     * @param render_session_list_config $config
     * @param int $datescount - determines the number for the rowspan.
     * @return html_table_cell
     */
    private function table_cell_signup_period(seminar_event $seminarevent, render_session_list_config $config, int $datescount = 0): html_table_cell {
        $registrationstring = session_time::to_html($seminarevent->get_registrationtimestart(), $seminarevent->get_registrationtimefinish(), 99, $config->displaytimezones);
        return static::create_table_cell($registrationstring, $datescount, 'registrationperiod');
    }

    /**
     * "Session times" table cell.
     *
     * @param seminar_session $date
     * @param render_session_list_config $config
     * @return html_table_cell
     */
    private function table_cell_session_time(seminar_session $date, render_session_list_config $config): html_table_cell {
        $html = session_time::to_html($date->get_timestart(), $date->get_timefinish(), $date->get_sessiontimezone(), $config->displaytimezones);
        return self::create_table_cell($html, 1, 'session-time');
    }

    /**
     * "Rooms" table cell.
     *
     * @param seminar_event $seminarevent
     * @param seminar_session $date
     * @param render_session_list_config $config
     * @return html_table_cell
     */
    private function table_cell_rooms(seminar_event $seminarevent, seminar_session $date, render_session_list_config $config): html_table_cell {
        $renderer = $this->get_renderer();
        $html = $renderer->render_room_list_links($seminarevent, $date, $config->currenturl, $config->currenttime);
        return self::create_table_cell($html, 1, 'room');
    }

    /**
     * "Facilitators" table cell.
     *
     * @param seminar_event $seminarevent
     * @param seminar_session $date
     * @param render_session_list_config $config
     * @return html_table_cell
     */
    private function table_cell_facilitators(seminar_event $seminarevent, seminar_session $date, render_session_list_config $config): html_table_cell {
        $renderer = $this->get_renderer();
        $html = $renderer->render_facilitator_list_links($date, $config->currenturl);
        return self::create_table_cell($html, 1, 'facilitator');
    }

    /**
     * Create a table cell containing the status of a session.
     *
     * @param seminar_event $seminarevent
     * @param seminar_session|null $date
     * @param render_session_list_config $config
     * @param boolean $attendancetracking
     * @return html_table_cell
     */
    private function table_cell_session_status(seminar_event $seminarevent, ?seminar_session $date, render_session_list_config $config, bool $attendancetracking): html_table_cell {
        $status = seminar_session_helper::get_status_from($seminarevent, $date, $config->currenttime);
        if ($status !== '') {
            $contents = [$status];
            if ($attendancetracking && $config->sessionattendance && $config->viewattendees) {
                [$state, $icon, $text, $url] = seminar_session_helper::get_attendance_taking_status($seminarevent, $date, $config->sessionattendance, $config->currenttime);
                if ($state === 'open' || $state === 'saved') {
                    $cell = (new attendance_tracking_table_cell())
                        ->set_state($state)
                        ->set_icon($icon)
                        ->set_text_or_link($text, $url);
                    $template = $cell->export_for_template($this);
                    $contents[] = $template->content;
                }
            }
            // Let's manually combine two cells, shall we?
            $status = mod_facetoface_renderer::render_unordered_list($contents);
        }
        return static::create_table_cell($status, 1, 'session-status');
    }

    /**
     * Creates a table cell for the actions available for a session.
     *
     * @param seminar_event $seminarevent
     * @param render_session_list_config $config
     * @param int $datescount - determines the number for the rowspan.
     * @return html_table_cell
     */
    private function table_cell_actions(seminar_event $seminarevent, render_session_list_config $config, int $datescount = 0): html_table_cell {
        $params = ['s' => $seminarevent->get_id()];

        $builder = seminarevent_dashboard_action::builder();

        $renderer = $this->get_renderer();
        $eventpagelink = $renderer->get_event_page_link($seminarevent);
        if ($eventpagelink !== null) {
            $eventpagelink->params($params);
            $builder->set_button(get_string('gotosession', 'mod_facetoface'), $eventpagelink);
        }

        $builder->set_menu_button(get_string('moreactions', 'mod_facetoface'), new \core\output\flex_icon('mod_facetoface|moreactions'));

        // Can view attendees.
        if ($config->viewattendees) {
            $url = '/mod/facetoface/attendees/event.php';
            $builder->add_menu(get_string('eventdetails', 'mod_facetoface'), new moodle_url($url, $params));
            if ($seminarevent->get_cancelledstatus()) {
                // Go to the cancellations tab.
                $url = '/mod/facetoface/attendees/cancellations.php';
            } else {
                $url = '/mod/facetoface/attendees/view.php';
            }
            $builder->add_menu(get_string('attendees', 'mod_facetoface'), new moodle_url($url, $params));
        }

        // Can edit sessions.
        if ($config->editevents) {
            $params['backtoallsessions'] = (int)(bool)$config->returntoallsessions;
            if ($config->viewattendees) {
                $builder->add_menu_separator();
            }
            if (!$seminarevent->get_cancelledstatus()) {
                $builder->add_menu(get_string('editsession', 'mod_facetoface'), new moodle_url('/mod/facetoface/events/edit.php', $params));
                if (!$seminarevent->is_first_started($config->currenttime)) {
                    $builder->add_menu(get_string('cancelsession', 'mod_facetoface'), new moodle_url('/mod/facetoface/events/cancel.php', $params));
                }
            }
            $builder->add_menu(get_string('copysession', 'mod_facetoface'), new moodle_url('/mod/facetoface/events/edit.php', $params + ['c' => 1]));
            $builder->add_menu(get_string('deletesession', 'mod_facetoface'), new moodle_url('/mod/facetoface/events/delete.php', $params));
        }

        $html = $renderer->render($builder->build());
        return static::create_table_cell($html, $datescount, 'actions');
    }

    /**
     * Drop empty columns if requested. Then remove all placeholders.
     *
     * @param render_session_list_config $config
     * @return bool
     */
    private function cleanup_columns(render_session_list_config $config): bool {
        $cleanup = false;
        if ($config->dropemptycolumns) {
            // Drop a column if all cells are empty.
            $rowcount = count($this->data);
            $colcount = count($this->head);
            for ($ci = $colcount - 1; $ci >= 0; $ci--) {
                $ri = $rowcount - 1;
                while ($ri >= 0 && empty_table_cell::is_empty($this->data[$ri]->cells[$ci])) {
                    $ri--;
                }
                if ($ri < 0) {
                    array_splice($this->head, $ci, 1);
                    for ($ri = 0; $ri < $rowcount; $ri++) {
                        array_splice($this->data[$ri]->cells, $ci, 1);
                    }
                    $cleanup = true;
                }
            }
        }
        // Remove placeholders.
        for ($ri = count($this->data) - 1; $ri >= 0; $ri--) {
            $this->data[$ri]->cells = array_filter($this->data[$ri]->cells, function ($cell) {
                return !empty_table_cell::is_placeholder($cell);
            });
        }
        return $cleanup;
    }
}
