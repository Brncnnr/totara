<?php
/*
 * This file is part of Totara Learn
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

namespace pathway_criteria_group\entity;


use core\orm\entity\entity;
use core\orm\entity\relations\has_one;
use totara_criteria\entity\criterion;

/**
 * Pathway per competency criterion
 *
 * @property int $criteria_group_id
 * @property string $criterion_type
 * @property int $criterion_id
 * @property-read criterion $criterion
 */
class criteria_group_criterion extends entity {

    public const TABLE = 'pathway_criteria_group_criterion';

    /**
     * Get the criterion this row is related to
     *
     * @return has_one
     */
    public function criterion(): has_one {
        return $this->has_one(criterion::class, 'id', 'criterion_id');
    }

}
