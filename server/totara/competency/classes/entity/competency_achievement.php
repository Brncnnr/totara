<?php
/*
 * This file is part of Totara Perform
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\entity;

use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use core\orm\entity\relations\has_many_through;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\scale_value;

/**
 * Class competency_achievement
 *
 * @property-read int $id ID
 * @property int $competency_id
 * @property int $user_id
 * @property int $assignment_id
 * @property int $scale_value_id
 * @property int $proficient
 * @property int $status
 * @property int $time_created
 * @property int $time_status
 * @property int $time_proficient
 * @property int $time_scale_value
 * @property int $last_aggregated
 *
 * @property-read competency $competency
 * @property-read assignment $assignment
 * @property-read scale_value $value
 * @property-read pathway_achievement[] $achieved_via
 *
 * @method static competency_achievement_repository repository()
 */
class competency_achievement extends entity {

    public const TABLE = 'totara_competency_achievement';

    /** @var int Status when this is the latest record for an active assignment */
    public const ACTIVE_ASSIGNMENT = 0;

    /** @var int Status when this is the latest record for an archived assignment */
    public const ARCHIVED_ASSIGNMENT = 1;

    /** @var int Status when this record is not the latest for an assignment (either active or archived) */
    public const SUPERSEDED = 2;

    /**
     * Competency
     *
     * @return belongs_to
     */
    public function competency(): belongs_to {
        return $this->belongs_to(competency::class, 'competency_id');
    }

    /**
     * Assignment
     *
     * @return belongs_to
     */
    public function assignment(): belongs_to {
        return $this->belongs_to(assignment::class, 'assignment_id');
    }

    /**
     * Scale value
     *
     * @return belongs_to
     */
    public function value(): belongs_to {
        return $this->belongs_to(scale_value::class, 'scale_value_id');
    }

    /**
     * Get the pathway achievements that led to this competency achievement initially being created.
     *
     * @return has_many_through
     */
    public function achieved_via(): has_many_through {
        return $this->has_many_through(
            achievement_via::class,
            pathway_achievement::class,
            'id',
            'comp_achievement_id',
            'pathway_achievement_id',
            'id'
        );
    }

}
