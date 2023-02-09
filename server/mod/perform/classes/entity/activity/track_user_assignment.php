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

use core\entity\user;
use core\orm\collection;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use core\orm\entity\relations\has_many_through;

/**
 * Represents an activity track assignment record in the repository.
 *
 * @property-read int $id record id
 * @property int $track_id parent track record id
 * @property int $subject_user_id user this assignment is about
 * @property int $period_start_date start date of this assignment
 * @property int $period_end_date end date of this assignment
 * @property bool $deleted is this record deleted?
 * @property int|null $job_assignment_id the job assignment this assignment is linked to,
 *                                       can be null for when it is instead being driven by a user id
 * @property int $created_at record creation time
 * @property int $updated_at record modification time
 *
 * @property-read string $key a key which is the combination of user id and job assighment id
 * @property-read user $subject_user subject user relation entity
 * @property-read track $track the track this user assignment belongs to
 * @property-read collection|track_assignment[] $assignments All assignments this user assignments is linked to
 *
 * @method static track_user_assignment_repository repository()
 */
final class track_user_assignment extends entity {

    public const TABLE = 'perform_track_user_assignment';
    public const CREATED_TIMESTAMP = 'created_at';
    public const UPDATED_TIMESTAMP = 'updated_at';
    public const SET_UPDATED_WHEN_CREATED = false;

    /**
     * Establishes the relationship with track entities.
     *
     * @return belongs_to the relationship.
     */
    public function track(): belongs_to {
        return $this->belongs_to(track::class, 'track_id');
    }

    /**
     * Establishes the relationship with user entities.
     *
     * @return belongs_to the relationship.
     */
    public function subject_user(): belongs_to {
        return $this->belongs_to(user::class, 'subject_user_id');
    }

    /**
     * Relation to get all connected assignments this particular user assignment belongs to
     *
     * @return has_many_through
     */
    public function assignments(): has_many_through {
        return $this->has_many_through(
            track_user_assignment_via::class,
            track_assignment::class,
            'id',
            'track_user_assignment_id',
            'track_assignment_id',
            'id'
        );
    }

    /**
     * Returns whether this user assignment is linked to any assignment
     *
     * @return bool
     */
    public function is_linked_to_any_assignment(): bool {
        return $this->assignments()->exists();
    }

    /**
     * Get a key to identify this user assignments and faster search
     *
     * @return string
     */
    public function get_key_attribute(): string {
        return $this->subject_user_id.'-'.$this->job_assignment_id;
    }

}
