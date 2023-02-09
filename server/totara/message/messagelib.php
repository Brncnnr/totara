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
 * messagelib.php - Contains generic messaging functions for the message system
 */

defined('MOODLE_INTERNAL') || die();

use core_message\api;
use totara_message\entity\message_metadata;
use totara_message\helper;

global $CFG;
require_once($CFG->dirroot . '/message/lib.php');
require_once($CFG->dirroot . '/totara/message/lib.php');
require_once($CFG->dirroot . '/totara/core/lib.php');
require_once($CFG->libdir . '/eventslib.php');

// message status constants
define('TOTARA_MSG_STATUS_UNDECIDED', 0);
define('TOTARA_MSG_STATUS_OK', 1);
define('TOTARA_MSG_STATUS_NOTOK', 2);

// message type constants
define('TOTARA_MSG_TYPE_UNKNOWN', 0);
define('TOTARA_MSG_TYPE_COURSE', 1);
define('TOTARA_MSG_TYPE_FORUM', 2);
define('TOTARA_MSG_TYPE_GRADING', 3);
define('TOTARA_MSG_TYPE_CHAT', 4);
define('TOTARA_MSG_TYPE_LESSON', 5);
define('TOTARA_MSG_TYPE_QUIZ', 6);
define('TOTARA_MSG_TYPE_FACE2FACE', 7);
define('TOTARA_MSG_TYPE_SURVEY', 8);
define('TOTARA_MSG_TYPE_SCORM', 9);
define('TOTARA_MSG_TYPE_LINK', 10);
define('TOTARA_MSG_TYPE_PROGRAM', 11);

// Message email constants (used to override default message processor behaviour):
// Send email via processor as normal (default)
define('TOTARA_MSG_EMAIL_YES', 0);
// Override to prevent the message sending an email (even if the user has asked for it)
define('TOTARA_MSG_EMAIL_NO', 1);
// Prevent the email processor sending an email, but manually send it using email_to_user() directly
// this is useful if you need to send a message and you want the email to have an attachment (which
// is not currently supported by normal messages).
define('TOTARA_MSG_EMAIL_MANUAL', 2);

// message type shortnames
global $TOTARA_MESSAGE_TYPES;
$TOTARA_MESSAGE_TYPES = [
    TOTARA_MSG_TYPE_UNKNOWN => 'unknown',
    TOTARA_MSG_TYPE_COURSE => 'course',
    TOTARA_MSG_TYPE_PROGRAM => 'program',
    TOTARA_MSG_TYPE_FORUM => 'forum',
    TOTARA_MSG_TYPE_GRADING => 'grading',
    TOTARA_MSG_TYPE_CHAT => 'chat',
    TOTARA_MSG_TYPE_LESSON => 'lesson',
    TOTARA_MSG_TYPE_QUIZ => 'quiz',
    TOTARA_MSG_TYPE_FACE2FACE => 'face2face',
    TOTARA_MSG_TYPE_SURVEY => 'survey',
    TOTARA_MSG_TYPE_SCORM => 'scorm',
    TOTARA_MSG_TYPE_LINK => 'link',
];

// list of supported categories
global $TOTARA_MESSAGE_CATEGORIES;
$TOTARA_MESSAGE_CATEGORIES = array_merge(
    array_keys($TOTARA_MESSAGE_TYPES),
    ['facetoface', 'learningplan', 'objective', 'resource']
);

// message urgency constants
define('TOTARA_MSG_URGENCY_LOW', -4);
define('TOTARA_MSG_URGENCY_NORMAL', 0);
define('TOTARA_MSG_URGENCY_URGENT', 4);


/**
 * Called when a message provider wants to send a message.
 * This functions checks the user's processor configuration to send the given type of message,
 * then tries to send it.
 *
 * Required parameter $eventdata structure:
 *  modulename     -
 *  userfrom object the user sending the message
 *  userto object the message recipient
 *  subject string the message subject
 *  fullmessage - the full message in a given format
 *  fullmessageformat  - the format if the full message (FORMAT_MOODLE, FORMAT_HTML, ..)
 *  fullmessagehtml  - the full version (the message processor will choose with one to use)
 *  smallmessage - the small version of the message
 *  contexturl - if this is a alert then you can specify a url to view the event. For example the forum post the user
 *  is being notified of. contexturlname - the display text for contexturl msgstatus - int Message Status see
 *  TOTARA_MSG_STATUS* constants msgtype - int Message Type see TOTARA_MSG_TYPE* constants
 *
 * @param stdClass|object $eventdata information about the message (modulename, userfrom, userto, ...)
 *
 * @return bool|int         Returning false, if the function is having problem to send message/notification. Otherwise,
 *                          the notification/message's id.
 */
