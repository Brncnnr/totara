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
namespace totara_notification\resolver;

use coding_exception;
use totara_core\extended_context;
use totara_notification\entity\notifiable_event_preference as notifiable_event_preference_entity;
use totara_notification\entity\notifiable_event_user_preference as notifiable_event_user_preference_entity;
use totara_notification\local\helper;
use totara_notification\model\notifiable_event_preference as notifiable_event_preference_model;
use totara_notification\model\notifiable_event_user_preference as notifiable_event_user_preference_model;
use totara_notification\model\notification_preference;
use totara_notification\notification\built_in_notification;
use totara_notification\placeholder\placeholder_option;
use totara_notification\placeholder\template_engine\engine;
use totara_notification\placeholder\template_engine\square_bracket\engine as square_bracket_engine;
use totara_notification\recipient\recipient;
use totara_notification\recipient\virtual_recipient;

/**
 * Given that the {@see built_in_notification} is the default configuration that used to define the message, delivery
 * channels, recipient and so on, then this class resolver will be used by the notifiable event to do all sort of
 * business logic calculation to help construct the message, finding out the actual list of users and the time to send
 * the notifications out.
 *
 * The children of this class must be matching with the class that implement interface notifiable_event_preference.
 * For example, if your notifiable_event_preference class is 'totara_core\event\course_created' then the resolver class
 * that you are introduce should be 'totara_core\totara_notification\resolver\course_created'. The destination of the
 * class itself should follow the same namespace as above.
 *
 * The class also provides all the available metadata (options) for a system admin to create a custom notification
 * for this very specific event or edit a built-in notification that come out-of-box.
 */
abstract class notifiable_event_resolver {

    // Constants indicating reasons why a notification might not have been sent.
    const NOT_SENT_DISABLED = 1; // The notification preference was disabled. DO NOT USE FOR DISABLED RESOLVER!
    const NOT_SENT_NO_PROCESSORS = 2; // No message processors were enabled, so there was no way to send notifications.

    /**
     * @var array
     */
    protected $event_data;

    /**
     * notifiable_event_resolver constructor.
     * Preventing any complicated construction.
     *
     * @param array $event_data
     */
    final public function __construct(array $event_data) {
        $this->event_data = $event_data;
    }

    /**
     * Returning the list of user's id of whom should the notification should be sent to.
     * Note that the current behavior is for prototyping only, the API will sure be changed,
     * hence no unit tests for this function.
     *
     * @param string $recipient_class
     * @return int[]
     */
    public function get_recipient_ids(string $recipient_class): array {
        $recipient_class = ltrim($recipient_class, '\\');
        if (!helper::is_valid_recipient_class($recipient_class)) {
            throw new coding_exception(
                "Invalid recipient class passed to the notifiable event resolver"
            );
        }

        $recipient_classes = array_map(
            function (string $cls): string {
                return ltrim($cls, '\\');
            },
            static::get_notification_available_recipients()
        );

        if (!in_array($recipient_class, $recipient_classes)) {
            throw new coding_exception("Invalid recipient class");
        }

        /** @see recipient::get_user_ids() */
        /** @var $recipient_class recipient */
        return $recipient_class::get_user_ids($this->event_data);
    }

    /**
     * Returning the list of user's id of whom should the notification should be sent to.
     * Note that the current behavior is for prototyping only, the API will sure be changed,
     * hence no unit tests for this function.
     *
     * @param string $recipient_class
     * @return int[]
     */
    public function get_recipient_virtual_users(string $recipient_class): array {
        $recipient_class = ltrim($recipient_class, '\\');
        if (!helper::is_valid_recipient_class($recipient_class)) {
            throw new coding_exception(
                "Invalid recipient class passed to the notifiable event resolver"
            );
        }

        $recipient_classes = array_map(
            function (string $cls): string {
                return ltrim($cls, '\\');
            },
            static::get_notification_available_recipients()
        );

        if (!in_array($recipient_class, $recipient_classes)) {
            throw new coding_exception("Invalid recipient class");
        }

        /** @see virtual_recipient::get_users() */
        /** @var $recipient_class virtual_recipient */
        return $recipient_class::get_user_objects($this->event_data);
    }

    /**
     * Get the placeholder engine instance. It can either be square bracket
     * engine or mustache engine. By default, we are using square bracket
     * engine, however the children can define which engine it is using.
     *
     * @return engine
     */
    public function get_placeholder_engine(): engine {
        return square_bracket_engine::create(
            static::class,
            $this->event_data
        );
    }

    /**
     * Fetch the notifiable event preference if it exists.
     *
     * @param extended_context $extended_context
     * @return notifiable_event_preference_model|null
     */
    public function get_notifiable_event_preference(extended_context $extended_context): ?notifiable_event_preference_model {
        $entity = notifiable_event_preference_entity::repository()
            ->for_context(static::class, $extended_context);

        if ($entity) {
            return notifiable_event_preference_model::from_entity($entity);
        }

        return null;
    }

    /**
     * Fetch the notifiable event preference for the specified user.
     *
     * @param extended_context $extended_context
     * @return notifiable_event_preference_model|null
     */
    public function get_notifiable_event_user_preference(int $user_id, extended_context $extended_context): ?notifiable_event_user_preference_model {
        $entity = notifiable_event_user_preference_entity::repository()
            ->filter_by_user($user_id)
            ->filter_by_resolver_class(static::class)
            ->filter_by_extended_context($extended_context)
            ->get()
            ->first();

        if ($entity) {
            return notifiable_event_user_preference_model::from_entity($entity);
        }

        return null;
    }

