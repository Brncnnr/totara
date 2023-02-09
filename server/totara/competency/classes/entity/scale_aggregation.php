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
 * @package totara_competency
 */

namespace totara_competency\entity;

use totara_hierarchy\entity\hierarchy_item;

// Currently only required to re-use the constants
global $CFG;
require_once($CFG->dirroot . '/totara/hierarchy/prefix/competency/lib.php');

/**
 * Class competency scale aggregation
 *
 * @property-read int $id ID
 * @property int $competency_id
 * @property string $type
 * @property int $timemodified
 */
class scale_aggregation extends hierarchy_item {

    public const TABLE = 'totara_competency_scale_aggregation';

    public const UPDATED_TIMESTAMP = 'timemodified';

    public const SET_UPDATED_WHEN_CREATED = true;

}
