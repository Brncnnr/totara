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

use coding_exception;
use core\orm\query\builder;
use totara_notification\factory\built_in_notification_factory;
use totara_notification\resolver\notifiable_event_resolver;
use totara_notification\resolver\resolver_helper;

/**
 * Given the resolver class name as a dependency of the class, the class will calculate
 * the maximum and minimum schedule offsets from the system that are associated
 * with the resolver classs.
 */
class event_resolver_schedule {
    /**
     * @var string
     */
    private $resolver_class_name;

    /**
     * Cached result of {@see notifiable_event_resolver::uses_on_event_queue()}
     * @var bool
     */
    private $uses_on_event_queue;

    /**
     * event_resolver_schedule constructor.
     * @param string $resolver_class_name
     * @param bool   $uses_on_event_queue
     */
    private function __construct(string $resolver_class_name, bool $uses_on_event_queue) {
        $this->resolver_class_name = $resolver_class_name;
        $this->uses_on_event_queue = $uses_on_event_queue;
    }

    /**
     * @param string $resolver_class_name
     * @return event_resolver_schedule
     */
    public static function instance(string $resolver_class_name): event_resolver_schedule {
        if (!resolver_helper::is_valid_event_resolver($resolver_class_name)) {
            throw new coding_exception("The resolver class is not a valid notifiable event resolver");
        }

        /**
         * @see notifiable_event_resolver::uses_on_event_queue()
         * @var bool $uses_on_event_queue
         */
        $uses_on_event_queue = call_user_func([$resolver_class_name, 'uses_on_event_queue']);
        return new static($resolver_class_name, $uses_on_event_queue);
    }

    /**
     * @return array
     */
    private function get_offsets_from_built_in_notifications(): array {
        // Note we do not check for the validability of the resolver class name,
        // as the function itself has a check in it already.
        $built_in_classes = built_in_notification_factory::get_notification_classes_of_event_resolver($this->resolver_class_name);
        $offsets = array_map(
            function (string $built_in_class): int {
                /** @see built_in_notification::get_default_schedule_offset() */
                return call_user_func([$built_in_class, 'get_default_schedule_offset']);
            },
            $built_in_classes
        );

        if (!$this->uses_on_event_queue) {
            return $offsets;
        }

        // Exclude those on event built in notification preference if the resolver does not have associated
        // notification event.
        return array_filter(
            $offsets,
            function (int $offset): bool {
                return !schedule_helper::is_on_event($offset);
            }
        );
    }

    /**
     * Returns the min/max from the database, or null if nothing is found.
     *
     * @param bool $min Pass this parameter down as true if you are looking for minimum number in the database.
     *                  Otherwise FALSE to get the maximum.
     * @return int
     */
    private function get_min_or_max_offset_from_database(bool $min): ?int {
        $builder = builder::table('notification_preference');
        $builder->where_not_null('schedule_offset');
        $builder->where('resolver_class_name', $this->resolver_class_name);

        $builder->when(
            $min,
            function (builder $inner_builder): void {
                $inner_builder->select_raw('MIN(schedule_offset) AS "offset"');
            },
            function (builder $inner_builder): void {
                $inner_builder->select_raw('MAX(schedule_offset) AS "offset"');
            }
        );

        // Remove those on_event notification preference if the notifiable_event_resolver does have
        // associated notifiable event.
        $builder->when(
            $this->uses_on_event_queue,
            function (builder $inner_builder): void {
                $inner_builder->where('schedule_offset', '<>', 0);
            }
        );

        $builder->results_as_arrays();

        $record = $builder->one();
        return $record['offset'];
    }

    /**
     * Returns a minimum number in table "ttr_notification_preference" which is
     * in seconds.
     *
     * Note: if the resolver class name does have the associated event interface then we will try to get
     * the minimum number between after/before schedule offset, exclude those on event offset (zero).
     * Otherwise, zero is included in the return.
     *
     * If the resolver does not have associated event and also does not provide any after/before offset
     * then null should be returned, because zero is excluded.
     *
     * @return int
     */
    public function get_minimum_offset(): ?int {
        $offsets_from_built_in = $this->get_offsets_from_built_in_notifications();
        $min_offset_from_built_in = null;

        if (!empty($offsets_from_built_in)) {
            $min_offset_from_built_in = min($offsets_from_built_in);
        }

        $min_offset_from_db = $this->get_min_or_max_offset_from_database(true);

        if (null === $min_offset_from_built_in) {
            return $min_offset_from_db;
        } else if (null === $min_offset_from_db) {
            return $min_offset_from_built_in;
        }

        // Return whatever the lowest, it can be zero.
        return min($min_offset_from_built_in, $min_offset_from_db);
    }

    /**
     * Returns a maximum number in table "ttr_notification_preference" which is
     * in seconds.
     *
     * Note: if the resolver class name does have the associated event interface then we will try to get
     * the maximum number between after/before schedule offset, exclude those on event offset (zero).
     * Otherwise, zero is included in the return.
     *
     * If the resolver does not have associated event and also does not provide any after/before offset
     * then null should be returned, because zero is excluded.
     *
     * @return int|null
     */
    public function get_maximum_offset(): ?int {
        $offsets_from_built_in = $this->get_offsets_from_built_in_notifications();

        // Default to null.
        $max_offset_from_built_in = null;

        if (!empty($offsets_from_built_in)) {
            $max_offset_from_built_in = max($offsets_from_built_in);
        }

        $max_offset_from_db = $this->get_min_or_max_offset_from_database(false);

        if (null === $max_offset_from_built_in) {
            return $max_offset_from_db;
        }

        // Return whatever the largest, it can be zero.
        // Note: max(null, 0) => null and max(0, null) => null. But it would not be a case here
        // as $max_offset_from_built_in will always have a value.
        return max($max_offset_from_built_in, $max_offset_from_db);
    }

    /**
     * @return bool
     */
    public function uses_on_event_queue(): bool {
        return $this->uses_on_event_queue;
    }

    /**
     * @return string
     */
    public function get_resolver_class_name(): string {
        return $this->resolver_class_name;
    }
}