function tm_message_send($eventdata) {
    global $CFG, $DB;

    if (empty($CFG->messaging)) {
        // Messaging currently disabled
        return true;
    }

    // Use the correct userfrom based on more general settings.
    $eventdata->userfrom = totara_get_user_from($eventdata->userfrom);
    if (empty($eventdata->userfrom)) {
        $eventdata->userfrom = $eventdata->userto;
    }

    if (is_int($eventdata->userto)) {
        debugging('tm_message_send() userto is a user ID when it should be a user object', DEBUG_DEVELOPER);
        $eventdata->userto = $DB->get_record('user', ['id' => $eventdata->userto], '*', MUST_EXIST);
    }

    if (is_int($eventdata->userfrom)) {
        debugging('tm_message_send() userfrom is a user ID when it should be a user object', DEBUG_DEVELOPER);
        $eventdata->userfrom = $DB->get_record('user', ['id' => $eventdata->userfrom], '*', MUST_EXIST);
    }

    // must have msgtype, urgency and msgstatus
    if (!isset($eventdata->msgstatus)) {
        debugging('tm_message_send() msgstatus not set', DEBUG_DEVELOPER);
        return false;
    }
    if (!isset($eventdata->urgency)) {
        debugging('tm_message_send() urgency not set', DEBUG_DEVELOPER);
        return false;
    }
    if (!isset($eventdata->msgtype)) {
        debugging('tm_message_send() msgtype not set', DEBUG_DEVELOPER);
        return false;
    }

    // map alert to notification
    if (!empty($eventdata->alert)) {
        $eventdata->notification = $eventdata->alert;
    } else if (empty($eventdata->notification)) {
        $eventdata->notification = 0;
    }

    if (!isset($eventdata->smallmessage)) {
        $eventdata->smallmessage = null;
    }

    if (!isset($eventdata->contexturl)) {
        $eventdata->contexturl = null;
    } else if ($eventdata->contexturl instanceof moodle_url) {
        $eventdata->contexturl = $eventdata->contexturl->out();
    }

    if (!isset($eventdata->contexturlname)) {
        $eventdata->contexturlname = null;
    }

    if (!isset($eventdata->timecreated)) {
        $eventdata->timecreated = time();
    }

    //after how long inactive should the user be considered logged off?
    if (isset($CFG->block_online_users_timetosee)) {
        $timetoshowusers = $CFG->block_online_users_timetosee * 60;
    } else {
        $timetoshowusers = 300;//5 minutes
    }

    // Work out if the user is logged in or not
    if ((time() - $timetoshowusers) < (isset($eventdata->userto->lastaccess) ? $eventdata->userto->lastaccess : 0)) {
        $userstate = 'loggedin';
    } else {
        $userstate = 'loggedoff';
    }

    // Most likely all totara messages are outside the course, if not they need to be fixed.
    if (!isset($eventdata->courseid)) {
        $eventdata->courseid = SITEID;
    }
    // call core message processing - this will trigger either output_totara_task output_totara_alert
    $eventdata->savedmessageid = message_send($eventdata);

    if (!$eventdata->savedmessageid || $eventdata->savedmessageid == 0) {
        debugging('Error inserting message: ' . var_export($eventdata, true), DEBUG_DEVELOPER);
        return false;
    }

    return $eventdata->savedmessageid;
}

/**
 * Create the message metadata structure, which contains workflow information
 *
 * @param stdClass|object $eventdata
 * @param int             $processorid
 *
 * @return int  The message id
 */
function tm_insert_metadata($eventdata, int $processorid): int {
    global $DB;

    // check if metadata record already exists (from other message provider alert/task)
    $parameters = [
        'notificationid' => $eventdata->savedmessageid,
        'processorid' => $processorid,
    ];

    if ($DB->record_exists('message_metadata', $parameters)) {
        return $eventdata->savedmessageid;
    }

    // add the metadata record
    $eventdata->onaccept = isset($eventdata->onaccept) ? serialize($eventdata->onaccept) : null;
    $eventdata->onreject = isset($eventdata->onreject) ? serialize($eventdata->onreject) : null;
    $eventdata->oninfo = isset($eventdata->oninfo) ? serialize($eventdata->oninfo) : null;
    $eventdata->msgtype = isset($eventdata->msgtype) ? $eventdata->msgtype : TOTARA_MSG_TYPE_UNKNOWN;
    $eventdata->msgstatus = isset($eventdata->msgstatus) ? $eventdata->msgstatus : TOTARA_MSG_STATUS_UNDECIDED;
    $eventdata->urgency = isset($eventdata->urgency) ? $eventdata->urgency : TOTARA_MSG_URGENCY_NORMAL;

    if (isset($eventdata->icon)) {
        $eventdata->icon = clean_param($eventdata->icon, PARAM_FILE);
    } else {
        $eventdata->icon = 'default';
    }

    $metadata = new stdClass();
    $metadata->notificationid = $eventdata->savedmessageid;
    $metadata->msgtype = $eventdata->msgtype;
    $metadata->msgstatus = $eventdata->msgstatus;
    $metadata->urgency = $eventdata->urgency;
    $metadata->processorid = $processorid;
    $metadata->icon = $eventdata->icon;
    $metadata->onaccept = $eventdata->onaccept;
    $metadata->onreject = $eventdata->onreject;
    $metadata->oninfo = $eventdata->oninfo;
    $DB->insert_record('message_metadata', $metadata);
    return $eventdata->savedmessageid;
}

