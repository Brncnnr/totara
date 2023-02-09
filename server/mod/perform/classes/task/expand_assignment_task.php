<?php
/*
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\task;

use core\entity\user;
use core\message\message;
use core\task\adhoc_task;
use core\task\manager;
use mod_perform\expand_task;

/**
 * Ad-hoc task for expanding an assignment to be triggered when the assignment was created or changed.
 */
class expand_assignment_task extends adhoc_task {

    public function execute() {
        $task_data = $this->get_custom_data();
        if (empty($task_data->expand_all) && empty($task_data->assignment_id) && empty($task_data->assignment_ids)) {
            throw new \coding_exception('Missing assignment_ids, assignment_id or expand_all in expand_assignment_task');
        }

        $expand_task = expand_task::create();
        if (!empty($task_data->assignment_id)) {
            $expand_task->expand_single($task_data->assignment_id);
        } else if (!empty($task_data->assignment_ids)) {
            $expand_task->expand_multiple($task_data->assignment_ids);
        } else if (!empty($task_data->expand_all)) {
            $expand_task->expand_all();
        }

        if (!empty($task_data->notify_user)) {
            $this->send_notification($task_data->notify_user);
        }
    }

    /**
     * Check if this task is already scheduled
     *
     * @return bool
     */
    public static function is_scheduled() {
        global $DB;
        $params = ['component' => 'mod_perform', 'classname' => "\\" . self::class];
        return $DB->record_exists('task_adhoc', $params);
    }

    /**
     * Schedule this task
     *
     * @param array $task_data
     */
    private static function schedule(array $task_data) {
        $adhocktask = new static();
        $adhocktask->set_custom_data($task_data);
        $adhocktask->set_component('mod_perform');
        manager::queue_adhoc_task($adhocktask);
    }

    /**
     * Schedule this task
     *
     * @param int $assignment_id
     */
    public static function schedule_for_assignment(int $assignment_id) {
        self::schedule(['assignment_id' => $assignment_id]);
    }

    /**
     * Schedule this task
     *
     * @param array $assignment_ids
     */
    public static function schedule_for_assignments(array $assignment_ids) {
        self::schedule(['assignment_ids' => $assignment_ids]);
    }

    /**
     * Schedule to expand all with optional user notification
     *
     * @param int|null $notify_user_id
     */
    public static function schedule_for_all($notify_user_id = null) {
        $task_data = ['expand_all' => true];
        if ($notify_user_id) {
            $task_data['notify_user'] = $notify_user_id;
        }
        self::schedule($task_data);
    }

    /**
     * Send notification to user.
     *
     * @param int $user_id
     */
    private function send_notification(int $user_id) {
        $userto = user::repository()->find_or_fail($user_id);

        $subject = get_string('expand_task_notification_subject', 'mod_perform');
        $body = get_string('expand_task_notification_body', 'mod_perform');

        $message = new message();
        $message->courseid          = 0;
        $message->notification      = 1;
        $message->component         = 'mod_perform';
        $message->name              = 'expand_task_finished';
        $message->userfrom          = \core_user::get_noreply_user();
        $message->userto            = (object) $userto->to_array();
        $message->subject           = $subject;
        $message->fullmessage       = $body;
        $message->fullmessageformat = FORMAT_MARKDOWN;
        $message->fullmessagehtml   = markdown_to_html($body);
        $message->smallmessage      = $subject;
        $message->contexturl        = new \moodle_url('/mod/perform/manage/activity/index.php');
        $message->contexturlname    = get_string('manage_activity_page_title', 'mod_perform');

        message_send($message);
    }

}

