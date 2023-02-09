<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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

use core\output\flex_icon;
use totara_core\virtualmeeting\virtual_meeting as vm_model;

use mod_facetoface\asset;
use mod_facetoface\asset_list;
use mod_facetoface\attendance_taking_status;
use mod_facetoface\attendees_helper;
use mod_facetoface\customfield_area\facetofaceasset;
use mod_facetoface\customfield_area\facetofacefacilitator;
use mod_facetoface\customfield_area\facetofaceroom;
use mod_facetoface\event_time;
use mod_facetoface\render_event_info_option;
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
use mod_facetoface\user_helper;
use mod_facetoface\internal\session_data;

use mod_facetoface\dashboard\filter_list;
use mod_facetoface\dashboard\render_session_list_config;
use mod_facetoface\dashboard\render_session_option;
use mod_facetoface\dashboard\filters\event_time_filter;
use mod_facetoface\dashboard\filters\room_filter;
use mod_facetoface\facilitator;
use mod_facetoface\output\session_time;
use mod_facetoface\output\show_previous_events;

use mod_facetoface\query\event\query;
use mod_facetoface\query\event\filter\event_times_filter as query_event_times_filter;
use mod_facetoface\query\event\sortorder\future_sortorder;
use mod_facetoface\query\event\sortorder\past_sortorder;
use mod_facetoface\facilitator_user;
use mod_facetoface\output\event_page_navigation;
use mod_facetoface\output\seminarevent_actionbar;
use mod_facetoface\output\seminarevent_detail;
use mod_facetoface\output\seminarevent_detail_section;
use mod_facetoface\output\seminarevent_detail_session_list;
use mod_facetoface\output\seminarevent_information;
use mod_facetoface\output\seminarevent_signup_card;
use mod_facetoface\output\session_list_table;
use mod_facetoface\room_dates_virtualmeeting;
use mod_facetoface\seminar_attachment_item;
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

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/classes/output/seminarevent_actionbar.php');
require_once(__DIR__.'/classes/output/attendance_tracking_table_cell.php');
require_once(__DIR__.'/classes/internal/renderer_deprecated.php');

/**
 * mod_facetoface renderer class.
 */
class mod_facetoface_renderer extends plugin_renderer_base {
    // Deprecated functions
    use mod_facetoface\internal\mod_facetoface_renderer_deprecated;

    /**
     * Query parameter names - deprecated.
     */
    /** @deprecated Totara 13 */
    const PARAM_FILTER_F2FID = 'f';
    /** @deprecated Totara 13 */
    const PARAM_FILTER_ROOMID = 'roomid';
    /** @deprecated Totara 13 */
    const PARAM_FILTER_EVENTTIME = 'eventtime';

    /**
     * Default sign-up link
     */
    const DEFAULT_SIGNUP_LINK = '/mod/facetoface/eventinfo.php';

    /**
     * This link will be changed if mod_facetoface\hook\alternative_signup_link hook is installed.
     * @var string
     */
    protected $signuplink = self::DEFAULT_SIGNUP_LINK;

    /** @var context|null */
    protected $context = null;

    /**
     * Outputs a table showing a list of sessions along with other information.
     *
     * @param (session_data|stdClass)[] $sessions
     * @param render_session_list_config $config
     * @param string|null $id
     * @return string
     */
    public function render_session_list_table(array $sessions, render_session_list_config $config, string $id = null): string {
        if (empty($sessions)) {
            // If there are no sessions, just return an empty string.
            return '';
        }

        $table = session_list_table::create($sessions, $config, $id);
        if ($table->is_empty()) {
            // There were sessions when we checked at the beginning, but they've been eliminated
            // for one reason or another, so just return an empty string.
            return '';
        }

        return $this->render_from_template($table->get_template(), $table->export_for_template($this));
    }

    /**
     * Main calendar hook function for rendering the f2f filter controls
     *
     * @return string html
     */
    public function calendar_filter_controls(): string {
        global $SESSION;

        // Custom fields.
        $fieldsall = \mod_facetoface\calendar::get_customfield_filters();
        $output = '';
        foreach ($fieldsall as $type => $fields) {
            foreach ($fields as $f) {
                $currentval = '';
                if (!empty($SESSION->calendarfacetofacefilter[$type][$f->shortname])) {
                    $currentval = $SESSION->calendarfacetofacefilter[$type][$f->shortname];
                }
                $output .= $this->custom_field_chooser($type, $f, $currentval);
            }
        }
        return $output;
    }

    /**
     * Generates a custom field select for a f2f custom field
     *
     * @param string $type Custom field set ("room", "sess", etc)
     * @param stdClass $field
     * @param string|null $currentvalue
     *
     * @return string html
     */
    public function custom_field_chooser(string $type, \stdClass $field, ?string $currentvalue): string {
        // Same $fieldname  must be used in lib.php facetoface_calendar_set_filter().
        $fieldname = "field_{$type}_{$field->shortname}";
        $stringsource = '';
        switch ($type) {
            case 'sess':
                $stringsource = 'customfieldsession';
                break;
            case 'room':
                $stringsource = 'customfieldroom';
                break;
            default:
                $stringsource = 'customfieldother';
        }

        $label = html_writer::tag('label', get_string($stringsource, 'facetoface', $field->fullname) . ':', ['for' => 'id_' . $fieldname]);

        $value = empty($currentvalue) ? '' : $currentvalue;
        $values = array();
        switch ($field->datatype) {
            case 'multiselect':
                $param1 = json_decode($field->param1, true);
                foreach ($param1 as $option) {
                    $values[] = $option['option'];
                }
                break;
            case 'menu':
                $values = explode("\n", $field->param1);
                break;
            case 'checkbox':
                $values = array(0 => get_string('no'), 1 => get_string('yes'));
                break;
            case 'datetime':
                $input = html_writer::empty_tag('input', array('type' => 'text', 'size' => 10, 'name' => $fieldname, 'value' => $value, 'id' => 'id_' . $fieldname));
                build_datepicker_js('#id_' . $fieldname);
                return html_writer::div($label . $input, 'mod_facetoface__calendarfilter');
                break;
            case 'location':
            case 'textarea':
            case 'text':
                $input = html_writer::empty_tag('input', array('type' => 'text', 'size' => 15, 'name' => $fieldname, 'value' => $value, 'id' => 'id_' . $fieldname));
                return html_writer::div($label . $input, 'mod_facetoface__calendarfilter');
                break;
            default:
                return false;
        }

        // Build up dropdown list of values.
        $options = array();
        if (!empty($values)) {
            foreach ($values as $value) {
                $v = clean_param(trim($value), PARAM_TEXT);
                if (!empty($v)) {
                    $options[s($v)] = format_string($v);
                }
            }
        }

        $nothing = get_string('all');
        $nothingvalue = 'all';

        $currentvalue = empty($currentvalue) ? $nothingvalue : $currentvalue;

        $dropdown = html_writer::select($options, $fieldname, $currentvalue, array($nothingvalue => $nothing), ['id' => 'id_' . $fieldname]);

        return html_writer::div($label . $dropdown, 'mod_facetoface__calendarfilter');

    }

    /**
     * @param context|null $context
     * @return void
     */
    public function setcontext(?context $context): void {
        $this->context = $context;
    }

    /**
     * @return context
     */
    public function getcontext(): \context {
        $context = $this->context;
        if (null == $context) {
            // Probably context is not being set here yet, therefore, we should use the one provided by $PAGE
            $context = $this->page->context;
        }
        return $context;
    }

    /**
     * Generate the multiselect inputs + add/remove buttons to control allocating / deallocating users
     * for this session
     *
     * @param stdClass $team containing the lists of users who can be allocated / deallocated
     * @param stdClass $session
     * @param array $reserveinfo details of the number of allocations allowed / left
     * @return string HTML fragment to be output
     */
    public function session_user_selector(\stdClass $team, \stdClass $session, array $reserveinfo): string {
        global $PAGE;

        $extrafields = get_extra_user_fields($PAGE->context);

        $output = html_writer::start_tag('div', array('class' => 'row-fluid user-multiselect'));

        // Current allocations.
        $output .= html_writer::start_tag('div', array('class' => 'span5'));
        $info = (object)array(
            'allocated' => $reserveinfo['allocated'][$session->id],
            'max' => $reserveinfo['maxallocate'][$session->id],
        );
        $heading = get_string('currentallocations', 'mod_facetoface', $info);
        $output .= html_writer::tag('label', $heading, array('for' => 'deallocation'));
        $selected = optional_param_array('deallocation', array(), PARAM_INT);

        $opts = '';
        $opts .= html_writer::start_tag('optgroup', array('label' => get_string('thissession', 'mod_facetoface')));
        if (empty($team->current)) {
            $opts .= html_writer::tag('option', get_string('none'), array('value' => null, 'disabled' => 'disabled'));
        } else {
            foreach ($team->current as $user) {
                $name = mod_facetoface\attendees_list_helper::output_user_for_selection($user, $extrafields, true);
                $attr = array('value' => $user->id);
                if (in_array($user->id, $selected)) {
                    $attr['selected'] = 'selected';
                }
                if (!empty($user->cannotremove)) {
                    $attr['disabled'] = 'disabled';
                    $name .= ' (' . get_string($user->cannotremove, 'mod_facetoface') . ')';
                }
                $opts .= html_writer::tag('option', $name, $attr) . "\n";
            }
        }
        $opts .= html_writer::end_tag('optgroup');
        if (!empty($team->othersession)) {
            $opts .= html_writer::start_tag('optgroup', array('label' => get_string('othersession', 'mod_facetoface')));
            foreach ($team->othersession as $user) {
                $name = mod_facetoface\attendees_list_helper::output_user_for_selection($user, $extrafields, true);
                $attr = array('value' => $user->id, 'disabled' => 'disabled');
                if (!empty($user->cannotremove)) {
                    $name .= ' (' . get_string($user->cannotremove, 'mod_facetoface') . ')';
                }
                $opts .= html_writer::tag('option', $name, $attr) . "\n";
            }
        }
        $output .= html_writer::tag('select', $opts, array('name' => 'deallocation[]', 'multiple' => 'multiple',
            'id' => 'deallocation', 'size' => 20));
        $output .= html_writer::end_tag('div');

        // Buttons.
        $output .= html_writer::start_tag('div', array('class' => 'span2 controls'));
        $addlabel = $this->output->larrow() . ' ' . get_string('add');
        $output .= html_writer::empty_tag('input', array('name' => 'add', 'id' => 'add', 'type' => 'submit',
            'value' => $addlabel, 'title' => get_string('add')));
        $removelabel = get_string('remove') . ' ' . $this->output->rarrow();
        $output .= html_writer::empty_tag('input', array('name' => 'remove', 'id' => 'remove', 'type' => 'submit',
            'value' => $removelabel, 'title' => get_string('remove')));
        $output .= html_writer::end_tag('div');

        // Potential allocations.
        $output .= html_writer::start_tag('div', array('class' => 'span5'));
        $output .= html_writer::tag('label', get_string('potentialallocations', 'mod_facetoface',
            $reserveinfo['allocate'][$session->id]),
            array('for' => 'allocation'));

        $selected = optional_param_array('allocation', array(), PARAM_INT);
        $optspotential = array();
        foreach ($team->potential as $potential) {
            $optspotential[$potential->id] = mod_facetoface\attendees_list_helper::output_user_for_selection($potential, $extrafields, true);
        }
        $attr = array('multiple' => 'multiple', 'id' => 'allocation', 'size' => 20);
        if ($reserveinfo['allocate'][$session->id] == 0) {
            $attr['disabled'] = 'disabled';
        }
        $output .= html_writer::select($optspotential, 'allocation[]', $selected, null, $attr);
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        return $output;
    }

