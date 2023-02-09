<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author  Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface;

use context_module;
use core\orm\query\builder;
use mod_facetoface\attendance\attendance_helper;
use mod_facetoface\signup\condition\event_taking_attendance;
use mod_facetoface\hook\event_is_being_cancelled;
use mod_facetoface\signup\state\booked;
use mod_facetoface\totara_notification\seminar_notification_helper;
use totara_notification\external_helper;
use mod_facetoface\totara_notification\resolver\event_cancelled as cn_event_cancelled;
use mod_facetoface\totara_notification\resolver\booking_event_cancelled;

defined('MOODLE_INTERNAL') || die();

/**
 * Class seminar_event represents Seminar event
 */
final class seminar_event implements seminar_iterator_item {

    use traits\crud_mapper;

    /**
     * Cancellation options for $this->allowcancellation.
     */
    const ALLOW_CANCELLATION_NEVER = 0;
    const ALLOW_CANCELLATION_ANY_TIME = 1;
    const ALLOW_CANCELLATION_CUT_OFF = 2;

    /**
     * @var int {facetoface_sessions}.id
     */
    private $id = 0;
    /**
     * @var int {facetoface_sessions}.facetoface
     */
    private $facetoface = 0;
    /**
     * @var int {facetoface_sessions}.capacity
     */
    private $capacity = 10;
    /**
     * @var int {facetoface_sessions}.allowoverbook
     */
    private $allowoverbook = 0;
    /**
     * @var int {facetoface_sessions}.waitlisteveryone
     */
    private $waitlisteveryone = 0;
    /**
     * @var string {facetoface_sessions}.details
     */
    private $details = '';
    /**
     * @var string {facetoface_sessions}.normalcost
     */
    private $normalcost = '';
    /**
     * @var string {facetoface_sessions}.discountcost
     */
    private $discountcost = '';
    /**
     * @var int {facetoface_sessions}.allowcancellations
     */
    private $allowcancellations = 1;
    /**
     * @var int {facetoface_sessions}.cancellationcutoff
     */
    private $cancellationcutoff = 86400;
    /**
     * @var int {facetoface_sessions}.timecreated
     */
    private $timecreated = 0;
    /**
     * @var int {facetoface_sessions}.timemodified
     */
    private $timemodified = 0;
    /**
     * @var int {facetoface_sessions}.usermodified
     */
    private $usermodified = 0;
    /**
     * @var int {facetoface_sessions}.selfapproval
     */
    private $selfapproval = 0;
    /**
     * @var int {facetoface_sessions}.mincapacity
     */
    private $mincapacity = 0;
    /**
     * @var int {facetoface_sessions}.cutoff
     */
    private $cutoff = 86400;
    /**
     * @var int {facetoface_sessions}.sendcapacityemail
     */
    private $sendcapacityemail = 0;
    /**
     * @var int {facetoface_sessions}.registrationtimestart
     */
    private $registrationtimestart = 0;
    /**
     * @var int {facetoface_sessions}.registrationtimefinish
     */
    private $registrationtimefinish = 0;
    /**
     * @var int {facetoface_sessions}.cancelledstatus
     */
    private $cancelledstatus = 0;

    /**
     * @var string facetoface_sessions table name
     */
    const DBTABLE = 'facetoface_sessions';

    /**
     * @var seminar_session_list
     */
    private $sessions = null;

    /**
     * Seminar event constructor.
     *
     * @param int $id {facetoface_session}.id If 0 - new Seminar Event will be created
     */
    public function __construct(int $id = 0) {

        $this->id = $id;
        $this->load();
    }

    /**
     * Load seminar event data from DB
     *
     * @return seminar_event this
     */
    private function load(): seminar_event {

        return $this->crud_load();
    }

    /**
     * Create/update {facetoface_sessions}.record
     */
    public function save(): void {
        global $USER;

        $this->timemodified = time();
        $this->usermodified = $USER->id;
        $this->cleanup_capacity();

        if (!$this->id) {
            $this->timecreated = time();
        }

        $this->crud_save();
    }

    /**
     * Return whether the seminar event can be cancelled or not.
     *
     * @param int $time
     * @return bool
     */
    public function is_cancellable(int $time = 0): bool {
        if (!$this->exists()) {
            // Event does not exist.
            return false;
        }

        if ($this->is_first_started($time)) {
            // Events can not be cancelled after they have started.
            return false;
        }

        if ($this->get_cancelledstatus() != 0) {
            // Event is already cancelled, can not cancel twice.
            return false;
        }

        return true;
    }

