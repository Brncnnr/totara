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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_notification
 */

namespace totara_notification\webapi\resolver\mutation;

use core\webapi\execution_context;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use totara_core\extended_context;
use totara_notification\delivery\channel\delivery_channel;
use totara_notification\entity\notifiable_event_preference as entity;
use totara_notification\exception\notification_exception;
use totara_notification\interactor\notification_preference_interactor;
use totara_notification\model\notifiable_event_preference;
use totara_notification\webapi\middleware\validate_delivery_channel_components;
use totara_notification\webapi\middleware\validate_resolver_class_name;

class update_default_delivery_channels extends mutation_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     * @return delivery_channel[]
     */
    public static function resolve(array $args, execution_context $ec): array {
        global $USER;

        // Default extended context.
        $extended_context = extended_context::make_system();

        // Note: there is no point to set the execution's context at the moment, because
        // we are only expecting the context system.

        // Find the notifiable event preference we're going to update
        $resolver_class_name = $args['resolver_class_name'];
        $notifiable_event_entity = entity::repository()->for_context($resolver_class_name, $extended_context);
        if (!$notifiable_event_entity) {
            $notifiable_event = notifiable_event_preference::create($resolver_class_name, $extended_context);
        } else {
            $notifiable_event = notifiable_event_preference::from_entity($notifiable_event_entity);
        }

        $interactor = new notification_preference_interactor($extended_context, $USER->id);
        if (!$interactor->can_manage_notification_preferences_of_resolver($resolver_class_name)) {
            throw notification_exception::on_manage();
        }

        // Load the delivery channels
        $delivery_channels = $notifiable_event->default_delivery_channels;

        // Force all to disabled, then enable those provided
        foreach ($delivery_channels as $delivery_channel) {
            $delivery_channel->set_enabled(false);
        }
        foreach ($args['default_delivery_channels'] as $arg_delivery_channel) {
            // If the parent channel is disabled, don't set this either (let it be disabled)
            $channel = $delivery_channels[$arg_delivery_channel];
            if ($channel->is_sub_delivery_channel && !in_array($channel->parent, $args['default_delivery_channels'])) {
                continue;
            }

            $channel->set_enabled(true);
        }

        // Update the model with the new delivery channels
        $notifiable_event->set_default_delivery_channels($delivery_channels);
        $notifiable_event->save();

        return $notifiable_event->default_delivery_channels;
    }

    /**
     * @return array
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new validate_resolver_class_name('resolver_class_name', true),
            new validate_delivery_channel_components('default_delivery_channels', true),
        ];
    }
}