    /**
     * Render strings as an unordered list HTML i.e. <ul> and <li>.
     * - if the list is empty the output will be '<div class="...emptyclass">emptytext</div>'
     * - if the list is not empty the output will be '<ul class="...outerclass"><li class="...innerclass">...</li>...</ul>'
     *
     * @param string[] $listitems
     * @param string $emptytext
     * @param string $class
     * @param string $outerclass
     * @param string $innerclass
     * @param string $emptyclass
     * @return string
     */
    public static function render_unordered_list(array $listitems, string $emptytext = '', string $class = 'mod_facetoface', string $outerclass = '', string $innerclass = '', string $emptyclass = 'empty'): string {
        if ($outerclass === '' && $innerclass === '') {
            $outerclass = 'list-items';
            $innerclass = 'list-item';
        } else if ($outerclass !== '' && $innerclass === '') {
            throw new coding_exception('$innerclass must be specified.');
        } else if ($outerclass === '' && $innerclass !== '') {
            throw new coding_exception('$outerclass must be specified.');
        }
        if (empty($listitems)) {
            return \html_writer::tag('div', $emptytext, ['class' => "{$class}__{$outerclass}--{$emptyclass}"]);
        }
        $listhtml = '';
        foreach ($listitems as $listitem) {
            $listhtml .= \html_writer::tag('li', $listitem, ['class' => "{$class}__{$innerclass}"]);
        }
        return \html_writer::tag('ul', $listhtml, ['class' => "{$class}__{$outerclass}"]);
    }

    /**
     * Format the dates for the given session, when listing the other bookings made by a given manager
     * in a particular face to face instance.
     *
     * @param seminar_event $seminarevent
     * @param boolean|null $displaytimezones
     * @return string
     */
    protected function format_booking_dates(seminar_event $seminarevent, bool $displaytimezones = null): string {
        $formatteddates = [];
        $sessionlist = clone $seminarevent->get_sessions();
        foreach ($sessionlist->sort('timestart') as $date) {
            /** @var seminar_session $date */
            $formatteddates[] = session_time::to_html($date->get_timestart(), $date->get_timefinish(), $date->get_sessiontimezone(), $displaytimezones);
        }
        return static::render_unordered_list(
            $formatteddates,
            get_string('wait-listed', 'mod_facetoface'),
            'mod_facetoface__booking',
            'dates',
            'date',
            'waitlisted'
        );
    }

    /**
     * Output the given list of reservations/allocations that this manager has made
     * in other sessions in this facetoface.
     *
     * @param stdClass[] $bookings
     * @param stdClass $manager
     * @return string HTML fragment to output the list
     */
    public function other_reservations(array $bookings, \stdClass $manager): string {
        global $USER;

        if (!$bookings) {
            return '';
        }

        // Gather the session data together.
        $sessions = array();
        foreach ($bookings as $booking) {
            if (!isset($sessions[$booking->sessionid])) {
                $seminarevent = new \mod_facetoface\seminar_event($booking->sessionid);
                $sessions[$booking->sessionid] = (object)array(
                    'reservations' => 0,
                    'bookings' => array(),
                    'dates' => $this->format_booking_dates($seminarevent),
                );
            }
            if ($booking->userid) {
                $sessions[$booking->sessionid]->bookings[$booking->userid] = fullname($booking);
            } else {
                $sessions[$booking->sessionid]->reservations++;
            }
        }

        // Output the details as a table.
        if ($manager->id == $USER->id) {
            $bookingstr = get_string('yourbookings', 'facetoface');
        } else {
            $bookingstr = get_string('managerbookings', 'facetoface', fullname($manager));
        }
        $table = new html_table();
        $table->head = array(
            get_string('sessiondatetime', 'facetoface'),
            $bookingstr,
        );
        $table->attributes = array('class' => 'generaltable managerbookings');

        foreach ($sessions as $session) {
            $details = array();
            if ($session->reservations) {
                $details[] = get_string('reservations', 'mod_facetoface', $session->reservations);
            }
            $details += $session->bookings;
            $detailshtml = static::render_unordered_list(
                $details,
                '',
                'mod_facetoface__booking',
                'users',
                'user',
                ''
            );
            $row = new html_table_row(array($session->dates, $detailshtml));
            $table->data[] = $row;
        }

        $heading = $this->output->heading(get_string('existingbookings', 'mod_facetoface'), 3);

        return $heading . html_writer::table($table);
    }

    /**
     * Manage customfield tabs displayed in customfield/index.php
     *
     * @param string $currenttab
     * @return string tabs
     */
    public function customfield_management_tabs(string $currenttab = 'facetofacesession'): string {
        $tabs = array();
        $row = array();
        $activated = array();
        $inactive = array();

        $row[] = new tabobject('facetofacesession', new moodle_url('/mod/facetoface/customfields.php', array('prefix' => 'facetofacesession')),
            get_string('sessioncustomfieldtab', 'facetoface'));
        $row[] = new tabobject('facetofaceasset', new moodle_url('/mod/facetoface/customfields.php', array('prefix' => 'facetofaceasset')),
            get_string('assetcustomfieldtab', 'facetoface'));
        $row[] = new tabobject('facetofacefacilitator', new moodle_url('/mod/facetoface/customfields.php', array('prefix' => 'facetofacefacilitator')),
            get_string('facilitatorcustomfieldtab', 'mod_facetoface'));
        $row[] = new tabobject('facetofaceroom', new moodle_url('/mod/facetoface/customfields.php', array('prefix' => 'facetofaceroom')),
            get_string('roomcustomfieldtab', 'facetoface'));
        $row[] = new tabobject('facetofacesignup', new moodle_url('/mod/facetoface/customfields.php', array('prefix' => 'facetofacesignup')),
            get_string('signupcustomfieldtab', 'facetoface'));
        $row[] = new tabobject('facetofacecancellation', new moodle_url('/mod/facetoface/customfields.php', array('prefix' => 'facetofacecancellation')),
            get_string('cancellationcustomfieldtab', 'facetoface'));
        $row[] = new tabobject('facetofacesessioncancel', new moodle_url('/mod/facetoface/customfields.php', array('prefix' => 'facetofacesessioncancel')),
            get_string('sessioncancellationcustomfieldtab', 'facetoface'));

        $tabs[] = $row;
        $activated[] = $currenttab;

        return print_tabs($tabs, $currenttab, $inactive, $activated, true);
    }

    /**
     * Manage report tabs
     *
     * @param string $currenttab
     * @return string tabs
     */
    public function reports_management_tabs(string $currenttab = 'facetofaceeventreport'): string {

        $tabs = array();
        $row = array();
        $activated = array();
        $inactive = array();

        $row[] = new tabobject('facetofaceeventreport', new moodle_url('/mod/facetoface/reports/events.php'),
            get_string('eventsview', 'mod_facetoface'));

        $row[] = new tabobject('facetofacesessionreport', new moodle_url('/mod/facetoface/reports/sessions.php'),
            get_string('sessionsview', 'mod_facetoface'));

        $tabs[] = $row;
        $activated[] = $currenttab;

        return print_tabs($tabs, $currenttab, $inactive, $activated, true);
    }

