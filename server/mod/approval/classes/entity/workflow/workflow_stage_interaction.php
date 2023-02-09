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
use core\orm\entity\relations\has_one;
use mod_approval\entity\has_active_trait;

/**
 * Approval Workflow Stage Interaction entity
 *
 * Properties:
 * @property-read int $id Database record ID
 * @property int $workflow_stage_id Parent workflow_stage ID
 * @property int $action_code Application action code
 * @property-read int $created Created timestamp
 * @property-read int $updated Last modified timestamp; same as created if not modified
 *
 * Relationships:
 * @property-read workflow_stage $workflow_stage Parent workflow_stage
 * @property-read collection|workflow_stage_interaction_transition[] $conditional_transitions Collection of conditional transitions on this interaction
 * @property-read workflow_stage_interaction_transition $default_transition The default transition for this interaction
 * @property-read collection|workflow_stage_interaction_action[] $conditional_actions Collection of conditional actions on this interaction
 */
class workflow_stage_interaction extends entity {

    public const TABLE = 'approval_workflow_stage_interaction';

    public const CREATED_TIMESTAMP = 'created';

    public const UPDATED_TIMESTAMP = 'updated';

    public const SET_UPDATED_WHEN_CREATED = true;

    /**
     * Workflow_stage this interaction belongs to.
     *
     * @return belongs_to the relationship.
     */
    public function workflow_stage(): belongs_to {
        return $this->belongs_to(workflow_stage::class, 'workflow_stage_id');
    }

    /**
     * Transitions on this interaction.
     *
     * @return has_many
     */
    public function conditional_transitions(): has_many {
        return $this->has_many(workflow_stage_interaction_transition::class, 'workflow_stage_interaction_id', 'id')
            ->where('priority', '>', 1)
            ->order_by('priority', 'DESC');
    }

    /**
     * The default transition for this interaction.
     *
     * @return has_one
     */
    public function default_transition(): has_one {
        return $this->has_one(workflow_stage_interaction_transition::class, 'workflow_stage_interaction_id', 'id')
            ->where('priority', '=', 1);
    }

    /**
     * Actions on this interaction.
     *
     * @return has_many
     */
    public function conditional_actions(): has_many {
        return $this->has_many(workflow_stage_interaction_action::class, 'workflow_stage_interaction_id', 'id');
    }
}
