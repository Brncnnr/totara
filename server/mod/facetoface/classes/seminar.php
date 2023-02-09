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
 * @author  Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @author  Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface;

use context_module;
use mod_facetoface\signup\state\{no_show, partially_attended, fully_attended, unable_to_attend, declined, not_set};

defined('MOODLE_INTERNAL') || die();

/**
 * Class seminar represents Seminar Activity
 */
final class seminar implements seminar_iterator_item {

    use traits\crud_mapper;

    /**
     * Approval types
     */
    const APPROVAL_NONE = 0;
    const APPROVAL_SELF = 1;
    const APPROVAL_ROLE = 2;
    const APPROVAL_MANAGER = 4;
    const APPROVAL_ADMIN = 8;

    /** @deprecated use EVENT_ATTENDANCE_LAST_SESSION_END instead */
    const ATTENDANCE_TIME_END = 0;
    /** @deprecated use EVENT_ATTENDANCE_FIRST_SESSION instead */
    const ATTENDANCE_TIME_START = 1;
    /** @deprecated use EVENT_ATTENDANCE_UNRESTRICTED instead */
    const ATTENDANCE_TIME_ANY = 2;

    /** Event attendance - end of last session */
    const EVENT_ATTENDANCE_LAST_SESSION_END = 0;

    /** Event attendance - beginning of first session */
    const EVENT_ATTENDANCE_FIRST_SESSION_START = 1;

    /** Event attendance - unrestricted (any time) */
    const EVENT_ATTENDANCE_UNRESTRICTED = 2;

    /** Event attendance - beginning of last session */
    const EVENT_ATTENDANCE_LAST_SESSION_START = 3;

    // NOTE: 4 and 5 are reserved to avoid mix-up between session/event attendance.

    /** Default attendancetime (event attendance) */
    const EVENT_ATTENDANCE_DEFAULT = self::EVENT_ATTENDANCE_LAST_SESSION_START;

    /** Array of valid event attendance values */
    const EVENT_ATTENDANCE_VALID_VALUES = [
        self::EVENT_ATTENDANCE_LAST_SESSION_END,
        self::EVENT_ATTENDANCE_FIRST_SESSION_START,
        self::EVENT_ATTENDANCE_UNRESTRICTED,
        self::EVENT_ATTENDANCE_LAST_SESSION_START
    ];

    /** Session attendance - disabled */
    const SESSION_ATTENDANCE_DISABLED = 0;

    // NOTE: 1 is reserved for shim.

    /** Session attendance - unrestricted (any time) */
    const SESSION_ATTENDANCE_UNRESTRICTED = 2;

    // NOTE: 3 is reserved to avoid mix-up between session/event attendance.

    /** Session attendance - end of session */
    const SESSION_ATTENDANCE_END = 4;

    /** Session attendance - beginning of session */
    const SESSION_ATTENDANCE_START = 5;

    /** Default sessionattendance (session attendance) */
    const SESSION_ATTENDANCE_DEFAULT = self::SESSION_ATTENDANCE_DISABLED;

    /** Array of valid session attendance values */
    const SESSION_ATTENDANCE_VALID_VALUES = [
        self::SESSION_ATTENDANCE_DISABLED,
        self::SESSION_ATTENDANCE_UNRESTRICTED,
        self::SESSION_ATTENDANCE_START,
        self::SESSION_ATTENDANCE_END
    ];

    /**
     * Event grading method field values
     */
    const GRADING_METHOD_GRADEHIGHEST = 0;
    const GRADING_METHOD_GRADELOWEST = 1;
    const GRADING_METHOD_EVENTFIRST = 2;
    const GRADING_METHOD_EVENTLAST = 3;
    const GRADING_METHOD_DEFAULT = self::GRADING_METHOD_GRADEHIGHEST;

    /**
     * Event grading value to pass
     */
    const GRADE_PASS_DEFAULT = 100.;
    const GRADE_PASS_MINIMUM = 0.;
    const GRADE_PASS_MAXIMUM = 100.;

    /**
     * Activity completion grade types
     */
    const COMPLETION_PASS_DISABLED = 0;
    const COMPLETION_PASS_ANY = 1;
    const COMPLETION_PASS_GRADEPASS = 2;

    /**
     * Activity completion delay min/max days
     */
    const COMPLETION_DELAY_MINIMUM = 0;
    const COMPLETION_DELAY_MAXIMUM = 999;

