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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */

use totara_notification\notification\built_in_notification;
use totara_notification\schedule\schedule_on_event;

class totara_notification_mock_built_in_notification extends built_in_notification {
    /**
     * @var lang_string|null
     */
    private static $body;

    /**
     * @var lang_string|null
     */
    private static $subject;

    /**
     * @var int|null
     */
    private static $body_format;

    /**
     * @var int|null
     */
    private static $subject_format;

    /**
     * @return string
     */
    public static function get_resolver_class_name(): string {
        global $CFG;

        if (!class_exists('totara_notification_mock_notifiable_event_resolver')) {
            require_once(
                "{$CFG->dirroot}/totara/notification/tests/fixtures/totara_notification_mock_notifiable_event_resolver.php"
            );
        }

        return totara_notification_mock_notifiable_event_resolver::class;
    }

    /**
     * @return string
     */
    public static function get_title(): string {
        return 'Mock built in notification';
    }

    /**
     * @return string
     */
    public static function get_recipient_class_name(): string {
        global $CFG;
        $event_class = totara_notification_mock_recipient::class;

        if (!class_exists($event_class)) {
            require_once("{$CFG->dirroot}/totara/notification/tests/fixtures/totara_notification_mock_recipient.php");
        }
        return $event_class;
    }

    /**
     * @return lang_string
     */
    public static function get_default_body(): lang_string {
        if (isset(self::$body)) {
            return self::$body;
        }

        // I could not be bothered to create a new lang_string.
        return new lang_string('pluginname', 'totara_notification');
    }

    /**
     * @return lang_string
     */
    public static function get_default_subject(): lang_string {
        if (isset(self::$subject)) {
            return self::$subject;
        }

        // I could not be bothered to create a new lang_string.
        return new lang_string('pluginname', 'totara_notification');
    }

    /**
     * @return int
     */
    public static function get_default_body_format(): int {
        if (!isset(self::$body_format)) {
            // Use format MOODLE as default value for body format.
            return FORMAT_MOODLE;
        }

        return self::$body_format;
    }

    /**
     * @return int
     */
    public static function get_default_subject_format(): int {
        if (!isset(self::$subject_format)) {
            // Use format MOODLE as default value for body format.
            return FORMAT_MOODLE;
        }

        return self::$subject_format;
    }

    /**
     * @param lang_string $mock_body
     * @return void
     */
    public static function set_default_body(lang_string $mock_body): void {
        self::$body = $mock_body;
    }

    /**
     * @param lang_string $mock_subject
     * @return void
     */
    public static function set_default_subject(lang_string $mock_subject): void {
        self::$subject = $mock_subject;
    }

    /**
     * @param int $value
     * @return void
     */
    public static function set_default_subject_format(int $value): void {
        self::$subject_format = $value;
    }

    /**
     * @param int $value
     * @return void
     */
    public static function set_default_body_format(int $value): void {
        self::$body_format = $value;
    }

    /**
     * @return void
     */
    public static function clear(): void {
        if (isset(self::$body)) {
            self::$body = null;
        }

        if (isset(self::$subject)) {
            self::$subject = null;
        }

        if (isset(self::$subject_format)) {
            self::$subject_format = null;
        }

        if (isset(self::$body_format)) {
            self::$body_format = null;
        }
    }

    /**
     * @return int
     */
    public static function get_default_schedule_offset(): int {
        return schedule_on_event::default_value();
    }
}