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
 * @author  Petr Skoda <petr.skoda@totaralearning.com>
 * @package core
 */

namespace core\session;

use core\redis\sentinel;

defined('MOODLE_INTERNAL') || die();

final class redis5 extends handler {
    /** @var \Redis|false|null $connection auxiliary connection to redis server */
    private $connection = null;

    // See https://github.com/phpredis/phpredis#php-session-handler for explanation.

    // NOTE: If session lock cannot be obtained in lock_wait_time x lock_retries seconds,
    //       then session is opened and request continues anyway, this may cause session
    //       problems later when the original request reverts the session data, but this should
    //       at least allow the user to save data before restarting the browser.

    /** @var string Redis Sentinel hosts - comma separated list */
    protected $sentinelhosts = '';
    /** @var string Redis Sentinel password */
    protected $sentinelpassword = '';
    /** @var string name of Redis Sentinel master */
    protected $sentinelmaster = '';
    /** @var string $host Redis server host */
    protected $host = '127.0.0.1';
    /** @var int $port port */
    protected $port = 6379;
    /** @var float $timeout connection timeout for connecting of redis host */
    protected $timeout = 5;
    /** @var string $prefix path prefix */
    protected $prefix = 'PHPREDIS_SESSION';
    /** @var string $auth Redis password */
    protected $auth = '';
    /** @var int $database database number */
    protected $database = 0;

    /** @var int $lock_expire How long should the lock live (in seconds)? */
    protected $lock_expire;
    /** @var int $lock_wait_time How long to wait between attempts to acquire lock (in microseconds)? */
    protected $lock_wait_time = 200000; // 0.2 s
    /** @var int $lock_retries Maximum number of times to retry (-1 means infinite). */
    protected $lock_retries = 100; // 0.2s x 100 = 20s max wait for lock by default

    /** @var int $use_igbinary Should we switch to 'igbinary' serializer for sessions? */
    protected $use_igbinary = false;

    /**
     * Create new instance of handler.
     */
    public function __construct() {
        global $CFG;

        if (!extension_loaded('redis')) {
            throw new exception('sessionhandlerproblem', 'error', '', null, 'redis extension is not loaded');
        }

        $version = phpversion('Redis');
        if (!$version || version_compare($version, '5.0', '<')) {
            throw new exception('sessionhandlerproblem', 'error', '', null, 'redis extension version must be 5.0 or higher');
        }

        // Parse all settings, this should be ideally fully compatible with old redis handler settings.

        if (isset($CFG->session_redis5_sentinel_hosts)) {
            $this->sentinelhosts = $CFG->session_redis5_sentinel_hosts;
        }

        if (isset($CFG->session_redis5_sentinel_auth)) {
            $this->sentinelpassword = $CFG->session_redis5_sentinel_auth;
        }

        if (isset($CFG->session_redis5_sentinel_master)) {
            $this->sentinelmaster = $CFG->session_redis5_sentinel_master;
        }

        if (isset($CFG->session_redis5_host) && $CFG->session_redis5_host !== '') {
            // NOTE: linux sockets are not supported
            $this->host = $CFG->session_redis5_host;
        }

        if (!empty($CFG->session_redis5_port)) {
            $this->port = (int)$CFG->session_redis5_port;
        }

        if (!empty($CFG->session_redis5_timeout)) {
            $this->timeout = (int)$CFG->session_redis5_timeout;
        }

        if (isset($CFG->session_redis5_prefix) && $CFG->session_redis5_prefix !== '') {
            $this->prefix = $CFG->session_redis5_prefix;
        }

        if (isset($CFG->session_redis5_auth)) {
            $this->auth = $CFG->session_redis5_auth;
        }

        if (isset($CFG->session_redis5_database)) {
            $this->database = (int)$CFG->session_redis5_database;
        }

        $this->lock_expire = $CFG->sessiontimeout;
        if (isset($CFG->session_redis5_lock_expire)) {
            $this->lock_expire = (int)$CFG->session_redis5_lock_expire;
        }

        if (!empty($CFG->session_redis5_lock_wait_time)) {
            $this->lock_wait_time = (int)$CFG->session_redis5_lock_wait_time;
        }

        if (!empty($CFG->session_redis5_lock_retries)) {
            $this->lock_retries = (int)$CFG->session_redis5_lock_retries;
        }

        if (!empty($CFG->session_redis5_serializer_use_igbinary) && function_exists('igbinary_serialize')) {
            $this->use_igbinary = true;
        }
    }

