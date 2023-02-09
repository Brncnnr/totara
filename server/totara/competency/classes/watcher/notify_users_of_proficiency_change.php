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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\watcher;

use core_user;
use core\message\message;
use moodle_url;
use totara_competency\hook\competency_achievement_updated_bulk;
use context_system;

/**
 * Hook watcher to notify users assigned to a competency when they become non-proficient.
 *
 * @package totara_competency\watcher
 */
class notify_users_of_proficiency_change {

    /**
     * Send notifications to users if proficiency changed to non-proficient after achievement aggregation.
     *
     * @param competency_achievement_updated_bulk
     * @return void
     */
    public static function send_notification(competency_achievement_updated_bulk $hook): void {
        $competency_data = $hook->get_competency_data();
        $users_proficiency_changes = $hook->get_user_ids_proficiency_data();

        foreach ($users_proficiency_changes as $user_id => $user_proficiency_data) {
            $changed_to_non_proficient = $user_proficiency_data['proficiency_changed'] && $user_proficiency_data['is_proficient'] === 0;

            if (!$changed_to_non_proficient) {
                continue;
            }
            $message_data = self::generate_lost_proficiency_message_content($competency_data, $user_id, $user_proficiency_data);
            self::send_message($user_id, $message_data);
        }
    }

    /**
     * Generate message content to send to user.
     *
     * @param $competency_data
     * @param $user_id
     * @param $user_proficiency_data
     *
     * @return array
     */
    private static function generate_lost_proficiency_message_content($competency_data, $user_id, $user_proficiency_data): array {
        $activity_log_url = new moodle_url(
            '/totara/competency/profile/details/index.php',
            [
                'competency_id' => $competency_data['id'],
                'user_id' => $user_id,
                'show_activity_log' => 1,
            ]
        );
        $current_value_name = $user_proficiency_data['new_scale_value']['name'] ?? get_string('none', 'totara_competency');
        $context = context_system::instance();

        $subject = get_string(
            'no_longer_proficient_notification_subject',
            'totara_competency',
            format_string($competency_data['fullname'], true, ['context' => $context])
        );
        $text_body = get_string(
            'no_longer_proficient_notification_text_body',
            'totara_competency',
            [
                'competency_name' => format_string($competency_data['fullname'], true, ['context' => $context]),
                'current_rating' => format_string($current_value_name, true, ['context' => $context]),
                'link_to_competency_activity_log' => $activity_log_url->out(false),
            ]
        );
        $html_body = get_string(
            'no_longer_proficient_notification_html_body',
            'totara_competency',
            [
                'competency_name' => format_string($competency_data['fullname'], true, ['context' => $context]),
                'current_rating' => format_string($current_value_name, true, ['context' => $context]),
                'link_to_competency_activity_log' => $activity_log_url->out(false),
            ]
        );

        return [
            'subject' => $subject,
            'text_body' => $text_body,
            'html_body' => $html_body,
        ];
    }

    /**
     * Sends message to user.
     *
     * @param int $user_id
     * @param array $message_data
     * @return void
     */
    private static function send_message(int $user_id, array $message_data): void {
        $message = new message();
        $message->courseid = 0;
        $message->notification = 1;
        $message->component = 'totara_competency';
        $message->name = 'no_longer_proficient';
        $message->userfrom = core_user::get_noreply_user();
        $message->userto = $user_id;
        $message->subject = $message_data['subject'];
        $message->smallmessage = $message_data['subject'];
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessage = $message_data['text_body'];
        $message->fullmessagehtml = $message_data['html_body'];

        message_send($message);
    }
}
