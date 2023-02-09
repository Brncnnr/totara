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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_criteria
 */

namespace totara_criteria\entity;

use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;

/**
 * One record for a criteria metadata
 *
 * @property int $criterion_id
 * @property string $metakey
 * @property string $metavalue
 *
 * @property-read criterion $criterion
 */
class criteria_metadata extends entity {

    public const TABLE = 'totara_criteria_metadata';

    public function criterion(): belongs_to {
        return $this->belongs_to(criterion::class, 'criterion_id');
    }

}
