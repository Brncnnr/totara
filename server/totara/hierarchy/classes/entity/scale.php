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

use core\orm\collection;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use core\orm\entity\relations\has_many;

/**
 * Resource competency_scale
 *
 * @property-read int $id ID
 * @property string $name Scale name
 * @property string $description Scale description
 * @property int $timemodified Time modified
 * @property int $usermodified User modified
 * @property int $defaultid Default id
 * @property int $minproficiencyid
 *
 * @property-read scale_value $default_value
 * @property-read scale_value $min_proficient_value
 * @property-read collection|scale_value[] $values Values for this scale, sorted from lowest to highest value
 * @property-read collection|scale_value[] $sorted_values_high_to_low Values for this scale, sorted from highest value to lowest
 */
class scale extends entity {

    public const TABLE = 'comp_scale';

    public const UPDATED_TIMESTAMP = 'timemodified';

    public const SET_UPDATED_WHEN_CREATED = true;

    /**
     * Values for this scale, sorted from lowest to highest value
     *
     * @return has_many
     */
    public function values(): has_many {
        return $this->has_many(scale_value::class, 'scaleid')
            ->order_by('sortorder', 'desc');
    }

    /**
     * Values for this scale, sorted from highest value to lowest
     *
     * @return has_many
     */
    public function sorted_values_high_to_low(): has_many {
        return $this->has_many(scale_value::class, 'scaleid')
            ->order_by('sortorder', 'asc');
    }

    /**
     * Default value for this scale
     *
     * @return belongs_to
     */
    public function default_value(): belongs_to {
        return $this->belongs_to(scale_value::class, 'defaultid');
    }

    /**
     * Min proficient value for this scale
     *
     * @return belongs_to
     */
    public function min_proficient_value(): belongs_to {
        return $this->belongs_to(scale_value::class, 'minproficiencyid');
    }
}