    /**
     * Cancel the seminar event.
     *
     * @param boolean $delete is the seminar event being deleted?
     * @return boolean
     */
    private function cancel_internal(bool $delete): bool {
        global $DB;

        if (!$this->is_cancellable()) {
            return false;
        }

        // Wrap necessary DB updates in a transaction.
        $trans = $DB->start_delegated_transaction();

        $this->set_cancelledstatus(1);
        $this->save();

        // Remove entries from the calendars.
        calendar::remove_all_entries($this);
        calendar::remove_facilitator_event($this);

        // Change all user sign-up statuses, the only exceptions are previously cancelled users and declined users.
        $notifylearners = [];
        /** @var signup[] $signups */
        $signups = signup_list::from_conditions(['sessionid' => $this->get_id()]);
        foreach ($signups as $signup) {
            if ($signup->can_switch(\mod_facetoface\signup\state\event_cancelled::class)) {
                $signup->switch_state(\mod_facetoface\signup\state\event_cancelled::class);
                // Add them to the affected learners list for later notifications.
                $notifylearners[$signup->get_userid()] = $signup;
            }
        }
        // All necessary DB updates are finished, let's commit.
        $trans->allow_commit();

        // Notify affected users.
        foreach ($notifylearners as $id => $user) {
            // Check if the user is waitlisted we should not attach an iCal.
            $state = $signup->get_state();
            $invite = !($state instanceof \mod_facetoface\signup\state\waitlisted);
            notice_sender::event_cancellation($id, $this, $invite);

            // Centralised notifications.
            if (!$delete) {
                $seminar = $this->get_seminar();
                $data = [
                    'user_id' => $id,
                    'seminar_event_id' => $this->id,
                    'seminar_id' => $seminar->get_id(),
                    'module_id' => $seminar->get_coursemodule()->id,
                    'course_id' => $seminar->get_course()
                ];
                seminar_notification_helper::create_seminar_notifiable_event_queue(
                    $seminar,
                    new booking_event_cancelled($data)
                );
            }
        }

        // Notify affected trainers assigned to the session.
        $notifytrainers = role_list::get_distinct_users_from_seminarevent($this);
        foreach ($notifytrainers as $role) {
            notice_sender::event_trainer_cancellation($role->get_userid(), $this);
        }

        // Centralised notifications only for event cancellation.
        if (!$delete) {
            $seminar = $this->get_seminar();
            $data = [
                'seminar_event_id' => $this->id,
                'seminar_id' => $seminar->get_id(),
                'module_id' => $seminar->get_coursemodule()->id,
                'course_id' => $seminar->get_course()
            ];

            seminar_notification_helper::create_seminar_notifiable_event_queue(
                $seminar,
                new cn_event_cancelled($data)
            );
        }

        // Notify managers who had reservations.
        notice_sender::reservation_cancelled($this);

        if (!$delete) {
            // Notify watchers.
            (new event_is_being_cancelled($this, false))->execute();
        }

        $cm = get_coursemodule_from_instance('facetoface', $this->get_facetoface());
        $context = context_module::instance($cm->id);
        \mod_facetoface\event\session_cancelled::create_from_session($this->to_record(), $context)->trigger();

        return true;
    }

    /**
     * Cancel the seminar event.
     *
     * @return bool
     */
    public function cancel(): bool {
        return $this->cancel_internal(false);
    }

    /**
     * Delete {facetoface_sessions}.record where id from database. This will only delete session date, signup, file,
     * custom field and notification records that are related to the seminar_event. It does not actually delete orphan
     * rooms nor orphan assets that had unlinked from the seminar events. Furthermore it does not cancel itself at all,
     * and it shouldn't.
     *
     * If external usage wants to perform a full scale on deleting seminar event (include trying to delete orphan rooms
     * and assets, plus cancelling event) then \mod_facetoface\seminar_event_helper::delete_seminarevent is the
     * way to go.
     *
     * @return void
     */
    public function delete(): void {
        global $DB;

        $cm = $this->get_seminar()->get_coursemodule();
        $context = context_module::instance($cm->id);

        // Capture the snapshot of the seminar event before cancelling or deleting it.
        $session = $this->to_record();
        $session->mintimestart = $this->get_mintimestart();
        $session->sessiondates = $this->get_sessions()->sort('timestart')->to_records(false);

        $alreadycancelled = !empty($this->get_cancelledstatus());
        // Cancel the event first.
        $this->cancel_internal(true);

        // Notify watchers.
        if (!$alreadycancelled) {
            (new event_is_being_cancelled($this, true))->execute();
        }

        // Remove entries from the calendars.
        calendar::remove_all_entries($this);
        calendar::remove_facilitator_event($this);

        $seminarsignups = signup_list::from_conditions(['sessionid' => $this->get_id()]);
        $seminarsignups->delete();

        $sessiondates = $this->get_sessions();
        $sessiondates->delete();

        $seminarroles = new role_list(['sessionid' => $this->get_id()]);
        $seminarroles->delete();

        $this->delete_files();
        $this->delete_customfields();
        $this->delete_notifications();

        $DB->delete_records(self::DBTABLE, ['id' => $this->id]);

        \mod_facetoface\event\session_deleted::create_from_session($session, $context)->trigger();

        // Re-load instance with default values.
        $this->map_object((object)get_object_vars(new self()));
    }

