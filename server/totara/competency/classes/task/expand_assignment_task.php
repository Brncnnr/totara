<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @package totara_competency
 */

namespace totara_competency\task;

use core\message\message;
use core\task\adhoc_task;
use totara_competency\expand_task;

/**
 * Ad-hoc tack for expanding an assignment to be triggered when the assignment was created or changed.
 */
class expand_assignment_task extends adhoc_task {

    use expand_task_trait;

    public function execute() {
        global $DB;

        $task_data = $this->get_custom_data();
        if (empty($task_data->expand_all) && empty($task_data->assignment_id) && empty($task_data->assignment_ids)) {
            throw new \coding_exception('Missing assignment_ids, assignment_id or expand_all in expand_assignment_task');
        }

        $lock = $this->get_expand_task_lock();

        try {
            $expand_task = new expand_task($DB);
            if (!empty($task_data->assignment_id)) {
                $expand_task->expand_single($task_data->assignment_id);
            } else if (!empty($task_data->assignment_ids)) {
                $expand_task->expand_multiple($task_data->assignment_ids);
            } else if (!empty($task_data->expand_all)) {
                $expand_task->expand_all();
            }
            $lock->release();
        } catch (\Exception $exception) {
            $lock->release();
            throw $exception;
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
        $params = ['component' => 'totara_competency', 'classname' => "\\" . self::class];
        return $DB->record_exists('task_adhoc', $params);
    }

    /**
     * Schedule this task
     *
     * @param array $task_data
     */
    public static function schedule(array $task_data) {
        $adhocktask = new static();
        $adhocktask->set_custom_data($task_data);
        $adhocktask->set_component('totara_competency');
        \core\task\manager::queue_adhoc_task($adhocktask);
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
        global $DB;

        $userto = $DB->get_record('user', ['id' => $user_id], '*', MUST_EXIST);

        $subject = get_string('expand_task_notification_subject', 'totara_competency');
        $body = get_string('expand_task_notification_body', 'totara_competency');

        $message = new message();
        $message->courseid          = 0;
        $message->notification      = 1;
        $message->component         = 'totara_competency';
        $message->name              = 'expand_task_finished';
        $message->userfrom          = \core_user::get_noreply_user();
        $message->userto            = $userto;
        $message->subject           = $subject;
        $message->fullmessage       = $body;
        $message->fullmessageformat = FORMAT_MARKDOWN;
        $message->fullmessagehtml   = markdown_to_html($body);
        $message->smallmessage      = $subject;
        $message->contexturl        = new \moodle_url('/totara/competency/assignments/users.php');
        $message->contexturlname    = get_string('title_users', 'totara_competency');

        message_send($message);
    }

}

