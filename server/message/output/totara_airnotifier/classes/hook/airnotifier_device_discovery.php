<?php
/**
 * This file is part of Totara LMS
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package message_totara_airnotifier
 */

namespace message_totara_airnotifier\hook;

defined('MOODLE_INTERNAL') || die();

/**
 * AirNotifier device discovery hook
 *
 * This hook is called by the AirNotifier message output plugin, and allows other plugins to determine which
 * device keys should receive a message, based on the message recipient.
 *
 * @package message_totara_airnotifier\hook
 */
class airnotifier_device_discovery extends \totara_core\hook\base {

    /**
     * Recipient user
     *
     * @var \stdClass user record
     */
    private $recipient;

    /**
     * Device keys belonging to recipient
     *
     * @var array of strings
     */
    private $device_keys;

    /**
     * The airnotifier_device_discovery hook constructor.
     *
     * @param \stdClass $recipient user record
     */
    public function __construct(\stdClass $recipient) {
        $this->recipient = $recipient;
        $this->device_keys = [];
    }

    /**
     * Gets the user for discovering device keys
     *
     * @return \stdClass user record
     */
    public function get_user(): \stdClass {
        return $this->recipient;
    }

    /**
     * Indicates whether any devices have been discovered for the user
     *
     * @return bool
     */
    public function has_devices(): bool {
        return (bool) count($this->device_keys);
    }

    /**
     * Gets a list of discovered device keys
     *
     * @return array
     */
    public function get_device_keys(): array {
        return $this->device_keys;
    }

    /**
     * Adds keys to the list of discovered device keys
     *
     * @param array $keys
     */
    public function add_device_keys(array $keys): void {
        $this->device_keys = array_merge($this->device_keys, array_values($keys));
    }
}