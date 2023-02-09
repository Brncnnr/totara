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

namespace core\redis;

/**
 * Redis Sentinel utility class.
 *
 * This class uses $CFG->localcachedir directory directly,
 * MUC cannot be used here because this is used during MUC cache init.
 */
final class sentinel {
    /**
     * Are Redis Sentinels supported?
     *
     * @return bool
     */
    public static function is_supported(): bool {
        if (!extension_loaded('redis')) {
            return false;
        }
        $version = phpversion('Redis');
        if (!$version || version_compare($version, '5.0', '<')) {
            return false;
        }
        if (!class_exists('Redis')) {
            return false;
        }
        return true;
    }

    /**
     * Purge last used Sentinel cache.
     */
    public static function purge_cache(): void {
        $cachedir = make_localcache_directory('core_redis/sentinel', false);
        if (!$cachedir) {
            return;
        }
        remove_dir($cachedir, true);
    }

    /**
     * Get sentinels, the last used sentinel is first.
     *
     * NOTE: localcache directory is used for storing of last used Sentinel,
     *       if the cache fails sentinel hosts are tried in the original
     *       configuration order.
     *
     * @param string $sentinelhosts
     * @return array
     */
    protected static function get_sentinels(string $sentinelhosts): array {
        $sentinels = util::parse_sentinel_hosts($sentinelhosts);

        $cachedir = make_localcache_directory('core_redis/sentinel', false);
        if (!$cachedir) {
            return $sentinels;
        }

        $cachefile = $cachedir . '/' . sha1($sentinelhosts);
        if (!file_exists($cachefile)) {
            return $sentinels;
        }

        $value = file_get_contents($cachefile);
        if ($value === false) {
            return $sentinels;
        }

        $sentinel = @json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $sentinels;
        }
        if (!is_array($sentinel) || !isset($sentinel['host']) || !isset($sentinel['port'])) {
            return $sentinels;
        }

        $removed = false;
        foreach ($sentinels as $k => $s) {
            if ($s['host'] === $sentinel['host'] && $s['port'] === $sentinel['port']) {
                unset($sentinels[$k]);
                $removed = true;
            }
        }
        if ($removed) {
            array_unshift($sentinels, $sentinel);
        }

        return $sentinels;
    }

    /**
     * Remember the Sentinel that gave us valid master.
     * This improves performance when asking sentinels next time because
     * we do not have to wait for offline sentinels.
     *
     * @param array $sentinelhosts
     * @param array $sentinels
     */
    protected static function set_primary_sentinel(string $sentinelhosts, array $sentinel): void {
        $cachedir = make_localcache_directory('core_redis/sentinel', false);
        if (!$cachedir) {
            return;
        }

        $cachefile = $cachedir . '/' . sha1($sentinelhosts);
        if (file_exists($cachefile)) {
            unlink($cachefile);
        }

        if (!$sentinelhosts) {
            return;
        }

        $sentinels = util::parse_sentinel_hosts($sentinelhosts);
        if ($sentinels[0]['host'] === $sentinel['host'] && $sentinels[0]['port'] === $sentinel['port']) {
            // First sentinel was used, we will use original order next time.
            return;
        }

        // NOTE: no need to deal with write atomicity here, json_decode deals with incomplete reads in get_sentinels().
        file_put_contents($cachefile, json_encode($sentinel));
        return;
    }

    /**
     * Ask sentinels what is the current master and connect to it.
     *
     * NOTE: Sentinel client logic is described at https://redis.io/topics/sentinel-clients
     *
     * @param string $sentinelhosts comma separate list of Sentinel hosts optionally with ports
     * @param string $sentinelpassword password for Sentinel connections
     * @param string $mastername name of master in sentinel configuration
     * @param string $masterpassword password for connection to Redis masters
     * @param bool $returnhost true means return host array instead of connection
     * @return \Redis|null|array open Redis connection or null if connection failed or host info
     */
    public static function resolve_master(string $sentinelhosts, string $sentinelpassword, string $mastername, string $masterpassword, bool $returnhost = false) {
        if (!self::is_supported()) {
            error_log("Look up of Redis master '$mastername' failed, Sentinels not supported");
            return null;
        }

        $sentinels = self::get_sentinels($sentinelhosts);
        if (!$sentinels) {
            error_log("Look up of Redis master '$mastername' failed, no Sentinels specified");
            return null;
        }

        for ($retry = 0; $retry <= 1; $retry++) {
            foreach ($sentinels as $sentinel) {
                try {
                    // NOTE: RedisSentinel class was introduced in 5.2 but it does not have all necessary features yet.
                    $s = new \Redis();
                    if (!$s->connect($sentinel['host'], $sentinel['port'], 1)) {
                        error_log("Error connecting to Redis Sentinel '{$sentinel['host']}:{$sentinel['port']}' - " . $s->getLastError());
                        continue;
                    }
                    if ($sentinelpassword !== '') {
                        $s->auth($sentinelpassword);
                    }
                    $master = $s->rawCommand('sentinel', 'get-master-addr-by-name', $mastername);
                    $s->close();
                    if (!$master) {
                        error_log("Redis Sentinel '{$sentinel['host']}:{$sentinel['port']} did not return master address for '$mastername'");
                        continue;
                    }
                } catch (\Throwable $e) {
                    error_log("Error connecting to Redis Sentinel '{$sentinel['host']}:{$sentinel['port']}' - " . $e->getMessage());
                    continue;
                }
                $masterhost = $master[0];
                $masterport = $master[1];
                try {
                    $redis = new \Redis();
                    if (!$redis->connect($masterhost, $masterport, 3)) {
                        error_log("Error connecting to Redis master '{$masterhost}:{$masterport}' - " . $redis->getLastError());
                        continue;
                    }
                    if ($masterpassword !== '') {
                        $redis->auth($masterpassword);
                    }
                    if (!$redis->ping()) {
                        error_log("Error connecting to Redis master '{$masterhost}:{$masterport}' - ping failed");
                        $redis->close();
                        continue;
                    }

                    $rinfo = $redis->info('replication');
                    if (isset($rinfo['role']) && $rinfo['role'] !== 'master') {
                        error_log("Error connecting to Redis master '{$masterhost}:{$masterport}' - invalid role '{$rinfo['role']}'");
                        $redis->close();
                        // If non-master is obtained it means something is wrong with Sentinels, let's give it one more attempt.
                        usleep(200000); // Give sentinels 0.2 seconds to catch up.
                        continue 2;
                    }
                } catch (\Throwable $e) {
                    error_log("Error connecting to Redis master '{$masterhost}:{$masterport}' - " . $e->getMessage());
                    continue;
                }
                // We have a winner, use this sentinel as first next time!
                self::set_primary_sentinel($sentinelhosts, $sentinel);

                if ($returnhost) {
                    $redis->close();
                    return ['host' => $masterhost, 'port' => $masterport];
                } else {
                    return $redis;
                }
            }
            // All sentinels tried without success, fail without retry.
            break;
        }

        error_log("Look up of Redis master '$mastername' failed");
        return null;
    }
}