/**
 * send a alert
 *
 * Required parameter $alert structure:
 *  userfrom object the user sending the message - optional
 *  userto object the message recipient
 *  fullmessage
 *  msgtype
 *  msgstatus
 *  urgency
 *
 * @param stdClass|object $eventdata information about the message (userfrom, userto, ...)
 *
 * @return bool|int Returning false, if the function is having trouble sending the alert,
 *                  otherwise the saved message/notification's id.
 */
function tm_alert_send($eventdata) {
    global $CFG;

    if (empty($CFG->messaging)) {
        // Messaging currently disabled
        return true;
    }

    if (!isset($eventdata->userto)) {
        // cant send without a target user
        debugging('tm_alert_send() userto is not set', DEBUG_DEVELOPER);
        return false;
    }
    (!isset($eventdata->msgtype)) && $eventdata->msgtype = TOTARA_MSG_TYPE_UNKNOWN;
    (!isset($eventdata->msgstatus)) && $eventdata->msgstatus = TOTARA_MSG_STATUS_UNDECIDED;
    (!isset($eventdata->urgency)) && $eventdata->urgency = TOTARA_MSG_URGENCY_NORMAL;
    (!isset($eventdata->sendemail)) && $eventdata->sendemail = TOTARA_MSG_EMAIL_YES;

    $eventdata->component = 'totara_message';
    $eventdata->name = 'alert';

    if (empty($eventdata->subject)) {
        $eventdata->subject = '';
    }
    if (empty($eventdata->fullmessageformat)) {
        $eventdata->fullmessageformat = FORMAT_PLAIN;
    }
    if (empty($eventdata->fullmessagehtml)) {
        $eventdata->fullmessagehtml = nl2br($eventdata->fullmessage);
    }
    $eventdata->notification = 1;

    $string_manager = get_string_manager();

    if (empty($eventdata->contexturl)) {
        $eventdata->contexturl = '';
        $eventdata->contexturlname = '';
    } else {
        $contexturl = $eventdata->contexturl;
        if ($eventdata->contexturl instanceof moodle_url) {
            $contexturl = $eventdata->contexturl->out();
        }

        $lang = (is_object($eventdata->userto) && !empty($eventdata->userto->lang)) ? $eventdata->userto->lang : null;
        $context_link_html = html_writer::empty_tag('br')
            . html_writer::empty_tag('br')
            . $string_manager->get_string('viewdetailshere', 'totara_message', $contexturl, $lang);

        // Don't append the link if it's already appended.
        // This can easily happen if this method is called with the same $eventdata object repeatedly.
        if (strpos($eventdata->fullmessagehtml, $context_link_html) === false) {
            $eventdata->fullmessagehtml .= $context_link_html;
        }
    }

    $result = tm_message_send($eventdata);

    //--------------------------------

    // Manually send the email using email_to_user(). This is necessary in cases where there is an
    // attachment (which cannot be handled by the messaging system)
    // We still should observe their messaging email preferences.

    // We can't handle attachments when logged on
    $alertemailpref = get_user_preferences('message_provider_totara_message_alert_loggedoff', null, $eventdata->userto->id);
    if ($result && strpos($alertemailpref ?? '', 'email') !== false && $eventdata->sendemail == TOTARA_MSG_EMAIL_MANUAL) {
        // Send alert email
        if (empty($eventdata->subject)) {
            $eventdata->subject = strlen($eventdata->fullmessage) > 80 ?
                substr($eventdata->fullmessage, 0, 78) . '...' : $eventdata->fullmessage;
        }

        // Add footer to email in the recipient language explaining how to change email preferences.
        // However, this is only for system users.
        if (core_user::is_real_user($eventdata->userto->id)) {
            $preferencesurl = new moodle_url('/message/edit.php');
            $eventdata->fullmessage .= "\n" .
                $string_manager->get_string(
                    'alertfooter2',
                    'totara_message',
                    $preferencesurl->out(false),
                    $eventdata->userto->lang
                );
            $eventdata->fullmessagehtml .= str_repeat(html_writer::empty_tag('br'), 2) .
                html_writer::empty_tag('hr') .
                $string_manager->get_string(
                    'alertfooter2html',
                    'totara_message',
                    $preferencesurl->out(true),
                    $eventdata->userto->lang
                );
        }

        // Setup some more variables
        $fromaddress = !empty($eventdata->fromaddress) ? $eventdata->fromaddress : '';
        $attachment = !empty($eventdata->attachment) ? $eventdata->attachment : '';
        $attachmentname = !empty($eventdata->attachmentname) ? $eventdata->attachmentname : '';

        $userfrom = !empty($eventdata->fromemailuser) ? $eventdata->fromemailuser : $eventdata->userfrom;

        switch ($eventdata->userto->mailformat) {
            case FORMAT_MOODLE: // 0 is current user preference email plain format value
            case FORMAT_PLAIN:
                $fullmessagehtml = '';
                break;

            default: // FORMAT_HTML
                $fullmessagehtml = format_text($eventdata->fullmessagehtml, FORMAT_HTML);
                break;
        }

        $result = email_to_user(
            $eventdata->userto,
            $userfrom,
            $eventdata->subject,
            html_to_text($eventdata->fullmessage),
            $fullmessagehtml,
            $attachment,
            $attachmentname,
            true,
            $fromaddress
        );
    }

    //---------------------------------

    return $result;
}

