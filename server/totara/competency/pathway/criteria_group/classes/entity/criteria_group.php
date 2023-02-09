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
use core\orm\entity\relations\belongs_to;
use core\orm\entity\relations\has_many;
use totara_hierarchy\entity\scale_value;

/**
 * Pathway per competency
 *
 * @property int $scale_value_id
 *
 * @property-read array|criteria_group_criterion[] $criterions
 * @property-read scale_value $scale_value
 */
class criteria_group extends entity {

    public const TABLE = 'pathway_criteria_group';

    /**
     * Get the criteria group criterions
     *
     * @return has_many
     */
    public function criterions(): has_many {
        return $this->has_many(criteria_group_criterion::class, 'criteria_group_id');
    }

    public function scale_value(): belongs_to {
        return $this->belongs_to(scale_value::class, 'scale_value_id');
    }

}
