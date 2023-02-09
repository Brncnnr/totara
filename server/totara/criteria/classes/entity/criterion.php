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
 * @package totara_criteria
 */

namespace totara_criteria\entity;


use core\orm\collection;
use core\orm\entity\entity;
use core\orm\entity\relations\has_many;

/**
 * One criterion
 *
 * @property string $plugin_type
 * @property string $idnumber
 * @property int $aggregation_method
 * @property string $aggregation_params
 * @property int $criterion_modified
 * @property int $last_evaluated
 * @property int $valid
 *
 * @property-read collection|criterion_item[] $items
 * @property-read collection|criteria_metadata[] $metadata
 */
class criterion extends entity {

    public const TABLE = 'totara_criteria';

    public function items(): has_many {
        return $this->has_many(criterion_item::class, 'criterion_id');
    }
    public function metadata(): has_many {
        return $this->has_many(criteria_metadata::class, 'criterion_id');
    }

}
