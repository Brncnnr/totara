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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package mod_facetoface
*/

namespace mod_facetoface;
use mod_facetoface\exception\signup_exception;
use mod_facetoface\signup\state\{
    attendance_state,
    booked,
    requested,
    requestedadmin,
    requestedrole,
    state,
    not_set,
    interface_event,
    waitlisted
};
use mod_facetoface\signup\transition;
use \stdClass;
use totara_job\job_assignment;

defined('MOODLE_INTERNAL') || die();

/**
 * Class signup represents Session SignUps
 * This class must not know anything about specific states (e.g. difference between booked, waitlisted, or cancelled).
 * If specific state class needs to be considered use signup_helper instead.
 */
final class signup implements seminar_iterator_item {

    use \mod_facetoface\traits\crud_mapper;

    /**
     * @var int {facetoface_signups}.id
     */
    private $id = 0;
    /**
     * @var int {facetoface_signups}.sessionid
     */
    private $sessionid = 0;
    /**
     * @var int {facetoface_signups}.userid
     */
    private $userid = 0;
    /**
     * @var string {facetoface_signups}.discountcode
     */
    private $discountcode = null;
    /**
     * @var int {facetoface_signups}.notificationtype
     */
    private $notificationtype = 0;
    /**
     * @var int {facetoface_signups}.archived
     */
    private $archived = 0;
    /**
     * @var int {facetoface_signups}.bookedby
     */
    private $bookedby = null;
    /**
     * @var int {facetoface_signups}.managerid
     */
    private $managerid = null;
    /**
     * @var int {facetoface_signups}.jobassignmentid
     */
    private $jobassignmentid = null;
    /**
     * @var string facetoface_signups table name
     */
    const DBTABLE = 'facetoface_signups';
    /**
     * @var seminar_event|null linked instance
     */
    private $seminarevent = null;

    /**
     * @var array Instance settings for signup.
     * These settings are not persistable (ephemeral) at this stage
     */
    private $settings = [];

    /**
     * @var array Default options for switch_state_with_grade.
     */
    private $default_options = [
        'gradeonly' => false
    ];

    /**
     * Unique key to use in caching to distinguish between 2 signup events
     * @var string
     */
    private $cache_key = null;



    /**
     * Seminar signup constructor.
     *
     * @param int $id {facetoface_signups}.id If 0 - new signup will be created
     */
    public function __construct(int $id = 0) {
        $this->id = $id;
        $this->load();
        $this->cache_key = md5(uniqid(rand(), true));
    }

    /**
     * A function to create a signup from userid and seminar eventid
     * Will return an existing signup, or create a new one if none exists.
     *
     * @param int $userid
     * @param seminar_event|integer $seminarevent
     * @param int $notificationtype - Default 3 = MDL_F2F_BOTH
     * @param bool $includearchived - Default false = exclude archived
     * @return signup
     */
    public static function create(int $userid, $seminarevent, int $notificationtype = 3, bool $includearchived = false): signup {
        global $DB;

        if ($seminarevent instanceof seminar_event) {
            $eventid = $seminarevent->get_id();
        } else {
            $eventid = (int)$seminarevent;
            $seminarevent = null;
        }
        /** @var seminar_event|null $seminarevent */
        if (empty($eventid)) {
            throw new signup_exception("Cannot create signup: Seminar event id is not set (it must be saved before signup created)");
        }

        $signup = new signup();
        $signup->seminarevent = $seminarevent;
        $signup->set_notificationtype($notificationtype);
        $signup->userid = $userid;
        $signup->sessionid = $eventid;
        if ($signup->userid > 0) {
            $conditions = ['userid' => $userid, 'sessionid' => $eventid];
            if (!$includearchived) {
                $conditions['archived'] = 0;
            }
            $existing = $DB->get_record('facetoface_signups', $conditions);
            if (!empty($existing)) {
                return $signup->map_object($existing);
            }
        }

        return $signup;
    }

    /**
     * Returning true if this object has associated id existing in the table.
     * @return bool
     */
    public function exists(): bool {
        return !empty($this->id);
    }

    /**
     * Set signup instance skipapproval setting
     * @param bool $skip
     * @return signup
     */
    public function set_skipapproval(bool $skip = true): signup {
        $this->settings['skipapproval'] = $skip;
        return $this;
    }

