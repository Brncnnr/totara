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
 * @author Alvin Smith <alvin.smith@totaralearning.com>
 * @package totara_notification
 */
namespace totara_notification\repository;

use coding_exception;
use core\orm\entity\repository;
use core\orm\lazy_collection;
use core\orm\query\builder;
use totara_core\extended_context;

/**
 * Repository for table "ttr_notification_queue"
 */
class notification_queue_repository extends repository {
    /**
     * Returns a list of notification queues that are less than the $current_time.
     * If $current_time is passed as either NULL or 0 then it would use the
     * {@see time()} from PHP.
     *
     * @param int|null $current_time
     * @return lazy_collection
     */
    public function get_due_notification_queues(?int $current_time = null): lazy_collection {
        if (empty($current_time)) {
            $current_time = time();
        }

        $this->builder->where('scheduled_time', '<=', $current_time);
        return $this->builder->get_lazy();
    }

    /**
     * Remove all queued notifications that belong to the given context or its descendants
     *
     * @param extended_context $extended_context
     */
    public function dequeue(extended_context $extended_context): void {
        // If it is not a natural context then it can have no descendents, so just delete in that context.
        if (!$extended_context->is_natural_context()) {
            $this->builder->where('context_id', $extended_context->get_context_id())
                ->where('component', $extended_context->get_component())
                ->where('area', $extended_context->get_area())
                ->where('item_id', $extended_context->get_item_id())
                ->delete();
            return;
        }

        $db = builder::get_db();
        $context = $extended_context->get_context();
        $context_ids = $db->get_fieldset_select(
            'context',
            'id',
            "path LIKE " . $db->sql_concat(':path', "'%'"),
            [
                'path' => $context->path,
            ]
        );

        // Remove all records where they belong to one of the descendant contexts, including the given context.
        $this->builder->where_in('context_id', $context_ids)->delete();
    }
}