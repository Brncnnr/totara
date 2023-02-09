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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core
 */

namespace core\link;

use moodle_url;

/**
 * Validate an url for use as a link.
 *
 * The following validations are applied:
 *  * We only allow https urls.
 *  * The ip of the hostname is resolved and by default we block IPs from internal address ranges
 *  * valid hostnames/ips can be added to an allowlist ($CFG->link_parser_allowed_hosts)
 *  * additional hostnames/ips can be added ot a blocklist ($CFG->link_parser_blocked_hosts)
 *
 * @package core\link
 */
class url_validator {

    /**
     * @var moodle_url
     */
    protected $url;

    /**
     * @param moodle_url $url
     */
    public function __construct(moodle_url $url) {
        $this->url = $url;
    }

    /**
     * Tries to determine the real IP of the host and applies the validations on it
     *
     * @return string|null
     */
    public function get_validated_ip(): ?string {
        // We only allow https urls
        if ($this->url->get_scheme() !== 'https') {
            return null;
        }

        // Could be invalid or a private ip
        $host_or_ip = $this->convert_if_ip_address($this->url->get_host());
        // If the host is invalid or explicitly blocked
        if ($host_or_ip === false || self::is_allowed($host_or_ip) === false) {
            return null;
        }

        $ip = $this->resolve_ip($host_or_ip);
        if (!$ip) {
            return null;
        }

        if (!self::is_allowed_ip($ip)) {
            return null;
        }

        return $ip;
    }

    /**
     * Resolve the IP of the given host. If the host is an IP address it will also return it.
     *
     * @param string $host_or_ip
     * @return string|null
     */
    private function resolve_ip(string $host_or_ip): ?string {
        // Maybe the host is already an ip address
        // Otherwise do a DNS lookup to get the ip
        if (filter_var($host_or_ip, FILTER_VALIDATE_IP)) {
            $ip = $host_or_ip;
        } else {
            $records = dns_get_record($host_or_ip, DNS_A);
            $ip = $records[0]['ip'] ?? null;
        }

        return $ip;
    }

    /**
     * Check whether the given ip address is allowed or not, it will validate it against
     * our block and allow list.
     * Also validate whether the IP address is valid and not in the reserved or private range.
     * This works for IPv4 and IPv6
     *
     * @param string $ip_address
     * @return bool
     */
    private static function is_allowed_ip(string $ip_address): bool {
        $is_allowed = self::is_allowed($ip_address);
        // If we explicitly allow or block react accordingly
        if (is_bool($is_allowed)) {
            return $is_allowed;
        }

        // Disallow private ip
        $valid = filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);

        return $valid !== false;
    }

    /**
     * Check whether the given host or ip address is allowed or not, it will validate it against
     * our block and allow list
     *
     * @param string $host_or_ip
     * @return bool|null
     */
    private static function is_allowed(string $host_or_ip): ?bool {
        global $CFG;

        $blocked = empty($CFG->link_parser_blocked_hosts) ? [] : explode(',', $CFG->link_parser_blocked_hosts);
        if (in_array($host_or_ip, $blocked, true)) {
            return false;
        }

        $allowed = empty($CFG->link_parser_allowed_hosts) ? [] : explode(',', $CFG->link_parser_allowed_hosts);
        if (in_array($host_or_ip, $allowed, true)) {
            return true;
        }

        return null;
    }

    /**
     * Normalise the host: if the host is an IP address (IPv4 or IPv6), then
     * convert it to a IP address, converting [2606:4700:20::681a:ada] to 2606:4700:20::681a:ada to make
     * validation easier
     *
     * @param string $host_or_ip
     * @return string
     */
    private function convert_if_ip_address(string $host_or_ip): string {
        $result = $host_or_ip;

        // It could be a host like [2606:4700:20::681a:ada] so essentially already an ip address
        $match = preg_match("/^\\[([a-z0-9:]*)\\]$/", $host_or_ip, $matches);
        if ($match) {
            $result = $matches[1];
        }

        // Disallow private ip
        $valid = filter_var($result, FILTER_VALIDATE_IP);
        // If this is a valid IP address return it
        if ($valid !== false) {
            return $result;
        }

        return $host_or_ip;
    }

}