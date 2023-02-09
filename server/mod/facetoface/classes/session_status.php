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

namespace mod_facetoface;
defined('MOODLE_INTERNAL') || die();

use mod_facetoface\exception\session_exception;
use mod_facetoface\signup\state\{attendance_state, not_set};
use mod_facetoface\traits\crud_mapper;
use stdClass;


/**
 * Class session_status
 * @package mod_facetoface
 */
final class session_status {
    use crud_mapper;

    /**
     * @var int {facetoface_signups_dates_status}.id
     */
    private $id = 0;

    /**
     * @var int {facetoface_signups_dates_status}.signupid
     */
    private $signupid = 0;

    /**
     * @var int {facetoface_signups_dates_status}.sessiondateid
     */
    private $sessiondateid = 0;

    /**
     * @var int {facetoface_signups_dates_status}.attendancecode
     */
    private $attendancecode = 0;

    /**
     * @var int {facetoface_signups_dates_status}.superceded
     */
    private $superceded = 0;

    /**
     * @var int {facetoface_signups_dates_status}.createdby
     */
    private $createdby = 0;

    /**
     * @var int {facetoface_signups_dates_status}.timecreated
     */
    private $timecreated = 0;

    /**
     * Related to the original signup of event.
     * @var signup|null
     */
    private $signup = null;

    const DBTABLE = 'facetoface_signups_dates_status';

    /**
     * Session_status constructor.
     *
     * @param int $id
     */
    public function __construct(int $id = 0) {
        $this->id = $id;
        $this->load();
    }

    /**
     * Session status reader.
     *
     * @return session_status
     */
    public function load(): session_status {
        return $this->crud_load();
    }

    /**
     * Load signup record.
     *
     * @return signup
     */
    public function get_signup(): signup {
        if (null == $this->signup) {
            $this->signup = new signup($this->signupid);
        }

        return $this->signup;
    }

    /**
     * Set attendance status on session.
     *
     * @param string $state
     * @return session_status
     */
    public function set_attendance_status(string $state): session_status {
        if (!class_exists($state)) {
            throw new session_exception(
                "Unable to set attendance status to '{$state}' because class '{$state}' does not exist"
            );
        } else if (!in_array(attendance_state::class, class_parents($state))) {
            if (!$state == not_set::class) {
                // If the state is a not_set state, then it is okay to save it. Otherwise, throwing exception here,
                // because, session status should accept attendance_status only.
                throw new session_exception(
                    "Unable to set attendance status to '{$state}' " .
                    "because '{$state}' is not a child of " . attendance_state::class
                );
            }
        }

        // When changing the status code of the session status, we should probalby reset a few
        // fields here, such as `id` and `superceded` if it is being set.
        $this->id = 0;
        $this->superceded = 0;

        $this->attendancecode = $state::get_code();
        return $this;
    }

    /**
     * Session status object mapper.
     *
     * @param stdClass $record
     * @return session_status
     */
    public static function from_record(stdClass $record): session_status {
        $o = new static();
        $o->map_object($record);
        return $o;
    }

    /**
     * Create the session status object base on the signup and sessiondate id here
     *
     * @param signup $signup
     * @param int $sessiondateid
     * @return session_status
     */
    public static function from_signup(signup $signup, int $sessiondateid): session_status {
        global $DB;
        $record = $DB->get_record(
            static::DBTABLE,
            [
                'signupid' => $signup->get_id(),
                'sessiondateid' => $sessiondateid,
                'superceded' => 0
            ]
        );

        $o = new static();
        $o->signup = $signup;

        if ($record) {
            $o->map_object($record);
        } else {
            // Setting a few things beforehand here, as if the record is not found in db, due to
            // record was not populated before.
            $o->sessiondateid = $sessiondateid;
            $o->signupid = $signup->get_id();
        }

        return $o;
    }

    /**
     * Get session status id.
     *
     * @return int
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Set session status id.
     *
     * @param int $id
     */
    public function set_id(int $id): void {
        $this->id = $id;
    }

    /**
     * Get signup id.
     *
     * @return int
     */
    public function get_signupid(): int {
        return $this->signupid;
    }

    /**
     * Set signup id.
     *
     * @param int $signupid
     * @return session_status
     */
    public function set_signupid(int $signupid): session_status {
        $this->signupid = $signupid;
        return $this;
    }

    /**
     * Get session date record id.
     *
     * @return int
     */
    public function get_sessiondateid(): int {
        return $this->sessiondateid;
    }

    /**
     * Set session date record id.
     *
     * @param int $sessiondateid
     * @return session_status
     */
    public function set_sessiondateid(int $sessiondateid): session_status {
        $this->sessiondateid = $sessiondateid;
        return $this;
    }

    /**
     * Get current attendance code for this session status.
     *
     * @return int
     */
    public function get_attendancecode(): int {
        return $this->attendancecode;
    }

    /**
     * Set attendance code for this session status.
     *
     * @param int $code
     * @return session_status
     */
    public function set_attendancecode(int $code): session_status {
        $this->attendancecode = $code;
        return $this;
    }

    /**
     * Get superceded value for this session status.
     *
     * @return int
     */
    public function get_superceded(): int {
        return $this->superceded;
    }

    /**
     * Set superceded value (1 or 0) for this session status.
     *
     * @param int $superceded
     * @return session_status
     */
    public function set_superceded(int $superceded): session_status {
        $this->superceded = $superceded;
        return $this;
    }

    /**
     * Get session status created-by user id.
     *
     * @return int
     */
    public function get_createdby(): int {
        return $this->createdby;
    }

    /**
     * Set created-by user id for this session status.
     *
     * @param int $createdby
     * @return session_status
     */
    public function set_createdby(int $createdby): session_status {
        $this->createdby = $createdby;
        return $this;
    }

    /**
     * Get created timestamp for this session status.
     *
     * @return int
     */
    public function get_timecreated(): int {
        return $this->timecreated;
    }

    /**
     * Set created by timestamp for this session status.
     *
     * @param int $timecreated
     * @return session_status
     */
    public function set_timecreated(int $timecreated): session_status {
        $this->timecreated = $timecreated;
        return $this;
    }

    /**
     * Session status record saver.
     *
     * @return session_status
     */
    public function save(): session_status {
        global $DB, $USER;
        $this->validate_before_save();

        $trans = $DB->start_delegated_transaction();
        $DB->set_field(
            'facetoface_signups_dates_status',
            'superceded',
            1,
            [
                'signupid' => $this->signupid,
                'sessiondateid' => $this->sessiondateid
            ]
        );

        if (0 == $this->createdby) {
            // If the createdby attribute is not being populated, then we start using the one in session
            $this->createdby = $USER->id;
        }

        if (0 >= $this->timecreated) {
            $this->timecreated = time();
        }

        $this->crud_save();
        $trans->allow_commit();

        return $this;
    }

    /**
     * Make sure session status is ready to save to the database.
     *
     * @return void
     */
    protected function validate_before_save(): void {
        $a = "Cannot update session status";
        if ($this->id && $this->superceded) {
            throw new session_exception("{$a} that was already saved and superceded");
        }

        if (0 == $this->sessiondateid) {
            throw new session_exception("{$a} without session's date id");
        }

        if (0 == $this->signupid) {
            throw new session_exception("{$a} without session's signup id");
        }
    }

    /**
     * Remove all seminar session statuses for the signup.
     *
     * @param signup $signup
     * @return void
     */
    public static function delete_signup(signup $signup): void {
        global $DB;
        $signupid = $signup->get_id();

        if ($signupid) {
            $DB->delete_records(self::DBTABLE, ['signupid' => $signupid]);
        }
    }
}