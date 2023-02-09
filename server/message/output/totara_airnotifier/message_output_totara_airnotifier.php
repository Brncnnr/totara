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

use message_totara_airnotifier\hook\airnotifier_device_discovery;
use message_totara_airnotifier\hook\airnotifier_message_count_discovery;
use message_totara_airnotifier\event\push_notification_sent;
use message_totara_airnotifier\airnotifier_client;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/message/output/lib.php');

/**
 * Class message_output_totara_airnotifier sends totara_mobile-style push notifications to Air Notifier server
 */
class message_output_totara_airnotifier extends message_output {

    /**
     * Process the alert message.
     * @param object $eventdata the event data submitted by the message sender plus $eventdata->savedmessageid
     * @return true if ok, false if error
     */
    public function send_message($eventdata) {
        global $DB;

        // Skip any messaging suspended or deleted users.
        if (empty($eventdata->userto) or $eventdata->userto->auth === 'nologin' or $eventdata->userto->suspended or $eventdata->userto->deleted) {
            return true;
        }

        // Make sure tenant is not suspended.
        if ($eventdata->userto->id > 0) {
            $usercontext = context_user::instance($eventdata->userto->id, IGNORE_MISSING);
            if (!empty($usercontext->tenantid)) {
                if ($DB->record_exists('tenant', ['id' => $usercontext->tenantid, 'suspended' => 1])) {
                    return true;
                }
            }
        }

        // use hook to find unread messages count
        $badge_hook = new airnotifier_message_count_discovery($eventdata->userto);
        $badge_hook->execute();

        // use hook to find device id(s)
        $hook = new airnotifier_device_discovery($eventdata->userto);
        $hook->execute();

        // use airnotifier class to push to devices
        $sent = false;
        if ($hook->has_devices()) {
            $message = new stdClass();
            $message->title = $eventdata->subject;
            $message->body = $eventdata->smallmessage;
            // The unread message count from message_popup does not yet include this message, so add 1 for this message.
            // Why? Because message processors are loaded in reverse alphabetical order, see get_message_processors().
            $message->badge_count = $badge_hook->get_count() + 1;
            $sent = airnotifier_client::push($hook->get_device_keys(), $message);
        }

        // trigger event that notification(s) were pushed
        if ($sent) {
            push_notification_sent::create_from_event_data($eventdata)->trigger();
        }

        return true;
    }

    /**
     * Are the message processor's system settings configured?
     *
     * @return bool True if all necessary config settings been entered
     */
    public function is_system_configured() {
        if (
            !empty(get_config(null, 'totara_airnotifier_host')) &&
            !empty(get_config(null, 'totara_airnotifier_appname')) &&
            !empty(get_config(null, 'totara_airnotifier_appcode'))
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns the default message output settings for this output
     *
     * @return int The default settings
     */
    public function get_default_messaging_settings() {
        return MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN + MESSAGE_DEFAULT_LOGGEDOFF;
    }

    /**
     * Are the message processor's user specific settings configured?
     *
     * @param  stdClass $user the user object, defaults to $USER.
     * @return bool True if the user has all necessary settings in their messaging preferences
     */
    public function is_user_configured($user = null) {
        return true;
    }

    /**
     * Following methods are abstract on base class and must simply be implemented here.
     *
     * @inheritDoc
     */
    public function config_form($preferences) {
        return null;
    }

    public function process_form($form, &$preferences) {
        return true;
    }

    public function load_data(&$preferences, $userid) {
        return true;
    }
}
