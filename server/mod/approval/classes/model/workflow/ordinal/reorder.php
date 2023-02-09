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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\model\workflow\ordinal;

use core\orm\entity\model;
use core\orm\query\builder;
use mod_approval\exception\model_exception;

/**
 * Renumber ordinal numbers according to a user-supplied list.
 */
final class reorder extends operation {
    /**
     * Update ordinal numbers.
     *
     * @param model $parent
     * @param model[] $reference_items
     * @param model[] $updating_items
     * @return boolean
     */
    public function execute(model $parent, array $reference_items, array $updating_items): bool {
        $this->validate($parent, $reference_items, $updating_items);
        $items_being_updated = $this->get_updatable_items($updating_items);
        if (empty($items_being_updated)) {
            return false;
        }
        builder::get_db()->transaction(function () use ($items_being_updated) {
            $this->do_reorder($items_being_updated);
        });
        return true;
    }

    /**
     * @param model[] $items
     * @return integer[]
     */
    private function get_ids_of_items(array $items): array {
        return array_map(function (model $item) {
            return $item->id;
        }, $items);
    }

    /**
     * @param model $parent
     * @param model[] $reference_items
     * @param model[] $updating_items
     */
    private function validate(model $parent, array $reference_items, array $updating_items): void {
        if (count($reference_items) !== count($updating_items)) {
            throw new model_exception('items do not match');
        }
        $reference_ids = $this->get_ids_of_items($reference_items);
        $updating_ids = $this->get_ids_of_items($updating_items);
        if (!empty(array_diff($reference_ids, $updating_ids)) || !empty(array_diff($updating_ids, $reference_ids))) {
            throw new model_exception('items do not match');
        }
        foreach ($updating_items as $item) {
            if (!$this->belongs_to($item, $parent)) {
                throw new model_exception('item does not belong to the parent');
            }
        }
    }

    /**
     * @param model[] $updating_items
     * @return integer[] array of [item_id => new_ordinal]
     */
    private function get_updatable_items(array $updating_items): array {
        $updatable_items = [];
        foreach ($updating_items as $index => $item) {
            $item_id = $item->id;
            $current_ordinal_number = $this->map_ordinal_number($item);
            $new_ordinal_number = $index + 1;
            if ($current_ordinal_number != $new_ordinal_number) {
                $updatable_items[$item_id] = $new_ordinal_number;
            }
        }
        return $updatable_items;
    }

    /**
     * @param integer[] $updatable_items array of [item_id => new_ordinal]
     */
    private function do_reorder(array $updatable_items): void {
        [$in_sql, $params] = builder::get_db()->get_in_or_equal(array_keys($updatable_items));
        $ordinal_field = $this->ordinal_field();
        builder::get_db()->execute("UPDATE {{$this->table_name()}} SET {$ordinal_field} = 0 - {$ordinal_field} WHERE id {$in_sql}", $params);
        foreach ($updatable_items as $item_id => $new_ordinal) {
            $this->do_update($item_id, $new_ordinal);
        }
    }
}