    /**
     * Delete event custom field records belonging to this seminar event
     *
     * @return seminar_event
     */
    protected function delete_customfields(): seminar_event {
        global $DB;

        // Get session data to delete.
        $sessiondataids = $DB->get_fieldset_select(
            'facetoface_session_info_data',
            'id',
            'facetofacesessionid = :facetofacesessionid',
            ['facetofacesessionid' => $this->get_id()]);

        if ($sessiondataids) {
            list($sqlin, $inparams) = $DB->get_in_or_equal($sessiondataids);
            $DB->delete_records_select('facetoface_session_info_data_param', "dataid {$sqlin}", $inparams);
            $DB->delete_records_select('facetoface_session_info_data', "id {$sqlin}", $inparams);
        }

        $sessioncancelids = $DB->get_fieldset_select(
            'facetoface_sessioncancel_info_data',
            'id',
            'facetofacesessioncancelid = :sessionid',
            ['sessionid' => $this->get_id()]
        );
        if ($sessioncancelids) {
            list($sqlin, $inparams) = $DB->get_in_or_equal($sessioncancelids);
            $DB->delete_records_select('facetoface_sessioncancel_info_data_param', "dataid $sqlin", $inparams);
            $DB->delete_records_select('facetoface_sessioncancel_info_data', "id {$sqlin}", $inparams);
        }

        return $this;
    }

