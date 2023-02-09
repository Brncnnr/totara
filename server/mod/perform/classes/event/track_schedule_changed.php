<?php
/**
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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\event;

use core\event\base;
use mod_perform\entity\activity\track as track_entity;
use mod_perform\event\helper\track_schedule;

/**
 * Class track_schedule_changed event is triggered when a user changes an activity
 * track's scheduling details.
 *
 * @package mod_perform\event
 */
class track_schedule_changed extends base {
    /**
     * @inheritDoc
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = track_entity::TABLE;
    }

    /**
     * Create instance of event.
     *
     * @param track_schedule $original original track schedule.
     * @param track_schedule $changed new track schedule.
     *
     * @return self|base
     */
    public static function create_from_track_schedules(
        track_schedule $original,
        track_schedule $changed
    ): self {
        $pre_values = self::schedule_properties($original, 'pre');
        $post_values = self::schedule_properties($changed, 'post');

        $data = [
            'objectid' => $original->get_track()->id,
            'other' => array_merge($pre_values, $post_values),
            'context' => $original->get_track()->activity->get_context()
        ];

        return static::create($data);
    }

    /**
     * Extracts schedule properties to be recorded in the event.
     *
     * @param track_schedule $schedule the schedule whose properties are to extracted.
     * @param string $prefix each key in the returned array starts with this value.
     *
     * @return array the schedule properties.
     */
    private static function schedule_properties(
        track_schedule $schedule,
        string $prefix
    ): array {
        $is_fixed = $schedule->is_fixed();
        $is_open = $schedule->has_end_date();

        $raw = [
            'is_open' => $is_open,
            'is_fixed' => $is_fixed,
            'fixed_from' => '',
            'fixed_to' => '',
            'dynamic_source' => '',
            'dynamic_from' => '',
            'dynamic_to' => '',
            'due_date' => ''
        ];

        $subject_instance_schedule = $schedule->get_schedule();
        if ($subject_instance_schedule) {
            $start_date = $subject_instance_schedule->get_start_date_formatted();
            $end_date = $subject_instance_schedule->get_end_date_formatted();

            if ($is_fixed) {
                $raw['fixed_from'] = $start_date;
                $raw['fixed_to'] = $end_date;
            } else {
                $raw['dynamic_source'] = $subject_instance_schedule->get_trigger_name();
                $raw['dynamic_from'] = $start_date;
                $raw['dynamic_to'] = $end_date;
            }
        }

        $due_date = $schedule->get_due_date();
        if ($due_date) {
            $raw['due_date'] = $schedule->is_due_date_fixed()
                ? $due_date->get_date_formatted()
                : $due_date->get_formatted() . ' each instance creation date';
        }

        $properties = [];
        foreach ($raw as $key => $value) {
            $properties[$prefix . '_' . $key] = $value;
        }

        return $properties;
    }

    /**
     * @inheritDoc
     */
    public static function get_name() {
        return get_string('event_track_schedule_changed', 'mod_perform');
    }

    /**
     * @inheritDoc
     */
    public function get_description() {
        $original_schedule = $this->schedule_description('pre');
        $new_schedule = $this->schedule_description('post');

        return "The schedule details for the track with id '$this->objectid'"
             . " was changed by the user with id '$this->userid';"
             . " originally $original_schedule; now $new_schedule";
    }

    /**
     * Formulates a description of the schedule properties with keys starting
     * with the given prefix.
     *
     * @param string $prefix schedule prefix.
     *
     * @return string the description.
     */
    private function schedule_description(string $prefix): string {
        $is_open = $this->other[$prefix . '_is_open'];
        $is_fixed = $this->other[$prefix . '_is_fixed'];

        $schedule = "";
        if ($is_fixed) {
            $start = $this->other[$prefix . '_fixed_from'];
            $to_end = $is_open ? '' : 'to ' . $this->other[$prefix . '_fixed_to'];

            $schedule = trim("from $start $to_end");
        } else {
            $start_of = $this->other[$prefix . '_dynamic_from'];
            $to_end = $is_open ? '' : 'to '. $this->other[$prefix . '_dynamic_to'];
            $event = $this->other[$prefix . '_dynamic_source'];

            $schedule = trim("from $start_of $event $to_end");
        }

        $due_date = $this->other[$prefix . '_due_date'] ?? null;
        $with_due_date =  $due_date ? "with due date $due_date" : "with no due date";

        return "$schedule $with_due_date";
    }
}
