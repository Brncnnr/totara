<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
 * Copyright (C) 1999 onwards Martin Dougiamas
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
 * @author Piers Harding <piers@catalyst.net.nz>
 * @author Luis Rodrigues
 * @package totara
 * @subpackage message
 */

/**
 * Task message processor - stores the message to be shown using the totara task notification system
 */

global $CFG;
require_once($CFG->dirroot.'/message/output/lib.php');
require_once($CFG->dirroot.'/totara/message/messagelib.php');

use totara_message\event\task_sent;

class message_output_totara_task extends message_output {
    /**
     * Hold onto the task processor id, because the file "/server/admin/cron.php" can send lot of message at once.
     * @var int|null
     */
    private static $processor_id = null;

    /**
     * Returning the processor's id of totara_task, with memory cached.
     * @return int
     */
    public static function get_processor_id(): int {
        global $DB;
        if (empty(static::$processor_id)) {
            static::$processor_id = $DB->get_field(
                'message_processors',
                'id',
                ['name' => 'totara_task'],
                MUST_EXIST
            );
        }

        return static::$processor_id;
    }

    /**
     * Process the task message.
     * @param object $eventdata the event data submitted by the message sender plus $eventdata->savedmessageid
     * @return true if ok, false if error
     */
    public function send_message($eventdata) {
        //prevent users from getting popup notifications of messages to themselves (happens with forum notifications)
        $processor_id = static::get_processor_id();

        // save the metadata
        $messageid = tm_insert_metadata($eventdata, $processor_id);

        if (!empty($messageid)) {
            $event = task_sent::create_from_message_data($eventdata, $messageid, $processor_id);
            $event->trigger();
        }

        return true;
    }

    /**
     * @param  object $user the user object, defaults to $USER.
     * @return bool has the user made all the necessary settings
     * in their profile to allow this plugin to be used.
     */
    public function is_user_configured($user = null) {
        return true;
    }

    /**
     * @param array $preferences
     * @return null|string
     */
    function config_form($preferences) {
        return null;
    }

    /**
     * @param stdClass $form
     * @param array    $preferences
     * @return bool
     */
    public function process_form($form, &$preferences) {
        return true;
    }

    /**
     * @param array $preferences
     * @param int   $userid
     * @return bool
     */
    public function load_data(&$preferences, $userid) {
        return true;
    }
}