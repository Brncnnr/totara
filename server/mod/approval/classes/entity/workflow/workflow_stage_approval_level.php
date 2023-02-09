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

namespace mod_approval\entity\workflow;

use core\orm\collection;
use core\orm\entity\relations\belongs_to;
use core\orm\entity\relations\has_many;
use mod_approval\entity\application\application_action;
use mod_approval\entity\application\application_activity;
use mod_approval\entity\assignment\assignment_approver;
use mod_approval\entity\application\application;
use mod_approval\entity\has_active_trait;

/**
 * Approval Workflow Stage Approval Level entity
 *
 * Properties:
 * @property-read int $id Database record ID
 * @property int $workflow_stage_id
 * @property string $name
 * @property int $sortorder
 * @property bool $active Is this approval_level active or not?
 * @property-read int $created Created timestamp
 * @property-read int $updated Last modified timestamp; same as created if not modified
 *
 * Relationships:
 * @property-read workflow_stage $workflow_stage Parent workflow_stage
 * @property-read collection|assignment_approver[] $approvers All approvers assigned to this particular approval level
 * @property-read collection|assignment_approver[] $active_approvers Active approvers assigned to this particular approval level
 * @property-read collection|application[] $applications using this approval level
 * @property-read collection|application_action[] $application_actions using this approval level
 * @property-read collection|application_activity[] $application_activities using this approval level
 */
class workflow_stage_approval_level extends \core\orm\entity\entity {

    use has_active_trait;

    public const TABLE = 'approval_workflow_stage_approval_level';

    public const CREATED_TIMESTAMP = 'created';

    public const UPDATED_TIMESTAMP = 'updated';

    public const SET_UPDATED_WHEN_CREATED = true;

    /**
     * Workflow_stage this approval_level belongs to.
     *
     * @return belongs_to the relationship.
     */
    public function workflow_stage(): belongs_to {
        return $this->belongs_to(workflow_stage::class, 'workflow_stage_id');
    }

    /**
     * All approvers assigned to this particular approval level.
     *
     * @return has_many
     */
    public function approvers(): has_many {
        return $this->has_many(assignment_approver::class, 'workflow_stage_approval_level_id')->order_by('id');
    }

    /**
     * Active approvers assigned to this particular approval level.
     *
     * @return has_many
     */
    public function active_approvers(): has_many {
        return $this->has_many(assignment_approver::class, 'workflow_stage_approval_level_id')
            ->where('active', '=', 1)
            ->order_by('id');
    }

    /**
     * Applications using this workflow stage approval level.
     * @return has_many
     */
    public function applications(): has_many {
        return $this->has_many(application::class, 'current_approval_level_id')->order_by('id');
    }

    /**
     * Application actions using this workflow stage approval level.
     * @return has_many
     */
    public function application_actions(): has_many {
        return $this->has_many(application_action::class, 'workflow_stage_approval_level_id')->order_by('id');
    }

    /**
     * Application activities using this workflow stage approval level.
     * @return has_many
     */
    public function application_activities(): has_many {
        return $this->has_many(application_activity::class, 'workflow_stage_approval_level_id')->order_by('id');
    }
}
