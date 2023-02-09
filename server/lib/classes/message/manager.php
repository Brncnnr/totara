<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * New messaging manager class.
 *
 * @package   core_message
 * @since     Moodle 2.8
 * @copyright 2014 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */

namespace core\message;

use core\entity\user;
use core\orm\query\builder;
use stdClass;
use context_course;
use moodle_url;
use core_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Class used for various messaging related stuff.
 *
 * Note: Do NOT use directly in your code, it is intended to be used from core code only.
 *
 * @access private
 *
 * @package   core_message
 * @since     Moodle 2.8
 * @copyright 2014 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */
class manager {
    /** @var array buffer of pending messages */
    protected static $buffer = array();

    /** @var array Totara buffers tracking of nested transaction messages */
    protected static $trans_buffers = [];

    /** @var string Totara transaction name */
    protected static $trans_name;

    /**
     * Sending a message that will add the footer for conversation reference to the message.
     *
     * @param message|stdClass $message
     * @param stdClass $savedmessage
     * @param array $processorlist
     *
     * @return int
     * @since Totara 15.0
     */
    public static function send_message_to_conversation(
        $message,
        stdClass $savedmessage,
        array $processorlist
    ): int {
        // Totara: Adding email tag line for the fullmessagehtml and fullmessage.
        $emailtagline = self::get_email_tag_line($message);

        // Using fullmessageformat or default to FORMAT_MOODLE.
        $message_format = FORMAT_MOODLE;
        if (isset($message->fullmessageformat)) {
            $message_format = $message->fullmessageformat;
        }

        if (!empty($message->fullmessagehtml)) {
            // Cleaning up any bad HTML tags. As clean_text will invoke html purifier.
            $message->fullmessagehtml = clean_text($message->fullmessagehtml, $message_format);
            $message->fullmessagehtml .= "<br /><br />---------------------------------------------------------------------<br />" . $emailtagline;
        }

        if (!empty($message->fullmessage)) {
            // Cleaning up any bad HTML tags. As clean_text will invoke html purifier.
            $message->fullmessage = clean_text($message->fullmessage, $message_format);
            $message->fullmessage .= "\n\n---------------------------------------------------------------------\n" . $emailtagline;
        }

        if (!empty($message->smallmessage)) {
            $message->smallmessage = clean_text($message->smallmessage, $message_format);
        }

        return self::send_message($message, $savedmessage, $processorlist);
    }

    /**
     * Returns the email tag line from the event data.
     *
     * @param stdClass|\core\message\message $eventdata
     * @return string
     */
    private static function get_email_tag_line($eventdata): string {
        global $SITE;

        $email_tag_line = "";
        $lang = null;

        if (isset($eventdata->userfrom) && isset($eventdata->userto)) {
            $user_from_id = $eventdata->userfrom;
            $user_to_id = $eventdata->userto;

            if (is_object($eventdata->userfrom)) {
                $user_from_id = $eventdata->userfrom->id;
            }

            if (is_object($eventdata->userto)) {
                $user_to_id = $eventdata->userto->id;
                $lang = $eventdata->userto->lang ?? null;
            }

            if (!core_user::is_real_user($user_from_id) || $user_from_id == $user_to_id) {
                return "";
            }

            if (empty($lang)) {
                $db = builder::get_db();
                $lang = $db->get_field(user::TABLE, "lang", ["id" => $user_to_id], MUST_EXIST);
            }

            $s = new stdClass();
            $s->sitename = format_string($SITE->shortname, true, ["context" => context_course::instance(SITEID)]);
            $s->url = (new moodle_url(
                "/message/index.php",
                [
                    "user" => $user_to_id,
                    "id" =>  $user_from_id
                ]
            ))->out();

            $manager = get_string_manager();
            $email_tag_line = $manager->get_string("emailtagline", "message", $s, $lang);
        }

        return $email_tag_line;
    }

    /**
     * Do the message sending.
     *
     * NOTE: to be used from message_send() only.
     *
     * @todo MDL-55449 Drop support for stdClass in Moodle 3.6
     * @param \core\message\message $eventdata fully prepared event data for processors
     * @param \stdClass $savemessage the message saved in 'message' table
     * @param array $processorlist list of processors for target user
     * @return int $messageid the id from 'messages' (false is not returned)
     */
    public static function send_message($eventdata, \stdClass $savemessage, array $processorlist) {
        global $CFG;

        // TODO MDL-55449 Drop support for stdClass in Moodle 3.6.
        if (!($eventdata instanceof \stdClass) && !($eventdata instanceof message)) {
            // Not a valid object.
            throw new \coding_exception('Message should be of type stdClass or \core\message\message');
        }

        // TODO MDL-55449 Drop support for stdClass in Moodle 3.6.
        if ($eventdata instanceof \stdClass) {
            if (!isset($eventdata->courseid)) {
                $eventdata->courseid = null;
            }

            // TODO TL-13945 - convert all Totara code to use the new message class
            //debugging('eventdata as \stdClass is deprecated. Please use \core\message\message instead.', DEBUG_DEVELOPER);
        }

        require_once($CFG->dirroot.'/message/lib.php'); // This is most probably already included from messagelib.php file.

        if (empty($processorlist)) {
            // Trigger event for sending a message or notification - we need to do this before marking as read!
            if ($eventdata->notification) {
                \core\event\notification_sent::create_from_ids(
                    $eventdata->userfrom->id,
                    $eventdata->userto->id,
                    $savemessage->id,
                    $eventdata->courseid
                )->trigger();
            } else { // Must be a message.
                \core\event\message_sent::create_from_ids(
                    $eventdata->userfrom->id,
                    $eventdata->userto->id,
                    $savemessage->id,
                    $eventdata->courseid
                )->trigger();
            }

            if ($eventdata->notification or empty($CFG->messaging)) {
                // If they have deselected all processors and its a notification mark it read. The user doesn't want to be bothered.
                // The same goes if the messaging is completely disabled.
                if ($eventdata->notification) {
                    $savemessage->timeread = null;
                    \core_message\api::mark_notification_as_read($savemessage);
                } else {
                    \core_message\api::mark_message_as_read($eventdata->userto->id, $savemessage);
                }
            }

            return $savemessage->id;
        }

        // Let the manager do the sending or buffering when db transaction in progress.
        return self::send_message_to_processors($eventdata, $savemessage, $processorlist);
    }

