<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author  Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\virtualmeeting\dto;

use coding_exception;
use core\plugininfo\virtualmeeting as virtualmeeting_plugininfo;
use totara_core\entity\virtual_meeting;
use totara_core\virtualmeeting\exception\auth_exception;
use totara_core\virtualmeeting\storage;
use totara_core\virtualmeeting\user_auth;

/**
 * A simple readonly object to transfer data from one service to another.
 *
 * This avoids a full entity to be passed down via hooks to watchers we might not
 * have control over.
 *
 * @property-read int $id virtualmeeting id
 * @property-read int $userid totara user id
 * @property-read user_auth $user user authentication
 *
 * @internal Do *NOT* instantiate this class
 */
class meeting_dto {
    /** @var int */
    protected $id;

    /** @var int */
    protected $userid;

    /** @var user_auth */
    protected $userauth;

    /** @var storage */
    protected $storage;

    /**
     * Constructor.
     *
     * @param virtual_meeting $entity
     */
    public function __construct(virtual_meeting $entity) {
        if (!$entity->exists() || !$entity->id) {
            throw new coding_exception('entity does not exist');
        }
        if (!$entity->userid) {
            throw new coding_exception('userid cannot be empty');
        }
        $this->id = $entity->id;
        $this->userid = $entity->userid;
        $this->userauth = user_auth::load($entity->plugin, $entity->user);
        $this->storage = new storage($entity);
    }

    /**
     * Get meeting instance id
     *
     * @return int
     * @internal Do *NOT* call me!!
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Get hosting user id
     *
     * @return integer
     */
    public function get_userid(): int {
        return $this->userid;
    }

    /**
     * Get hosting user
     * NOTE: For app auth, this function always throws auth_exception
     *
     * @return user_auth
     * @throws auth_exception thrown when a user is not authenticated by the virtual meeting provider
     */
    public function get_user(): user_auth {
        if ($this->userauth === null) {
            throw new auth_exception('user is not authorised');
        }
        return $this->userauth;
    }

    /**
     * Get storage space
     *
     * @return storage
     */
    public function get_storage(): storage {
        return $this->storage;
    }

    /**
     * @param string $name
     * @return mixed
     * @codeCoverageIgnore
     */
    final public function __get(string $name) {
        $getter_name = "get_{$name}";
        if (method_exists($this, $getter_name)) {
            return $this->{$getter_name}();
        }

        throw new coding_exception('Unknown getter method for '.$name);
    }

    /**
     * @param string $name
     * @param mixed $value
     * @codeCoverageIgnore
     */
    final public function __set(string $name, $value) {
        throw new coding_exception('This dto is ready-only and cannot be modified');
    }

    /**
     * @param string $name
     * @return boolean
     * @codeCoverageIgnore
     */
    final public function __isset(string $name) {
        return method_exists($this, "get_{$name}");
    }
}
