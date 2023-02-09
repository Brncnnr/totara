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
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\testing;

/**
 * Class application_activity_generator_object
 *
 * Provides a structured interface for passing properties to the application activity generator.
 *
 * @package mod_approval\testing
 */
final class application_activity_generator_object {
    public $application_id;
    public $workflow_stage_id;
    public $workflow_stage_approval_level_id;
    public $user_id;
    public $activity_type;
    public $activity_info;

    /**
     * Assignment_generator_object constructor, captures required properties.
     *
     * @param int $application_id
     * @param int $workflow_stage_id
     * @param int $workflow_stage_approval_level_id
     * @param int $user_id
     * @param int $activity_type
     * @param string|null $activity_info
     */
    public function __construct(int $application_id, int $workflow_stage_id, int $workflow_stage_approval_level_id,  int $user_id, int $activity_type, ?string $activity_info) {
        $this->application_id = $application_id;
        $this->workflow_stage_id = $workflow_stage_id;
        $this->workflow_stage_approval_level_id = $workflow_stage_approval_level_id;
        $this->user_id = $user_id;
        $this->activity_type = $activity_type;
        $this->activity_info = $activity_info ?: '{}';
    }
}
