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

final class timespan {

    /**
     * @var string
     */
    public const UNIT_HOUR = 'HOUR';

    /**
     * @var string
     */
    public const UNIT_MINUTE = 'MINUTE';

    /**
     * @var string
     */
    public const UNIT_SECOND = 'SECOND';

    /**
     * The units that can be specified, with how many seconds are in each unit.
     */
    private const UNIT_VALUES = [
        self::UNIT_HOUR      => HOURSECS,
        self::UNIT_MINUTE    => MINSECS,
        self::UNIT_SECOND    => 1,
    ];

    /**
     * @var int
     */
    private $duration;

    /**
     * @var string
     */
    private $unit;

    /**
     * timespan constructor.
     * @param int $duration
     * @param string $unit
     */
    public function __construct(int $duration, string $unit) {
        if (!self::is_valid_unit($unit)) {
            throw new coding_exception("Invalid unit specified for timespan: $unit");
        }
        $this->duration = $duration;
        $this->unit = $unit;
    }

    /**
     * Define a timespan in hours.
     * @param int $hours
     * @return static
     */
    public static function hours(int $hours): self {
        return new self($hours, self::UNIT_HOUR);
    }

    /**
     * Define a timespan in minutes.
     * @param int $minutes
     * @return static
     */
    public static function minutes(int $minutes): self {
        return new self($minutes, self::UNIT_MINUTE);
    }

    /**
     * Define a timespan in seconds.
     * @param int $seconds
     * @return static
     */
    public static function seconds(int $seconds): self {
        return new self($seconds, self::UNIT_SECOND);
    }

    /**
     * Get the amount of time in seconds.
     *
     * @return int
     */
    public function get(): int {
        return $this->duration * self::UNIT_VALUES[$this->unit];
    }

    /**
     * Get the raw duration of this time.
     *
     * @return int
     */
    public function get_raw_duration(): int {
        return $this->duration;
    }

    /**
     * Get the unit that this time is in.
     *
     * @return string
     */
    public function get_raw_unit(): string {
        return $this->unit;
    }

    /**
     * @param string $unit
     * @return bool
     */
    public static function is_valid_unit(string $unit): bool {
        return array_key_exists($unit, self::UNIT_VALUES);
    }
}
