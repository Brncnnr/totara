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

namespace mod_approval\entity\application;

use core\entity\user;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use totara_job\entity\job_assignment;
use core\orm\collection;
use core\orm\entity\relations\has_many;
use core\orm\entity\relations\has_one;
use mod_approval\entity\assignment\assignment;
use mod_approval\entity\form\form_version;
use mod_approval\entity\workflow\workflow_stage;
use mod_approval\entity\workflow\workflow_stage_approval_level;
use mod_approval\entity\workflow\workflow_version;

/**
 * Approval application entity
 *
 * Properties:
 * @property-read int $id Database record ID
 * @property string $title Human-readable name
 * @property string $id_number Auto-generated reference number
 * @property int $user_id Applicant id
 * @property int|null $job_assignment_id Applicant job assignment
 * @property int $workflow_version_id Related workflow_version ID
 * @property int $form_version_id Related form_version ID
 * @property int $approval_id Related assignment ID (mod_approval)
 * @property int $creator_id User who initially create this application
 * @property int $owner_id User who owns the application, initially the creator
 * @property int $current_stage_id ID of application's current workflow_stage
 * @property int $is_draft enum indicating whether the application is currently a draft (only visible to those with draft caps)
 * @property int|null $current_approval_level_id ID of application's current workflow_stage_approval_level
 * @property-read int $created Creation timestamp
 * @property-read int $updated Last-modified timestamp; same as created if not modified
 * @property int|null $submitted Timestamp that the application was submitted, or null
 * @property int|null $submitter_id User who submitted the application, or null
 * @property int|null $completed Application completed timestamp, or null
 *
 * Relationships:
 * @property-read user $user Related user entity
 * @property-read job_assignment|null $job_assignment Related job assignment entity
 * @property-read workflow_version $workflow_version Related workflow_version
 * @property-read form_version $form_version Related form_version
 * @property-read assignment $assignment Parent assignment entity
 * @property-read user $creator Related user entity
 * @property-read user $owner Related user entity
 * @property-read user|null $submitter Related user entity
 * @property-read collection|application_action[] $actions Related application_action entity
 * @property-read collection|application_submission[] $submissions Related application_submission entity
 * @property-read collection|application_activity[] $activities Related application_activity entity
 * @property-read workflow_stage $current_stage Related workflow_stage
 * @property-read workflow_stage_approval_level|null $current_approval_level Related workflow_approval_level
 *
 */
class application extends entity {

    public const TABLE = 'approval_application';

    public const CREATED_TIMESTAMP = 'created';

    public const UPDATED_TIMESTAMP = 'updated';

    public const SET_UPDATED_WHEN_CREATED = true;

    /**
     * Applicant
     *
     * @return has_one the relationship.
     */
    public function user(): has_one {
        return $this->has_one(user::class, 'id', 'user_id');
    }

    /**
     * Applicant job assignment
     *
     * @return has_one the relationship.
     */
    public function job_assignment(): has_one {
        return $this->has_one(job_assignment::class, 'id', 'job_assignment_id');
    }

    /**
     * Workflow version associated with application
     *
     * @return has_one the relationship.
     */
    public function workflow_version(): has_one {
        return $this->has_one(workflow_version::class, 'id', 'workflow_version_id');
    }

    /**
     *  Form version associated with application
     *
     * @return has_one the relationship.
     */
    public function form_version(): has_one {
        return $this->has_one(form_version::class, 'id', 'form_version_id');
    }

    /**
     *  Assignment associated with application
     *
     * @return belongs_to the relationship.
     */
    public function assignment(): belongs_to {
        return $this->belongs_to(assignment::class, 'approval_id');
    }

    /**
     * User created the application
     *
     * @return has_one the relationship.
     */
    public function creator(): has_one {
        return $this->has_one(user::class, 'id', 'creator_id');
    }

    /**
     * The owner of the application, initially the user who created it
     *
     * @return has_one the relationship.
     */
    public function owner(): has_one {
        return $this->has_one(user::class, 'id', 'owner_id');
    }

    /**
     *  User submitted the application
     *
     * @return has_one the relationship.
     */
    public function submitter(): has_one {
        return $this->has_one(user::class, 'id', 'submitter_id');
    }

    /**
     *  Actions associated with application
     *
     * @return has_many the relationship.
     */
    public function actions(): has_many {
        return $this->has_many(application_action::class, 'application_id')->order_by('id');
    }

    /**
     * Submissions associated with application
     *
     * @return has_many the relationship.
     */
    public function submissions(): has_many {
        return $this->has_many(application_submission::class, 'application_id')->order_by('id');
    }

    /**
     * Activities associated with application
     *
     * @return has_many the relationship.
     */
    public function activities(): has_many {
        return $this->has_many(application_activity::class, 'application_id')->order_by('id');
    }

    /**
     * Approval workflow stage
     *
     * @return has_one the relationship.
     */
    public function current_stage(): has_one {
        return $this->has_one(workflow_stage::class, 'id', 'current_stage_id');
    }

    /**
     * Workflow_stage_approval_level associated with this application_activity.
     *
     * @return has_one the relationship.
     */
    public function current_approval_level(): has_one {
        return $this->has_one(workflow_stage_approval_level::class, 'id', 'current_approval_level_id');
    }
}
