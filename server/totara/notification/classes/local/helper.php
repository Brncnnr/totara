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
namespace totara_notification\local;

use coding_exception;
use totara_core\extended_context;
use totara_notification\entity\notifiable_event_preference as entity;
use totara_notification\event\notifiable_event;
use totara_notification\model\notifiable_event_preference;
use totara_notification\notification\built_in_notification;
use totara_notification\recipient\recipient;
use totara_notification\resolver\abstraction\additional_criteria_resolver;
use totara_notification\resolver\notifiable_event_resolver;
use totara_notification\resolver\resolver_helper;

class helper {
    /**
     * helper constructor.
     * Preventing this class from instantiation.
     */
    private function __construct() {
    }

    /**
     * @param string $event_class_name
     * @return bool
     */
    public static function is_valid_notifiable_event(string $event_class_name): bool {
        return is_a($event_class_name, notifiable_event::class, true);
    }

    /**
     * @param string $built_in_notification_class_name
     * @return bool
     */
    public static function is_valid_built_in_notification(string $built_in_notification_class_name): bool {
        if (!class_exists($built_in_notification_class_name)) {
            return false;
        }

        return is_subclass_of($built_in_notification_class_name, built_in_notification::class);
    }

    /**
     * @param string $resolver_class_name
     * @return array
     */
    public static function get_component_of_recipients(string $resolver_class_name): array {
        if (!resolver_helper::is_valid_event_resolver($resolver_class_name)) {
            throw new coding_exception("Resolver class is an invalid notifiable event resolver");
        }

        /**
         * @see notifiable_event_resolver::get_notification_available_recipients()
         * @var string[] $recipients
         */
        $recipients = call_user_func([$resolver_class_name, 'get_notification_available_recipients']);
        if (count($recipients) == 0) {
            throw new coding_exception("Class {$resolver_class_name} need to define recipient");
        }

        return $recipients;
    }

    /**
     * @param string $recipient_class
     * @return bool
     */
    public static function is_valid_recipient_class(string $recipient_class): bool {
        return is_a($recipient_class, recipient::class, true);
    }

    /**
     * Check the specified resolver in the specified context
     * to see if there are any disabled flags set.
     *
     * @param string $resolver_class_name
     * @param extended_context $extended_context
     * @return bool
     * @deprecated since 14.4
     */
    public static function is_resolver_enabled(
        string $resolver_class_name,
        extended_context $extended_context
    ): bool {
        debugging('totara_notification\local\helper::is_resolver_enabled was deprecated in 14.4', DEBUG_DEVELOPER);

        $notifiable_event_entity = entity::repository()->for_context($resolver_class_name, $extended_context);
        if ($notifiable_event_entity) {
            $notifiable_event = notifiable_event_preference::from_entity($notifiable_event_entity);
            if ($notifiable_event->get_enabled() !== null) {
                return $notifiable_event->get_enabled();
            }
        }

        // If null return default.
        return $resolver_class_name::get_default_enabled();
    }

    /**
     * Check the entire context tree (bottom to top) from this context to
     * see if there are any disabled flags set.
     *
     * @param string $resolver_class_name
     * @param extended_context $extended_context
     * @return bool
     * @deprecated since 14.4
     */
    public static function is_resolver_enabled_for_all_parent_contexts(
        string $resolver_class_name,
        extended_context $extended_context
    ): bool {
        debugging(
            'totara_notification\local\helper::is_resolver_enabled_for_all_parent_contexts was deprecated in 14.4. ' .
            'Instead use totara_notification\local\helper::is_resolver_disabled_by_any_context',
            DEBUG_DEVELOPER
        );

        return !self::is_resolver_disabled_by_any_context(
            $resolver_class_name,
            $extended_context->get_parent()
        );
    }

    /**
     * Check the context path from the given context and above to see if there are any disabled flags set.
     *
     * @param notifiable_event_resolver|string $resolver_class_name
     * @param extended_context $descendent_extended_context
     * @return bool
     */
    public static function is_resolver_disabled_by_any_context(
        string $resolver_class_name,
        extended_context $descendent_extended_context
    ): bool {
        // Start with the descendent context.
        $extended_context = $descendent_extended_context;

        while ($extended_context !== null) {
            // Check if the event has been disabled in the context.
            $notifiable_event_entity = entity::repository()->for_context($resolver_class_name, $extended_context);
            if ($notifiable_event_entity) {
                $notifiable_event = notifiable_event_preference::from_entity($notifiable_event_entity);
                if ($notifiable_event->get_enabled() !== null) {
                    if (!$notifiable_event->get_enabled()) {
                        // If it is disabled in a context then it is disabled in the given descendent context and
                        // there's no need to check in any higher context.
                        return true;
                    }
                }
            }
            // Continue up the context path.
            $extended_context = $extended_context->get_parent();
        }

        // If all contexts have returned null, get the default.
        return !$resolver_class_name::get_default_enabled();
    }

    /**
     * @param int|null $request_format
     * @return int
     */
    public static function get_preferred_editor_format(?int $request_format = null): int {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/editorlib.php");

        $editor = editors_get_preferred_editor($request_format);
        return $editor->get_preferred_format();
    }

    /**
     * @param array $resolver_preferences
     * @return array
     */
    public static function transform_resolver_preferences_for_page(array $resolver_preferences): array {
        foreach ($resolver_preferences as $i => $resolver_preference) {
            $delivery_channels = $resolver_preference['delivery_channels'];
            $delivery_channels = array_values(array_map(function ($delivery_channel) {
                return $delivery_channel->to_array();
            }, $delivery_channels));

            $resolver_preference['delivery_channels'] = $delivery_channels;
            $resolver_preferences[$i] = $resolver_preference;
        }

        return $resolver_preferences;
    }

    /**
     * @param string $raw_additional_criteria
     * @param array $event_data
     * @param string $resolver_class_name
     * @param extended_context $extended_context
     * @return bool
     */
    public static function needs_notification(string $raw_additional_criteria,
                                              array $event_data, string $resolver_class_name,
                                              extended_context $extended_context ): bool {

        if (empty($raw_additional_criteria)) {
            $additional_criteria = null;
        } else {
            $additional_criteria = @json_decode(
                $raw_additional_criteria,
                true,
                32,
                JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE | JSON_BIGINT_AS_STRING
            );
            if (!is_array($additional_criteria)) {
                throw new coding_exception('json decoding failed');
            }
        }

        /** @var additional_criteria_resolver $resolver_class_name */
        if (!$resolver_class_name::is_valid_additional_criteria($additional_criteria, $extended_context)) {
            return false;
        }

        if (!$resolver_class_name::meets_additional_criteria(
            $additional_criteria,
            $event_data
        )) {
            return false;
        }

        return  true;
    }
}
