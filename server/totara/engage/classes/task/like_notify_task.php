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
use totara_engage\output\like_message;

final class like_notify_task extends adhoc_task {
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
        $keys = ['owner', 'liker', 'name', 'url', 'resourcetype'];

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

        $liker = core_user::get_user($data->liker);
        if (!$liker) {
            // Ignore if liker doesn't exist.
            debugging('Skipped sending notification from non-existent liker with id ' . $data->liker);
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

        // Initialize message body.
        $message_body = new \stdClass();
        $message_body->fullname = fullname($liker);
        $message_body->name = $data->name;
        $message_body->url = $url;
        $message_body->type = $resourcetype;

        $template = like_message::create($message_body);
        $message_body = $OUTPUT->render($template);

        // Initialize message subject.
        $subject = new \stdClass();
        $subject->fullname = fullname($liker);
        $subject->name = $resourcetype;

        $message = new message();
        $message->courseid = SITEID;
        $message->component = 'totara_engage';
        $message->name = 'like_notification';
        $message->userfrom = $liker;
        $message->userto = $recipient;
        $message->notification = 1;
        $message->subject = get_string('like_message_subject', 'totara_engage', $subject);
        $message->fullmessage = html_to_text($message_body);
        $message->fullmessageformat = FORMAT_HTML;
        $message->fullmessagehtml = $message_body;

        message_send($message);
    }
}
