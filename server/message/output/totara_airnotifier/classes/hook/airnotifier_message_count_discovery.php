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
 * AirNotifier message count hook
 *
 * This hook is called by the AirNotifier message output plugin, and allows other plugins to set the number
 * of unread messages which a user should see as an app badge on their device.
 *
 * @package message_totara_airnotifier\hook
 */
class airnotifier_message_count_discovery extends \totara_core\hook\base {

    /**
     * Recipient user
     *
     * @var \stdClass user record
     */
    private $recipient;

    /**
     * Total count of unread messages for badge display
     *
     * @var int
     */
    private $count;

    /**
     * The airnotifier_message_count_discovery hook constructor.
     *
     * @param \stdClass $recipient user record
     */
    public function __construct(\stdClass $recipient) {
        $this->recipient = $recipient;
        $this->count = 0;
    }

    /**
     * Gets the user for checking messages
     *
     * @return \stdClass user record
     */
    public function get_user(): \stdClass {
        return $this->recipient;
    }

    /**
     * Gets the total count of unread messages reported for the user
     *
     * @return int
     */
    public function get_count(): int {
        return $this->count;
    }

    /**
     * Adds a number of unread messages to the total count
     *
     * @param int $number
     */
    public function add_to_count(int $number): void {
        $this->count += $number;
    }
}