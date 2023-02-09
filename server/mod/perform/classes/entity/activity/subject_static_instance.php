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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\entity\activity;

use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;

/**
 * Static subject static instance entity
 *
 * @property int $subject_instance_id
 * @property int $job_assignment_id
 * @property int $manager_job_assignment_id
 * @property int $position_id
 * @property int $organisation_id
 * @property int $created_at
 * @property int $updated_at
 *
 * @method static subject_static_instance_repository repository
 *
 * @package mod_perform\entity
 */
class subject_static_instance extends entity {

    public const TABLE = 'perform_subject_static_instance';
    public const CREATED_TIMESTAMP = 'created_at';
    public const UPDATED_TIMESTAMP = 'updated_at';

    /**
     * Relationship with subject_instance entities.
     *
     * @return belongs_to
     */
    public function subject_instance(): belongs_to {
        return $this->belongs_to(subject_instance::class, 'subject_instance_id');
    }

}