/**
 * send a task
 *
 * Required parameter $eventdata structure:
 *  userfrom object the user sending the message - optional
 *  userto object the message recipient
 *  fullmessage
 *
 * @param stdClass|object $eventdata information about the message (userfrom, userto, ...)
 *
 * @return bool|int Returning false if the function is having trouble sending the task.
 *                  Otherwise a saved message/notification's id.
 */
function tm_task_send($eventdata) {
    global $CFG;

    if (empty($CFG->messaging)) {
        // Messaging currently disabled
        return true;
    }

    if (!isset($eventdata->userto)) {
        // cant send without a target user
        debugging('tm_task_send() userto is not set', DEBUG_DEVELOPER);
        return false;
    }
    (!isset($eventdata->msgtype)) && $eventdata->msgtype = TOTARA_MSG_TYPE_UNKNOWN;
    (!isset($eventdata->msgstatus)) && $eventdata->msgstatus = TOTARA_MSG_STATUS_UNDECIDED;
    (!isset($eventdata->urgency)) && $eventdata->urgency = TOTARA_MSG_URGENCY_NORMAL;
    (!isset($eventdata->sendemail)) && $eventdata->sendemail = TOTARA_MSG_EMAIL_YES;
    (!isset($eventdata->onaccept)) && $eventdata->onaccept = null;
    (!isset($eventdata->onreject)) && $eventdata->onreject = null;

    $eventdata->component = 'totara_message';
    $eventdata->name = 'task';

    if (!isset($eventdata->subject)) {
        $eventdata->subject = '';
    }
    if (empty($eventdata->fullmessageformat)) {
        $eventdata->fullmessageformat = FORMAT_HTML;
    }
    if (empty($eventdata->fullmessagehtml)) {
        $eventdata->fullmessagehtml = nl2br($eventdata->fullmessage);
    }
    $eventdata->notification = 1;

    if (!isset($eventdata->contexturl)) {
        $eventdata->contexturl = '';
        $eventdata->contexturlname = '';
    }
    if (!empty($eventdata->contexturl)) {
        $lang = (is_object($eventdata->userto) && !empty($eventdata->userto->lang)) ? $eventdata->userto->lang : null;
        $eventdata->fullmessagehtml .= html_writer::empty_tag('br')
            .html_writer::empty_tag('br')
            .get_string_manager()->get_string('viewdetailshere', 'totara_message', $eventdata->contexturl, $lang);
    }

    return tm_message_send($eventdata);
}

/**
 * send a custom task that initiates a workflow based on
 * the contexturl set
 *
 * Required parameter $eventdata structure:
 *  userfrom object the user sending the message - optional
 *  userto object the message recipient
 *  subject
 *  fullmessage
 * Optional parameter $eventdata structure:
 *  acceptbutton - affirmative action button label text
 *  accepttext - text that goes on the affirmative action screen
 *
 *  example from plan approval:
 *          $event = new stdClass;
 *          $event->userfrom = $learner;
 *          $event->contexturl = $this->get_display_url();
 *          $event->contexturlname = $this->name;
 *          $event->icon = 'learningplan-request.png';
 *          $a = new stdClass;
 *          $a->learner = fullname($learner);
 *          $a->plan = s($this->name);
 *          $event->subject = get_string('plan-request-manager-short', 'totara_plan', $a);
 *          $event->fullmessage = get_string('plan-request-manager-long', 'totara_plan', $a);
 *          $event->acceptbutton = get_string('approve', 'totara_plan').' '.get_string('plan', 'totara_plan');
 *          $event->accepttext = get_string('approveplantext', 'totara_plan');
 *
 *
 * @param stdClass|object $eventdata information about the message (userfrom, userto, ...)
 *
 * @return bool|int Returning false, if the function is having trouble sending the workflow.
 *                  Otherwise the message/notification's id is returned.
 */
