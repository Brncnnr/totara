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
use totara_notification\model\notification_preference;
use totara_notification\placeholder\placeholder_option;
use totara_notification\resolver\abstraction\audit_resolver;
use totara_notification\resolver\notifiable_event_resolver;
use totara_notification\resolver\abstraction\permission_resolver;

class totara_notification_mock_notifiable_event_resolver extends notifiable_event_resolver implements permission_resolver, audit_resolver {

    public static $notification_not_sent_call_count = 0;

    public static $notification_sent_call_count = 0;

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
     * @var string|null
     */
    private static $test_plugin_name = null;

    /*
     * @var string[]
     */
    public static $warnings = [];

    /**
     * @var notification_preference
     */
    public static $preference_passed_to_get_attachment;

    /**
     * @var object
     */
    public static $user_passed_to_get_attachment;

    /**
     * @var bool
     */
    public static $is_get_attachment_params_valid = false;

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

        if(isset(self::$preference_passed_to_get_attachment)) {
            self::$preference_passed_to_get_attachment = null;
        }

        if(isset(self::$user_passed_to_get_attachment)) {
            self::$user_passed_to_get_attachment = null;
        }

        if(isset(self::$is_get_attachment_params_valid)) {
            self::$user_passed_to_get_attachment = false;
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

    public function notification_not_sent(notification_preference $preference, int $reason): void {
        // When called, simply increment the count so that tests can see.
        self::$notification_not_sent_call_count++;
    }

    public function notification_sent(notification_preference $preference): void {
        // When called, simply increment the count so that tests can see.
        self::$notification_sent_call_count++;
    }

    /**
     * Allows tests to reset the number of times that notification_not_sent has been called.
     */
    public static function reset_notification_not_sent_call_count(): void {
        self::$notification_not_sent_call_count = 0;
    }

    /**
     * Allows tests to reset the number of times that notification_sent has been called.
     */
    public static function reset_notification_sent_call_count(): void {
        self::$notification_sent_call_count = 0;
    }

    /**
     * Allows tests to check how many time the notification_not_sent function has been called.
     *
     * When using this function, make sure that you either record the previous count before running
     * the test or else reset the count using reset_notification_not_sent_call_count().
     *
     * @return int
     */
    public static function get_notification_not_sent_call_count(): int {
        return self::$notification_not_sent_call_count;
    }

    /**
     * Allows tests to check how many time the notification_not_sent function has been called.
     *
     * When using this function, make sure that you either record the previous count before running
     * the test or else reset the count using reset_notification_sent_call_count().
     *
     * @return int
     */
    public static function get_notification_sent_call_count(): int {
        return self::$notification_sent_call_count;
    }

    /**
     * Allow tests to specify a custom plugin name, which is used for grouping resolvers in the front end.
     *
     * @param string|null $name
     * @return void
     */
    public static function set_test_plugin_name(?string $name): void {
        self::$test_plugin_name = $name;
    }

    /**
     * Get the custom plugin name specified during the test. Defaults to null, which results in
     * test_get_human_readable_plugin_name returning the notification's component's "pluginname" string.
     *
     * @return string|null
     */
    public static function get_plugin_name(): ?string {
        return self::$test_plugin_name;
    }

    /*
     * Allows tests to set warnings.
     *
     * @param string[] $warnings
     * @return void
     */
    public static function set_warnings(array $warnings): void {
        self::$warnings = $warnings;
    }

    /**
     * Override the base get_warnings (as is intended with this function) to return the warnings set
     * during the test.
     *
     * @param extended_context $extended_context
     * @return array|string[]
     */
    public static function get_warnings(extended_context $extended_context): array {
        $warnings = parent::get_warnings($extended_context);
        return array_merge($warnings, self::$warnings);
    }

    /**
     * @param ?notification_preference $preference
     * @return void
     */
    public static function set_attachment_preference(?notification_preference $preference): void {
        self::$preference_passed_to_get_attachment = $preference;
    }

    /**
     * @param ?object $user
     * @return void
     */
    public static function set_attachment_user(?object $user): void {
        self::$user_passed_to_get_attachment = $user;
    }

    /**
     * @return bool
     */
    public static function is_get_attachment_params_valid(): bool {
        return self::$is_get_attachment_params_valid;
    }

    /**
     * @param notification_preference $preference
     * @param object $user
     *
     * @return array
     */
    public function get_attachments(notification_preference $preference, $user): array {
        global $DB, $USER;

        $ical_attachments = [$this->generate_dummy_ical_attachment(), $this->generate_dummy_ical_attachment()];

        self::$is_get_attachment_params_valid = self::$preference_passed_to_get_attachment === $preference && self::$user_passed_to_get_attachment === $user;

        $attachments = [];
        foreach($ical_attachments as $idx => $ical_attach) {
            $ical_content = $ical_attach->content;
            $ical_uids = null;
            $ical_method = '';

            if (!empty($ical_content)) {
                preg_match_all('/UID:([^\r\n ]+)/si', $ical_content, $matches);
                $ical_uids = $matches[1];
                preg_match('/METHOD:([a-z]+)/si', $ical_content, $matches);
                $ical_method = $matches[1];
            }

            $attachments[] = [
                'attachname' => 'test' . $idx . '.ics',
                'attachment' => $ical_attach->file,
                'ical_uids' => $ical_uids,
                'ical_method' => $ical_method,
            ];
        }

        return $attachments;
    }

    private function generate_dummy_ical_attachment() {
        global $CFG;

        $user = get_admin();
        $now = time();

        $contextid = \context_user::instance($user->id)->id;
        $fs = get_file_storage();
        $draftitemid = rand(1, 999999999);
        while ($files = $fs->get_area_files($contextid, 'user', 'draft', $draftitemid)) {
            $draftitemid = rand(1, 999999999);
        }

        $icalmethod = 'REQUEST';

        $dt_start = self::ical_generate_timestamp(strtotime('+1 day'));
        $dt_end   = self::ical_generate_timestamp(strtotime('+2 days'));
        $seq = rand(1, 2147483647);

        $dt_stamp = self::ical_generate_timestamp($now);
        $url_bits = parse_url($CFG->wwwroot);

        $uid =
            $dt_stamp .
            // Unique identifier, salted with site identifier.
            '-' . substr(md5($CFG->siteidentifier . $seq . $user->id), -8) .
            '-' . $seq .
            '@' . $url_bits['host']; // Hostname for this moodle installation


        $event = implode("\r\n", [
            "BEGIN:VEVENT",
            "ORGANIZER;CN={$user->email}:MAILTO:{$user->email}",
            "DTSTART:{$dt_start}",
            "DTEND:{$dt_end}",
            "SEQUENCE:{$seq}",
            "UID:{$uid}",
            "DTSTAMP:{$dt_stamp}",
            "DESCRIPTION:Whatever description",
            "SUMMARY:Whatever summary",
            "PRIORITY:5",
            "CLASS:PUBLIC",
            "END:VEVENT",
        ]);
        $events = implode("\r\n", [$event]);

        $template = implode("\r\n", [
            "BEGIN:VCALENDAR",
            "VERSION:2.0",
            "PRODID:-//Moodle//NONSGML Facetoface//EN",
            "METHOD:{$icalmethod}",
            "{$events}",
            "END:VCALENDAR\r\n"
        ]);

        return (object) [
            'file' => $fs->create_file_from_string([
                'contextid' => $contextid,
                'component' => 'user',
                'filearea' => 'draft',
                'itemid' => $draftitemid,
                'filepath' => '/',
                'filename' => 'ical.ics'
            ], $template),
            'content' => $template
        ];
    }

    /**
     * Generates a timestamp for Ical
     *
     * @param int $timestamp
     * @return string|false a formatted date string. If a non-numeric value is used for timestamp, false is returned
     */
    private static function ical_generate_timestamp($timestamp) {
        return gmdate('Ymd', $timestamp) . 'T' . gmdate('His', $timestamp) . 'Z';
    }

}