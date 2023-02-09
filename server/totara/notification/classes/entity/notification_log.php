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

use core\orm\collection;
use core\orm\entity\entity;
use core\orm\entity\relations\has_many;
use totara_notification\repository\notification_log_repository;


/**
 * Entity class represent for table "notification_log"
 *
 * @property int    $id
 * @property int    $notification_event_log_id
 * @property int    $preference_id
 * @property int    $recipient_user_id
 * @property int    $time_created
 * @property int    $has_error
 *
 * Relationships:
 * @property-read collection|notification_delivery_log[] $notification_delivery_logs
 *
 * @method static notification_log_repository repository()
 *
 */
class notification_log extends entity {
    /**
     * @var string
     */
    public const TABLE = 'notification_log';

    /**
     * @return string
     */
    public static function repository_class_name(): string {
        return notification_log_repository::class;
    }

    /**
     * Notification delivery logs for this notification log.
     *
     * @return has_many
     */
    public function notification_delivery_logs(): has_many {
        return $this->has_many(notification_delivery_log::class, 'notification_log_id');
    }
}