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

class totara_notification_mock_scheduled_built_in_notification extends built_in_notification {
    /**
     * @var int|null
     */
    private static $schedule_offset;

    /**
     * @return string
     */
    public static function get_resolver_class_name(): string {
        global $CFG;

        if (!class_exists('totara_notification_mock_scheduled_aware_event_resolver')) {
            require_once(
                "{$CFG->dirroot}/totara/notification/tests/fixtures/totara_notification_mock_scheduled_aware_event_resolver.php"
            );
        }

        return totara_notification_mock_scheduled_aware_event_resolver::class;
    }

    /**
     * @return string
     */
    public static function get_title(): string {
        return 'Mock scheduled built in notification';
    }

    /**
     * @return string
     */
    public static function get_recipient_class_name(): string {
        global $CFG;

        if (!class_exists('totara_notification_mock_recipient')) {
            require_once(
                "{$CFG->dirroot}/totara/notification/tests/fixtures/totara_notification_mock_recipient.php"
            );
        }

        return totara_notification_mock_recipient::class;
    }

    /**
     * @return lang_string
     */
    public static function get_default_body(): lang_string {
        return new lang_string('pluginname', 'totara_notification');
    }

    /**
     * @return lang_string
     */
    public static function get_default_subject(): lang_string {
        return new lang_string('pluginname', 'totara_notification');
    }

    /**
     * @return int
     */
    public static function get_default_schedule_offset(): int {
        if (!isset(static::$schedule_offset)) {
            return schedule_on_event::default_value();
        }

        return static::$schedule_offset;
    }

    /**
     * Note that the offset value must be in seconds unit.
     *
     * @param int $value
     * @return void
     */
    public static function set_default_schedule_offset(int $value): void {
        static::$schedule_offset = $value;
    }

    /**
     * @return void
     */
    public static function clear(): void {
        if (isset(static::$schedule_offset)) {
            static::$schedule_offset = null;
        }
    }
}