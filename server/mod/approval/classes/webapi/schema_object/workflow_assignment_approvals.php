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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\webapi\schema_object;

use mod_approval\model\assignment\assignment;
use mod_approval\model\assignment\assignment_approval_level;

/**
 * Class workflow_assignment
 */
class workflow_assignment_approvals {

    /**
     * @var assignment
     */
    public $assignment;

    /**
     * @var assignment_approval_level[]
     */
    public $assignment_approval_levels;

    /**
     * @param assignment $assignment
     * @param assignment_approval_level[] $assignment_approval_levels
     */
    public function __construct(assignment $assignment, array $assignment_approval_levels) {
        $this->assignment = $assignment;
        $this->assignment_approval_levels = $assignment_approval_levels;
    }
}
