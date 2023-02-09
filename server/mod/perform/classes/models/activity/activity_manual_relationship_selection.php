<?php
/**
 *
 * This file is part of Totara LMS
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\activity;

use core\orm\entity\model;
use mod_perform\entity\activity\manual_relationship_selection;
use totara_core\relationship\relationship;

/**
 * Class activity_manual_relationship_selection
 *
 * Represents a single manual relationship.
 *
 * @property-read int $id ID
 * @property-read int $activity_id
 * @property-read int $manual_relationship_id
 * @property-read int $selector_relationship_id
 * @property-read activity $activity
 * @property-read relationship $manual_relationship
 * @property-read relationship $selector_relationship
 */
class activity_manual_relationship_selection extends model {

    protected $entity_attribute_whitelist = [
        'id',
        'activity_id',
        'manual_relationship_id',
        'selector_relationship_id',
    ];

    protected $model_accessor_whitelist = [
        'activity',
        'manual_relationship',
        'selector_relationship'
    ];

    /**
     * {@inheritdoc}
     */
    protected static function get_entity_class(): string {
        return manual_relationship_selection::class;
    }

    /**
     * Get the manual relationship.
     *
     * @return relationship
     */
    public function get_manual_relationship(): relationship {
        return relationship::load_by_entity($this->entity->participant_relationship);
    }

    /**
     * Get the selector relationship. i.e relation that selects the user list.
     *
     * @return relationship
     */
    public function get_selector_relationship(): relationship {
        return relationship::load_by_entity($this->entity->selector_relationship);
    }

    /**
     * Get the activity.
     *
     * @return activity
     */
    public function get_activity(): activity {
        return activity::load_by_entity($this->entity->activity);
    }

    /**
     * Update the selector relationship. i.e relation that selects the user list.
     *
     * @param int $relationship_id
     * @return bool
     */
    public function update_selector_relationship(int $relationship_id): bool {
        return (int)$this->selector_relationship_id !== $relationship_id
            ? (bool)$this->entity->set_attribute('selector_relationship_id', $relationship_id)->update()
            : false;
    }
}
