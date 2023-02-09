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
use totara_core\extended_context;
use totara_notification\entity\notification_event_log as entity;

/**
 * @property-read collection|notification_log[] $notification_logs
 */
class notification_event_log extends model {
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
     * @return extended_context
     */
    public function get_extended_context(): extended_context {
        return extended_context::make_with_id(
            $this->entity->get_attribute('context_id'),
            $this->entity->get_attribute('component'),
            $this->entity->get_attribute('area'),
            $this->entity->get_attribute('item_id')
        );
    }

    /**
     * @param entity $entity
     * @return notification_event_log
     */
    public static function from_entity(entity $entity): notification_event_log {
        if (!$entity->exists()) {
            throw new coding_exception("Cannot instantiate a notification event log from a non-existing entity");
        }

        return new notification_event_log($entity);
    }

   /**
     * @param string $resolver_class_name
     * @param extended_context $extended_context
     * @param int $subject_user_id
     * @param array $event_data
     * @param string $schedule_type
     * @param string $schedule_offset
     * @param string $display_string_key
     * @param array $display_string_params
     * @param int $time_created
     * @param bool $has_error
     * @return notification_event_log
     */
    public static function create_if_not_exist(
        string $resolver_class_name,
        extended_context $extended_context,
        int $subject_user_id,
        array $event_data,
        string $schedule_type,
        string $schedule_offset,
        string $display_string_key,
        array $display_string_params,
        int $time_created,
        bool $has_error = false
    ): notification_event_log {
        $existing_log = null;
        $encoded_event_data = entity::encode_event_data($event_data);

        /** @var entity[] $logs */
        $logs = entity::repository()
            ->where('subject_user_id', $subject_user_id)
            ->where('resolver_class_name', $resolver_class_name)
            ->where('schedule_type', $schedule_type)
            ->where('schedule_offset', $schedule_offset)
            ->where('time_created', $time_created)
            ->filter_by_extended_context($extended_context)
            ->get();

        // If there's a log entry with the same event_data as the given one
        // use it. We do not include it in the query as it should be quicker to
        // compare this in code than querying on a non-indexed text field.
        foreach ($logs as $log) {
            if ($log->event_data == $encoded_event_data) {
                $existing_log = $log;
                break;
            }
        }

        if (!$existing_log) {
            return static::create(
                $resolver_class_name,
                $extended_context,
                $subject_user_id,
                $event_data,
                $schedule_type,
                $schedule_offset,
                $display_string_key,
                $display_string_params,
                $time_created,
                $has_error
            );
        }

        if ($has_error && $has_error != $existing_log->has_error) {
            $existing_log->has_error = $has_error;
            $existing_log->save();
        }

        return static::from_entity($existing_log);
    }

    /**
     * @param string $resolver_class_name
     * @param extended_context $extended_context
     * @param int $subject_user_id
     * @param array $event_data
     * @param string $schedule_type
     * @param string $schedule_offset
     * @param string $display_string_key
     * @param array $display_string_params
     * @param int $time_created
     * @param bool $has_error
     * @return notification_event_log
     */
    public static function create(
        string $resolver_class_name,
        extended_context $extended_context,
        int $subject_user_id,
        array $event_data,
        string $schedule_type,
        string $schedule_offset,
        string $display_string_key,
        array $display_string_params,
        int $time_created,
        bool $has_error = false
    ): notification_event_log {

        $entity = new entity();
        $entity->subject_user_id = $subject_user_id;
        $entity->resolver_class_name = $resolver_class_name;
        $entity->context_id = $extended_context->get_context_id();
        $entity->component = $extended_context->get_component();
        $entity->area = $extended_context->get_area();
        $entity->item_id = $extended_context->get_item_id();
        $entity->set_decoded_event_data($event_data);
        $entity->schedule_type = $schedule_type;
        $entity->schedule_offset = $schedule_offset;
        $entity->display_string_key = $display_string_key;
        $entity->display_string_params = json_encode($display_string_params);
        $entity->time_created = $time_created;
        $entity->has_error = $has_error;

        $entity->save();

        return static::from_entity($entity);
    }

    /**
     * @return collection|notification_log[]
     */
    public function get_notification_logs(): collection {
        return  $this->entity->notification_logs->map_to(notification_log::class);
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