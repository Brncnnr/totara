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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */
namespace totara_notification\webapi\resolver\query;

use core\webapi\execution_context;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use totara_core\extended_context;
use totara_notification\exception\notification_exception;
use totara_notification\interactor\notification_preference_interactor;
use totara_notification\loader\notification_preference_loader;
use totara_notification\model\notification_preference as model;

/**
 * Resolving query totara_notification_notification_preferences
 * @deprecated Since totara 17.0
 */
class notification_preferences extends query_resolver {
    /**
     * @param array             $args
     * @param execution_context $ec
     *
     * @return model[]
     */
    public static function resolve(array $args, execution_context $ec): array {
        global $USER;
        debugging('Do not use notification_preferences::resolve any more as it has been deprecated', DEBUG_DEVELOPER);
        $context_args = $args['extended_context'];
        $extended_context = extended_context::make_with_id(
            $context_args['context_id'],
            $context_args['component'] ?? extended_context::NATURAL_CONTEXT_COMPONENT,
            $context_args['area'] ?? extended_context::NATURAL_CONTEXT_AREA,
            $context_args['item_id'] ?? extended_context::NATURAL_CONTEXT_ITEM_ID
        );

        $resolver_class_name = $args['resolver_class_name'] ?? null;
        $interactor = new notification_preference_interactor($extended_context, $USER->id);

        if (null !== $resolver_class_name && !$interactor->can_manage_notification_preferences_of_resolver($resolver_class_name)) {
            throw notification_exception::on_manage();
        } else if (!$interactor->can_manage_notification_preferences()) {
            // This is for the case where the resolver class name is null. It could have been a part of the if statement
            // above. However, splitting them into two different blocks might be easier to read.
            throw notification_exception::on_manage();
        }

        if (CONTEXT_SYSTEM != $extended_context->get_context_level() && !$ec->has_relevant_context()) {
            $ec->set_relevant_context($extended_context->get_context());
        }

        return notification_preference_loader::get_notification_preferences(
            $extended_context,
            $resolver_class_name,
            $args['at_context_only'] ?? false
        );
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