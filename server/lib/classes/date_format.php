<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core
 */

namespace core;

defined('MOODLE_INTERNAL') || die();

/**
 * This class defines all formats related to dates
 */
class date_format implements format_interface {

    // Ths following constants also map to the graphql core_date_format enums
    public const FORMAT_ISO8601 = 'ISO8601';
    public const FORMAT_TIMESTAMP = 'TIMESTAMP';
    public const FORMAT_DAYDATETIME = 'DAYDATETIME';
    public const FORMAT_TIME = 'TIME';
    public const FORMAT_TIMESHORT = 'TIMESHORT';
    public const FORMAT_DATE = 'DATE';
    public const FORMAT_DATESHORT = 'DATESHORT';
    public const FORMAT_DATELONG = 'DATELONG';
    public const FORMAT_DATETIME = 'DATETIME';
    public const FORMAT_DATETIMESHORT = 'DATETIMESHORT';
    public const FORMAT_DATETIMELONG = 'DATETIMELONG';
    public const FORMAT_DATETIMESECONDS = 'DATETIMESECONDS';

    public static function is_defined(string $format): bool {
        return defined('self::FORMAT_'.strtoupper($format));
    }

    public static function get_available(): array {
        return [
            self::FORMAT_ISO8601,
            self::FORMAT_TIMESTAMP,
            self::FORMAT_DAYDATETIME,
            self::FORMAT_TIME,
            self::FORMAT_TIMESHORT,
            self::FORMAT_DATE,
            self::FORMAT_DATESHORT,
            self::FORMAT_DATELONG,
            self::FORMAT_DATETIME,
            self::FORMAT_DATETIMESHORT,
            self::FORMAT_DATETIMELONG,
            self::FORMAT_DATETIMESECONDS
        ];
    }

    /**
     * Returns the language string for the given format
     *
     * @param string $format see constants for available options
     * @return string
     */
    public static function get_lang_string(string $format): string {
        switch ($format) {
            case self::FORMAT_TIME:
                $string = 'strftimetime';
                break;
            case self::FORMAT_TIMESHORT:
                $string = 'strftimeshort';
                break;
            case self::FORMAT_DATE:
                $string = 'strftimedate';
                break;
            case self::FORMAT_DATESHORT:
                $string = 'strftimedateshort';
                break;
            case self::FORMAT_DATELONG:
                $string = 'strftimedatefulllong';
                break;
            case self::FORMAT_DATETIME:
                $string = 'strftimedatetime';
                break;
            case self::FORMAT_DATETIMESHORT:
                $string = 'strftimedatetimeshort';
                break;
            case self::FORMAT_DATETIMELONG:
                $string = 'strftimedatetimelong';
                break;
            case self::FORMAT_DATETIMESECONDS:
                $string = 'strftimedateseconds';
                break;
            case self::FORMAT_DAYDATETIME:
                $string = 'strftimedaydatetime';
                break;
            default:
                throw new \coding_exception('No language string mapping defined for format '.$format);
                break;
        }

        return $string;
    }

}