    /**
     * Show a list of all reservations for a session and allow them to be removed.
     *
     * @param stdClass[] $reservations Data about all the reservations
     * @param boolean $backtoallsessions
     * @param boolean $backtoeventinfo
     * @return string
     */
    public function print_reservation_management_table(array $reservations, bool $backtoallsessions = true, bool $backtoeventinfo = false): string {

        $out = '';

        if (count($reservations) > 0) {
            $table = new html_table();
            $table->head = array(
                get_string('managername', 'mod_facetoface'),
                get_string('spacesreserved', 'mod_facetoface'),
                get_string('actions'));

            $table->attributes = array('class' => 'generaltable managereservations fullwidth');

            $strdelete = get_string('delete');

            foreach ($reservations as $reservation) {
                $managername = fullname($reservation);

                $managerlink = html_writer::link(new moodle_url('/user/profile.php',
                    array('id' => $reservation->bookedby)), $managername);

                $deleteurl = new moodle_url('/mod/facetoface/reservations/delete.php', ['s' => $reservation->sessionid,
                    'backtoeventinfo' => (int)$backtoeventinfo,
                    'managerid' => $reservation->bookedby, 'sesskey' => sesskey()]);
                $buttons = $this->action_icon($deleteurl, new pix_icon('t/delete', $strdelete));

                $row = new html_table_row(array($managerlink, $reservation->reservedspaces, $buttons));
                $table->data[] = $row;
            }

            $out .= html_writer::table($table);

        } else {
            $out .= html_writer::tag('p', get_string('noreservationsforsession', 'mod_facetoface'));
        }

        return $out;
    }

    /**
     * Render table of users used in add attendees list
     * @param array $users
     * @param \mod_facetoface\bulk_list $list User list Needed only with job assignments
     * @param int $sessionid Needed only with job assignments
     * @param int $jaselector Job assignements selection: 0 - no, 1 - optional, 2 - required
     * @return string
     */
    public function print_userlist_table(array $users, \mod_facetoface\bulk_list $list = null, int $sessionid = 0, int $jaselector = 0): string {
        global $OUTPUT, $PAGE;

        $out = '';
        $showcfdatawarning = false;
        if (count($users) > 0) {

            $showemail = in_array('email', get_extra_user_fields($PAGE->context));
            $showidnumber = in_array('idnumber', get_extra_user_fields($PAGE->context));

            $table = new html_table();
            $table->head = [get_string('name')];
            if ($showemail) {
                $table->head[] = get_string('email');
            }
            if ($showidnumber) {
                $table->head[] = get_string('idnumber');
            }
            if ($jaselector) {
                $jacolumnheader = get_string('jobassignment', 'facetoface');
                if ($jaselector == 2) {
                    // Taken from lib/formslib.php.
                    $jacolumnheader .= $OUTPUT->flex_icon('required', array(
                        'classes' => 'form-required',
                        'alt' => get_string('requiredelement', 'form'),
                        'title' => get_string('requiredelement', 'form')
                    ));
                }
                $table->head[] = $jacolumnheader;
            }

            if (isset(current($users)->cntcfdata)) {
                $table->head[] = get_string('signupdata', 'facetoface');
            }

            $table->attributes = array('class' => 'generaltable userstoadd fullwidth');

            foreach ($users as $user) {
                $row = [fullname($user)];
                if ($showemail) {
                    $row[] = clean_string($user->email);
                }
                if ($showidnumber) {
                    $row[] = s($user->idnumber);
                }
                if ($jaselector) {
                    $janame = '';
                    // Get previously stored jobassignmentid from user list. @see attendess/select_job_assignment.php.
                    $userdata = $list->get_user_data($user->id);
                    if (!empty($userdata['jobassignmentid'])) {
                        $jobassignment = \totara_job\job_assignment::get_with_id($userdata['jobassignmentid']);
                        $janame = $jobassignment->fullname;
                    }

                    $url = new moodle_url('/mod/facetoface/attendees/ajax/select_job_assignment.php',
                            array('id' => $user->id, 's' => $sessionid, 'listid' => $list->get_list_id()));

                    $icon = $OUTPUT->action_icon($url, new pix_icon('t/edit', get_string('edit')), null,
                        array('class' => 'action-icon attendee-edit-job-assignment pull-right'));
                    $jobassign = html_writer::span($janame, 'jobassign' . $user->id, array('id' => 'jobassign' . $user->id));
                    $row[] = $icon . $jobassign;
                }

                if (isset($user->cntcfdata)) {
                    if ($user->cntcfdata) {
                        $showcfdatawarning = true;
                        $row[] = html_writer::tag('strong', get_string('yes'));
                    } else {
                        $row[] = get_string('no');
                    }
                }
                $row = new html_table_row($row);
                $table->data[] = $row;
            }

            if ($showcfdatawarning) {
                $out .= $OUTPUT->notification(get_string('removecfdatawarning', 'facetoface'), 'notifymessage');
            }
            $out .= $OUTPUT->render($table);
        }
        return $out;
    }

    /**
     * Displays the dismiss action icon the mismatched approval types notice.
     *
     * @param int $f2fid The id of the facetoface currently being viewed
     * @return void
     */
    public function selfapproval_notice(int $f2fid): void {
        global $OUTPUT;

        $approvalcount = \mod_facetoface\approver::count_selfapproval($f2fid);
        if ($approvalcount > 1) {
            $attributes = array('class' => 'smallicon dismissicon');
            $dismissstr = get_string('dismiss', 'mod_facetoface');
            $dismissurl = new \moodle_url('/mod/facetoface/approver/dismiss.php', array('fid' => $f2fid, 'sesskey' => sesskey()));
            $icon = $this->output->action_icon($dismissurl, new pix_icon('/t/delete', $dismissstr), null, $attributes);
            $message = get_string('warning:mixedapprovaltypes', 'mod_facetoface') . $icon;
            echo $OUTPUT->notification($message, 'notifynotice');
        }
    }

    /**
     * Render details of the room
     * @param room $room
     * @return string
     */
    public function render_room_details(room $room): string {
        return $this->render_attachment_details($room, facetofaceroom::class, 'room');
    }

    /**
     * Gets the HTML output of room details for the session.
     *
     * @param \mod_facetoface\seminar_session $date
     * @param \mod_facetoface\room $room - The room instance to get details for
     * @param string|null $backurl
     * @param bool $joinnow to display virtual room link if exists and time has come(15 min prior to the session start time)
     * @return string containing room details with relevant html tags.
     */
    public function get_session_room_details_html(seminar_session $date, room $room, string $backurl = null, bool $joinnow = false): string {
        global $CFG;

        $url = new moodle_url('/mod/facetoface/reports/rooms.php', array(
            'roomid' => $room->get_id(),
            'sdid' => $date->get_id(),
        ));

        $roomurl = $room->get_url();
        if (empty($roomurl)) {
            // This isn't a basic linked room, check if it's a virtual room.
            $vm = room_dates_virtualmeeting::load_by_session_room($date, $room, true);
            if ($vm !== null) {
                $model = $vm->get_virtualmeeting();
                if ($model !== null) {
                    try {
                        $roomurl = $model->get_join_url(true);
                    } catch (\Throwable $ex) {
                        if (!defined('BEHAT_SITE_RUNNING')) {
                            debugging('Cannot get the virtualmeeting URL: ' . $ex->getMessage(), DEBUG_DEVELOPER);
                        }
                        // Swallow any exception thrown by a plugin.
                        // Otherwise a user won't be able to see a course page or a seminar event dashboard page.
                        $joinnow = false;
                        $roomurl = '';
                    }
                }
            }
        }

        $roomhtml = '';
        if ($joinnow && !empty($roomurl)) {
            $roomlinklabel = get_string('roomjoinnowx', 'mod_facetoface', $room->get_name());
            $roomhtml .= '<br>' . html_writer::link(
                $roomurl,
                get_string('roomjoinnow', 'mod_facetoface'),
                ['class' => 'roomurl btn btn-primary btn-xs', 'role' => 'button', 'target' => '_blank', 'rel' => 'noopener noreferrer', 'aria-label' => $roomlinklabel, 'title' => $roomlinklabel]
            );
        }
        // Display room custom fields?
        if (!empty($CFG->facetoface_roomidentifier)) {
            $customfields = $room->get_customfield_array();
            $cfs = [];
            // Always display building if present.
            if (!empty($customfields[CUSTOMFIELD_BUILDING])) {
                $cfs[] = s($customfields[CUSTOMFIELD_BUILDING]);
            }
            // Also display location if configured to, and present.
            if ($CFG->facetoface_roomidentifier == \mod_facetoface\room::ROOM_IDENTIFIER_LOCATION && !empty($customfields[CUSTOMFIELD_LOCATION])) {
                $cfs[] = s(str_replace('<br />', ', ', $customfields[CUSTOMFIELD_LOCATION]));
            }
            if (!empty($cfs)) {
                $cf_glue = '<br>';
                $cf_class = 'mod_facetoface__sessionlist__roomdetails';
                $roomhtml .= html_writer::tag('span', implode($cf_glue, $cfs), ['class' => $cf_class]);
            }
        }

        return $this->render_details_inplace_html($room->get_name(), 'room', $url, $backurl, $roomhtml);
    }

    /**
     * Gets the HTML output for facilitator details.
     * @param \mod_facetoface\facilitator_user $facilitator - The facilitator instance to get details for
     * @param string|null $backurl
     * @return string containing facilitator details with relevant html tags.
     */
    public function get_facilitator_details_html(\mod_facetoface\facilitator_user $facilitator, string $backurl = null): string {
        $url = new moodle_url('/mod/facetoface/reports/facilitators.php', [
            'facilitatorid' => $facilitator->get_id(),
        ]);
        return $this->render_details_inplace_html($facilitator->get_name(), 'facilitator', $url, $backurl);
    }

    /**
     * Gets the HTML output for asset details.
     *
     * @param \mod_facetoface\asset $asset - The asset instance to get details for
     * @param string|null $backurl
     * @return string containing asset details with relevant html tags.
     */
    public function get_asset_details_html(\mod_facetoface\asset $asset, string $backurl = null): string {
        $url = new moodle_url('/mod/facetoface/reports/assets.php', array(
            'assetid' => $asset->get_id(),
        ));
        return $this->render_details_inplace_html($asset->get_name(), 'asset', $url, $backurl);
    }

