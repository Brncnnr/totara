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
namespace totara_notification\factory;

use cache;
use cache_loader;
use core_component;
use totara_core\extended_context;
use totara_notification\interactor\notification_audit_interactor;
use totara_notification\interactor\notification_preference_interactor;
use totara_notification\local\helper as base_helper;
use totara_notification\resolver\notifiable_event_resolver;
use totara_notification\resolver\resolver_helper;

/**
 * A factory class that helps to get all the resolver classes that exist in the system.
 * Note that we can only filter the resolvers by the class name. Not by the context.
 */
class notifiable_event_resolver_factory {
    /**
     * @var string
     */
    public const MAP_KEY = 'map';

    /**
     * notifiable_event_resolver_factory constructor.
     * Prevent the construction
     */
    private function __construct() {
    }

    /**
     * Returns the cache loader for totara_notification::notifiable_resolver_map
     * @return cache
     */
    public static function get_cache_loader(): cache_loader {
        return cache::make('totara_notification', 'notifiable_resolver_map');
    }

    /**
     * @return void
     */
    public static function load_map(): void {
        $loader = static::get_cache_loader();
        $map = $loader->get(static::MAP_KEY);

        if (!is_array($map)) {
            // If it is an array and it is empty, meaning that we make
            // it empty for the test. Otherwise the cache was never
            // initialised beforehand.
            $map = core_component::get_namespace_classes_grouped_by_component(
                'totara_notification\\resolver',
                notifiable_event_resolver::class
            );
            $loader->set(static::MAP_KEY, $map);
        }
    }

    /**
     * Get all the resolver classes within the system, and filter by $component
     * if there is value passed in.
     *
     * @param string|null $component
     * @return string[]
     */
    public static function get_resolver_classes(?string $component = null): array {
        static::load_map();

        $cache = static::get_cache_loader();
        $map = $cache->get(static::MAP_KEY, MUST_EXIST);

        if (!empty($component)) {
            return $map[$component] ?? [];
        }

        return array_merge(...array_values($map));
    }

    /**
     * @param string|null $component
     * @return string[]
     */
    public static function get_scheduled_resolver_classes(?string $component = null): array {
        $classes = static::get_resolver_classes($component);
        return array_filter(
            $classes,
            function (string $cls): bool {
                return resolver_helper::is_valid_scheduled_event_resolver($cls);
            }
        );
    }


    /**
     * Get the available notification resolvers for a given context (and component)
     *
     * @param int|null $context_id
     * @param string|null $component
     * @return string[]
     */
    public static function get_context_available_resolvers($context_id = null, ?string $component = null): array {
        $resolvers = self::get_resolver_classes($component);

        if (!empty($context_id)) {
            $context = \context::instance_by_id($context_id);
        } else {
            // Default to the system context.
            $context = \context_system::instance();
        }

        $extended_context = extended_context::make_with_context($context);

        $available = [];
        foreach ($resolvers as $resolver_class) {
            // Check the resolver is supported in the given context.
            $supported = call_user_func_array([$resolver_class, 'supports_context'], [$extended_context]);
            if (!$supported) {
                continue;
            }

            // If the resolver has a parent context and it is disabled in the parent context then we exclude it.
            $parent_extended_context = $extended_context->get_parent();
            if ($parent_extended_context && base_helper::is_resolver_disabled_by_any_context(
                $resolver_class,
                $parent_extended_context
            )) {
                continue;
            }

            $available[] = $resolver_class;
        }

        return $available;
    }

    /**
     * Returns true if there are any resolvers at the given context and the current user has some capability to manage/audit
     * the notifications that are applicable at that same context
     *
     * @param extended_context $extended_context The context we are checking at.
     * @param int|null $user_id The user who is being checked, defaults to $USER->id.
     * @param bool $only_auditing If true then only auditing capabilities are checked.
     * @return bool
     */
    public static function context_has_resolvers_with_capabilities(extended_context $extended_context, int $user_id = null, bool $only_auditing = false): bool {
        global $USER;

        $user_id ??= $USER->id;

        $resolvers = array_filter(
            self::get_resolver_classes(),
            function (string $resolver_class) use ($extended_context): bool {
                /** @see self::supports_context() */
                return call_user_func_array(
                    [$resolver_class, 'supports_context'],
                    [$extended_context]
                );
            }
        );

        if (empty($resolvers)) {
            // No resolvers, the answer is no.
            return false;
        }

        // Check capabilities, outcome is our result.
        $audit_interactor = new notification_audit_interactor($extended_context, $user_id);
        if ($only_auditing) {
            return $audit_interactor->has_any_capability_for_context();
        } else {
            $pref_interactor = new notification_preference_interactor($extended_context, $user_id);
            return ($pref_interactor->has_any_capability_for_context() || $audit_interactor->has_any_capability_for_context());
        }
    }

}
