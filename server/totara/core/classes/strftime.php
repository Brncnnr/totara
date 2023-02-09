<?php
/*
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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_core
 */

namespace totara_core;

use coding_exception;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use IntlDateFormatter;

/**
 * This class is a drop-in replacement for the built-in functions strftime and gmstrftime
 * using the methods strftime::format and strftime::format_gm respectively.
 *
 * @since Totara 17
 */
class strftime {
    /**
     * @var array|null
     */
    private static ?array $map = null;

    /**
     * Collection of formats used with IntlDateFormatter
     * These are all locale aware and can potentially give different results depending
     * on what locale is available and what underlying libraries are used.
     * See also {@link https://unicode-org.github.io/icu/userguide/format_parse/datetime/#datetime-format-syntax}
     *
     * @var array
     */
    private static array $intl_map = [
        '%b' => 'LLL', // Jan - Dec
        '%B' => 'LLLL', // Full month name
        '%h' => 'LLL', // Alias of %b
        '%X' => 'jms', // Human-readable time
        '%x' => 'dMy', // Human-readable date
        '%c' => 'EEE d MMM yyyy jms', // Human-readable date & time
        '%a' => 'ccc', // Day of week
        '%A' => 'cccc', // Full day of week
    ];

    /**
     * List of placeholders that are human-readable, and therefore fed through the pattern generator.
     * Where the pattern generator is unavailable we'll use strftime instead.
     *
     * See also {@link https://www.php.net/manual/en/class.intldatepatterngenerator.php}.
     *
     * @var array
     */
    private static array $human_readable = [
        '%c', '%X', '%x'
    ];

    /**
     * Convert the $timestamp into a readable date/time based on the provided $format.
     * Replaces strftime.
     *
     * Everything is run via Date & IntlDateFormatter, except in certain cases for PHP < 8.1
     * which will fall back to strftime.
     *
     * @param string $format A strftime compatible timestamp format string
     * @param int|null $timestamp The time to convert. Defaults to now.
     * @return string The formatted timestamp
     */
    public static function format(string $format, ?int $timestamp = null): string {
        // The internal logic of this method cannot know anything specific to Totara, it must rely
        // on globally set PHP settings to replicate strftime's behaviour.
        // For that reason we use the default timezone set at the server level and not a user-specific one.
        $timezone = new DateTimeZone(date_default_timezone_get());
        $date_time = self::make_datetime($timestamp, $timezone);

        return self::convert($format, $date_time);
    }

    /**
     * Convert the $timestamp into a readable date/time based on the provided format,
     * while in GMT timezone.
     *
     * Replaces gmstrftime.
     *
     * @param string $format A strftime compatible timestamp format string
     * @param int|null $timestamp The time to convert. Defaults to now.
     * @return string The formatted timestamp in GMT
     */
    public static function format_gm(string $format, ?int $timestamp = null): string {
        $timezone = new DateTimeZone('GMT');
        $timestamp = self::make_datetime($timestamp, $timezone);
        return self::convert($format, $timestamp, true);
    }

    /**
     * Processes an individual placeholder using the IntlDateFormatter library.
     * Will return the converted value based on the set locale, or will throw an exception if the placeholder
     * is unexpected.
     *
     * @param DateTimeInterface $date_time
     * @param string $format
     * @param bool $gmt
     * @return string
     */
    protected static function convert_via_intl(
        DateTimeInterface $date_time,
        string $format,
        bool $gmt = false
    ): string {
        $pattern = self::$intl_map[$format] ?? null;
        if (is_null($pattern)) {
            throw new coding_exception(sprintf('The IntlDateFormatter placeholder “%s“ is undefined', $format));
        }

        $locale = self::get_locale();
        if (in_array($format, self::$human_readable)) {
            // If the pattern is marked as human-readable, then it is fed via
            // the pattern generator (if we have it) or it falls back to
            // the original strftime value.
            if (!class_exists('\IntlDatePatternGenerator')) {
                $call = $gmt ? 'gmstrftime' : 'strftime';
                return $call($format, $date_time->format('U'));
            }

            $pattern = (new \IntlDatePatternGenerator($locale))->getBestPattern($pattern);
        }

        return IntlDateFormatter::formatObject($date_time, $pattern, $locale);
    }

    /**
     * Get the locale that's active
     *
     * @return string
     */
    protected static function get_locale(): string {
        $locale = setlocale(LC_TIME, 0);
        return preg_replace('/[^\w-].*$/', '', $locale);
    }

