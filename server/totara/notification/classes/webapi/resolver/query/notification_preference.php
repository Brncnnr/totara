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
use totara_notification\exception\notification_exception;
use totara_notification\interactor\notification_preference_interactor;
use totara_notification\model\notification_preference as model;

/**
 * Resolves query totara_notification_notification_preference
 * @deprecated Since totara 17.0
 */
class notification_preference extends query_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     * @return model
     */
    public static function resolve(array $args, execution_context $ec): model {
        global $USER;
        debugging('Do not use notification_preference::resolve any more, use notification_preference_v2::resolve instead', DEBUG_DEVELOPER);

        // If the record does not exist, then exception will be thrown.
        $notification_preference = model::from_id($args['id']);
        $extended_context = $notification_preference->get_extended_context();

        $interactor = new notification_preference_interactor($extended_context, $USER->id);
        $resolver_class_name = $notification_preference->get_resolver_class_name();

        if (!$interactor->can_manage_notification_preferences_of_resolver($resolver_class_name)) {
            throw notification_exception::on_manage();
        }

        if (CONTEXT_SYSTEM != $extended_context->get_context_level() && !$ec->has_relevant_context()) {
            $ec->set_relevant_context($extended_context->get_context());
        }

        return $notification_preference;
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
