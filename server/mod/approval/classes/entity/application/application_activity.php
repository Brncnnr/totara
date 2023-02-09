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

namespace mod_approval\entity\application;

use core\entity\user;
use core\orm\entity\relations\belongs_to;
use core\orm\entity\relations\has_one;
use mod_approval\entity\workflow\workflow_stage;
use mod_approval\entity\workflow\workflow_stage_approval_level;

/**
 * Approval workflow application activity entity
 *
 * Properties:
 * @property-read int $id Database record ID
 * @property int $application_id Parent application ID
 * @property int $workflow_stage_id Related workflow_stage ID
 * @property int|null $workflow_stage_approval_level_id Related workflow_stage_approval_level ID
 * @property int|null $user_id Related user/actor ID
 * @property-read int $timestamp Activity timestamp
 * @property int $activity_type Application activity type (from activity::get_type)
 * @property string $activity_info JSON blob of information about the event
 *
 * Relationships:
 * @property-read application $application Parent application
 * @property-read workflow_stage $workflow_stage Related workflow_stage
 * @property-read workflow_stage_approval_level|null $workflow_stage_approval_level Related workflow_stage_approval_level
 * @property-read user|null $user Related user entity
 */
class application_activity extends \core\orm\entity\entity {

    public const TABLE = 'approval_application_activity';

    public const CREATED_TIMESTAMP = 'timestamp';

    /**
     * Application on which this activity occurred
     *
     * @return belongs_to the relationship.
     */
    public function application(): belongs_to {
        return $this->belongs_to(application::class, 'application_id');
    }

    /**
     * Workflow_stage associated with this application_activity.
     *
     * @return has_one the relationship.
     */
    public function workflow_stage(): has_one {
        return $this->has_one(workflow_stage::class, 'id', 'workflow_stage_id');
    }

    /**
     * Workflow_stage_approval_level associated with this application_activity.
     *
     * @return has_one the relationship.
     */
    public function workflow_stage_approval_level(): has_one {
        return $this->has_one(workflow_stage_approval_level::class, 'id', 'workflow_stage_approval_level_id');
    }

    /**
     * Applicant, or actor who triggered this application_activity.
     *
     * @return has_one the relationship.
     */
    public function user(): has_one {
        return $this->has_one(user::class, 'id', 'user_id');
    }

    /**
     * Cast activity_type as int for comparisons.
     *
     * @return int
     * @internal called by the entity class
     */
    public function get_activity_type_attribute(): int {
        return (int)$this->get_attributes_raw()['activity_type'];
    }
}