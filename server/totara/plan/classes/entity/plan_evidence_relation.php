<?php
/**
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
 * @package totara_plan
 */

namespace totara_plan\entity;

use core\orm\entity\entity;

/**
 * Learning plan to evidence item relation
 *
 * @property-read int $id ID
 * @property int $evidenceid Evidence item ID
 * @property int $planid Learning plan ID
 * @property int $itemid Learning plan item ID
 * @property string $component Learning plan component
 *
 * @package totara_plan\entity
 */
class plan_evidence_relation extends entity {

    /**
     * @var string
     */
    public const TABLE = 'dp_plan_evidence_relation';

}