    /**
     * Start the session.
     *
     * @param bool $uselocking
     * @return bool success
     */
    public function start(bool $uselocking) {
        if (sentinel::is_supported() && $this->sentinelhosts !== '') {
            if (trim($this->sentinelmaster) === '') {
                throw new exception('sessionhandlerproblem', 'error', '', null, 'Missing Redis Sentinel master name');
            }
            $master = sentinel::resolve_master($this->sentinelhosts, $this->sentinelpassword, $this->sentinelmaster, $this->auth, true);
            if (!$master) {
                throw new exception('sessionhandlerproblem', 'error', '', null, 'Cannot obtain host from Redis Sentinel');
            }
            $host = $master['host'];
            $port = $master['port'];
        } else {
            $host = $this->host;
            $port = $this->port;
        }

        // Unix socket is not supported.
        $prefix = urlencode($this->prefix);
        $savepath = "tcp://{$host}:{$port}?timeout={$this->timeout}&prefix={$prefix}&database={$this->database}";
        if ($this->auth !== '') {
            $savepath .= '&auth=' . urlencode($this->auth);
        }

        ini_set('session.save_handler', 'redis');
        ini_set('session.save_path', $savepath);
        ini_set('redis.session.lock_expire', $this->lock_expire);
        ini_set('redis.session.lock_wait_time', $this->lock_wait_time);
        ini_set('redis.session.lock_retries', $this->lock_retries);

        if ($uselocking) {
            ini_set('redis.session.locking_enabled', '1');
        } else {
            ini_set('redis.session.locking_enabled', '0');
        }

        if ($this->use_igbinary) {
            ini_set('session.serialize_handler', 'igbinary');
        }

        // Make sure admins did not break things by forcing PHP settings in web server config.
        if (ini_get('session.save_handler') !== 'redis') {
            error_log("Error setting 'session.save_handler' handler to 'redis', Totara sessions will not work properly");
        }
        if (ini_get('session.save_path') !== $savepath) {
            error_log("Error setting 'session.save_path', Totara sessions will not work properly");
        }

        return parent::start($uselocking);
    }

    /**
     * Init session handler.
     */
    public function init() {
        // Nothing to do here.
    }

    /**
     * Get auxiliary Redis server connection.
     *
     * NOTE: aux connection failure is not a fatal problem,
     *       main purpose is to remove unused data to free memory.
     *
     * @return \Redis|false
     */
    protected function get_aux_connection() {
        if (isset($this->connection)) {
            return $this->connection;
        }

        $redis = null;
        if (sentinel::is_supported() && $this->sentinelhosts !== '') {
            if (trim($this->sentinelmaster) === '') {
                debugging('Unable to connect to host using Redis Sentinel - missing master name.', DEBUG_DEVELOPER);
                $this->connection = false;
                return false;
            }
            $redis = sentinel::resolve_master($this->sentinelhosts, $this->sentinelpassword, $this->sentinelmaster, $this->auth, false);
            if (!$redis) {
                debugging('Unable to connect to host using Redis Sentinel.', DEBUG_DEVELOPER);
                $this->connection = false;
                return false;
            }
        } else {
            $redis = new \Redis();
            if ($this->auth !== '') {
                $redis->auth($this->auth);
            }
            if (!$redis->connect($this->host, $this->port, $this->timeout, null)) {
                debugging('Unable to connect to Redis host.', DEBUG_DEVELOPER);
                $this->connection = false;
                return false;
            }
        }

        $this->connection = $redis;
        try {
            if (!$this->connection->setOption(\Redis::OPT_PREFIX, $this->prefix)) {
                debugging('Unable to set prefix for Redis sessions.', DEBUG_DEVELOPER);
                $this->connection = false;
                return $this->connection;
            }
            if (!$this->connection->select($this->database)) {
                debugging('Unable to set database for Redis sessions.', DEBUG_DEVELOPER);
                $this->connection = false;
                return $this->connection;
            }
            if ('test' !== $this->connection->ping('test')) {
                debugging('Unable to ping Redis sessions server.', DEBUG_DEVELOPER);
                $this->connection = false;
                return $this->connection;
            }
        } catch (\Throwable $e) {
            debugging('Redis connection problem: ' . $e->getMessage(), DEBUG_DEVELOPER);
            $this->connection = false;
            return $this->connection;
        }

        return $this->connection;
    }

    /**
     * Check the backend contains data for this session id.
     *
     * Note: this is intended to be called from manager::session_exists() only.
     *
     * @param string $sid
     * @return bool true if session found.
     */
    public function session_exists($sid) {
        $connection = $this->get_aux_connection();
        if (!$connection) {
            return false;
        }

        try {
            return !empty($connection->exists($sid));
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Kill all active sessions, the core sessions table is
     * purged afterwards.
     */
    public function kill_all_sessions() {
        global $DB;

        $connection = $this->get_aux_connection();
        if (!$connection) {
            return;
        }

        $rs = $DB->get_recordset('sessions', array(), 'id DESC', 'id, sid');
        foreach ($rs as $record) {
            try {
                $connection->del($record->sid);
            } catch (\Throwable $e) {
                // Ignore errors.
            }
        }
        $rs->close();
    }

    /**
     * Kill one session, the session record is removed afterwards.
     *
     * @param string $sid
     */
    public function kill_session($sid) {
        $connection = $this->get_aux_connection();
        if (!$connection) {
            return;
        }

        try {
            $connection->del($sid);
        } catch (\Throwable $e) {
            // Ignore errors.
        }
    }

    /**
     * Does this handler support both locking and non-locking sessions?
     *
     * @return bool
     */
    public function is_locking_configurable(): bool {
        return true;
    }

    /**
     * Destructor.
     */
    public function __destruct() {
        if ($this->connection) {
            try {
                $this->connection->close();
            } catch (\Exception $e) {
            }
            $this->connection = null;
        }
    }
}
