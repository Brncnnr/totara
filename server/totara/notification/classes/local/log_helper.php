<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Riana ROssouw <riana.rossouw@totaralearning.com>
 * @package totara_notification
 */
namespace totara_notification\local;

use totara_notification\entity\notification_event_log as notification_event_log_entity;
use totara_notification\entity\notification_log as notification_log_entity;
use totara_notification\model\notification_delivery_log as delivery_log_model;
use totara_notification\model\notification_event_log as notification_event_log_model;
use totara_notification\model\notification_log as log_model;
use totara_notification\model\notification_preference as notification_preference_model;
use totara_notification\resolver\notifiable_event_resolver;

/**
 * Helper functions to log notifiable events and notifications
 */
class log_helper {
    /**
     * helper constructor.
     * Preventing this class from instantiation.
     */
    private function __construct() {
    }

    /**
     * @param notifiable_event_resolver $resolver
     * @param notification_preference_model $preference
     * @param int $timecreated
     * @param bool $has_error
     * @return int
     */
    public static function log_event(
        notifiable_event_resolver $resolver,
        notification_preference_model $preference,
        int $time_created,
        bool $has_error = false
    ): int {
        $logs_enabled = get_config('core','notificationlogs');
        if (empty($logs_enabled)) {
            return 0;
        }

        $resolver_class_name = get_class($resolver);
        $extended_context = $resolver->get_extended_context();
        $event_data = $resolver->get_event_data();
        $subject_user_id = $resolver->get_subject();

        $schedule_offset = $preference->get_schedule_offset();
        $schedule_type = schedule_helper::get_schedule_class_from_offset($schedule_offset);

        $string_params = $resolver->get_notification_log_display_string_key_and_params();
        $string_key = $string_params['key'] ?: 'notifiable_event_triggered';
        unset($string_params['key']);
        $string_params['component'] = $string_params['component'] ?: 'totara_notification';
        $string_params['params'] = $string_params['params'] ?? ['resolver_title' => ''];

        $log = notification_event_log_model::create_if_not_exist(
            $resolver_class_name,
            $extended_context,
            $subject_user_id,
            $event_data,
            $schedule_type,
            $schedule_offset,
            $string_key,
            $string_params,
            $time_created,
            $has_error
        );

        return $log->id;
    }

    /**
     * @param int $event_log_id
     */
    public static function set_event_log_has_error(int $event_log_id) {
        if (!$event_log_id) {
            return;
        }

        $log = notification_event_log_entity::repository()->find($event_log_id);
        $log->has_error = true;
        $log->save();
    }

    /**
     * @param int $event_log_id
     * @param int $preference_id
     * @param \stdClass|int $recipient
     * @param int $time_created
     * @param bool $has_error
     * @return array
     */
    public static function log_notification(
        int $event_log_id,
        int $preference_id,
        $recipient,
        int $time_created,
        bool $has_error = false
    ): int {
        $logs_enabled = get_config('core','notificationlogs');
        if (empty($logs_enabled) || !$event_log_id) {
            return 0;
        }

        $recipient_id = is_object($recipient) ? $recipient->id : $recipient;

        $log = log_model::create(
            $event_log_id,
            $preference_id,
            $recipient_id,
            $time_created,
            $has_error
        );

        return $log->id;
    }

    /**
     * @param int $event_log_id
     */
    public static function set_log_has_error(int $log_id) {
        if (!$log_id) {
            return;
        }

        $log = notification_log_entity::repository()->find($log_id);
        $log->has_error = true;
        $log->save();
    }

    /**
     * @param int $notification_log_id
     * @param string $delivery_channel
     * @param int $time_created
     * @param string|null $address
     * @param bool $has_error
     * @return int
     */
    public static function log_delivery(
        int $notification_log_id,
        string $delivery_channel,
        int $time_created,
        ?string $address = null,
        bool $has_error = false
    ): int {
        $logs_enabled = get_config('core','notificationlogs');
        if (empty($logs_enabled) || !$notification_log_id) {
            return 0;
        }

        $log = delivery_log_model::create(
            $notification_log_id,
            $delivery_channel,
            $time_created,
            $address,
            $has_error
        );

        return $log->id;
    }
}