    /**
     * Gets the HTML output of a details link opening in a popup window.
     *
     * @param string $name - The name of the asset without escaping.
     * @param string $class - The CSS class of the link.
     * @param moodle_url $popupurl
     * @param string|null $backurl
     * @param string $extrahtml HTML appended to the link
     * @return string containing asset details with relevant html tags.
     */
    public function render_details_popup_html(string $name, string $class, moodle_url $popupurl, string $backurl = null, string $extrahtml = ''): string {
        $popupurl = clone($popupurl);
        $popupurl->param('popup', 1);
        if (!empty($backurl)) {
            $popupurl->param('b', $backurl);
        }
        $action = new popup_action('click', $popupurl, 'popup', array('width' => 800, 'height' => 600));
        $link = $this->output->action_link($popupurl, s($name), $action);
        return html_writer::span($link . $extrahtml, 'mod_facetoface__' . $class . '__detail');
    }

    /**
     * Gets the HTML output of a details link.
     *
     * @param string $name - The name of the asset without escaping.
     * @param string $class - The CSS class of the link.
     * @param moodle_url $detailurl
     * @param string|null $backurl
     * @param string $extrahtml HTML appended to the link
     * @return string containing asset details with relevant html tags.
     */
    public function render_details_inplace_html(string $name, string $class, moodle_url $detailurl, string $backurl = null, string $extrahtml = ''): string {
        $detailurl = clone($detailurl);
        $detailurl->param('view', 1);
        if (!empty($backurl)) {
            $detailurl->param('b', (new moodle_url($backurl))->out_as_local_url(false));
        }
        $link = $this->output->action_link($detailurl, s($name));
        return html_writer::span($link . $extrahtml, 'mod_facetoface__' . $class . '__detail');
    }

    /**
     * Render asset meta data
     *
     * @param \mod_facetoface\asset $asset
     * @return string
     */
    public function render_asset_details(\mod_facetoface\asset $asset): string {
        return $this->render_attachment_details($asset, facetofaceasset::class, 'asset');
    }

    /**
     * Render facilitator meta data
     *
     * @param \mod_facetoface\facilitator $facilitator
     * @return string
     */
    public function render_facilitator_details(facilitator $facilitator): string {
        return $this->render_attachment_details($facilitator, facetofacefacilitator::class, facetofacefacilitator::get_area_name());
    }

    /**
     * Render the details of asset, room or facilitator.
     *
     * @param seminar_attachment_item $item
     * @param string $customfieldclass a fully qualified class name in the mod_facetoface\customfield_area namespace
     * @param string $descarea file area for the description
     * @return string
     * @throws coding_exception
     */
    protected function render_attachment_details(seminar_attachment_item $item, string $customfieldclass, string $descarea): string {
        if (strpos($customfieldclass, 'mod_facetoface\\customfield_area') !== 0) {
            throw new coding_exception('Invalid $customfieldclass passed');
        }

        $displayadmincontents = has_capability('mod/facetoface:addinstance', $this->getcontext());
        $builder = seminarevent_detail_section::builder();

        // Custom fields.
        $filearea = $customfieldclass::get_area_name();
        $tblprefix = $customfieldclass::get_prefix();
        $options = array('prefix' => $filearea, 'extended' => true);
        $cfdata = (object)[
            'id' => $item->get_id(),
            'fullname' => $item->get_name(),
            'custom' => $item->get_custom(),
        ];
        $fields = customfield_get_data($cfdata, $tblprefix, $filearea, true, $options);
        if (!empty($fields)) {
            foreach ($fields as $field => $value) {
                if ((string)$value !== '') {
                    $builder->add_detail_unsafe($field, $value);
                }
            }
        }

        // Capacity.
        if ($item instanceof room) {
            $builder->add_detail(get_string('capacity', 'mod_facetoface'), (string)$item->get_capacity());
        }

        if ($item instanceof facilitator) {
            if (!empty($item->get_userid())) {
                $facuser = new facilitator_user($item);
                $builder->add_detail_unsafe(get_string('facilitatorlinkeduser', 'mod_facetoface'), $facuser->get_fullname_link(true));
            }
        }

        if ($displayadmincontents) {
            // Allow scheduling conflicts.
            $builder->add_detail(get_string('allowassetconflicts', 'mod_facetoface'), $item->get_allowconflicts() ? get_string('yes') : get_string('no'));
        }

        // Description.
        if ($item->get_description() !== '') {
            $descriptionhtml = file_rewrite_pluginfile_urls(
                $item->get_description(),
                'pluginfile.php',
                \context_system::instance()->id,
                'mod_facetoface',
                $descarea,
                $item->get_id()
            );
            $descriptionhtml = format_text($descriptionhtml, FORMAT_HTML);
            $builder->set_summary($descriptionhtml);
        }

        if ($displayadmincontents) {
            // Created.
            $timecreated = $item->get_timecreated();
            $builder->add_detail_unsafe(get_string('created', 'mod_facetoface'), user_helper::get_timestamp_and_profile($timecreated, $item->get_usercreated()));

            // Modified.
            $timemodified = $item->get_timemodified();
            if (!empty($timemodified) && $timemodified != $timecreated) {
                $builder->add_detail_unsafe(get_string('modified'), user_helper::get_timestamp_and_profile($timemodified, $item->get_usermodified()));
            }
        }

        return $this->render($builder->build());
    }

    /**
     * Output for a removable approver in the facetoface mod_form.
     *
     * @param stdClass  $user           The user object for the approver being displayed
     * @param boolean   $activity       Whether the approver is activity level or site level
     * @return string                   The html output for the approver
     */
    public function display_approver(\stdClass $user, bool $activity = false) : string {

        $uniqueid = "facetoface_approver_{$user->id}";
        if ($activity) {
            $classname = 'activity_approver';
            $delete = $this->action_icon('', new pix_icon('/t/delete', get_string('remove')), null,
                array('class' => 'activity_approver_del', 'id' => $user->id));
            $content = get_string('approval_activityapprover', 'mod_facetoface', fullname($user)) . ' ' . $delete;
        } else {
            $classname = 'system_approver';
            $content = get_string('approval_siteapprover', 'mod_facetoface', fullname($user));
        }

        return html_writer::tag('div', $content, array('id' => $uniqueid, 'class' => $classname));
    }

    /**

     * Declare or withdraw interest html output button.
     *
     * @param \mod_facetoface\seminar $seminar
     * @param bool $return return the result or output
     * @param array $options associative array {disabled, title, etc.}
     * @return string
     */
    public function declare_interest(\mod_facetoface\seminar $seminar, bool $return = false, array $options = []): string {
        global $OUTPUT;

        $html = '';
        $interest = \mod_facetoface\interest::from_seminar($seminar);
        if ($interest->is_user_declared() || $interest->can_user_declare()) {
            if ($interest->is_user_declared()) {
                $strbutton = get_string('declareinterestwithdraw', 'mod_facetoface');
            } else {
                $strbutton = get_string('declareinterest', 'mod_facetoface');
            }
            $url = new moodle_url('/mod/facetoface/interest.php', array('f' => $seminar->get_id()));
            $html = $OUTPUT->single_button($url, $strbutton, 'get', $options);
        }
        if ($return) {
            return $html;
        } else {
            echo $html;
        }
    }

    /**
     * Action bar html output for seminar tab pages.
     *
     * @param string|\moodle_url $goback_url the URL for the 'View all events' button (a.k.a 'Go back' button)
     * @return string
     */
    public function render_action_bar_on_tabpage($goback_url): string {
        $actionbar = \mod_facetoface\output\seminarevent_actionbar::builder()
            ->set_align('near')
            ->set_class('attendance')
            ->add_commandlink(
                'goback',
                $goback_url,
                get_string('viewallsessions', 'mod_facetoface')
            );

        return $this->render($actionbar->build());
    }

    /**
     * Action bar html output for reservation pages.
     *
     * @param \moodle_url|null $goback_url the URL for the 'Go back' button
     * @param \moodle_url|null $viewall_url the URL for the 'View all events' button
     * @return string
     */
    public function render_action_bar_on_reservation_page(\moodle_url $goback_url = null, \moodle_url $viewall_url = null): string {
        $actionbar = seminarevent_actionbar::builder()
            ->set_align('near')
            ->set_class('reservation');

        if ($goback_url !== null) {
            $actionbar->add_commandlink('goback', $goback_url, get_string('gotosession', 'mod_facetoface'));
        }
        if ($viewall_url) {
            $actionbar->add_commandlink('viewallsessions', $viewall_url, get_string('viewallsessions', 'mod_facetoface'));
        }

        return $this->render($actionbar->build());
    }

    /**
     * Action bar html output.
     *
     * @param \mod_facetoface\seminar $seminar
     * @return string
     */
    public function render_action_bar(\mod_facetoface\seminar $seminar) : string {
        $id = html_writer::random_id('id-');

        $editevents = has_capability('mod/facetoface:editevents', $this->getcontext());

        if ($editevents) {
            $actionbar = \mod_facetoface\output\seminarevent_actionbar::builder($id)
                ->set_align('far')
                ->set_class('dashboard')
                ->add_commandlink(
                    'addevent',
                    new moodle_url(
                        'events/add.php',
                        array('f' => $seminar->get_id(), 'backtoallsessions' => 1)
                    ),
                    get_string('addsession', 'mod_facetoface')
                );

            return $this->render($actionbar->build());
        }

        return '';
    }

    /**
     * Edit event button action html output.
     * @param \mod_facetoface\seminar_event $seminarevent
     * @return string
     */
    public function render_editevent_button(seminar_event $seminarevent): string {

        $editevents = has_capability('mod/facetoface:editevents', $this->getcontext());

        if (!$editevents || !$seminarevent->exists()) {
            return '';
        }

        $url = new moodle_url('/mod/facetoface/events/edit.php', ['s' => $seminarevent->get_id(), 'backtoevent' => 1]);
        $attrs = ['role' => 'button', 'class' => 'btn btn-default'];
        $link = html_writer::link($url, get_string('editsession', 'mod_facetoface'), $attrs);
        $output = html_writer::div(
            $link,
            'mod_facetoface__action mod_facetoface__action-editevent mod_facetoface__action--far'
        );
        return $output;
    }

