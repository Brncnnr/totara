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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\output;

defined('MOODLE_INTERNAL') || die();

use core\output\template;
use mod_facetoface\seminar_session;
use MoodleExcelFormat;
use MoodleExcelWorkbook;
use MoodleOdsFormat;

/**
 * Provide functions to format session times.
 */
class session_time {

    /**
     * Format the session time according to specific timezone.
     *
     * @param int $start Unix timestamp to start
     * @param int $end Unix timestamp to finish
     * @param int|string|float|DateTimeZone $timezone
     * @param boolean|null $displaytimezones true to display timezone, null to use facetoface_displaysessiontimezones config
     * @return \stdClass of startdate, starttime, enddate, endtime and timezone
     */
    public static function format(int $start, int $end, $timezone, bool $displaytimezones = null): \stdClass {
        if ($displaytimezones === null) {
            $displaytimezones = (bool)(int)get_config(null, 'facetoface_displaysessiontimezones');
        }

        if (empty($timezone) || (int)$timezone == 99 or !$displaytimezones) {
            $timezone = \core_date::get_user_timezone();
        } else {
            $timezone = \core_date::normalise_timezone($timezone);
        }

        $formattedsession = new \stdClass();
        $formattedsession->startdate = userdate($start, get_string('strftimedate', 'langconfig'), $timezone);
        $formattedsession->starttime = userdate($start, get_string('strftimetime', 'langconfig'), $timezone);
        $formattedsession->enddate = userdate($end, get_string('strftimedate', 'langconfig'), $timezone);
        $formattedsession->endtime = userdate($end, get_string('strftimetime', 'langconfig'), $timezone);
        if (!$displaytimezones) {
            $formattedsession->timezone = '';
        } else {
            $formattedsession->timezone = \core_date::get_localised_timezone($timezone);
        }
        return $formattedsession;
    }

    /**
     * Format the session time as localised string according to specific timezone.
     *
     * @param int $start Unix timestamp to start
     * @param int $end Unix timestamp to finish
     * @param string $timezone Timezone string e.g. "Pacific/Auckland"
     * @return string
     */
    public static function to_string(int $start, int $end, string $timezone): string {

        $sessionobj = static::format($start, $end, $timezone);

        // No timezone to display.
        if (empty($sessionobj->timezone)) {
            if ($sessionobj->startdate == $sessionobj->enddate) {
                $timestring = get_string('sessionstartdateandtimewithouttimezone', 'mod_facetoface', $sessionobj);
            } else {
                $timestring = get_string('sessionstartfinishdateandtimewithouttimezone', 'mod_facetoface', $sessionobj);
            }
        } else {
            if ($sessionobj->startdate == $sessionobj->enddate) {
                $timestring = get_string('sessionstartdateandtime', 'mod_facetoface', $sessionobj);
            } else {
                $timestring = get_string('sessionstartfinishdateandtime', 'mod_facetoface', $sessionobj);
            }
        }
        return $timestring;
    }

    /**
     * Format the sign-up period time as localised string according to specific timezone.
     *
     * @param int|string|null $startdate - string or Unix timestamp
     * @param int|string|null $finishdate - string or Unix timestamp
     * @param int|string|float|DateTimeZone $timezone
     * @param string $format - export format
     * @return string
     */
    public static function signup_period($startdate, $finishdate, $timezone = 99, string $format = 'html'): string {

        $returntext    = '';
        $startdatestr  = null;
        $finishdatestr = null;
        $displaytimezones = (bool)(int)get_config(null, 'facetoface_displaysessiontimezones');
        if (empty($timezone) || (int)$timezone == 99 || !$displaytimezones) {
            $targettz = \core_date::get_user_timezone();
        } else {
            $targettz = \core_date::normalise_timezone($timezone);
        }

        if ($startdate && is_numeric($startdate)) {
            $startdatestr = userdate($startdate, get_string('strftimedatetime', 'langconfig'), $targettz);
        }
        if ($finishdate && is_numeric($finishdate)) {
            $finishdatestr = userdate($finishdate, get_string('strftimedatetime', 'langconfig'), $targettz);
        }

        if ($startdatestr && $finishdatestr) {
            $returntext = get_string('signupstartend', 'mod_facetoface', ['startdate' => $startdatestr, 'enddate' => $finishdatestr]);
        } else if ($startdatestr) {
            $returntext = get_string('signupstartsonly', 'mod_facetoface', ['startdate' => $startdatestr]);
        } else if ($finishdatestr) {
            $returntext = get_string('signupendsonly', 'mod_facetoface', ['enddate' => $finishdatestr]);
        }

        if (!empty($returntext) && $displaytimezones) {
            $tzstring = \core_date::get_localised_timezone($targettz);
            $returntext .= 'html' == $format ? self::wrap_timezone($tzstring) : ' ' .$tzstring;
        }
        return $returntext;
    }

