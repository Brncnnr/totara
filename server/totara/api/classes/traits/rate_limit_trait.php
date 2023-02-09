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
 * @author Michael Ivanov <michael.ivanov@totaralearning.com>
 * @package totara_api
 */

namespace totara_api\traits;

use core\orm\entity\entity;
use core\orm\query\builder;
use totara_api\global_api_config;

/**
 * This trait should be used with *_rate_limit models only
 *
 * The trait implements sliding window algorithm for rate limiting
 * It needs the following fields to be working as expected
 *
 * @property-read int|null $current_window_reset_time The last time the sampling period was rotated
 * @property-read int|null $prev_window_value The accumulated complexity value from the previous sampling period
 * @property-read int|null $current_window_value The accumulated complexity value for the current period
 * @property-read int|null $current_limit The maximum complexity limit for this entity, cached for performance reasons
 * @property-read entity $entity
 *
 * Algorithm details:
 * Increment a counter $current_window_value that we reset at the start of each sampling period.
 * The counter is reset at regular intervals which is currently set to one minute
 * (referenced as $window_size in the code).
 * This algorithm tries to solve the traffic spike problem which occurs with fixed window algorithm by saving
 * the number of requests that were served in the previous time slot, see $prev_window_value.
 * Let's say we have set our rate limit to 500 complexity points per hour and we reset the counter every hour.
 * Then let's assume we got 400 points from 10:00 to 11:00 and we got 200 more points from 11:00 to 11:15.
 * If one more request comes in at 11:15 we will calculate whether it is allowed using the formula below
 * $current_rate = 400 * ((60-15) / 60) + 200 = 400 * 0.75 + 200 = 500
 * Since the calculated rate is at our set limit we would deny the request.
 * The sliding window approach approximates the rate of requests based on the past usage pattern.
 */
trait rate_limit_trait {

    /**
     * Whether there is capacity to handle the complexity of the resolver
     * @param int $value Resolver complexity
     * @param int $window_size The size of the sliding window
     * @param int|null $time The cutoff time of the sliding window, assume current time if null
     * @return bool
     */
    public function has_capacity(int $value, int $window_size = 60, ?int $time = null): bool {
        if (is_null($time)) {
            $time = time();
        }

        $time_diff = $time - $this->current_window_reset_time;

        if ($time_diff < 0) {
            if ($time_diff > -5) {
                // Ignore if the reset time is within 5 seconds in the future
                $time_diff = 0;
                $time = $this->current_window_reset_time;
            } else {
                // Assume we have the capacity since we can't calculate if we do when the reset time is in the future
                debugging(
                    'The rate limiting reset time is in the future! It is likely that you have multiple '
                    . 'web instances of Totara running and some of them have their server time out of sync',
                    DEBUG_DEVELOPER
                );
                return true;
            }
        }

        if ($time_diff > $window_size) {
            // Rotate the values if we are more than a window past the reset time
            $this->rotate_values($time);
            $time_diff = 0;
        }

        switch (true) {
            case is_null($this->current_limit):
                // Do not apply rate limiting if the current limit is null
                return true;
            case $value > $this->current_limit:
                // The passed complexity value is greater than the current limit, no reason to calculate further
                return false;
            case $time_diff < $window_size:
                $current_rate = round($this->prev_window_value * (($window_size - $time_diff) / $window_size))
                    + $this->current_window_value;
                return $current_rate + $value <= $this->current_limit;
            default:
                /*
                * If the keys were rotated by the concurrent request and we were waiting for a DB lock for more
                * than a window size then let the request through
                */
                return true;
        }
    }

    /**
     * Increment the accumulated complexity for the current window
     * @param int $value Resolver complexity
     * @return void
     */
    public function add_value(int $value): void {
        // Run raw SQL query to avoid data out-of-date issues
        $sql = builder::get_db()->sql(
            "UPDATE {{$this->entity->get_table()}}"
            . " SET current_window_value = current_window_value + ?"
            . " WHERE id = ?",
            [$value, $this->entity->id]
        );
        builder::get_db()->execute($sql);
        $this->entity->refresh();
    }

    /**
     * Read and cache the current limit from a corresponding entity
     * Can be set at global/client level
     * @return int|null
     */
    protected function calculate_current_limit(): ?int {
        if (isset($this->client)) {
            $rate_limit = $this->client->client_settings->client_rate_limit ?? null;
            $global_client_rate_limit = global_api_config::get_client_rate_limit();

            // Make sure the global per client setting is not lower than per client setting
            if (!is_null($global_client_rate_limit)) {
                if (is_null($rate_limit)) {
                    $rate_limit = $global_client_rate_limit;
                } else {
                    $rate_limit = min($rate_limit, $global_client_rate_limit);
                }
            }
        } else {
            $rate_limit = global_api_config::get_site_rate_limit();
        }
        return isset($rate_limit) ? (int) $rate_limit : null;
    }

    /**
     * Replace previous window complexity value with the value from the current window and update the reset time
     * @param int|null $time
     * @return void
     */
    public function rotate_values(?int $time = null): void {
        $current_limit = $this->calculate_current_limit();
        if (is_null($time)) {
            $time = time();
        }

        // Make sure the current_window_value is up-to-date before the update
        $this->refresh();
        /*
         *  We use a query here instead of an entity->save() to include an extra where condition that prevents
         *  multiple update requests queueing up rewriting each other
         */
        builder::table($this->entity->get_table())
            ->where('id', '=', $this->entity->id)
            ->where('current_window_reset_time', '=', $this->entity->current_window_reset_time)
            ->update([
                'prev_window_value' => $this->current_window_value,
                'current_window_value' => 0,
                'current_window_reset_time' => $time,
                'current_limit' => $current_limit
            ]);

        $this->refresh();
    }

    /**
     * Refresh the underlying entity
     * @return void
     * @throws \coding_exception
     */
    public function refresh(): void {
        $this->entity->refresh();
    }
}