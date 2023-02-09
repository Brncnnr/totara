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

use core\orm\entity\entity;
use core\orm\entity\model;
use core\orm\query\builder;

/**
 * Allocate ordinal number.
 */
final class allocate extends operation {
    /**
     * Set the next available ordinal number to the entity field.
     *
     * @param model $parent
     * @param entity $entity
     */
    public function execute(model $parent, entity $entity): void {
        $field = $this->ordinal_field();
        /** @var mixed */
        $records = builder::table($this->table_name())
            ->where($this->foreign_key(), $parent->id)
            ->select("MAX({$field}) as maximum")
            ->fetch();
        if (!empty($records) && !empty(current($records)->maximum)) {
            $entity->set_attribute($field, current($records)->maximum + 1);
        } else {
            $entity->set_attribute($field, 1);
        }
    }
}