    /**
     * @var int {facetoface}.id
     */
    private $id = 0;
    /**
     * @var int {facetoface}.course
     */
    private $course = 0;
    /**
     * @var string {facetoface}.name
     */
    private $name = "";
    /**
     * @var string {facetoface}.intro
     */
    private $intro = "";
    /**
     * @var int {facetoface}.introformat
     */
    private $introformat = 0;
    /**
     * @var string {facetoface}.thirdparty
     */
    private $thirdparty = "";
    /**
     * @var int {facetoface}.thirdpartywaitlist
     */
    private $thirdpartywaitlist = "";
    /**
     * @var int {facetoface}.waitlistautoclean
     */
    private $waitlistautoclean = 0;
    /**
     * @var int {facetoface}.display
     */
    private $display = 0;
    /**
     * @var int {facetoface}.timecreated
     */
    private $timecreated = 0;
    /**
     * @var int {facetoface}.timemodified
     */
    private $timemodified = 0;
    /**
     * @var string {facetoface}.shortname
     */
    private $shortname = "";
    /**
     * @var int {facetoface}.showoncalendar
     */
    private $showoncalendar = 1;
    /**
     * @var int {facetoface}.usercalentry
     */
    private $usercalentry = 1;
    /**
     * Note: saved in the database as multiplesessions,
     *       referred to elsewhere as multiplesignups.
     * @var int {facetoface}.multiplesessions
     */
    private $multiplesessions = 0;
    /**
     * @var int {facetoface}.multisignupfully
     */
    private $multisignupfully = 0;
    /**
     * @var int {facetoface}.multisignuppartly
     */
    private $multisignuppartly = 0;
    /**
     * @var int {facetoface}.multisignupnoshow
     */
    private $multisignupnoshow = 0;
    /**
     * @var int {facetoface}.multisignupunableto
     */
    private $multisignupunableto = 0;
    /**
     * @var int {facetoface}.multisignupmaximum
     */
    private $multisignupmaximum = 0;
    /**
     * @var string {facetoface}.completionstatusrequired
     */
    private $completionstatusrequired = null;
    /**
     * @var int {facetoface}.managerreserve
     */
    private $managerreserve = 0;
    /**
     * @var int {facetoface}.maxmanagerreserves
     */
    private $maxmanagerreserves = 1;
    /**
     * @var int {facetoface}.reservecanceldays
     */
    private $reservecanceldays = 1;
    /**
     * @var int {facetoface}.reservedays
     */
    private $reservedays = 2;
    /**
     * @var int {facetoface}.declareinterest
     */
    private $declareinterest = 0;
    /**
     * @var int {facetoface}.interestonlyiffull
     */
    private $interestonlyiffull = 0;
    /**
     * @var int {facetoface}.allowcancellationsdefault
     */
    private $allowcancellationsdefault  = 1;
    /**
     * @var int {facetoface}.cancellationscutoffdefault
     */
    private $cancellationscutoffdefault  = 86400;
    /**
     * @var int {facetoface}.selectjobassignmentonsignup
     */
    private $selectjobassignmentonsignup  = 0;
    /**
     * @var int {facetoface}.forceselectjobassignment
     */
    private $forceselectjobassignment  = 0;
    /**
     * @var int {facetoface}.approvaltype
     */
    private $approvaltype = 0;
    /**
     * @var int {facetoface}.approvalrole
     */
    private $approvalrole = 0;
    /**
     * @var string {facetoface}.approvalterms
     */
    private $approvalterms = "";
    /**
     * @var string {facetoface}.approvaladmins
     */
    private $approvaladmins = "";
    /**
     * @var int {facetoface}.sessionattendance
     */
    private $sessionattendance = self::SESSION_ATTENDANCE_DEFAULT;
    /**
     * @var int {facetoface}.attendancetime
     */
    private $attendancetime = self::EVENT_ATTENDANCE_DEFAULT;
    /**
     * @var int {facetoface}.eventgradingmanual
     */
    private $eventgradingmanual = 0;
    /**
     * @var int {facetoface}.eventgradingmethod
     */
    private $eventgradingmethod = self::GRADING_METHOD_GRADEHIGHEST;
    /**
     * @var int {facetoface}.completionpass
     */
    private $completionpass = self::COMPLETION_PASS_DISABLED;
    /**
     * @var int|null {facetoface}.completiondelay
     */
    private $completiondelay = null;
    /**
     * @var int {facetoface}.decluttersessiontable
     */
    private $decluttersessiontable = 0;
    /**
     * @var int {facetoface}.legacy_notifications
     */
    private $legacy_notifications;
    /**
     * @var string facetoface table name
     */
    const DBTABLE = 'facetoface';

    /**
     * Seminar constructor.
     *
     * @param int $id {facetoface}.id If 0 - new Seminar Activity will be created
     */
    public function __construct(int $id = 0) {

        $this->id = $id;
        $this->load();
    }