    /**
     * Get signup instance skipapproval setting
     * @return bool
     */
    public function get_skipapproval(): bool {
        return empty($this->settings['skipapproval']) ? false : $this->settings['skipapproval'];
    }

    /**
     * Set signup instance ignoreconflicts setting
     * @param bool $ignore
     * @return signup
     */
    public function set_ignoreconflicts(bool $ignore = true): signup {
        $this->settings['ignoreconflicts'] = $ignore;
        return $this;
    }

    /**
     * Get signup instance ignoreconflicts setting
     * @return bool
     */
    public function get_ignoreconflicts(): bool {
        return empty($this->settings['ignoreconflicts']) ? false : $this->settings['ignoreconflicts'];
    }

    /**
     * Set signup instance skipusernotification setting
     * @param bool $skip
     * @return signup
     */
    public function set_skipusernotification(bool $skip = true): signup {
        $this->settings['skipusernotification'] = $skip;
        return $this;
    }

    /**
     * Get signup instance skipusernotification setting
     * @return bool
     */
    public function get_skipusernotification(): bool {
        return empty($this->settings['skipusernotification']) ? false : $this->settings['skipusernotification'];
    }

    /**
     * Set signup instance skipmanagernotification setting
     * @param bool $skip
     * @return signup
     */
    public function set_skipmanagernotification(bool $skip = true): signup {
        $this->settings['skipmanagernotification'] = $skip;
        return $this;
    }

    /**
     * Get signup instance skipmanagernotification setting
     * @return bool
     */
    public function get_skipmanagernotification(): bool {
        return empty($this->settings['skipmanagernotification']) ? false : $this->settings['skipmanagernotification'];
    }

    /**
     * Set signup notification sender user instance
     * @param \stdClass $user
     * @return signup
     */
    public function set_fromuser(\stdClass $user): signup {
        $this->settings['fromuser'] = $user;
        return $this;
    }
    /**
     * Get signup notification sender user instance
     * @return \stdClass or null
     */
    public function get_fromuser(): ?\stdClass {
        return empty($this->settings['fromuser']) ? null : $this->settings['fromuser'];
    }

    /**
     * Load seminar signup data from DB
     *
     * @return signup this
     */
    public function load(): signup {

        return $this->crud_load();
    }

    /**
     * Create/update {facetoface_signups}.record
     * @return signup
     */
    public function save(): signup {
        $this->crud_save();
        return $this;
    }

    /**
     * Map data object to signup instance.
     *
     * @param \stdClass $object
     * @return signup
     */
    public function map_instance(\stdClass $object): signup {

        return $this->map_object($object);
    }

    /**
     * Delete {facetoface_signups}.record where id
     * @return signup
     */
    public function delete(): signup {
        global $DB;

        if (!$this->exists()) {
            $this->map_object((object)get_object_vars(new self()));
            return $this;
        }

        $this->delete_customfields();

        // Delete all signup session statuses.
        session_status::delete_signup($this);

        // Delete all signup event statuses.
        $signupstatuses = new signup_status_list(['signupid' => $this->get_id()]);
        $signupstatuses->delete();

        // Save the instance before it's washed out.
        $presignup = clone $this;
        $cm = $this->get_seminar_event()->get_seminar()->get_coursemodule();
        $context = \context_module::instance($cm->id);

        $DB->delete_records(self::DBTABLE, ['id' => $this->id]);
        // Re-load instance with default values.
        $this->map_object((object)get_object_vars(new self()));

        \mod_facetoface\event\signup_deleted::create_from_signup($presignup, $context)->trigger();
        return $this;
    }

    /**
     * Check availability of states to switch for signup.
     * @param string ...$newstates class names
     * @return boolean
     */
    public function can_switch(string ...$newstates): bool {
        return $this->get_state()->can_switch(...$newstates);
    }

    /**
     * Switch signup state.
     * This function must be used for any state changes
     * @param string ...$newstates class names
     * @return signup
     */
    public function switch_state(string ...$newstates): signup {
        return $this->switch_state_with_grade(null, null, ...$newstates);
    }