function tm_workflow_send($eventdata) {
    global $CFG;

    if (empty($CFG->messaging)) {
        // Messaging currently disabled
        return true;
    }

    if (!isset($eventdata->userto)) {
        // cant send without a target user
        debugging('tm_task_send() userto is not set', DEBUG_DEVELOPER);
        return false;
    }
    $eventdata->msgtype = TOTARA_MSG_TYPE_LINK; // tells us how to treat the display
    (!isset($eventdata->msgstatus)) && $eventdata->msgstatus = TOTARA_MSG_STATUS_UNDECIDED;
    (!isset($eventdata->urgency)) && $eventdata->urgency = TOTARA_MSG_URGENCY_NORMAL;

    $eventdata->component = 'totara_message';
    $eventdata->name = 'task';

    if (!isset($eventdata->subject)) {
        $eventdata->subject = '';
    }
    if (empty($eventdata->fullmessageformat)) {
        $eventdata->fullmessageformat = FORMAT_PLAIN;
    }
    if (empty($eventdata->fullmessagehtml)) {
        $eventdata->fullmessagehtml = nl2br($eventdata->fullmessage);
    }
    $eventdata->notification = 1;

    if (!isset($eventdata->contexturl)) {
        debugging('tm_message_workflow_send() must have have contexturl', DEBUG_DEVELOPER);
        return false;
    }
    if (!empty($eventdata->acceptbutton)) {
        $onaccept = new stdClass();
        $onaccept->action = 'plan';
        $onaccept->text = $eventdata->accepttext;
        $onaccept->data = $eventdata->data;
        $onaccept->acceptbutton = $eventdata->acceptbutton;
        $eventdata->onaccept = $onaccept;
    }
    if (!empty($eventdata->rejectbutton)) {
        $onreject = new stdClass();
        $onreject->action = 'plan';
        $onreject->text = $eventdata->rejecttext;
        $onreject->data = $eventdata->data;
        $onreject->rejectbutton = $eventdata->rejectbutton;
        $eventdata->onreject = $onreject;
    }
    if (!empty($eventdata->infobutton)) {
        $oninfo = new stdClass();
        $oninfo->action = 'plan';
        $oninfo->text = $eventdata->infotext;
        $oninfo->data = $eventdata->data;
        $oninfo->data['redirect'] = $eventdata->contexturl;
        $oninfo->infobutton = $eventdata->infobutton;
        $eventdata->oninfo = $oninfo;
    }

    if ($eventdata->contexturl) {
        $eventdata->fullmessagehtml .= html_writer::empty_tag('br') . html_writer::empty_tag('br');
        $eventdata->fullmessagehtml .= get_string('viewdetailshere', 'totara_message', $eventdata->contexturl);

    }

    return tm_message_send($eventdata);
}

/**
 * Dismiss a message - this will move a notification to a read notification
 * without doing any of the workflow processing in message_metadata
 *
 * @param int         $id             Notification's id or message's id.
 * @param string|null $processor_type If the $processor_type is not provided, then all the message metadata related
 *                                    to the message's id will be dismissed.
 * @return boolean success
 */
function tm_message_dismiss(int $id, ?string $processor_type = null): bool {
    global $DB;

    $notification = $DB->get_record('notifications', ['id' => $id]);
    if ($notification) {
        $processor_id = null;
        if (!empty($processor_type)) {
            $processor_id = $DB->get_field('message_processors', 'id', ['name' => $processor_type], MUST_EXIST);
        }

        // We are marking the notification record as notification. So that the function
        // is able to tell mark the notification as read.
        $notification->notification = 1;
        tm_message_mark_message_read($notification, time(), null, $processor_id);
        return true;
    }

    return false;
}

/**
 * accept a task - this will invoke the task onaccept action
 * saved against this message
 *
 * @param int         $id                message id or the notification's id.
 * @param string      $reasonfordecision Reason for granting the request
 * @param string|null $processor_type
 *
 * @return boolean success
 */
function tm_message_task_accept(int $id, string $reasonfordecision, ?string $processor_type = null): bool {
    global $DB;

    $notification_record = $DB->get_record('notifications', ['id' => $id]);
    if (!$notification_record) {
        return false;
    }

    if (empty($processor_type)) {
        // This is for the totara_task only, so it is safe to assume that we are going to approve the
        // message_metadata record for totara_task message.
        $processor_type = 'totara_task';
    }

    $processor_id = $DB->get_field('message_processors', 'id', ['name' => $processor_type]);
    $repository = message_metadata::repository();

    $metadata = $repository->find_message_metadata_from_notification_id($id, $processor_id);
    if (!$metadata) {
        return false;
    }

    $event_data = totara_message_eventdata($id, 'onaccept', $metadata->get_record());

    // Default result.
    $result = false;

    // Grep the onaccept handler
    if (isset($event_data->action)) {
        /** @var totara_message_workflow_plugin_base|bool $plugin */
        $plugin = tm_message_workflow_object($event_data->action);

        if (!$plugin) {
            return false;
        }

        if (!empty($reasonfordecision)) {
            $event_data->data['reasonfordecision'] = $reasonfordecision;
        }
        // Run the onaccept phase
        $result = $plugin->onaccept($event_data->data, $notification_record);
    }

    // Finally - dismiss this message as it has now been processed
    $notification_record->notification = 1;
    tm_message_mark_message_read($notification_record, time(), null, $processor_id);
    return $result;
}