    /**
     * Load facetoface data from DB
     *
     * @return seminar this
     */
    public function load(): seminar {

        return $this->crud_load();
    }

    /**
     * Save seminar to database
     */
    public function save(): void {

        $this->timemodified = time();

        if (!$this->id) {
            $this->timecreated = time();
        }

        $this->crud_save();
    }

    /**
     * Delete seminar and related items from database
     */
    public function delete(): void {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/mod/facetoface/notification/lib.php'); // facetoface_notification

        $seminarinterests = new interest_list(['facetoface' => $this->get_id()]);
        $seminarinterests->delete();

        $seminarevents = $this->get_events();
        foreach ($seminarevents as $seminarevent) {
            // We are going to full scale deleting seminar event here, so that the event custom assets/facilitators/rooms
            // are going to be gone.
            $seminarevent->delete();
        }

        $notifications = $DB->get_records('facetoface_notification', ['facetofaceid' => $this->get_id()], '', 'id');
        foreach ($notifications as $notification) {
            $notification = new \facetoface_notification(['id' => $notification->id]);
            $notification->delete();
        }

        $DB->delete_records('event', array('modulename' => 'facetoface', 'instance' => $this->get_id()));

        $this->grade_item_delete();

        $DB->delete_records(self::DBTABLE, ['id' => $this->id]);

        // Re-load instance with default values.
        $this->map_object((object)get_object_vars(new self()));
    }

    /**
     * Get seminar events
     * @return seminar_event_list
     */
    public function get_events(): seminar_event_list {
        return seminar_event_list::from_seminar($this);
    }

    /**
     * Return true if a seminar has at least one seminar event
     * @return boolean
     */
    public function has_events(): bool {
        global $DB;
        return $DB->record_exists('facetoface_sessions', ['facetoface' => $this->id]);
    }

    /**
     * Delete grade item for given facetoface
     */
    private function grade_item_delete(): void {
        grade_update('mod/facetoface', $this->course, 'mod', 'facetoface', $this->id, 0, NULL, ['deleted' => 1]);
    }

    /**
     * Does this seminar require approval of any kind
     * Notice: If seminar required approval, it doesn't mean that signup will require approval, use state of signup to determine it
     * @return bool
     */
    public function is_approval_required(): bool {
        return $this->approvaltype == static::APPROVAL_MANAGER
        || $this->approvaltype == static::APPROVAL_ROLE
        || $this->approvaltype == static::APPROVAL_ADMIN;
    }

    /**
     * Check if current seminar approval settings require manager or admin approval.
     * @return bool
     */
    public function is_manager_required(): bool {
        return $this->approvaltype == static::APPROVAL_MANAGER || $this->approvaltype == static::APPROVAL_ADMIN;
    }

    /**
     * Check if current seminar approval settings require role approval.
     * @return bool
     */
    public function is_role_required(): bool {
        return $this->approvaltype == static::APPROVAL_ROLE;
    }

    /**
     * Map data object to seminar instance.
     *
     * @param \stdClass $object
     * @return seminar instance
     */
    public function map_instance(\stdClass $object): seminar {

        if (isset($object->sessionattendance) && !in_array($object->sessionattendance, self::SESSION_ATTENDANCE_VALID_VALUES)) {
            debugging("The session attendance value {$object->sessionattendance} is not valid.");
        }
        if (isset($object->attendancetime) && !in_array($object->attendancetime, self::EVENT_ATTENDANCE_VALID_VALUES)) {
            debugging("The event attendance value {$object->attendancetime} is not valid.");
        }
        return $this->map_object($object);
    }

    /**
     * Map seminar instance properties to data object.
     *
     * @return \stdClass
     */
    public function get_properties(): \stdClass {

        return $this->unmap_object();
    }

    /**
     * Check whether the seminar exists yet or not.
     * If the asset has been saved into the database the $id field should be non-zero.
     *
     * @return bool - true if the asset has an $id, false if it hasn't
     */
    public function exists(): bool {
        return !empty($this->id);
    }

