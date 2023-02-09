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
namespace totara_notification\webapi\resolver\type;

use coding_exception;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use totara_core\extended_context;
use totara_notification\entity\notifiable_event_preference as notifiable_event_preference_entity;
use totara_notification\interactor\interactor_helper;
use totara_notification\loader\delivery_channel_loader;
use totara_notification\loader\notification_preference_loader;
use totara_notification\local\helper;
use totara_notification\local\schedule_helper;
use totara_notification\model\notifiable_event_preference as notifiable_event_preference_model;
use totara_notification\resolver\abstraction\additional_criteria_resolver;
use totara_notification\resolver\notifiable_event_resolver;
use totara_notification\resolver\resolver_helper;

/**
 * Type resolver for totara_notification_event_resolver.
 * @deprecated Since Totara 17.0
 */
class event_resolver extends type_resolver {
    /**
     * Note that at this point we are going to use $source as the notifiable resolver class name
     * to resolve the field's value of a totara_notification_event_resolver graphql type.
     *
     * Ideally the $source should be a model of notifiable_event_preference, however it had not  yet
     * been implemented and will be done in TL-29288 & TL-29289
     *
     * @param string            $field
     * @param string|notifiable_event_resolver $source
     * @param array             $args
     * @param execution_context $ec
     * @return mixed|null
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        global $USER;
        debugging('Do not use event_resolver::resolve any more, use event_resolver_v2::resolve instead', DEBUG_DEVELOPER);

        if (!is_string($source) || !resolver_helper::is_valid_event_resolver($source)) {
            throw new coding_exception("Invalid source passed to the resolver (event_resolver)");
        }
        switch ($field) {
            case 'component':
                return resolver_helper::get_component_of_resolver_class_name($source);

            case 'plugin_name':
                return resolver_helper::get_human_readable_plugin_name($source);

            case 'class_name':
                return (string) $source;

            case 'name':
                return resolver_helper::get_human_readable_resolver_name($source);

            case 'interactor':
                $extended_context = self::get_extended_context_from_args($args, $ec);
                return interactor_helper::get_resolver_interaction_in_context($extended_context, (string)$source, $USER->id);

            case 'notification_preferences':
                $extended_context = self::get_extended_context_from_args($args, $ec);

                return notification_preference_loader::get_notification_preferences($extended_context, $source);

            case 'valid_schedules':
                return schedule_helper::get_available_schedules_for_resolver($source);

            case 'recipients':
                return helper::get_component_of_recipients($source);

            case 'status':
                // Default extended context.
                $extended_context = self::get_extended_context_from_args($args, $ec);

                // Note that this only works because the only property that can be modified on a resolver below the system
                // context is the status, and because it is not possible to re-enable it in a lower context. If one of
                // these properties changes then we'd need to keep track of the 'undefined' (null) value.
                return [
                    'is_enabled' => !helper::is_resolver_disabled_by_any_context(
                        $source,
                        $extended_context
                    )
                ];

            case 'default_delivery_channels':
                // Note that this only works because delivery channel can only be set in the system context. If we allow
                // it to be changed in lower contexts then we would need to load the specific context and keep track of
                // the 'undefined' (null) value. Also see comment on 'status' above.
                $extended_context = extended_context::make_system();

                // Find the notifiable event preference record
                $entity = notifiable_event_preference_entity::repository()->for_context($source, $extended_context);

                // If there's no override, return the defaults
                if (!$entity) {
                    return delivery_channel_loader::get_for_event_resolver($source);
                }

                $model = notifiable_event_preference_model::from_entity($entity);
                return $model->default_delivery_channels;

            case 'additional_criteria_component':
                if (resolver_helper::is_additional_criteria_resolver($source)) {
                    /** @var additional_criteria_resolver $source */
                    return $source::get_additional_criteria_component();
                } else {
                    return null;
                }

            case 'warnings':
                $extended_context = self::get_extended_context_from_args($args, $ec);

                /** @var notifiable_event_resolver $source */
                return $source::get_warnings($extended_context);

            default:
                throw new coding_exception("The field '{$field}' is not yet supported");
        }
    }

    /**
     * @param array $args
     * @param execution_context|null $ec
     * @return extended_context
     */
    private static function get_extended_context_from_args(array $args, ?execution_context $ec = null): extended_context {
        $extended_context_args = $args['extended_context'] ?? [];

        // Default extended context.
        $extended_context = extended_context::make_system();

        if (isset($extended_context_args['context_id'])) {
            $extended_context = extended_context::make_with_id(
                $extended_context_args['context_id'],
                $extended_context_args['component'] ?? extended_context::NATURAL_CONTEXT_COMPONENT,
                $extended_context_args['area'] ?? extended_context::NATURAL_CONTEXT_AREA,
                $extended_context_args['item_id'] ?? extended_context::NATURAL_CONTEXT_ITEM_ID
            );
        } else if ($ec && $ec->has_relevant_context()) {
            $context = $ec->get_relevant_context();
            $extended_context = extended_context::make_with_context($context);
        }
        return $extended_context;
    }
}