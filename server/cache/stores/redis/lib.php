<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Redis Cache Store - Main library
 *
 * @package   cachestore_redis
 * @copyright 2013 Adam Durana
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\redis\sentinel;
use core\redis\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Redis Cache Store
 *
 * To allow separation of definitions in Moodle and faster purging, each cache
 * is implemented as a Redis hash.  That is a trade-off between having functionality of TTL
 * and being able to manage many caches in a single redis instance.  Given the recommendation
 * not to use TTL if at all possible and the benefits of having many stores in Redis using the
 * hash configuration, the hash implementation has been used.
 *
 * @copyright   2013 Adam Durana
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cachestore_redis extends cache_store implements cache_is_key_aware, cache_is_lockable,
        cache_is_configurable, cache_is_searchable {
    /**
     * Compressor: none.
     */
    const COMPRESSOR_NONE = 0;

    /**
     * Compressor: PHP GZip.
     */
    const COMPRESSOR_PHP_GZIP = 1;

    /**
     * Compressor: PHP Zstandard.
     */
    const COMPRESSOR_PHP_ZSTD = 2;

    /**
     * Name of this store.
     *
     * @var string
     */
    protected $name;

    /**
     * The definition hash, used for hash key
     *
     * @var string
     */
    protected $hash;

    /**
     * Flag for readiness!
     *
     * @var boolean
     */
    protected $isready = false;

    /**
     * Cache definition for this store.
     *
     * @var cache_definition
     */
    protected $definition = null;

    /** @var int database number */
    protected $database = 0;

    /**
     * Connection to Redis for this store.
     *
     * @var Redis
     */
    protected $redis;

    /**
     * Connection to Redis read replica for this store.
     *
     * @var Redis
     */
    protected $replica;

    /**
     * Serializer for this store.
     *
     * @var int
     */
    protected $serializer;

    /**
     * Compressor for this store.
     *
     * @var int
     */
    protected $compressor = self::COMPRESSOR_NONE;

    /**
     * A flag whether the cache store supposed to use a read replica
     *
     * @var bool
     */
    protected $using_replica = false;

    /**
     * Determines if the requirements for this type of store are met.
     *
     * @return bool
     */
    public static function are_requirements_met() {
        return class_exists('Redis');
    }

    /**
     * Determines if this type of store supports a given mode.
     *
     * @param int $mode
     * @return bool
     */
    public static function is_supported_mode($mode) {
        return ($mode === self::MODE_APPLICATION || $mode === self::MODE_SESSION);
    }

    /**
     * Get the features of this type of cache store.
     *
     * @param array $configuration
     * @return int
     */
    public static function get_supported_features(array $configuration = array()) {
        return self::SUPPORTS_DATA_GUARANTEE + self::DEREFERENCES_OBJECTS + self::IS_SEARCHABLE;
    }

    /**
     * Get the supported modes of this type of cache store.
     *
     * @param array $configuration
     * @return int
     */
    public static function get_supported_modes(array $configuration = array()) {
        return self::MODE_APPLICATION + self::MODE_SESSION;
    }

    /**
     * Constructs an instance of this type of store.
     *
     * @param string $name
     * @param array $configuration
     */
    public function __construct($name, array $configuration = array()) {
        $this->name = $name;
        $this->isready = false;

        if (!self::are_requirements_met()) {
            return;
        }

        if (array_key_exists('serializer', $configuration)) {
            $this->serializer = (int)$configuration['serializer'];
        } else {
            $this->serializer = Redis::SERIALIZER_PHP;
        }

        if (array_key_exists('compressor', $configuration)) {
            $this->compressor = (int)$configuration['compressor'];
        }

        if (isset($configuration['database'])) {
            $this->database = (int)$configuration['database'];
        }

        $sentinelhosts = isset($configuration['sentinelhosts']) ? trim($configuration['sentinelhosts']) : '';
        $sentinelpassword = isset($configuration['sentinelpassword']) ? $configuration['sentinelpassword'] : '';
        $sentinelmaster = isset($configuration['sentinelmaster']) ? $configuration['sentinelmaster'] : '';

        $replica = isset($configuration['read_server']) ? $configuration['read_server'] : '';

        if (!sentinel::is_supported()) {
            $sentinelhosts = '';
        }

        $server = isset($configuration['server']) ? $configuration['server'] : '';
        $password = isset($configuration['password']) ? $configuration['password'] : '';
        $read_password = isset($configuration['read_password']) ? $configuration['read_password'] : '';
        $prefix = isset($configuration['prefix']) ? $configuration['prefix'] : '';

        $redis = null;
        if ($sentinelhosts !== '') {
            if (trim($sentinelmaster) === '') {
                return;
            }
            $redis = sentinel::resolve_master($sentinelhosts, $sentinelpassword, $sentinelmaster, $password);
        } else {
            $hosts = util::parse_redis_hosts($server, true); // Use first host for BC reasons.
            if ($hosts) {
                $host = reset($hosts);
                $redis = new Redis();
                if ($host['port']) {
                    $success = $redis->connect($host['host'], $host['port'], 3);
                } else {
                    // Must be unix socket.
                    $success = $redis->connect($host['host']);
                }
                if ($success) {
                    if ($password !== '') {
                        $success = $redis->auth($password);
                    }
                }
                if (!$success) {
                    $redis = null;
                }
            }
        }

        if (!$redis) {
            return;
        }

        try {
            $this->redis = $redis;
            if ($this->database != 0) {
                $this->redis->select($this->database);
            }
            // If using compressor, serialisation will be done at cachestore level, not php-redis.
            if ($this->compressor == self::COMPRESSOR_NONE) {
                $redis->setOption(Redis::OPT_SERIALIZER, $this->serializer);
            }
            if (!empty($prefix)) {
                $this->redis->setOption(Redis::OPT_PREFIX, $prefix);
            }
            $this->isready = $this->redis->ping();
        } catch (Throwable $e) {
            error_log("Error connecting Redis store '{$this->name}'");
        }

        // Let's attempt to establish connection to a read replica if configured.
        // If something fails along the way we fall back to use master for reads, as it will work
        // We should add some alert or store an error or something. But the failure isn't critical
        if (!empty($replica)) {

            $this->using_replica = true;

            // Let's get a host and a port if provided, socket isn't supported as we are talking about a clustered environment.
            $replica = explode(':', $replica);
            if (!isset($replica[1])) {
                $replica[1] = '6379';
            }

            try {
                $this->replica = new Redis();

                if (!$success = $this->replica->connect($replica[0], $replica[1], 3)) {
                    throw new moodle_exception('Can not connect to redis replica, falling back to use write master');
                }

                if ($read_password !== '') {
                    $this->replica->auth($read_password);
                }

                $this->replica->select($this->database);
                // If using compressor, serialisation will be done at cachestore level, not php-redis.
                if ($this->compressor == self::COMPRESSOR_NONE) {
                    $redis->setOption(Redis::OPT_SERIALIZER, $this->serializer);
                }
                if (!empty($prefix)) {
                    $this->replica->setOption(Redis::OPT_PREFIX, $prefix);
                }

            } catch (Throwable $e) {
                $this->replica = $this->redis;
                error_log("Error connecting Redis store read replica, at '{$replica[0]}:{$replica[1]}' falling back to master '{$this->name}'");
            }
        } else {
            $this->replica = $this->redis;
        }

    }

    /**
     * Get the name of the store.
     *
     * @return string
     */
    public function my_name() {
        return $this->name;
    }

    /**
     * Initialize the store.
     *
     * @param cache_definition $definition
     * @return bool
     */
    public function initialise(cache_definition $definition) {
        $this->definition = $definition;
        $this->hash       = $definition->generate_definition_hash();
        return true;
    }

    /**
     * Determine if the store is initialized.
     *
     * @return bool
     */
    public function is_initialised() {
        return ($this->definition !== null);
    }

    /**
     * Determine if the store is ready for use.
     *
     * @return bool
     */
    public function is_ready() {
        return $this->isready;
    }

    /**
     * Get the value associated with a given key.
     *
     * @param string $key The key to get the value of.
     * @return mixed The value of the key, or false if there is no value associated with the key.
     */
    public function get($key) {
        $value = $this->replica->hGet($this->hash, $key);

        if ($this->compressor == self::COMPRESSOR_NONE) {
            return $value;
        }

        return $this->uncompress($value);
    }

    /**
     * Get the values associated with a list of keys.
     *
     * @param array $keys The keys to get the values of.
     * @return array An array of the values of the given keys.
     */
    public function get_many($keys) {
        $values = $this->replica->hMGet($this->hash, $keys);

        if ($this->compressor == self::COMPRESSOR_NONE) {
            return $values;
        }

        foreach ($values as &$value) {
            $value = $this->uncompress($value);
        }

        return $values;
    }

    /**
     * Set the value of a key.
     *
     * @param string $key The key to set the value of.
     * @param mixed $value The value.
     * @return bool True if the operation succeeded, false otherwise.
     */
    public function set($key, $value) {
        if ($this->compressor != self::COMPRESSOR_NONE) {
            $value = $this->compress($value);
        }

        return ($this->redis->hSet($this->hash, $key, $value) !== false);
    }

    /**
     * Set the values of many keys.
     *
     * @param array $keyvaluearray An array of key/value pairs. Each item in the array is an associative array
     *      with two keys, 'key' and 'value'.
     * @return int The number of key/value pairs successfuly set.
     */
    public function set_many(array $keyvaluearray) {
        $pairs = [];
        foreach ($keyvaluearray as $pair) {
            $key = $pair['key'];
            if ($this->compressor != self::COMPRESSOR_NONE) {
                $pairs[$key] = $this->compress($pair['value']);
            } else {
                $pairs[$key] = $pair['value'];
            }
        }
        if ($this->redis->hMSet($this->hash, $pairs)) {
            return count($pairs);
        }
        return 0;
    }

    /**
     * Delete the given key.
     *
     * @param string $key The key to delete.
     * @return bool True if the delete operation succeeds, false otherwise.
     */
    public function delete($key) {
        return ($this->redis->hDel($this->hash, $key) > 0);
    }

    /**
     * Delete many keys.
     *
     * @param array $keys The keys to delete.
     * @return int The number of keys successfully deleted.
     */
    public function delete_many(array $keys) {
        // Redis needs the hash as the first argument, so we have to put it at the start of the array.
        array_unshift($keys, $this->hash);
        return call_user_func_array(array($this->redis, 'hDel'), $keys);
    }

    /**
     * Purges all keys from the store.
     *
     * @return bool
     */
    public function purge() {
        return ($this->redis->del($this->hash) !== false);
    }

    /**
     * Cleans up after an instance of the store.
     */
    public function instance_deleted() {
        $this->purge();
        $this->redis->close();
        $this->redis = null; // Totara: do not unset declared object properties!

        // Let's see whether we had a read connection
        if ($this->replica !== null) {
            $this->replica->close();
            $this->replica = null;
        }
    }

    /**
     * Determines if the store has a given key.
     *
     * @see cache_is_key_aware
     * @param string $key The key to check for.
     * @return bool True if the key exists, false if it does not.
     */
    public function has($key) {
        return !empty($this->replica->hExists($this->hash, $key));
    }

    /**
     * Determines if the store has any of the keys in a list.
     *
     * @see cache_is_key_aware
     * @param array $keys The keys to check for.
     * @return bool True if any of the keys are found, false none of the keys are found.
     */
    public function has_any(array $keys) {
        foreach ($keys as $key) {
            if ($this->has($key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determines if the store has all of the keys in a list.
     *
     * @see cache_is_key_aware
     * @param array $keys The keys to check for.
     * @return bool True if all of the keys are found, false otherwise.
     */
    public function has_all(array $keys) {
        foreach ($keys as $key) {
            if (!$this->has($key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Tries to acquire a lock with a given name.
     *
     * @see cache_is_lockable
     * @param string $key Name of the lock to acquire.
     * @param string $ownerid Information to identify owner of lock if acquired.
     * @return bool True if the lock was acquired, false if it was not.
     */
    public function acquire_lock($key, $ownerid) {
        return $this->redis->setnx($key, $ownerid);
    }

    /**
     * Checks a lock with a given name and owner information.
     *
     * @see cache_is_lockable
     * @param string $key Name of the lock to check.
     * @param string $ownerid Owner information to check existing lock against.
     * @return mixed True if the lock exists and the owner information matches, null if the lock does not
     *      exist, and false otherwise.
     */
    public function check_lock_state($key, $ownerid) {
        // Leaving here as connection to 'redis' and not read replica
        $result = $this->redis->get($key);
        if ($result === $ownerid) {
            return true;
        }
        if ($result === false) {
            return null;
        }
        return false;
    }

    /**
     * Finds all of the keys being used by this cache store instance.
     *
     * @return array of all keys in the hash as a numbered array.
     */
    public function find_all() {
        return $this->replica->hKeys($this->hash);
    }

    /**
     * Finds all of the keys whose keys start with the given prefix.
     *
     * @param string $prefix
     *
     * @return array List of keys that match this prefix.
     */
    public function find_by_prefix($prefix) {
        $return = [];
        foreach ($this->find_all() as $key) {
            if (strpos($key, $prefix) === 0) {
                $return[] = $key;
            }
        }
        return $return;
    }

    /**
     * Releases a given lock if the owner information matches.
     *
     * @see cache_is_lockable
     * @param string $key Name of the lock to release.
     * @param string $ownerid Owner information to use.
     * @return bool True if the lock is released, false if it is not.
     */
    public function release_lock($key, $ownerid) {
        if ($this->check_lock_state($key, $ownerid)) {
            return ($this->redis->del($key) !== false);
        }
        return false;
    }

    /**
     * Creates a configuration array from given 'add instance' form data.
     *
     * @see cache_is_configurable
     * @param stdClass $data
     * @return array
     */
    public static function config_get_configuration_array($data) {
        return array(
            'sentinelhosts' => $data->sentinelhosts,
            'sentinelmaster' => $data->sentinelmaster,
            'sentinelpassword' => $data->sentinelpassword,
            'server' => $data->server,
            'read_server' => $data->read_server,
            'read_password' => $data->read_password,
            'database' => $data->database,
            'prefix' => $data->prefix,
            'password' => $data->password,
            'serializer' => $data->serializer,
            'compressor' => $data->compressor,
        );
    }

    /**
     * Sets form data from a configuration array.
     *
     * @see cache_is_configurable
     * @param moodleform $editform
     * @param array $config
     */
    public static function config_set_edit_form_data(moodleform $editform, array $config) {
        $data = array();
        $data['sentinelhosts'] = isset($config['sentinelhosts']) ? $config['sentinelhosts'] : '';
        $data['sentinelmaster'] = isset($config['sentinelmaster']) ? $config['sentinelmaster'] : '';
        $data['sentinelpassword'] = isset($config['sentinelpassword']) ? $config['sentinelpassword'] : '';
        $data['server'] = $config['server'];
        $data['read_server'] = $config['read_server'];
        $data['database'] = isset($config['database']) ? $config['database'] : 0;
        $data['prefix'] = isset($config['prefix']) ? $config['prefix'] : '';
        $data['password'] = isset($config['password']) ? $config['password'] : '';
        $data['read_password'] = isset($config['read_password']) ? $config['read_password'] : '';
        if (isset($config['serializer'])) {
            $data['serializer'] = $config['serializer'];
        }
        if (!empty($config['compressor'])) {
            $data['compressor'] = $config['compressor'];
        }
        $editform->set_data($data);
    }


    /**
     * Creates an instance of the store for testing.
     *
     * @param cache_definition $definition
     * @return mixed An instance of the store, or false if an instance cannot be created.
     */
    public static function initialise_test_instance(cache_definition $definition) {
        if (!self::are_requirements_met()) {
            return false;
        }
        $config = get_config('cachestore_redis');
        if (!sentinel::is_supported()) {
            $config->test_sentinelhosts = '';
        }

        if (empty($config->test_sentinelhosts) && empty($config->test_server)) {
            return false;
        }

        $config = (array)$config;
        foreach ($config as $k => $v) {
            unset($config[$k]);
            if (substr($k, 0, 5) !== 'test_') {
                continue;
            }
            $k = substr($k, 5);
            $config[$k] = $v;
        }

        $cache = new cachestore_redis('Redis test', $config);
        $cache->initialise($definition);

        return $cache;
    }

    /**
     * Return configuration to use when unit testing.
     *
     * @return array
     */
    public static function unit_test_configuration() {
        global $DB;

        if (!self::are_requirements_met() || !self::ready_to_be_used_for_testing()) {
            throw new moodle_exception('TEST_CACHESTORE_REDIS_TESTSERVERS not configured, unable to create test configuration');
        }

        return [
            'server' => TEST_CACHESTORE_REDIS_TESTSERVERS,
            'prefix' => $DB->get_prefix(),
            'password' => defined('TEST_CACHESTORE_REDIS_PASSWORD') ? TEST_CACHESTORE_REDIS_PASSWORD : '',
            'read_server' => defined('TEST_CACHESTORE_REDIS_READ_SERVER') ? TEST_CACHESTORE_REDIS_READ_SERVER : '',
            'read_password' => defined('TEST_CACHESTORE_REDIS_READ_PASSWORD') ? TEST_CACHESTORE_REDIS_READ_PASSWORD : '',
        ];

    }

    /**
     * Returns true if this cache store instance is both suitable for testing, and ready for testing.
     *
     * When TEST_CACHESTORE_REDIS_TESTSERVERS is set, then we are ready to be use d for testing.
     *
     * @return bool
     */
    public static function ready_to_be_used_for_testing() {
        return defined('TEST_CACHESTORE_REDIS_TESTSERVERS');
    }

    /**
     * Gets an array of options to use as the serialiser.
     * @return array
     */
    public static function config_get_serializer_options() {
        $options = array(
            Redis::SERIALIZER_PHP => get_string('serializer_php', 'cachestore_redis')
        );

        if (defined('Redis::SERIALIZER_IGBINARY')) {
            $options[Redis::SERIALIZER_IGBINARY] = get_string('serializer_igbinary', 'cachestore_redis');
        }
        return $options;
    }

    /**
     * Check whether the read replica is actually used.
     * We check whether we had read replica configured and whether master and replica objects are different.
     *
     * @return bool
     */
    public function has_read_replica() {
        return $this->using_replica && $this->redis !== $this->replica;
    }

    /**
     * Gets an array of options to use as the compressor.
     *
     * @return array
     */
    public static function config_get_compressor_options() {
        $arr = [
            self::COMPRESSOR_NONE     => get_string('compressor_none', 'cachestore_redis'),
            self::COMPRESSOR_PHP_GZIP => get_string('compressor_php_gzip', 'cachestore_redis'),
        ];

        // Check if the Zstandard PHP extension is installed.
        if (extension_loaded('zstd')) {
            $arr[self::COMPRESSOR_PHP_ZSTD] = get_string('compressor_php_zstd', 'cachestore_redis');
        }

        return $arr;
    }

    /**
     * Compress the given value, serializing it first.
     *
     * @param mixed $value
     * @return string
     */
    private function compress($value) {
        $value = $this->serialize($value);

        switch ($this->compressor) {
            case self::COMPRESSOR_NONE:
                return $value;

            case self::COMPRESSOR_PHP_GZIP:
                return gzencode($value);

            case self::COMPRESSOR_PHP_ZSTD:
                return zstd_compress($value);

            default:
                debugging("Invalid compressor: {$this->compressor}");
                return $value;
        }
    }

    /**
     * Uncompresses (deflates) the data, unserialising it afterwards.
     *
     * @param string $value
     * @return mixed
     */
    private function uncompress($value) {
        if ($value === false) {
            return false;
        }

        switch ($this->compressor) {
            case self::COMPRESSOR_NONE:
                break;
            case self::COMPRESSOR_PHP_GZIP:
                $value = gzdecode($value);
                break;
            case self::COMPRESSOR_PHP_ZSTD:
                $value = zstd_uncompress($value);
                break;
            default:
                debugging("Invalid compressor: {$this->compressor}");
        }

        return $this->unserialize($value);
    }

    /**
     * Serializes the data according to the configured serializer.
     *
     * @param mixed $value
     * @return string
     */
    private function serialize($value) {
        switch ($this->serializer) {
            case Redis::SERIALIZER_NONE:
                return $value;
            case Redis::SERIALIZER_PHP:
                return serialize($value);
            case defined('Redis::SERIALIZER_IGBINARY') && Redis::SERIALIZER_IGBINARY:
                return igbinary_serialize($value);
            default:
                debugging("Invalid serializer: {$this->serializer}");
                return $value;
        }
    }

    /**
     * Unserializes the data according to the configured serializer
     *
     * @param string $value
     * @return mixed
     */
    private function unserialize($value) {
        switch ($this->serializer) {
            case Redis::SERIALIZER_NONE:
                return $value;
            case Redis::SERIALIZER_PHP:
                return unserialize($value);
            case defined('Redis::SERIALIZER_IGBINARY') && Redis::SERIALIZER_IGBINARY:
                return igbinary_unserialize($value);
            default:
                debugging("Invalid serializer: {$this->serializer}");
                return $value;
        }
    }
}
