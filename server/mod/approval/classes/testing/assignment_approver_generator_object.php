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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\testing;

/**
 * Class assignment_approver_generator_object
 *
 * Provides a structured interface for passing properties to the assignment_approver generator.
 *
 * @package mod_approval\testing
 */
final class assignment_approver_generator_object {
    public $approval_id;
    public $workflow_stage_approval_level_id;
    public $type;
    public $identifier;
    public $active = true;

    /**
     * Assignment_approval generator object constructor, captures required properties.
     *
     * @param int $approval_id
     * @param int $workflow_stage_approval_level_id;
     * @param int $type
     * @param int $identifier
     */
    public function __construct(int $approval_id, int $workflow_stage_approval_level_id, int $type, int $identifier) {
        $this->approval_id = $approval_id;
        $this->workflow_stage_approval_level_id = $workflow_stage_approval_level_id;
        $this->type = $type;
        $this->identifier = $identifier;
    }
}