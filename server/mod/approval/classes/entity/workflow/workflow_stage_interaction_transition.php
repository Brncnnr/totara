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

use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;

/**
 * Approval Workflow Stage Interaction Transition entity
 *
 * Properties:
 * @property-read int $id Database record ID
 * @property int $workflow_stage_interaction_id Parent workflow_stage_interaction ID
 * @property string|null $condition_key Form field_key to be used for checking condition
 * @property string|null $condition_data JSON-encoded parameters for evaluating condition
 * @property string $transition Stage id to transition to, or classname of transition to use to resolve new state
 * @property int $priority Priority, determines which of several possible transitions to execute (highest priority wins)
 * @property-read int $created Created timestamp
 * @property-read int $updated Last modified timestamp; same as created if not modified
 *
 * Relationships:
 * @property-read workflow_stage_interaction $workflow_stage_interaction Parent workflow_stage_interaction
 */
class workflow_stage_interaction_transition extends entity {

    public const TABLE = 'approval_workflow_stage_interaction_transition';

    public const CREATED_TIMESTAMP = 'created';

    public const UPDATED_TIMESTAMP = 'updated';

    public const SET_UPDATED_WHEN_CREATED = true;

    /**
     * Workflow_stage_interaction this transition belongs to.
     *
     * @return belongs_to the relationship.
     */
    public function workflow_stage_interaction(): belongs_to {
        return $this->belongs_to(workflow_stage_interaction::class, 'workflow_stage_interaction_id');
    }
}
