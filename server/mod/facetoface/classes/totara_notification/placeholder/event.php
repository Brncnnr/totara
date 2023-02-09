<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author  Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package mod_facetoface
 * @category totara_notification
 */

namespace mod_facetoface\totara_notification\placeholder;

use coding_exception;
use core\entity\user;
use html_writer;
use mod_facetoface\attendees_helper;
use mod_facetoface\seminar_event;
use mod_facetoface\seminar_event_helper;
use mod_facetoface\seminar_session_list;
use mod_facetoface\signup\state\attendance_state;
use mod_facetoface\signup\state\booked;
use mod_facetoface\signup\state\waitlisted;
use moodle_url;
use totara_notification\placeholder\abstraction\placeholder_instance_cache;
use totara_notification\placeholder\abstraction\single_emptiable_placeholder;
use totara_notification\placeholder\option;

global $CFG;
require_once $CFG->dirroot . '/mod/facetoface/notification/lib.php';

class event extends single_emptiable_placeholder {
    use placeholder_instance_cache;

    /** @var ?seminar_event */
    private $event;

    /**
     * event constructor.
     * @param seminar_event|null $event
     */
    public function __construct(?seminar_event $event = null) {
        $this->event = $event;
    }

    /**
     * @param int $event_id
     *
     * @return self
     */
    public static function from_event_id(int $event_id): self {
        $cache_key = $event_id;

        $instance = self::get_cached_instance($cache_key);
        if (!$instance) {
            $event = new seminar_event($event_id);
            $instance = new static($event);
            self::add_instance_to_cache($cache_key, $instance);
        }
        return $instance;
    }

    /**
     * @return option[]
     */
    public static function get_options(): array {
        // Went for notification_placeholder_... to prevent overlap with existing strings
        return [
            option::create('all_sessions', get_string('notification_placeholder_event_all_sessions', 'mod_facetoface')),
            option::create('attendees_link', get_string('notification_placeholder_event_attendees_linked', 'mod_facetoface')),
            option::create('booked', get_string('notification_placeholder_event_booked', 'mod_facetoface')),
            option::create('capacity', get_string('notification_placeholder_event_capacity', 'mod_facetoface')),
            option::create('cost', get_string('notification_placeholder_event_cost', 'mod_facetoface')),
            option::create('details', get_string('notification_placeholder_event_details', 'mod_facetoface')),
            option::create('duration', get_string('notification_placeholder_event_duration', 'mod_facetoface')),
            option::create('event_page_link', get_string('notification_placeholder_event_event_page_linked', 'mod_facetoface')),
            option::create('finish_date', get_string('notification_placeholder_event_finish_date', 'mod_facetoface')),
            option::create('finish_time', get_string('notification_placeholder_event_finish_time', 'mod_facetoface')),
            option::create('latest_finish_date', get_string('notification_placeholder_event_latest_finish_date', 'mod_facetoface')),
            option::create('latest_finish_time', get_string('notification_placeholder_event_latest_finish_time', 'mod_facetoface')),
            option::create('latest_start_date', get_string('notification_placeholder_event_latest_start_date', 'mod_facetoface')),
            option::create('latest_start_time', get_string('notification_placeholder_event_latest_start_time', 'mod_facetoface')),
            option::create('minimum_capacity', get_string('notification_placeholder_event_minimum_capacity', 'mod_facetoface')),
            option::create('registration_cutoff', get_string('notification_placeholder_event_registration_cutoff', 'mod_facetoface')),
            option::create('reminder_period', get_string('notification_placeholder_event_reminder_period', 'mod_facetoface')),
            option::create('session_date', get_string('notification_placeholder_event_session_date', 'mod_facetoface')),
            option::create('session_role', get_string('notification_placeholder_event_session_role', 'mod_facetoface')),
            option::create('start_date', get_string('notification_placeholder_event_start_date', 'mod_facetoface')),
            option::create('start_time', get_string('notification_placeholder_event_start_time', 'mod_facetoface')),
        ];
    }

    /**
     * @param string $key
     * @return bool
     */
    protected function is_available(string $key): bool {
        return $this->event !== null;
    }

