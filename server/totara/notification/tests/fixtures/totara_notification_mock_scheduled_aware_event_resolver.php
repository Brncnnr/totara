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

use totara_core\extended_context;
use totara_notification\placeholder\placeholder_option;
use totara_notification\resolver\abstraction\permission_resolver;
use totara_notification\resolver\abstraction\scheduled_event_resolver;
use totara_notification\resolver\notifiable_event_resolver;
use totara_notification\schedule\schedule_after_event;
use totara_notification\schedule\schedule_before_event;

global $CFG;

require_once("{$CFG->libdir}/dml/array_recordset.php");
require_once("{$CFG->libdir}/dml/moodle_recordset.php");

class totara_notification_mock_scheduled_aware_event_resolver extends notifiable_event_resolver
    implements permission_resolver, scheduled_event_resolver {
    /**
     * The key name that we want to set for the event_data for the value of setted event time.
     * @var string
     */
    public const EVENT_TIME_KEY = 'event_time';

    /**
     * @var placeholder_option[]|null
     */
    private static $placeholder_options;

    /**
     * @var array|null
     */
    private static $events;

    /**
     * @var array|null
     */
    private static $schedule_classes;

    /**
     * @var bool|null
     */
    private static $has_associated_event;

    /**
     * A hashmap of extended context against user's id and the given permissions.
     * @var array|null
     */
    private static $permissions;

    /**
     * @var extended_context[]|null
     */
    private static $support_contexts;

    /**
     * @return int
     */
    public function get_fixed_event_time(): int {
        if (!isset($this->event_data[static::EVENT_TIME_KEY])) {
            throw new coding_exception("Cannot find the event time within the event data");
        }

        // We are letting the native php to validate the data type at this key.
        return $this->event_data[static::EVENT_TIME_KEY];
    }

    /**
     * @return string
     */
    public static function get_notification_title(): string {
        return 'Mock scheduled aware notifiable event';
    }

    /**
     * @return array
     */
    public static function get_notification_available_recipients(): array {
        global $CFG;
        require_once("{$CFG->dirroot}/totara/notification/tests/fixtures/totara_notification_mock_recipient.php");

        return [
            totara_notification_mock_recipient::class,
        ];
    }

    /**
     * @return array
     */
    public static function get_notification_available_schedules(): array {
        if (isset(static::$schedule_classes)) {
            return static::$schedule_classes;
        }

        // Default to only before and after event.
        return [
            schedule_before_event::class,
            schedule_after_event::class,
        ];
    }

    /**
     * @return array
     */
    public static function get_notification_default_delivery_channels(): array {
        return ['email', 'popup'];
    }

    /**
     * @return placeholder_option[]
     */
    public static function get_notification_available_placeholder_options(): array {
        if (!isset(self::$placeholder_options)) {
            self::$placeholder_options = [];
        }

        return self::$placeholder_options;
    }

    /**
     * @return void
     */
    public static function clear(): void {
        if (isset(static::$placeholder_options)) {
            static::$placeholder_options = null;
        }

        if (isset(static::$events)) {
            static::$events = null;
        }

        if (isset(static::$schedule_classes)) {
            static::$schedule_classes = null;
        }

        if (isset(static::$has_associated_event)) {
            static::$has_associated_event = null;
        }

        if (isset(self::$permissions)) {
            self::$permissions = null;
        }

        if (isset(self::$support_contexts)) {
            self::$support_contexts = null;
        }
    }

    /**
     * @param placeholder_option[] $options
     * @return void
     */
    public static function add_placeholder_options(placeholder_option ...$options): void {
        self::$placeholder_options = $options;
    }

    /**
     * @param int $min_time
     * @param int $max_time
     *
     * @return moodle_recordset
     */
    public static function get_scheduled_events(int $min_time, int $max_time): moodle_recordset {
        return new array_recordset(static::$events);
    }

    /**
     * @param array $events
     * @return void
     */
    public static function set_events(array $events): void {
        static::$events = $events;
    }

    /**
     * @param string ...$scheduled_classes
     * @return void
     */
    public static function set_scheduled_classes(string ...$scheduled_classes): void {
        static::$schedule_classes = $scheduled_classes;
    }

    /**
     * @return bool
     */
    public static function uses_on_event_queue(): bool {
        if (isset(static::$has_associated_event)) {
            return static::$has_associated_event;
        }

        return parent::uses_on_event_queue(); // TODO: Change the autogenerated stub
    }

    /**
     * @param bool $value
     * @return void
     */
    public static function set_associated_notifiable_event(bool $value): void {
        static::$has_associated_event = $value;
    }

    public function get_extended_context(): extended_context {
        return extended_context::make_with_context(context_system::instance());
    }

    /**
     * @param extended_context $extended_context
     * @param int              $user_id
     * @param bool             $grant
     *
     * @return void
     */
    public static function set_permissions(extended_context $extended_context, int $user_id, bool $grant): void {
        if (!isset(self::$permissions)) {
            self::$permissions = [];
        }

        $identifier = md5("{$extended_context->__toString()}/{$user_id}");
        self::$permissions[$identifier] = $grant;
    }

    /**
     * @param extended_context $context
     * @param int              $user_id
     * @return bool
     */
    public static function can_user_manage_notification_preferences(extended_context $context, int $user_id): bool {
        if (!isset(self::$permissions)) {
            // Permissions was not set. However, site admin is able to see it thru.
            return is_siteadmin();
        }

        $identifier = md5("{$context->__toString()}/{$user_id}");
        return self::$permissions[$identifier] ?? is_siteadmin();
    }

    /**
     * @param extended_context $context
     * @param int              $user_id
     * @return bool
     */
    public static function can_user_audit_notifications(extended_context $context, int $user_id): bool {
        if (!isset(self::$permissions)) {
            // Permissions was not set. However, site admin is able to see it thru.
            return is_siteadmin();
        }

        $identifier = md5("{$context->__toString()}/{$user_id}");
        return self::$permissions[$identifier] ?? is_siteadmin();
    }

    /**
     * @param extended_context ...$support_contexts
     * @return void
     */
    public static function set_support_contexts(extended_context ...$support_contexts): void {
        static::$support_contexts = $support_contexts;
    }

    /**
     * @param extended_context $extend_context
     * @return bool
     */
    public static function supports_context(extended_context $extend_context): bool {
        if (!static::$support_contexts) {
            return parent::supports_context($extend_context);
        }

        foreach (static::$support_contexts as $support_context) {
            if ($extend_context->is_same($support_context)) {
                return true;
            }
        }

        return false;
    }

}