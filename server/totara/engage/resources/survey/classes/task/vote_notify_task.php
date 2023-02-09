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
 * @package engage_survey
 */
namespace engage_survey\task;

use core\message\message;
use core\task\adhoc_task;
use core_user;
use engage_survey\output\vote_message;

final class vote_notify_task extends adhoc_task {
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
        $keys = ['owner', 'voter', 'name', 'url'];

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

        $voter = core_user::get_user($data->voter);
        if (!$voter) {
            // Ignore if voter doesn't exist.
            debugging('Skipped sending notification from non-existent voter with id ' . $data->voter);
            return;
        }

        cron_setup_user($recipient);

        $url = new \moodle_url($data->url);
        $template = vote_message::create($data->name ,$url);
        $message_body = $OUTPUT->render($template);

        $message = new message();
        $message->courseid = SITEID;
        $message->component = 'engage_survey';
        $message->name = 'voting_survey_notification';
        $message->userfrom = $voter;
        $message->userto = $recipient;
        $message->notification = 1;
        $message->subject = get_string('vote_message_subject', 'engage_survey');
        $message->fullmessage = html_to_text($message_body);
        $message->fullmessageformat = FORMAT_HTML;
        $message->fullmessagehtml = $message_body;

        message_send($message);
    }
}