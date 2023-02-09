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

/**
 * Ordinal interface.
 */
interface ordinal {
    /**
     * Return the name of the database table.
     *
     * @return string
     */
    public function table_name(): string;

    /**
     * Return the name of the foreign key that refers to the parent entity.
     *
     * @return string
     */
    public function foreign_key(): string;

    /**
     * Return the name of the ordinal number field.
     *
     * @return string
     */
    public function ordinal_field(): string;

    /**
     * Return the name of the updated timestamp field.
     *
     * @return string|null return null if none applicable
     */
    public function timestamp_field(): ?string;

    /**
     * Get the ordinal number of $item.
     *
     * @param model $item
     * @return integer
     */
    public function map_ordinal_number(model $item): int;
}
