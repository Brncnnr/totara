<?php
/*
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package totara_notification
 */
namespace totara_notification\webapi\resolver\mutation;

use coding_exception;
use core\webapi\execution_context;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use totara_notification\exception\notification_exception;
use totara_notification\interactor\notification_preference_interactor;
use totara_notification\model\notification_preference;

class delete_notification_preference extends mutation_resolver {
    /**
     * @param array             $args
     * @param execution_context $ec
     * @return bool
     */
    public static function resolve(array $args, execution_context $ec): bool {
        global $USER;

        if (empty($args['id'])) {
            throw new coding_exception(get_string('error_preference_id_missing', 'totara_notification'));
        }

        $notification_preference = notification_preference::from_id($args['id']);
        $interactor = new notification_preference_interactor(
            $notification_preference->get_extended_context(),
            $USER->id
        );

        $resolver_class_name = $notification_preference->get_resolver_class_name();
        if (!$interactor->can_manage_notification_preferences_of_resolver($resolver_class_name)) {
            throw notification_exception::on_manage();
        }

        $extended_context = $notification_preference->get_extended_context();
        if (CONTEXT_SYSTEM != $extended_context->get_context_level() && !$ec->has_relevant_context()) {
            $context = $extended_context->get_context();
            $ec->set_relevant_context($context);
        }

        return $notification_preference->delete_custom();
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