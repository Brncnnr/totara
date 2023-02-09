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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_notification
 */

namespace totara_notification\loader;

use totara_core\extended_context;
use totara_notification\entity\notifiable_event_preference as notifiable_event_preference_entity;
use totara_notification\entity\notifiable_event_user_preference as notifiable_event_user_preference_entity;
use totara_notification\factory\notifiable_event_resolver_factory;
use totara_notification\local\notifiable_event_user_preference_helper;

class notifiable_event_user_preference_loader {
    /**
     * Preventing this class from construction.
     */
    private function __construct() {
    }

    /**
     * @param int $user_id
     * @param extended_context|null $extended_context
     * @param bool $apply_transformations If true, the formatter must apply any extra transformations to match GraphQL
     * @return array
     */
    public static function get_user_resolver_classes(
        int $user_id,
        ?extended_context $extended_context = null,
        bool $apply_transformations = false
    ): array {
        $resolver_classes = notifiable_event_resolver_factory::get_resolver_classes();

        // Remove all resolver classes disabled on system level
        $admin_disabled_resolver_classes = notifiable_event_preference_entity::repository()
            ->select('resolver_class_name')
            ->filter_by_extended_context($extended_context)
            ->where('enabled', 0)
            ->get()
            ->pluck('resolver_class_name');

        if (count($admin_disabled_resolver_classes) > 0) {
            $resolver_classes = array_filter($resolver_classes, function (string $resolver_class_name) use ($admin_disabled_resolver_classes) {
                return !in_array($resolver_class_name, $admin_disabled_resolver_classes);
            });
        }

        // Add user_id and enabled
        $user_resolver_preferences = notifiable_event_user_preference_entity::repository()
            ->filter_by_extended_context($extended_context)
            ->where('user_id', $user_id)
            ->get();

        $results = array_map(function ($resolver_class_name) use ($user_id, $user_resolver_preferences, $apply_transformations) {
            $user_preference = $user_resolver_preferences->find('resolver_class_name', $resolver_class_name);
            return notifiable_event_user_preference_helper::format_response_data(
                $user_id,
                $resolver_class_name,
                $user_preference,
                $apply_transformations
            );
        }, $resolver_classes);

        return $results;
    }

}