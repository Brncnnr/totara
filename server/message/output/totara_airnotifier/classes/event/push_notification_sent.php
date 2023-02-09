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
 * @package message_totara_airnotifier
 */

namespace message_totara_airnotifier\event;

use context_system;

defined('MOODLE_INTERNAL') || die();

/**
 * Class alert_sent
 *
 * @package totara_message
 */
class push_notification_sent extends \core\event\base {

    /**
     * @var bool
     */
    protected static $preventcreatecall = true;

    /**
     * Initialise the event data.
     */
    protected function init() {
        $this->data['crud']        = 'c';
        $this->data['edulevel']    = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'course';
    }

    /**
     * Implements get_name().
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_pushnotification_sent', 'message_totara_airnotifier');
    }

    /**
     * Implements get_description().
     *
     * @return string
     */
    public function get_description() {
        // Some messages have msgtype integers, others do not.
        $type = $this->other['msgtype'];
        if (!empty($type)) {
            $type = $this->other['component'] . ':' . $type;
        } else {
            $type = $this->other['component'];
        }

        // Some messages are sent by real users, others are not.
        if (\core_user::is_real_user($this->userid)) {
            $description  = "The user with id '{$this->userid}' sent a push notification of the type '{$type}'";
            $description .= " to all devices registered to the user with id '{$this->relateduserid}'.";
        } else {
            $description = "A push notification of type '{$type}' was sent by the system";
            $description .= " to all devices registered to the user with id '{$this->relateduserid}'.";
        }

        return $description;
    }

    /**
     * Create an event instance from given message data.
     *
     * @param \stdClass $eventdata
     * @return \message_totara_airnotifier\event\push_notification_sent
     */
    public static function create_from_event_data(\stdClass $eventdata) {

        if (empty($eventdata->msgtype)) {
            $eventdata->msgtype = $eventdata->component;
        }

        self::$preventcreatecall = false;
        $event = self::create(
            array(
                'objectid'      => $eventdata->courseid,
                'context'       => context_system::instance(),
                'userid'        => $eventdata->userfrom->id,
                'relateduserid' => $eventdata->userto->id,
                'other'         => array(
                    'component'    => $eventdata->component,
                    'msgtype'      => $eventdata->msgtype,
                ),
            )
        );
        self::$preventcreatecall = true;

        return $event;
    }

    /**
     * Custom validation.
     *
     * @return void
     */
    public function validate_data() {

        parent::validate_data();

        if (self::$preventcreatecall) {
            throw new \coding_exception('Cannot call create() directly, use create_from_message_data() instead.');
        }
    }
}