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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_notification
 */

namespace totara_notification;

use core\orm\query\builder;
use totara_core\extended_context;
use totara_notification\entity\notifiable_event_queue;
use totara_notification\entity\notification_preference;
use totara_notification\entity\notification_queue;
use totara_notification\resolver\notifiable_event_resolver;

/**
 * This class can be called for other plugins
 *
 * Class external_helper
 */
class external_helper {
    /**
     * helper constructor.
     * Preventing this class from instantiation.
     */
    private function __construct() {
    }

    /**
     * @param notifiable_event_resolver $resolver
     */
    public static function create_notifiable_event_queue(notifiable_event_resolver $resolver): void {
        $queue = new notifiable_event_queue();
        $queue->resolver_class_name = get_class($resolver);
        $queue->set_decoded_event_data($resolver->get_event_data());
        $queue->set_extended_context($resolver->get_extended_context());

        $queue->save();
    }

    /**
     * When target item has been deleted, we need to remove records from notification preference and queue table.
     *
     * @param int $context_id
     * @param string $component
     * @param string $area
     * @param int $item_id
     */
    public static function remove_notification_preferences(
        int $context_id,
        string $component = '',
        string $area = '',
        int $item_id = 0
    ): void {
        $extend_context = extended_context::make_with_id($context_id, $component, $area, $item_id);
        $db = builder::get_db();
        $transaction = $db->start_delegated_transaction();

        notification_preference::repository()->delete_custom_by_context($extend_context);
        notifiable_event_queue::repository()->dequeue($extend_context);
        notification_queue::repository()->dequeue($extend_context);

        $transaction->allow_commit();
    }
}