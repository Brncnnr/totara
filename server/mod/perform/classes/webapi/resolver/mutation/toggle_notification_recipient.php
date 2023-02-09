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
use mod_perform\models\activity\notification_recipient as notification_recipient_model;

class toggle_notification_recipient extends mutation_resolver {
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

        // Set activation.
        if (!isset($input['active'])) {
            throw new \invalid_parameter_exception('active not set as part of input');
        }
        $active = $input['active'] ?? false;

        $relationship_id = $input['relationship_id'] ?? 0;
        /** @var notification_recipient_model $recipient */
        $recipient = $notification->get_recipients()->find('core_relationship_id', $relationship_id);
        if (!$recipient) {
            throw new \invalid_parameter_exception(
                'Invalid relationship_id specified or recipient record for relationship with ID '
                . $relationship_id . ' does not exist'
            );
        }

        $recipient->toggle($active);
        $notification->refresh();

        return (object) [
            'notification' => $notification,
        ];
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
