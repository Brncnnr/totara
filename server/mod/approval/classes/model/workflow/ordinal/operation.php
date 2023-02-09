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

/**
 * Operation base class.
 */
abstract class operation {
    /** @var ordinal */
    private $ordinal;

    /**
     * Constructor.
     *
     * @param ordinal $ordinal
     */
    public function __construct(ordinal $ordinal) {
        $this->ordinal = $ordinal;
    }

    /**
     * Return the table name.
     *
     * @return string
     */
    final protected function table_name(): string {
        return $this->ordinal->table_name();
    }

    /**
     * Return the foreign key that refers to the parent entity.
     *
     * @return string
     */
    final protected function foreign_key(): string {
        return $this->ordinal->foreign_key();
    }

    /**
     * Return the name of the ordinal number field.
     *
     * @return string
     */
    final protected function ordinal_field(): string {
        return $this->ordinal->ordinal_field();
    }

    /**
     * Return the name of the updated timestamp field.
     *
     * @return string|null return null if none applicable
     */
    final protected function timestamp_field(): ?string {
        return $this->ordinal->timestamp_field();
    }

    /**
     * Get the ordinal number of $item.
     *
     * @param model $item
     * @return integer
     */
    final protected function map_ordinal_number(model $item): int {
        return $this->ordinal->map_ordinal_number($item);
    }

    /**
     * See if $item belongs to $parent.
     *
     * @param model $item
     * @param model $parent
     * @return boolean
     */
    final protected function belongs_to(model $item, model $parent): bool {
        $foreign_key = $this->foreign_key();
        return $item->{$foreign_key} == $parent->id;
    }

    /**
     * Update the ordinal number of the item.
     *
     * @param integer $item_id
     * @param integer $ordinal_number
     */
    final protected function do_update(int $item_id, int $ordinal_number): void {
        $ordinal_field = $this->ordinal_field();
        $time_field = $this->timestamp_field();
        $attributes = [$ordinal_field => $ordinal_number];
        if ($time_field !== null) {
            $attributes[$time_field] = time();
        }
        builder::table($this->table_name())->where('id', $item_id)->update($attributes);
    }
}
