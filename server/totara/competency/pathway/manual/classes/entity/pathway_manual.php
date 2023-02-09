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
 * @package pathway_manual;
 */

namespace pathway_manual\entity;

use core\orm\entity\entity;
use core\orm\entity\relations\has_many;
use core\orm\entity\relations\has_one;
use totara_competency\entity\pathway;

/**
 * @property-read int $id ID
 * @property int $aggregation_method
 * @property int $aggregation_params
 *
 * @property-read pathway $pathway
 * @property-read role[] $roles
 */
class pathway_manual extends entity {

    public const TABLE = 'pathway_manual';

    /**
     * Associated competency pathway entry.
     *
     * @return has_one
     */
    public function pathway(): has_one {
        return $this->has_one(pathway::class, 'path_instance_id');
    }

    /**
     * Roles for this manual rating pathway.
     *
     * @return has_many
     */
    public function roles(): has_many {
        return $this->has_many(role::class, 'path_manual_id');
    }

}