    /**
     * Filter bar html output.
     *
     * @param seminar $seminar
     * @param filter_list $filters
     * @param integer|stdClass|null $user
     * @return string
     */
    public function render_filter_bar(\mod_facetoface\seminar $seminar, filter_list $filters, $user = null) : string {
        $id = html_writer::random_id('id-');
        $filterbar = $filters->to_filterbar_builder($seminar, $id, $this->getcontext(), $user)
            ->set_icon(new \core\output\flex_icon('preferences'));

        // Show "Reset" link if any filters are set
        if (!$filters->are_default()) {
            $filterbar->add_link('Reset', '?f=' . $seminar->get_id());
        }

        return $this->render($filterbar->build());
    }

    /**
     * Attendees export html form.
     *
     * @param \mod_facetoface\seminar $seminar
     */
    public function attendees_export_form(\mod_facetoface\seminar $seminar): void {
        global $OUTPUT;

        if (has_all_capabilities(['mod/facetoface:exportattendance', 'mod/facetoface:viewattendees'], $this->context)) {
            echo \html_writer::start_tag('form', array('action' => 'export.php', 'method' => 'post'));
            echo \html_writer::start_tag('div') . \html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'f', 'value' => $seminar->get_id()));
            echo $OUTPUT->help_icon('exportattendance', 'mod_facetoface', true) . '&nbsp;';

            $formats = [
                '0' => get_string('format', 'mod_facetoface'),
                'excel' => get_string('excelformat', 'facetoface'),
                'ods' => get_string('odsformat', 'facetoface')
            ];
            echo \html_writer::select($formats, 'download', '0', '', ['aria-label' => get_string('exportformat', 'totara_core')]);

