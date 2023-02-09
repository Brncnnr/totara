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
use core\orm\entity\relations\belongs_to;
use core\orm\entity\relations\has_one;
use mod_approval\entity\workflow\workflow_stage;

/**
 * Approval application submission entity
 *
 * Properties:
 * @property-read int $id Database record ID
 * @property int $application_id Parent application ID
 * @property int $user_id user who created or submitted the submission
 * @property int $workflow_stage_id Related workflow_stage ID
 * @property-read int $created First saved timestamp
 * @property-read int $updated Last saved timestamp
 * @property int|null $submitted Last submitted timestamp, or null
 * @property bool $superseded Whether this submission has been superseded
 * @property string $form_data JSON blob of form field state at the time of the submission
 *
 * Relationships:
 * @property-read application $application Parent application
 * @property-read user $user Related user entity
 * @property-read workflow_stage $workflow_stage Related workflow_stage
 *
 * Functions:
 * @method static application_submission_repository repository()
 */
class application_submission extends \core\orm\entity\entity {

    public const TABLE = 'approval_application_submission';

    public const CREATED_TIMESTAMP = 'created';

    public const UPDATED_TIMESTAMP = 'updated';

    public const SET_UPDATED_WHEN_CREATED = true;

    /**
     * Application on which this activity occurred
     *
     * @return belongs_to the relationship.
     */
    public function application(): belongs_to {
        return $this->belongs_to(application::class, 'application_id');
    }

    /**
     * The user who created or submitted the submission.
     *
     * @return has_one the relationship.
     */
    public function user(): has_one {
        return $this->has_one(user::class, 'id', 'user_id');
    }

    /**
     * Approval workflow stage
     *
     * @return has_one the relationship.
     */
    public function workflow_stage(): has_one {
        return $this->has_one(workflow_stage::class, 'id', 'workflow_stage_id');
    }

    /**
     * Bool casting.
     *
     * @return bool
     */
    public function get_superseded_attribute(): bool {
        return $this->get_attributes_raw()['superseded'] ?? false;
    }

    /**
     * Bool casting.
     *
     * @param bool $value
     * @return bool
     */
    public function set_superseded_attribute(bool $value): bool {
        return (bool) $this->set_attribute_raw('superseded', $value);
    }
}
