<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @package core
 */

namespace core\entity;

use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;

/**
 * @property-read int $id
 * @property int $cohortid
 * @property int $userid
 * @property int $timeadded
 * @property-read cohort $cohort
 *
 * @method static cohort_member_repository repository()
 *
 * @package core
 */
class cohort_member extends entity {
    public const TABLE = 'cohort_members';

    /**
     * Get cohort relation
     *
     * @return belongs_to
     */
    public function cohort(): belongs_to {
        return $this->belongs_to(cohort::class, 'cohortid');
    }
}
