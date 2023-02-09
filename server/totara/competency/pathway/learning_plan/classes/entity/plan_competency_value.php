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
 * @package pathway_learning_plan
 */

namespace pathway_learning_plan\entity;

use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use totara_hierarchy\entity\scale_value;

defined('MOODLE_INTERNAL') || die();

/**
 * Class plan_competency_value
 *
 * @package pathway_learning_plan\entity
 *
 * @property-read int $id ID
 * @property int $competency_id
 * @property int $user_id
 * @property int $scale_value_id
 * @property int $date_assigned
 * @property int $positionid
 * @property int $organisationid
 * @property int $assessorid
 * @property string $assessorname
 * @property string $assessmenttype
 * @property int $timeproficient
 * @property int $manual
 *
 * @property-read scale_value $scale_value
 */
class plan_competency_value extends entity {

    public const TABLE = 'dp_plan_competency_value';

    /**
     * Get the scale value
     *
     * @return belongs_to
     */
    public function scale_value(): belongs_to {
        return $this->belongs_to(scale_value::class, 'scale_value_id');
    }

}