    /**
     * This is to check whether the resolver is processed through event queue or not and also it could be override if
     * dev want to skip queueing up.
     *
     * @return bool
     */
    public static function uses_on_event_queue(): bool {
        $cls = get_called_class();

        if (self::class === $cls) {
            throw new coding_exception(
                "This function is not supported by abstract class itself"
            );
        }

        $cls = ltrim($cls, '\\');
        $parts = explode('\\', $cls);

        $component = reset($parts);
        $event_name = end($parts);

        return class_exists("{$component}\\event\\{$event_name}");
    }

    /**
     * Returns the title for this notifiable event, which should be used
     * within the tree table of available notifiable events.
     *
     * @return string
     */
    abstract public static function get_notification_title(): string;

    /**
     * Returns an array of available recipients (metadata) for this event (concrete class).
     *
     * @return array
     */
    abstract public static function get_notification_available_recipients(): array;

    /**
     * Returns the default delivery channels that defined for the event by developers.
     * However, note that admin can override this default delivery channels.
     *
     * If nothing/a specific channel is not listed here, it will fallback to the built in default.
     * To disable it, specify the actual default here.
     *
     * @return array
     */
    abstract public static function get_notification_default_delivery_channels(): array;

    /**
     * Returns the list of available placeholder options.
     *
     * @return placeholder_option[]
     */
    abstract public static function get_notification_available_placeholder_options(): array;

    /**
     * Indicates whether the resolver supports the given context.
     * By default, resolvers support the system context.
     * Override this function to support other contexts.
     *
     * @param extended_context $extended_context
     * @return bool
     */
    public static function supports_context(extended_context $extended_context): bool {
        $context = $extended_context->get_context();

        if (!$extended_context->is_natural_context()) {
            return false;
        }
        if ($context->contextlevel === CONTEXT_SYSTEM) {
            return true;
        }
        return false;
    }

    /**
     * Returns the extended context of where this event occurred. Note that this should almost certainly be
     * either the same as the natural context (but wrapped in the extended context container class) or an
     * extended context where the natural context is the immediate parent.
     *
     * @return extended_context
     */
    abstract public function get_extended_context(): extended_context;

    /**
     * @return array
     */
    public function get_event_data(): array {
        return $this->event_data;
    }

    /**
     * Newly created records with enabled = null inherit the setting from their parent object (if one exists).  Where
     * there is no parent, we use this default value instead.
     *
     * @return bool
     */
    public static function get_default_enabled(): bool {
        return true;
    }

    /**
     * When a notification is not sent, the resolver will be notified by calling this function. Resolvers can implement
     * this function and use it to perform actions when notification are not sent, such as logging the event.
     *
     * @param notification_preference $preference
     * @param int $reason
     */
    public function notification_not_sent(notification_preference $preference, int $reason): void {
    }

    /**
     * When a notification is sent, the resolver will be notified by calling this function. Resolvers can implement
     * this function and use it to perform actions when notification are sent, such as logging the event.
     *
     * Note that the event data used when sending the notification can be obtained from $this->event_data.
     *
     * @param notification_preference $preference
     */
    public function notification_sent(notification_preference $preference): void {
    }

    /**
     * Override to specify the user-readable group name which the resolver should belong to.
     *
     * By default, this will be the "plugin_name" of the component that this resolver belongs to, but is
     * undefined for components that do not have this lang file and string key, and instead will default
     * to the name of the component, such as "core_course".
     *
     * @return string|null
     */
    public static function get_plugin_name(): ?string {
        return null;
    }

    /*
     * Override to return an array of strings letting users (notif admins) know that there is a problem with
     * some configuration which will occur if this resolver is used in this context. For example, if some
     * functionality is disabled which means that notifications will never be fired from this resolver.
     *
     * @param extended_context $extended_context
     * @return string[]
     */
    public static function get_warnings(extended_context $extended_context): array {
        return [];
    }

    /**
     * Override to specify the attachments to add on the email.
     * Each attachment should be an array of properties and should specify at least  'attachname' and 'attachment'
     *
     * @param notification_preference $preference
     * @param object $user
     *
     * @return array of attachments
     */
    public function get_attachments(notification_preference $preference, $user): array {
        return [];
    }

    /**
     * Override if this resolver is related to a subject user and the id is not provided present in 'user_id' or if 'user_id' is not the subject's id
     * @return int
     */
    public function get_subject(): int {
        return $this->get_event_data()['user_id'] ?? 0;
    }

    /**
     * Please override with the correct values
     *
     * Returns the string key and optional component
     *
     * @return array
     */
    public function get_notification_log_display_string_key_and_params(): array {
        // Keeping this backward compatible by returning a generic string instead of making the method abstract

        $resolver_class_name = static::class;
        return [
            'key' => 'notifiable_event_triggered',
            'component' => 'totara_notification',
            'params' => ['resolver_title' => '']
        ];
    }

    /**
     * Wrapper for get_string to allow resolvers to override if there are string params like dates that can only be translated at display time
     *
     * @param string $string_key
     * @param array $string_params
     * @return string
     */
    public static function format_event_log_display_string(string $string_key, array $string_params): string {
        $component = $string_params['component'] ?? 'core';
        $extra_params = $string_params['params'] ?? [];

        if (isset($extra_params['resolver_title'])) {
            $extra_params['resolver_title'] = static::get_notification_title();
        }

        if (!empty($extra_params['date'])) {
            $extra_params['date'] = userdate($extra_params['date']);
        }

        return get_string($string_key, $component, $extra_params);
    }
}