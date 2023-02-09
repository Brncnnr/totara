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
 * @author  Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_notification
 */

namespace totara_notification\schedule;

/**
 * Schedule representing on the actual time of the event.
 * No offset is calculated.
 */
class schedule_on_event implements notification_schedule {
    /**
     * On Event will just return the timestamp - no further calculations are needed.
     *
     * @param int $event_timestamp
     * @param int $offset
     * @return int
     */
    public static function calculate_timestamp(int $event_timestamp, int $offset): int {
        return $event_timestamp;
    }

    /**
     * @param int $offset
     * @return string
     */
    public static function get_label(int $offset): string {
        return get_string('schedule_label_on_event', 'totara_notification');
    }

    /**
     * @return string
     */
    public static function identifier(): string {
        return 'ON_EVENT';
    }

    /**
     * @param int|null $days_offset
     * @return int
     */
    public static function default_value(?int $days_offset = null): int {
        return 0;
    }

    /**
     * Only 0 is acceptable.
     *
     * @param int|null $offset
     * @return bool
     */
    public static function validate_offset(?int $offset = null): bool {
        return $offset === 0;
    }
}