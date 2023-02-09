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
 * @author  Gihan Hewaralalage <gihan.hewaralalage@totaralearning.com>
 * @package totara_notification
 */

namespace totara_notification\entity;

use coding_exception;
use core\orm\collection;
use core\orm\entity\entity;
use core\orm\entity\relations\has_many;
use totara_notification\repository\notification_event_log_repository;

/**
 * Entity class represent for table "notification_event_log"
 *
 * @property int    $id
 * @property int    $context_id
 * @property int    $subject_user_id
 * @property int    $item_id
 * @property string $resolver_class_name
 * @property string $event_data     A json string, please use {@see notification_event_log::get_decoded_event_data()}
 *                                  for a decoded version of this attribute. Note that the result returned will be an array.
 * @property string $component
 * @property string $area
 * @property string $schedule_type
 * @property string $schedule_offset
 * @property string $display_string_key
 * @property string $display_string_params
 * @property int    $has_error
 * @property int    $time_created
 *
 * Relationships:
 * @property-read collection|notification_log[] $notification_logs
 *
 * @method static notification_event_log_repository repository()
 */
class notification_event_log extends entity {
    /**
     * @var string
     */
    public const TABLE = 'notification_event_log';

    /**
     * @var string
     */
    public const CREATED_TIMESTAMP = 'time_created';

    /**
     * @return array
     */
    public function get_decoded_event_data(): array {
        $json_data = $this->get_attribute('event_data');
        $result = json_decode($json_data, true);

        if (JSON_ERROR_NONE != json_last_error()) {
            throw new coding_exception(
                "Cannot decode the json data due to: " . json_last_error_msg()
            );
        }

        return $result;
    }

    /**
     * Encode the event data as JSON
     *
     * @param array $event_data
     * @return string|null
     */
    public static function encode_event_data(array $event_data): string {
        return json_encode($event_data, JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR);
    }

    /**
     * @param array $decoded_data
     * @return void
     */
    public function set_decoded_event_data(array $decoded_data): void {
        $this->set_attribute('event_data', self::encode_event_data($decoded_data));
    }

    /**
     * @return string
     */
    public static function repository_class_name(): string {
        return notification_event_log_repository::class;
    }

    /**
     * Notification logs for this notification event.
     *
     * @return has_many
     */
    public function notification_logs(): has_many {
        return $this->has_many(notification_log::class, 'notification_event_log_id');
    }
}