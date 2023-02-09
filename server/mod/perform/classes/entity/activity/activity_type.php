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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\entity\activity;

use core\orm\collection;
use core\orm\entity\entity;
use core\orm\entity\relations\has_many;

/**
 * Represents an activity type record in the repository.
 *
 * @property-read int $id record id
 * @property string $name activity type
 * @property bool $is_system True=read only, false=user defined
 * @property int $created_at record creation time
 * @property-read collection|activity[] $activities all activities of this type
 * @method static activity_type_repository repository()
 */
class activity_type extends entity {
    public const TABLE = 'perform_type';
    public const CREATED_TIMESTAMP = 'created_at';

    /**
     * "is_system" field getter. Needed because the read from the DB _returns
     * strings and the ORM does not convert the value_.
     *
     * TBD: to remove once the base_entity handles this.
     *
     * @param int|null $value incoming value.
     *
     * @return bool the converted value.
     */
    public function get_is_system_attribute(?int $value = null): bool {
        return (bool)$value;
    }

    /**
     * Establishes the relationship with activities.
     *
     * @return has_many the relationship.
     */
    public function activities(): has_many {
        return $this->has_many(activity::class, 'type_id');
    }
}
