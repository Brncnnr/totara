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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_reportedcontent
 */

namespace totara_reportedcontent\task;

use core\message\message;
use core\task\adhoc_task;
use core_user;
use stdClass;
use function strip_tags;

/**
 * Notification task for removal of user posted content
 */
final class remove_notify_task extends adhoc_task {
    /**
     * @return void
     */
    public function execute() {
        $data = $this->get_custom_data();
        $keys = ['target_user_id', 'url', 'time_created'];

        foreach ($keys as $key) {
            if (!property_exists($data, $key)) {
                debugging("The custom data for the task does not have key '{$key}'");
                return;
            }
        }

        $target_user = core_user::get_user($data->target_user_id, '*', MUST_EXIST);
        cron_setup_user($target_user);

        $message_data = new stdClass();
        $message_data->date = userdate(
            $data->time_created,
            get_string('strftimedatetime', 'langconfig'),
            $target_user->timezone
        );
        $message_data->url = $data->url;
        $message_content = get_string('contentdeletedmessage', 'totara_reportedcontent', $message_data);

        $message = new message();
        $message->userfrom = core_user::get_support_user();
        $message->userto = $target_user;
        $message->component = 'totara_reportedcontent';
        $message->name = 'removereview';
        $message->subject = get_string('contentdeletedsubject', 'totara_reportedcontent');
        $message->courseid = SITEID;
        $message->fullmessage = strip_tags($message_content);
        $message->fullmessageformat = FORMAT_HTML;
        $message->fullmessagehtml = $message_content;

        message_send($message);
    }
}