    /**
     * Send message to message processors.
     *
     * @param \stdClass|\core\message\message $eventdata
     * @param \stdClass $savemessage
     * @param array $processorlist
     * @return int $messageid
     */
    protected static function send_message_to_processors($eventdata, \stdClass $savemessage, array
    $processorlist) {
        global $CFG;

        // We cannot communicate with external systems in DB transactions,
        // buffer the messages if necessary.

        if (self::$trans_name !== null) {
            // We need to clone all objects so that devs may not modify it from outside later.
            $eventdata = clone($eventdata);
            $eventdata->userto = clone($eventdata->userto);
            $eventdata->userfrom = clone($eventdata->userfrom);

            // Conserve some memory the same was as $USER setup does.
            unset($eventdata->userto->description);
            unset($eventdata->userfrom->description);

            self::$trans_buffers[self::$trans_name][] = array($eventdata, $savemessage, $processorlist);
            return $savemessage->id;
        }

        foreach ($processorlist as $procname) {
            // Let new messaging class add custom content based on the processor.
            $proceventdata = ($eventdata instanceof message) ? $eventdata->get_eventobject_for_processor($procname) : $eventdata;
            $stdproc = new \stdClass();
            $stdproc->name = $procname;
            $processor = \core_message\api::get_processed_processor_object($stdproc);
            if (!$processor->object->send_message($proceventdata)) {
                debugging('Error calling message processor ' . $procname);
            }
        }

        // Trigger event for sending a message or notification - we need to do this before marking as read!
        if ($eventdata->notification) {
            \core\event\notification_sent::create_from_ids(
                $eventdata->userfrom->id,
                $eventdata->userto->id,
                $savemessage->id,
                $eventdata->courseid
            )->trigger();
        } else { // Must be a message.
            \core\event\message_sent::create_from_ids(
                $eventdata->userfrom->id,
                $eventdata->userto->id,
                $savemessage->id,
                $eventdata->courseid
            )->trigger();
        }

        if (empty($CFG->messaging)) {
            // If they have deselected all processors and its a notification mark it read. The user doesn't want to be bothered.
            // The same goes if the messaging is completely disabled.
            if ($eventdata->notification) {
                $savemessage->timeread = null;
                \core_message\api::mark_notification_as_read($savemessage);
            } else {
                \core_message\api::mark_message_as_read($eventdata->userto->id, $savemessage);
            }
        }

        return $savemessage->id;
    }

    /**
     * Notification from DML layer.
     * @internal to be used from DML layer only.
     * @param string $name
     */
    public static function database_transaction_began(string $name) {
        self::$trans_buffers[$name] = [];
        self::$trans_name = $name;
    }

    /**
     * Notification from DML layer.
     *
     * Note: to be used from DML layer only.
     * @param string $name
     */
    public static function database_transaction_commited(string $name) {
        if ($name !== self::$trans_name or !isset(self::$trans_buffers[$name])) {
            debugging('Invalid transaction commit order detected', DEBUG_DEVELOPER);
            return;
        }

        $lastkey = array_key_last(self::$trans_buffers);
        if ($lastkey !== $name) {
            debugging('Invalid transaction commit order detected', DEBUG_DEVELOPER);
            return;
        }

        if (count(self::$trans_buffers) > 1) {
            $lastbuffer = self::$trans_buffers[$name];
            unset(self::$trans_buffers[$name]);
            $prevname = array_key_last(self::$trans_buffers);
            self::$trans_buffers[$prevname] = array_merge(self::$trans_buffers[$prevname], $lastbuffer);
            self::$trans_name = $prevname;
            return;
        }

        // Move all transaction events to the buffer to get it processed.
        $events = reset(self::$trans_buffers);
        self::$buffer = array_merge(self::$buffer, $events);
        self::$trans_buffers = [];
        self::$trans_name = null;

        self::process_buffer();
    }

    /**
     * Notification from DML layer.
     * Note: to be used from DML layer only.
     *
     * @param string|null $name null means discard the whole buffer
     */
    public static function database_transaction_rolledback(?string $name) {
        if ($name === null) {
            self::$trans_buffers = [];
            self::$trans_name = null;
            return;
        }

        if (!isset(self::$trans_buffers[$name])) {
            debugging('Invalid transaction rollback order detected', DEBUG_DEVELOPER);
            return;
        }

        while (true) {
            $lastname = array_key_last(self::$trans_buffers);
            unset(self::$trans_buffers[$lastname]);
            if ($lastname === $name) {
                break;
            }
        }

        self::$trans_name = array_key_last(self::$trans_buffers);
    }

    /**
     * Sent out any buffered messages if necessary.
     */
    protected static function process_buffer() {
        if (!self::$buffer) {
            return;
        }
        // Reset the buffer first in case we get exception from processor.
        $messages = self::$buffer;
        self::$buffer = array();

        foreach ($messages as $message) {
            list($eventdata, $savemessage, $processorlist) = $message;
            self::send_message_to_processors($eventdata, $savemessage, $processorlist);
        }
    }
}
