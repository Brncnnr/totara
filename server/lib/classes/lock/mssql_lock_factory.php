<?php
/*
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package core
 */

namespace core\lock;

defined('MOODLE_INTERNAL') || die();

/**
 * MS SQL Server advisory locking factory.
 */
class mssql_lock_factory implements lock_factory {
    /** @var \moodle_database $db Hold a reference to the global $DB needed for shutdown */
    protected $db;

    /** @var string $type Used to prefix lock keys */
    protected $type;

    /** @var array list of locks that need to be released */
    protected $openlocks = array();

    /**
     * Almighty constructor.
     * @param string $type - Used to prefix lock keys.
     */
    public function __construct($type) {
        global $DB;

        $this->type = $type;

        // Save a reference to the global $DB so it will not be released while we still have open locks.
        $this->db = $DB;

        \core_shutdown_manager::register_function(array($this, 'auto_release'));
    }

    /**
     * Is available.
     * @return boolean - True if this lock type is available in this environment.
     */
    public function is_available() {
        return $this->db->get_dbfamily() === 'mssql';
    }

    /**
     * Return information about the blocking behaviour of the lock type on this platform.
     * @return boolean - Defer to the DB driver.
     */
    public function supports_timeout() {
        return true;
    }

    /**
     * Will this lock type will be automatically released when a process ends.
     *
     * @return boolean - Via shutdown handler.
     */
    public function supports_auto_release() {
        return true;
    }

    /**
     * Multiple locks for the same resource can be held by a single process.
     * @return boolean - Defer to the DB driver.
     */
    public function supports_recursion() {
        return true;
    }

    /**
     * Returns name of DB lock.
     *
     * @param string $key
     * @return string
     */
    public function get_lock_name_from_key($key) {
        return $this->db->get_dbname() . ':' . $this->db->get_prefix() . 'dblock_' . $this->type . '_' . $key;
    }

    /**
     * Create and get a lock
     * @param string $key - The identifier for the lock. Should use frankenstyle prefix.
     * @param int $timeout - The number of seconds to wait for a lock before giving up.
     * @param int $maxlifetime - Unused by this lock type.
     * @return boolean|lock - true if a lock was obtained.
     */
    public function get_lock($key, $timeout, $maxlifetime = 86400) {
        $name = $this->get_lock_name_from_key($key);

        $timeout = $timeout * 1000; // Miliseconds are expected.

        $sql = "BEGIN
                    DECLARE @result INT;
                    EXEC @result = sp_getapplock @Resource=?, @LockMode='Exclusive', @LockOwner='Session', @LockTimeout=?;
                    SELECT @result;
                END";
        $locked = $this->db->get_field_sql($sql, [$name, $timeout]);
        if ($locked >= 0) {
            $this->openlocks[$name] = $key;
            return new lock($key, $this);
        }
        return false;
    }

    /**
     * Release a lock that was previously obtained with @lock.
     * @param lock $lock - a lock obtained from this factory.
     * @return boolean - true if the lock is no longer held (including if it was never held).
     */
    public function release_lock(lock $lock) {
        $key = $lock->get_key();
        $name = $this->get_lock_name_from_key($key);

        $sql = "BEGIN
                    DECLARE @result INT;
                    EXEC @result = sp_releaseapplock @Resource=?, @LockOwner='Session';
                    SELECT @result;
                END";
        try {
            $unlocked = $this->db->get_field_sql($sql, [$name]);
        } catch (\dml_read_exception $ex) {
            // Most likely already released.
            $unlocked = 0;
        }
        if ($unlocked >= 0) {
            unset($this->openlocks[$name]);
            return true;
        }

        return false;
    }

    /**
     * Extend a lock that was previously obtained with @lock.
     * @param lock $lock - a lock obtained from this factory.
     * @param int $maxlifetime - the new lifetime for the lock (in seconds).
     * @return boolean - true if the lock was extended.
     */
    public function extend_lock(lock $lock, $maxlifetime = 86400) {
        // Not supported by this factory.
        return false;
    }

    /**
     * Auto release any open locks on shutdown.
     * This is required, because we may be using persistent DB connections.
     */
    public function auto_release() {
        // Called from the shutdown handler. Must release all open locks.
        foreach ($this->openlocks as $name => $key) {
            $lock = new lock($key, $this);
            $lock->release();
            unset($this->openlocks[$name]);
        }
    }
}
