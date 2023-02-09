<?php
/*
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package totara_hierarchy
 */

namespace totara_hierarchy\entity;

use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;

/**
 * Resource competency_scale
 *
 * @property-read int $id ID
 * @property string $name Value name
 * @property string $idnumber Unique identifier for end users
 * @property string $description Scale description
 * @property int $scaleid Scale id
 * @property int $numericscore Numeric score
 * @property int $sortorder Sortorder within the scale
 * @property int $timemodified Time modified
 * @property int $usermodified User modified
 * @property int $proficient Whether this value is counted as proficient
 * @property-read scale $scale The parent scale
 */
class scale_value extends entity {

    public const TABLE = 'comp_scale_values';

    public const UPDATED_TIMESTAMP = 'timemodified';

    public const SET_UPDATED_WHEN_CREATED = true;

    /**
     * @return belongs_to
     */
    public function scale(): belongs_to {
        return $this->belongs_to(scale::class, 'scaleid');
    }

}