    /**
     * Format the session time as HTML according to specific timezone.
     *
     * @param int $timestart Unix timestamp to start
     * @param int $timefinish Unix timestamp to finish
     * @param int|string|float|DateTimeZone $timezone
     * @param boolean|null $displaytimezones true to display timezone, null to use facetoface_displaysessiontimezones config
     * @return string
     */
    public static function to_html(int $timestart, int $timefinish, $timezone = 99, bool $displaytimezones = null): string {
        $sessionobj = static::format($timestart, $timefinish, $timezone, $displaytimezones);

        if ($timestart && $timefinish) {
            if ($sessionobj->startdate == $sessionobj->enddate) {
                // <startdate>, <starttime> - <finishtime> [Timezone: <timezone>]
                $html = get_string('sessionstartdateandtimewithouttimezone', 'mod_facetoface', [
                    'startdate' => self::wrap_eventtime($sessionobj->startdate),
                    'starttime' => self::wrap_eventtime($sessionobj->starttime),
                    'endtime' => self::wrap_eventtime($sessionobj->endtime),
                ]);
            } else {
                // <startdate>, <starttime> - <finishdate>, <finishtime> [Timezone: <timezone>]
                $html = get_string('sessionstartfinishdateandtimewithouttimezone', 'mod_facetoface', [
                    'startdate' => self::wrap_eventtime($sessionobj->startdate),
                    'starttime' => self::wrap_eventtime($sessionobj->starttime),
                    'enddate' => self::wrap_eventtime($sessionobj->enddate),
                    'endtime' => self::wrap_eventtime($sessionobj->endtime),
                ]);
            }
        } else if ($timestart) {
            // After <startdate>, <starttime> [Timezone: <timezone>]
            $html = get_string('signupstartdateandtimeonlywithouttimezone', 'mod_facetoface', [
                'startdate' => self::wrap_eventtime($sessionobj->startdate),
                'starttime' => self::wrap_eventtime($sessionobj->starttime),
            ]);
        } else if ($timefinish) {
            // Before <enddate>, <endtime> [Timezone: <timezone>]
            $html = get_string('signupenddateandtimeonlywithouttimezone', 'mod_facetoface', [
                'enddate' => self::wrap_eventtime($sessionobj->enddate),
                'endtime' => self::wrap_eventtime($sessionobj->endtime),
            ]);
        } else {
            return '';
        }

        if ($sessionobj->timezone) {
            $html .= self::wrap_timezone($sessionobj->timezone);
        }
        return $html;
    }

    /**
     * Wrap a date/time string with a <time> tag.
     *
     * @param string $time  The date/time string
     * @return string
     */
    private static function wrap_eventtime(string $time): string {
        return \html_writer::tag('time', clean_string($time), ['class' => 'mod_facetoface__sessionlist__eventtime']);
    }

    /**
     * @param string $timezone
     * @return string
     */
    private static function wrap_timezone(string $timezone): string {
        $timezonestring = clean_string(get_string('timezoneformat', 'mod_facetoface', $timezone));
        return \html_writer::tag('span', $timezonestring, ['class' => 'mod_facetoface__sessionlist__timezone']);
    }

