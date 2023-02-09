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
namespace totara_notification\webapi\resolver\query;

use context;
use context_system;
use core\webapi\execution_context;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use totara_core\extended_context;
use totara_notification\exception\notification_exception;
use totara_notification\factory\notifiable_event_resolver_factory;
use totara_notification\interactor\notification_audit_interactor;
use totara_notification\interactor\notification_preference_interactor;
use totara_notification\local\helper;
use totara_notification\resolver\notifiable_event_resolver;

/**
 * @deprecated Since Totara 17.0
 */
class event_resolvers extends query_resolver {
    /**
     * @param array             $args
     * @param execution_context $ec
     * @return string[]
     */
    public static function resolve(array $args, execution_context $ec): array {
        global $USER;
        debugging('Do not use event_resolvers::resolve any more, use event_resolvers_v2::resolve instead', DEBUG_DEVELOPER);

        // Note: for now we are returning a list of notifiable_event_resolver classes within the system.
        // However for future development, we might just do sort of DB looks up to get all the notifiable
        // event within configuration within the system
        $context = context_system::instance();
        $extended_context_args = $args['extended_context'];

        if (isset($extended_context_args['context_id'])) {
            $context = context::instance_by_id($args['extended_context']['context_id']);
        }

        $extended_context = extended_context::make_with_context(
            $context,
            $extended_context_args['component'] ?? extended_context::NATURAL_CONTEXT_COMPONENT,
            $extended_context_args['area'] ?? extended_context::NATURAL_CONTEXT_AREA,
            $extended_context_args['item_id'] ?? extended_context::NATURAL_CONTEXT_ITEM_ID
        );

        if (CONTEXT_SYSTEM != $extended_context->get_context_level() && !$ec->has_relevant_context()) {
            $ec->set_relevant_context($context);
        }

        // Check if the user has any of the capabilities provided by the integrated plugins or not.
        if (!notifiable_event_resolver_factory::context_has_resolvers_with_capabilities($extended_context)) {
            // Note: i'm not sure if throwing exception is the ideal way or return an empty result is.
            throw notification_exception::on_manage();
        }

        $resolver_classes = notifiable_event_resolver_factory::get_resolver_classes();
        $preference_interactor = new notification_preference_interactor($extended_context, $USER->id);
        $audit_interactor = new notification_audit_interactor($extended_context, $USER->id);

        return array_filter(
            $resolver_classes,
            function ($resolver_class) use ($preference_interactor, $audit_interactor, $extended_context): bool {
                /**
                 * @see notifiable_event_resolver::supports_context()
                 * @var bool $support
                 */
                $support = call_user_func_array([$resolver_class, 'supports_context'], [$extended_context]);
                if (!$support) {
                    return false;
                }

                // If the resolver has a parent context and it is disabled in the parent context then we exclude it.
                $parent_extended_context = $extended_context->get_parent();
                if ($parent_extended_context && helper::is_resolver_disabled_by_any_context(
                    $resolver_class,
                    $parent_extended_context
                )) {
                    return false;
                }

                // We are only displaying the event resolver if the user has the capability to
                // manage/interact with the resolver.
                return $preference_interactor->can_manage_notification_preferences_of_resolver($resolver_class)
                    || $audit_interactor->can_audit_notifications_of_resolver($resolver_class);
            }
        );
    }

    /**
     * @return array
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
        ];
    }
}
