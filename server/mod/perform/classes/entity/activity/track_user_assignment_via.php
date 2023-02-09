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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\entity\activity;

use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;

/**
 * This is a table to connecting assignments with user assignments
 *
 * @property int $track_assignment_id parent track record id
 * @property int $track_user_assignment_id user this assignment is about
 * @property int $created_at record creation time
 *
 * @property-read track_assignment $assignment related track assignment
 * @property-read track_user_assignment $user_assignment related track user assignment
 */
final class track_user_assignment_via extends entity {

    public const TABLE = 'perform_track_user_assignment_via';
    public const CREATED_TIMESTAMP = 'created_at';

    /**
     * Establishes the relationship with track assignment
     *
     * @return belongs_to the relationship.
     */
    public function assignment(): belongs_to {
        return $this->belongs_to(track_assignment::class, 'track_assignment_id');
    }

    /**
     * Establishes the relationship with track user assignment.
     *
     * @return belongs_to the relationship.
     */
    public function user_assignment(): belongs_to {
        return $this->belongs_to(track_user_assignment::class, 'track_user_assignment_id');
    }
}