    /**
     * Switch signup state and set grade.
     * This function must be used for any state changes
     * @param float|null $grade grade
     * @param array|null $options optional associative array optionally containing:
     *                  - 'gradeonly' => true to set grade without state changes.
     *                    In the case, $newstates must be the same as the current state.
     *                    Otherwise the signup_exception will be thrown.
     * @param string ...$newstates class names
     * @return signup
     * @throws signup_exception
     */
    public function switch_state_with_grade(?float $grade, $options, string ...$newstates): signup {
        global $DB;
        // Load options
        $options = ($options ?? []) + $this->default_options;

        $trans = $DB->start_delegated_transaction();
        $oldstate = $this->get_state();
        if ($options['gradeonly']) {
            if (count($newstates) !== 1 || get_class($oldstate) !== $newstates[0]) {
                throw new signup_exception('The gradeonly option is not available for the desired state(s): '.implode(', ', $newstates));
            }
            $newstate = $oldstate;
        } else {
            $newstate = $oldstate->switch_to(...$newstates);
        }
        $this->update_status($newstate, 0, 0, $grade, $options);

        /**
         * @var state $newstate
         */
        if ($newstate->get_code() != $oldstate->get_code()) {
            // Fire event only if switching to a different state.
            if ($newstate instanceof interface_event) {
                $newstate->get_event()->trigger();
            }
            $newstate->on_enter();
        }

        $trans->allow_commit();

        return $this;
    }

    /**
     * Print debug information for all states transitions
     * @param bool $return return debug instead of outputting it (like in print_r)
     * @return array
     */
    public function debug_state_transitions(bool $return=false): array {
        $results = [];
        $currentstate = $this->get_state();
        /**
         * @var transition $transition
         */
        foreach ($currentstate->get_map() as $transition) {
            $results[] = [get_class($transition->get_to()) => $transition->debug_conditions()];
        }
        $output = ['current' => get_class($currentstate), 'transitions' => $results];
        if (!$return) {
            echo \html_writer::tag('pre', print_r($output, true));
            return [];
        }
        return $output;
    }

    /**
     * Get reasons why transition to any of states is impossible for current user
     * If transition is possible then will
     * @param string ...$newstates class names
     * @return array
     */
    public function get_failures(string ...$newstates): array {
        $newstates = state::validate_state_classes($newstates);

        $results = [];
        $currentstate = $this->get_state();

        $map = $currentstate->get_map();
        $found = false;
        foreach ($newstates as $desiredstate) {
            /**
             * @var transition $transition
             */
            foreach ($map as $transition) {
                if ($transition->get_to() instanceof $desiredstate) {
                    $found = true;
                    if ($transition->possible()) {
                        return [];
                    } else {
                        $results = array_merge($results, $transition->get_failures());
                    }
                }
            }
        }
        if (!$found || empty($results)) {
            $results['notfound'] = get_string('error:nostatetransitionfound', 'mod_facetoface');
        }

        return $results;
    }

    /**
     * Delete records from facetoface_signup_info_data/facetoface_cancellation_info_data
     * @return signup
     */
    protected function delete_customfields(): signup {
        global $DB;

        // Get all associated signup customfield data to delete.
        $signupinfoids = $DB->get_fieldset_select(
            'facetoface_signup_info_data',
            'id',
            'facetofacesignupid = :facetofacesignupid',
            ['facetofacesignupid' => $this->get_id()]
        );
        if ($signupinfoids) {
            list($sqlin, $inparams) = $DB->get_in_or_equal($signupinfoids);
            $DB->delete_records_select('facetoface_signup_info_data_param', "dataid {$sqlin}", $inparams);
            $DB->delete_records_select('facetoface_signup_info_data', "id {$sqlin}", $inparams);
        }

        // Get all associated cancellation customfield data to delete.
        $cancellationids = $DB->get_fieldset_select(
            'facetoface_cancellation_info_data',
            'id',
            'facetofacecancellationid = :facetofacecancellationid',
            ['facetofacecancellationid' => $this->get_id()]
        );
        if ($cancellationids) {
            list($sqlin, $inparams) = $DB->get_in_or_equal($cancellationids);
            $DB->delete_records_select('facetoface_cancellation_info_data_param', "dataid {$sqlin}", $inparams);
            $DB->delete_records_select('facetoface_cancellation_info_data', "id {$sqlin}", $inparams);
        }

        return $this;
    }

