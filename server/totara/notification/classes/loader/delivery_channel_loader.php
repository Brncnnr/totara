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
 * @author  Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_notification
 */

namespace totara_notification\loader;

use core_component;
use totara_core\extended_context;
use totara_notification\delivery\channel\delivery_channel;
use totara_notification\entity\notifiable_event_preference as notifiable_event_preference_entity;
use totara_notification\entity\notifiable_event_user_preference as notifiable_event_user_preference_entity;
use totara_notification\model\notifiable_event_preference as notifiable_event_preference_model;

/**
 * Class delivery_channel_helper
 *
 * @package totara_notification\local
 */
final class delivery_channel_loader {
    /**
     * @var array
     */
    private static $definitions;

    /**
     * @var array
     */
    private static $resolver_channels = [];

    /**
     * @var array
     */
    private static $enabled_outputs;

    /**
     * Returns a list of all available delivery channel classes.
     *
     * @return array
     */
    public static function get_built_in_classes(): array {
        if (null === self::$definitions) {
            $plugin_names = core_component::get_plugin_list('message');
            $plugin_names = array_keys($plugin_names);

            foreach ($plugin_names as $plugin_name) {
                $class = "message_{$plugin_name}\\totara_notification\\delivery\\channel\\delivery_channel";
                if (class_exists($class)) {
                    self::$definitions[] = $class;
                }
            }
        }

        return self::$definitions;
    }

    /**
     * Return a list of all delivery channels for enabled outputs, in their default state.
     *
     * @return delivery_channel[]
     */
    public static function get_defaults(): array {
        /** @var delivery_channel[] $defaults */
        $defaults = [];
        $enabled_outputs = self::get_enabled_outputs();
        foreach (self::get_built_in_classes() as $built_in_class) {
            /** @var delivery_channel $channel */
            $channel = call_user_func([$built_in_class, 'make']);
            // Skip any that do not have the matching output enabled
            if (!in_array($channel->component, $enabled_outputs)) {
                continue;
            }
            // If this is a sub channel, then skip if the parent is disabled
            if ($channel->is_sub_delivery_channel && !in_array($channel->parent, $enabled_outputs)) {
                continue;
            }

            $defaults[$channel->component] = $channel;
        }

        return self::sort_channels($defaults);
    }

    /**
     * @param string $resolver_class_name
     * @return delivery_channel[]
     */
    public static function get_for_event_resolver(string $resolver_class_name): array {
        $defaults = self::get_defaults();
        $default_enabled_keys = call_user_func([$resolver_class_name, 'get_notification_default_delivery_channels']);

        if (is_array($default_enabled_keys)) {
            foreach ($default_enabled_keys as $default_enabled_key) {
                if (!isset($defaults[$default_enabled_key])) {
                    // It's possible a setting might have been saved for a output that's no longer enabled,
                    // so we continue quietly here as it's not exceptional.
                    continue;
                }
                $defaults[$default_enabled_key]->set_enabled(true);
            }
        }

        return $defaults;
    }

    /**
     * Convert the delivery channel list into the collection of delivery channels.
     *
     * @param string $resolver_class_name
     * @param array $delivery_channel_list
     * @return delivery_channel[]
     */
    public static function get_from_list(string $resolver_class_name, array $delivery_channel_list): array {

        // Load the initial list up for the notifiable event
        $channels = self::get_for_event_resolver($resolver_class_name);

        // Mark any channels listed as enabled
        $changed = [];
        foreach ($delivery_channel_list as $component) {
            // We can have empty entries, since the split also includes boundaries.
            if (empty($component) || !isset($channels[$component])) {
                continue;
            }

            $channels[$component]->set_enabled(true);
            $changed[] = $component;
        }

        // Now mark any that we didn't touch as disabled
        foreach ($channels as $channel) {
            if (!in_array($channel->component, $changed)) {
                $channel->set_enabled(false);
            }
        }

        return self::sort_channels($channels);
    }

    /**
     * Return the list of delivery channels with the user preferences applied.
     * Falls back to the admin defaults and finally the built in defaults.
     *
     * @param string $resolver_class_name
     * @param array|null $delivery_channel_list
     * @return delivery_channel[]
     */
    public static function get_from_user_preferences(string $resolver_class_name, ?array $delivery_channel_list): array {
        // If $delivery_channel_list is an array, then the user has overridden the preferences, therefore we
        // process those and ignore the admin defaults (as it's a either-or situation).
        if (null !== $delivery_channel_list) {
            return self::get_from_list($resolver_class_name, $delivery_channel_list);
        }

        // Otherwise we need to load the admin defaults (at system context)
        $context = extended_context::make_system();
        $entity = notifiable_event_preference_entity::repository()->for_context($resolver_class_name, $context);
        if ($entity && $entity->exists()) {
            $model = notifiable_event_preference_model::from_entity($entity);
            return $model->default_delivery_channels;
        }

        // Finally, just use the defaults
        return delivery_channel_loader::get_for_event_resolver($resolver_class_name);
    }