    /** Check if the user has any signups that don't have any of the following
     *     not being archived
     *     cancelled by user
     *     declined
     *     session cancelled
     *     status not set
     *
     * @param int $userid
     * @return bool
     */
    public function has_unarchived_signups(int $userid = 0): bool {
        global $DB, $USER;

        $userid = $userid == 0 ? $USER->id : $userid;

        $sql  = "SELECT 1 FROM {facetoface_signups} ftf_sign
               JOIN {facetoface_sessions} sess
                    ON sess.facetoface = :facetofaceid
               JOIN {facetoface_signups_status} sign_stat
                    ON sign_stat.signupid = ftf_sign.id
                    AND sign_stat.superceded <> 1
              WHERE ftf_sign.userid = :userid
                AND ftf_sign.sessionid = sess.id
                AND ftf_sign.archived <> 1
                AND sign_stat.statuscode > :statusdeclined
                AND sign_stat.statuscode <> :statusnotset";
        $params = [
            'facetofaceid' => $this->id,
            'userid' => $userid,
            'statusdeclined' => declined::get_code(),
            'statusnotset' => not_set::get_code(),
        ];

        // Check if user is already signed up to a session in the facetoface and it has not been archived.
        return $DB->record_exists_sql($sql, $params);
    }

    /**
     * Get list of approval admins for current seminar
     * @return array
     */
    public function get_approvaladmins_list(): array {
        if (empty($this->get_approvaladmins())) {
            return [];
        };
        return explode(',', $this->get_approvaladmins());
    }

    /**
     * Return the approval type of a facetoface as a human readable string
     * @return string
     */
    public function get_approvaltype_string(): string {
        switch ($this->approvaltype) {
            case self::APPROVAL_NONE:
                return get_string('approval_none', 'mod_facetoface');
            case self::APPROVAL_SELF:
                return get_string('approval_self', 'mod_facetoface');
            case self::APPROVAL_ROLE:
                $rolenames = role_fix_names(get_all_roles());
                return $rolenames[$this->approvalrole]->localname;
            case self::APPROVAL_MANAGER:
                return get_string('approval_manager', 'mod_facetoface');
            case self::APPROVAL_ADMIN:
                return get_string('approval_admin', 'mod_facetoface');
            default:
                print_error('error:unrecognisedapprovaltype', 'mod_facetoface');
        }
    }

    /**
     * Return course module.
     *
     * @return \stdClass
     */
    public function get_coursemodule(): \stdClass {
        return get_coursemodule_from_instance('facetoface', $this->id, $this->course, false, MUST_EXIST);
    }

    /**
     * Get the context for this seminar.
     *
     * @return context_module
     */
    public function get_context(): context_module {
        return context_module::instance($this->get_coursemodule()->id);
    }

    /**
     * Return context module.
     *
     * Don't use this function. Use get_context instead.
     *
     * @param int $cmid course module id
     * @return \context_module
     */
    public function get_contextmodule(int $cmid): \context_module {
        return \context_module::instance($cmid);
    }

    /**
     * @return int
     */
    public function get_id(): int {
        return (int)$this->id;
    }

