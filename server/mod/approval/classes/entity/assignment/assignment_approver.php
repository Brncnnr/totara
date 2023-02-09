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
 * @author David Curry <david.curry@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\entity\assignment;

use core\orm\collection;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use core\orm\entity\relations\has_many;
use core\orm\entity\relations\has_one;
use mod_approval\entity\has_active_trait;
use mod_approval\entity\workflow\workflow_stage_approval_level;

/**
 * Approval workflow approver entity
 *
 * Properties:
 * @property-read int $id Database record ID
 * @property int $approval_id Parent assignment (mod_approval) ID
 * @property int $workflow_stage_approval_level_id Approval_level ID for this approver
 * @property int $type Approver type code (relationship|user)
 * @property int $identifier Database ID of assignee record
 * @property bool $active Is this approver active or not?
 * @property-read int $created Created timestamp
 * @property-read int $updated Last-modified timestamp; same as created if not modified
 * @property null|int $ancestor_id Database record that this approver instance is inherited from
 *
 * Relationships:
 * @property-read assignment $assignment Parent assignment
 * @property-read workflow_stage_approval_level $workflow_stage_approval_level Workflow_stage_approval_level for this assignment
 * @property-read null|assignment_approver $ancestor Approver entity (if any) that this approver is inherited from
 * @property-read collection|assignment_approver[] $descendants Descendant entities that inherit from this approver on other assignments
 *
 * Functions:
 * @method static assignment_approver_repository repository()
 *
 * @package mod_approval\entity
 */
class assignment_approver extends entity {

    use has_active_trait;

    public const TABLE = 'approval_approver';

    public const CREATED_TIMESTAMP = 'created';

    public const UPDATED_TIMESTAMP = 'updated';

    public const SET_UPDATED_WHEN_CREATED = true;

    /**
     * Parent assignment (approval activity).
     *
     * @return belongs_to the relationship.
     */
    public function assignment(): belongs_to {
        return $this->belongs_to(assignment::class, 'approval_id', 'id');
    }

    /**
     * Workflow stage approval level to which this approver is assigned.
     *
     * @return has_one the relationship.
     */
    public function workflow_stage_approval_level(): has_one {
        return $this->has_one(workflow_stage_approval_level::class, 'id', 'workflow_stage_approval_level_id');
    }

    /**
     * Ancestor approver if inherited.
     *
     * @return belongs_to the relationship.
     */
    public function ancestor(): belongs_to {
        return $this->belongs_to(assignment_approver::class, 'ancestor_id', 'id')
            ->where('active', '=', true);
    }

    /**
     * All inherited descendants of this approver.
     *
     * @return has_many
     */
    public function descendants(): has_many {
        return $this->has_many(assignment_approver::class, 'ancestor_id', 'id')
            ->where('active', '=', true)
            ->order_by('id');
    }

    /**
     * Cast type as int for comparisons.
     *
     * @return int
     * @internal called by the entity class
     */
    public function get_type_attribute(): int {
        return (int)$this->get_attributes_raw()['type'];
    }
}