    /**
     * Displays/Exports the session date and time with timezone or without timezone if timezone display is disabled.
     *
     * @uses \mod_facetoface\export_helper::download_xls
     * @uses \mod_facetoface\export_helper::download_ods
     * @uses \mod_facetoface\rb\display\local_event_date
     * @uses \mod_facetoface\rb\display\event_date
     * @uses \mod_facetoface\rb\display\event_date_link
     *
     * @param int|string|null $value - timestamp
     * @param string $format - export format
     * @param int|string $timezone 99 - force 99 for reportbuilder local_session_date() display
     * @param bool $sessiontimezone true|false - force false for reportbuilder local_session_date() display
     * @return array|string
     */
    public static function format_datetime($value, string $format, $timezone = 99, bool $sessiontimezone = true) {

        if (empty($value)) {
            return '';
        }

        if (!is_numeric($value) || $value == 0 || $value == -1) {
            return '';
        }

        // Modify this to reflect special format requirements if necessary
        switch ($format) {
            case 'excel':
            case 'xls':
                $date = static::export_excel($value, MoodleExcelWorkbook::NUMBER_FORMAT_STANDARD_DATETIME);
                break;
            case 'ods':
                $date = static::export_ods($value, 22);
                break;
            default:
                // Html/csv/pdflandscape/pdfportrait/etc
                $displaytimezones = (bool)(int)get_config(null, 'facetoface_displaysessiontimezones') && $sessiontimezone;

                if (empty($timezone) || (int)$timezone == 99 || !$displaytimezones) {
                    $targettz = \core_date::get_user_timezone();
                } else {
                    $targettz = \core_date::normalise_timezone($timezone);
                }

                $date = userdate($value, get_string('strftimedatetime', 'langconfig'), $targettz);
                if ($displaytimezones) {
                    $tzstring = \core_date::get_localised_timezone($targettz);
                    $date .= 'html' == $format ? self::wrap_timezone($tzstring) : ' ' . $tzstring;
                }
                break;
        }
        return $date;
    }

    /**
     * Displays/Exports the session time with timezone or without timezone if timezone display is disabled.
     *
     * @uses \mod_facetoface\export_helper::download_xls
     * @uses \mod_facetoface\export_helper::download_ods
     * @uses \mod_facetoface\rb\display\event_time
     *
     * @param int|string|null $value - timestamp
     * @param string $format - export format
     * @param int|string $timezone 99 - force 99 for reportbuilder local_session_date() display
     * @param bool $sessiontimezone true|false - force false for reportbuilder local_session_date() display
     * @return array|string
     */
    public static function format_time($value, string $format, $timezone = 99, bool $sessiontimezone = true) {

        if (empty($value)) {
            return '';
        }

        if (!is_numeric($value) || $value == 0 || $value == -1) {
            return '';
        }

        // Modify this to reflect special format requirements if necessary
        switch ($format) {
            case 'excel':
            case 'xls':
                $date = static::export_excel($value, MoodleExcelWorkbook::NUMBER_FORMAT_STANDARD_TIME);
                break;
            case 'ods':
                $date = static::export_ods($value, 24);
                break;
            default:
                // Html/csv/pdflandscape/pdfportrait/etc
                $displaytimezones = (bool)(int)get_config(null, 'facetoface_displaysessiontimezones') && $sessiontimezone;

                if (empty($timezone) || (int)$timezone == 99 || !$displaytimezones) {
                    $targettz = \core_date::get_user_timezone();
                } else {
                    $targettz = \core_date::normalise_timezone($timezone);
                }

                $date = userdate($value, get_string('strftimetime', 'langconfig'), $targettz);
                if ($displaytimezones) {
                    $tzstring = \core_date::get_localised_timezone($targettz);
                    $date .= 'html' == $format ? self::wrap_timezone($tzstring) : ' ' . $tzstring;
                }
                break;
        }
        return $date;
    }

