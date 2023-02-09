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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_criteria
 */

namespace totara_criteria\entity;

use core\orm\collection;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use core\orm\entity\relations\has_many;

/**
 * One users record for a criteria item
 *
 * @property int $criterion_id
 * @property string $item_type
 * @property int $item_id
 *
 * @property-read criterion $criterion
 * @property-read collection|criteria_item_record[] $item_records
 */
class criteria_item extends entity {

    public const TABLE = 'totara_criteria_item';

    public function criterion(): belongs_to {
        return $this->belongs_to(criterion::class, 'criterion_id');
    }

    public function item_records(): has_many {
        return $this->has_many(criteria_item_record::class, 'criterion_item_id');
    }
}
