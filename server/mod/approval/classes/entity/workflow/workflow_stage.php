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
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use core\orm\entity\relations\has_many;
use mod_approval\entity\application\application;
use mod_approval\entity\application\application_activity;
use mod_approval\entity\application\application_submission;
use mod_approval\entity\has_active_trait;

/**
 * Approval Workflow Stage entity
 *
 * Properties:
 * @property-read int $id Database record ID
 * @property int $workflow_version_id Parent workflow_version ID
 * @property string $name Human-readable name
 * @property int $type_code the type identifier of this stage
 * @property int $sortorder Sort order of this stage
 * @property bool $active Is this stage active or not?
 * @property-read int $created Created timestamp
 * @property-read int $updated Last modified timestamp; same as created if not modified
 *
 * Relationships:
 * @property-read workflow_version $workflow_version Parent workflow_version
 * @property-read collection|workflow_stage_approval_level[] $approval_levels Collection of all approval levels at this stage
 * @property-read collection|workflow_stage_formview[] $formviews Collection of all formview definitions at this stage
 * @property-read collection|workflow_stage_interaction[] $interactions Collection of all interaction entities at this stage
 * @property-read collection|workflow_stage_approval_level[] $active_approval_levels Sorted collection of active approval levels at this stage
 * @property-read collection|workflow_stage_formview[] $active_formviews Sorted collection of active formview definitions at this stage
 * @property-read collection|workflow_stage_interaction[] $active_interactions Collection of active interaction entities at this stage
 * @property-read collection|application[] $applications Collection of applications using this stage
 * @property-read collection|application_activity[] $activities Collection of activities using this stage
 * @property-read collection|application_submission[] $submissions Collection of submissions for this stage
 */
class workflow_stage extends entity {

    use has_active_trait;

    public const TABLE = 'approval_workflow_stage';

    public const CREATED_TIMESTAMP = 'created';

    public const UPDATED_TIMESTAMP = 'updated';

    public const SET_UPDATED_WHEN_CREATED = true;

    /**
     * Workflow_version this stage belongs to.
     *
     * @return belongs_to the relationship.
     */
    public function workflow_version(): belongs_to {
        return $this->belongs_to(workflow_version::class, 'workflow_version_id');
    }

    /**
     * Approval levels in this stage.
     *
     * @return has_many
     */
    public function approval_levels(): has_many {
        return $this->has_many(workflow_stage_approval_level::class, 'workflow_stage_id')->order_by('sortorder')->order_by('id');
    }

    /**
     * Formview definitions in this stage.
     *
     * @return has_many
     */
    public function formviews(): has_many {
        return $this->has_many(workflow_stage_formview::class, 'workflow_stage_id')->order_by('id');
    }

    /**
     * Workflow interactions in this stage.
     *
     * @return has_many
     */
    public function interactions(): has_many {
        return $this->has_many(workflow_stage_interaction::class, 'workflow_stage_id')->order_by('id');
    }

    /**
     * Active approval levels in this stage, sorted by sortorder
     *
     * @return has_many
     */
    public function active_approval_levels(): has_many {
        return $this->has_many(workflow_stage_approval_level::class, 'workflow_stage_id')
            ->where('active', '=', 1)
            ->order_by('sortorder');
    }

    /**
     * Active formview definitions in this stage, sorted by id.
     *
     * @return has_many
     */
    public function active_formviews(): has_many {
        return $this->has_many(workflow_stage_formview::class, 'workflow_stage_id')
            ->where('active', '=', 1)
            ->order_by('id');
    }

    /**
     * Active workflow interactions in this stage.
     *
     * @return has_many
     */
    public function active_interactions(): has_many {
        return $this->has_many(workflow_stage_interaction::class, 'workflow_stage_id')
            ->where('active', '=', 1)
            ->order_by('id');
    }

    /**
     * Applications currently on this stage
     * @return has_many
     */
    public function applications(): has_many {
        return $this->has_many(application::class, 'current_stage_id')->order_by('id');
    }

    /**
     * Application activities associated with this stage
     * @return has_many
     */
    public function activities(): has_many {
        return $this->has_many(application_activity::class, 'workflow_stage_id')->order_by('id');
    }

    /**
     * Application submissions for this stage
     * @return has_many
     */
    public function submissions(): has_many {
        return $this->has_many(application_submission::class, 'workflow_stage_id')->order_by('id');
    }
}
