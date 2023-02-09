<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @package totara_plan
 */

namespace totara_plan\entity;

use core\orm\entity\entity;

/**
 * Record of learning entity
 *
 * @property-read int $id ID
 * @property int $userid User id
 * @property int $instanceid Id of the item, i.e. courseid
 * @property int $type Type of the item, i.e. 1 = course
 */
class record_of_learning extends entity {

    /**
     * @var string
     */
    public const TABLE = 'dp_record_of_learning';

}
