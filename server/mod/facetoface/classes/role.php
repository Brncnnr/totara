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

defined('MOODLE_INTERNAL') || die();

/**
* Class role represents Seminar event roles
*/
final class role implements seminar_iterator_item {

    use traits\crud_mapper;

    /**
     * @var int {facetoface_session_roles}.id
     */
    private $id = 0;
    /**
     * @var int {facetoface_session_roles}.sessionid
     */
    private $sessionid = 0;
    /**
     * @var int {facetoface_session_roles}.roleid
     */
    private $roleid = 0;
    /**
     * @var int {facetoface_session_roles}.userid
     */
    private $userid = 0;

    /**
     * @var string facetoface_session_roles table name
     */
    const DBTABLE = 'facetoface_session_roles';

    /**
     * Sesseion Roles constructor.
     * @param int $id {facetoface_session_roles}.id If 0 - new session_roles will be created
     */
    public function __construct(int $id = 0) {

        $this->id = $id;
        $this->load();
    }

    /**
     * Load session roles data from DB.
     * 
     * @return role this
     */
    public function load() : role {

        return $this->crud_load();
    }

    /**
     * Create/update {facetoface_session_roles}.record
     */
    public function save() {

        $this->crud_save();
    }

    /**
     * Map data object to class instance.
     *
     * @param \stdClass $object
     * @return role
     */
    public function from_record(\stdClass $object) {

        return $this->map_object($object);
    }

    /**
     * Delete {facetoface_session_roles}.record where id from database
     */
    public function delete() {
        global $DB;

        $DB->delete_records(self::DBTABLE, ['id' => $this->id]);
        // Re-load instance with default values.
        $this->map_object((object)get_object_vars(new self()));
    }

    /**
     * @return int
     */
    public function get_id() : int {
        return (int)$this->id;
    }

    /**
     * @return int
     */
    public function get_sessionid() : int {
        return (int)$this->sessionid;
    }
    /**
     * @param int $sessionid
     * @return role
     */
    public function set_sessionid(int $sessionid) : role {
        $this->sessionid = $sessionid;
        return $this;
    }

    /**
     * @return int
     */
    public function get_roleid() : int {
        return (int)$this->roleid;
    }
    /**
     * @param int $roleid
     * @return role
     */
    public function set_roleid(int $roleid) : role {
        $this->roleid = $roleid;
        return $this;
    }

    /**
     * @return int
     */
    public function get_userid() : int {
        return (int)$this->userid;
    }
    /**
     * @param int $userid
     * @return role
     */
    public function set_userid(int $userid) : role {
        $this->userid = $userid;
        return $this;
    }

    /**
     * Returning true if the record does have associated id with it.
     * @return bool
     */
    public function exists(): bool {
        return !empty($this->id);
    }

    /**
     * If the record was not found in the db, then it is okay to return the object without the associated id. However, it will still
     * set the property for the record obj.
     *
     * @param int $userid
     * @param int $sessionid
     * @param int $roleid
     *
     * @return role
     */
    public static function find_from(int $userid, int $sessionid, int $roleid): role {
        global $DB;

        $params = [
            'userid' => $userid,
            'sessionid' => $sessionid,
            'roleid' => $roleid
        ];

        $record = $DB->get_record(static::DBTABLE, $params);
        $self = new static();

        if (!$record) {
            // Just setting a few properties related first, but do not set the associated id. So that the external usage can check
            // that the record does not exist in the system yet base on associated id.
            $self->userid = $userid;
            $self->sessionid = $sessionid;
            $self->roleid = $roleid;
        } else {
            $self->map_object($record);
        }

        return $self;
    }
}