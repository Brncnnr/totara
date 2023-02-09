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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\mutation;

use core\webapi\execution_context;
use core\webapi\mutation_resolver;
use core\webapi\middleware\require_advanced_feature;

use mod_perform\webapi\middleware\require_activity;
use mod_perform\webapi\middleware\require_manage_capability;

use mod_perform\models\activity\notification as notification_model;

class update_notification_triggers extends mutation_resolver {
    /**
     * {@inheritdoc}
     */
    public static function resolve(array $args, execution_context $ec) {
        // Get input from args
        $input = $args['input'] ?? 0;
        if (!$input) {
            throw new \invalid_parameter_exception('missing mod_perform_toggle_notification_recipient_input');
        }

        $notification_id = $input['notification_id'] ?? 0;
        if (!$notification_id) {
            throw new \invalid_parameter_exception('notification_id not set as part of input');
        }
        $notification = notification_model::load_by_id($notification_id);

        // Set triggers.
        if (!isset($input['values'])) {
            throw new \invalid_parameter_exception('values not set as part of input');
        }
        if (!is_array($input['values'])) {
            throw new \invalid_parameter_exception('values not in valid format');
        }
        $notification->set_triggers($input['values']);
        $notification->refresh();

        // Build and return result object.
        $result = new \stdClass();
        $result->notification = $notification;

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('performance_activities'),
            require_activity::by_notification_id('input.notification_id', true),
            require_manage_capability::class
        ];
    }
}
