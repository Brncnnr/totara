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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */
namespace totara_notification\repository;

use coding_exception;
use context_system;
use core\entity\context;
use core\orm\entity\repository;
use core\orm\query\builder;
use totara_core\extended_context;
use totara_notification\entity\notification_preference;

/**
 * Repository for table "ttr_notification_preference"
 */
class notification_preference_repository extends repository {
    /**
     * Return null if there is no record under context and ancestor_id. Otherwise entity record.
     *
     * @param extended_context $extended_context
     * @param int $ancestor_id
     *
     * @return notification_preference|null
     */
    public function find_by_context_and_ancestor_id(
        extended_context $extended_context,
        int $ancestor_id
    ): ?notification_preference {
        $this->builder->where('context_id', $extended_context->get_context_id());
        $this->builder->where('component', $extended_context->get_component());
        $this->builder->where('area', $extended_context->get_area());
        $this->builder->where('item_id', $extended_context->get_item_id());
        $this->builder->where('ancestor_id', $ancestor_id);

        /** @var notification_preference|null $entity */
        $entity = $this->builder->one();
        return $entity;
    }

    /**
     * @param string $notification_class_name
     * @return notification_preference|null
     */
    public function find_in_system_context(string $notification_class_name): ?notification_preference {
        $extended_context = extended_context::make_with_context(context_system::instance());
        return $this->find_built_in($notification_class_name, $extended_context);
    }

    /**
     * @param string $notification_class_name
     * @param extended_context $extended_context
     * @return notification_preference|null
     */
    public function find_built_in(string $notification_class_name, extended_context $extended_context): ?notification_preference {
        $this->builder->where('context_id', $extended_context->get_context_id());
        $this->builder->where('component', $extended_context->get_component());
        $this->builder->where('area', $extended_context->get_area());
        $this->builder->where('item_id', $extended_context->get_item_id());
        $this->builder->where('notification_class_name', ltrim($notification_class_name, '\\'));

        /** @var notification_preference|null $entity */
        $entity = $this->builder->one();
        return $entity;
    }

    /**
     * Delete a custom notification and all descendants
     *
     * @param int $id
     */
    public function delete_custom(int $id): void {
        $this->builder->or_where('id', $id)->or_where('ancestor_id', $id)->delete();
    }

    /**
     * Delete all custom notifications that existing in the given context or a descendant context
     *
     * @param extended_context $extended_context
     */
    public function delete_custom_by_context(extended_context $extended_context): void {
        // If it is not a natural context then it can have no descendents, so just delete in that context.
        if (!$extended_context->is_natural_context()) {
            $this->builder->where('context_id', $extended_context->get_context_id())
                ->where('component', $extended_context->get_component())
                ->where('area', $extended_context->get_area())
                ->where('item_id', $extended_context->get_item_id())
                ->delete();
            return;
        }

        // Find all contexts that are descendants of the given context, including itself.
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