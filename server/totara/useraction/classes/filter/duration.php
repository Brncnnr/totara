<?php
/**
 * This file is part of Totara Talent Experience Platform
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
 * @author Cody Finegan <cody.finegan@totara.com>
 * @package totara_useraction
 */

namespace totara_useraction\filter;

use core\entity\user_repository;
use totara_useraction\model\scheduled_rule\execution_data;

/**
 * Filter against the date/durations.
 * Internally in this class we store the raw DB values, and on input/output with GraphQL we convert the values back.
 */
class duration implements filter_contract {
    // Units Involved
    const UNIT_DAYS = 0;
    const UNIT_MONTHS = 2;
    const UNIT_YEARS = 3;

    // Unit Enums
    const ENUM_UNIT_DAYS = 'DAY';
    const ENUM_UNIT_MONTHS = 'MONTH';
    const ENUM_UNIT_YEARS = 'YEAR';

    // Sources
    const SOURCE_SUSPENDED = 0;

    // Enum versions of the sources
    const ENUM_SUSPENDED = 'DATE_SUSPENDED';

    // Units in seconds, for calculations.
    const DAY_IN_SECONDS = 86400;
    const MONTH_IN_SECONDS = 2592000; // 30 days
    const YEAR_IN_SECONDS = 31536000; // 365 days

    /**
     * Helper method to map the enums with their values.
     *
     * @var array|string[]
     */
    private static array $source_map = [
        self::SOURCE_SUSPENDED => self::ENUM_SUSPENDED,
    ];

    /**
     * Helper method to map the enums with their values.
     *
     * @var array|string[]
     */
    private static array $unit_map = [
        self::UNIT_DAYS => self::ENUM_UNIT_DAYS,
        self::UNIT_MONTHS => self::ENUM_UNIT_MONTHS,
        self::UNIT_YEARS => self::ENUM_UNIT_YEARS,
    ];

    /**
     * @var int One of the SOURCE_* constants.
     */
    protected int $source;

    /**
     * @var int One of the UNIT_* constants.
     */
    protected int $unit;

    /**
     * @var int The value of the duration (in the unit above).
     */
    protected int $value;

    /**
     * We expect database-friendly values here, all GraphQL values need to be processed already.
     *
     * @param int $source
     * @param int $unit
     * @param int $value
     */
    protected function __construct(int $source, int $unit, int $value) {
        $this->source = $source;
        $this->unit = $unit;
        $this->value = $value;
    }

    /**
     * @param $input
     * @return filter_contract
     */
    public static function create_from_input($input): filter_contract {
        // We expect an array of values
        $source = $input['source'] ?? null;
        $unit = $input['unit'] ?? null;
        $value = $input['value'] ?? null;

        $source = self::enum_to_stored($source);
        $unit = self::enum_to_stored($unit);

        if ($source === null || $unit === null || $value === null || $value < 1) {
            throw new \coding_exception('Invalid duration filter input.');
        }

        $value = self::unit_to_seconds($value, $unit);

        return new self($source, $unit, $value);
    }

    /**
     * Create a instance based on database values.
     *
     * @param array $stored
     * @return filter_contract
     */
    public static function create_from_stored($stored): filter_contract {
        return new self($stored['source'], $stored['unit'], $stored['value']);
    }

    /**
     * Helper method to fetch the stored value from the enum.
     * This works as long as the enums do not cross over.
     *
     * @param string $key
     * @return int|null
     */
    public static function enum_to_stored(string $key): ?int {
        static $map = [];
        if (!$map) {
            $map = array_merge(array_flip(self::$source_map), array_flip(self::$unit_map));
        }

        return $map[$key] ?? null;
    }

    /**
     * Convert the raw number in seconds into the appropriate unit.
     *
     * @param int $raw_seconds
     * @param int $unit
     * @return int
     */
    public static function seconds_to_unit(int $raw_seconds, int $unit): int {
        switch ($unit) {
            case self::UNIT_DAYS:
                $value = $raw_seconds / self::DAY_IN_SECONDS;
                break;
            case self::UNIT_MONTHS:
                $value = $raw_seconds / self::MONTH_IN_SECONDS;
                break;
            case self::UNIT_YEARS:
                $value = $raw_seconds / self::YEAR_IN_SECONDS;
                break;
            default:
                throw new \coding_exception('Unknown unit');
        }

        return intval($value); // We must return a int
    }

    /**
     * Convert the raw input into the relevant seconds value.
     *
     * @param int $unit_value
     * @param int $unit
     * @return int
     */
    public static function unit_to_seconds(int $unit_value, int $unit): int {
        switch ($unit) {
            case self::UNIT_DAYS:
                $value = $unit_value * self::DAY_IN_SECONDS;
                break;
            case self::UNIT_MONTHS:
                $value = $unit_value * self::MONTH_IN_SECONDS;
                break;
            case self::UNIT_YEARS:
                $value = $unit_value * self::YEAR_IN_SECONDS;
                break;
            default:
                throw new \coding_exception('Unknown unit');
        }

        return intval($value); // We must return a int
    }

    /**
     * Source column.
     *
     * @return int
     */
    public function get_source(): int {
        return $this->source;
    }

    /**
     * @return int
     */
    public function get_unit(): int {
        return $this->unit;
    }

    /**
     * Number of seconds.
     *
     * @return int
     */
    public function get_value(): int {
        return $this->value;
    }

    /**
     * Return the status in a form for GraphQL.
     *
     * @return array
     */
    public function to_graphql(): array {
        return [
            'source' => self::$source_map[$this->source],
            'unit' => self::$unit_map[$this->unit],
            'value' => self::seconds_to_unit($this->value, $this->unit),
        ];
    }

    /**
     * @inheritDoc
     */
    public function apply(user_repository $user_repository, execution_data $execution_data): user_repository {
        switch ($this->get_source()) {
            case self::SOURCE_SUSPENDED:
                $time_now = $execution_data->get_timestamp();
                $compared_time = $time_now - $this->value;
                $alias = uniqid('tuac');
                $user_repository->join(['totara_userdata_user', $alias], 'id', 'userid')
                    ->where("\"$alias\".timesuspended", "<=",$compared_time);
                break;
            default:
                break;
        }

        return $user_repository;
    }
}