    /**
     * Add new current signup status with a new state.
     * To change state of signup use signup::switch_state()
     * @param state         $state the new state
     * @param int           $timecreated timestamp or 0 to use current time
     * @param int           $userbyid who's updating the status? 0 for the current user
     * @param float|null    $grade
     * @param array|null    $options see signup::switch_state_with_grade()
     * @return signup_status
     * @throws signup_exception when switching to $state is not permitted
     */
    protected function update_status(state $state, int $timecreated = 0, int $userbyid = 0, ?float $grade = null, $options = null): signup_status {
        global $USER;
        // Load options
        $options = ($options ?? []) + $this->default_options;

        if ($state instanceof not_set) {
            throw new signup_exception("New booking status cannot be 'not set'");
        }

        // Special interception for the 'gradeonly' option:
        // - the current state and the desired $state must be the same. otherwise throw signup_exception.
        // - if the current grade is identical to the desired grade, do nothing;
        //   just returns the current state without adding a new record nor firing an event.
        if ($options['gradeonly']) {
            $currentstatus = $this->get_signup_status();
            if ($state->get_code() != $currentstatus->get_statuscode()) {
                throw new signup_exception('The gradeonly option is not available for the desired state(s): '.get_class($state));
            }
            if ($grade === $currentstatus->get_grade()) {
                return $currentstatus;
            }
        }

        $status = signup_status::create($this, $state, $timecreated, $grade, null);

        if (empty($userbyid)) {
            $userbyid = (int)$USER->id;
        }
        $status->set_createdby($userbyid);
        $status->save();

        $cm = $this->get_seminar_event()->get_seminar()->get_coursemodule();
        $context = \context_module::instance($cm->id);

        // The signup status has been updated, throw the generic event.
        // This will also trigger the update to the event grade and the seminar activity completion.
        \mod_facetoface\event\signup_status_updated::create_from_items($status, $context, $this)->trigger();

        return $status;
    }

    /**
     * @return int
     */
    public function get_id(): int {
        return (int)$this->id;
    }

    /**
     * Get current signup state. If no current status, then not_set will be returned
     * @return state
     */
    public function get_state(): state {
        $signupstatus = $this->get_signup_status();
        if ($signupstatus !== null) {
            $stateclass = $signupstatus->get_state_class();
            return new $stateclass($this);
        }

        return new not_set($this);
    }

    /**
     * @return int
     */
    public function get_sessionid(): int {
        return (int)$this->sessionid;
    }
    /**
     * @param int $sessionid
     * @return signup
     */
    public function set_sessionid(int $sessionid): signup {
        $this->sessionid = $sessionid;
        return $this;
    }

    /**
     * @return int
     */
    public function get_userid(): int {
        return (int)$this->userid;
    }
    /**
     * @param int $userid
     * @return signup
     */
    public function set_userid(int $userid): signup {
        $this->userid = $userid;
        return $this;
    }

    /**
     * @return string
     */
    public function get_discountcode(): string {
        return $this->discountcode;
    }
    /**
     * @param string $discountcode
     * @return signup
     */
    public function set_discountcode(string $discountcode): signup {
        $this->discountcode = $discountcode;
        return $this;
    }

    /**
     * Get cost associated with signup.
     * @return string
     */
    public function get_cost(): string {

        $cost = '';
        $hidecostconfig = get_config(null, 'facetoface_hidecost');
        $hidediscountconfig = get_config(null, 'facetoface_hidediscount');

        if ($hidecostconfig && $hidediscountconfig) {
            return $cost;
        }

        if (!empty($this->discountcode) && !$hidediscountconfig) {
            $cost = format_string($this->get_seminar_event()->get_discountcost());
        } else {
            if (!$hidecostconfig) {
                $cost = format_string($this->get_seminar_event()->get_normalcost());
            }
        }
        return $cost;
    }

    /**
     * @return int
     */
    public function get_notificationtype(): int {
        return (int)$this->notificationtype;
    }
    /**
     * @param int $notificationtype
     * @return signup
     */
    public function set_notificationtype(int $notificationtype): signup {
        $this->notificationtype = $notificationtype;
        return $this;
    }

    /**
     * @return int
     */
    public function get_archived(): int {
        return (int)$this->archived;
    }
    /**
     * @param int $archived
     * @return signup
     */
    public function set_archived(int $archived) : signup {
        $this->archived = $archived;
        return $this;
    }

    /**
     * @return int
     */
    public function get_bookedby() : int {
        return (int)$this->bookedby;
    }

    /**
     * @param int|null $bookedby
     * @return signup
     */
    public function set_bookedby(?int $bookedby) : signup {
        $this->bookedby = $bookedby;
        return $this;
    }

    /**
     * @return int
     */
    public function get_managerid() : int {
        return (int)$this->managerid;
    }