    /**
     * @param string $key
     * @return string
     */
    public function do_get(string $key): string {
        if ($this->event === null) {
            throw new coding_exception("The seminar event is empty");
        }

        switch ($key) {
            case 'all_sessions':
                $layout = get_string('notification_placeholder_event_all_sessions_layout', 'mod_facetoface');
                $sessiondata = seminar_event_helper::get_sessiondata($this->event, null);
                return facetoface_notification_loop_session_placeholders($layout, $sessiondata);

            case 'attendees_link':
                $attendees_url = new moodle_url('/mod/facetoface/attendees/approvalrequired.php', ['s' => $this->event->get_id()]);
                return html_writer::link($attendees_url, $attendees_url, ['title' => get_string('attendees', 'mod_facetoface')]);

            case 'booked':
                $status_codes = attendance_state::get_all_attendance_code_with([waitlisted::class, booked::class]);
                $helper = new attendees_helper($this->event);
                return $helper->count_attendees_with_codes($status_codes);

            case 'capacity':
                return $this->event->get_capacity();

            case 'cost':
                $hidecost_config = get_config(null, 'facetoface_hidecost');
                if (!$hidecost_config) {
                    return $this->event->get_normalcost();
                } else {
                    return '';
                }

            case 'details':
                return $this->event->get_details();

            case 'duration':
                // Get the first timestart and the last timefinish
                $sessions = $this->event->get_sessions();
                $sessions->sort('timestart', seminar_session_list::SORT_ASC);
                $session_dates = $sessions->to_records(false);

                if (empty($session_dates)) {
                    return '';
                }

                $start = (int)$session_dates[0]->timestart;
                $finish = (int)end($session_dates)->timefinish;

                return format_time($finish - $start);

            case 'event_page_link':
                $eventlink = new moodle_url('/mod/facetoface/eventinfo.php', ['s' => $this->event->get_id()]);
                return html_writer::link($eventlink, htmlentities($eventlink));

            case 'finish_date':
                // Date at the end of the event. If there are multiple sessions it will use the first one.
                $sessions = $this->event->get_sessions();
                $sessions->sort('timestart', seminar_session_list::SORT_ASC);
                $session_dates = $sessions->to_records(false);

                if (empty($session_dates)) {
                    return get_string('unknowndate', 'mod_facetoface');
                }
                return userdate(
                    $session_dates[0]->timefinish,
                    get_string('strftimedate'),
                    $session_dates[0]->sessiontimezone
                );

            case 'finish_time':
                // Finish time of the event. If there are multiple sessions it will use the first one.
                $sessions = $this->event->get_sessions();
                $sessions->sort('timestart', seminar_session_list::SORT_ASC);
                $session_dates = $sessions->to_records(false);

                if (empty($session_dates)) {
                    return get_string('unknowntime', 'mod_facetoface');
                }
                return userdate(
                    $session_dates[0]->timefinish,
                    get_string('strftimetime'),
                    $session_dates[0]->sessiontimezone
                );

            case 'latest_finish_date':
                // Latest finish date of the event. If there are multiple sessions it will use the last one.
                $sessions = $this->event->get_sessions();
                $sessions->sort('timestart', seminar_session_list::SORT_DESC);
                $session_dates = $sessions->to_records(false);

                if (empty($session_dates)) {
                    return get_string('unknowndate', 'mod_facetoface');
                }
                return userdate(
                    $session_dates[0]->timefinish,
                    get_string('strftimedate'),
                    $session_dates[0]->sessiontimezone
                );

            case 'latest_finish_time':
                // Latest finish time of the event. If there are multiple sessions it will use the last one.
                $sessions = $this->event->get_sessions();
                $sessions->sort('timestart', seminar_session_list::SORT_DESC);
                $session_dates = $sessions->to_records(false);

                if (empty($session_dates)) {
                    return get_string('unknowntime', 'mod_facetoface');
                }
                return userdate(
                    $session_dates[0]->timefinish,
                    get_string('strftimetime'),
                    $session_dates[0]->sessiontimezone
                );

            case 'latest_start_date':
                // Latest start of the event. If there are multiple sessions it will use the last one
                $sessions = $this->event->get_sessions();
                $sessions->sort('timestart', seminar_session_list::SORT_DESC);
                $session_dates = $sessions->to_records(false);

                if (empty($session_dates)) {
                    return get_string('unknowndate', 'mod_facetoface');
                }
                return userdate(
                    $session_dates[0]->timestart,
                    get_string('strftimedate'),
                    $session_dates[0]->sessiontimezone
                );

            case 'latest_start_time':
                // Latest start time of the event. If there are multiple sessions it will use the last one.
                $sessions = $this->event->get_sessions();
                $sessions->sort('timestart', seminar_session_list::SORT_DESC);
                $session_dates = $sessions->to_records(false);

                if (empty($session_dates)) {
                    return get_string('unknowntime', 'mod_facetoface');
                }
                return userdate(
                    $session_dates[0]->timestart,
                    get_string('strftimetime'),
                    $session_dates[0]->sessiontimezone
                );

            case 'minimum_capacity':
                return $this->event->get_mincapacity();

            case 'registration_cutoff':
                // Returning the datetime in the user's timezone for consistency
                $sessions = $this->event->get_sessions();
                $sessions->sort('timestart', seminar_session_list::SORT_ASC);
                $session_dates = $sessions->to_records(false);

                $registration_finish = $this->event->get_registrationtimefinish();
                if (!empty($registration_finish)) {
                    return userdate(
                        $registration_finish,
                        get_string('strftimerecent'),
                        $session_dates[0]->sessiontimezone ?? user::logged_in()->timezone
                    );
                } else {
                    if (!empty($session_dates) && !empty($session_dates[0]->timestart)) {
                        return userdate(
                            $session_dates[0]->timestart,
                            get_string('strftimerecent'),
                            $session_dates[0]->sessiontimezone
                        );
                    } else {
                        return get_string('unknowndate', 'mod_facetoface');
                    }
                }

            case 'reminder_period':
                global $DB;

                // Deviating from original seminar code here in that we allow for other schedule units (not only days)
                $sql =
                    "SELECT id, scheduleunit, scheduleamount
                       FROM {facetoface_notification}
                      WHERE facetofaceid = :facetofaceid
                        AND conditiontype = :conditiontype
                        AND status = :status
                        AND scheduleunit IS NOT NULL";
                $params = [
                    'facetofaceid' => $this->event->get_seminar()->get_id(),
                    'conditiontype' => MDL_F2F_CONDITION_BEFORE_SESSION,
                    'status' => 1
                ];

                // Convert non-day units to days. Default to 30 days per month and 365 days per year
                $reminderperiod = 0;

                $reminders = $DB->get_records_sql($sql, $params);
                foreach ($reminders as $reminder) {
                    $days = 0;
                    switch ($reminder->scheduleunit) {
                        case MDL_F2F_SCHEDULE_UNIT_HOUR:
                            $days = (int)($reminder->scheduleamount / 24);
                            break;

                        case MDL_F2F_SCHEDULE_UNIT_DAY:
                            $days = $reminder->scheduleamount;
                            break;

                        case MDL_F2F_SCHEDULE_UNIT_WEEK:
                            $days = 7 * $reminder->scheduleamount;
                            break;

                        case MDL_F2F_SCHEDULE_UNIT_MONTH:
                            $days = 30 * $reminder->scheduleamount;
                            break;

                        case MDL_F2F_SCHEDULE_UNIT_YEAR:
                            $days = 365 * $reminder->scheduleamount;
                            break;
                    }

                    if ($days > $reminderperiod) {
                        $reminderperiod = $days;
                    }
                }

                return $reminderperiod;

            case 'session_date':
                $sessions = $this->event->get_sessions();
                $sessions->sort('timestart', seminar_session_list::SORT_ASC);
                $session_dates = $sessions->to_records(false);

                if (empty($session_dates)) {
                    return get_string('unknowndate', 'mod_facetoface');
                }

                $startdate = userdate(
                    $session_dates[0]->timestart,
                    get_string('strftimedate'),
                    $session_dates[0]->sessiontimezone
                );
                $finishdate = userdate(
                    $session_dates[0]->timefinish,
                    get_string('strftimedate'),
                    $session_dates[0]->sessiontimezone
                );
                return ($startdate == $finishdate) ? $startdate : $startdate . ' - ' . $finishdate;

            case 'session_role':
                $approvalrole = $this->event->get_seminar()->get_approvalrole();
                if (!empty($approvalrole)) {
                    $rolenames = role_fix_names(get_all_roles());
                    return $rolenames[$approvalrole]->localname;
                }

                return '';

            case 'start_date':
                // Date at the start of the event. If there are multiple sessions it will use the first one.
                $sessions = $this->event->get_sessions();
                $sessions->sort('timestart', seminar_session_list::SORT_ASC);
                $session_dates = $sessions->to_records(false);

                if (empty($session_dates)) {
                    return get_string('unknowndate', 'mod_facetoface');
                }
                return userdate(
                    $session_dates[0]->timestart,
                    get_string('strftimedate'),
                    $session_dates[0]->sessiontimezone
                );

            case 'start_time':
                // Start time of the event. If there are multiple sessions it will use the first one.
                $sessions = $this->event->get_sessions();
                $sessions->sort('timestart', seminar_session_list::SORT_ASC);
                $session_dates = $sessions->to_records(false);

                if (empty($session_dates)) {
                    return get_string('unknowntime', 'mod_facetoface');
                }
                return userdate(
                    $session_dates[0]->timestart,
                    get_string('strftimetime'),
                    $session_dates[0]->sessiontimezone
                );

            // The timestamp placeholders are not exposed for use in the notification templates - therefore not added as options
            // They are used in logging to allow for translation using the viewing user's language preferences

            case 'start_timestamp':
                // Date at the start of the event. If there are multiple sessions it will use the first one.
                $sessions = $this->event->get_sessions();
                $sessions->sort('timestart', seminar_session_list::SORT_ASC);
                $session_dates = $sessions->to_records(false);

                if (empty($session_dates)) {
                    return '';
                }
                return $session_dates[0]->timestart;
        }

        throw new coding_exception("Invalid key '$key'");
    }

    /**
     * @param string $key
     * @return bool
     */
    public static function is_safe_html(string $key): bool {
        if ($key === 'attendees_link' || $key === 'event_page_link' || $key === 'all_sessions') {
            return true;
        }

        return parent::is_safe_html($key);
    }
}