    /**
     * Delete files embedded in details text associated with this seminar event
     *
     * @return seminar_event $this
     */
    protected function delete_files(): seminar_event {

        $seminar = new seminar($this->get_facetoface());
        $cm = get_coursemodule_from_instance('facetoface', $seminar->get_id(), $seminar->get_course(), false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_facetoface', 'session', $this->id);

        return $this;
    }

    /**
     * Map object to only class instance, not session dates list.
     *
     * @param \stdClass $object
     * @param boolean $strict Set false to ignore bogus properties
     * @return seminar_event
     */
    public function from_record(\stdClass $object, bool $strict = true): seminar_event {
        $this->sessions = null;
        return $this->map_object($object, $strict);
    }

    /**
     * Map object to class instance and session dates list.
     *
     * @param \stdClass $object that can optionally contain sessiondates
     * @param boolean $strict Set false to ignore bogus properties
     * @return seminar_event
     */
    public function from_record_with_dates(\stdClass $object, bool $strict = true): seminar_event {
        $object = clone $object;
        $sessiondates = $object->sessiondates ?? null;
        unset($object->sessiondates);
        $this->from_record($object, $strict);
        if (!empty($sessiondates)) {
            $this->sessions = seminar_session_list::from_records($sessiondates, $strict);
        }
        return $this;
    }

    /**
     * Map seminar event instance properties to data object.
     *
     * @return \stdClass
     */
    public function to_record(): \stdClass {
        return $this->unmap_object();
    }

    /**
     * Prepare the user data to go into the database.
     *
     * @return seminar_event
     */
    protected function cleanup_capacity(): seminar_event {
        // Only numbers allowed here
        $capacity = preg_replace('/[^\d]/', '', $this->capacity);
        $MAX_CAPACITY = 100000;
        if ($capacity < 1) {
            $capacity = 1;
        } elseif ($capacity > $MAX_CAPACITY) {
            $capacity = $MAX_CAPACITY;
        }

        $this->set_capacity((int)$capacity);

        return $this;
    }

    /**
     * Check whether the seminar event exists yet or not.
     * If the asset has been saved into the database the $id field should be non-zero.
     *
     * @return bool - true if the asset has an $id, false if it hasn't
     */
    public function exists(): bool {
        return !empty($this->id);
    }

    /**
     * Dismiss approver from seminar event.
     *
     */
    public function dismiss_approver(): void {
        global $DB;

        $this->set_selfapproval(0);
        $DB->update_record(self::DBTABLE, ['selfapproval' => $this->selfapproval, 'id' => $this->id]);
    }

    /**
     * Return associated seminar instance
     * @return seminar
     */
    public function get_seminar(): seminar {
        if (empty($this->get_facetoface())) {
            throw new \coding_exception("Cannot get seminar from unassociated event");
        }
        return new seminar($this->get_facetoface());
    }

    /**
     * Has this seminar event started at certain point of time
     * @param int $time
     * @return bool
     * @deprecated Totara 13 use `is_first_started()` instead
     */
    public function is_started(int $time = 0): bool {
        debugging('The method ' . __METHOD__ . '() has been deprecated. Please use seminar_event::is_first_started() instead.', DEBUG_DEVELOPER);
        return $this->is_first_started($time);
    }

    /**
     * Has the first session of this seminar event started at certain point of time
     * @param int $time
     * @return bool
     */
    public function is_first_started(int $time = 0): bool {
        $time = $time ? $time : time();
        $sessions = $this->get_sessions();

        // Check that a date has actually been set.
        if (!$sessions->count()) {
            return false;
        }

        $mintimestart = $this->get_mintimestart();
        if (empty($mintimestart)) {
            // There are no sessions so it can't have started.
            return false;
        }

        return $mintimestart < $time;
    }

    /**
     * Has the last session of this seminar event started at certain point of time
     * @param int $time
     * @return bool
     */
    public function is_last_started(int $time = 0): bool {
        $time = $time ? $time : time();
        $sessions = $this->get_sessions();

        // Check that a date has actually been set.
        if (!$sessions->count()) {
            return false;
        }

        $maxtimestart = $this->get_maxtimestart();
        if (empty($maxtimestart)) {
            // There are no sessions so it can't have started.
            return false;
        }

        return $maxtimestart < $time;
    }

    /**
     * Returning true, if all the session(s) of this event are already completed.
     *
     * @param int $time
     * @return bool
     */
    public function is_over(int $time = 0): bool {
        $sessions = $this->get_sessions();
        return $sessions->is_everything_over($time);
    }

    /**
     * Get the earliest start time from associated sessions.
     * @return int
     */
    public function get_mintimestart(): int {
        $mintimestart = 0;
        /** @var seminar_session[] $sessions */
        $sessions = $this->get_sessions();
        foreach ($sessions as $session) {
            // Check for minimum time start.
            if (empty($mintimestart) || $session->get_timestart() < $mintimestart) {
                $mintimestart = $session->get_timestart();
            }
        }

        return $mintimestart;
    }

    /**
     * Get the latest start time from associated sessions.
     * @return int
     */
    public function get_maxtimestart(): int {
        $maxtimestart = 0;
        /** @var seminar_session[] $sessions */
        $sessions = $this->get_sessions();
        foreach ($sessions as $session) {
            // Check for maximum time start.
            if ($session->get_timestart() > $maxtimestart) {
                $maxtimestart = $session->get_timestart();
            }
        }

        return $maxtimestart;
    }

    /**
     * Is seminar event in progress, for checking it, the rule is: first session has been started,
     * and the last session must not be over.
     *
     * @param int $time
     * @return bool
     */
    public function is_progress(int $time = 0): bool {
        if (0 != $this->cancelledstatus) {
            // Cancelled event
            return false;
        }

        $timenow = $time ? $time : time();
        $dates = $this->get_sessions();
        if ($dates->is_empty()) {
            // Wait-listed events
            return false;
        }

        $first = $dates->get_first();
        $last = $dates->get_last();

        return $first->is_start($timenow) && !$last->is_over($timenow);
    }

    /**
     * Does this event have session(s)
     * @return bool
     */
    public function is_sessions(): bool {
        return !$this->get_sessions()->is_empty();
    }

    /**
     * Get sessions for this event
     *
     * @param bool $reload
     * @return seminar_session_list
     */
    public function get_sessions(bool $reload = false): seminar_session_list {
        if (null == $this->sessions || $reload) {
            $this->sessions = seminar_session_list::from_seminar_event($this);
        }

        return $this->sessions;
    }

    /**
     * With the purpose to reload the inner sessions here, and it should be used mostly for the testing purpose, as sometimes data
     * needed to be reload after update.
     *
     * @return seminar_event
     */
    public function clear_sessions(): seminar_event {
        $this->sessions = null;
        return $this;
    }

    /**
     * Get the oldiest finish time from associated sessions.
     * @return int
     */
    public function get_maxtimefinish(): int {
        $maxtimefinish = 0;
        /** @var seminar_session[] $sessions */
        $sessions = $this->get_sessions();
        foreach ($sessions as $session) {
            // Check for max time finish.
            if (empty($maxtimefinish) || $session->get_timefinish() > $maxtimefinish) {
                $maxtimefinish = $session->get_timefinish();
            }
        }
        return $maxtimefinish;
    }

    /**
     * Id of seminar event
     * @return int
     */
    public function get_id(): int {
        return (int)$this->id;
    }

    /**
     * Get facetoface id
     * @return int
     */
    public function get_facetoface(): int {
        return (int)$this->facetoface;
    }

    /**
     * Set facetoface id
     * @param int $facetoface
     * @return seminar_event
     */
    public function set_facetoface(int $facetoface): seminar_event {
        $this->facetoface = $facetoface;
        return $this;
    }

    /**
     * Get capacity of event (total number of places to book)
     * @return int
     */
    public function get_capacity(): int {
        return (int)$this->capacity;
    }

    /**
     * Get amount of free capacity
     * @return int
     */
    public function get_free_capacity(): int {
        global $DB;
        $attendeesql = 'SELECT COUNT(ss.id)
                           FROM {facetoface_signups} su
                           JOIN {facetoface_signups_status} ss ON su.id = ss.signupid
                          WHERE sessionid = :sessionid
                            AND ss.superceded = 0
                            AND ss.statuscode >= :status';
        $numattendees = (int)$DB->count_records_sql($attendeesql, ['sessionid' => $this->id, 'status' => \mod_facetoface\signup\state\booked::get_code()]);
        return $this->get_capacity() - $numattendees;
    }

    /**
     * Check if event has capacity
     * @return bool
     */
    public function has_capacity(): bool {
        if ($this->get_free_capacity() > 0) {
            return true;
        }
        // User can overbook directly if waitlist is disabled.
        $cm = $this->get_seminar()->get_coursemodule();
        $context = \context_module::instance($cm->id);
        if (!$this->get_allowoverbook() && has_capability('mod/facetoface:signupwaitlist', $context)) {
            return true;
        }
        return false;
    }

    /**
     * Set capacity of event
     * @param int $capacity
     * @return seminar_event
     */
    public function set_capacity(int $capacity): seminar_event {
        $this->capacity = $capacity;
        return $this;
    }

    /**
     * Get event allowoverbook
     * @return int
     */
    public function get_allowoverbook(): int {
        return (int)$this->allowoverbook;
    }
    /**
     * Set allowoverbook of event
     * @param int $allowoverbook
     * @return seminar_event
     */
    public function set_allowoverbook(int $allowoverbook): seminar_event {
        $this->allowoverbook = $allowoverbook;
        return $this;
    }

    /**
     * Get event waitlisteveryone
     * @return int
     */
    public function get_waitlisteveryone(): int {
        return (int)$this->waitlisteveryone;
    }

    /**
     * Check if waitlist everyone is enabled globally and for the event.
     * @return bool
     */
    public function is_waitlisteveryone(): bool {
        return get_config(null, 'facetoface_allowwaitlisteveryone') && $this->waitlisteveryone;
    }

    /**
     * Set waitlisteveryone of event
     * @param int $waitlisteveryone
     * @return seminar_event
     */
    public function set_waitlisteveryone(int $waitlisteveryone): seminar_event {
        $this->waitlisteveryone = $waitlisteveryone;
        return $this;
    }

    /**
     * Get event details
     * @return string
     */
    public function get_details(): string {
        return (string)$this->details;
    }
    /**
     * Set event details
     * @param string $details
     * @return seminar_event
     */
    public function set_details(string $details): seminar_event {
        $this->details = $details;
        return $this;
    }

    /**
     * Get event normalcost
     * @return string
     */
    public function get_normalcost(): string {
        return (string)$this->normalcost;
    }
    /**
     * Set event normalcost
     * @param string $normalcost
     * @return seminar_event
     */
    public function set_normalcost(string $normalcost): seminar_event {
        $this->normalcost = $normalcost;
        return $this;
    }

    /**
     * Get event discountcost
     * @return string
     */
    public function get_discountcost(): string {
        return (string)$this->discountcost;
    }
    /**
     * Set event discountcost
     * @param string $discountcost
     * @return seminar_event
     */
    public function set_discountcost(string $discountcost): seminar_event {
        $this->discountcost = $discountcost;
        return $this;
    }

    /**
     * Should discount cost be displayed taking into account global settings
     * @return bool
     */
    public function is_discountcost(): bool {
        return !get_config(null, 'facetoface_hidecost')
            && !get_config(null, 'facetoface_hidediscount')
            && !empty($this->get_discountcost());
    }


    /**
     * Get event allowcancellations
     * @return int
     */
    public function get_allowcancellations(): int {
        return (int)$this->allowcancellations;
    }
    /**
     * Set event allowcancellations
     * @param int $allowcancellations
     * @return seminar_event
     */
    public function set_allowcancellations(int $allowcancellations): seminar_event {
        $this->allowcancellations = $allowcancellations;
        return $this;
    }

    /**
     * Get event cancellationcutoff
     * @return int
     */
    public function get_cancellationcutoff(): int {
        return (int)$this->cancellationcutoff;
    }
    /**
     * Set event cancellationcutoff
     * @param int $cancellationcutoff
     * @return seminar_event
     */
    public function set_cancellationcutoff(int $cancellationcutoff): seminar_event {
        $this->cancellationcutoff = $cancellationcutoff;
        return $this;
    }

    /**
     * Get event timecreated
     * @return int
     */
    public function get_timecreated(): int {
        return (int)$this->timecreated;
    }
    /**
     * Set event timecreated
     * @param int $timecreated
     * @return seminar_event
     */
    public function set_timecreated(int $timecreated): seminar_event {
        $this->timecreated = $timecreated;
        return $this;
    }

    /**
     * Get event timemodified
     * @return int
     */
    public function get_timemodified(): int {
        return (int)$this->timemodified;
    }
    /**
     * Set event timemodified
     * @param int $timemodified
     * @return seminar_event
     */
    public function set_timemodified(int $timemodified): seminar_event {
        $this->timemodified = $timemodified;
        return $this;
    }

    /**
     * Get event usermodified
     * @return int
     */
    public function get_usermodified(): int {
        return (int)$this->usermodified;
    }
    /**
     * Set event usermodified
     * @param int $usermodified
     * @return seminar_event
     */
    public function set_usermodified(int $usermodified): seminar_event {
        $this->usermodified = $usermodified;
        return $this;
    }

    /**
     * Get event selfapproval
     * @return int
     */
    public function get_selfapproval(): int {
        return (int)$this->selfapproval;
    }
    /**
     * Set event selfapproval
     * @param int $selfapproval
     * @return seminar_event
     */
    public function set_selfapproval(int $selfapproval): seminar_event {
        $this->selfapproval = $selfapproval;
        return $this;
    }

    /**
     * Get event mincapacity
     * @return int
     */
    public function get_mincapacity(): int {
        return (int)$this->mincapacity;
    }
    /**
     * Set event mincapacity
     * @param int $mincapacity
     * @return seminar_event
     */
    public function set_mincapacity(int $mincapacity): seminar_event {
        $this->mincapacity = $mincapacity;
        return $this;
    }

    /**
     * Get event cutoff
     * @return int
     */
    public function get_cutoff(): int {
        return (int)$this->cutoff;
    }

    /**
     * Checking whether the seminar_event has the cutoff time or not. By default, it is zero in database/table.
     * @return bool
     */
    public function has_cutoff(): bool {
        return !empty($this->cutoff);
    }

    /**
     * Set event cutoff
     * @param int $cutoff
     * @return seminar_event
     */
    public function set_cutoff(int $cutoff): seminar_event {
        $this->cutoff = $cutoff;
        return $this;
    }

    /**
     * Get event sendcapacityemail
     * @return int
     */
    public function get_sendcapacityemail(): int {
        return (int)$this->sendcapacityemail;
    }
    /**
     * Set event sendcapacityemail
     * @param int $sendcapacityemail
     * @return seminar_event
     */
    public function set_sendcapacityemail(int $sendcapacityemail): seminar_event {
        $this->sendcapacityemail = $sendcapacityemail;
        return $this;
    }

    /**
     * Get event registrationtimestart
     * @return int
     */
    public function get_registrationtimestart(): int {
        return (int)$this->registrationtimestart;
    }
    /**
     * Set event registrationtimestart
     * @param int $registrationtimestart
     * @return seminar_event
     */
    public function set_registrationtimestart(int $registrationtimestart): seminar_event {
        $this->registrationtimestart = $registrationtimestart;
        return $this;
    }

    /**
     * Get event registrationtimefinish
     * @return int
     */
    public function get_registrationtimefinish(): int {
        return (int)$this->registrationtimefinish;
    }

    /**
     * Set event registrationtimefinish
     * @param int $registrationtimefinish
     * @return seminar_event
     */
    public function set_registrationtimefinish(int $registrationtimefinish): seminar_event {
        $this->registrationtimefinish = $registrationtimefinish;
        return $this;
    }

    /**
     * See if the registration is open.
     *
     * @param integer $time Timestamp or 0 for current time
     * @return boolean
     */
    public function is_registration_open(int $time = 0): bool {
        $time = $time ?: time();
        $timestart = $this->registrationtimestart;
        $timefinish = $this->registrationtimefinish;
        $start = empty($timestart) || $time > $timestart;
        $finish = empty($timefinish) || $time < $timefinish;
        return $start && $finish;
    }

    /**
     * Get event cancelledstatus
     * @return int
     */
    public function get_cancelledstatus(): int {
        return (int)$this->cancelledstatus;
    }

    /**
     * Set event cancelledstatus
     * @param int $cancelledstatus
     * @return seminar_event
     */
    public function set_cancelledstatus(int $cancelledstatus): seminar_event {
        $this->cancelledstatus = $cancelledstatus;
        return $this;
    }

    /**
     * Check whether the event attendance taking is available or not.
     *
     * @param int $time
     * @return bool
     */
    public function is_attendance_open(int $time = 0): bool {
        return attendance_taking_status::is_available($this->get_attendance_taking_status(null, $time, false, false));
    }

    /**
     * Return attendance taking status of the seminar event.
     *
     * @param integer|null  $eventattendance One of seminar::EVENT_ATTENDANCE_xxx, or null to load the seminar setting
     * @param integer       $time           The current timestamp
     * @param boolean       $checksaved     Set false to not check the status of each attendee
     * @param boolean       $checkattendees Set true to return NOTAVAILABLE when the session is open but no attendees
     * @return integer  One of attendance_taking_status constants
     */
    public function get_attendance_taking_status(int $eventattendance = null, int $time = 0, bool $checksaved = true, bool $checkattendees = false): int {
        if (null === $eventattendance) {
            $seminar = $this->get_seminar();
            $eventattendance = $seminar->get_attendancetime();
        }
        if (0 != $this->get_cancelledstatus()) {
            // A cancelled event should not open for taking attendance.
            return attendance_taking_status::CANCELLED;
        }

        if (0 >= $time) {
            $time = time();
        }

        switch ($eventattendance) {
            case seminar::EVENT_ATTENDANCE_FIRST_SESSION_START:
                // For checking attendance at time start, which apply that number of minutes
                // before start.
                $time += event_taking_attendance::UNLOCKED_SECS_PRIOR_TO_START;
                if (!$this->is_first_started($time)) {
                    return attendance_taking_status::CLOSED_UNTILSTARTFIRST;
                }
                break;

            case seminar::EVENT_ATTENDANCE_LAST_SESSION_START:
                // For checking attendance at time start, which apply that number of minutes
                // before start.
                $time += event_taking_attendance::UNLOCKED_SECS_PRIOR_TO_START;
                if (!$this->is_last_started($time)) {
                    return attendance_taking_status::CLOSED_UNTILSTARTLAST;
                }
                break;

            case seminar::EVENT_ATTENDANCE_LAST_SESSION_END:
                if (!$this->is_over($time)) {
                    return attendance_taking_status::CLOSED_UNTILEND;
                }
                break;

            case seminar::EVENT_ATTENDANCE_UNRESTRICTED:
                if (!$this->is_sessions()) {
                    // It is a wait-listed event, so attendance is not open.
                    return attendance_taking_status::NOTAVAILABLE;
                }
                break;

            default:
                debugging("The event attendance time {$eventattendance} is not valid.", DEBUG_DEVELOPER);
                return attendance_taking_status::UNKNOWN;
        }

        // Skip digging into attendees to improve performance.
        if (!$checksaved) {
            return attendance_taking_status::OPEN;
        }

        $helper = new attendance_helper();
        $attendees = $helper->get_event_attendees($this->get_id(), false);

        if (empty($attendees)) {
            if ($checkattendees) {
                // Taking attendance is not open if no one attends the event.
                return attendance_taking_status::NOTAVAILABLE;
            } else {
                // Taking attendance is open if no one attends the event.
                return attendance_taking_status::OPEN;
            }
        }

        $saved = true;
        foreach ($attendees as $attendee) {
            if (!$attendee->statuscode || $attendee->statuscode == booked::get_code()) {
                $saved = false;
                break;
            }
        }

        if ($saved) {
            return attendance_taking_status::ALLSAVED;
        } else {
            return attendance_taking_status::OPEN;
        }
    }

    /**
     * Checking whether the session is open for taking attendance or not.
     * @param int $sessiondateid
     * @return bool
     */
    public function is_session_open(int $sessiondateid): bool {
        debugging('The method ' . __METHOD__ . '() has been deprecated. Please use seminar_session::is_attendance_open() instead.', DEBUG_DEVELOPER);
        $sessions = $this->get_sessions();
        // Check those session dates that is valid or not.
        /** @var seminar_session|null $session */
        return !$sessions->get($sessiondateid)->is_attendance_open();
    }

    /**
     * Extended logic for seminar events to handle the case where attendance is open for a session, but not for
     * the event itself.
     *
     * @param int $time Unix timestamp or 0 to use the current time
     * @param int|null $sessionattendance One of seminar::SESSION_ATTENDANCE_xxx, or null to load the seminar setting
     * @return bool
     */
    public function is_any_attendance_open(int $time = 0, int $sessionattendance = null): bool {
        debugging('The method ' . __METHOD__ . '() has been deprecated. Please use seminar_event::is_any_session_attendance_open() instead.', DEBUG_DEVELOPER);
        return $this->is_any_session_attendance_open($time, $sessionattendance);
    }

    /**
     * Extended logic for seminar events to handle the case where attendance is open for a session, but not for
     * the event itself.
     *
     * @param int $time Unix timestamp or 0 to use the current time
     * @param int|null $sessionattendance One of seminar::SESSION_ATTENDANCE_xxx, or null to load the seminar setting
     * @return bool
     */
    public function is_any_session_attendance_open(int $time = 0, int $sessionattendance = null): bool {
        if (0 >= $time) {
            $time = time();
        }
        if ($sessionattendance === null) {
            $seminar = $this->get_seminar();
            $sessionattendance = $seminar->get_sessionattendance();
        }

        switch ($sessionattendance) {
            case seminar::SESSION_ATTENDANCE_UNRESTRICTED:
                if ($this->is_sessions()) {
                    return true;
                }
                break;

            case seminar::SESSION_ATTENDANCE_START:
                if ($this->is_first_started($time + event_taking_attendance::UNLOCKED_SECS_PRIOR_TO_START)) {
                    return true;
                }
                break;

            case seminar::SESSION_ATTENDANCE_END:
                if ($this->get_sessions()->is_anything_over($time)) {
                    return true;
                }
                break;

            case 1:
                debugging("An old sessionattendance has been detected. Please upgrade the website!");
                // Recursively call itself to deal with outdated $sessionattendance
                if (!isset($seminar)) {
                    $seminar = $this->get_seminar();
                }
                $sessionattendance = $seminar->fix_up_session_attendance_time($sessionattendance);
                return $this->is_any_session_attendance_open($time, $sessionattendance);
        }

        // No session attendance is open; Try event attendance.
        return $this->is_attendance_open($time);
    }

    /**
     * Deleting notifications for facetoface, if there are any. It is deleting records in both table {facetoface_notification_sent}
     * and {facetoface_notification_hist} that are related to this event.
     *
     * Primarily being used in delete function.
     *
     * @return bool
     */
    private function delete_notifications(): bool {
        global $DB;

        if (empty($this->id)) {
            return false;
        }

        $params = ['sessionid' => $this->id];
        $DB->delete_records('facetoface_notification_sent', $params);
        $DB->delete_records('facetoface_notification_hist', $params);

        // Delete centralised notifications.
        $cm = $this->get_seminar()->get_coursemodule();
        $context = context_module::instance($cm->id);
        external_helper::remove_notification_preferences(
            $context->id,
            'mod_facetoface',
            'seminar_event',
            $this->id,
        );

        return true;
    }

    /**
     * Check if a seminar event exists and if it does then load it. The constructor fails if no event found for an
     * ID provided which makes the exist() function irrelevant and in some situations we would want to know if a
     * session exist without causing an exception.
     * @param int $eventid
     * @return seminar_event
     * @deprecated since Totara 13.0
     */
    public static function find(int $eventid): seminar_event {
        debugging('seminar_event::find() function has been deprecated, please use seminar_event::seek()', DEBUG_DEVELOPER);
        return self::seek($eventid);
    }

    /**
     * Limits session list to those with a particular facilitator assigned.
     *
     * @param int $facilitatorid
     * @return void
     */
    public function facilitator_sessions_only(int $facilitatorid): void {
        $all_sessions = $this->get_sessions(true);
        $facilitator_sessions = new seminar_session_list(); // Create an empty list.
        foreach ($all_sessions as $id => $date) {
            $facilitators = facilitator_list::from_session($id);
            if ($facilitators->count()) {
                $ids = $facilitators->get_ids();
                if (in_array($facilitatorid, $ids)) {
                    $facilitator_sessions->add($date);
                }
            }
        }
        $this->sessions = $facilitator_sessions;
    }

    /**
     * Take users who created or edited room with virtualmeeting
     *
     * @return stdClass[]
     */
    public function get_virtualmeeting_creators_in_all_sessions(): array {
        if (!$this->exists()) {
            return [];
        }
        return builder::table('user', 'u')
            ->join([room_virtualmeeting::DBTABLE, 'frvm'], 'frvm.userid', 'u.id')
            ->join([room::DBTABLE, 'fr'], 'frvm.roomid', 'fr.id')
            ->join([room_dates_virtualmeeting::DBTABLE, 'frdvm'], 'frdvm.roomid', 'fr.id')
            ->join([seminar_session::DBTABLE, 'sd'], 'frdvm.sessionsdateid', 'sd.id')
            ->where('u.deleted', 0)
            ->where('u.suspended', 0)
            ->where('sd.sessionid', $this->id)
            ->select_raw('distinct u.id, u.*')
            ->fetch();
    }
}
