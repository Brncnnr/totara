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
use core\webapi\mutation_resolver;
use core\webapi\middleware\require_login;
use moodle_exception;
use totara_core\extended_context;
use totara_notification\entity\notifiable_event_user_preference as notifiable_event_user_preference_entity;
use totara_notification\local\notifiable_event_user_preference_helper;
use totara_notification\model\notifiable_event_user_preference as notifiable_event_user_preference_model;
use totara_notification\webapi\middleware\validate_delivery_channel_components;
use totara_notification\webapi\middleware\validate_resolver_class_name;

/**
 * Class update_notifiable_event_user_delivery_channels
 *
 * @package totara_notification\webapi\resolver\mutation
 */
class update_notifiable_event_user_delivery_channels extends mutation_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     * @return array
     */
    public static function resolve(array $args, execution_context $ec) {
        global $USER;

        $user_id = $args['user_id'] ?? $USER->id;

        // For now only allowing a user to change his own preferences.
        // We may in future add a separate capability and allow users with this capability
        // to change other users' settings
        if ($user_id != $USER->id || isguestuser()) {
            throw new moodle_exception('error_user_preference_permission', 'totara_notification');
        }

        $resolver_class_name = $args['resolver_class_name'];
        $extended_context_args = $args['extended_context'] ?? [];
        $delivery_channels = $args['delivery_channels'];
        $preference_id = $args['user_preference_id'] ?? null;

        // Default extended context.
        $extended_context = extended_context::make_system();

        if (isset($extended_context_args['context_id'])) {
            $extended_context = extended_context::make_with_id(
                $extended_context_args['context_id'],
                $extended_context_args['component'] ?? extended_context::NATURAL_CONTEXT_COMPONENT,
                $extended_context_args['area'] ?? extended_context::NATURAL_CONTEXT_AREA,
                $extended_context_args['item_id'] ?? extended_context::NATURAL_CONTEXT_ITEM_ID
            );
        } else if ($ec->has_relevant_context()) {
            $context = $ec->get_relevant_context();
            $extended_context = extended_context::make_with_context($context);
        }

        if ($preference_id === null) {
            // Ensure we don't try to create a duplicate
            $preference_ids = notifiable_event_user_preference_entity::repository()
                ->select('id')
                ->filter_by_user($user_id)
                ->filter_by_resolver_class($resolver_class_name)
                ->filter_by_extended_context($extended_context)
                ->get()
                ->pluck('id');

            if (!empty($preference_ids)) {
                // There should be at most 1, but just making sure
                $preference_id = reset($preference_ids);
            }
        }

        $model = $preference_id === null
            ? notifiable_event_user_preference_model::create($user_id, $resolver_class_name, $extended_context)
            : notifiable_event_user_preference_model::from_id($preference_id);

        $model->set_delivery_channels($delivery_channels)->save();
        $entity = new notifiable_event_user_preference_entity($model->get_id());

        return notifiable_event_user_preference_helper::format_response_data($user_id, $resolver_class_name, $entity);
    }

    /**
     * @return array
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new validate_resolver_class_name('resolver_class_name', true),
            new validate_delivery_channel_components('delivery_channels', false),
        ];
    }
}