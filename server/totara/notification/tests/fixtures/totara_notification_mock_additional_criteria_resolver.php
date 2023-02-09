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
use totara_notification\delivery\channel\delivery_channel;
use totara_notification\placeholder\placeholder_option;
use totara_notification\resolver\abstraction\audit_resolver;
use totara_notification\resolver\notifiable_event_resolver;
use totara_notification\resolver\abstraction\additional_criteria_resolver;
use totara_notification\resolver\abstraction\permission_resolver;

class totara_notification_mock_additional_criteria_resolver
    extends notifiable_event_resolver
    implements permission_resolver, audit_resolver, additional_criteria_resolver {

    /**
     * @var Closure|null
     */
    private static $recipient_ids_resolver;

    /**
     * @var array|null
     */
    private static $available_recipients;

    /**
     * @var array|null
     */
    private static $placeholder_options;

    /**
     * @var array|null
     */
    private static $default_delivery_channels = ['email', 'popup'];

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
     * @param callable $recipient_ids_resolver
     * @return void
     */
    public static function set_recipient_ids_resolver(callable $recipient_ids_resolver): void {
        if (!isset(self::$recipient_ids_resolver)) {
            self::$recipient_ids_resolver = null;
        }

        self::$recipient_ids_resolver = Closure::fromCallable($recipient_ids_resolver);
    }

    /**
     * @return void
     */
    public static function clear(): void {
        if (isset(self::$recipient_ids_resolver)) {
            self::$recipient_ids_resolver = null;
        }

        if (isset(self::$available_recipients)) {
            self::$available_recipients = null;
        }

        if (isset(self::$placeholder_options)) {
            self::$placeholder_options = null;
        }

        if (isset(self::$default_delivery_channels)) {
            self::$default_delivery_channels = ['email', 'popup'];
        }

        if (isset(self::$permissions)) {
            self::$permissions = null;
        }

        if (isset(self::$support_contexts)) {
            self::$support_contexts = null;
        }
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
     * @param string $recipient_name
     * @return array
     */
    public function get_recipient_ids(string $recipient_name): array {
        if (!isset(self::$recipient_ids_resolver)) {
            return [];
        }

        // Let the native php handle the miss-matched type returned from callback - i'm tired.
        return self::$recipient_ids_resolver->__invoke($this->event_data);
    }

    /**
     * @return string
     */
    public static function get_notification_title(): string {
        return 'Mock notifiable event';
    }

    /**
     * @return array
     */
    public static function get_notification_available_recipients(): array {
        // Return set available recipients.
        if (!is_null(static::$available_recipients)) {
            return static::$available_recipients;
        }

        // Return default available recipients.
        return [
            totara_notification_mock_recipient::class,
        ];
    }

    /**
     * @param string[] $available_recipients
     * @return void
     */
    public static function set_notification_available_recipients(array $available_recipients): void {
        static::$available_recipients = $available_recipients;
    }

    /**
     * @return delivery_channel[]
     */
    public static function get_notification_default_delivery_channels(): array {
        return static::$default_delivery_channels ?? [];
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
     * @param placeholder_option[] $options
     * @return void
     */
    public static function add_placeholder_options(placeholder_option ...$options): void {
        self::$placeholder_options = $options;
    }

    /**
     * @return extended_context
     */
    public function get_extended_context(): extended_context {
        return extended_context::make_with_id($this->event_data['expected_context_id']);
    }

    /**
     * @param string[] $delivery_channels
     */
    public static function set_notification_default_delivery_channels(array $delivery_channels): void {
        self::$default_delivery_channels = $delivery_channels;
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

    public static function get_additional_criteria_component(): string {
        return 'mock template not implemented';
    }

    public static function is_valid_additional_criteria(array $additional_criteria, extended_context $extended_context): bool {
        // Validity is determined by the 'valid' property that was set when the mock notification was created.
        return $additional_criteria['valid'];
    }

    public static function meets_additional_criteria(array $additional_criteria, array $event_data): bool {
        // An event is valid if has the 'meets_criteria' property in the event data.
        return $event_data['meets_criteria'];
    }
}