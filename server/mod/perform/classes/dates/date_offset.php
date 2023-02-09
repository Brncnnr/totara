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

namespace mod_perform\dates;

use coding_exception;
use JsonSerializable;

class date_offset implements JsonSerializable {

    public const UNIT_DAY = 'DAY';
    public const UNIT_WEEK = 'WEEK';

    public const DIRECTION_AFTER = 'AFTER';
    public const DIRECTION_BEFORE = 'BEFORE';

    /**
     * @var int
     */
    protected $count;

    /**
     * @var string
     */
    protected $unit;

    /**
     * @var string
     */
    protected $direction;

    /**
     * dynamic_offset constructor.
     *
     * @param int $count
     * @param string $unit
     * @param string $direction
     */
    public function __construct(
        int $count,
        string $unit,
        string $direction = self::DIRECTION_AFTER
    ) {
        self::validate_unit($unit);
        self::validate_direction($direction);

        $this->count = $count;
        $this->unit = $unit;
        $this->direction = $direction;
    }

    /**
     * Create a new offset from json (or assoc array).
     *
     * @param string|array $data A json encoded string or assoc array, with mandatory 'count', 'unit' and 'direction' fields.
     *                           If direction is not provided it will default to self::DIRECTION_AFTER.
     * @return static
     */
    public static function create_from_json($data) {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        $count = $data['count'] ?? null;
        $unit = $data['unit'] ?? null;
        $direction = $data['direction'] ?? self::DIRECTION_AFTER;

        // Note: that we don't require display name because we will try load that from the resolver.
        if ($count === null || $unit === null) {
            throw new coding_exception('count and unit are mandatory');
        }

        return new static($count, $unit, $direction);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array {
        //  Used for saving to a single database column.
        return [
            'count' => $this->get_count(),
            'unit' => $this->get_unit(),
            'direction' => $this->get_direction(),
        ];
    }

    /**
     * @return int
     */
    public function get_count(): int {
        return $this->count;
    }

    /**
     * @return string
     */
    public function get_unit(): string {
        return $this->unit;
    }

    /**
     * @return string
     */
    public function get_direction(): string {
        return $this->direction;
    }

    public static function get_directions(): array {
        return [
            self::DIRECTION_AFTER,
            self::DIRECTION_BEFORE,
        ];
    }

    public static function get_units(): array {
        return [
            self::UNIT_DAY,
            self::UNIT_WEEK,
        ];
    }

    /**
     * Ensure the supplied direction is valid for use in the mod_perform/dates namespace.
     *
     * @param string $direction
     */
    public static function validate_direction(string $direction): void {
        if (!in_array($direction, self::get_directions(), true)) {
            throw new coding_exception(sprintf('Invalid direction %s', $direction));
        }
    }

    /**
     * Ensure the supplied unit is valid for use in the mod_perform/dates namespace.
     *
     * @param string $unit
     */
    public static function validate_unit(string $unit): void {
        if (!in_array($unit, self::get_units())) {
            throw new coding_exception(sprintf('Invalid unit %s', $unit));
        }
    }

    /**
     * Get the date as an epoch.
     *
     * @param int $date The unix timestamp of the date to shift
     * @return int Unix timestamp
     */
    public function apply(int $date): int {
        $date_object = (new \DateTimeImmutable('@' . $date));

        $modifier = $this->get_direction() === self::DIRECTION_BEFORE ? '-' : '+';

        $adjusted = $date_object->modify("{$modifier} {$this->get_count()} {$this->get_unit()}");

        return $adjusted->getTimestamp();
    }

}