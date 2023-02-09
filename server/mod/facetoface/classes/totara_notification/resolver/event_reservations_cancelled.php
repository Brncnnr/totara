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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\totara_notification\resolver;

use coding_exception;
use core_course\totara_notification\placeholder\activity as placeholder_activity;
use core_course\totara_notification\placeholder\course as placeholder_course;
use core_user\totara_notification\placeholder\user as placeholder_user;
use lang_string;
use mod_facetoface\totara_notification\placeholder\event as placeholder_event;
use mod_facetoface\totara_notification\recipient\reservation_managers;
use mod_facetoface\totara_notification\recipient\third_party;
use totara_notification\placeholder\placeholder_option;

class event_reservations_cancelled extends seminar_resolver_base {

    /**
     * Returns the title for this notifiable event, which should be used
     * within the tree table of available notifiable events.
     *
     * @return string
     * @throws coding_exception
     */
    public static function get_notification_title(): string {
        return get_string('notification_event_reservations_cancelled_title', 'mod_facetoface');
    }

    /**
     * Returns an array of available recipients (metadata) for this event (concrete class).
     *
     * @return array
     */
    public static function get_notification_available_recipients(): array {
        return [
            reservation_managers::class,
            third_party::class,
        ];
    }

    /**
     * Returns the list of available placeholder options.
     *
     * @return placeholder_option[]
     * @throws coding_exception
     */
    public static function get_notification_available_placeholder_options(): array {
        return [
            placeholder_option::create(
                'recipient',
                placeholder_user::class,
                new lang_string('placeholder_group_recipient', 'totara_notification'),
                function (array $unused_event_data, int $target_user_id): placeholder_user {
                    return placeholder_user::from_id($target_user_id);
                }
            ),
            placeholder_option::create(
                'event',
                placeholder_event::class,
                new lang_string('notification_placeholder_group_event', 'mod_facetoface'),
                function (array $event_data): placeholder_event {
                    return placeholder_event::from_event_id($event_data['seminar_event_id']);
                }
            ),
            placeholder_option::create(
                'activity',
                placeholder_activity::class,
                new lang_string('placeholder_group_course_module'),
                function (array $event_data): placeholder_activity {
                    return placeholder_activity::from_id($event_data['module_id']);
                }
            ),
            placeholder_option::create(
                'course',
                placeholder_course::class,
                new lang_string('placeholder_group_course'),
                function (array $event_data): placeholder_course {
                    return placeholder_course::from_id($event_data['course_id']);
                }
            )
        ];
    }

    /**
     * This is to check whether the resolver is processed through event queue or not and also it could be override if
     * dev want to skip queueing up.
     *
     * @return bool
     */
    public static function uses_on_event_queue(): bool {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function get_notification_log_display_string_key_and_params(): array {
        // The resolver title is translated at view time
        $params = ['resolver_title' => ''];

        $course = placeholder_course::from_id($this->get_event_data()['course_id']);
        $params['course'] = $course->do_get('full_name');

        $event = placeholder_event::from_event_id($this->get_event_data()['seminar_event_id']);
        $params['date'] = $event->do_get('start_timestamp');

        $activity = placeholder_activity::from_id($this->get_event_data()['module_id']);
        $params['activity'] = $activity->do_get('name');

        return [
            'key' => 'notification_log_event_reservations_cancelled',
            'component' => 'mod_facetoface',
            'params' => $params,
        ];
    }

}
