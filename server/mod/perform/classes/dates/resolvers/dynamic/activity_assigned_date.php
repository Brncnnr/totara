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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_perform
 */
namespace mod_perform\dates\resolvers\dynamic;

use core\collection;
use core\orm\query\builder;
use lang_string;
use mod_perform\dates\constants;
use mod_perform\entity\activity\track;
use mod_perform\entity\activity\track_user_assignment;

class activity_assigned_date extends base_dynamic_date_resolver {

    public const DEFAULT_KEY = 'default';
    public const THIS_ACTIVITY_ID = 'this_activity_id';

    /**
     * @var int
     */
    private $activity_id;

    /**
     * @inheritDoc
     */
    protected function resolve(): void {
        // Now will be used as a default if there is no track_user_assignment for a user.
        $now = $this->get_time();

        // Load the track_user_assignment.created_on field for the specified users.
        $this->date_map = builder::create()
            ->from(track_user_assignment::TABLE)
            ->as('user_assignment')
            ->select_raw('subject_user_id, min(user_assignment.created_at) min_created_at')
            ->join([track::TABLE, 'track'], 'user_assignment.track_id', 'track.id')
            ->where('user_assignment.subject_user_id', $this->bulk_fetch_keys)
            ->where('track.activity_id', $this->activity_id)
            ->group_by('subject_user_id')
            ->get()
            ->map(function ($row) {
                // Using map (rather than pluck) to preserve keys.
                return $row->min_created_at;
            })->all(true);

        foreach ($this->bulk_fetch_keys as $user_id) {
            if (!isset($this->date_map[$user_id])) {
                $this->date_map[$user_id] = $now;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function get_options(): collection {
        return new collection(
            [$this->make_option(static::DEFAULT_KEY)]
        );
    }

    /**
     * @inheritDoc
     */
    public function option_is_available(string $option_key): bool {
        return in_array($option_key, $this->get_supported_option_keys(), true);
    }


    /**
     * @return array|string[]
     */
    protected function get_supported_option_keys(): array {
        return [static::DEFAULT_KEY];
    }


    /**
     * @param string|null $option_key
     * @return lang_string|string
     */
    protected function get_option_display_name(?string $option_key) {
        return get_string('activity_first_assigned_date', 'mod_perform');
    }


    /**
     * @param string $option_key
     * @return dynamic_source
     */
    protected function make_option(string $option_key): dynamic_source {
        return new dynamic_source(
            $this,
            $option_key,
            $this->get_option_display_name($option_key)
        );
    }

    /**
     * returns default values when custom data is empty
     *
     * @return string|null
     */
    public function get_custom_data(): ?string {
        return json_encode([self::THIS_ACTIVITY_ID => $this->activity_id]);
    }

    /**
     * @param string|null $custom_data
     */
    public function set_custom_data(?string $custom_data): void {
        if (!$this->is_valid_custom_data($custom_data)) {
            throw new \coding_exception('Invalid custom data, custom data must include "this_activity_id" (int)');
        }

        $data = json_decode($custom_data, true);

        $this->activity_id = $data[self::THIS_ACTIVITY_ID];
    }

    /**
     * @param string|null $custom_data
     *
     * @return bool
     */
    public function is_valid_custom_data(?string $custom_data): bool {
        $data = json_decode($custom_data, true);

        return isset($data[self::THIS_ACTIVITY_ID]) && is_number($data[self::THIS_ACTIVITY_ID]);
    }

    /**
     * @inheritDoc
     */
    public function get_resolver_base(): string {
        return constants::DATE_RESOLVER_USER_BASED;
    }

}
