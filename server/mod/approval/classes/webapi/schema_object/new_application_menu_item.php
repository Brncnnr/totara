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

namespace mod_approval\webapi\schema_object;

/**
 * New application menu item schema object
 */
class new_application_menu_item {
    /** @var int */
    public $assignment_id;

    /** @var string */
    public $workflow_type;

    /** @var string|null */
    public $job_assignment;

    /** @var int */
    public $job_assignment_id;

    /**
     * Create a new new_application_menu_item schema object.
     *
     * @param int $assignment_id
     * @param string $workflow_type Workflow_type name
     * @param string|null $job_assignment Job assignment name
     * @param integer|null $job_assignment_id Job assignment ID
     */
    public function __construct(int $assignment_id, string $workflow_type, ?string $job_assignment = null, ?int $job_assignment_id = null) {
        $this->assignment_id = $assignment_id;
        $this->workflow_type = $workflow_type;
        $this->job_assignment = $job_assignment;
        $this->job_assignment_id = $job_assignment_id;
    }
}