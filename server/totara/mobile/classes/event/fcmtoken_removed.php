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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package totara_mobile
 */

namespace totara_mobile\event;

defined('MOODLE_INTERNAL') || die();

use core\event\base;

class fcmtoken_removed extends base {

    /** @var bool Flag for prevention of direct create() call. */
    protected static $preventcreatecall = true;

    /**
     * Create event from device record.
     *
     * @param   \stdClass $device device record.
     * @return  fcmtoken_removed $event
     */
    public static function create_from_device(\stdClass $device) {
        $data = array(
            'objectid' => $device->id,
            'context' => \context_system::instance(),
            'other' => array(
                'userid' => $device->userid,
                'fcmtoken' => $device->fcmtoken
            )
        );

        self::$preventcreatecall = false;
        $event = self::create($data);
        self::$preventcreatecall = true;

        return $event;
    }

    /**
     * Initialise required event data properties.
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'totara_mobile_devices';
    }

    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_fcmtoken_removed', 'totara_mobile');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return "The FCM push notification token assigned to a device registered to user {$this->other['userid']} has been removed";
    }

    /**
     * Validate data passed to this event.
     *
     */
    protected function validate_data() {
        if (self::$preventcreatecall) {
            throw new \coding_exception('cannot call create() directly, use create_from_device() instead.');
        }

        parent::validate_data();

        // Check userid and fcmtoken are in $other.
        if (!isset($this->other['userid'])) {
            throw new \coding_exception('userid must be set in $other.');
        }
        if (!isset($this->other['fcmtoken'])) {
            throw new \coding_exception('fcmtoken must be set in $other.');
        }
    }
}
