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
use core\orm\entity\model;
use totara_notification\entity\notification_delivery_log as entity;

class notification_delivery_log extends model {
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
     *
     * @return notification_delivery_log
     */
    public static function from_entity(entity $entity): notification_delivery_log {
        if (!$entity->exists()) {
            throw new coding_exception("Cannot instantiate a notification event log from a non-existing entity");
        }

        return new notification_delivery_log($entity);
    }

    /**
     * @param notification_log $notification_log
     * @param string $delivery_channel
     * @param int $time_created
     * @param string $address
     * @param bool $has_error
     *
     * @return notification_delivery_log
     */
    public static function create(
        int $notification_log_id,
        string $delivery_channel,
        int $time_created,
        ?string $address = null,
        bool $has_error = false
    ): notification_delivery_log {

        $entity = new entity();
        $entity->notification_log_id = $notification_log_id;
        $entity->delivery_channel = $delivery_channel;
        $entity->time_created = $time_created;
        $entity->address = $address;
        $entity->has_error = $has_error;

        $entity->save();

        return static::from_entity($entity);
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