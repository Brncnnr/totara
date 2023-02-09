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

namespace totara_notification\webapi\resolver\query;

use context;
use context_system;
use core\webapi\execution_context;
use core\webapi\query_resolver;
use core\webapi\middleware\require_login;
use totara_core\extended_context;
use totara_notification\exception\notification_exception;
use totara_notification\factory\notifiable_event_resolver_factory;
use totara_notification\interactor\notification_preference_interactor;
use totara_notification\loader\notifiable_event_user_preference_loader;

class notifiable_event_user_preferences extends query_resolver {
    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec): array {
        global $USER;

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

        if (CONTEXT_SYSTEM != $context->contextlevel && !$ec->has_relevant_context()) {
            $ec->set_relevant_context($context);
        }

        // Ascertain whether querying user may request this information.
        $user_id = (int) $args['user_id'];
        $interactor = new notification_preference_interactor($extended_context, $USER->id);
        if ($USER->id != $user_id && !$interactor->can_manage_notification_preferences()) {
            throw notification_exception::on_manage();
        }

        return notifiable_event_user_preference_loader::get_user_resolver_classes($user_id, $extended_context);
    }

    /**
     * @return array
     */
    public static function get_middleware(): array {
        return [
            new require_login()
        ];
    }
}
