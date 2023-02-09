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
 * Redis Cache Store - English language strings
 *
 * @package   cachestore_redis
 * @copyright 2013 Adam Durana
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['database'] = 'Database number';
$string['database_help'] = 'Ideally you should specify unique database number for each store.

Different installations should never share one Redis database.';
$string['compressor_none'] = 'No compression.';
$string['compressor_php_gzip'] = 'Use gzip compression.';
$string['compressor_php_zstd'] = 'Use Zstandard compression.';
$string['errordatabasenegative'] = 'Database must be 0 or positive integer.';
$string['pluginname'] = 'Redis';
$string['prefix'] = 'Key prefix';
$string['prefix_help'] = 'This prefix is used for all key names on the Redis server.
* If you only have one Totara instance using this server, you can leave this value default.
* Due to key length restrictions, a maximum of five characters is permitted.';
$string['prefixinvalid'] = 'Invalid prefix. You can only use a-z A-Z 0-9-_.';
$string['read_password'] = 'Read replica password';
$string['read_password_help'] = 'This sets the password of the read replica Redis server.';
$string['read_server'] = 'Read replica server';
$string['read_server_help'] = 'If you have an environment with redis read replicas please specify the replica load-balancer address here';
$string['sentinelhosts'] = 'Sentinel hosts';
$string['sentinelhosts_help'] = 'Redis Sentinel is a high availability solution for Redis.
It provides monitoring, failover and simplifies configuration.

Use a comma separated list of sentinels using format "host1:port,host2:port,host3:port", unix sockets are not supported.

When specified the Server option is not used and Password option is used for master returned from sentinel.';
$string['sentinelmaster'] = 'Sentinel master name';
$string['sentinelmaster_help'] = 'Internal master name in Redis Sentinel configuration';
$string['sentinelpassword'] = 'Sentinel password';
$string['serializer_igbinary'] = 'The igbinary serializer.';
$string['serializer_php'] = 'The default PHP serializer.';
$string['server'] = 'Server';
$string['server_help'] = 'This sets the hostname or IP address of the Redis server to use.';
$string['password'] = 'Password';
$string['password_help'] = 'This sets the password of the Redis server.';
$string['test_database'] = 'Redis test database';
$string['test_database_desc'] = 'Redis test database number on test server.';
$string['test_sentinelhosts'] = 'Redis test Sentinel hosts';
$string['test_sentinelhosts_desc'] = 'Redis Sentinel hosts used for MUC testing.';
$string['test_sentinelmaster'] = 'Redis test Sentinel master name';
$string['test_sentinelmaster_desc'] = 'Redis Sentinel master name used for MUC testing.';
$string['test_sentinelpassword'] = 'Redis test Sentinel password';
$string['test_sentinelpassword_desc'] = 'Redis Sentinel password used for MUC testing.';
$string['test_server'] = 'Redis test server';
$string['test_server_desc'] = 'Redis server to use for testing.';
$string['test_read_server'] = 'Redis read replica test server';
$string['test_read_server_desc'] = 'Redis read replica server to use for testing.';
$string['test_password'] = 'Redis test server password';
$string['test_password_desc'] = 'Redis test server password.';
$string['test_read_password'] = 'Redis read replica password';
$string['test_read_password_desc'] = 'Redis read replica password.';
$string['test_serializer'] = 'Serializer';
$string['test_serializer_desc'] = 'Serializer to use for testing.';
$string['useserializer'] = 'Use serializer';
$string['useserializer_help'] = 'Specifies the serializer to use for serializing.
The valid serializers are Redis::SERIALIZER_PHP or Redis::SERIALIZER_IGBINARY.
The latter is supported only when phpredis is configured with --enable-redis-igbinary option and the igbinary extension is loaded.';
$string['useserializer_warning'] = 'Serializer must not be changed if there is any data in the cache already.';
$string['usecompressor'] = 'Use compressor';
$string['usecompressor_help'] = 'Specifies the compressor to use after serializing. It is done at Totara Cache API level, not at php-redis level.';
