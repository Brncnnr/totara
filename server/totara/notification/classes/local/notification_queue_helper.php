<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */
namespace totara_notification\local;

use totara_notification\entity\notification_queue;
use totara_notification\model\notification_preference;
use totara_notification\resolver\notifiable_event_resolver;
use totara_notification\resolver\resolver_helper;

/**
 * A static class that contains helper methods to create the queue and what not.
 */
class notification_queue_helper {
    /**
     * notification_queue_helper constructor.
     */
    private function __construct() {
        // Prevent the construction of this class.
    }

    /**
     * @param notification_preference $preference
     * @param array                   $event_data
     * @param int                     $event_time
     * @return notification_queue
     */
    public static function create_queue_from_preference(
        notification_preference $preference,
        array $event_data,
        int $event_time
    ): notification_queue {
        $resolver_class_name = $preference->get_resolver_class_name();
        $resolver = resolver_helper::instantiate_resolver_from_class($resolver_class_name, $event_data);

        $queue = new notification_queue();
        $queue->notification_preference_id = $preference->get_id();
        $queue->set_decoded_event_data($event_data);
        $queue->set_extended_context($resolver->get_extended_context());

        $queue->scheduled_time = schedule_helper::calculate_schedule_timestamp(
            $event_time,
            $preference->get_schedule_offset()
        );

        $queue->save();
        return $queue;
    }
}