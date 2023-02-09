<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package message_popup
 */

namespace message_popup\webapi\resolver\mutation;

use core\entity\notification;
use core\orm\query\builder;
use core\webapi\execution_context;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\mutation_resolver;
use core_message\api;
use invalid_parameter_exception;

class mark_messages_read extends mutation_resolver {
    /**
     * This updates a list of messages as being read.
     *
     * @param array             $args
     * @param execution_context $ec
     * @return array
     */
    public static function resolve(array $args, execution_context $ec): array {
        global $USER;
        $message_ids = $args['input']['message_ids'];
        if (empty($message_ids)) {
            throw new invalid_parameter_exception('empty message id list');
        }

        // Following logic from message\externallib::mark_message_read()
        $timeread = time();
        $notifications = static::get_notifications($message_ids);

        // We are doing the logic check first before any database write happening.
        foreach ($message_ids as $message_id) {
            if (!isset($notifications[$message_id])) {
                throw new invalid_parameter_exception('Invalid messageid, the message doesn\'t exist');
            }

            $notification = $notifications[$message_id];

            if ($notification->useridto != $USER->id) {
                throw new invalid_parameter_exception(
                    'Invalid messageid, you don\'t have permissions to mark this message as read'
                );
            }
        }

        // Validation is over, we should be able to move on to update the records.
        foreach ($message_ids as $message_id) {
            $notification = $notifications[$message_id];
            api::mark_notification_as_read($notification->get_record(), $timeread);
        }

        return ['read_message_ids' => $message_ids];
    }

    /**
     * @param int[] $message_ids
     * @return notification[]
     */
    private static function get_notifications(array $message_ids): array {
        $builder = builder::table(notification::TABLE);
        $builder->where_in('id', $message_ids);
        $builder->map_to(notification::class);

        return $builder->fetch();
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            require_authenticated_user::class
        ];
    }
}