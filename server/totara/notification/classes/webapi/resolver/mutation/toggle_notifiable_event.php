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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_notification
 */
namespace totara_notification\webapi\resolver\mutation;

use core\webapi\execution_context;
use core\webapi\mutation_resolver;
use core\webapi\middleware\require_login;
use totara_core\extended_context;
use totara_notification\entity\notifiable_event_preference as entity;
use totara_notification\exception\notification_exception;
use totara_notification\interactor\notification_preference_interactor;
use totara_notification\model\notifiable_event_preference;
use totara_notification\resolver\resolver_helper;
use totara_notification\webapi\middleware\validate_resolver_class_name;

/**
 * @deprecated Since Totara 17.0
 */
class toggle_notifiable_event extends mutation_resolver {
    /**
     * @param array             $args
     * @param execution_context $ec
     *
     * @return string   Concrete class
     */
    public static function resolve(array $args, execution_context $ec): string {
        global $USER;
        debugging('No not use toggle_notifiable_event::resolve any more use toggle_notifiable_event_v2::resolve instead', DEBUG_DEVELOPER);
        $extended_context_args = $args['extended_context'] ?? [];

        // Default extended context.
        $extended_context = extended_context::make_system();

        if (isset($extended_context_args['context_id'])) {
            if (!\context::instance_by_id($extended_context_args['context_id'], IGNORE_MISSING)) {
                throw new \coding_exception(
                    "Invalid context"
                );
            }
            $extended_context = extended_context::make_with_id(
                $extended_context_args['context_id'],
                $extended_context_args['component'] ?? extended_context::NATURAL_CONTEXT_COMPONENT,
                $extended_context_args['area'] ?? extended_context::NATURAL_CONTEXT_AREA,
                $extended_context_args['item_id'] ?? extended_context::NATURAL_CONTEXT_ITEM_ID
            );
        }

        if (CONTEXT_SYSTEM != $extended_context->get_context_level() && !$ec->has_relevant_context()) {
            $context = $extended_context->get_context();
            $ec->set_relevant_context($context);
        }

        $resolver_class_name = $args['resolver_class_name'];

        resolver_helper::validate_event_resolver($resolver_class_name);
        $context_supported = call_user_func([$resolver_class_name, 'supports_context'], $extended_context);
        if (!$context_supported) {
            throw new \coding_exception(
                "Resolver does not support provided context"
            );
        }

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

        $is_enabled = $args['is_enabled'] ?? true;

        $notifiable_event->set_enabled($is_enabled);
        $notifiable_event->save();

        return $resolver_class_name;
    }

    /**
     * @return array
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new validate_resolver_class_name('resolver_class_name', true)
        ];
    }
}