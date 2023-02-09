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

use coding_exception;
use core\orm\entity\model;
use core\orm\query\builder;
use core\orm\query\order;
use mod_approval\exception\model_exception;

/**
 * Renumber ordinal numbers after deletion.
 */
final class move extends operation {
    /**
     * Update ordinal numbers.
     *
     * @param model $parent
     * @param model $item_deleted
     * @return boolean
     */
    public function execute(model $parent, model $item_deleted): bool {
        if (!empty($item_deleted->id)) {
            throw new coding_exception('item is not deleted');
        }
        if (!$this->belongs_to($item_deleted, $parent)) {
            throw new model_exception('item does not belong to the parent');
        }
        return builder::get_db()->transaction(function () use ($parent, $item_deleted) {
            $ordinal = $this->map_ordinal_number($item_deleted);
            $item_ids = $this->get_moveable_items($parent, $ordinal);
            $this->do_move($item_ids, $ordinal);
            return true;
        });
    }

    /**
     * @param model $parent
     * @param integer $ordinal
     * @return integer[]
     */
    private function get_moveable_items(model $parent, int $ordinal): array {
        $field = $this->ordinal_field();
        return builder::table($this->table_name())
            ->where($this->foreign_key(), $parent->id)
            ->where($field, '>', $ordinal)
            ->order_by($field, order::DIRECTION_ASC)
            ->select(['id'])
            ->get()
            ->keys();
    }

    /**
     * @param integer[] $ids
     * @param integer $ordinal
     */
    private function do_move(array $ids, int $ordinal): void {
        foreach ($ids as $id) {
            $this->do_update($id, $ordinal);
            $ordinal++;
        }
    }
}