    /**
     * The entire placeholder map, in the form of an array of placeholder→replacement.
     *
     * Where the replacement is a string, it is a PHP DateTimeInterface::format string.
     * In certain cases the replacement is a callable, in which case it expects to be invoked
     * as $callable($timestamp, $placeholder, $gmt).
     *
     * It should return the processed result (without placeholders).
     *
     * @return array
     */
    protected static function get_map(): array {
        if (!empty(self::$map)) {
            return self::$map;
        }

        // Locale-aware formats are heavier, so they're in a callback that's only processed if necessary
        $intl_format = [static::class, 'convert_via_intl'];

        self::$map = [
            // Day
            '%a' => $intl_format, // Sun-Sat
            '%A' => $intl_format, // Sunday-Saturday
            '%d' => 'd', // Day of Month, 01-31
            '%e' => function ($timestamp): string { // Day of Month, padded with a single space
                return sprintf('%2u', $timestamp->format('j'));
            },
            '%j' => function ($timestamp): string { // Day of year, padded with 0's
                return sprintf('%03u', $timestamp->format('z') + 1);
            },
            '%u' => 'N', // Day of week (1=Mon - 7=Sun)
            '%w' => 'w', // Day of week (0=Sun - 6=Sat)

            // Week
            '%V' => 'W', // ISO-8601 week of year
            '%U' => function ($timestamp): string { // Week of year, starting from first Sunday
                $first_sunday = (new DateTime($timestamp->format('Y') . '-01 Sunday'))->format('z');
                $current_day = $timestamp->format('z');
                // Week is the difference between the days of year / 7
                return sprintf('%02u', (($current_day - $first_sunday) / 7) + 1);
            },
            '%W' => function ($timestamp): string { // Week of year, starting from first Monday
                $first_monday = (new DateTime($timestamp->format('Y') . '-01 Monday'))->format('z');
                $current_day = $timestamp->format('z');
                // Week is the difference between the days of year / 7
                return sprintf('%02u', (($current_day - $first_monday) / 7) + 1);
            },

            // Month
            '%b' => $intl_format, // Jan-Dec
            '%B' => $intl_format, // January-December
            '%h' => $intl_format, // Jan-Dec (alias of %b)
            '%m' => 'm', // 01-12

            // Year
            '%C' => function ($timestamp): string { // Century, year / 100 floored
                return (string) floor($timestamp->format('Y') / 100);
            },
            '%g' => function ($timestamp): string { // Year, ISO-8601, 2 digit
                return substr($timestamp->format('o'), -2);
            },
            '%G' => 'o', // Year, ISO-8601, 4 digit
            '%y' => 'y', // Year, 2 digit
            '%Y' => 'Y', // Year, 4 digit

            // Time
            '%H' => 'H', // 00-23, hours
            '%k' => function ($timestamp): string { // 0-23 hours, padded with a single space
                return sprintf('%2u', $timestamp->format('G'));
            },
            '%I' => 'h', // 01-12, hours
            '%l' => function ($timestamp): string { // 1-12 hours, padded with a single space
                return sprintf('%2u', $timestamp->format('g'));
            },
            '%M' => 'i', // Minutes
            '%p' => 'A', // AM-PM uppercase
            '%P' => 'a', // am-pm lowercase (note case is backwards)
            '%r' => 'h:i:s A', // Alias for %I:%M:%S %p
            '%R' => 'H:i', // Alias for %H:%M
            '%S' => 's', // 00-59, Seconds
            '%T' => 'H:i:s', // Alias for %H:%M:%S
            '%X' => $intl_format, // Preferred time by locale
            '%z' => 'O', // +0200, Timezone
            '%Z' => 'T', // NZDT, Timezone

            // Time and date stamps
            '%c' => $intl_format, // Preferred timestamp by locale
            '%D' => 'm/d/y', // Alias for %m/%d/%y
            '%F' => 'Y-m-d', // Alias for %Y-%m-%d
            '%s' => 'U', // Same as time(). Note: stftime always gave an incorrect value.
            '%x' => $intl_format, // Preferred date by locale

            // String Placeholders
            '%%' => '%',
            '%t' => "\t",
            '%n' => "\n",
        ];

        return self::$map;
    }

    /**
     * Process the placeholders for the format & timestamp
     *
     * @param string $format
     * @param DateTimeInterface $date_time
     * @param bool $gmt
     * @return string
     */
    private static function convert(string $format, DateTimeInterface $date_time, bool $gmt = false): string {
        // This is a bit ugly, but it is needed so `%%%p %%p` ends up as `%AM %p`
        $format = str_replace('%%', '$$%$$', $format);

        $formatted = preg_replace_callback('/(?<!%)%[OE]?([_#-]?)([a-zA-Z])/', function ($match) use ($date_time, $gmt) {
            $key = '%' . $match[2];
            $modifier = $match[1];

            $translation = self::get_map()[$key] ?? null;
            if ($translation === null) {
                // We aren't aware of this placeholder, therefore it's printed as-is.
                return $key;
            }

            // If the translation is a callable, then call it
            if (!is_string($translation) && is_callable($translation)) {
                $result = $translation($date_time, $key, $gmt);
            } else {
                $result = $date_time->format($translation);
            }

            // We can have modifiers that tweak the results slightly
            if ($modifier === '-') {
                // Remove leading 0's
                return preg_replace('/^0+(?=.)/', '', $result);
            }
            if ($modifier === '_') {
                // Swap leading 0's with spaces
                return preg_replace('/\G0(?=.)/', ' ', $result);
            }

            return $result;
        }, $format);

        return str_replace('$$%$$', '%', $formatted);
    }

    /**
     * Convert the $timestamp to a date/time, and attach the provided timezone.
     *
     * @param int|null $timestamp
     * @param DateTimeZone $timezone
     * @return DateTime
     */
    private static function make_datetime(?int $timestamp, DateTimeZone $timezone): DateTime {
        $timestamp ??= 'now';
        if (is_int($timestamp)) {
            $timestamp = '@' . $timestamp;
        }
        try {
            $timestamp = new DateTime($timestamp);
            $timestamp->setTimezone($timezone);
        } catch (Exception $exception) {
            echo $exception->getMessage();
            throw new coding_exception('Unable to parse the date time provided to strftime');
        }

        return $timestamp;
    }
}
