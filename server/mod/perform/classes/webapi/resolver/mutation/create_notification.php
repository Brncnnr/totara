<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author  Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\mutation;

use core\webapi\execution_context;
use core\webapi\mutation_resolver;
use core\webapi\middleware\require_advanced_feature;

use mod_perform\models\activity\activity;
use mod_perform\webapi\middleware\require_activity;
use mod_perform\webapi\middleware\require_manage_capability;

use mod_perform\models\activity\notification as notification_model;

/**
 * Handles the "mod_perform_create_notification" GraphQL mutation.
 *
 * @deprecated since Totara 13.2
 */
class create_notification extends mutation_resolver {
    /**
     * {@inheritdoc}
     * @deprecated since Totara 13.2
     */
    public static function resolve(array $args, execution_context $ec) {
        debugging(
            'The GraphQL mutation mod_perform_create_notification is deprecated and should no longer be used. ' .
            'Notification records are no longer dynamically created and are now created upon the creation of the activity.',
            DEBUG_DEVELOPER
        );

        // Activity id is verified, and activity is loaded, by middleware.
        /** @var activity $activity */
        $activity = $args['activity'];

        // Get input from args
        $input = $args['input'] ?? 0;
        if (!$input) {
            throw new \invalid_parameter_exception('missing mod_perform_create_notification_input');
        }

        $class_key = $input['class_key'] ?? 0;
        if (!$class_key) {
            throw new \invalid_parameter_exception('class_key not set as part of input');
        }

        // Load the notification - all notifications are created automatically, so creating it here would create an error.
        $notification = notification_model::load_by_activity_and_class_key($activity, $class_key);

        // Build and return result object.
        $result = new \stdClass();
        $result->notification = $notification;

        return $result;
    }

    /**
     * {@inheritdoc}
     * @deprecated since Totara 13.2
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('performance_activities'),
            require_activity::by_activity_id('input.activity_id', true),
            require_manage_capability::class
        ];
    }
}
