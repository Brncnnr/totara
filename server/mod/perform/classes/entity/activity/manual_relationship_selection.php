<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\entity\activity;

use core\orm\collection;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use core\orm\entity\relations\has_many;
use totara_core\entity\relationship;

/**
 * Represents a role who needs to manually choose a participant role.
 *
 * @property-read int $id record id
 * @property int $activity_id parent activity
 * @property int $manual_relationship_id the participant role to be selected
 * @property int $selector_relationship_id role that selects the participant role
 * @property int $created_at record creation time
 *
 * @property-read activity $activity
 * @property-read relationship $participant_relationship
 * @property-read relationship $selector_relationship
 * @property-read collection|manual_relationship_selection_progress[] $progress
 *
 * @method static manual_relationship_selection_repository repository()
 */

class manual_relationship_selection extends entity {
    public const TABLE = 'perform_manual_relation_selection';
    public const CREATED_TIMESTAMP = 'created_at';

    /**
     * Returns the parent activity.
     *
     * @return belongs_to the relationship.
     */
    public function activity(): belongs_to {
        return $this->belongs_to(activity::class, 'activity_id');
    }

    /**
     * Returns the participant role to be selected.
     *
     * @return belongs_to the relationship.
     */
    public function participant_relationship(): belongs_to {
        return $this->belongs_to(relationship::class, 'manual_relationship_id');
    }

    /**
     * Returns the assigned user who will participate in the activity.
     *
     * @return belongs_to the relationship.
     */
    public function selector_relationship(): belongs_to {
        return $this->belongs_to(relationship::class, 'selector_relationship_id');
    }

    /**
     * Returns the related selection progress instances.
     *
     * @return has_many the relationship.
     */
    public function progress(): has_many {
        return $this->has_many(manual_relationship_selection_progress::class, 'manual_relation_selection_id');
    }
}
