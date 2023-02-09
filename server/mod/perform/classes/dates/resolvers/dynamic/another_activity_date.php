<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\dates\resolvers\dynamic;

use core\collection;
use core\orm\query\builder;
use mod_perform\entity\activity\subject_instance;
use mod_perform\entity\activity\track as track_entity;
use mod_perform\entity\activity\track_user_assignment;
use mod_perform\dates\constants;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\track;

class another_activity_date extends base_dynamic_date_resolver {

    public const ACTIVITY_COMPLETED_DAY = 'activity_completed_day';
    public const ACTIVITY_INSTANCE_CREATION_DAY = 'activity_instance_creation_day';
    public const ACTIVITY_CLOSED_DAY = 'activity_closed_day';
    public const ACTIVITY_CLOSED_OR_COMPLETED_DAY = 'activity_closed_or_completed_day';

    /**
     * @inheritDoc
     */
    protected function resolve(): void {
        $custom_data = json_decode($this->get_custom_data(), true);
        switch ($this->option_key) {
            case self::ACTIVITY_CLOSED_OR_COMPLETED_DAY:
                $this->date_map = self::closed_or_completed_date_map($custom_data['activity'], $this->bulk_fetch_keys);
                break;
            case self::ACTIVITY_CLOSED_DAY:
                $timestamp_field_name = 'closed_at';
                $this->date_map = self::get_date_map($custom_data['activity'], $timestamp_field_name, $this->bulk_fetch_keys);
                break;
            case self::ACTIVITY_COMPLETED_DAY:
                $timestamp_field_name = 'completed_at';
                $this->date_map = self::get_date_map($custom_data['activity'], $timestamp_field_name, $this->bulk_fetch_keys);
                break;
            default:
                $timestamp_field_name = 'created_at';
                $this->date_map = self::get_date_map($custom_data['activity'], $timestamp_field_name, $this->bulk_fetch_keys);
                break;
        }
    }

    /**
     * Return available source options.
     *
     * @return collection
     */
    public function get_options(): collection {
        $options = [
            new dynamic_source(
                $this,
                self::ACTIVITY_COMPLETED_DAY,
                get_string(
                    'schedule_dynamic_another_activity_completion_date',
                    'mod_perform'
                )
            ),
            new dynamic_source(
                $this,
                self::ACTIVITY_INSTANCE_CREATION_DAY,
                get_string(
                    'schedule_dynamic_another_activity_instance_creation_date',
                    'mod_perform'
                )
            ),
            new dynamic_source(
                $this,
                self::ACTIVITY_CLOSED_DAY,
                get_string(
                    'schedule_dynamic_another_activity_close_date',
                    'mod_perform'
                )
            ),
            new dynamic_source(
                $this,
                self::ACTIVITY_CLOSED_OR_COMPLETED_DAY,
                get_string(
                    'schedule_dynamic_another_activity_close_or_completion_date',
                    'mod_perform'
                )
            ),
        ];
        return new collection($options);
    }

    /**
     * @param string $option_key
     *
     * @return bool
     */
    public function option_is_available(string $option_key): bool {
        return in_array(
            $option_key,
            [
                self::ACTIVITY_INSTANCE_CREATION_DAY,
                self::ACTIVITY_COMPLETED_DAY,
                self::ACTIVITY_CLOSED_DAY,
                self::ACTIVITY_CLOSED_OR_COMPLETED_DAY
            ]
        );
    }

    /**
     * get custom setting VUE component
     *
     * @return string|null
     */
    public function get_custom_setting_component(): ?string {
        return 'mod_perform/components/manage_activity/assignment/schedule/custom_settings/ActivitySelector';
    }

    /**
     * returns default values when custom data is empty
     *
     * @return string|null
     */
    public function get_custom_data(): ?string {
        if (!$this->custom_data) {
            return json_encode(['activity' => null]);
        }

        return $this->custom_data;
    }

    /**
     * @param string|null $custom_data
     *
     * @return bool
     */
    public function is_valid_custom_data(?string $custom_data): bool {
        $data = json_decode($custom_data, true);
        return isset($data['activity']) && is_number($data['activity']);
    }

    /**
     * @inheritDoc
     */
    public function get_resolver_base(): string {
        return constants::DATE_RESOLVER_USER_BASED;
    }

    /**
     * Generate a date map records for "Closed or Completed date of another activity instance(Whichever is sooner)" reference date option
     *
     * @param int $activity_id
     * @param array $subject_user_ids
     * @return array
     */
    private static function closed_or_completed_date_map(int $activity_id, array $subject_user_ids): array {
        $date_map = [];
        $records = builder::create()
            ->select(['si.subject_user_id', 'max(si.completed_at) as completed_date', 'max(si.closed_at) as closed_date'])
            ->from(subject_instance::TABLE, 'si')
            ->join([track_user_assignment::TABLE, 'tua'], 'si.track_user_assignment_id', 'id')
            ->join([track_entity::TABLE, 'tr'], 'tua.track_id', 'id')
            ->where('tr.activity_id', $activity_id)
            ->where_in('si.subject_user_id', $subject_user_ids)
            ->group_by('si.subject_user_id')
            ->get();
        foreach ($records as $raw) {
            if (is_null($raw->completed_date) && is_null($raw->closed_date)) {
                continue;
            } elseif (is_null($raw->completed_date) && !is_null($raw->closed_date)) {
                $date_map[$raw->subject_user_id] = $raw->closed_date;
            } elseif (!is_null($raw->completed_date) && is_null($raw->closed_date)) {
                $date_map[$raw->subject_user_id] = $raw->completed_date;
            } else {
                $date_map[$raw->subject_user_id] = ($raw->completed_date > $raw->closed_date) ?
                    $raw->closed_date :
                    $raw->completed_date;
            }
        }
        return $date_map;
    }

    /**
     * Generate a date map records for
     * "Closed date of another activity instance" reference date option OR
     * "Completed date of another activity instance" reference date option OR
     * "Created date of another activity instance" reference date option
     *
     * @param int $activity_id
     * @param string $timestamp_field_name
     * @param array $subject_user_ids
     * @return array
     */
    private static function get_date_map(int $activity_id, string $timestamp_field_name, array $subject_user_ids): array {
        return builder::create()
            ->select(['si.subject_user_id', "max(si.{$timestamp_field_name}) as user_reference_date"])
            ->from(subject_instance::TABLE, 'si')
            ->join([track_user_assignment::TABLE, 'tua'], 'si.track_user_assignment_id', 'id')
            ->join([track_entity::TABLE, 'tr'], 'tua.track_id', 'id')
            ->where('tr.activity_id', $activity_id)
            ->where_not_null("si.{$timestamp_field_name}")
            ->where_in('si.subject_user_id', $subject_user_ids)
            ->group_by('si.subject_user_id')
            ->get()
            ->map(function ($row) {
                // Using map (rather than pluck) to preserve keys.
                return $row->user_reference_date;
            })
            ->all(true);
    }
}
