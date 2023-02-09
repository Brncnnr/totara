<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 Mind Click Limited
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
 * @copyright  2015 Mind Click Limited <http://mind-click.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Joby Harding <joby@77gears.com>
 * @package    totara_message
 */
namespace totara_message\event;

use core\entity\notification;
use core_user;
use context_system;
use core\event\base;
use stdClass;
use coding_exception;
use totara_message\entity\message_metadata;

defined('MOODLE_INTERNAL') || die();

/**
 * Class alert_sent
 *
 * @package totara_message
 */
class alert_sent extends base {

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
        $this->data['objecttable'] = 'message_metadata';
    }

    /**
     * Implements get_name().
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventalertsent', 'totara_message');
    }

    /**
     * Implements get_description().
     *
     * @return string
     */
    public function get_description(): string {

        if (core_user::is_real_user($this->userid)) {
            $description  = "The user with id '{$this->userid}' sent an alert of the type '{$this->other['msgtype']}'";
            $description .= " to the user with id '{$this->relateduserid}'.";

            return $description;
        }

        $description  = "An alert of type '{$this->other['msgtype']}' was sent by the system";
        $description .= " to the user with id '{$this->relateduserid}'.";

        return $description;
    }

    /**
     * Create an event instance from given message data.
     *
     * @param stdClass $event_data      Object as returned by tm_insert_metadata().
     * @param int      $message_id
     * @param int|null $processor_id
     *
     * @return alert_sent
     */
    public static function create_from_message_data(stdClass $event_data, int $message_id, ?int $processor_id = null): alert_sent {
        global $DB;

        if (empty($processor_id)) {
            // This is mainly for alert sent. I have no ideas, why this event is sitting
            // in here (partly a core API for totara_alert and totara_task). But for now,
            // we will assume the totara_alert processor by default
            $processor_id = $DB->get_field('message_processors', 'id', ['name' => 'totara_alert']);
        }

        $message = $DB->get_record(notification::TABLE, ['id' => $message_id]);
        $metadata = message_metadata::repository()->find_message_metadata_from_notification_id(
            $message_id,
            $processor_id,
            true
        );

        $message_type = TOTARA_MSG_TYPE_UNKNOWN;
        if (isset($event_data->msgtype)) {
            $message_type = $event_data->msgtype;
        }

        self::$preventcreatecall = false;
        $data = [
            'objectid' => $metadata->id,
            'context' => context_system::instance(),
            'userid' => $message->useridfrom,
            // We are defaulting the related user's id to the same as user sender.
            // However down the line, we are getting the related user from either
            // the event data or the message table
            'relateduserid' => $message->useridfrom,
            'other' => [
                'messageid' => $message_id,
                'processorid' => $processor_id,
                'msgtype' => $message_type
            ]
        ];

        if (isset($message->useridto)) {
            $data['relateduserid'] = $message->useridto;
        } else if (isset($event_data->userto)) {
            if (is_object($event_data->userto)) {
                $data['relateduserid'] = $event_data->userto->id;
            } else {
                $data['relateduserid'] = $event_data->userto;
            }
        } else {
            debugging('Unable to find out the related user id from message or event data', DEBUG_DEVELOPER);
        }

        /** @var alert_sent $event */
        $event = self::create($data);
        self::$preventcreatecall = true;

        $event->add_record_snapshot('message_metadata', $metadata->get_record());
        $event->add_record_snapshot(notification::TABLE, $message);

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
            throw new coding_exception('Cannot call create() directly, use create_from_message_data() instead.');
        }
    }
}
