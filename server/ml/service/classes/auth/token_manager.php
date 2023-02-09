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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package ml_service
 */

namespace ml_service\auth;

use coding_exception;

/**
 * API helper for calling the remote Totara machine learning service.
 *
 * @package ml_service
 */
final class token_manager {
    /**
     * The min/max acceptable variance in timestamps for the token.
     * Used to offset minor clock drifting.
     */
    const BOUNDARY = 30;

    /**
     * @var null|int Base time used for calculating tokens
     */
    protected static $base_time = null;

    /**
     * Create a token based on the provided time.
     *
     * @param float $request_time
     * @return string
     */
    public static function make_token(float $request_time): string {
        global $CFG;
        if (empty($CFG->ml_service_key)) {
            throw new coding_exception('No ml_service_key was defined, cannot connect to the machine learning service.');
        }
        return hash('sha256', $request_time . $CFG->ml_service_key);
    }

    /**
     * Validates the provided request timestamp and token against our stored key
     *
     * @param float $request_time
     * @param string $request_token
     * @return bool
     */
    public static function valid_token(float $request_time, string $request_token): bool {
        // Only accept times within x (30) seconds.
        $lower_boundary = $upper_boundary = (self::$base_time ?? time());
        $lower_boundary -= self::BOUNDARY;
        $upper_boundary += self::BOUNDARY;

        // Request time must be between the boundaries provided
        if ($request_time < $lower_boundary || $request_time > $upper_boundary) {
            return false;
        }

        return $request_token === self::make_token($request_time);
    }

    /**
     * Will parse the request and return the provided token & time.
     * Note: This will not validate if either are set, that must be done afterwards.
     *
     * @return array
     */
    public static function extract_request_time_token(): array {
        // We expect to see both a time & a token passed in via the headers
        $request_time = $_SERVER['HTTP_X_TOTARA_TIME'] ?? 0;
        $request_token = $_SERVER['HTTP_X_TOTARA_ML_KEY'] ?? null;

        return [$request_time, $request_token];
    }
}