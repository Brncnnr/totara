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

use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;

/**
 * One users record for a criteria item
 *
 * @property int $user_id
 * @property int $criterion_item_id
 * @property int $criterion_met true/false
 * @property int $timeevaluated
 * @property int $timeachieved
 *
 * @property-read criteria_item $item
 */
class criteria_item_record extends entity {

    public const TABLE = 'totara_criteria_item_record';

    public function item(): belongs_to {
        return $this->belongs_to(criteria_item::class, 'criterion_item_id');
    }

}