    /**
     * Get course id
     * There is no course class, so use id
     * @return int
     */
    public function get_course(): int {
        return (int)$this->course;
    }
    /**
     * Set course id
     * There is no course class, so use id
     * @param int $course
     * @return seminar
     */
    public function set_course(int $course): seminar {
        $this->course = $course;
        return $this;
    }
    /**
     * @return string
     */
    public function get_name(): string {
        return format_string($this->name);
    }
    /**
     * @param string $name
     * @return seminar
     */
    public function set_name(string $name): seminar {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function get_intro(): string {
        return (string)$this->intro;
    }
    /**
     * @param string $intro
     * @return seminar
     */
    public function set_intro(string $intro): seminar {
        $this->intro = $intro;
        return $this;
    }

    /**
     * @return int
     */
    public function get_introformat(): int {
        return (int)$this->introformat;
    }
    /**
     * @param int $introformat
     * @return seminar
     */
    public function set_introformat(int $introformat): seminar {
        $this->introformat = $introformat;
        return $this;
    }

    /**
     * @return string
     */
    public function get_thirdparty(): string {
        return (string)$this->thirdparty;
    }
    /**
     * @param string $thirdparty
     * @return seminar
     */
    public function set_thirdparty(string $thirdparty): seminar {
        $this->thirdparty = $thirdparty;
        return $this;
    }

    /**
     * @return int
     */
    public function get_thirdpartywaitlist(): int {
        return (int)$this->thirdpartywaitlist;
    }
    /**
     * @param string $thirdpartywaitlist
     * @return seminar
     */
    public function set_thirdpartywaitlist(string $thirdpartywaitlist): seminar {
        $this->thirdpartywaitlist = $thirdpartywaitlist;
        return $this;
    }

    /**
     * @return bool
     */
    public function get_waitlistautoclean(): bool {
        return (bool)$this->waitlistautoclean;
    }
    /**
     * @param bool $waitlistautoclean
     * @return seminar
     */
    public function set_waitlistautoclean(bool $waitlistautoclean): seminar {
        $this->waitlistautoclean = (int) $waitlistautoclean;
        return $this;
    }

    /**
     * @return int
     */
    public function get_display(): int {
        return (int)$this->display;
    }
    /**
     * @param int $display
     * @return seminar
     */
    public function set_display(int $display): seminar {
        $this->display = $display;
        return $this;
    }

    /**
     * @return int
     */
    public function get_timecreated(): int {
        return (int)$this->timecreated;
    }
    /**
     * @param int $timecreated
     * @return seminar
     */
    public function set_timecreated(int $timecreated): seminar {
        $this->timecreated = $timecreated;
        return $this;
    }

    /**
     * @return int
     */
    public function get_timemodified(): int {
        return (int)$this->timemodified;
    }
    /**
     * @param int $timemodified
     * @return seminar
     */
    public function set_timemodified(int $timemodified): seminar {
        $this->timemodified = $timemodified;
        return $this;
    }

    /**
     * @return string
     */
    public function get_shortname(): string {
        return (string)$this->shortname;
    }
    /**
     * @param string $shortname
     * @return seminar
     */
    public function set_shortname(string $shortname): seminar {
        $this->shortname = $shortname;
        return $this;
    }

    /**
     * @return int
     */
    public function get_showoncalendar(): int {
        return (int)$this->showoncalendar;
    }
    /**
     * @param int $showoncalendar
     * @return seminar
     */
    public function set_showoncalendar(int $showoncalendar): seminar {
        $this->showoncalendar = $showoncalendar;
        return $this;
    }

    /**
     * @return int
     */
    public function get_usercalentry(): int {
        return (int)$this->usercalentry;
    }
    /**
     * @param int $usercalentry
     * @return seminar
     */
    public function set_usercalentry(int $usercalentry): seminar {
        $this->usercalentry = $usercalentry;
        return $this;
    }

    /**
     * Note: saved in the database as multiplesessions,
     *       referred to elsewhere as multiplesignups.
     * @return int
     */
    public function get_multiplesessions(): int {
        return (int)$this->multiplesessions;
    }
    /**
     * Note: saved in the database as multiplesessions,
     *       referred to elsewhere as multiplesignups.
     * @param int $multiplesignups
     * @return seminar
     */
    public function set_multiplesessions(int $multiplesignups): seminar {
        $this->multiplesessions = $multiplesignups;
        return $this;
    }

    /**
     * Group all the state restrictions settings into one array
     * @return string[] An array of attendance classes. Key is code, value is class.
     */
    public function get_multisignup_states(): array {
        $states = [];

        if (!empty($this->multisignupfully)) {
            $states[fully_attended::get_code()] = fully_attended::class;
        }

        if (!empty($this->multisignuppartly)) {
            $states[partially_attended::get_code()] = partially_attended::class;
        }

        if (!empty($this->multisignupnoshow)) {
            $states[no_show::get_code()] = no_show::class;
        }

        if (!empty($this->multisignupunableto)) {
            $states[unable_to_attend::get_code()] = unable_to_attend::class;
        }

        return $states;
    }

    /**
     * Get multiple signup maximum number
     *
     * @return int
     */
    public function get_multisignup_maximum(): int {
        return $this->multisignupmaximum;
    }

    /**
     * @param bool $multisignupfully
     * @return seminar
     */
    public function set_multisignupfully(bool $multisignupfully): seminar {
        $this->multisignupfully = (int)$multisignupfully;
        return $this;
    }

    /**
     * @param bool $multisignuppartly
     * @return seminar
     */
    public function set_multisignuppartly(bool $multisignuppartly): seminar {
        $this->multisignuppartly = (int)$multisignuppartly;
        return $this;
    }

    /**
     * @param bool $multisignupnoshow
     * @return seminar
     */
    public function set_multisignupnoshow(bool $multisignupnoshow): seminar {
        $this->multisignupnoshow = (int)$multisignupnoshow;
        return $this;
    }

    /**
     * @param bool $multisignupunableto
     * @return seminar
     */
    public function set_multisignupunableto(bool $multisignupunableto): seminar {
        $this->multisignupunableto = (int)$multisignupunableto;
        return $this;
    }

    /**
     * @param int $multisignupmaximum
     * @return seminar
     */
    public function set_multisignupmaximum(int $multisignupmaximum): seminar {
        $this->multisignupmaximum = $multisignupmaximum;
        return $this;
    }

    /**
     * @return string
     */
    public function get_completionstatusrequired(): string {
        return (string)$this->completionstatusrequired;
    }
    /**
     * @param string $completionstatusrequired
     * @return seminar
     */
    public function set_completionstatusrequired(string $completionstatusrequired): seminar {
        $this->completionstatusrequired = $completionstatusrequired;
        return $this;
    }

    /**
     * @return int
     */
    public function get_managerreserve(): int {
        return (int)$this->managerreserve;
    }
    /**
     * @param int $managerreserve
     * @return seminar
     */
    public function set_managerreserve(int $managerreserve): seminar {
        $this->managerreserve = $managerreserve;
        return $this;
    }

    /**
     * @return int
     */
    public function get_maxmanagerreserves(): int {
        return (int)$this->maxmanagerreserves;
    }
    /**
     * @param int $maxmanagerreserves
     * @return seminar
     */
    public function set_maxmanagerreserves(int $maxmanagerreserves): seminar {
        $this->maxmanagerreserves = $maxmanagerreserves;
        return $this;
    }

    /**
     * @return int
     */
    public function get_reservecanceldays(): int {
        return (int)$this->reservecanceldays;
    }
    /**
     * @param int $reservecanceldays
     * @return seminar
     */
    public function set_reservecanceldays(int $reservecanceldays): seminar {
        $this->reservecanceldays = $reservecanceldays;
        return $this;
    }

    /**
     * @return int
     */
    public function get_reservedays(): int {
        return (int)$this->reservedays;
    }
    /**
     * @param int $reservedays
     * @return seminar
     */
    public function set_reservedays(int $reservedays): seminar {
        $this->reservedays = $reservedays;
        return $this;
    }

    /**
     * Users can declare interest has 3 options:
     * - Never
     * - Always
     * - When no upcoming events are available for booking
     * if Never: declareinterest value is 0 and interestonlyiffull value is 0
     * if Always: declareinterest value is 1 and interestonlyiffull value is 0
     * if When no upcoming events are available for booking: declareinterest value is 1 and interestonlyiffull value is 1
     * @see mod_form.php for more information
     * @return int
     */
    public function get_declareinterest(): int {
        if ($this->get_interestonlyiffull() == 0 && (int)$this->declareinterest == 0) {
            // 'Never' option.
            return 0;
        } else if ($this->get_interestonlyiffull() == 0 && (int)$this->declareinterest == 1) {
            // 'Always' option.
            return 1;
        } else if ($this->get_interestonlyiffull() == 1 && (int)$this->declareinterest == 1) {
            // 'When no upcoming events are available for booking' option.
            return 2;
        }
        // Should be never happened, but well, something happened.
        return (int)$this->declareinterest;
    }
    /**
     * @param int $declareinterest
     * @return seminar
     */
    public function set_declareinterest(int $declareinterest): seminar {
        $this->declareinterest = $declareinterest;
        return $this;
    }

    /**
     * @return int
     */
    public function get_interestonlyiffull(): int {
        return (int)$this->interestonlyiffull;
    }
    /**
     * @param int $interestonlyiffull
     * @return seminar
     */
    public function set_interestonlyiffull(int $interestonlyiffull): seminar {
        $this->interestonlyiffull = $interestonlyiffull;
        return $this;
    }

    /**
     * @return int
     */
    public function get_allowcancellationsdefault(): int {
        return (int)$this->allowcancellationsdefault;
    }
    /**
     * @param int $allowcancellationsdefault
     * @return seminar
     */
    public function set_allowcancellationsdefault(int $allowcancellationsdefault): seminar {
        $this->allowcancellationsdefault = $allowcancellationsdefault;
        return $this;
    }

    /**
     * @return int
     */
    public function get_cancellationscutoffdefault(): int {
        return (int)$this->cancellationscutoffdefault;
    }
    /**
     * @param int $cancellationscutoffdefault
     * @return seminar
     */
    public function set_cancellationscutoffdefault(int $cancellationscutoffdefault): seminar {
        $this->cancellationscutoffdefault = $cancellationscutoffdefault;
        return $this;
    }

    /**
     * @return int
     */
    public function get_selectjobassignmentonsignup(): int {
        return (int)$this->selectjobassignmentonsignup;
    }
    /**
     * @param int $selectjobassignmentonsignup
     * @return seminar
     */
    public function set_selectjobassignmentonsignup(int $selectjobassignmentonsignup): seminar {
        $this->selectjobassignmentonsignup = $selectjobassignmentonsignup;
        return $this;
    }

    /**
     * @return int
     */
    public function get_forceselectjobassignment(): int {
        return (int)$this->forceselectjobassignment;
    }
    /**
     * @param int $forceselectjobassignment
     * @return seminar
     */
    public function set_forceselectjobassignment(int $forceselectjobassignment): seminar {
        $this->forceselectjobassignment = $forceselectjobassignment;
        return $this;
    }

    /**
     * @return int
     */
    public function get_approvaltype(): int {
        return (int)$this->approvaltype;
    }
    /**
     * @param int $approvaltype
     * @return seminar
     */
    public function set_approvaltype(int $approvaltype): seminar {
        $this->approvaltype = $approvaltype;
        return $this;
    }

    /**
     * @return int
     */
    public function get_approvalrole(): int {
        return (int)$this->approvalrole;
    }
    /**
     * @param int $approvalrole
     * @return seminar
     */
    public function set_approvalrole(int $approvalrole): seminar {
        $this->approvalrole = $approvalrole;
        return $this;
    }

    /**
     * @return string
     */
    public function get_approvalterms(): string {
        return (string)$this->approvalterms;
    }
    /**
     * @param string $approvalterms
     * @return seminar
     */
    public function set_approvalterms(string $approvalterms): seminar {
        $this->approvalterms = $approvalterms;
        return $this;
    }

    /**
     * @return string
     */
    public function get_approvaladmins(): string {
        return (string)$this->approvaladmins;
    }
    /**
     * @param string $approvaladmins
     * @return seminar
     */
    public function set_approvaladmins(string $approvaladmins): seminar {
        $this->approvaladmins = $approvaladmins;
        return $this;
    }

    /**
     * @return int SESSION_ATTENDANCE_xxx
     */
    public function get_sessionattendance(): int {
        return (int)$this->sessionattendance;
    }
    /**
     * @param int $sessionattendance SESSION_ATTENDANCE_xxx
     * @return seminar
     */
    public function set_sessionattendance(int $sessionattendance): seminar {
        if (!in_array($sessionattendance, self::SESSION_ATTENDANCE_VALID_VALUES)) {
            debugging("The session attendance value {$sessionattendance} is not valid.");
        }
        $this->sessionattendance = $sessionattendance;
        return $this;
    }

    /**
     * @return int EVENT_ATTENDANCE_xxx
     */
    public function get_attendancetime(): int {
        return (int)$this->attendancetime;
    }
    /**
     * @param int $attendancetime EVENT_ATTENDANCE_xxx
     * @return seminar
     */
    public function set_attendancetime(int $attendancetime): seminar {
        if (!in_array($attendancetime, self::EVENT_ATTENDANCE_VALID_VALUES)) {
            debugging("The event attendance value {$attendancetime} is not valid.");
        }
        $this->attendancetime = $attendancetime;
        return $this;
    }

    /**
     * @return int 0 or 1
     */
    public function get_eventgradingmanual(): int {
        return (int)$this->eventgradingmanual;
    }
    /**
     * @param int $eventgradingmanual 0 or 1
     * @return seminar
     */
    public function set_eventgradingmanual(int $eventgradingmanual) : seminar {
        $this->eventgradingmanual = $eventgradingmanual;
        return $this;
    }

    /**
     * @return int GRADING_METHOD_xxx
     */
    public function get_eventgradingmethod(): int {
        return (int)$this->eventgradingmethod;
    }
    /**
     * @param int $eventgradingmethod GRADING_METHOD_xxx
     * @return seminar
     */
    public function set_eventgradingmethod(int $eventgradingmethod) : seminar {
        $this->eventgradingmethod = $eventgradingmethod;
        return $this;
    }

    /**
     * @return int COMPLETION_PASS_xxx
     */
    public function get_completionpass(): int {
        return (int)$this->completionpass;
    }
    /**
     * @param int $completionpass COMPLETION_PASS_xxx
     * @return seminar
     */
    public function set_completionpass(int $completionpass) : seminar {
        $this->completionpass = $completionpass;
        return $this;
    }

    /**
     * Get completiondelay setting.
     *
     * @return int|null
     */
    public function get_completiondelay(): ?int {
        if ($this->completiondelay === '' || $this->completiondelay === null) {
            return null;
        } else {
            return (int)$this->completiondelay;
        }
    }
    /**
     * Set completiondelay setting.
     *
     * @param int|null $completiondelay Number of days to delay completion
     * @return seminar
     */
    public function set_completiondelay(?int $completiondelay): seminar {
        $this->completiondelay = $completiondelay;
        return $this;
    }

    /**
     * @return int 0 or 1
     */
    public function get_decluttersessiontable(): int {
        return (int)(bool)$this->decluttersessiontable;
    }
    /**
     * @param int $decluttersessiontable 0 or 1
     * @return seminar
     */
    public function set_decluttersessiontable(int $decluttersessiontable) : seminar {
        $this->decluttersessiontable = (int)(bool)$decluttersessiontable;
        return $this;
    }

    /**
     * Set completion state of seminar.
     * @param int $userid
     * @param int $completionstate
     * @return bool
     */
    public function set_completion(int $userid, int $completionstate): bool {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        $course = new \stdClass();
        $course->id = $this->get_course();
        $completion_info = new \completion_info($course);

        // Check if completion is enabled site-wide.
        if (!$completion_info->is_enabled()) {
            return false;
        }
        // Check if completion is enabled for the course.
        $cm = $this->get_coursemodule();
        if (empty($cm) || !$completion_info->is_enabled($cm)) {
            return false;
        }

        $completion_info->update_state($cm, $completionstate, $userid);
        $completion_info->invalidatecache($course->id, $userid);
        return true;
    }

    /**
     * Checks if user is an admin approver for this seminar or site.
     *
     * @param int $userid
     * @return bool
     */
    public function is_admin_approver(int $userid) : bool {
        // If user is a system approver then return true.
        $sysapprovers = get_users_from_config(
            get_config(
                null,
                'facetoface_adminapprovers'
            ),
            'mod/facetoface:approveanyrequest'
        );
        foreach ($sysapprovers as $sysapprover) {
            if ($sysapprover->id == $userid) {
                return true;
            }
        }

        // If user is activity approver then return true.
        if ($this->approvaladmins !== null && in_array($userid, explode(',', $this->approvaladmins))) {
            return true;
        }

        // If user is a site administrator then return true.
        $admins = array_keys(get_admins());
        if (in_array($userid, $admins)) {
            return true;
        }

        return false;
    }

    /**
     * @return int
     */
    public function get_legacy_notifications(): int {
        return $this->legacy_notifications;
    }

    /**
     * @param int $legacy_notifications
     * @return $this
     */
    public function set_legacy_notifications(int $legacy_notifications): seminar {
        $this->legacy_notifications = $legacy_notifications;

        return $this;
    }

    /**
     * Should notifications be sent vie the legacy system
     * @return bool
     */
    public function use_legacy_notifications(): bool {
        return (bool)$this->legacy_notifications;
    }

    /**
     * Should notifications be sent via the CN system
     * @return bool
     */
    public function use_cn_notifications(): bool {
        return !(bool)$this->legacy_notifications;
    }

    /**
     * Fix up session attendance time value to keep backward compatibility.
     * $sessionattendance used to be a boolean value. (0 or 1)
     * If it is 1 (true), then we need to use EVENT attendance time as session attendance time.
     *
     * @param integer $eventattendance
     * @param boolean|integer $sessionattendance
     * @return integer the up-to-date $sessionattendance
     */
    public static function fix_up_session_attendance_time_with(int $eventattendance, $sessionattendance): int {
        if ($sessionattendance === 0 || $sessionattendance === '0' || $sessionattendance === false || $sessionattendance === '') {
            return self::SESSION_ATTENDANCE_DISABLED;
        }
        if ($sessionattendance === 1 || $sessionattendance === '1' || $sessionattendance === true) {
            switch ($eventattendance) {
                case self::EVENT_ATTENDANCE_LAST_SESSION_END:
                    return self::SESSION_ATTENDANCE_END;
                case self::EVENT_ATTENDANCE_FIRST_SESSION_START:
                    return self::SESSION_ATTENDANCE_START;
                case self::EVENT_ATTENDANCE_UNRESTRICTED:
                    return self::SESSION_ATTENDANCE_UNRESTRICTED;
                default:
                    // EVENT_ATTENDANCE_LAST_SESSION_START is unknown at that time
                    debugging("Unrecognisable event attendance time: {$eventattendance}");
                    return self::SESSION_ATTENDANCE_DISABLED;
            }
        }
        if (!is_numeric($sessionattendance) || !in_array($sessionattendance, self::SESSION_ATTENDANCE_VALID_VALUES)) {
            debugging("The session attendance time {$sessionattendance} is not valid.", DEBUG_DEVELOPER);
            return self::SESSION_ATTENDANCE_DISABLED;
        }
        return (int)$sessionattendance;
    }

    /**
     * Fix up session attendance time value to keep backward compatibility that no one cares.
     *
     * @see seminar::fix_up_session_attendance_time_with for more information.
     * @param boolean|integer $sessionattendance
     * @return integer the up-to-date $sessionattendance
     */
    public function fix_up_session_attendance_time($sessionattendance): int {
        return self::fix_up_session_attendance_time_with($this->get_attendancetime(), $sessionattendance);
    }
}
