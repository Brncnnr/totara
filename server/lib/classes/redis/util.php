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
 * Redis utility class.
 */
final class util {
    /**
     * Parse host and port from Redis or Sentinel configuration option.
     *
     * NOTE: ipv6 addresses must be enclosed in square brackets
     *
     * @param string $host
     * @param int $defaultport
     * @param bool $allowsocket
     * @return array with 'host' and 'port' keys, NULL on error
     */
    public static function parse_host(string $host, int $defaultport, bool $allowsocket = false): ?array {
        $host = trim($host);
        if ($host === '') {
            return null;
        }

        if (substr($host, 0, 1) === '/') {
            // Unix socket.
            if ($allowsocket) {
                return ['host' => $host, 'port' => null];
            } else {
                // Socket not supported.
                return null;
            }

        } else if (substr($host, 0, 1) === '[') {
            // This must be ipv6.
            if (preg_match('/^\[([^\] ,]+)\](:(\d+))?$/', $host, $matches)) {
                if (isset($matches[3])) {
                    return ['host' => $matches[1], 'port' => (int)$matches[3]];
                } else {
                    return ['host' => $matches[1], 'port' => $defaultport];
                }
            } else {
                // Invalid host.
                return null;
            }

        } else {
            // Host name of IPv4.
            if (preg_match('/^([^:\[\] ,]+)(:(\d+))?$/', $host, $matches)) {
                if (isset($matches[3])) {
                    return ['host' => $matches[1], 'port' => (int)$matches[3]];
                } else {
                    return ['host' => $matches[1], 'port' => $defaultport];
                }
            } else {
                // Invalid host.
                return null;
            }
        }
    }

    /**
     * Parse Redis hosts configuration from comma separate list
     * with optional ports. Invalid hosts are ignored.
     *
     * @param string $hosts
     * @param bool $allowsocket
     * @return array list of hosts from parse_host()
     */
    public static function parse_redis_hosts(string $hosts, bool $allowsocket = false): array {
        $result = [];
        $hosts = explode(',', $hosts);
        foreach ($hosts as $host) {
            $r = self::parse_host($host, 6379, $allowsocket);
            if (!$r) {
                continue;
            }
            $result[] = $r;
        }
        return $result;
    }

    /**
     * Parse Sentinel hosts configuration from comma separate list
     * with optional ports. Invalid hosts are ignored.
     *
     * @param string $hosts
     * @return array list of hosts from parse_host()
     */
    public static function parse_sentinel_hosts(string $hosts): array {
        $result = [];
        $hosts = explode(',', $hosts);
        foreach ($hosts as $host) {
            $r = self::parse_host($host, 26379, false);
            if (!$r) {
                continue;
            }
            $result[] = $r;
        }
        return $result;
    }
}