/**
 * Redirect to a task's context URL
 *
 * @param int $id message id
 * @return boolean success
 * @deprecated since Totara 15.0
 */
function tm_message_task_link(int $id): bool {
    debugging('tm_message_task_link() has been deprecated since Totara 15.0. Please use tm_message_task_accept() instead.', DEBUG_DEVELOPER);

    global $DB;

    // Note from TL-29085 - this function seems to not be used elsewhere, so i had left
    // it out from modification/tweaking to make it compatible with the new behaviour from MOODLE.

    $message = $DB->get_record('notifications', ['id' => $id]);
    if ($message) {
        // get the event data
        $eventdata = totara_message_eventdata($id, 'oninfo');

        // grab the onaccept handler
        if (isset($eventdata->action)) {
            $plugin = tm_message_workflow_object($eventdata->action);
            if (!$plugin) {
                return false;
            }

            // run the onaccept phase
            $plugin->onaccept($eventdata->data, $message);
        }

        // finally - dismiss this message as it has now been processed
        // This can only be a notification, so mark it as such.
        $message->notification = 1;
        tm_message_mark_message_read($message, time());
        return true;
    }

    return false;
}


/**
 * reject a task - this will invoke the task onreject action
 * saved against this message
 *
 * @param int    $id                Notification's id.
 * @param string $reasonfordecision Reason for rejecting the request.
 * @param string $processor_type    We default to totara task, because this should be for the totara_task only,
 *                                  so it is safe to assume that we are going to approve the message_metadata
 *                                  record for totara_task message.
 *
 * @return boolean success
 */
function tm_message_task_reject(int $id, string $reasonfordecision,
                                string $processor_type = 'totara_task'): bool {
    global $DB;

    $notification_record = $DB->get_record('notifications', ['id' => $id]);
    if (!$notification_record) {
        return false;
    }

    $processor_id = $DB->get_field('message_processors', 'id', ['name' => $processor_type], MUST_EXIST);
    $repository = message_metadata::repository();

    $metadata = $repository->find_message_metadata_from_notification_id($id, $processor_id);
    if (!$metadata) {
        return false;
    }

    $event_data = totara_message_eventdata($id, 'onreject', $metadata->get_record());
    if ($event_data && !empty($reasonfordecision)) {
        $event_data->data['reasonfordecision'] = $reasonfordecision;
    }

    $result = false;

    // Grab the onreject handler
    if (isset($event_data->action)) {
        /** @var totara_message_workflow_plugin_base|bool $plugin */
        $plugin = tm_message_workflow_object($event_data->action);
        if (!$plugin) {
            return false;
        }

        // Run the onreject phase
        $result = $plugin->onreject($event_data->data, $notification_record);
    }

    // Finally - dismiss this message as it has now been processed
    $notification_record->notification = 1;
    tm_message_mark_message_read($notification_record, time(), null, $processor_id);
    return $result;
}


/**
 * instantiate workflow object
 *
 * @param string $action workflow object action name
 * @return totara_message_workflow_plugin_base|bool
 */
function tm_message_workflow_object(string $action) {
    global $CFG;

    require_once($CFG->dirroot . '/totara/message/workflow/lib.php');
    $file = $CFG->dirroot . '/totara/message/workflow/plugins/' . $action . '/workflow_' . $action . '.php';
    if (!file_exists($file)) {
        debugging('tm_message_task_accept() plugin does not exist: ' . $action, DEBUG_DEVELOPER);
        return false;
    }
    require_once($file);

    // create the object
    $ctlclass = 'totara_message_workflow_' . $action;
    if (class_exists($ctlclass)) {
        return new $ctlclass();
    } else {
        debugging('tm_message_task_accept() plugin class does not exist: ' . $ctlclass, DEBUG_DEVELOPER);
        return false;
    }
}

/**
 * get the current list of messages by type - alert/task
 *
 * @param string        $type     The processor type, of which we are proccessing message for.
 * @param string        $order_by Order by clause
 * @param stdClass|null $user_to  User table record for user required
 * @param bool          $limit    Apply the block limit
 *
 * @return array of messages
 */
