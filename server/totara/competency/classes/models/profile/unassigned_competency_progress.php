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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\models\profile;

use core\orm\collection;
use totara_hierarchy\entity\competency;

/**
 * This is an adapter class/null object for competency progress with no assignments.
 *
 * i.e. a competency exists but it has not been assigned at all.
 */
class unassigned_competency_progress extends competency_progress {

    /**
     * competency_progress constructor.
     *
     * @param competency $competency
     */
    public function __construct(competency $competency) {
        $this->competency = $competency;
        $this->assignments = new collection();
    }

}