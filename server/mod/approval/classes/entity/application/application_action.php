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
use core\orm\entity\relations\has_one;
use mod_approval\entity\workflow\workflow_stage;
use mod_approval\entity\workflow\workflow_stage_approval_level;

/**
 * Approval application action entity
 *
 * Properties:
 * @property-read int $id Database record ID
 * @property int $application_id Parent application ID
 * @property int $user_id Applicant or approver id
 * @property int $workflow_stage_id Related workflow_stage ID
 * @property int|null $workflow_stage_approval_level_id Related workflow_stage_approval_level ID
 * @property int $code Action taken (approved, rejected, withdrawn)
 * @property-read int $created Creation timestamp
 * @property bool $superseded Whether this action has been superseded
 * @property string $form_data JSON blob of form field state at the time of the approval
 *
 * Relationships:
 * @property-read application $application Parent application
 * @property-read user $user Related user entity
 * @property-read workflow_stage $workflow_stage Related workflow stage
 * @property-read workflow_stage_approval_level|null $workflow_stage_approval_level Related approval level
 *
 */
class application_action extends entity {

    public const TABLE = 'approval_application_action';

    public const CREATED_TIMESTAMP = 'created';

    /**
     * Application on which this activity occurred
     *
     * @return belongs_to the relationship.
     */
    public function application(): belongs_to {
        return $this->belongs_to(application::class, 'application_id');
    }

    /**
     * Approver
     *
     * @return has_one the relationship.
     */
    public function user(): has_one {
        return $this->has_one(user::class, 'id', 'user_id');
    }

    /**
     * Workflow_stage_approval_level associated with this application_action.
     *
     * @return has_one the relationship.
     */
    public function workflow_stage_approval_level(): has_one {
        return $this->has_one(workflow_stage_approval_level::class, 'id', 'workflow_stage_approval_level_id');
    }

    /**
     * Workflow_stage associated with this application_action.
     *
     * @return has_one the relationship.
     */
    public function workflow_stage(): has_one {
        return $this->has_one(workflow_stage::class, 'id', 'workflow_stage_id');
    }

    /**
     * Cast code as int for comparisons.
     *
     * @return int
     * @internal called by the entity class
     */
    public function get_code_attribute(): int {
        return (int)$this->get_attributes_raw()['code'];
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
