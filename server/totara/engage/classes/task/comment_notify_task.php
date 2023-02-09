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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage\task;

use core\message\message;
use core\task\adhoc_task;
use core_user;
use totara_engage\output\comment_message;

final class comment_notify_task extends adhoc_task {
    /**
     * Constructor.
     */
    public function __construct() {
        $this->set_component('totara_engage');
    }

    /**
     * @return void
     */
    public function execute() {
        global $OUTPUT;

        $data = $this->get_custom_data();
        $keys = ['owner', 'resourcetype', 'commenter', 'name', 'url', 'is_comment', 'component'];

        foreach ($keys as $key) {
            if (!property_exists($data, $key)) {
                debugging("The custom data for the task does not have key '{$key}'");
                return;
            }
        }

        $recipient = core_user::get_user($data->owner);
        if (!$recipient) {
            // Ignore if owner doesn't exist.
            debugging('Skipped sending notification to non-existent user with id ' . $data->owner);
            return;
        }

        $commenter = core_user::get_user($data->commenter);
        if (!$commenter) {
            // Ignore if commenter doesn't exist.
            debugging('Skipped sending notification from non-existent commenter with id ' . $data->commenter);
            return;
        }

        cron_setup_user($recipient);

        $resourcetype = $data->resourcetype; // Default to pre-translated string, just in case.
        if (property_exists($data, 'resource_info') && !empty($data->resource_info)) {
            $stringkey = $data->resource_info->stringkey ?? null;
            $component = $data->resource_info->component ?? null;

            if (!empty($stringkey) && !empty($component)) {
                // However when possible get the resourcetype string here for better translation.
                $resourcetype = get_string($stringkey, $component);
            }
        }

        $url = new \moodle_url($data->url);

        $message_content = null;
        $subject_content = null;

        // Initialize message body.
        $message_body = new \stdClass();
        $message_body->fullname = fullname($commenter);
        $message_body->name = $data->name;
        $message_body->url = $url;

        $template = comment_message::create($message_body, $data->is_comment);
        $message_body = $OUTPUT->render($template);

        // Initialize message subject.
        $subject = new \stdClass();
        $subject->fullname = fullname($commenter);
        if ($data->is_comment) {
            $subject->resourcetype = $resourcetype;
            $subject_content = get_string('comment_message_subject', 'totara_engage', $subject);
        } else {
            $subject->name = $data->name;
            $subject_content = get_string('reply_message_subject', 'totara_engage', $subject);
        }

        $message = new message();
        $message->courseid = SITEID;
        $message->component = $data->component;
        $message->name = $data->is_comment ? 'comment_notification' : 'reply_notification';
        $message->userfrom = $commenter;
        $message->userto = $recipient;
        $message->notification = 1;
        $message->subject = $subject_content;
        $message->fullmessage = html_to_text($message_body);
        $message->fullmessageformat = FORMAT_HTML;
        $message->fullmessagehtml = $message_body;

        message_send($message);
    }
}
