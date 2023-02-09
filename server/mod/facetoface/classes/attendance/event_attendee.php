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

namespace mod_facetoface\attendance;

defined('MOODLE_INTERNAL') || die();

/**
 * Provide a convenient way to handle the result of \mod_facetoface\attendance\attendance_helper::get_attendees().
 * Futhermore, this event_attendee class is also being constructed via \mod_facetoface\event_attendee_helper class as
 * it is a class to load attendees within an event.
 *
 * We using casting in getter method, because those properties of this class/object can be null at some point, therefore
 * casting will allow us to return the specific type of that kind of declared type.
 */
final class event_attendee extends \stdClass {

    /** @var int {user}.id */
    public $id = 0;
    /** @var string {user}.username */
    public $username = '';
    /** @var string {user}.idnumber */
    public $idnumber = '';
    /** @var string {user}.email */
    public $email = '';
    /** @var int {user}.deleted */
    public $deleted = 0;
    /** @var int {user}.suspended */
    public $suspended = 0;

    /** @var int {facetoface_signups}.id */
    public $submissionid = 0;
    /** @var int {facetoface_signups}.id */
    public $signupid = 0;
    /** @var int {facetoface_signups}.archived */
    public $archived = 0;
    /** @var int {facetoface_signups}.bookedby */
    public $bookedby;
    /** @var int {facetoface}.id */
    public $facetofaceid = 0;
    /** @var int {faceotface_signups}.jobassignmentid */
    public $jobassignmentid = 0;
    /** @var int {facetoface_sessions}.id */
    public $sessionid = 0;
    /** @var int {course}.id */
    public $course = 0;
    /** @var int {facetoface_signups_status}.statuscode / {facetoface_signups_dates_status}.attendancecode */
    public $statuscode = 0;
    /** @var float|null {facetoface_signups_status}.grade / null */
    public $grade = null;
    /** @var int {facetoface_signups_status}.timecreated / {facetoface_signups_dates_status}.timecreated */
    public $timecreated = 0;

    /** @var int MAX({facetoface_signups_status}.timecreated) with code booked/waitlisted */
    public $timesignedup = 0;

    /** @var string {user}.firstname */
    public $firstname = '';
    /** @var string {user}.lastname */
    public $lastname = '';
    /** @var string {user}.alternatename */
    public $alternatename = '';
    /** @var string {user}.middlename */
    public $middlename = '';
    /** @var string {user}.firstnamephonetic */
    public $firstnamephonetic = '';
    /** @var string {user}.lastnamephonetic */
    public $lastnamephonetic = '';

    /** @var int {facetoface_sessions_dates}.timestart */
    public $timestart = 0;
    /** @var int {facetoface_sessions_dates}.timefinish */
    public $timefinish = 0;
    /** @var int {facetoface_sessions_dates}.sessiontimezone */
    public $sessiontimezone = 0;

    /**
     * Map data object to class instance.
     *
     * @param \stdClass $object an element of an array returned by \mod_facetoface\attendance\attendance_helper::get_attendees()
     * @return event_attendee
     */
    public function from_record(\stdClass $object): event_attendee {
        return $this->map_object($object);
    }

    /**
     * Map data object to class instance.
     *
     * @param \stdClass $object an element of an array returned by \mod_facetoface\attendance\attendance_helper::get_attendees()
     * @return event_attendee new class instance
     * @throws \coding_exception if $object->id is missing or 0
     */
    public static function map_from_record(\stdClass $object): ?event_attendee {
        $self = new static();
        $self->map_object($object);
        if (!$self->is_valid()) {
            throw new \coding_exception(sprintf('Missing user id'));
        }
        return $self;
    }

    /**
     * @see \mod_facetoface\traits\crud_mapper::map_object
     *
     * @param \stdClass $object
     * @return event_attendee
     */
    protected function map_object(\stdClass $object): event_attendee {
        foreach ((array)$object as $property => $value) {
            if (property_exists($this, $property)) {
                $this->{$property} = $value;
            } else {
                debugging("Provided object does not have {$property} field", DEBUG_DEVELOPER);
            }
        }
        return $this;
    }

    /**
     * Return true if the entry is valid.
     *
     * @return boolean
     */
    public function is_valid(): bool {
        return !empty($this->id);
    }

    /**
     * @return int
     */
    public function get_id(): int {
        return (int) $this->id;
    }

    /**
     * @return string
     */
    public function get_username(): string {
        return (string) $this->username;
    }

    /**
     * @return string
     */
    public function get_idnumber(): string {
        return (string) $this->idnumber;
    }

    /**
     * @return string
     */
    public function get_email(): string {
        return (string) $this->email;
    }

    /**
     * @return bool
     */
    public function has_email(): bool {
        return !empty($this->email);
    }

    /**
     * @return boolean
     */
    public function is_deleted(): bool {
        return !empty($this->deleted);
    }

    /**
     * @return boolean
     */
    public function is_suspended(): bool {
        return !empty($this->suspended);
    }

    /**
     * @return integer
     */
    public function get_signupid(): int {
        return (int) $this->submissionid;
    }

    /**
     * @return integer
     */
    public function get_facetofaceid(): int {
        return (int) $this->facetofaceid;
    }

    /**
     * @return integer
     */
    public function get_courseid(): int {
        return (int) $this->course;
    }

    /**
     * @return integer
     */
    public function get_statuscode(): int {
        return (int) $this->statuscode;
    }

    /**
     * @return float|null
     */
    public function get_grade(): ?float {
        return $this->grade;
    }

    /**
     * @return int
     */
    public function get_seminar_id(): int {
        return (int) $this->facetofaceid;
    }

    /**
     * @return int
     */
    public function get_seminar_event_id(): int {
        return (int) $this->sessionid;
    }

    /**
     * @return int
     */
    public function get_job_assignment_id(): int {
        return (int) $this->jobassignmentid;
    }

    /**
     * @return int
     */
    public function get_timecreated(): int {
        return (int) $this->timecreated;
    }

    /**
     * @return int
     */
    public function get_bookedby(): int {
        return (int) $this->bookedby;
    }

    /**
     * @return bool
     */
    public function has_bookedby(): bool {
        return !empty($this->bookedby);
    }

    /**
     * @return int
     */
    public function get_archived(): int {
        return (int)$this->archived;
    }

    /**
     * @return bool
     */
    public function is_archived(): bool {
        return (bool)$this->get_archived();
    }
}