    /**
     * Displays/Exports the session date with timezone or without timezone if timezone display is disabled.
     *
     * @uses \mod_facetoface\export_helper::download_xls
     * @uses \mod_facetoface\export_helper::download_ods
     * @uses \mod_facetoface\rb\display\session_date
     *
     * @param int|string|null $value - timestamp
     * @param string $format - export format
     * @param int|string $timezone 99 - force 99 for reportbuilder local_session_date() display
     * @param bool $sessiontimezone true|false - force false for reportbuilder local_session_date() display
     * @return array|string
     */
    public static function format_date($value, string $format, $timezone = 99, bool $sessiontimezone = true) {

        if (empty($value)) {
            return '';
        }

        if (!is_numeric($value) || $value == 0 || $value == -1) {
            return '';
        }

        // Modify this to reflect special format requirements if necessary
        switch ($format) {
            case 'excel':
            case 'xls':
                $date = static::export_excel($value, MoodleExcelWorkbook::NUMBER_FORMAT_STANDARD_DATE);
                break;
            case 'ods':
                $date = static::export_ods($value, 26);
                break;
            default:
                // Html/csv/pdflandscape/pdfportrait/etc
                $displaytimezones = (bool)(int)get_config(null, 'facetoface_displaysessiontimezones') && $sessiontimezone;

                if (empty($timezone) || (int)$timezone == 99 || !$displaytimezones) {
                    $targettz = \core_date::get_user_timezone();
                } else {
                    $targettz = \core_date::normalise_timezone($timezone);
                }

                $date = userdate($value, get_string('strftimedate', 'langconfig'), $targettz);
                if ($displaytimezones) {
                    $tzstring = \core_date::get_localised_timezone($targettz);
                    $date .= 'html' == $format ? self::wrap_timezone($tzstring) : ' ' . $tzstring;
                }
                break;
        }
        return $date;
    }

    /**
     * Return MS Excel export value
     * @param int $value - timestamp
     * @param int $excelformat 14|15|16|17|22|24
     * - $numbers[14] = 'm/d/yyyy';
     * - $numbers[15] = 'd-mmm-yy';
     * - $numbers[16] = 'd-mmm';
     * - $numbers[17] = 'mmm-yy';
     * - $numbers[22] = 'm/d/yyyy h:mm';
     * - $numbers[24] = 'h:mm';
     * @return array
     */
    private static function export_excel(int $value, int $excelformat = MoodleExcelWorkbook::NUMBER_FORMAT_STANDARD_DATETIME): array {

        $dateformat = new MoodleExcelFormat();
        $dateformat->set_num_format($excelformat);
        return ['date', $value, $dateformat];
    }

    /**
     * Return ODS export value
     * @param int $value
     * @param int $odsformat 14|15|16|17|22|24|26
     * - $numbers[14] = 'mm-dd-yy';
     * - $numbers[15] = 'd-mmm-yy';
     * - $numbers[16] = 'd-mmm';
     * - $numbers[17] = 'mmm-yy';
     * - $numbers[22] = 'm/d/yy h:mm';
     * - $numbers[24] = 'h:mm';
     * - $numbers[26] = 'm/d/yy';
     * @return array
     */
    private static function export_ods(int $value, int $odsformat = 22): array {

        $dateformat = new MoodleOdsFormat();
        $dateformat->set_num_format($odsformat);
        if ($odsformat == 24) {
            return ['time', $value, $dateformat];
        }
        return ['date', $value, $dateformat];
    }

    /**
     * Format the duration time in a friendly way rather than just subtraction.
     * If the duration is longer than $cutoff i.e. working time, then the duration is round up to the nearest days.
     *
     * @param integer       $timestart      Start time in Unix timestamp
     * @param integer       $timefinish     Finish time in Unix timestamp
     * @param integer       $cutoff         Cut off time in seconds, 8 hours by default
     * @param stdClass|null $str            Should be a time object for format_time()
     * @return string       Duration string.
     */
    public static function format_duration(int $timestart, int $timefinish, int $cutoff = HOURSECS * 8, \stdClass $str = null): string {
        $duration = (int)abs($timefinish - $timestart);    // Call abs() like format_time
        // Subtract full days from duration, we'll add them back later.
        $days = floor($duration / DAYSECS);
        $duration = $duration - ($days * DAYSECS);
        // If duration is greater that cutoff, round up to a full day.
        if ($duration >= $cutoff) {
            $duration = DAYSECS;
        }
        // Add full days back in to duration.
        $duration = $duration + ($days * DAYSECS);
        return format_time($duration, $str);
    }
}
