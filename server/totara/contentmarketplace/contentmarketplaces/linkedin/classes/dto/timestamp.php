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
 * @author  Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

namespace contentmarketplace_linkedin\dto;

use coding_exception;

/**
 * Useful for handling timestamps that could be in JavaScript format - i.e in milliseconds.
 * @package contentmarketplace_linkedin\dto
 */
final class timestamp {
    /**
     * @var string
     */
    public const SECONDS = 'SECOND';

    /**
     * @var string
     */
    public const MILLISECONDS = 'MILLISECOND';

    /**
     * @var int
     */
    public const MILLISECONDS_IN_SECOND = 1000;

    /**
     * Available units, and their relation to a second.
     * @var array
     */
    private const UNITS = [
        self::SECONDS => 1,
        self::MILLISECONDS => 1 / self::MILLISECONDS_IN_SECOND,
    ];

    /**
     * @var int
     */
    private $timestamp;

    /**
     * @var string
     */
    private $unit;

    /**
     * time_to_complete constructor.
     * @param int $timestamp
     * @param string $unit
     */
    public function __construct(int $timestamp, string $unit) {
        if (!array_key_exists($unit, self::UNITS)) {
            throw new coding_exception("Invalid timestamp unit specified: $unit");
        }
        $this->timestamp = $timestamp;
        $this->unit = $unit;
    }

    /**
     * Get the timestamp, in seconds.
     *
     * @return int
     */
    public function get_timestamp(): int {
        return (int) ($this->timestamp * self::UNITS[$this->unit]);
    }

    /**
     * Get the raw, unconverted timestamp that was originally specified.
     *
     * @return int
     */
    public function get_raw(): int {
        return $this->timestamp;
    }

    /**
     * Get the specified unit.
     *
     * @return string
     */
    public function get_unit(): string {
        return $this->unit;
    }

}