    /**
     * Checking whether this signup has associated managerid or not.
     * @return bool
     */
    public function has_manager(): bool {
        return !empty($this->managerid);
    }

    /**
     * @param int|null $managerid
     * @return signup
     */
    public function set_managerid(?int $managerid) : signup {
        $this->managerid = $managerid;
        return $this;
    }

    /**
     * @return int
     */
    public function get_jobassignmentid() : int {
        return (int)$this->jobassignmentid;
    }
    /**
     * @param int|null $jobassignmentid
     * @return signup
     */
    public function set_jobassignmentid(?int $jobassignmentid) : signup {
        $this->jobassignmentid = $jobassignmentid;
        return $this;
    }

    /**
     * Checking whether the user has chosen the job assignment for their signup or not
     * @return bool
     */
    public function has_jobassignment(): bool {
        return !empty($this->jobassignmentid);
    }

    /**
     * Get linked seminar event
     * @return seminar_event
     */
    public function get_seminar_event(): seminar_event {
        if (is_null($this->seminarevent) || $this->seminarevent->get_id() != $this->sessionid) {
            $this->seminarevent = new seminar_event((int)$this->sessionid);
        }
        return $this->seminarevent;
    }

    /**
     * @param int $actorid
     * @return signup
     */
    public function set_actorid(int $actorid): signup {
        $this->settings['actorid'] = $actorid;
        return $this;
    }

    /**
     * @return int
     */
    public function get_actorid(): int {
        global $USER;
        if (!isset($this->settings['actorid'])) {
            return (int)$USER->id;
        }
        return (int)$this->settings['actorid'];
    }

    /**
     * Returning null if the actorid is not being set, or a full record information (stdClass) of a user retrieved
     * from the database
     * @return stdClass
     */
    public function get_actor(): stdClass {
        global $DB, $USER;
        $actorid = $this->get_actorid();
        if ($actorid == $USER->id || $actorid == 0) {
            return $USER;
        }
        return $DB->get_record("user", ['id' => $actorid]);
    }

    /**
     * Tells the signup instance that the system is dealing with attendance.
     * This must always be set if the signup status could be changed as the result of processing attendance.
     * @param bool $value
     * @return signup
     */
    public function set_attendance_processed(bool $value): signup {
        $this->settings['process_attendance'] = $value;
        return $this;
    }

    /**
     * See if attendance is being processed or not.
     * @return bool
     */
    public function get_attendance_processed(): bool {
        return (bool)($this->settings['process_attendance'] ?? false);
    }

    /**
     * Map session data object to class instance.
     *
     * @param \stdClass|session_data|session_signup_data $object
     * @param boolean $strict Set false to ignore bogus properties
     * @return signup
     */
    public function from_record(\stdClass $object, bool $strict = true): signup {
        // First, flush all properties.
        $this->map_object((object)get_object_vars(new self()));
        // This is an ugly guess game.
        if (isset($object->facetoface)) {
            // OK, this object looks like session_data.
            $this->seminarevent = (new seminar_event())->from_record_with_dates($object, $strict);
            if (isset($object->bookedsession)) {
                $this->map_object($object->bookedsession, $strict);
            }
        } else {
            // This looks like session_signup_data.
            $this->map_object($object, $strict);
            $this->seminarevent = null;
        }
        $this->settings = [];
        return $this;
    }

    /**
     * Return the object that has all of properties that are mapped with the database's table.
     * @return stdClass
     */
    public function to_record(): \stdClass {
        return $this->unmap_object();
    }

    /**
     * Returning the signup status of this signup. If it is existing in the system.
     * @return signup_status|null
     */
    public function get_signup_status(): ?signup_status {
        return signup_status::find_current($this);
    }

    /**
     * Returning true if the signup is within certain stastes that are not cancelled or not set. By cancelled state,
     * this is including any cancelled state or declined state.
     *
     * @return bool
     */
    public function is_active(): bool {
        if (!$this->exists()) {
            // Not really an active signup if the signup itself is not even existed.
            return false;
        }

        $state = $this->get_state();
        $statuscodes = attendance_state::get_all_attendance_code_with([
            requested::class,
            requestedrole::class,
            requestedadmin::class,
            waitlisted::class,
            booked::class
        ]);

        return in_array($state::get_code(), $statuscodes);
    }

    /**
     * @return string
     */
    public function get_cahce_key(): string {
        return $this->cache_key;
    }
}