function tm_messages_get(string $type, $order_by = null, $user_to = null, bool $limit = true): array {
    global $USER, $DB;

    if (is_bool($order_by)) {
        // Preventing boolean to be passed thru from second parameter - $orderby ($order_by)
        debugging(
            "The second parameter of function 'tm_message_get()' does not accept boolean anymore, " .
            "please either provide a string or empty string",
            DEBUG_DEVELOPER
        );
    }

    if (is_bool($user_to)) {
        // Preventing boolean to be pass thru from third parameter - $userto ($user_to).
        debugging(
            "The third parameter of function 'tm_message_get()' does not accept boolean anymore, " .
            "please either use NULL or an instance of stdClass that is fetched from database",
            DEBUG_DEVELOPER
        );
    }

    // select only particular type
    $processor = $DB->get_record('message_processors', ['name' => $type]);
    if (empty($processor)) {
        return [];
    }

    // sort out for which user.
    $user_id = !empty($user_to) ? $user_to->id : $USER->id;

    // do we sort?
    $order_by = !empty($order_by) ? " ORDER BY {$order_by}" : ' ';

    // do we apply a limit?
    if ($limit) {
        $limit = TOTARA_MSG_ALERT_LIMIT;
    }

    // Hunt for messages
    $sql = "
        SELECT 
            n.id,
            n.useridfrom, 
            n.subject,
            n.fullmessage,
            n.timecreated,
            mm.msgstatus,
            mm.msgtype,
            mm.urgency,
            mm.icon,
            n.contexturl,
            n.contexturlname
        FROM \"ttr_notifications\" n
        INNER JOIN \"ttr_message_metadata\" mm ON n.id = mm.notificationid
        WHERE n.useridto = ? AND mm.processorid = ?
        AND mm.timeread IS NULL            
        {$order_by}    
    ";

    return $DB->get_records_sql($sql, [$user_id, $processor->id], 0, $limit);
}

/**
 * get the current count of messages by type - alert/task
 *
 * @param string   $type    Processor type for the message
 * @param stdClass $user_to user table record for user required
 *
 * @return int count of messages
 */
function tm_messages_count(string $type, $user_to = null): int {
    global $USER, $DB;

    if (is_bool($user_to)) {
        // Preventing parameter $user_to to accept boolean.
        debugging(
            "The second parameter of function 'tm_message_count' does not accept boolean anymore, " .
            "please either provide NULL or an instance of stdClass fetched from database",
            DEBUG_DEVELOPER
        );
    }

    // select only particular type
    $processor = $DB->get_record('message_processors', ['name' => $type]);
    if (empty($processor)) {
        return false;
    }

    // sort out for which user
    $user_id = !empty($user_to) ? $user_to->id : $USER->id;

    $sql = '
        SELECT COUNT(n.id) FROM "ttr_notifications" n
        INNER JOIN "ttr_message_metadata" d ON (d.notificationid = n.id)
        WHERE n.useridto = ? AND d.processorid = ?
    ';

    return $DB->count_records_sql($sql, [$user_id, $processor->id]);
}

/**
 * Set the default config values for totara ouput types on install.
 * @return void
 */
function tm_set_preference_defaults(): void {
    set_config('totara_task_provider_totara_message_task_permitted', 'permitted', 'message');
    set_config('totara_alert_provider_totara_message_alert_permitted', 'permitted', 'message');
    set_config('totara_alert_provider_totara_message_task_permitted', 'disallowed', 'message');
    set_config('message_provider_totara_message_alert_loggedin', 'totara_alert,popup,email', 'message');
    set_config('message_provider_totara_message_alert_loggedoff', 'totara_alert,popup,email', 'message');
    set_config('message_provider_totara_message_task_loggedin', 'totara_task,email', 'message');
    set_config('message_provider_totara_message_task_loggedoff', 'totara_task,email', 'message');
    set_config('popup_provider_totara_message_task_permitted', 'disallowed', 'message');
    set_config('email_provider_totara_message_task_permitted', 'permitted', 'message');
    set_config('jabber_provider_totara_message_task_permitted', 'disallowed', 'message');
    set_config('totara_airnotifier_provider_totara_message_task_permitted', 'disallowed', 'message');
    set_config('totara_task_provider_totara_message_alert_permitted', 'disallowed', 'message');
    set_config('popup_provider_totara_message_alert_permitted', 'permitted', 'message');
    set_config('email_provider_totara_message_alert_permitted', 'permitted', 'message');
    set_config('jabber_provider_totara_message_alert_permitted', 'permitted', 'message');
}

/**
 * @param array  $messagearray An array of objects
 * @param string $field        Is a valid property of object. If $field is empty then return
 *                             the count of the whole array. If $field is non-existent then return 0;
 * @param string $value
 *
 * @return int
 */
function tm_message_count_messages(array $messagearray, string $field = '', string $value = '') {
    if (!is_array($messagearray)) {
        return 0;
    }
    if ($field == '' or empty($messagearray)) {
        return count($messagearray);
    }

    $count = 0;
    foreach ($messagearray as $message) {
        $count += ($message->$field == $value) ? 1 : 0;
    }
    return $count;
}

/**
 * Returns the count of unread messages for user. Either from a specific user or from all users.
 * @param object $user1 the first user. Defaults to $USER
 * @param object $user2 the second user. If null this function will count all of user 1's unread messages.
 * @return int the count of $user1's unread messages
 */
