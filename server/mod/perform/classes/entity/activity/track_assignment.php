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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\entity\activity;


use core\collection;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use core\orm\entity\relations\has_many_through;

/**
 * Represents an activity track assignment record in the repository.
 *
 * @property-read int $id record id
 * @property int $track_id parent track record id
 * @property int $type assignment type enum
 * @property int $user_group_type grouping type
 * @property int $user_group_id grouping record id
 * @property int $created_by userid who did assignment
 * @property int $created_at record creation time
 * @property int $updated_at record modification time
 * @property int $expand if the assignment should be expanded on the next expand
 * @property-read collection|track_user_assignment[] $user_assignments All user assignments linked to this particular assignment
 * @property-read track $track The track this assignment belongs to
 * @method static track_assignment_repository repository()
 */
class track_assignment extends entity {
    public const TABLE = 'perform_track_assignment';
    public const CREATED_TIMESTAMP = 'created_at';
    public const UPDATED_TIMESTAMP = 'updated_at';

    /**
     * Group id getter. Needed because the read from the DB _returns strings and
     * the ORM does not convert the value_.
     *
     * TBD: to remove once the base_entity handles this.
     *
     * @param int|null $value incoming id.
     *
     * @return int the converted value.
     */
    public function get_user_group_id_attribute(?int $value = null): int {
        return (int)$value;
    }

    /**
     * Establishes the relationship with track entities.
     *
     * @return belongs_to the relationship.
     */
    public function track(): belongs_to {
        return $this->belongs_to(track::class, 'track_id');
    }

    /**
     * Relation to get all connected user assignments for this particular assignment
     *
     * @return has_many_through
     */
    public function user_assignments(): has_many_through {
        return $this->has_many_through(
            track_user_assignment_via::class,
            track_user_assignment::class,
            'id',
            'track_assignment_id',
            'track_user_assignment_id',
            'id'
        );
    }
}
