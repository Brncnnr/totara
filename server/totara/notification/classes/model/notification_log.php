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

namespace totara_notification\model;

use coding_exception;
use core\orm\collection;
use core\orm\entity\model;
use core\orm\query\builder;
use totara_notification\entity\notification_log as entity;

/**
 * @property-read collection|notification_delivery_log[] $notification_delivery_logs
 */
class notification_log extends model {
    /**
     * @var entity
     */
    protected $entity;

    /**
     * @param entity $entity
     */
    private function __construct(entity $entity) {
        parent::__construct($entity);
    }

    /**
     * @return string
     */
    protected static function get_entity_class(): string {
        return entity::class;
    }

    /**
     * @param entity $entity
     * @return notification_log
     */
    public static function from_entity(entity $entity): notification_log {
        if (!$entity->exists()) {
            throw new coding_exception("Cannot instantiate a notification event log from a non-existing entity");
        }

        return new notification_log($entity);
    }

    /**
     * @param int $notification_event_log_id
     * @param int $preference_id
     * @param int $recipient_user_id
     * @param int $time_created
     * @param bool $has_error
     * @return notification_log
     */
    public static function create(
        int $notification_event_log_id,
        int $preference_id,
        int $recipient_user_id,
        int $time_created,
        bool $has_error = false
    ): notification_log {

        $entity = new entity();
        $entity->notification_event_log_id = $notification_event_log_id;
        $entity->preference_id = $preference_id;
        $entity->recipient_user_id = $recipient_user_id;
        $entity->time_created = $time_created;
        $entity->has_error = $has_error;

        $entity->save();

        return static::from_entity($entity);
    }

    /**
     * @return collection|notification_delivery_log[]
     */
    public function get_notification_delivery_logs(): collection {
        return  $this->entity->notification_delivery_logs->map_to(notification_delivery_log::class);
    }

    /**
     * Get all entity properties
     *
     * @return array
     */
    public function to_array(): array {
        return $this->entity->to_array();
    }

    /**
     * @return void
     */
    public function save(): void {
        $this->entity->save();
    }

    /**
     * @return bool
     */
    public function exists(): bool {
        return $this->entity->exists();
    }
}