    /**
     * Sort the delivery channels based on their display_order preference.
     *
     * @param delivery_channel[] $channels
     * @return delivery_channel[]
     */
    private static function sort_channels(array $channels): array {
        uasort($channels, function ($channel_a, $channel_b) {
            return $channel_a->display_order <=> $channel_b->display_order;
        });

        return $channels;
    }

    /**
     * Lookup what delivery channels this user, in this context, for this resolver class should use.
     * Will return the keys for the enabled delivery channels only.
     *
     * @param int $user_id
     * @param extended_context $extended_context
     * @param string $resolver_class
     * @param bool $reset Load any lookups fresh, bypassing the request cache
     * @return array
     */
    public static function get_user_enabled_delivery_channels(
        int $user_id,
        extended_context $extended_context,
        string $resolver_class,
        bool $reset = false
    ): array {
        // Load the user's preferences first
        /** @var $user_preference notifiable_event_user_preference_entity */
        $user_preference = notifiable_event_user_preference_entity::repository()
            ->filter_by_user($user_id)
            ->filter_by_resolver_class($resolver_class)
            ->filter_by_extended_context($extended_context)
            ->order_by('id')
            ->first();

        if ($user_preference) {
            if (!$user_preference->enabled) {
                // If the preference is disabled, return no delivery channels
                return [];
            }
            if (null !== $user_preference->delivery_channels) {
                // Return the list of channels. If null it means we need to use the defaults.
                return array_intersect($user_preference->delivery_channels, self::get_enabled_outputs());
            }
        }

        // User has no preferences, then we need to grab the admin default channels instead
        // Check that we've not previously called for this resolver
        if (array_key_exists($resolver_class, self::$resolver_channels) && !$reset) {
            $resolver_channels = self::$resolver_channels[$resolver_class];
            if ($resolver_channels !== null) {
                return $resolver_channels;
            }
        } else {
            // Key doesn't exist, so no lookup took place yet. Do the lookup and cache it for this request.
            $extended_context = extended_context::make_system();

            /** @var notifiable_event_preference_entity $resolver_preference */
            $resolver_preference = notifiable_event_preference_entity::repository()
                ->filter_by_extended_context($extended_context)
                ->filter_by_resolver_class_name($resolver_class)
                ->filter_by_enabled(true)
                ->filter_by_has_overridden_default_delivery_channels()
                ->order_by('id')
                ->first();

            if ($resolver_preference) {
                $channels = array_values(array_filter(explode(',', $resolver_preference->default_delivery_channels)));
                $channels = array_intersect($channels, self::get_enabled_outputs());
                if (!$reset) {
                    self::$resolver_channels[$resolver_class] = $channels;
                }
                return $channels;
            }
            // Null indicates there's no specific preference
            self::$resolver_channels[$resolver_class] = null;
        }

        // Fall back to the resolver built in defaults
        if (!isset(self::$resolver_channels[$resolver_class . '_default']) || $reset) {
            $channels = [];
            $built_in_defaults = delivery_channel_loader::get_for_event_resolver($resolver_class);
            foreach ($built_in_defaults as $delivery_channel) {
                if ($delivery_channel->is_enabled) {
                    $channels[] = $delivery_channel->component;
                }
            }
            $channels = array_intersect($channels, self::get_enabled_outputs());

            if ($reset) {
                return $channels;
            }

            self::$resolver_channels[$resolver_class . '_default'] = $channels;
        }

        return self::$resolver_channels[$resolver_class . '_default'];
    }

    /**
     * Reset the loader cache
     */
    public static function reset(): void {
        self::$resolver_channels = [];
    }

    /**
     * Get a simple list of the message outputs that are enabled.
     * Will return an array of output names, which should match delivery channel components.
     *
     * @return array
     */
    private static function get_enabled_outputs(): array {
        global $CFG;
        if (null === self::$enabled_outputs) {
            require_once($CFG->dirroot . '/message/lib.php');
            self::$enabled_outputs = array_keys(get_message_processors(true));
        }

        return self::$enabled_outputs;
    }
}