function tm_message_count_unread_messages($user1 = null, $user2 = null) {
    global $USER, $DB;

    if (empty($user1)) {
        $user1 = $USER;
    }

    if (!empty($user2)) {
        return $DB->count_records_select(
            'message',
            "useridto = ? AND useridfrom = ?",
            [$user1->id, $user2->id], "COUNT('id')"
        );
    } else {
        return $DB->count_records_select(
            'message',
            "useridto = ?",
            [$user1->id],
            "COUNT('id')"
        );
    }
}

/**
 * marks ALL messages being sent from $fromuserid to $touserid as read
 * @param int $to_user_id   the id of the message recipient
 * @param int $from_user_id the id of the message sender
 * @return void
 */
function tm_message_mark_messages_read(int $to_user_id, int $from_user_id): void {
    global $DB;

    $sql = '
        SELECT n.*, mm.processorid FROM "ttr_notifications" n 
        INNER JOIN "ttr_message_metadata" mm ON n.id = mm.notificationid
        WHERE n.useridto = :user_id_to 
        AND n.useridfrom = :user_id_from
    ';

    $messages = $DB->get_recordset_sql(
        $sql,
        [
            'user_id_to' => $to_user_id,
            'user_id_from' => $from_user_id,
        ]
    );

    foreach ($messages as $message) {
        $message->notification = 1;
        tm_message_mark_message_read($message, time(), null, $message->processorid);
    }

    $messages->close();
}

/**
 * Mark a single message as read
 * @param stdClass  $message                  An object with an object property ie $message->id which is an id in the
 *                                            message table
 * @param int       $timeread                 The timestamp for when the message should be marked read. Usually time().
 * @param bool|null $messageworkingempty      Deprecated since Totara 14.0
 * @param int|null  $processor_id             Whether it is a record id of  a totara_task or totara_alert.
 *                                            By default, we are marking all the records to be read.
 * @return void
 */
function tm_message_mark_message_read(stdClass $message, int $timeread, ?bool $messageworkingempty = null,
                                      ?int $processor_id = null): void {
    if (null !== $messageworkingempty) {
        debugging(
            "The third parameter '\$messageworkingempty' of function tm_message_mark_message_read() " .
            "had been deprecated. Please update all calls",
            DEBUG_DEVELOPER
        );
    }

    if (!empty($message->notification)) {
        // For totara message, it is more about notifications rather than a peer-to-peers message.
        helper::mark_message_metadata_read($message->id, $timeread, $processor_id);
        api::mark_notification_as_read($message, $timeread);
    } else {
        api::mark_message_as_read($message->useridto, $message, $timeread);
    }
}

/**
 * Set default message preferences.
 *
 * * @deprecated Since Totara 12.31
 * @param stdClass $user - User to set message preferences
 * @return bool
 */
function tm_message_set_default_message_preferences(stdClass $user): bool {
    debugging('tm_message_set_default_message_preferences has been deprecated since Totara 12.31', DEBUG_DEVELOPER);
    global $DB;

    $defaultonlineprocessor = 'email';
    $defaultofflineprocessor = 'email';
    $offlineprocessortouse = $onlineprocessortouse = null;

    //look for the pre-2.0 preference if it exists
    $oldpreference = get_user_preferences('message_showmessagewindow', -1, $user->id);
    //if they elected to see popups or the preference didnt exist
    $usepopups = (intval($oldpreference) == 1 || intval($oldpreference) == -1);

    if ($usepopups) {
        $defaultonlineprocessor = 'popup';
    }

    $providers = $DB->get_records('message_providers');
    $preferences = [];
    if (!$providers) {
        $providers = [];
    }

    foreach ($providers as $providerid => $provider) {
        //force some specific defaults for some types of message
        if ($provider->name == 'instantmessage') {
            //if old popup preference was set to 1 or is missing use popups for IMs
            if ($usepopups) {
                $onlineprocessortouse = 'popup';
                $offlineprocessortouse = 'email,popup';
            }
        } else if ($provider->name == 'posts') {
            //forum posts
            $offlineprocessortouse = $onlineprocessortouse = 'email';
        } else if ($provider->name == 'alert') {
            //totara alert
            $offlineprocessortouse = $onlineprocessortouse = 'totara_alert,email';
        } else if ($provider->name == 'task') {
            //totara task
            $offlineprocessortouse = $onlineprocessortouse = 'totara_task,email';
        } else {
            $onlineprocessortouse = $defaultonlineprocessor;
            $offlineprocessortouse = $defaultofflineprocessor;
        }

        $preferences['message_provider_' . $provider->component . '_' . $provider->name . '_loggedin'] = $onlineprocessortouse;
        $preferences['message_provider_' . $provider->component . '_' . $provider->name . '_loggedoff'] = $offlineprocessortouse;
    }

    return set_user_preferences($preferences, $user->id);
}