            echo \html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('exporttofile', 'facetoface')));
            echo \html_writer::end_tag('div') . \html_writer::end_tag('form');
        }
    }

    /**
     * Print the details of a seminar event
     *
     * @param \mod_facetoface\seminar_event $seminarevent  Record from facetoface_sessions
     * @param boolean $showcapacity   Show the capacity (true) or only the seats available (false)
     * @param boolean $calendaroutput Whether the output should be formatted for a calendar event
     * @param boolean $hidesignup     Hide any messages relating to signing up
     * @param string  $class          Custom css class for dl
     * @param int     $sessionid      Limit session list to a single session, useful for calendar events & notifications
     * @return string html markup
     */
    public function render_seminar_event(
        \mod_facetoface\seminar_event $seminarevent,
        bool $showcapacity,
        bool $calendaroutput = false,
        bool $hidesignup = false,
        string $class = 'mod_facetoface__event_details',
        int $sessionid = 0
    ): string {
        global $USER;

        if ($class != 'mod_facetoface__event_details') {
            debugging('The $class argument has been deprecated and always \'mod_facetoface__event_details\'. Please do not use it anymore.');
        }

        $option = (new render_event_info_option())
            ->set_displaycapacity($showcapacity)
            ->set_calendaroutput($calendaroutput)
            ->set_displaysignupinfo(!$hidesignup)
            ->set_displayassets(false)
            ->set_displayrooms(false)
            ->set_displayassetsinsessions(!$calendaroutput)
            ->set_singlesession($sessionid);

        $signup = signup::create($USER->id, $seminarevent);

        $data = ['id' => html_writer::random_id('f2f-eventinfo-'), 'nosidebar' => true, 'details' => $this->get_seminar_event_details_data($signup, $option)];
        return $this->render(new seminarevent_information($data));
    }

    /**
     * Render seminar event information.
     *
     * @param signup $signup
     * @param render_event_info_option $option
     * @param boolean $cancelsignup Set true to activate the "cancel sign-up" sidebar
     * @return string
     */
    public function render_seminar_event_information(signup $signup, render_event_info_option $option, bool $cancelsignup = false): string {
        $seminarevent = $signup->get_seminar_event();
        $seminar = $seminarevent->get_seminar();
        $cm = $seminar->get_coursemodule();

        $data = ['id' => html_writer::random_id('f2f-eventinfo-'), 'class' => 'eventinfo'];

        $data['navigation'] = event_page_navigation::create(
            $seminarevent,
            get_string('eventinfo:movetotop', 'mod_facetoface'), [
                [
                    'icon' => new flex_icon('arrow-left'),
                    'label' => get_string('eventinfo:allevents', 'mod_facetoface'),
                    'link' => $option->get_backurl()
                ],
                [
                    'label' => get_string('eventinfo:eventinfo', 'mod_facetoface'),
                    'link' => '#f2f-eventinfo',
                ],
                [
                    'label' => get_string('eventinfo:sessions', 'mod_facetoface'),
                    'link' => '#f2f-sessions',
                ],
            ]
        )->get_template_data();

        $currentstate = $signup->get_state();
        if ($seminarevent->get_cancelledstatus() || $seminarevent->is_over()) {
            // No sidebars for past or cancelled events.
            $data['sidebars'] = [];
            $data['nosidebar'] = true;
        } else if (signup_helper::is_booked($signup) || $currentstate->can_switch(user_cancelled::class)) {
            $data['sidebars'] = $this->get_sidebars_cancellation($signup, $option, $cancelsignup);
        } else if (signup_helper::can_signup($signup)) {
            $data['sidebars'] = [ $this->get_sidebar_signup($signup, $option) ];
        } else if ($currentstate instanceof not_set || $currentstate instanceof user_cancelled || $currentstate instanceof declined) {
            $data['sidebars'] = [ $this->get_sidebar_signup_failure($signup) ];
        } else {
            $data['sidebars'] = [];
            $data['nosidebar'] = true;
        }

        $manageractions = $this->manager_action_links($seminarevent, $option);
        if (!empty($manageractions)) {
            $data['management'] = ['actions' => $manageractions];
        }

        $data['heading'] = $option->get_heading();
        if ($seminar->get_intro() !== '') {
            $intro = \format_module_intro('facetoface', $seminar->get_properties(), $cm->id);
            $data['intro'] = $intro;
        }

        $data['details'] = $this->get_seminar_event_details_data($signup, $option);

        $builder = seminarevent_actionbar::builder()
            ->set_align('near')
            ->set_class('eventinfofooter')
            ->add_commandlink('goback', $option->get_backurl(), get_string('viewallsessions', 'mod_facetoface'));
        $data['footeraction'] = $builder->build()->get_template_data();

        $data['declareinterest'] = $this->declare_interest($seminar, true, ['class' => 'enrol_facetoface_declare_interest']);

        return $this->render(new seminarevent_information($data));
    }

    /**
     * Get the list of manager actions.
     *
     * @param seminar_event $seminarevent
     * @param render_event_info_option $option
     * @return string[]
     */
    protected function manager_action_links(seminar_event $seminarevent, render_event_info_option $option): array {
        $class = 'mod_facetoface__eventinfo__content__management';
        $params = [
            's' => $seminarevent->get_id(),
            'backtoallsessions' => (int)$option->get_backtoallsessions(),
            'backtoeventinfo' => (int)$option->get_backtoeventinfo()
        ];
        $sessiondata = \mod_facetoface\seminar_event_helper::get_sessiondata($seminarevent, null);
        if ($seminarevent->is_registration_open()) {
            $actionlinks = $this->create_reserve_links($sessiondata, null, null, $params, $class);
        } else {
            // Registration not open.
            $actionlinks = [];
        }
        $viewattendees = has_capability('mod/facetoface:viewattendees', $this->getcontext());
        if ($viewattendees) {
            unset($params['backtoeventinfo']); // Attendees page doesn't understand the backtoeventinfo parameter
            $viewurl = new moodle_url('/mod/facetoface/attendees/view.php', $params);
            $seeattendees = get_string('eventinfo:attendees', 'mod_facetoface');
            $actionlinks[] = html_writer::link($viewurl, $seeattendees, ['class' => $class . '__viewattendees']);
        }
        return $actionlinks;
    }

    /**
     * Create an array of reserve links.
     *
     * @param session_data $session - session and sessiondates
     * @param int|null $signupcount - number currently signed up to this session.
     * @param array|null $reserveinfo - if managereserve is set to true for the seminar, use reservations::can_reserve_or_allocate
     * to fill out this array.
     * @param array|null $params - URL query parameters passed to reserve links
     * @param string $class - the base CSS class of reserve links
     * @return array of html links with optional text
     * @throws coding_exception
     */
    protected function create_reserve_links(session_data $session, int $signupcount = null, array $reserveinfo = null, array $params = null, string $class = 'mod_facetoface__reserve'): array {
        $seminarevent = $session->to_seminar_event();
        if ($seminarevent->get_cancelledstatus()) {
            return [];
        }

        $currentime = time();
        if ($seminarevent->is_first_started($currentime) || $seminarevent->is_over($currentime)) {
            return [];
        }

        if ($signupcount === null) {
            $includedeleted = has_capability('totara/core:seedeletedusers', $this->getcontext());
            $signupcount = attendees_helper::count_signups($seminarevent, $includedeleted);
        }
        if ($reserveinfo === null) {
            $seminar = $seminarevent->get_seminar();
            if (!empty($seminar->get_managerreserve())) {
                $reserveinfo = reservations::can_reserve_or_allocate($seminar, [$session], $this->getcontext());
            } else {
                $reserveinfo = array();
            }
        }
        if ($params === null) {
            $params = [];
        }

        // Clean up $params
        $params['s'] = $seminarevent->get_id();
        unset($params['id']);
        unset($params['f']);
        if (!isset($params['backtoallsessions']) && !isset($params['backtoeventinfo'])) {
            $params['backtoallsessions'] = 1;
        }

        /**
         * Helper function to render a reserve link.
         *
         * @param string $baseurl
         * @param string $text
         * @param string $class
         * @param integer $capacity
         * @param integer $maxcapacity Set non-zero to display capacity info next to the link
         * @return string
         */
        $create_reserve_link = function (string $baseurl, string $text, string $class, int $capacity = 0, int $maxcapacity = 0) use ($params): string {
            $linkurl = new moodle_url($baseurl, $params);
            $link = html_writer::link($linkurl, $text, ['class' => $class]);
            if ($maxcapacity > 0) {
                // Add capacity info at the end of the link.
                $link = get_string('reservelinkformat', 'mod_facetoface', [
                    'link' => $link,
                    'capacity' => $capacity,
                    'maxcapacity' => $maxcapacity
                ]);
            }
            return $link;
        };

        $reservelinks = [];

        // Output links to reserve/allocate spaces.
        if (!empty($reserveinfo)) {
            // TODO: find out why the logic is different from reservations/allocate.php et al
            $sessreserveinfo = $reserveinfo;
            if (!$seminarevent->get_allowoverbook()) {
                $sessreserveinfo = \mod_facetoface\reservations::limit_info_to_capacity_left($seminarevent, $sessreserveinfo,
                    max(0, $seminarevent->get_capacity() - $signupcount));
            }
            $sessreserveinfo = \mod_facetoface\reservations::limit_info_by_session_date($seminarevent, $sessreserveinfo);
            if (!empty($sessreserveinfo['allocate']) && $sessreserveinfo['maxallocate'][$seminarevent->get_id()] > 0) {
                // Able to allocate and not used all allocations for other sessions.
                $reservelinks[] = $create_reserve_link(
                    '/mod/facetoface/reservations/allocate.php',
                    get_string('allocate', 'mod_facetoface'),
                    $class . '__allocate',
                    $sessreserveinfo['allocated'][$seminarevent->get_id()],
                    $sessreserveinfo['maxallocate'][$seminarevent->get_id()]
                );
            }
            if (!empty($sessreserveinfo['reserve']) && $sessreserveinfo['maxreserve'][$seminarevent->get_id()] > 0) {
                if (empty($sessreserveinfo['reservepastdeadline'])) {
                    $reservelinks[] = $create_reserve_link(
                        '/mod/facetoface/reservations/reserve.php',
                        get_string('reserve', 'mod_facetoface'),
                        $class . '__reserve',
                        $sessreserveinfo['reserved'][$seminarevent->get_id()],
                        $sessreserveinfo['maxreserve'][$seminarevent->get_id()]
                    );
                }
            } else if (!empty($sessreserveinfo['reserveother']) && empty($sessreserveinfo['reservepastdeadline'])) {
                $reservelinks[] = $create_reserve_link(
                    '/mod/facetoface/reservations/reserve.php',
                    get_string('reserveother', 'mod_facetoface'),
                    $class . '__reserve'
                );
            }
            if (has_capability('mod/facetoface:managereservations', $this->context)) {
                $reservelinks[] = $create_reserve_link(
                    '/mod/facetoface/reservations/manage.php',
                    get_string('managereservations', 'mod_facetoface'),
                    $class . '__managereservations'
                );
            }
        }

        return $reservelinks;
    }

    /**
     * Render the details of a session event
     *
     * @param signup $signup
     * @param render_event_info_option $option
     * @param integer|null $userid
     * @return array of template data of seminarevent_detail
     */
    protected function get_seminar_event_details_data(signup $signup, render_event_info_option $option): array {
        $sessiondata = seminar_event_helper::get_sessiondata($signup->get_seminar_event(), $signup);
        $details = [
            $this->get_seminar_event_details_eventinfo($sessiondata, $signup, $option),
            $this->get_seminar_event_details_sessions($sessiondata, $signup, $option)
        ];

        return array_map(
            function (seminarevent_detail $e) {
                return $e->get_template_data();
            },
            array_filter(
                $details,
                function (?seminarevent_detail $e) {
                    return $e !== null;
                }
            )
        );
    }

    /**
     * Render event summary in seminar event details.
     *
     * @param session_data $session
     * @param signup $signup
     * @param render_event_info_option $option
     * @return seminarevent_detail|null
     */
    protected function get_seminar_event_details_eventinfo(session_data $session, signup $signup, render_event_info_option $option): ?seminarevent_detail {
        global $USER;

        if (!$option->get_calendaroutput()) {
            $builder = seminarevent_detail::builder('eventinfo')
                ->set_title(get_string('eventinfo:eventinfo', 'mod_facetoface'))
                ->set_id('f2f-eventinfo')
                ->set_collapsible(true, html_writer::random_id('f2fsection'));
        } else {
            $builder = seminarevent_detail::builder('eventinfo')->set_id('f2f-eventinfo');
        }

        if ($option->get_displayediteventlink() && has_capability('mod/facetoface:editevents', $this->getcontext())) {
            $builder->set_actionbar(
                seminarevent_actionbar::builder()
                ->set_align('far')
                ->set_class('eventinfo')
                ->add_commandlink(
                    'editevent',
                    new moodle_url('/mod/facetoface/events/edit.php',
                    ['s' => $session->id]),
                    new flex_icon('edit', ['classes' => 'ft-size-400', 'alt' => get_string('editsession', 'mod_facetoface')])
                )
                ->build()
            );
        }

        $section = seminarevent_detail_section::builder();

        $seminarevent = (new seminar_event())->from_record_with_dates($session, false);
        $seminar = $seminarevent->get_seminar();
        $cm = $seminar->get_coursemodule();

        $includedeleted = has_capability('totara/core:seedeletedusers', $this->getcontext());
        $signupcount = attendees_helper::count_signups($seminarevent, $includedeleted);

        // Print customfields.
        $tblprefix = (bool)$seminarevent->get_cancelledstatus() ? 'facetoface_sessioncancel' : 'facetoface_session';
        $prefix    = (bool)$seminarevent->get_cancelledstatus() ? 'facetofacesessioncancel'  : 'facetofacesession';

        $customfields = customfield_get_data(
            $seminarevent->to_record(),
            $tblprefix,
            $prefix,
            true,
            ['extended' => true]
        );
        if (!empty($customfields)) {
            foreach ($customfields as $cftitle => $cfvalue) {
                if (!empty($cfvalue)) {
                    $section->add_detail_unsafe($cftitle, $cfvalue);
                }
            }
        }

        if ($option->get_displaycapacity()) {
            $labeltext = get_string('eventinfo:details:bookedcapacity', 'mod_facetoface');
            $currenthtml = \html_writer::span($signupcount, 'mod_facetoface__capacity__current');
            $maximumhtml = \html_writer::span($seminarevent->get_capacity(), 'mod_facetoface__capacity__maximum');
            $a = array('current' => $currenthtml, 'maximum' => $maximumhtml);
            $waitlisted = (new attendees_helper($seminarevent))->count_attendees_with_codes([waitlisted::get_code()], $includedeleted, true);
            if ($waitlisted > 0) {
                $a['waitlist'] = \html_writer::span($waitlisted, 'mod_facetoface__capacity__waitlist');
                $stats = get_string('capacitycurrentofmaximumwaitlist', 'mod_facetoface', $a);
            } else {
                $stats = get_string('capacitycurrentofmaximum', 'mod_facetoface', $a);
            }
            if ($seminarevent->get_allowoverbook() && $waitlisted <= 0) { // NOTE: avoid displaying '(1 on waitlist) (waitlist enabled)'
                $section->add_detail_unsafe($labeltext, get_string('capacityallowoverbook', 'mod_facetoface', $stats));
            } else {
                $section->add_detail_unsafe($labeltext, $stats);
            }
        } else if (!$option->get_calendaroutput()) {
            $placesleft = $seminarevent->get_capacity() - $signupcount;
            $section->add_detail(get_string('seatsavailable', 'mod_facetoface'), (string)max(0, $placesleft));
        }

        // Display Sign-up period.
        $signupperiod = \mod_facetoface\output\session_time::signup_period(
            $seminarevent->get_registrationtimestart(),
            $seminarevent->get_registrationtimefinish()
        );
        if ($signupperiod) {
            $section->add_detail_unsafe(get_string('registrationdetails', 'mod_facetoface'), $signupperiod);
        }

        // Display booking status.
        $bookingstatus = \mod_facetoface\seminar_event_helper::booking_status($session, $signupcount);
        $section->add_detail(get_string('eventbookingstatus', 'mod_facetoface'), $bookingstatus);

        // Display job assignments.
        if (get_config(null, 'facetoface_selectjobassignmentonsignupglobal') &&
            ($seminar->get_selectjobassignmentonsignup() || $seminar->get_forceselectjobassignment())) {
            if ($signup->has_jobassignment()) {
                $jobassignment = \totara_job\job_assignment::get_with_id(
                    $signup->get_jobassignmentid(),
                    false
                );
                if (null == $jobassignment) {
                    // If the job assignment does not exist, we should let the user know that
                    // the job assignment might have been deleted by the site admin, and the
                    // reference is not updated yet
                    $fullname = get_string("missingjobassignment", "mod_facetoface");
                } else {
                    $fullname = clean_string($jobassignment->fullname);
                }
                if (!empty($fullname)) {
                    $section->add_detail_unsafe(get_string('jobassignment', 'mod_facetoface'), $fullname);
                }
            }
        }

        // Display managers.
        if (!in_array($seminar->get_approvaltype(), [seminar::APPROVAL_NONE, seminar::APPROVAL_SELF])) {
            $approver = $seminar->get_approvaltype_string();
            if (!empty($approver)) {
                $section->add_detail(get_string('approvalrequiredby', 'mod_facetoface'), $approver);
            }

            $managernames = [];
            $managers = signup_helper::find_managers_from_signup($signup);
            foreach ($managers as $manager) {
                $manager_url = user_get_profile_url($manager);
                if ($manager_url) {
                    $managernames[] = html_writer::link($manager_url, $manager->fullname);
                } else {
                    $managernames[] = $manager->fullname;
                }
            }
            if (!empty($managernames)) {
                $section->add_detail_unsafe(get_string('managername', 'mod_facetoface'), implode(', ', $managernames));
            }
        }

        // Display trainers.
        $trainerhelper = new trainer_helper($seminarevent);
        $trainerroles = trainer_helper::get_trainer_roles(context_course::instance($seminar->get_course()));
        $trainers = $trainerhelper->get_trainers();
        foreach ((array)$trainerroles as $role => $rolename) {
            if (empty($trainers[$role])) {
                continue;
            }
            $trainer_names = [];
            $rolename = $rolename->localname;
            foreach ($trainers[$role] as $trainer) {
                $user = fullname($trainer);
                $trainer_url = user_get_profile_url($trainer->id);
                $trainer_names[] = $trainer_url ? html_writer::link($trainer_url, $user) : html_writer::span($user);
            }
            if (!empty($trainer_names)) {
                $section->add_detail_unsafe($rolename, implode(', ', $trainer_names));
            }
        }

        if (!get_config(null, 'facetoface_hidecost') && !empty($seminarevent->get_normalcost())) {
            $section->add_detail(get_string('normalcost', 'mod_facetoface'), format_string($seminarevent->get_normalcost()));

            if (!get_config(null, 'facetoface_hidediscount') && !empty($seminarevent->get_discountcost())) {
                $section->add_detail(get_string('discountcost', 'mod_facetoface'), format_string($seminarevent->get_discountcost()));
            }
        }

        if (!empty($seminarevent->get_details())) {
            $details = file_rewrite_pluginfile_urls(
                $seminarevent->get_details(),
                'pluginfile.php',
                context_module::instance($cm->id)->id,
                'mod_facetoface',
                'session',
                $seminarevent->get_id()
            );
            $details = format_text($details, FORMAT_HTML);
            $section->add_detail_unsafe(get_string('details', 'mod_facetoface'), $details, 'image');
        }

        return $builder->add_section($section)->build();
    }

    /**
     * Return CSS classes for seminar event status.
     *
     * @param seminar_event $seminarevent
     * @param boolean $isbookedsession
     * @param integer $signupcount
     * @param integer $time
     * @return array
     */
    public static function table_row_status_classes(seminar_event $seminarevent, bool $isbookedsession, int $signupcount, int $time = 0): array {
        if ($time <= 0) {
            $time = time();
        }
        $classes = [];
        if (!$seminarevent->is_sessions()) {
            $classes[] = 'waitlisted';
        }
        if ($seminarevent->is_first_started($time)) {
            $classes[] = 'started';
        }
        if ($seminarevent->is_over($time)) {
            $classes[] = 'over';
        }
        if ($seminarevent->get_cancelledstatus()) {
            $classes[] = 'cancelled';
        }
        if ($isbookedsession) {
            $classes[] = 'userbooked';
        }
        $sessionfull = ($signupcount >= $seminarevent->get_capacity());
        if ($sessionfull && !$seminarevent->get_allowoverbook()) {
            $classes[] = 'fullybooked';
        }
        if (!signup_helper::is_signup_open($seminarevent, $signupcount)) {
            $classes[] = 'closed';
        }
        return $classes;
    }

    /**
     * Render event session information in seminar event details.
     *
     * @param session_data $session
     * @param signup $signup
     * @param render_event_info_option $option
     * @return seminarevent_detail|null
     */
    protected function get_seminar_event_details_sessions(session_data $session, signup $signup, render_event_info_option $option): ?seminarevent_detail {
        $class = 'mod_facetoface__event_details';
        $seminarevent = (new seminar_event())->from_record_with_dates($session, false);

        if (!$option->get_calendaroutput()) {
            $builder = seminarevent_detail::builder('sessions')
                ->set_title(get_string('eventinfo:sessions', 'mod_facetoface'))
                ->set_id('f2f-sessions')
                ->set_collapsible(true, html_writer::random_id('f2fsection'));
        } else {
            $builder = seminarevent_detail::builder('sessions')->set_id('f2f-sessions');
        }

        $includedeleted = has_capability('totara/core:seedeletedusers', $this->getcontext());
        $signupcount = attendees_helper::count_signups($seminarevent, $includedeleted);
        $sessionbuilder = seminarevent_detail_session_list::builder('sessions');
        $sessionbuilder->set_states(self::table_row_status_classes($seminarevent, (bool)$session->bookedsession, $signupcount));

        $dates = clone $seminarevent->get_sessions();
        if (!$dates->is_empty()) {
            $backurl = $option->get_current_url();

            /** @var \mod_facetoface\seminar_session $date */
            foreach ($dates as $date) {

                if ($option->get_singlesession() && $option->get_singlesession() != $date->get_id()) {
                    continue;
                }

                // Session status.
                $status = \mod_facetoface\seminar_session_helper::get_status_from($seminarevent, $date);

                // Session dates.
                $sessiontime = \mod_facetoface\output\session_time::to_html(
                    $date->get_timestart(),
                    $date->get_timefinish(),
                    $date->get_sessiontimezone()
                );

                // TODO: refactor this and output/session_list_table.php after TL-21518 is merged
                $time = time();
                $states = [];
                if ($date->get_timestart() <= $time) {
                    $states[] = 'started';
                }
                if ($date->get_timefinish() < $time) {
                    $states[] = 'over';
                }

                $rooms = \mod_facetoface\room_list::from_session($date->get_id());
                $roomoutputs = [];
                if (!$rooms->is_empty()) {
                    // Display room information
                    /** @var \mod_facetoface\room $room */
                    foreach ($rooms as $room) {
                        $joinnow = false;

                        if ($room->is_virtual()) {
                            $joinnow = \mod_facetoface\room_helper::show_joinnow($seminarevent, $date, $signup);
                            if ($joinnow && !in_array('joinnow', $states)) {
                                $states[] = 'joinnow';
                            }
                        }
                        $roomoutputs[] = $this->get_session_room_details_html($date, $room, $backurl, $joinnow);
                    }
                }

                $facilitators = \mod_facetoface\facilitator_list::from_session($date->get_id());
                $facilitatoroutput = [];
                if (!$facilitators->is_empty()) {
                    // Display facilitator information
                    $facilitatoroutput = [];
                    /** @var \mod_facetoface\facilitator_user $facilitator */
                    foreach ($facilitators as $facilitator) {
                        $facilitatoroutput[] = $this->get_facilitator_details_html($facilitator, $backurl);
                    }
                }

                $assetoutputs = [];
                if ($option->get_displayassetsinsessions()) {
                    $assets = \mod_facetoface\asset_list::from_session($date->get_id());
                } else {
                    $assets = new \mod_facetoface\asset_list();
                }
                if (!$assets->is_empty()) {
                    // Display asset information
                    $assetoutputs = [];
                    /** @var \mod_facetoface\asset $asset */
                    foreach ($assets as $asset) {
                        $assetoutputs[] = $this->get_asset_details_html($asset, $backurl);
                    }
                }

                $sessionbuilder->add_session($status, $sessiontime, $states, array(), $assetoutputs, $roomoutputs, $facilitatoroutput);
            }
        } else {
            $sessionbuilder->add_session(get_string('wait-listed', 'facetoface'), '', [], [], [], [], []);
        }

        $builder->add_section_template($sessionbuilder->build());
        $output = $builder->build();

        return $output;
    }

    /**
     * Get the template data of the signup failures information sidebar
     * Only most relevant failures will be displayed
     *
     * @param signup $signup
     * @return array the template data of the sidebar
     */
    protected function get_sidebar_signup_failure(signup $signup): array {
        $failures = signup_helper::get_failures($signup);
        // Display first failure as the most relevant
        $failure = reset($failures);
        $card = seminarevent_signup_card::create_plain(get_string('signupunavailable', 'mod_facetoface'), $failure);
        return ['class' => 'signup-failure', 'content' => $this->render($card)];
    }

    /**
     * Get the template data of the sign-up sidebar
     *
     * @param signup $signup
     * @param render_event_info_option $option
     * @return array the template data of the sidebar
     */
    protected function get_sidebar_signup(signup $signup, render_event_info_option $option): array {
        $params = [
            'signup' => $signup,
            'backtoallsessions' => $option->get_backtoallsessions(),
        ];
        $attrs = [
            'name' => 'signupform'
        ];
        $signuplink = $this->get_signup_link($signup->get_seminar_event());
        if ($signuplink === '') {
            return [];
        }
        $mform = new \mod_facetoface\form\signup(new moodle_url($signuplink), $params, 'post', '', $attrs);
        // Add server-side validation results.
        if (!$mform->is_cancelled() && $mform->is_submitted()) {
            $mform->is_validated();
        }
        return ['class' => 'signup', 'content' => $mform->render()];
    }

    /**
     * Get the array of template data of
     * - the booked sidebar containing the link to cancellation
     * - the cancellation form sidebar
     *
     * @param signup $signup
     * @param render_event_info_option $option
     * @param boolean $cancelsignup Set true to activate the "cancel sign-up" sidebar
     * @return array of the template data of the sidebar
     */
    protected function get_sidebars_cancellation(signup $signup, render_event_info_option $option, bool $cancelsignup): array {
        global $OUTPUT;
        $idpart = 'f2f-signup-cancel-';
        $checkid = html_writer::random_id($idpart);
        $bookedid = html_writer::random_id($idpart);
        $confirmid = html_writer::random_id($idpart);

        $sidebars = [];

        // Cancellation
        $currentstate = $signup->get_state();
        $allowcancellation = $currentstate->can_switch(user_cancelled::class);
        if ($currentstate instanceof waitlisted) {
            $canceltext = get_string('cancelwaitlist', 'mod_facetoface');
        } else {
            $canceltext = get_string('cancelbooking', 'mod_facetoface');
        }
        $heading = signup_helper::get_user_booking_status($currentstate, false);
        if ($allowcancellation) {
            $card = seminarevent_signup_card::create_toggle($heading, $canceltext, $checkid);
            $toggle = ['id' => $checkid, 'class' => 'cancellation', 'on' => $cancelsignup, 'idon' => $bookedid, 'idoff' => $confirmid];
        } else {
            $card = seminarevent_signup_card::create_simple($heading);
            $toggle = [];
        }
        $sidebars[] = ['class' => 'cancellation', 'content' => $this->render($card), 'id' => $bookedid, 'toggle' => $toggle];

        if ($allowcancellation) {
            // Confirm
            $cancellation_note = (object)['id' => $signup->get_id()];
            customfield_load_data($cancellation_note, 'facetofacecancellation', 'facetoface_cancellation');

            $params = [
                's' => $signup->get_sessionid(),
                'backtoallsessions' => $option->get_backtoallsessions(),
                'cancellation_note' => $cancellation_note,
                'userisinwaitlist' => $currentstate instanceof waitlisted,
            ];
            $url = new \moodle_url('/mod/facetoface/eventinfo.php');
            $mform = new \mod_facetoface\form\cancelsignup($url, $params);
            // Add server-side validation results.
            if (!$mform->is_cancelled() && $mform->is_submitted()) {
                $mform->is_validated();
            }
            $closebutton = ['id' => $checkid, 'text' => $OUTPUT->flex_icon('close'), 'alttext' => get_string('eventinfo:cancelcancel', 'mod_facetoface'), 'class' => 'cancellation-cancellation'];
            $sidebars[] = ['class' => 'cancellation-confirm', 'content' => $mform->render(), 'id' => $confirmid, 'togglebutton' => $closebutton];
        }

        return $sidebars;
    }

    /**
     * Render signup state tranisitons failures
     * Only most relevant failures will be displayed
     * @param array $failures string[string] where value - failure text and key failure code
     * @return string
     */
    public function render_signup_failures(array $failures) : string {
        // Display first failure as the most relevant
        reset($failures);
        $failure = current($failures);
        return $this->output->notification($failure, 'info');
    }

    /**
     * Displays a bulk actions selector
     * @return string
     */
    public function display_bulk_actions_picker(): string {
        global $OUTPUT;

        $status_options = \mod_facetoface\attendees_helper::get_status();
        unset($status_options[\mod_facetoface\signup\state\not_set::get_code()]);
        $out = $OUTPUT->container_start('facetoface-bulk-actions-picker');
        $select = html_writer::select($status_options, 'bulkattendanceop', '',
            array('' => get_string('bulkactions', 'facetoface')), array('class' => 'bulkactions'));
        $label = get_string('mark_selected_as', 'facetoface');
        $error = get_string('selectoptionbefore', 'facetoface');
        $hidenlabel = html_writer::tag('span', $error, array('id' => 'selectoptionbefore', 'class' => 'hide error'));
        $out .= $label;
        $out .= $select;
        $out .= $hidenlabel;
        $out .= $OUTPUT->container_end();

        return $out;
    }

    /**
     * Room list output.
     *
     * @param seminar_event $seminarevent
     * @param seminar_session $date
     * @param string $currenturl
     * @param integer $time
     * @return string
     */
    public function render_room_list_links(seminar_event $seminarevent, seminar_session $date, string $currenturl, int $time): string {
        $rooms = \mod_facetoface\room_list::from_session($date->get_id());
        if ($rooms->is_empty()) {
            return '';
        }

        $listitems = [];
        /** @var \mod_facetoface\room $room */
        foreach ($rooms as $room) {
            $joinnow = false;
            if ($room->is_virtual()) {
                $joinnow = \mod_facetoface\room_helper::show_joinnow($seminarevent, $date, null, $time);
            }
            $listitems[] = $this->get_session_room_details_html($date, $room, $currenturl, $joinnow);
        }
        return self::render_unordered_list($listitems);
    }

    /**
     * Facilitator list output.
     *
     * @param seminar_session $date
     * @param string $currenturl
     * @return string
     */
    public function render_facilitator_list_links(seminar_session $date, string $currenturl): string {
        $facilitators = \mod_facetoface\facilitator_list::from_session($date->get_id());
        if ($facilitators->is_empty()) {
            return '';
        }

        $listitems = [];
        /** @var \mod_facetoface\facilitator_user $facilitator */
        foreach ($facilitators as $facilitator) {
            $listitems[] = $this->get_facilitator_details_html($facilitator, $currenturl);
        }
        return self::render_unordered_list($listitems);
    }

    /**
     * Get signup link URL.
     * This link is processed through mod_facetoface\\hook\\alternative_signup_link to allow additional plugins to set different
     * page for users to sign up.
     *
     * @param seminar_event $seminarevent
     * @param string $link
     * @return string
     */
    public function get_signup_link(seminar_event $seminarevent): string {
        // look for hooks unless set_signup_link() is called
        if ($this->signuplink === self::DEFAULT_SIGNUP_LINK) {
            // Start hook code
            $signupurl = $this->signuplink;
            $hook = new \mod_facetoface\hook\alternative_signup_link($seminarevent, $signupurl);
            $hook->execute();
            // End hook code
            return $hook->signuplink;
        }
        return $this->signuplink;
    }

    /**
     * Get event page link URL.
     * This link is processed through mod_facetoface\\hook\\alternative_signup_link to allow additional plugins to set different
     * page for users to sign up.
     *
     * @param seminar_event $seminarevent
     * @param string $link
     * @return moodle_url|null
     */
    public function get_event_page_link(seminar_event $seminarevent): ?moodle_url {
        $signuplink = $this->get_signup_link($seminarevent);
        if ($signuplink !== '' && $signuplink != self::DEFAULT_SIGNUP_LINK) {
            // Use an alternate sign-up link if it is provided by other components such as the totara_facetoface enrolment plugin.
            return new moodle_url($signuplink);
        } else if (signup_helper::is_user_actionable($seminarevent)) {
            return new moodle_url('/mod/facetoface/eventinfo.php');
        } else {
            return null;
        }
    }

    /**
     * Render table of users used in attendance list
     * @param \mod_facetoface\bulk_list $list User list Needed only with job assignments
     * @param int $courseid
     */
    public function print_attendance_upload_table(\mod_facetoface\bulk_list $list, int $courseid): void {
        global $OUTPUT;

        $status = attendance_state::get_all_attendance_csv();

        $userlist = $list->get_user_ids();
        $users = \mod_facetoface\attendees_list_helper::get_user_list($userlist);

        $table = new html_table();
        $table->head = [
            get_string('name'),
            get_string('eventattendanceheader', 'mod_facetoface'),
        ];
        // Really bad hack to know if optional column exists or not.
        // if you can think something better, let me know.
        if (isset($list->get_user_data(current($users)->id)['eventgrade'])) {
            $table->head[] = get_string('eventgradeheader', 'mod_facetoface');
        }
        $table->head[] = '';
        $table->attributes = ['class' => 'generaltable userstoupload fullwidth'];
        $context = \context_course::instance($courseid);
        if (count($users) > 0) {
            foreach ($users as $user) {
                $error = '';
                $row = [];
                $params = [];
                if ($courseid != SITEID && is_enrolled($context, $user->id)) {
                    $params['course'] = $courseid;
                }
                // User full name column.
                $row[] = user_helper::get_profile($user, null, $params);

                $data = $list->get_user_data($user->id);

                // Event attendance column
                $state = $data['eventattendance'];
                if (!in_array($state, array_keys($status))) {
                    $row[] = $error = get_string('error:invalidvalue', 'mod_facetoface');
                } else {
                    $row[] = $status[$state]::get_string();
                }
                // Event grade column.
                if (isset($data['eventgrade'])) {
                    $value = trim($data['eventgrade']);
                    if (is_numeric($value) && $value >= 0 && $value <= 100) {
                        $row[] = \mod_facetoface\grade_helper::format($value, $courseid);
                    } else {
                        $row[] = $error = get_string('error:invalidvalue', 'mod_facetoface');
                    }
                }
                // Result icon column.
                if (!empty($error)) {
                    $row[] = $OUTPUT->flex_icon('warning', ['classes' => 'ft-size-100', 'alt' => $error]);
                } else {
                    $row[] = $OUTPUT->flex_icon('check', ['classes' => 'ft-size-100 ft-state-success', 'alt' => get_string('success')]);
                }

                $table->data[] = new html_table_row($row);
            }
        }

        $data = $list->get_validation_results();
        if (!empty($data)) {
            foreach ($data as $entry) {
                $row = [];
                foreach ($entry as $key => $value) {
                    $row[] = s($value);
                }
                reset($row);
                $row[array_key_first($row)] = current($row) .
                    \html_writer::span(get_string('error:usernotfound', 'mod_facetoface'), 'usernotfound');
                $row[] = $OUTPUT->flex_icon('warning', [
                    'classes' => 'ft-size-100',
                    'alt' => get_string('userdoesnotexist', 'totara_core')
                ]);
                $table->data[] = new html_table_row($row);
            }
        }
        $total = count($users) + count($data);
        echo \html_writer::tag('h3', get_string('uploadattendancereview', 'mod_facetoface', $total));
        echo $OUTPUT->render($table);
    }
}
